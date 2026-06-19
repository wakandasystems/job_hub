<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RetryPublerPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public int $backoff = 120;

    public function __construct(
        public int $jobId,
        public int $automationId,
        public ?string $preferredImageField = null,
        public array $excludeNetworks = [],
    ) {
        $this->onQueue('publer');
    }

    public static function retryCacheKey(int $jobId, int $automationId, ?string $preferredImageField = null, array $excludeNetworks = []): string
    {
        return 'job-board:publer-retry:'
            . $jobId . ':'
            . $automationId;
    }

    public function handle(SocialPublisherService $publisher): void
    {
        $job = Job::query()->find($this->jobId);
        $automation = SocialAutomation::query()
            ->where('platform', 'publer')
            ->where('is_active', true)
            ->find($this->automationId);

        if (! $job || ! $automation) {
            Cache::forget($this->cacheKey());

            return;
        }

        $settings = $automation->settings ?? [];
        $countryId = $settings['country_id'] ?? null;
        if ($countryId && (int) $countryId !== (int) $job->country_id) {
            Cache::forget($this->cacheKey());

            return;
        }

        $apiKey = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            $apiKey = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
        }

        $accountIds = array_values(array_filter((array) ($settings['account_ids'] ?? [])));
        $workspaceId = trim((string) ($settings['workspace_id'] ?? ''));

        if ($apiKey === '' || empty($accountIds)) {
            Cache::forget($this->cacheKey());

            return;
        }

        $success = $publisher->publerPost(
            $job,
            $apiKey,
            $accountIds,
            $workspaceId,
            $this->preferredImageField,
            $this->excludeNetworks,
        );

        if ($success) {
            Cache::forget($this->cacheKey());

            return;
        }

        if (! $success) {
            $error = (string) $publisher->getLastPublerError();

            if ($this->isPublerMinimumGapError($error) && $this->attempts() < $this->tries) {
                Log::info('Publer retry delayed because provider requires a posting gap', [
                    'job_id' => $job->getKey(),
                    'automation_id' => $automation->getKey(),
                    'attempt' => $this->attempts(),
                    'error' => $error,
                ]);

                $this->release(120);

                return;
            }

            if ($this->isTikTokDailyApiLimitError($error) && $this->attempts() < $this->tries) {
                $retryAt = $this->nextTikTokRetryWindow();

                Log::info('Publer retry delayed because TikTok OpenAPI daily limit is active', [
                    'job_id' => $job->getKey(),
                    'automation_id' => $automation->getKey(),
                    'attempt' => $this->attempts(),
                    'retry_at' => $retryAt->toDateTimeString(),
                    'error' => $error,
                ]);

                $this->release((int) now()->diffInSeconds($retryAt));

                return;
            }

            Log::warning('Background Publer retry failed', [
                'job_id' => $job->getKey(),
                'automation_id' => $automation->getKey(),
                'error' => $error,
            ]);

            Cache::forget($this->cacheKey());
        }
    }

    private function isPublerMinimumGapError(string $error): bool
    {
        $error = strtolower($error);

        return str_contains($error, 'one minute gap')
            || str_contains($error, 'another post at this time');
    }

    private function isTikTokDailyApiLimitError(string $error): bool
    {
        $error = strtolower($error);

        return str_contains($error, 'too many posts via openapi')
            || str_contains($error, 'last 24 hours');
    }

    private function nextTikTokRetryWindow(): \Illuminate\Support\Carbon
    {
        $retryAt = now()->addDay()->setTime(1, 0);

        if ($retryAt->lessThanOrEqualTo(now()->addMinutes(5))) {
            $retryAt->addDay();
        }

        return $retryAt;
    }

    private function cacheKey(): string
    {
        return self::retryCacheKey(
            $this->jobId,
            $this->automationId,
            $this->preferredImageField,
            $this->excludeNetworks,
        );
    }
}
