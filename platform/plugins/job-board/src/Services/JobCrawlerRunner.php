<?php

namespace Botble\JobBoard\Services;

use Botble\Base\Events\CreatedContentEvent;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Enums\ModerationStatusEnum;
use Botble\JobBoard\Enums\SalaryRangeEnum;
use Botble\JobBoard\Enums\SalaryTypeEnum;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Currency;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobCrawler;
use Botble\JobBoard\Models\JobCrawlerRun;
use Botble\Media\Facades\RvMedia;
use Botble\Slug\Facades\SlugHelper;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Throwable;

class JobCrawlerRunner
{
    protected ?JobCrawlerRun $currentRun = null;

    /** Pre-loaded existing external_source_ids for the current GoZambia crawl (keyed for O(1) lookup). */
    protected array $goZambiaExistingIds = [];

    /** Per-run cache of resolved Category models keyed by normalised name. */
    protected array $categoryCache = [];

    /** True when the GoZambia scan reached GoZambia's natural end (not early-stopped). */
    protected bool $goZambiaFullScan = false;

    /** When true, skip the early-stop optimisation and scan every GoZambia page. */
    public bool $disableEarlyStop = false;

    public function run(JobCrawler $crawler): JobCrawlerRun
    {
        $run = JobCrawlerRun::query()->create([
            'crawler_id' => $crawler->getKey(),
            'status' => 'running',
            'started_at' => Carbon::now(),
            'meta' => ['stage' => 'scanning', 'current_page' => 0, 'total_pages' => 20, 'jobs_found_so_far' => 0],
        ]);

        $this->executeRun($crawler, $run);

        return $run;
    }

    public function executeRun(JobCrawler $crawler, JobCrawlerRun $run): void
    {
        $this->currentRun          = $run;
        $this->categoryCache       = [];
        $this->goZambiaExistingIds = [];
        $this->goZambiaFullScan    = false;

        try {
            $items = $this->fetchItems($crawler);
            $stats = $this->importItems($crawler, $items);

            $run->fill([
                'status' => 'success',
                'finished_at' => Carbon::now(),
                'meta' => array_merge($run->meta ?? [], ['stage' => 'completed']),
                ...$stats,
            ])->save();

            $crawler->fill([
                'last_run_at' => $run->started_at,
                'last_status' => 'success',
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $run->fill([
                'status' => 'failed',
                'finished_at' => Carbon::now(),
                'error_message' => $exception->getMessage(),
                'error_trace' => $exception->getTraceAsString(),
            ])->save();

            $crawler->fill([
                'last_run_at' => $run->started_at,
                'last_status' => 'failed',
                'last_error' => $exception->getMessage(),
            ])->save();

            throw $exception;
        } finally {
            $this->currentRun = null;
        }
    }

    protected function saveMeta(array $fields): void
    {
        if (! $this->currentRun) {
            return;
        }

        $this->currentRun->meta = array_merge($this->currentRun->meta ?? [], $fields);
        $this->currentRun->saveQuietly();
    }

    protected function saveProgress(int $page, int $jobsFoundSoFar, int $newFoundSoFar = 0): void
    {
        $this->saveMeta([
            'stage' => 'scanning',
            'current_page' => $page,
            'jobs_found_so_far' => $jobsFoundSoFar,
            'new_found_so_far' => $newFoundSoFar,
        ]);
    }

    protected function saveNewImportProgress(int $current, int $total, array $stats = []): void
    {
        $this->saveMeta([
            'stage' => 'importing_new',
            'new_current' => $current,
            'new_total' => $total,
            'jobs_created' => $stats['jobs_created'] ?? 0,
            'jobs_skipped' => $stats['jobs_skipped'] ?? 0,
        ]);
    }

    protected function saveExistingUpdateProgress(int $current, int $total, array $stats = []): void
    {
        $this->saveMeta([
            'stage' => 'updating_existing',
            'existing_current' => $current,
            'existing_total' => $total,
            'jobs_updated' => $stats['jobs_updated'] ?? 0,
        ]);
    }

    protected function fetchItems(JobCrawler $crawler): array
    {
        if ($crawler->parser_type === 'gozambiajobs') {
            return $this->fetchGoZambiaJobs($crawler);
        }

        $sourceUrl = (string) $crawler->source_url;
        $paginated = str_contains($sourceUrl, '{page}');

        if ($crawler->parser_type === 'json') {
            $path = trim((string) $crawler->item_selector);
            $all = [];

            for ($page = 1; $page <= 20; $page++) {
                $url = $paginated ? str_replace('{page}', (string) $page, $sourceUrl) : $sourceUrl;

                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'WakandaJobsCrawler/1.0'])
                    ->get($url);

                $response->throw();

                $payload = $response->json();
                $items = $path === '' ? Arr::wrap($payload) : Arr::wrap(data_get($payload, $path, []));

                if (empty($items)) {
                    break;
                }

                $all = array_merge($all, $items);
                $this->saveProgress($page, count($all));

                if (! $paginated) {
                    break;
                }
            }

            return $all;
        }

        $selector = trim((string) $crawler->item_selector);
        if ($selector === '') {
            throw new \RuntimeException('HTML crawlers require an item selector.');
        }

        $all = [];

        for ($page = 1; $page <= 20; $page++) {
            $url = $paginated ? str_replace('{page}', (string) $page, $sourceUrl) : $sourceUrl;

            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'WakandaJobsCrawler/1.0'])
                ->get($url);

