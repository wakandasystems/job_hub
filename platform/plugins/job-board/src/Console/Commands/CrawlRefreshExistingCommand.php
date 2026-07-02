<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobCrawlerRun;
use Botble\JobBoard\Services\JobCrawlerRunner;
use Illuminate\Console\Command;
use Throwable;

class CrawlRefreshExistingCommand extends Command
{
    protected $signature = 'job-board:crawl-refresh {runId : The JobCrawlerRun ID whose existing jobs should be refreshed}';

    protected $description = 'Background: fetch detail pages for existing crawled jobs and update if content changed';

    public function handle(JobCrawlerRunner $runner): int
    {
        $run = JobCrawlerRun::find($this->argument('runId'));

        if (! $run) {
            return 1;
        }

        $map = (array) data_get($run->meta, 'existing_to_refresh', []);
        if (empty($map)) {
            return 0;
        }

        $total = count($map);
        $checked = 0;
        $updated = 0;

        $this->updateRunMeta($run, ['bg_status' => 'running', 'bg_checked' => 0, 'bg_updated' => 0, 'bg_total' => $total]);

        foreach ($map as $sourceId => $detailPath) {
            $sourceId = (string) $sourceId;
            $detailUrl = $this->absoluteUrl($detailPath);

            if (! $detailUrl) {
                $checked++;
                continue;
            }

            try {
                $response = \Illuminate\Support\Facades\Http::timeout(12)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; WakandaJobsCrawler/1.0)',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    ])
                    ->get($detailUrl);

                if (! $response->successful()) {
                    $checked++;
                    continue;
                }

                $detail = $runner->extractGoZambiaDetailJob($response->body());
                if (! $detail) {
                    $checked++;
                    continue;
                }

                $job = Job::query()
                    ->where('crawler_id', $run->crawler_id)
                    ->where('external_source_id', $sourceId)
                    ->first();

                if (! $job) {
                    $checked++;
                    continue;
                }

                $newContent = $this->sanitize((string) data_get($detail, 'description'));
                $newApplyUrl = $this->normalizeApplyTarget((string) data_get($detail, 'apply_to'));

                $contentChanged = $newContent && md5($newContent) !== md5((string) $job->content);
                $applyChanged = $newApplyUrl && $newApplyUrl !== $job->apply_url;

                if ($contentChanged || $applyChanged) {
                    $patch = [];
                    if ($contentChanged) {
                        $patch['content'] = $newContent;
                        $patch['description'] = \Illuminate\Support\Str::limit(trim(strip_tags($newContent)), 400, '');
                    }
                    if ($applyChanged) {
                        $patch['apply_url'] = $newApplyUrl;
                    }
                    $job->fill($patch);
                }

                $runner->resolveApplyContact($job);

                if ($job->isDirty()) {
                    $job->save();
                    $updated++;
                }
            } catch (Throwable) {
                // Skip individual failures silently.
            }

            $checked++;

            if ($checked % 10 === 0 || $checked === $total) {
                $this->updateRunMeta($run, ['bg_checked' => $checked, 'bg_updated' => $updated]);
            }
        }

        $this->updateRunMeta($run, [
            'bg_status' => 'completed',
            'bg_checked' => $checked,
            'bg_updated' => $updated,
        ]);

        return 0;
    }

    protected function updateRunMeta(JobCrawlerRun $run, array $fields): void
    {
        $run->refresh();
        $run->meta = array_merge($run->meta ?? [], $fields);
        $run->saveQuietly();
    }

    protected function sanitize(string $html): string
    {
        return preg_replace('/<img\b[^>]*\bsrc=(["\'])data:[^"\']+\1[^>]*>/i', '', $html) ?: $html;
    }

    protected function normalizeApplyTarget(string $target): ?string
    {
        $target = trim($target);
        if ($target === '') {
            return null;
        }

        if (filter_var($target, FILTER_VALIDATE_EMAIL)) {
            return 'mailto:' . $target;
        }

        return $target;
    }

    protected function absoluteUrl(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (\Illuminate\Support\Str::startsWith($path, '//')) {
            return 'https:' . $path;
        }

        return 'https://gozambiajobs.com/' . ltrim($path, '/');
    }
}
