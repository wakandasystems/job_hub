<?php

namespace App\Console\Commands;

use Composer\Autoload\ClassLoader;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SocialPublishJob extends Command
{
    protected $signature = 'job-board:social-publish {jobId : The Job ID to publish to social platforms}';

    protected $description = 'Post a job to all enabled social media automations';

    public function handle(): int
    {
        $this->loadJobBoardNamespace();

        $job = $this->findJob();

        if (! $job) {
            return 1;
        }

        $results = app(\Botble\JobBoard\Services\SocialPublisherService::class)->publishJob($job);

        foreach ($results as $result) {
            $automation = $result['automation'] ?? 'Social automation';
            $platform = $result['platform'] ?? 'unknown';

            if ($result['success']) {
                $this->components->info("{$automation} ({$platform}): posted successfully.");
            } else {
                $this->components->warn("{$automation} ({$platform}): failed - " . ($result['error'] ?? 'unknown'));
            }
        }

        return 0;
    }

    private function loadJobBoardNamespace(): void
    {
        if (class_exists(\Botble\JobBoard\Models\Job::class)) {
            return;
        }

        $loader = new ClassLoader();
        $loader->setPsr4('Botble\\JobBoard\\', base_path('platform/plugins/job-board/src'));
        $loader->register(true);
    }

    private function findJob(): ?\Botble\JobBoard\Models\Job
    {
        try {
            return \Botble\JobBoard\Models\Job::find($this->argument('jobId'));
        } catch (QueryException $exception) {
            if ((int) $exception->getCode() !== 2002) {
                throw $exception;
            }

            DB::purge('mysql');
            config([
                'database.connections.mysql.host' => 'localhost',
                'database.connections.mysql.unix_socket' => '/var/run/mysqld/mysqld.sock',
            ]);

            return \Botble\JobBoard\Models\Job::find($this->argument('jobId'));
        }
    }
}
