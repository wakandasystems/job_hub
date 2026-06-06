<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\JobCrawlerRunner;
use Botble\Media\Facades\RvMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class BackfillJobSearchZmLogosCommand extends Command
{
    protected $signature = 'job-board:backfill-jobsearchzm-logos
                            {--dry-run : Show what would be fetched without saving}
                            {--company= : Backfill a single company ID}';

    protected $description = 'Backfill company logos for jobs crawled from JobSearchZM.';

    /** JobSearchZM crawler ID */
    private const CRAWLER_ID = 60;

    public function handle(): int
    {
        $dryRun    = (bool) $this->option('dry-run');
        $companyId = $this->option('company');

        if ($dryRun) {
            $this->components->warn('DRY RUN — no logos will be saved.');
        }

        $query = Company::query()
            ->whereIn('id', function ($q): void {
                $q->select('company_id')
                    ->from('jb_jobs')
                    ->where('crawler_id', self::CRAWLER_ID)
                    ->distinct();
            })
            ->where(function ($q): void {
                $q->whereNull('logo')->orWhere('logo', '');
            });

        if ($companyId) {
            $query->where('id', (int) $companyId);
        }

        $companies = $query->get();
        $total     = $companies->count();

        $this->components->info("Companies to backfill: {$total}");

        if ($total === 0) {
            $this->components->info('Nothing to do.');
            return self::SUCCESS;
        }

        $bar     = $this->output->createProgressBar($total);
        $updated = 0;
        $skipped = 0;
        $failed  = 0;

        $bar->start();

        foreach ($companies as $company) {
            $bar->advance();

            // Pick the most recently crawled job for this company to use its detail URL.
            $job = Job::query()
                ->where('crawler_id', self::CRAWLER_ID)
                ->where('company_id', $company->id)
                ->whereNotNull('external_source_url')
                ->where('external_source_url', '!=', '')
                ->latest('id')
                ->first();

            if (! $job) {
                $skipped++;
                continue;
            }

            try {
                $logo = $this->fetchLogoFromPage((string) $job->external_source_url);
            } catch (Throwable) {
                $failed++;
                continue;
            }

            if (! $logo || $this->isPlaceholderLogo($logo)) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->newLine();
                $this->line("  [{$company->id}] {$company->name} → {$logo}");
                $updated++;
                continue;
            }

            $logoPath = $this->uploadLogo($logo);
            if (! $logoPath) {
                $failed++;
                continue;
            }

            $company->logo = $logoPath;
            $company->save();
            $updated++;

            usleep(300_000); // 0.3 s between requests — polite throttle
        }

        $bar->finish();
        $this->newLine();

        $this->table(
            ['Result', 'Count'],
            [
                ['Logos ' . ($dryRun ? 'found' : 'updated'), $updated],
                ['No logo on source page',                    $skipped],
                ['Fetch/upload error',                        $failed],
            ]
        );

        return self::SUCCESS;
    }

    private function isPlaceholderLogo(string $url): bool
    {
        // JobSearchZM uses this image when no real company logo is provided.
        return str_contains($url, 'cropped-Job-Search-Zambia');
    }

    private function fetchLogoFromPage(string $url): ?string
    {
        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'WakandaJobsCrawler/1.0 (+https://www.wakandajobs.com)'])
            ->get($url);

        if (! $response->successful()) {
            return null;
        }

        $html = $response->body();

        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(html_entity_decode(trim($json), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (! is_array($decoded)) {
                continue;
            }

            foreach ($this->flatten($decoded) as $entry) {
                if (($entry['@type'] ?? null) !== 'JobPosting') {
                    continue;
                }
                $logo = (string) data_get($entry, 'hiringOrganization.logo', '');
                if ($logo !== '') {
                    return $logo;
                }
            }
        }

        return null;
    }

    private function flatten(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = array_is_list($value) ? $value : [$value];
        $flat  = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $flat[] = $item;
            if (isset($item['@graph'])) {
                $flat = array_merge($flat, $this->flatten($item['@graph']));
            }
        }

        return $flat;
    }

    private function uploadLogo(string $url): ?string
    {
        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg';
        $path      = sys_get_temp_dir() . '/jszm-logo-' . uniqid() . '.' . preg_replace('/[^a-z0-9]/i', '', $extension);

        try {
            $response = Http::withoutVerifying()->timeout(12)->get($url);

            if ($response->failed() || ! $response->body()) {
                return null;
            }

            file_put_contents($path, $response->body());

            $result = RvMedia::uploadFromPath($path, 0, 'companies', $response->header('Content-Type'));
        } catch (Throwable) {
            return null;
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (($result['error'] ?? true) || empty($result['data'])) {
            return null;
        }

        return $result['data']->url ?? null;
    }
}
