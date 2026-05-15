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
            $this->components->info(sprintf('Running crawler agent: %s', $crawler->name));

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
        $schedule = strtolower(trim((string) $crawler->schedule));

        return match (true) {
            str_contains($schedule, '5') && str_contains($schedule, 'minute') => Carbon::now()->addMinutes(5),
            str_contains($schedule, '15') && str_contains($schedule, 'minute') => Carbon::now()->addMinutes(15),
            str_contains($schedule, '30') && str_contains($schedule, 'minute') => Carbon::now()->addMinutes(30),
            str_contains($schedule, 'hour') => Carbon::now()->addHour(),
            str_contains($schedule, 'day') || str_contains($schedule, 'daily') => Carbon::now()->addDay(),
            default => Carbon::now()->addHour(),
        };
    }
}
