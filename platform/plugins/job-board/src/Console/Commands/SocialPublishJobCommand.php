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
            if ($r['success']) {
                $this->components->info("{$r['automation']} ({$r['platform']}): posted successfully.");
            } else {
                $this->components->warn("{$r['automation']} ({$r['platform']}): failed — " . ($r['error'] ?? 'unknown'));
            }
        }

        return 0;
    }
}
