<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\JobCrawlerRun;
use Botble\JobBoard\Services\JobCrawlerRunner;
use Illuminate\Console\Command;
use Throwable;

class CrawlRunCommand extends Command
{
    protected $signature = 'job-board:crawl-run {runId : The JobCrawlerRun ID to execute}';

    protected $description = 'Execute a queued crawler run in the background';

    public function handle(JobCrawlerRunner $runner): int
    {
        $run = JobCrawlerRun::find($this->argument('runId'));

        if (! $run || $run->status !== 'running') {
            $this->error('Run not found or not in running state.');

            return 1;
        }

        $crawler = $run->crawler;

        if (! $crawler || ! $crawler->exists) {
            $run->fill(['status' => 'failed', 'error_message' => 'Crawler not found.'])->save();

            return 1;
        }

        try {
            $runner->executeRun($crawler, $run);
        } catch (Throwable) {
            return 1;
        }

        return 0;
    }
}
