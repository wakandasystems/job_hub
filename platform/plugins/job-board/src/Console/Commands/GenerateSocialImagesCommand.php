<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Http\Controllers\Settings\AiImageSettingController;
use Botble\JobBoard\Jobs\GenerateSocialImagesJob;
use Botble\JobBoard\Jobs\SendPushNotificationsJob;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\OpenAiImageService;
use Illuminate\Console\Command;

class GenerateSocialImagesCommand extends Command
{
    protected $signature = 'job-board:generate-social-images {jobId : The Job ID to generate social images for}
        {--force : Ignore the country/logo/multi-position gates and the master toggle}
        {--publish : Post the job to social channels once image generation has finished}
        {--attempt=1 : Internal retry counter for the rate-limit deferral loop}';

    protected $description = 'Generate social media images for a job via OpenAI (gpt-image-1)';

    /** Maximum number of deferral cycles before we give up and publish with whatever exists. */
    private const MAX_ATTEMPTS = 4;

    /** Cooldown (seconds) before each deferred retry, indexed by attempt number. */
    private const DEFER_COOLDOWNS = [1 => 180, 2 => 420, 3 => 900];

    /** Slot types that came back rate-limited (429) and are still missing an image. */
    private array $rateLimitedSlots = [];

    /** Did this job pass the AI image gates (so SendPushNotificationListener deferred to us)? */
    private bool $jobQualifiesForImage = false;

    public function handle(OpenAiImageService $service): int
    {
        $job = Job::with(['company', 'slugable', 'country', 'currency', 'jobTypes'])->find($this->argument('jobId'));

        if (! $job) {
            $this->components->error('Job not found.');

            return self::FAILURE;
        }

        // Image generation is best-effort — never let a failure here block publishing.
        try {
            $this->generateImages($job, $service);
        } catch (\Throwable $e) {
            $this->components->error('Image generation failed: ' . $e->getMessage());
        }

        if (! $this->option('publish')) {
            return self::SUCCESS;
        }

        $attempt = max(1, (int) $this->option('attempt'));

        // If OpenAI rate-limited us and the image is still missing, DON'T post image-less.
        // Pause and reschedule the whole generate+publish step for after the limit cools,
        // so the social posts (and the employer email) go out WITH the image. After
        // MAX_ATTEMPTS we give up waiting and publish with whatever images we have.
        if (! empty($this->rateLimitedSlots) && $attempt < self::MAX_ATTEMPTS) {
            $this->deferPublish($job, $attempt);

            return self::SUCCESS;
        }

        if (! empty($this->rateLimitedSlots)) {
            $this->components->warn(
                'Rate limit persisted after ' . self::MAX_ATTEMPTS . ' attempts — publishing with available images.'
            );
        }

        // Post to social channels only AFTER image generation has finished, so the freshly
        // saved image is attached instead of the channel post racing ahead text-only.
        $this->call('job-board:social-publish', ['jobId' => $job->getKey()]);

        // Same reasoning for push: this job qualified for an AI image, so
        // SendPushNotificationListener deferred to us. Send it now that generation has
        // concluded (success or rate-limit-exhausted), so the push carries the image.
        if ($this->jobQualifiesForImage) {
            SendPushNotificationsJob::dispatch($job->getKey());
        }

        return self::SUCCESS;
    }

    /**
     * Reschedule this command to run again after a cooldown, so a transient OpenAI rate
     * limit has time to reset before we regenerate the missing image(s) and then publish.
     * Dispatches a delayed queue job (Horizon-backed, so it survives PHP-FPM worker
     * recycling — unlike the detached `sleep N; exec(...) &` this used to be); the cost
     * guard in generateImages() skips slots that already succeeded, so retries only
     * re-request the still-missing images.
     */
    private function deferPublish(Job $job, int $attempt): void
    {
        $cooldown = self::DEFER_COOLDOWNS[$attempt] ?? 900;

        $this->components->warn(sprintf(
            'Rate limited on: %s. Deferring publish ~%ds (attempt %d/%d).',
            implode(', ', $this->rateLimitedSlots),
            $cooldown,
            $attempt,
            self::MAX_ATTEMPTS
        ));

        GenerateSocialImagesJob::dispatch($job->getKey(), true, $attempt + 1)
            ->delay(now()->addSeconds($cooldown));
    }

