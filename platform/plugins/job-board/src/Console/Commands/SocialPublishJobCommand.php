<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SocialPublishJobCommand extends Command
{
    protected $signature = 'job-board:social-publish {jobId : The Job ID to publish to social platforms}';

    protected $description = 'Post a job to all enabled social media automations';

    public function handle(SocialPublisherService $publisher): int
    {
        $job = Job::find($this->argument('jobId'));

        if (! $job) {
            return 1;
        }

        // This command runs detached (`exec ... > /dev/null 2>&1 &`), so console output is
        // discarded — an uncaught exception here would otherwise fail completely silently.
        try {
            $results = $publisher->publishJob($job);
        } catch (Throwable $e) {
            Log::error('publishJob crashed — employer pitch never attempted', [
                'job_id' => $job->getKey(),
                'error'  => $e->getMessage(),
            ]);

            return 1;
        }

        foreach ($results as $r) {
            $automation = $r['automation'] ?? 'Social automation';
            $platform = $r['platform'] ?? 'unknown';

            if ($r['success']) {
                $this->components->info("{$automation} ({$platform}): posted successfully.");
            } else {
                $this->components->warn("{$automation} ({$platform}): failed — " . ($r['error'] ?? 'unknown'));
            }
        }

        // After the image is generated and the job is posted, pitch the external employer
        // by email — throttled to one email per employer per day (handled in the service).
        try {
            $pitch = $publisher->autoPitchEmployerEmail($job->refresh(), $results);
        } catch (Throwable $e) {
            Log::error('autoPitchEmployerEmail crashed', [
                'job_id' => $job->getKey(),
                'error'  => $e->getMessage(),
            ]);

            return 0;
        }

        // Logged (not just console-printed) so the outcome is visible even though this
        // command runs detached with its output discarded.
        Log::info('Employer pitch evaluated', [
            'job_id'       => $job->getKey(),
            'results_count' => count($results),
            'any_posted'   => collect($results)->contains(fn ($r) => ($r['success'] ?? false) === true),
            'pitch'        => $pitch,
        ]);

        if ($pitch['sent'] ?? false) {
            $this->components->info('Employer pitch email sent to ' . $pitch['to'] . '.');
        } else {
            $this->components->info('Employer pitch skipped: ' . ($pitch['reason'] ?? 'unknown') . '.');
        }

        return 0;
    }
}
