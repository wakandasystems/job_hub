<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobCrawler;
use Botble\JobBoard\Services\JobCrawlerRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FixCrawledJobCategoriesCommand extends Command
{
    protected $signature = 'job-board:fix-crawled-categories {crawler? : Crawler ID (defaults to all GoZambia crawlers)}';

    protected $description = 'Backfill correct category assignments for all existing GoZambia crawled jobs';

    public function handle(JobCrawlerRunner $runner): int
    {
        $crawlers = JobCrawler::query()
            ->where('parser_type', 'gozambiajobs')
            ->when($this->argument('crawler'), fn ($q, $id) => $q->whereKey($id))
            ->get();

        if ($crawlers->isEmpty()) {
            $this->components->warn('No GoZambia crawlers found.');
            return 0;
        }

        foreach ($crawlers as $crawler) {
            $this->components->info("Processing crawler: {$crawler->name}");
            $this->fixCrawler($runner, $crawler);
        }

        return 0;
    }

    protected function fixCrawler(JobCrawlerRunner $runner, JobCrawler $crawler): void
    {
        $jobs = [];
        $seenIds = [];

        // Scan all pages — no early stop, we need everything.
        for ($page = 1; $page <= 20; $page++) {
            if ($page > 1) {
                usleep(500_000);
            }

            $url = $this->buildPageUrl($crawler->source_url, $page);
            $this->line("  Scanning page {$page}…");

            try {
                $response = Http::timeout(12)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; WakandaJobsCrawler/1.0)',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    ])
                    ->get($url);

                if ($response->notFound()) {
                    break;
                }

                $html = $response->body();

                if (str_contains($html, "Sorry, we couldn't find any matches for your search.")) {
                    break;
                }

                $pageJobs = $runner->extractGoZambiaJobsList($html);
                if (empty($pageJobs)) {
                    break;
                }

                foreach ($pageJobs as $job) {
                    $id = (string) data_get($job, 'id');
                    if ($id !== '' && isset($seenIds[$id])) {
                        continue;
                    }
                    if ($id !== '') {
                        $seenIds[$id] = true;
                    }
                    $jobs[] = $job;
                }
            } catch (\Throwable) {
                $this->components->warn("  Page {$page} failed — skipping.");
            }
        }

        $this->line("  Found " . count($jobs) . " jobs on GoZambia.");

        $updated = 0;
        $skipped = 0;

        foreach ($jobs as $item) {
            $sourceId = (string) data_get($item, 'id');
            if ($sourceId === '') {
                continue;
            }

            $job = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $sourceId)
                ->first();

            if (! $job) {
                $skipped++;
                continue;
            }

            $category = $runner->resolveGoZambiaCategory((string) data_get($item, 'category.name'));
            $job->categories()->sync($category ? [$category->id] : []);
            $updated++;
        }

        // Unpublish any published job in our DB that GoZambia no longer lists.
        $scannedIds = array_filter(array_column($jobs, 'id'), fn ($id) => $id !== '');
        $unpublished = \Botble\JobBoard\Models\Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->where('status', 'published')
            ->whereNotNull('external_source_id')
            ->whereNotIn('external_source_id', array_map('strval', $scannedIds))
            ->update(['status' => 'draft']);

        $this->components->info("  Done — {$updated} categories fixed, {$unpublished} jobs unpublished, {$skipped} not in DB.");
    }

    protected function buildPageUrl(string $sourceUrl, int $page): string
    {
        $parts = parse_url($sourceUrl);
        parse_str($parts['query'] ?? '', $query);
        $query['page'] = $page;

        return sprintf(
            '%s://%s%s?%s',
            $parts['scheme'] ?? 'https',
            $parts['host'] ?? 'gozambiajobs.com',
            $parts['path'] ?? '/jobs',
            http_build_query($query)
        );
    }
}