    private function generateImages(Job $job, OpenAiImageService $service): void
    {
        $force = (bool) $this->option('force');

        if (! $force && ! setting('ai_social_image_enabled')) {
            $this->components->warn('Auto-generation is disabled in settings.');

            return;
        }

        if (! $service->isConfigured()) {
            $this->components->error('OpenAI API key is not configured.');

            return;
        }

        if (! $force && ! $this->passesGates($job, $service)) {
            return;
        }

        // Gates passed (or bypassed via --force) — SendPushNotificationListener held off
        // pushing this job, so we send it ourselves once generation below finishes.
        $this->jobQualifiesForImage = true;

        $platforms = $force ? array_keys(AiImageSettingController::PLATFORMS) : $this->enabledPlatforms();
        if (empty($platforms)) {
            $this->components->warn('No platforms enabled for generation.');

            return;
        }

        foreach ($platforms as $type) {
            // Skip slots that already have an image (avoid re-spending on regeneration).
            if (! empty($job->{$type})) {
                $this->components->info("{$type}: already present — skipped.");

                continue;
            }

            $result = $this->generateSlotWithShortRetry($job, $type, $service);

            if ($result['ok'] ?? false) {
                $this->components->info("{$type}: generated → " . ($result['path'] ?? ''));
            } elseif ($result['rate_limited'] ?? false) {
                // Short in-process retries didn't clear the limit — flag for deferral.
                $this->rateLimitedSlots[] = $type;
                $this->components->warn("{$type}: rate limited — will retry after cooldown.");
            } else {
                $this->components->warn("{$type}: failed — " . ($result['message'] ?? 'unknown error'));
            }
        }

        $fallbackChanges = $service->applyPlatformFallbacks($job, $platforms);
        foreach ($fallbackChanges as $target => $source) {
            $this->components->info("{$target}: reused {$source}.");
        }
    }

    /**
     * Generate one slot, retrying briefly in-process if OpenAI returns a 429. Short bursts
     * of rate limiting (the common case) clear within seconds, so a couple of bounded waits
     * here avoid a full deferral cycle. If it's still limited after these retries, the
     * caller defers the whole publish to a later run.
     */
    private function generateSlotWithShortRetry(Job $job, string $type, OpenAiImageService $service): array
    {
        $maxInline = 2;          // quick retries before falling back to a deferral
        $capSeconds = 60;        // never block a single slot longer than this per wait

        for ($i = 0; ; $i++) {
            $result = $service->generateForJob($job, $type);

            if (($result['ok'] ?? false) || ! ($result['rate_limited'] ?? false) || $i >= $maxInline) {
                return $result;
            }

            $wait = (int) ($result['retry_after'] ?? 0);
            $wait = max(5, min($wait ?: 15, $capSeconds));
            $this->components->warn("{$type}: rate limited, waiting {$wait}s (inline retry " . ($i + 1) . "/{$maxInline})…");
            sleep($wait);
        }
    }

    private function passesGates(Job $job, OpenAiImageService $service): bool
    {
        if (! $service->qualifiesForJob($job)) {
            $this->components->info('Job does not pass the country/logo/multi-position gates — skipped.');

            return false;
        }

        return true;
    }

    private function enabledPlatforms(): array
    {
        $platforms = json_decode((string) setting('ai_social_image_platforms', '[]'), true) ?: [];

        return array_values(array_intersect(array_keys(AiImageSettingController::PLATFORMS), $platforms));
    }
}
