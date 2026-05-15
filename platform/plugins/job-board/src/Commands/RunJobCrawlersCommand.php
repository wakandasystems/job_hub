<?php

namespace Botble\JobBoard\Commands;

use Botble\JobBoard\Models\JobCrawler;
use Botble\JobBoard\Services\JobCrawlerRunner;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('cms:jobs:crawl', 'Run job board crawler agents')]
class RunJobCrawlersCommand extends Command
{
    protected $signature = 'cms:jobs:crawl {crawler? : Optional crawler ID} {--all : Run all active crawlers even when they are not due}';

    public function handle(JobCrawlerRunner $runner): int
    {
        $query = JobCrawler::query()->where('is_active', true);

        if ($crawlerId = $this->argument('crawler')) {
            $query->whereKey($crawlerId);
        } elseif (! $this->option('all')) {
            $query->where(function ($query): void {
                $query
                    ->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', Carbon::now());
            });
        }

        $crawlers = $query->get();

        if ($crawlers->isEmpty()) {
            $this->components->info('No crawler agents are due.');

            return self::SUCCESS;
        }

        foreach ($crawlers as $crawler) {
            if ($runningRun = $crawler->runningRun()) {
                $this->components->info(sprintf(
                    'Skipping crawler agent %s because run #%d is still running.',
                    $crawler->name,
                    $runningRun->id
                ));

                continue;
            }

            $this->components->info(sprintf('Running crawler agent: %s', $crawler->name));

            // --all disables the early-stop so we scan every page and can detect removed jobs.
            $runner->disableEarlyStop = $this->option('all');
            $run = $runner->run($crawler);
            $crawler->next_run_at = $this->nextRunAt($crawler);
            $crawler->save();

            $this->components->info(sprintf(
                'Run #%d finished with status %s. Found: %d, created: %d, updated: %d, skipped: %d.',
                $run->id,
                $run->status,
                $run->jobs_found,
                $run->jobs_created,
                $run->jobs_updated,
                $run->jobs_skipped
            ));
        }

        return self::SUCCESS;
    }

    protected function nextRunAt(JobCrawler $crawler): ?Carbon
    {
        return Carbon::now()->addMinutes($crawler->scheduleIntervalMinutes());
    }
}
