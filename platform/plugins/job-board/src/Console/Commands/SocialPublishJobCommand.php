<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Console\Command;

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

        $results = $publisher->publishJob($job);

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
        $pitch = $publisher->autoPitchEmployerEmail($job->refresh(), $results);
        if ($pitch['sent'] ?? false) {
            $this->components->info('Employer pitch email sent to ' . $pitch['to'] . '.');
        } else {
            $this->components->info('Employer pitch skipped: ' . ($pitch['reason'] ?? 'unknown') . '.');
        }

        return 0;
    }
}