            $response->throw();

            $dom = new DomCrawler($response->body(), $url);
            $items = $dom->filter($selector)->each(fn (DomCrawler $node) => $node);

            if (empty($items)) {
                break;
            }

            $all = array_merge($all, $items);
            $this->saveProgress($page, count($all));

            if (! $paginated) {
                break;
            }
        }

        return $all;
    }

    /**
     * Find or create a category matching the GoZambia category name, with deduplication.
     * Returns null for "Other" or empty names — those jobs won't be force-assigned a category.
     */
    public function resolveGoZambiaCategory(string $rawName): ?\Botble\JobBoard\Models\Category
    {
        $name = trim(html_entity_decode($rawName, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($name === '' || strtolower($name) === 'other') {
            return null;
        }

        $key = mb_strtolower($name);

        if (array_key_exists($key, $this->categoryCache)) {
            return $this->categoryCache[$key];
        }

        $category = \Botble\JobBoard\Models\Category::query()
            ->whereRaw('LOWER(name) = ? OR LOWER(name) = ?', [
                $key,
                mb_strtolower(htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            ])
            ->first();

        if (! $category) {
            $category = \Botble\JobBoard\Models\Category::query()->create([
                'name'   => $name,
                'status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED,
            ]);
            SlugHelper::createSlug($category);
            $this->assignCategoryIcon($category, $name);
        } elseif (! $category->getMetaData('icon_image', true)) {
            $this->assignCategoryIcon($category, $name);
        }

        return $this->categoryCache[$key] = $category;
    }

    protected function assignGoZambiaCategory(Job $job, array $item): void
    {
        $category = $this->resolveGoZambiaCategory((string) data_get($item, 'category.name'));
        $job->categories()->sync($category ? [$category->id] : []);
    }

    protected function syncJobCategories(Job $job): void
    {
        static $categoryIds = null;
        if ($categoryIds === null) {
            $categoryIds = \Botble\JobBoard\Models\Category::pluck('id')->all();
        }
        if (! empty($categoryIds)) {
            $job->categories()->syncWithoutDetaching($categoryIds);
        }
    }

    protected function importItems(JobCrawler $crawler, array $items): array
    {
        if ($crawler->parser_type === 'gozambiajobs') {
            return $this->importGoZambiaJobs($crawler, $items);
        }

        $stats = [
            'jobs_found' => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        foreach ($items as $item) {
            $payload = $this->extractPayload($crawler, $item);

            if (empty($payload['name'])) {
                $stats['jobs_skipped']++;

                continue;
            }

            $sourceId = $payload['external_source_id'] ?: md5(($payload['external_source_url'] ?: $payload['name']) . '|' . $crawler->source_url);

            $companyId = $crawler->default_company_id ?: Company::query()->value('id');
            if (! $companyId) {
                throw new \RuntimeException('No company exists. Create a company or assign a default company to the crawler.');
            }

            $job = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $sourceId)
                ->first();

            $attributes = [
                'crawler_id' => $crawler->getKey(),
                'external_source_id' => $sourceId,
                'external_source_url' => $payload['external_source_url'] ?: $crawler->source_url,
                'name' => $payload['name'],
                'description' => $payload['description'] ?: Str::limit(strip_tags((string) $payload['content']), 400),
                'content' => $payload['content'] ?: $payload['description'],
                'company_id' => $companyId,
                'address' => $payload['address'],
                'apply_url' => $payload['apply_url'],
                'status' => JobStatusEnum::PUBLISHED,
                'moderation_status' => ModerationStatusEnum::APPROVED,
                'expire_date' => Carbon::now()->addDays(30),
                'never_expired' => false,
            ];

            if ($job) {
                $job->fill($attributes)->save();
                $stats['jobs_updated']++;
            } else {
                $newJob = Job::query()->create($attributes);
                SlugHelper::createSlug($newJob);
                $this->syncJobCategories($newJob);
                $newJob->jobTypes()->syncWithoutDetaching([3]); // Full Time
                $this->dispatchNewJobEvents($newJob);
                $stats['jobs_created']++;
            }
        }

        return $stats;
    }

    protected function extractPayload(JobCrawler $crawler, mixed $item): array
    {
        $payload = [
            'name' => $this->extractValue($crawler, $item, 'title_selector'),
            'company_name' => $this->extractValue($crawler, $item, 'company_selector'),
            'address' => $this->extractValue($crawler, $item, 'location_selector'),
            'description' => $this->extractValue($crawler, $item, 'description_selector'),
            'content' => $this->extractValue($crawler, $item, 'content_selector'),
            'apply_url' => $this->extractValue($crawler, $item, 'apply_url_selector'),
            'external_source_url' => $this->extractValue($crawler, $item, 'apply_url_selector'),
            'external_source_id' => null,
        ];

        foreach ($crawler->field_mappings ?: [] as $field => $selector) {
            $payload[$field] = $this->extract($crawler, $item, (string) $selector);
        }

        return array_map(fn ($value) => is_string($value) ? trim($value) : $value, $payload);
    }

    protected function extractValue(JobCrawler $crawler, mixed $item, string $field): ?string
    {
        $selector = (string) $crawler->{$field};

        return $selector === '' ? null : $this->extract($crawler, $item, $selector);
    }

    protected function extract(JobCrawler $crawler, mixed $item, string $selector): ?string
    {
        if ($crawler->parser_type === 'json') {
            $value = data_get($item, $selector);

            return is_scalar($value) ? (string) $value : null;
        }

        if (! $item instanceof DomCrawler) {
            return null;
        }

        [$cssSelector, $attribute] = str_contains($selector, '@')
            ? explode('@', $selector, 2)
            : [$selector, null];

        $nodes = $item->filter(trim($cssSelector));
        if (! $nodes->count()) {
            return null;
        }

        return $attribute ? $nodes->first()->attr(trim($attribute)) : $nodes->first()->text('');
    }

    // -------------------------------------------------------------------------
    // GoZambia — fetch (list pages only, no detail pages)
    // -------------------------------------------------------------------------

    protected function fetchGoZambiaJobs(JobCrawler $crawler): array
    {
        // Pre-load all known IDs so we can detect "caught up" pages and stop early.
        $this->goZambiaExistingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $isFirstRun  = empty($this->goZambiaExistingIds);
        $jobs        = [];
        $seenIds     = [];
        $totalNew    = 0;

        for ($page = 1; $page <= 20; $page++) {
            $this->saveProgress($page, count($jobs), $totalNew);

            // Polite throttle between page requests to avoid rate-limiting.
            if ($page > 1) {
                usleep(500_000); // 0.5 s
            }

            $response = $this->goZambiaRequest($this->goZambiaPageUrl($crawler->source_url, $page));

            if ($response->notFound()) {
                $this->goZambiaFullScan = true; // reached GoZambia's natural end
                break;
            }

            $response->throw();

            $html = $response->body();
            if ($this->goZambiaHasNoMatches($html)) {
                $this->goZambiaFullScan = true;
                break;
            }

            $pageJobs = $this->extractGoZambiaJobsList($html);
            if (empty($pageJobs)) {
                $this->goZambiaFullScan = true;
                break;
            }

            $newOnPage = 0;

            foreach ($pageJobs as $job) {
                $id = (string) data_get($job, 'id');
                if ($id !== '' && isset($seenIds[$id])) {
                    continue;
                }
                if ($id !== '') {
                    $seenIds[$id] = true;
                }
                $jobs[] = $job;

                if ($id !== '' && ! array_key_exists($id, $this->goZambiaExistingIds)) {
                    $newOnPage++;
                    $totalNew++;
                }
            }

            // After the first page, stop as soon as an entire page contains only
            // jobs we already have — we've caught up with known history.
            // Skipped when $disableEarlyStop is set (e.g. --all flag) so we can
            // detect removed jobs across all pages.
            if (! $this->disableEarlyStop && ! $isFirstRun && $page > 1 && $newOnPage === 0) {
                // goZambiaFullScan stays false — we didn't reach GoZambia's end.
                break;
            }
        }

        return $jobs;
    }

    // -------------------------------------------------------------------------
    // GoZambia — import
    // -------------------------------------------------------------------------

    protected function importGoZambiaJobs(JobCrawler $crawler, array $items): array
    {
        $deletedDuplicates = $this->deleteDuplicateCrawledJobs($crawler);

        $stats = [
            'jobs_found' => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        if ($deletedDuplicates > 0) {
            $stats['jobs_deleted'] = $deletedDuplicates;
            $this->saveMeta(['jobs_deleted' => $deletedDuplicates]);
        }

        // Reuse the IDs already loaded during the fetch phase — no extra DB query needed.
        $existingSourceIds = $this->goZambiaExistingIds;

        $newItems = [];
        $existingItems = [];

        foreach ($items as $item) {
            $id = (string) data_get($item, 'id');
            if ($id !== '' && array_key_exists($id, $existingSourceIds)) {
                $existingItems[] = $item;
            } else {
                $newItems[] = $item;
            }
        }

        $newTotal = count($newItems);
        $existingTotal = count($existingItems);

        // --- Phase 1: fetch detail pages + import for new jobs only ---
        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $sourceId = (string) data_get($item, 'id');
            $title = trim((string) data_get($item, 'title'));

            if ($sourceId === '' || $title === '') {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->firstOrCreateGoZambiaCompany((array) data_get($item, 'employer', []));
            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            // Only new jobs get a detail-page fetch.
            $detailPath = (string) data_get($item, 'job_details_path');
            $detailUrl = $this->absoluteGoZambiaUrl($detailPath);

            if ($detailUrl) {
                try {
                    $detailResponse = $this->goZambiaRequest($detailUrl);
                    if ($detailResponse->successful()) {
                        $detail = $this->extractGoZambiaDetailJob($detailResponse->body());
                        if ($detail) {
                            $item = array_replace_recursive($item, $detail);
                        }
                    }
                } catch (Throwable) {
                    // Keep list data if detail page fails.
                }
            }

            $item['external_source_url'] = $detailUrl ?: '';

            $job = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $sourceId)
                ->first();

            if ($job) {
                $job->forceFill($this->buildGoZambiaListAttributes($item))->save();
                $this->assignGoZambiaCategory($job, $item);
                $stats['jobs_updated']++;
            } else {
                $newJob = new Job();
                $newJob->forceFill($this->buildGoZambiaJobAttributes($crawler, $item, $company))->save();
                $this->clearConflictingCrawlerSlugs($crawler, $newJob);
                SlugHelper::createSlug($newJob);
                $this->assignGoZambiaCategory($newJob, $item);
                $newJob->jobTypes()->syncWithoutDetaching([3]); // Full Time
                $this->dispatchNewJobEvents($newJob);
                $stats['jobs_created']++;
            }

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        // --- Phase 2: update existing jobs from list data only (no HTTP) ---
        $this->saveExistingUpdateProgress(0, $existingTotal, $stats);

        foreach ($existingItems as $index => $item) {
            $sourceId = (string) data_get($item, 'id');
            $title = trim((string) data_get($item, 'title'));

            if ($sourceId === '' || $title === '') {
                $stats['jobs_skipped']++;
            } else {
                $job = Job::query()
                    ->where('crawler_id', $crawler->getKey())
                    ->where('external_source_id', $sourceId)
                    ->first();

                if ($job) {
                    $job->forceFill($this->buildGoZambiaListAttributes($item))->save();
                    $this->assignGoZambiaCategory($job, $item);
                    $stats['jobs_updated']++;
                } else {
                    $stats['jobs_skipped']++;
                }
            }

            // Save progress every 10 items to reduce DB writes.
            if ($index % 10 === 9 || $index === $existingTotal - 1) {
                $this->saveExistingUpdateProgress($index + 1, $existingTotal, $stats);
            }
        }

        // --- Phase 3: unpublish jobs no longer found on GoZambia (full scan only) ---
        if ($this->goZambiaFullScan) {
            $scannedIds = array_map(fn ($i) => (string) data_get($i, 'id'), $items);
            $scannedIds = array_filter($scannedIds); // remove empty strings

            $unpublishedCount = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('status', JobStatusEnum::PUBLISHED)
                ->whereNotNull('external_source_id')
                ->whereNotIn('external_source_id', $scannedIds)
                ->update(['status' => JobStatusEnum::DRAFT]);

            if ($unpublishedCount > 0) {
                $this->saveMeta(['jobs_unpublished' => $unpublishedCount]);
                $stats['jobs_unpublished'] = $unpublishedCount;
            }
        }

        // --- Phase 4: queue existing jobs for background detail refresh ---
        $existingMap = [];
        foreach ($existingItems as $item) {
            $id = (string) data_get($item, 'id');
            $path = (string) data_get($item, 'job_details_path');
            if ($id !== '' && $path !== '') {
                $existingMap[$id] = $path;
            }
        }

        if ($this->currentRun && ! empty($existingMap)) {
            $this->saveMeta([
                'bg_queued' => count($existingMap),
                'bg_status' => 'pending',
                'existing_to_refresh' => $existingMap,
            ]);
            $this->spawnBackgroundCommand('job-board:crawl-refresh', $this->currentRun->getKey());
        }

        return $stats;
    }

    /**
     * Remove any slug records that share the same key as the new job and belong to
     * expired or draft jobs from the same crawler. This prevents the new job's slug
     * from silently colliding with a stale entry, which would cause the URL to keep
     * resolving to the old expired job instead.
     */
    protected function assignCategoryIcon(\Botble\JobBoard\Models\Category $category, string $name): void
    {
        static $iconMap = [
            'engineering' => 'general/lightning.png', 'construction' => 'general/lightning.png',
            'transport'   => 'general/management.png', 'logistics'   => 'general/management.png',
            'ngo'         => 'general/research.png',  'development'  => 'general/research.png',
            'marketing'   => 'general/marketing.png', 'communications' => 'general/marketing.png',
            'administration' => 'general/management.png', 'office'  => 'general/management.png',
            'hospitality' => 'general/customer.png',  'tourism'     => 'general/customer.png',
            'banking'     => 'general/finance.png',   'financial'   => 'general/finance.png',
            'retail'      => 'general/retail.png',    'sales'       => 'general/marketing.png',
            'accounting'  => 'general/finance.png',   'auditing'    => 'general/finance.png',
            'education'   => 'general/research.png',  'training'    => 'general/research.png',
            'manufacturing' => 'general/lightning.png', 'fmcg'      => 'general/retail.png',
            'agriculture' => 'general/research.png',  'it'          => 'general/lightning.png',
            'telecoms'    => 'general/lightning.png', 'human resource' => 'general/human.png',
            'recruitment' => 'general/human.png',     'sheq'        => 'general/security.png',
            'security'    => 'general/security.png',  'healthcare'  => 'general/customer.png',
            'medical'     => 'general/customer.png',  'mining'      => 'general/lightning.png',
            'energy'      => 'general/lightning.png', 'legal'       => 'general/management.png',
            'management'  => 'general/management.png', 'customer'   => 'general/customer.png',
            'software'    => 'general/lightning.png', 'finance'     => 'general/finance.png',
        ];

        $lower = mb_strtolower($name);
        $icon  = 'general/management.png';

        foreach ($iconMap as $keyword => $file) {
            if (str_contains($lower, $keyword)) {
                $icon = $file;
                break;
            }
        }

        \DB::table('meta_boxes')->updateOrInsert(
            [
                'reference_id'   => $category->getKey(),
                'reference_type' => get_class($category),
                'meta_key'       => 'icon_image',
            ],
            [
                'meta_value' => json_encode([$icon]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    protected function clearConflictingCrawlerSlugs(JobCrawler $crawler, Job $newJob): void
    {
        $slugKey = Str::slug($newJob->name);

        DB::table('slugs')
            ->where('key', $slugKey)
            ->where('prefix', 'jobs')
            ->whereIn('reference_id', function ($query) use ($crawler): void {
                $query->select('id')
                    ->from('jb_jobs')
                    ->where('crawler_id', $crawler->getKey())
                    ->where(function ($q): void {
                        $q->where('status', JobStatusEnum::DRAFT)
                            ->orWhere(function ($q2): void {
                                $q2->where('never_expired', false)
                                    ->whereNotNull('expire_date')
                                    ->where('expire_date', '<', Carbon::now());
                            });
                    });
            })
            ->delete();
    }

    protected function deleteDuplicateCrawledJobs(JobCrawler $crawler): int
    {
        $duplicates = DB::table('jb_jobs')
            ->select('external_source_id')
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->groupBy('external_source_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('external_source_id');

        $deleted = 0;

        foreach ($duplicates as $sourceId) {
            $jobs = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $sourceId)
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [(string) JobStatusEnum::PUBLISHED])
                ->latest('updated_at')
                ->latest('id')
                ->get();

            $jobs->shift();

            foreach ($jobs as $job) {
                $job->delete();
                $deleted++;
            }
        }

        return $deleted;
    }

    protected function buildGoZambiaJobAttributes(JobCrawler $crawler, array $item, Company $company): array
    {
        $postedAt = data_get($item, 'posted_at');
        // GoZambia sends UTC timestamps (Z suffix). Convert to the app's runtime timezone
        // so MySQL stores local time and diffForHumans() is accurate.
        $postedAtDate = $postedAt ? Carbon::parse($postedAt)->setTimezone(date_default_timezone_get()) : null;
        $expiresAt = data_get($item, 'validThrough')
            ?: ($postedAtDate ? $postedAtDate->copy()->addDays((int) data_get($item, 'job_expires_in_days', 30)) : null);

        $sourceUrl = (string) data_get($item, 'external_source_url')
            ?: $this->absoluteGoZambiaUrl((string) data_get($item, 'job_details_path'));
        $description = $this->sanitizeGoZambiaHtml((string) data_get($item, 'description'));
        $address = data_get($item, 'job_location.name')
            ?: data_get($item, 'location')
            ?: 'Zambia';

        return [
            'crawler_id' => $crawler->getKey(),
            'external_source_id' => (string) data_get($item, 'id'),
            'external_source_url' => $sourceUrl,
            'name' => $this->limitGoZambiaField(trim((string) data_get($item, 'title')), 110),
            'description' => Str::limit(trim(strip_tags($description)), 400, ''),
            'content' => $description ?: Str::limit(trim(strip_tags($description)), 400, ''),
            'company_id' => $company->getKey(),
            'address' => $address,
            'country_id' => 7, // Zambia
            'apply_url' => $this->normalizeApplyTarget((string) data_get($item, 'apply_to')),
            'status' => JobStatusEnum::PUBLISHED,
            'moderation_status' => ModerationStatusEnum::APPROVED,
            'salary_from' => data_get($item, 'min_compensation'),
            'salary_to' => data_get($item, 'max_compensation'),
            'salary_range' => $this->goZambiaSalaryRange((string) data_get($item, 'compensation_time_frame')),
            'salary_type' => (data_get($item, 'min_compensation') || data_get($item, 'max_compensation'))
                ? SalaryTypeEnum::FIXED
                : SalaryTypeEnum::HIDDEN,
            'currency_id' => $this->currencyIdForCode((string) data_get($item, 'compensation_currency')),
            'career_level_id' => 3, // Experienced Professional
            'is_featured' => false,
            'latitude' => data_get($item, 'job_location.latitude'),
            'longitude' => data_get($item, 'job_location.longitude'),
            'expire_date' => $expiresAt ? Carbon::parse($expiresAt) : Carbon::now()->addDays(30),
            'application_closing_date' => $expiresAt ? Carbon::parse($expiresAt) : null,
            'never_expired' => false,
            'created_at' => $postedAtDate ?? Carbon::now(),
            'updated_at' => $postedAtDate ?? Carbon::now(),
        ];
    }

    // Only fields available from the list page — no detail fetch needed.
    protected function buildGoZambiaListAttributes(array $item): array
    {
        $postedAt = data_get($item, 'posted_at');
        $postedAtDate = $postedAt ? Carbon::parse($postedAt)->setTimezone(date_default_timezone_get()) : null;
        $expiresAt = data_get($item, 'validThrough')
            ?: ($postedAtDate ? $postedAtDate->copy()->addDays((int) data_get($item, 'job_expires_in_days', 30)) : null);
        $address = data_get($item, 'job_location.name')
            ?: data_get($item, 'location')
            ?: 'Zambia';

        return [
            'name' => $this->limitGoZambiaField(trim((string) data_get($item, 'title')), 110),
            'address' => $address,
            'salary_from' => data_get($item, 'min_compensation'),
            'salary_to' => data_get($item, 'max_compensation'),
            'salary_range' => $this->goZambiaSalaryRange((string) data_get($item, 'compensation_time_frame')),
            'salary_type' => (data_get($item, 'min_compensation') || data_get($item, 'max_compensation'))
                ? SalaryTypeEnum::FIXED
                : SalaryTypeEnum::HIDDEN,
            'is_featured' => false,
            'latitude' => data_get($item, 'job_location.latitude'),
            'longitude' => data_get($item, 'job_location.longitude'),
            'expire_date' => $expiresAt ? Carbon::parse($expiresAt) : Carbon::now()->addDays(30),
            'application_closing_date' => $expiresAt ? Carbon::parse($expiresAt) : null,
        ] + ($postedAtDate ? ['created_at' => $postedAtDate] : []);
    }

    protected function spawnBackgroundCommand(string $command, int $id): void
    {
        $php = PHP_BINARY;
        if (str_contains($php, 'fpm') || ! is_executable($php)) {
            $php = '/usr/bin/php';
        }

        $artisan = base_path('artisan');
        \exec(sprintf(
            '%s %s %s %d > /dev/null 2>&1 &',
            escapeshellcmd($php),
            escapeshellarg($artisan),
            $command,
            $id
        ));
    }

    protected function dispatchNewJobEvents(Job $job): void
    {
        event(new CreatedContentEvent(JOB_MODULE_SCREEN_NAME, request(), $job));
        event(new JobPublishedEvent($job));
    }

    protected function firstOrCreateGoZambiaCompany(array $employer): ?Company
    {
        $name = trim((string) data_get($employer, 'name'));
        if ($name === '') {
            return null;
        }

        $website = $this->normalizeGoZambiaCompanyWebsite((string) data_get($employer, 'website'));
        $company = $this->findGoZambiaCompany($name, $website);

        $attributes = [
            'name' => $this->limitGoZambiaField($name, 110),
            'website' => $website ? $this->limitGoZambiaField($website, 110) : null,
            'description' => Str::limit(trim(strip_tags((string) data_get($employer, 'description'))), 400, ''),
            'content' => data_get($employer, 'description'),
            'country_id' => 7, // Zambia
            'status' => BaseStatusEnum::PUBLISHED,
            'is_verified' => true,
            'verified_at' => Carbon::now(),
        ];

        if (! $company) {
            $company = Company::query()->create($attributes);
            SlugHelper::createSlug($company);
        } else {
            $company->fill(array_filter($attributes, fn ($value) => $value !== null && $value !== ''))->save();
            if (! $company->slugable) {
                SlugHelper::createSlug($company);
            }
        }

        if (! $company->logo && ($logo = data_get($employer, 'logo'))) {
            $uploadedLogo = $this->uploadCompanyLogo((string) $logo);
            if ($uploadedLogo) {
                $company->logo = $uploadedLogo;
                $company->save();
            }
        }

        return $company;
    }

    protected function findGoZambiaCompany(string $name, ?string $website): ?Company
    {
        $normalizedName = $this->normalizeGoZambiaCompanyName($name);

        return Company::query()
            ->where(function ($query) use ($website, $name): void {
                if ($website) {
                    $query->where('website', $website);
                }

                $query->orWhereIn('name', array_values(array_unique([
                    $name,
                    html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                ])));
            })
            ->get()
            ->first(fn (Company $company) => $this->normalizeGoZambiaCompanyName((string) $company->name) === $normalizedName);
    }

    protected function normalizeGoZambiaCompanyName(string $name): string
    {
        $name = html_entity_decode(str_replace('&amp;', '&', $name), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::of($name)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->lower()
            ->toString();
    }

    protected function limitGoZambiaField(string $value, int $limit): string
    {
        return mb_substr(trim($value), 0, $limit);
    }

    protected function sanitizeGoZambiaHtml(string $html): string
    {
        return preg_replace('/<img\b[^>]*\bsrc=(["\'])data:[^"\']+\1[^>]*>/i', '', $html) ?: $html;
    }

    protected function normalizeGoZambiaCompanyWebsite(string $website): ?string
    {
        $website = trim($website);
        if ($website === '') {
            return null;
        }

        $host = parse_url($website, PHP_URL_HOST);
        if ($host && str_ends_with($host, 'facebook.com')) {
            parse_str(parse_url($website, PHP_URL_QUERY) ?: '', $query);
            if (! empty($query['u']) && is_string($query['u'])) {
                $website = urldecode($query['u']);
            }
        }

        return $website;
    }

    protected function uploadCompanyLogo(string $url): ?string
    {
        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg';
        $path = sprintf('%s/gozambia-logo-%s.%s', sys_get_temp_dir(), Str::random(16), preg_replace('/[^a-z0-9]/i', '', $extension));
        $result = null;

        try {
            $response = Http::withoutVerifying()
                ->timeout(12)
                ->get($url);

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

    protected function goZambiaRequest(string $url): \Illuminate\Http\Client\Response
    {
        return Http::timeout(12)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; WakandaJobsCrawler/1.0)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($url);
    }

    protected function goZambiaPageUrl(string $sourceUrl, int $page): string
    {
        $parts = parse_url($sourceUrl);
        parse_str($parts['query'] ?? '', $query);
        $query['page'] = $page;
        $query['order'] = 'posted_at';

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? 'gozambiajobs.com';
        $path = $parts['path'] ?? '/jobs';

        return sprintf('%s://%s%s?%s', $scheme, $host, $path, http_build_query($query));
    }

    protected function goZambiaHasNoMatches(string $html): bool
    {
        return str_contains($html, "Sorry, we couldn't find any matches for your search.");
    }

    public function extractGoZambiaJobsList(string $html): array
    {
        $jobs = [];
        $offset = 0;
        $needle = 'window.jobsList = window.jobsList.concat(';

        while (($position = strpos($html, $needle, $offset)) !== false) {
            $json = $this->extractBalancedJson($html, $position + strlen($needle), '[');
            $decoded = $json ? json_decode($json, true) : null;

            if (is_array($decoded)) {
                $jobs = array_merge($jobs, $decoded);
            }

            $offset = $position + strlen($needle);
        }

        return $jobs;
    }

    public function extractGoZambiaDetailJob(string $html): ?array
    {
        $position = strpos($html, 'window.job =');
        if ($position === false) {
            return null;
        }

        $json = $this->extractBalancedJson($html, $position, '{');
        $decoded = $json ? json_decode($json, true) : null;

        return is_array($decoded) ? $decoded : null;
    }

    protected function extractBalancedJson(string $html, int $startAt, string $openingCharacter): ?string
    {
        $start = strpos($html, $openingCharacter, $startAt);
        if ($start === false) {
            return null;
        }

        $closingCharacter = $openingCharacter === '[' ? ']' : '}';
        $depth = 0;
        $inString = false;
        $escape = false;
        $length = strlen($html);

        for ($i = $start; $i < $length; $i++) {
            $character = $html[$i];

            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($character === '\\') {
                    $escape = true;
                } elseif ($character === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($character === '"') {
                $inString = true;
            } elseif ($character === $openingCharacter) {
                $depth++;
            } elseif ($character === $closingCharacter) {
                $depth--;

                if ($depth === 0) {
                    return substr($html, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    protected function absoluteGoZambiaUrl(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, '//')) {
            return 'https:' . $path;
        }

        return 'https://gozambiajobs.com/' . ltrim($path, '/');
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

    protected function goZambiaSalaryRange(string $timeFrame): string
    {
        return match (strtolower($timeFrame)) {
            'monthly', 'month' => SalaryRangeEnum::MONTHLY,
            'weekly', 'week' => SalaryRangeEnum::WEEKLY,
            'daily', 'day' => SalaryRangeEnum::DAILY,
            'hourly', 'hour' => SalaryRangeEnum::HOURLY,
            default => SalaryRangeEnum::YEARLY,
        };
    }

    protected function currencyIdForCode(string $code): ?int
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        return Currency::query()
            ->where('symbol', $code)
            ->orWhere('title', 'like', $code . '%')
            ->value('id');
    }
}
