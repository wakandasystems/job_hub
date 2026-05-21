<?php

namespace Botble\JobBoard\Services;

use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Facades\EmailHandler;
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

    /**
     * 'full'        — scan all pages with slow delays, import from listing data only (no detail fetches).
     * 'incremental' — scan until caught-up with early-stop, fetch detail pages for new jobs only.
     */
    public string $runMode = 'incremental';

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

            try {
                EmailHandler::setModule(JOB_BOARD_MODULE_SCREEN_NAME)
                    ->setVariableValues([
                        'crawler_name' => $crawler->name,
                        'error_message' => $exception->getMessage(),
                        'crawler_url' => route('job-board.crawlers.edit', $crawler->getKey()),
                    ])
                    ->sendUsingTemplate('crawler-failed');
            } catch (Throwable) {
                // Don't let email failure mask the crawler error
            }

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

        if ($crawler->parser_type === 'careers24') {
            return $this->fetchCareers24Jobs($crawler);
        }

        if ($crawler->parser_type === 'ringier') {
            return $this->fetchRingierJobs($crawler);
        }

        if ($crawler->parser_type === 'myjobmu') {
            return $this->fetchMyJobMuJobs($crawler);
        }

        if ($crawler->parser_type === 'jobstanzania') {
            return $this->fetchJobsTanzaniaJobs($crawler);
        }

        if ($crawler->parser_type === 'africawork') {
            return $this->fetchAfricaworkJobs($crawler);
        }

        if ($crawler->parser_type === 'jobinrwanda') {
            return $this->fetchJobInRwandaJobs($crawler);
        }

        if ($crawler->parser_type === 'keejob') {
            return $this->fetchKeejobJobs($crawler);
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
            throw new \RuntimeException(sprintf(
                'HTML crawlers require an item selector (crawler "%s", parser_type="%s").',
                $crawler->name,
                $crawler->getRawOriginal('parser_type') ?? $crawler->parser_type,
            ));
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
     * Find or create a category by name, never creating duplicates.
     * Returns null for "Other" or empty names.
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

        // Lookup first using case-insensitive match (utf8mb4_unicode_ci collation
        // makes this work for the unique index too).
        $category = \Botble\JobBoard\Models\Category::query()
            ->whereRaw('LOWER(name) = ?', [$key])
            ->first();

        if (! $category) {
            try {
                $category = \Botble\JobBoard\Models\Category::query()->create([
                    'name'   => $name,
                    'status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED,
                ]);
                SlugHelper::createSlug($category);
                $this->assignCategoryIcon($category, $name);
            } catch (\Illuminate\Database\QueryException $e) {
                // Unique constraint violation — a concurrent run just created it.
                if ($e->errorInfo[1] === 1062) {
                    $category = \Botble\JobBoard\Models\Category::query()
                        ->whereRaw('LOWER(name) = ?', [$key])
                        ->first();
                } else {
                    throw $e;
                }
            }
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

        if ($crawler->parser_type === 'careers24') {
            return $this->importCareers24Jobs($crawler, $items);
        }

        if ($crawler->parser_type === 'ringier') {
            return $this->importRingierJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'myjobmu') {
            return $this->importMyJobMuJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'jobstanzania') {
            return $this->importJobsTanzaniaJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'africawork') {
            return $this->importAfricaworkJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'jobinrwanda') {
            return $this->importJobInRwandaJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'keejob') {
            return $this->importKeejobJobs($crawler, $items);
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
                $newJob = new Job();
                $newJob->forceFill($attributes);
                $this->persistNewJob($newJob, $crawler, $stats, function (Job $j): void {
                    $this->syncJobCategories($j);
                    $j->jobTypes()->syncWithoutDetaching([3]);
                    $this->dispatchNewJobEvents($j);
                });
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
    // Careers24 South Africa
    // -------------------------------------------------------------------------

    protected function fetchCareers24Jobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $isFirstRun  = empty($existingIds);
        $isFullPull  = $this->runMode === 'full';
        $jobs        = [];
        $seenIds     = [];
        $prevPageIds = [];
        // Full pull: grab everything (site has ~6,800 jobs across ~680 pages).
        // Incremental: 20 pages is enough to catch up with daily new postings.
        $maxPages    = $isFullPull ? 200 : 20;
        $listUrl     = 'https://www.careers24.com/jobs/';

        // ── Page 1: HTML GET — also extracts the vsp config for AJAX pagination ──
        $this->saveProgress(1, 0);
        $firstResponse = $this->careers24Request($listUrl);

        if (! $firstResponse->successful()) {
            return $jobs;
        }

        $html = $firstResponse->body();

        // vsp is the server-side search state object embedded in the page
        $vsp = null;
        if (preg_match('/var vsp = ({.*?});/s', $html, $m)) {
            $vsp = json_decode($m[1], true);
        }

        $pageJobs = $this->extractCareers24List($html);
        foreach ($pageJobs as $job) {
            $id = (string) ($job['id'] ?? '');
            if ($id === '' || isset($seenIds[$id])) {
                continue;
            }
            $seenIds[$id] = true;
            $jobs[]       = $job;
            $prevPageIds[] = $id;
        }

        if (empty($vsp) || empty($pageJobs)) {
            return $jobs;
        }

        // ── Pages 2+: AJAX POST /Search/_SearchResults ────────────────────────
        // Careers24 ignores the ?pageIndex query param on GET; pagination is
        // jQuery $.post with bracket-notation form fields (vsp[pageIndex]=N etc.)
        for ($page = 2; $page <= $maxPages; $page++) {
            $this->saveProgress($page, count($jobs));
            // Full pull: slower random delays to mimic human browsing.
            // Incremental: moderate delays — we only fetch a handful of pages.
            sleep($isFullPull ? rand(5, 12) : rand(2, 5));

            $vsp['pageIndex'] = $page;
            $vsp['startIndex'] = ($page - 1) * (int) ($vsp['pageSize'] ?? 10);

            $postResponse = Http::timeout(20)
                ->withHeaders([
                    'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Accept'           => 'text/html, */*; q=0.01',
                    'Referer'          => $listUrl,
                ])
                ->asForm()
                ->post('https://www.careers24.com/Search/_SearchResults', $this->buildCareers24VspFormData($vsp));

            if (! $postResponse->successful()) {
                break;
            }

            $pageHtml = $postResponse->body();
            $pageJobs = $this->extractCareers24List($pageHtml);

            if (empty($pageJobs)) {
                break;
            }

            $newOnPage    = 0;
            $newIdsOnPage = [];

            foreach ($pageJobs as $job) {
                $id = (string) ($job['id'] ?? '');
                if ($id === '' || isset($seenIds[$id])) {
                    continue;
                }
                $seenIds[$id]   = true;
                $newIdsOnPage[] = $id;
                $jobs[]         = $job;

                if (! array_key_exists($id, $existingIds)) {
                    $newOnPage++;
                }
            }

            // Redirect/repeat loop: same IDs as previous page → stop
            if (! empty($newIdsOnPage) && $newIdsOnPage === $prevPageIds) {
                break;
            }
            $prevPageIds = $newIdsOnPage;

            // Incremental only: stop as soon as we've caught up with known jobs.
            // Full pull never stops early — we want every page.
            if (! $isFullPull && ! $isFirstRun && $newOnPage === 0) {
                break;
            }
        }

        return $jobs;
    }

    protected function buildCareers24VspFormData(array $vsp): array
    {
        $formData = [];
        foreach ($vsp as $k => $v) {
            if (is_null($v)) {
                $formData["vsp[$k]"] = '';
            } elseif (is_bool($v)) {
                $formData["vsp[$k]"] = $v ? 'true' : 'false';
            } elseif (is_array($v)) {
                $formData["vsp[$k]"] = json_encode($v);
            } else {
                $formData["vsp[$k]"] = (string) $v;
            }
        }

        return $formData;
    }

    protected function importCareers24Jobs(JobCrawler $crawler, array $items): array
    {
        $deletedDuplicates = $this->deleteDuplicateCrawledJobs($crawler);

        $stats = [
            'jobs_found'   => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        if ($deletedDuplicates > 0) {
            $stats['jobs_deleted'] = $deletedDuplicates;
            $this->saveMeta(['jobs_deleted' => $deletedDuplicates]);
        }

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $newItems      = [];
        $existingItems = [];

        foreach ($items as $item) {
            $id = (string) ($item['id'] ?? '');
            if ($id !== '' && array_key_exists($id, $existingIds)) {
                $existingItems[] = $item;
            } else {
                $newItems[] = $item;
            }
        }

        $newTotal      = count($newItems);
        $existingTotal = count($existingItems);

        // Phase 1: import new jobs.
        // Full pull skips detail-page fetches entirely — fetching detail pages for
        // hundreds of jobs would take hours. Jobs are imported from listing data and
        // a subsequent incremental run (or manual run) fills in the full descriptions.
        // Incremental runs DO fetch detail pages because they only process a handful
        // of genuinely new jobs.
        $isFullPull = $this->runMode === 'full';
        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $sourceId = (string) ($item['id'] ?? '');
            $title    = trim((string) ($item['title'] ?? ''));

            if ($sourceId === '' || $title === '') {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $detailUrl = $this->absoluteCareers24Url((string) ($item['url'] ?? ''));
            $detail    = null;

            if (! $isFullPull && $detailUrl) {
                try {
                    $detailResponse = $this->careers24Request($detailUrl, 'https://www.careers24.com/jobs/');
                    if ($detailResponse->successful()) {
                        $detail = $this->extractCareers24Detail($detailResponse->body());
                    }
                } catch (Throwable) {
                    // keep list data on failure
                }
            }

            if ($detail) {
                $item = array_merge($item, $detail);
            }

            $item['external_source_url'] = $detailUrl ?: '';

            $company = $this->firstOrCreateCareers24Company($item);
            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $existing = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $sourceId)
                ->first();

            if ($existing) {
                $existing->forceFill($this->buildCareers24Attributes($crawler, $item, $company))->save();
                $this->assignGoZambiaCategory($existing, ['category' => ['name' => $item['industry'] ?? '']]);
                $stats['jobs_updated']++;
            } else {
                $newJob = new Job();
                $newJob->forceFill($this->buildCareers24Attributes($crawler, $item, $company));
                $this->persistNewJob($newJob, $crawler, $stats, function (Job $j) use ($item): void {
                    $this->assignGoZambiaCategory($j, ['category' => ['name' => $item['industry'] ?? '']]);
                    $j->jobTypes()->syncWithoutDetaching([3]);
                    $this->dispatchNewJobEvents($j);
                });
            }

            if (! $isFullPull) {
                sleep(rand(1, 3)); // delay between detail fetches on incremental runs
            }

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        // Phase 2: update existing jobs from list data (no HTTP)
        $this->saveExistingUpdateProgress(0, $existingTotal, $stats);

        foreach ($existingItems as $index => $item) {
            $sourceId = (string) ($item['id'] ?? '');
            if ($sourceId === '') {
                $stats['jobs_skipped']++;
            } else {
                $job = Job::query()
                    ->where('crawler_id', $crawler->getKey())
                    ->where('external_source_id', $sourceId)
                    ->first();

                if ($job) {
                    // Refresh expiry from list data
                    $job->forceFill([
                        'name'       => $this->limitGoZambiaField(trim((string) ($item['title'] ?? '')), 110),
                        'expire_date' => isset($item['validThrough'])
                            ? Carbon::parse($item['validThrough'])
                            : Carbon::now()->addDays(30),
                        'application_closing_date' => isset($item['validThrough'])
                            ? Carbon::parse($item['validThrough'])
                            : null,
                    ])->save();
                    $stats['jobs_updated']++;
                } else {
                    $stats['jobs_skipped']++;
                }
            }

            if ($index % 10 === 9 || $index === $existingTotal - 1) {
                $this->saveExistingUpdateProgress($index + 1, $existingTotal, $stats);
            }
        }

        return $stats;
    }

    protected function extractCareers24List(string $html): array
    {
        $jobs    = [];
        $offset  = 0;
        $needle  = 'class="job-card"';

        while (($pos = strpos($html, $needle, $offset)) !== false) {
            // find data-id
            $chunk = substr($html, $pos, 2000);

            if (! preg_match('/data-id="(\d+)"/', $chunk, $idM)) {
                $offset = $pos + strlen($needle);
                continue;
            }

            // title and URL — attributes may appear in any order on the <a> tag
            preg_match('/<a\b[^>]*href="([^"]+)"[^>]*data-control="vacancy-title"[^>]*>\s*<h2>([^<]+)<\/h2>/', $chunk, $linkM);
            if (! $linkM) {
                preg_match('/<a\b[^>]*data-control="vacancy-title"[^>]*href="([^"]+)"[^>]*>\s*<h2>([^<]+)<\/h2>/', $chunk, $linkM);
            }
            $url   = $linkM[1] ?? null;
            $title = isset($linkM[2]) ? html_entity_decode(trim($linkM[2])) : '';

            // location = first <li> in job-card-left
            $location = '';
            if (preg_match('/job-card-left.*?<li>(.*?)<\/li>/s', $chunk, $locM)) {
                $location = trim(strip_tags($locM[1]));
            }

            $jobs[] = [
                'id'       => $idM[1],
                'title'    => $title,
                'url'      => $url,
                'location' => $location,
            ];

            $offset = $pos + strlen($needle);
        }

        return $jobs;
    }

    protected function extractCareers24Detail(string $html): array
    {
        $result = [];

        // ── JSON-LD: metadata (description here is truncated by careers24) ────
        if (preg_match('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $m)) {
            $data = json_decode($m[1], true);
            if (is_array($data) && ($data['@type'] ?? '') === 'JobPosting') {
                $org      = $data['hiringOrganization'] ?? [];
                $location = $data['jobLocation']['address'] ?? [];
                $salary   = $data['baseSalary'] ?? [];
                $salValue = $salary['value']['value'] ?? null;
                $salUnit  = $salary['value']['unitText'] ?? null;

                $result = [
                    'title'           => $data['title'] ?? '',
                    'company_name'    => $org['name'] ?? '',
                    'location'        => $this->formatCareers24Location($location),
                    'industry'        => $data['industry'] ?? '',
                    'datePosted'      => $data['datePosted'] ?? null,
                    'validThrough'    => $data['validThrough'] ?? null,
                    'employmentType'  => $data['employmentType'] ?? '',
                    'salary_from'     => $salValue,
                    'salary_to'       => $salValue,
                    'salary_currency' => $salary['currency'] ?? 'ZAR',
                    'salary_unit'     => $salUnit,
                ];
            }
        }

        // ── Full description from .v-descrip divs ─────────────────────────────
        $fullDesc = $this->extractCareers24VDescriptions($html);
        if ($fullDesc !== '') {
            $result['description'] = $fullDesc;
        }

        // ── Apply URL ─────────────────────────────────────────────────────────
        if (preg_match('/href="(\/login\/\?returnurl=[^"]+)"/', $html, $applyM)) {
            $result['apply_url'] = 'https://www.careers24.com' . html_entity_decode($applyM[1]);
        }

        return $result;
    }

    protected function formatCareers24Location(array $address): string
    {
        $parts = array_filter([
            trim($address['addressLocality'] ?? ''),
            trim($address['addressRegion'] ?? ''),
        ]);

        return implode(', ', $parts);
    }

    /**
     * Extract content of each <div class="v-descrip"> block, handling nested tags
     * correctly by walking balanced opening/closing div pairs.
     */
    protected function extractCareers24VDescriptions(string $html): string
    {
        $parts  = [];
        $needle = 'class="v-descrip"';
        $offset = 0;

        while (($pos = strpos($html, $needle, $offset)) !== false) {
            // Find the closing > of this opening tag
            $gt = strpos($html, '>', $pos);
            if ($gt === false) {
                $offset = $pos + 1;
                continue;
            }
            $start = $gt + 1;

            // Walk HTML to find the matching </div>
            $depth = 1;
            $i     = $start;
            $len   = strlen($html);

            while ($i < $len && $depth > 0) {
                $nextOpen  = strpos($html, '<div', $i);
                $nextClose = strpos($html, '</div>', $i);

                if ($nextClose === false) {
                    break;
                }

                if ($nextOpen !== false && $nextOpen < $nextClose) {
                    $depth++;
                    $i = $nextOpen + 4;
                } else {
                    $depth--;
                    if ($depth === 0) {
                        $parts[] = trim(substr($html, $start, $nextClose - $start));
                    }
                    $i = $nextClose + 6;
                }
            }

            $offset = $pos + strlen($needle);
        }

        return implode("\n", array_filter($parts));
    }

    protected function buildCareers24Attributes(JobCrawler $crawler, array $item, Company $company): array
    {
        $postedAt  = $item['datePosted'] ?? null;
        $expiresAt = $item['validThrough'] ?? null;

        $postedDate = $postedAt ? Carbon::parse($postedAt) : Carbon::now();
        $expireDate = $expiresAt
            ? Carbon::parse($expiresAt)
            : $postedDate->copy()->addDays(60);

        $rawDesc     = (string) ($item['description'] ?? '');
        $description = trim(strip_tags($rawDesc));
        $salary      = $item['salary_from'] ?? null;

        return [
            'crawler_id'              => $crawler->getKey(),
            'external_source_id'      => (string) ($item['id'] ?? ''),
            'external_source_url'     => (string) ($item['external_source_url'] ?? ''),
            'name'                    => $this->limitGoZambiaField(trim((string) ($item['title'] ?? '')), 110),
            'description'             => Str::limit($description, 400, ''),
            'content'                 => $rawDesc ?: $description,
            'company_id'              => $company->getKey(),
            'address'                 => (string) ($item['location'] ?? 'South Africa'),
            'country_id'              => 53, // South Africa
            'apply_url'               => (string) ($item['apply_url'] ?? $item['external_source_url'] ?? ''),
            'status'                  => JobStatusEnum::PUBLISHED,
            'moderation_status'       => ModerationStatusEnum::APPROVED,
            'salary_from'             => $salary ? (float) $salary : null,
            'salary_to'               => $salary ? (float) $salary : null,
            'salary_range'            => $this->careers24SalaryRange($item['salary_unit'] ?? ''),
            'salary_type'             => $salary ? \Botble\JobBoard\Enums\SalaryTypeEnum::FIXED : \Botble\JobBoard\Enums\SalaryTypeEnum::HIDDEN,
            'currency_id'             => 46, // ZAR
            'career_level_id'         => 3,
            'is_featured'             => false,
            'expire_date'             => $expireDate,
            'application_closing_date'=> $expireDate,
            'never_expired'           => false,
            'created_at'              => $postedDate,
            'updated_at'              => $postedDate,
        ];
    }

    protected function firstOrCreateCareers24Company(array $item): ?Company
    {
        $name = trim((string) ($item['company_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $company = $this->findGoZambiaCompany($name, null);

        if (! $company) {
            $company = $this->firstOrCreateCompany([
                'name'        => $this->limitGoZambiaField($name, 110),
                'country_id'  => 53,
                'status'      => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED,
                'is_verified' => false,
            ], $name);
            SlugHelper::createSlug($company);
        }

        return $company;
    }

    protected function careers24SalaryRange(string $unit): string
    {
        return match (strtoupper(trim($unit))) {
            'MONTH', 'MONTHLY' => \Botble\JobBoard\Enums\SalaryRangeEnum::MONTHLY,
            'WEEK', 'WEEKLY'   => \Botble\JobBoard\Enums\SalaryRangeEnum::WEEKLY,
            'DAY', 'DAILY'     => \Botble\JobBoard\Enums\SalaryRangeEnum::DAILY,
            'HOUR', 'HOURLY'   => \Botble\JobBoard\Enums\SalaryRangeEnum::HOURLY,
            default            => \Botble\JobBoard\Enums\SalaryRangeEnum::YEARLY,
        };
    }

    protected function absoluteCareers24Url(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return 'https://www.careers24.com' . $path;
    }

    protected function careers24Request(string $url, string $referer = ''): \Illuminate\Http\Client\Response
    {
        $headers = [
            'User-Agent'                => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language'           => 'en-ZA,en-GB;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding'           => 'gzip, deflate, br',
            'Connection'                => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest'            => 'document',
            'Sec-Fetch-Mode'            => 'navigate',
            'Sec-Fetch-Site'            => $referer !== '' ? 'same-origin' : 'none',
            'Sec-Fetch-User'            => '?1',
        ];

        if ($referer !== '') {
            $headers['Referer'] = $referer;
        }

        return Http::timeout(20)->withHeaders($headers)->get($url);
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
                $job->forceFill($this->buildGoZambiaListAttributes($item));
                if ($job->isDirty()) {
                    $job->save();
                    $stats['jobs_updated']++;
                } else {
                    $stats['jobs_skipped']++;
                }
                $this->assignGoZambiaCategory($job, $item);
            } else {
                $newJob = new Job();
                $newJob->forceFill($this->buildGoZambiaJobAttributes($crawler, $item, $company));
                $this->persistNewJob($newJob, $crawler, $stats, function (Job $j) use ($item): void {
                    $this->assignGoZambiaCategory($j, $item);
                    $j->jobTypes()->syncWithoutDetaching([3]);
                    $this->dispatchNewJobEvents($j);
                });
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
                    $job->forceFill($this->buildGoZambiaListAttributes($item));
                    if ($job->isDirty()) {
                        $job->save();
                        $stats['jobs_updated']++;
                    } else {
                        $stats['jobs_skipped']++;
                    }
                    $this->assignGoZambiaCategory($job, $item);
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

    /**
     * Save a new Job, create its slug, and run $configure (category/types/events).
     * Silently skips on a unique-key violation (1062) — concurrent run already saved it.
     * Increments $stats['jobs_created'] on success, 'jobs_skipped' on duplicate.
     */
    protected function persistNewJob(Job $job, JobCrawler $crawler, array &$stats, callable $configure): void
    {
        try {
            $job->save();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] === 1062) {
                $stats['jobs_skipped']++;
                return;
            }
            throw $e;
        }
        $this->clearConflictingCrawlerSlugs($crawler, $job);
        SlugHelper::createSlug($job);
        $configure($job);
        $stats['jobs_created']++;
    }

    /**
     * Find or create a Company by name. On unique-key collision (concurrent run), refetches.
     */
    protected function firstOrCreateCompany(array $attributes, string $name): Company
    {
        try {
            $company = Company::query()->create($attributes);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] !== 1062) {
                throw $e;
            }
            $company = Company::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])
                ->firstOrFail();
        }

        return $company;
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
            $company = $this->firstOrCreateCompany($attributes, $name);
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

    // -------------------------------------------------------------------------
    // Ringier Africa (Jobberman Nigeria/Ghana, BrighterMonday Kenya/Uganda)
    // -------------------------------------------------------------------------

    /**
     * Fetch job listing URLs from Ringier-platform sites (Jobberman / BrighterMonday).
     * These sites embed prerender hints in the <head> for each job on the page.
     */
    protected function fetchRingierJobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $isFirstRun = empty($existingIds);
        $isFullPull = $this->runMode === 'full';
        $maxPages   = $isFullPull ? 50 : 20;
        $jobs       = [];
        $seenSlugs  = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $this->saveProgress($page, count($jobs));

            if ($page > 1) {
                sleep($isFullPull ? rand(3, 7) : rand(1, 3));
            }

            $url      = $this->ringierPageUrl((string) $crawler->source_url, $page);
            $response = $this->ringierRequest($url);

            if (! $response->successful()) {
                break;
            }

            $html = $response->body();

            preg_match_all('/<link[^>]+rel=["\']prerender["\'][^>]+href=["\']([^"\']+\/listings\/[^"\']+)["\']/', $html, $matches);
            $urls = array_unique($matches[1]);

            if (empty($urls)) {
                break;
            }

            $newOnPage = 0;

            foreach ($urls as $listingUrl) {
                $slug = basename(parse_url($listingUrl, PHP_URL_PATH));
                if (! $slug || isset($seenSlugs[$slug])) {
                    continue;
                }
                $seenSlugs[$slug] = true;
                $jobs[] = ['url' => $listingUrl, 'slug' => $slug];

                if (! array_key_exists($slug, $existingIds)) {
                    $newOnPage++;
                }
            }

            if (! $isFullPull && ! $isFirstRun && $page > 1 && $newOnPage === 0) {
                break;
            }
        }

        return $jobs;
    }

    protected function importRingierJobs(JobCrawler $crawler, array $items): array
    {
        $mappings        = $crawler->field_mappings ?? [];
        $countryId       = (int) ($mappings['country_id'] ?? 0);
        $currencyId      = isset($mappings['currency_id']) ? (int) $mappings['currency_id'] : null;
        $defaultLocation = (string) ($mappings['default_location'] ?? 'Africa');

        $stats = [
            'jobs_found'   => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $newItems      = [];
        $existingItems = [];

        foreach ($items as $item) {
            $slug = $item['slug'] ?? '';
            if ($slug !== '' && array_key_exists($slug, $existingIds)) {
                $existingItems[] = $item;
            } else {
                $newItems[] = $item;
            }
        }

        $newTotal    = count($newItems);
        $isFullPull  = $this->runMode === 'full';

        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $slug = $item['slug'] ?? '';
            $url  = $item['url'] ?? '';

            if (! $slug || ! $url) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $detail = null;

            try {
                if (! $isFullPull) {
                    sleep(rand(1, 3));
                }
                $detailResponse = $this->ringierRequest($url, 'jobs');
                if ($detailResponse->successful()) {
                    $detail = $this->extractRingierDetail($detailResponse->body());
                }
            } catch (Throwable) {
                // keep going
            }

            if (! $detail || empty($detail['title'])) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->firstOrCreateRingierCompany($detail['company_name'] ?? '', $countryId);

            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $attributes = $this->buildRingierAttributes(
                $crawler, $slug, $url, $detail, $company, $countryId, $currencyId, $defaultLocation
            );

            $existing = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $slug)
                ->first();

            if ($existing) {
                $existing->forceFill($attributes)->save();
                $this->assignGoZambiaCategory($existing, ['category' => ['name' => $detail['industry'] ?? '']]);
                $stats['jobs_updated']++;
            } else {
                $newJob = new Job();
                $newJob->forceFill($attributes);
                $this->persistNewJob($newJob, $crawler, $stats, function (Job $j) use ($detail): void {
                    $this->assignGoZambiaCategory($j, ['category' => ['name' => $detail['industry'] ?? '']]);
                    $j->jobTypes()->syncWithoutDetaching([3]);
                    $this->dispatchNewJobEvents($j);
                });
            }

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        // Extend expiry for existing jobs that have passed their expire_date
        foreach ($existingItems as $item) {
            $slug = $item['slug'] ?? '';
            if (! $slug) {
                continue;
            }

            $job = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $slug)
                ->first();

            if ($job && $job->expire_date && $job->expire_date->isPast()) {
                $job->forceFill(['expire_date' => Carbon::now()->addDays(30)])->save();
                $stats['jobs_updated']++;
            }
        }

        return $stats;
    }

    protected function extractRingierDetail(string $html): ?array
    {
        if (! preg_match('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $m)) {
            return null;
        }

        $data = json_decode($m[1], true);
        if (! $data) {
            return null;
        }

        $graph     = $data['@graph'] ?? [$data];
        $jobPosting = null;
        $companyOrg = null;

        foreach ($graph as $node) {
            $type = $node['@type'] ?? '';
            $id   = $node['@id'] ?? '';

            if ($type === 'JobPosting') {
                $jobPosting = $node;
            }

            if ($type === 'Organization' && str_contains($id, '/agency-')) {
                $companyOrg = $node;
            }
        }

        if (! $jobPosting) {
            return null;
        }

        $salary      = $jobPosting['baseSalary'] ?? [];
        $salaryValue = $salary['value'] ?? [];
        $location    = $jobPosting['jobLocation']['address'] ?? [];

        if (($jobPosting['jobLocationType'] ?? '') === 'TELECOMMUTE') {
            $locationStr = 'Remote';
        } else {
            $parts       = array_filter([
                trim($location['addressLocality'] ?? ''),
                trim($location['addressRegion'] ?? ''),
            ]);
            $locationStr = implode(', ', $parts);
        }

        return [
            'title'           => (string) ($jobPosting['title'] ?? ''),
            'description'     => (string) ($jobPosting['description'] ?? ''),
            'date_posted'     => $jobPosting['datePosted'] ?? null,
            'valid_through'   => $jobPosting['validThrough'] ?? null,
            'industry'        => (string) ($jobPosting['industry'] ?? ''),
            'employment_type' => (string) ($jobPosting['employmentType'] ?? 'FULL_TIME'),
            'location'        => $locationStr,
            'salary_from'     => $salaryValue['minValue'] ?? $salaryValue['value'] ?? null,
            'salary_to'       => $salaryValue['maxValue'] ?? $salaryValue['value'] ?? null,
            'salary_currency' => $salary['currency'] ?? null,
            'salary_unit'     => $salaryValue['unitText'] ?? null,
            'company_name'    => (string) ($companyOrg['name'] ?? ''),
        ];
    }

    protected function buildRingierAttributes(
        JobCrawler $crawler,
        string $slug,
        string $url,
        array $detail,
        Company $company,
        int $countryId,
        ?int $currencyId,
        string $defaultLocation,
    ): array {
        $postedDate = ! empty($detail['date_posted'])
            ? Carbon::parse($detail['date_posted'])
            : Carbon::now();

        $expireDate = ! empty($detail['valid_through'])
            ? Carbon::parse($detail['valid_through'])
            : $postedDate->copy()->addDays(60);

        $rawDesc    = (string) ($detail['description'] ?? '');
        $location   = (string) ($detail['location'] ?? '') ?: $defaultLocation;
        $salaryFrom = isset($detail['salary_from']) && $detail['salary_from'] !== null
            ? (float) $detail['salary_from'] : null;
        $salaryTo   = isset($detail['salary_to']) && $detail['salary_to'] !== null
            ? (float) $detail['salary_to'] : null;

        // Resolve currency from job data if available
        $effectiveCurrencyId = $currencyId;
        if ($salaryFrom && ! empty($detail['salary_currency'])) {
            $found = Currency::query()
                ->where('symbol', strtoupper(trim($detail['salary_currency'])))
                ->value('id');
            if ($found) {
                $effectiveCurrencyId = $found;
            }
        }

        return [
            'crawler_id'               => $crawler->getKey(),
            'external_source_id'       => $slug,
            'external_source_url'      => $url,
            'name'                     => $this->limitGoZambiaField(trim($detail['title']), 110),
            'description'              => Str::limit(trim(strip_tags($rawDesc)), 400, ''),
            'content'                  => $rawDesc ?: Str::limit(trim(strip_tags($rawDesc)), 400, ''),
            'company_id'               => $company->getKey(),
            'address'                  => $location,
            'country_id'               => $countryId,
            'apply_url'                => $url,
            'status'                   => JobStatusEnum::PUBLISHED,
            'moderation_status'        => ModerationStatusEnum::APPROVED,
            'salary_from'              => $salaryFrom,
            'salary_to'                => $salaryTo,
            'salary_range'             => $salaryFrom
                ? $this->careers24SalaryRange($detail['salary_unit'] ?? '')
                : SalaryRangeEnum::YEARLY,
            'salary_type'              => $salaryFrom ? SalaryTypeEnum::FIXED : SalaryTypeEnum::HIDDEN,
            'currency_id'              => $effectiveCurrencyId,
            'career_level_id'          => 3,
            'is_featured'              => false,
            'expire_date'              => $expireDate,
            'application_closing_date' => $expireDate,
            'never_expired'            => false,
            'created_at'               => $postedDate,
            'updated_at'               => $postedDate,
        ];
    }

    protected function firstOrCreateRingierCompany(string $name, int $countryId): ?Company
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $company = $this->findGoZambiaCompany($name, null);

        if (! $company) {
            $company = $this->firstOrCreateCompany([
                'name'        => $this->limitGoZambiaField($name, 110),
                'country_id'  => $countryId,
                'status'      => BaseStatusEnum::PUBLISHED,
                'is_verified' => false,
            ], $name);
            SlugHelper::createSlug($company);
        }

        return $company;
    }

    protected function ringierRequest(string $url, string $refererPath = ''): \Illuminate\Http\Client\Response
    {
        $parsed = parse_url($url);
        $base   = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        $headers = [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Connection'      => 'keep-alive',
        ];

        if ($refererPath !== '') {
            $headers['Referer'] = rtrim($base, '/') . '/' . ltrim($refererPath, '/');
        }

        return Http::timeout(20)->withHeaders($headers)->get($url);
    }

    protected function ringierPageUrl(string $sourceUrl, int $page): string
    {
        if (str_contains($sourceUrl, '{page}')) {
            return str_replace('{page}', (string) $page, $sourceUrl);
        }

        $parts = parse_url($sourceUrl);
        parse_str($parts['query'] ?? '', $query);

        if ($page > 1) {
            $query['page'] = $page;
        } else {
            unset($query['page']);
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host   = $parts['host'] ?? '';
        $path   = $parts['path'] ?? '';
        $qs     = empty($query) ? '' : '?' . http_build_query($query);

        return "{$scheme}://{$host}{$path}{$qs}";
    }

    // -------------------------------------------------------------------------
    // MyJob Mauritius (myjob.mu) — JSON API
    // -------------------------------------------------------------------------

    protected function fetchMyJobMuJobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $isFirstRun = empty($existingIds);
        $isFullPull = $this->runMode === 'full';
        $maxPages   = $isFullPull ? 60 : 20;
        $jobs       = [];
        $seenIds    = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $this->saveProgress($page, count($jobs));

            if ($page > 1) {
                sleep($isFullPull ? rand(2, 5) : rand(1, 2));
            }

            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; WakandaJobsCrawler/1.0)',
                    'Accept'     => 'application/ld+json',
                    'Referer'    => 'https://www.myjob.mu/',
                ])
                ->get("https://app.myjob.mu/api/job-board/jobs?page={$page}");

            if (! $response->successful()) {
                break;
            }

            $data    = $response->json();
            $members = $data['hydra:member'] ?? [];

            if (empty($members)) {
                break;
            }

            $newOnPage = 0;

            foreach ($members as $job) {
                $id = (string) ($job['id'] ?? '');
                if (! $id || isset($seenIds[$id])) {
                    continue;
                }
                $seenIds[$id] = true;
                $jobs[]       = $job;

                if (! array_key_exists($id, $existingIds)) {
                    $newOnPage++;
                }
            }

            if (! $isFullPull && ! $isFirstRun && $page > 1 && $newOnPage === 0) {
                break;
            }

            if (empty($data['hydra:view']['hydra:next'])) {
                break;
            }
        }

        return $jobs;
    }

    protected function importMyJobMuJobs(JobCrawler $crawler, array $items): array
    {
        $stats = [
            'jobs_found'   => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $newItems = [];

        foreach ($items as $item) {
            $id = (string) ($item['id'] ?? '');
            if ($id !== '' && array_key_exists($id, $existingIds)) {
                // Existing — optionally extend expiry
            } else {
                $newItems[] = $item;
            }
        }

        $newTotal = count($newItems);
        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $jobId = (string) ($item['id'] ?? '');
            $title = trim((string) ($item['title'] ?? ''));

            if (! $jobId || ! $title) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $description = '';
            $applyUrl    = '';

            try {
                sleep(rand(1, 2));
                $detailResponse = Http::timeout(15)
                    ->withHeaders([
                        'Accept'  => 'application/ld+json',
                        'Referer' => 'https://www.myjob.mu/',
                    ])
                    ->get("https://app.myjob.mu/api/job-board/jobs/{$jobId}");

                if ($detailResponse->successful()) {
                    $detail      = $detailResponse->json();
                    $description = (string) ($detail['description'] ?? '');
                    $applyUrl    = (string) ($detail['applyUrl'] ?? '');
                }
            } catch (Throwable) {
                // continue with list data
            }

            $companyName = (string) ($item['company']['name'] ?? '');
            $company     = $this->firstOrCreateRingierCompany($companyName, 41);

            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $slug       = (string) ($item['slug'] ?? Str::slug($title));
            $sourceUrl  = "https://www.myjob.mu/job/{$jobId}/{$slug}";
            $postedDate = $this->parseMyJobMuDate((string) ($item['postedAt'] ?? '')) ?? Carbon::now();
            $expireDate = $this->parseMyJobMuDate((string) ($item['closingAt'] ?? '')) ?? $postedDate->copy()->addDays(60);

            $rawDesc = $description;

            $attrs = [
                'crawler_id'               => $crawler->getKey(),
                'external_source_id'       => $jobId,
                'external_source_url'      => $sourceUrl,
                'name'                     => $this->limitGoZambiaField($title, 110),
                'description'              => Str::limit(trim(strip_tags($rawDesc)), 400, ''),
                'content'                  => $rawDesc ?: Str::limit(trim(strip_tags($rawDesc)), 400, ''),
                'company_id'               => $company->getKey(),
                'address'                  => (string) ($item['location'] ?? 'Mauritius'),
                'country_id'               => 41,
                'apply_url'                => $applyUrl ?: $sourceUrl,
                'status'                   => JobStatusEnum::PUBLISHED,
                'moderation_status'        => ModerationStatusEnum::APPROVED,
                'salary_type'              => SalaryTypeEnum::HIDDEN,
                'currency_id'              => 28, // MUR
                'career_level_id'          => 3,
                'is_featured'              => false,
                'expire_date'              => $expireDate,
                'application_closing_date' => $expireDate,
                'never_expired'            => false,
                'created_at'               => $postedDate,
                'updated_at'               => $postedDate,
            ];

            $existing = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $jobId)
                ->first();

            if ($existing) {
                $existing->forceFill($attrs)->save();
                $this->assignGoZambiaCategory($existing, ['category' => ['name' => $item['category']['name'] ?? '']]);
                $stats['jobs_updated']++;
            } else {
                $newJob = new Job();
                $newJob->forceFill($attrs);
                $this->persistNewJob($newJob, $crawler, $stats, function (Job $j) use ($item): void {
                    $this->assignGoZambiaCategory($j, ['category' => ['name' => $item['category']['name'] ?? '']]);
                    $j->jobTypes()->syncWithoutDetaching([3]);
                    $this->dispatchNewJobEvents($j);
                });
            }

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        return $stats;
    }

    protected function parseMyJobMuDate(string $str): ?Carbon
    {
        $str = trim(preg_replace('/^(Posted|Closing)\s+/i', '', trim($str)));

        if ($str === '') {
            return null;
        }

        try {
            return Carbon::parse($str);
        } catch (Throwable) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Jobs Tanzania (jobstanzania.co.tz)
    // -------------------------------------------------------------------------

    protected function fetchJobsTanzaniaJobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $isFirstRun = empty($existingIds);
        $isFullPull = $this->runMode === 'full';
        $baseUrl    = 'https://jobstanzania.co.tz';
        $maxPages   = $isFullPull ? 20 : 10;
        $jobs       = [];
        $seenSlugs  = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $this->saveProgress($page, count($jobs));

            if ($page > 1) {
                sleep(rand(1, 3));
            }

            $url      = "{$baseUrl}/jobs?page={$page}";
            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept'     => 'text/html,application/xhtml+xml,*/*;q=0.9',
                    'Referer'    => $baseUrl,
                ])
                ->get($url);

            if (! $response->successful()) {
                break;
            }

            $html = $response->body();
            preg_match_all('/href="\/jobs\/([^"#]+)"/', $html, $matches);
            $slugs = array_unique($matches[1]);

            if (empty($slugs)) {
                break;
            }

            $newOnPage = 0;

            foreach ($slugs as $slug) {
                if (isset($seenSlugs[$slug])) {
                    continue;
                }
                $seenSlugs[$slug] = true;
                $jobs[] = ['slug' => $slug, 'url' => "{$baseUrl}/jobs/{$slug}"];

                if (! array_key_exists($slug, $existingIds)) {
                    $newOnPage++;
                }
            }

            if (! $isFullPull && ! $isFirstRun && $page > 1 && $newOnPage === 0) {
                break;
            }
        }

        return $jobs;
    }

    protected function importJobsTanzaniaJobs(JobCrawler $crawler, array $items): array
    {
        $stats = [
            'jobs_found'   => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $newItems = array_values(array_filter(
            $items,
            fn ($i) => ! array_key_exists($i['slug'] ?? '', $existingIds)
        ));
        $newTotal = count($newItems);

        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $slug = $item['slug'] ?? '';
            $url  = $item['url'] ?? '';

            if (! $slug || ! $url) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $detail = null;

            try {
                sleep(rand(1, 3));
                $detailResponse = Http::timeout(20)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                        'Referer'    => 'https://jobstanzania.co.tz/jobs',
                    ])
                    ->get($url);

                if ($detailResponse->successful()) {
                    $detail = $this->extractJobsTanzaniaDetail($detailResponse->body());
                }
            } catch (Throwable) {
                // skip
            }

            if (! $detail || empty($detail['title'])) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->firstOrCreateRingierCompany($detail['company_name'] ?? '', 56);

            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $postedDate = ! empty($detail['date_posted'])
                ? Carbon::parse($detail['date_posted'])
                : Carbon::now();

            $expireDate = ! empty($detail['valid_through'])
                ? Carbon::parse($detail['valid_through'])
                : $postedDate->copy()->addDays(30);

            $rawDesc = (string) ($detail['description'] ?? '');

            $attrs = [
                'crawler_id'               => $crawler->getKey(),
                'external_source_id'       => $slug,
                'external_source_url'      => $url,
                'name'                     => $this->limitGoZambiaField(trim($detail['title']), 110),
                'description'              => Str::limit(trim(strip_tags($rawDesc)), 400, ''),
                'content'                  => $rawDesc ?: Str::limit(trim(strip_tags($rawDesc)), 400, ''),
                'company_id'               => $company->getKey(),
                'address'                  => (string) ($detail['location'] ?? 'Tanzania'),
                'country_id'               => 56,
                'apply_url'                => $url,
                'status'                   => JobStatusEnum::PUBLISHED,
                'moderation_status'        => ModerationStatusEnum::APPROVED,
                'salary_type'              => SalaryTypeEnum::HIDDEN,
                'currency_id'              => 42, // TZS
                'career_level_id'          => 3,
                'is_featured'              => false,
                'expire_date'              => $expireDate,
                'application_closing_date' => $expireDate,
                'never_expired'            => false,
                'created_at'               => $postedDate,
                'updated_at'               => $postedDate,
            ];

            $existing = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $slug)
                ->first();

            if ($existing) {
                $existing->forceFill($attrs)->save();
                $this->assignGoZambiaCategory($existing, ['category' => ['name' => $detail['industry'] ?? '']]);
                $stats['jobs_updated']++;
            } else {
                $newJob = new Job();
                $newJob->forceFill($attrs);
                $this->persistNewJob($newJob, $crawler, $stats, function (Job $j) use ($detail): void {
                    $this->assignGoZambiaCategory($j, ['category' => ['name' => $detail['industry'] ?? '']]);
                    $j->jobTypes()->syncWithoutDetaching([3]);
                    $this->dispatchNewJobEvents($j);
                });
            }

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        return $stats;
    }

    protected function extractJobsTanzaniaDetail(string $html): ?array
    {
        // Try application/ld+json block first, then any <script> with @type=JobPosting
        $candidates = [];

        preg_match_all('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $m1);
        $candidates = array_merge($candidates, $m1[1] ?? []);

        preg_match_all('/<script[^>]*>(.*?"@type"\s*:\s*"JobPosting".*?)<\/script>/s', $html, $m2);
        $candidates = array_merge($candidates, $m2[1] ?? []);

        foreach ($candidates as $block) {
            try {
                $data = json_decode($block, true);
                if (! $data || ($data['@type'] ?? '') !== 'JobPosting') {
                    continue;
                }

                $location = $data['jobLocation']['address'] ?? [];
                $parts    = array_filter([
                    trim($location['addressLocality'] ?? ''),
                    trim($location['addressCountry'] ?? ''),
                ]);

                return [
                    'title'        => (string) ($data['title'] ?? ''),
                    'description'  => (string) ($data['description'] ?? ''),
                    'date_posted'  => $data['datePosted'] ?? null,
                    'valid_through'=> $data['validThrough'] ?? null,
                    'company_name' => (string) ($data['hiringOrganization']['name'] ?? ''),
                    'location'     => implode(', ', $parts),
                    'industry'     => (string) ($data['occupationalCategory'] ?? ''),
                ];
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Africawork network (emploi.ma Morocco, emploi.cm Cameroon)
    // -------------------------------------------------------------------------

    protected function fetchAfricaworkJobs(JobCrawler $crawler): array
    {
        $mappings   = $crawler->field_mappings ?? [];
        $searchPath = (string) ($mappings['search_path'] ?? 'recherche-jobs-maroc');
        $baseUrl    = rtrim((string) $crawler->source_url, '/');

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $isFirstRun = empty($existingIds);
        $isFullPull = $this->runMode === 'full';
        $maxPages   = $isFullPull ? 40 : 10;
        $jobs       = [];
        $seenIds    = [];

        for ($page = 0; $page <= $maxPages; $page++) {
            $this->saveProgress($page + 1, count($jobs));

            if ($page > 0) {
                sleep($isFullPull ? rand(2, 5) : rand(1, 3));
            }

            $url      = "{$baseUrl}/{$searchPath}" . ($page > 0 ? "?page={$page}" : '');
            $response = $this->africaworkRequest($url, $baseUrl);

            if (! $response->successful()) {
                break;
            }

            $html = $response->body();

            // Job URLs: /offre-emploi-maroc/slug-ID or /offre-emploi-cameroun/slug-ID
            preg_match_all('|href="(/offre-emploi-[a-z]+/([^"]+))"|', $html, $m);

            if (empty($m[1])) {
                break;
            }

            $newOnPage = 0;

            foreach ($m[1] as $i => $path) {
                $slug = basename($path);
                // Extract numeric ID from end of slug
                $id   = preg_match('/-(\d+)$/', $slug, $idM) ? $idM[1] : $slug;

                if (isset($seenIds[$id])) {
                    continue;
                }
                $seenIds[$id] = true;
                $jobs[]       = ['id' => $id, 'url' => $baseUrl . $path, 'slug' => $slug];

                if (! array_key_exists($id, $existingIds)) {
                    $newOnPage++;
                }
            }

            if (! $isFullPull && ! $isFirstRun && $page > 0 && $newOnPage === 0) {
                break;
            }
        }

        return $jobs;
    }

    protected function importAfricaworkJobs(JobCrawler $crawler, array $items): array
    {
        $mappings        = $crawler->field_mappings ?? [];
        $countryId       = (int) ($mappings['country_id'] ?? 0);
        $currencyId      = isset($mappings['currency_id']) ? (int) $mappings['currency_id'] : null;
        $defaultLocation = (string) ($mappings['default_location'] ?? '');

        $stats = [
            'jobs_found'   => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $newItems = array_values(array_filter(
            $items,
            fn ($i) => ! array_key_exists($i['id'] ?? '', $existingIds)
        ));
        $newTotal = count($newItems);

        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $id  = $item['id'] ?? '';
            $url = $item['url'] ?? '';

            if (! $id || ! $url) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $detail = null;

            try {
                sleep(rand(1, 3));
                $resp = $this->africaworkRequest($url, (string) $crawler->source_url);
                if ($resp->successful()) {
                    $detail = $this->extractAfricaworkDetail($resp->body());
                }
            } catch (Throwable) {
                // skip
            }

            if (! $detail || empty($detail['title'])) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->firstOrCreateRingierCompany($detail['company'] ?? '', $countryId);

            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $postedDate = ! empty($detail['date_posted'])
                ? Carbon::parse($detail['date_posted'])
                : Carbon::now();

            $expireDate = ! empty($detail['valid_through'])
                ? Carbon::parse($detail['valid_through'])
                : $postedDate->copy()->addDays(60);

            $rawDesc = (string) ($detail['description'] ?? '');

            $attrs = [
                'crawler_id'               => $crawler->getKey(),
                'external_source_id'       => $id,
                'external_source_url'      => $url,
                'name'                     => $this->limitGoZambiaField($detail['title'], 110),
                'description'              => Str::limit(trim(strip_tags($rawDesc)), 400, ''),
                'content'                  => $rawDesc,
                'company_id'               => $company->getKey(),
                'address'                  => $detail['location'] ?: $defaultLocation,
                'country_id'               => $countryId,
                'apply_url'                => $url,
                'status'                   => JobStatusEnum::PUBLISHED,
                'moderation_status'        => ModerationStatusEnum::APPROVED,
                'salary_type'              => SalaryTypeEnum::HIDDEN,
                'currency_id'              => $currencyId,
                'career_level_id'          => 3,
                'is_featured'              => false,
                'expire_date'              => $expireDate,
                'application_closing_date' => $expireDate,
                'never_expired'            => false,
                'created_at'               => $postedDate,
                'updated_at'               => $postedDate,
            ];

            $existing = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $id)
                ->first();

            if ($existing) {
                $existing->forceFill($attrs)->save();
                $stats['jobs_updated']++;
            } else {
                $newJob = new Job();
                $newJob->forceFill($attrs);
                $this->persistNewJob($newJob, $crawler, $stats, function (Job $j): void {
                    $j->jobTypes()->syncWithoutDetaching([3]);
                    $this->dispatchNewJobEvents($j);
                });
            }

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        return $stats;
    }

    protected function extractAfricaworkDetail(string $html): array
    {
        // Extract metadata fields via regex (JSON-LD contains control chars that break json_decode)
        $title       = '';
        $company     = '';
        $location    = '';
        $datePosted  = null;
        $validThrough = null;
        $description = '';

        if (preg_match('/"title"\s*:\s*"([^"]+)"/', $html, $m)) {
            $title = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (preg_match('/"datePosted"\s*:\s*"([^"]+)"/', $html, $m)) {
            $datePosted = $m[1];
        }

        if (preg_match('/"validThrough"\s*:\s*"([^"]+)"/', $html, $m)) {
            $validThrough = $m[1];
        }

        if (preg_match('/"hiringOrganization"\s*:\s*\{[^}]*"name"\s*:\s*"([^"]+)"/', $html, $m)) {
            $company = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (preg_match('/"addressLocality"\s*:\s*"([^"]+)"/', $html, $m)) {
            $location = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Description from field--name-body div (the job text content area)
        if (preg_match('/class="[^"]*field--name-body[^"]*"[^>]*>(.*?)<\/div>/s', $html, $m)) {
            $description = trim($m[1]);
        }

        return compact('title', 'company', 'location', 'datePosted', 'validThrough', 'description') + [
            'date_posted'  => $datePosted,
            'valid_through'=> $validThrough,
        ];
    }

    protected function africaworkRequest(string $url, string $base = ''): \Illuminate\Http\Client\Response
    {
        $headers = [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept'          => 'text/html,application/xhtml+xml,*/*;q=0.9',
            'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.7',
        ];

        if ($base !== '') {
            $headers['Referer'] = $base;
        }

        return Http::timeout(20)->withHeaders($headers)->get($url);
    }

    // -------------------------------------------------------------------------
    // Job in Rwanda (jobinrwanda.com)
    // -------------------------------------------------------------------------

    protected function fetchJobInRwandaJobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $this->saveProgress(1, 0);

        $response = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept'     => 'text/html,application/xhtml+xml,*/*;q=0.9',
                'Referer'    => 'https://www.jobinrwanda.com',
            ])
            ->get('https://www.jobinrwanda.com/jobs/all');

        if (! $response->successful()) {
            return [];
        }

        $html = $response->body();

        // Remove HTML comments (Drupal inserts theme-debug comments)
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Match articles and extract title+slug from anchor, then summary from col-10 div
        preg_match_all(
            '/<article[^>]*data-history-node-id="(\d+)"[^>]*>(.*?)<\/article>/s',
            $html,
            $articles
        );

        $jobs     = [];
        $seenIds  = [];

        foreach ($articles[1] as $i => $nid) {
            $artHtml = $articles[2][$i];

            // Job title from anchor to /job/slug
            if (! preg_match('|href="(/job/([^"]+))"|', $artHtml, $linkM)) {
                continue;
            }

            $slug  = $linkM[2];
            $jobUrl = 'https://www.jobinrwanda.com' . $linkM[1];

            // Title from the span with field--name-title class
            preg_match('/class="[^"]*field--name-title[^"]*"[^>]*>(.*?)<\/span>/s', $artHtml, $titleM);
            $title = $titleM ? trim(strip_tags($titleM[1])) : $slug;

            if (isset($seenIds[$nid]) || ! $title) {
                continue;
            }
            $seenIds[$nid] = true;

            // Extract company, location, deadline from col-10 div summary text
            $company  = '';
            $location = '';
            $deadline = null;
            $posted   = null;

            if (preg_match('/class="col-10"[^>]*>(.*?)<\/div>/s', $artHtml, $sumM)) {
                $sumText = trim(strip_tags($sumM[1]));
                $sumText = preg_replace('/\s+/', ' ', $sumText);

                // Format: "Title Company | Location | Published on dd-mm-yyyy | Deadline dd-mm-yyyy ..."
                $afterTitle = trim(substr($sumText, strlen($title)));
                if (preg_match('/^(.+?)\s*\|\s*(.+?)\s*\|\s*Published on\s+([0-9\-]+)\s*\|\s*Deadline\s+([0-9\-]+)/i', $afterTitle, $p)) {
                    $company  = trim($p[1]);
                    $location = trim($p[2]);
                    $posted   = $p[3];
                    $deadline = $p[4];
                }
            }

            $jobs[] = [
                'id'       => $nid,
                'slug'     => $slug,
                'url'      => $jobUrl,
                'title'    => $title,
                'company'  => $company,
                'location' => $location,
                'posted'   => $posted,
                'deadline' => $deadline,
            ];
        }

        $this->saveProgress(1, count($jobs));

        return $jobs;
    }

    protected function importJobInRwandaJobs(JobCrawler $crawler, array $items): array
    {
        $stats = [
            'jobs_found'   => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $newItems = array_values(array_filter(
            $items,
            fn ($i) => ! array_key_exists($i['id'] ?? '', $existingIds)
        ));
        $newTotal = count($newItems);

        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $id    = $item['id'] ?? '';
            $url   = $item['url'] ?? '';
            $title = trim($item['title'] ?? '');

            if (! $id || ! $title) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            // Fetch detail page for full description
            $description = '';

            try {
                sleep(rand(1, 3));
                $resp = Http::timeout(20)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                        'Referer'    => 'https://www.jobinrwanda.com/jobs/all',
                    ])
                    ->get($url);

                if ($resp->successful()) {
                    $description = $this->extractJobInRwandaDescription($resp->body());
                }
            } catch (Throwable) {
                // use empty description
            }

            $company = $this->firstOrCreateRingierCompany($item['company'] ?? '', 47);

            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $postedDate = ! empty($item['posted'])
                ? (Carbon::createFromFormat('d-m-Y', $item['posted']) ?: Carbon::now())
                : Carbon::now();

            $expireDate = ! empty($item['deadline'])
                ? (Carbon::createFromFormat('d-m-Y', $item['deadline']) ?: $postedDate->copy()->addDays(30))
                : $postedDate->copy()->addDays(30);

            $attrs = [
                'crawler_id'               => $crawler->getKey(),
                'external_source_id'       => $id,
                'external_source_url'      => $url,
                'name'                     => $this->limitGoZambiaField($title, 110),
                'description'              => Str::limit(trim(strip_tags($description)), 400, ''),
                'content'                  => $description,
                'company_id'               => $company->getKey(),
                'address'                  => (string) ($item['location'] ?? 'Rwanda'),
                'country_id'               => 47, // Rwanda
                'apply_url'                => $url,
                'status'                   => JobStatusEnum::PUBLISHED,
                'moderation_status'        => ModerationStatusEnum::APPROVED,
                'salary_type'              => SalaryTypeEnum::HIDDEN,
                'currency_id'              => 33, // RWF
                'career_level_id'          => 3,
                'is_featured'              => false,
                'expire_date'              => $expireDate,
                'application_closing_date' => $expireDate,
                'never_expired'            => false,
                'created_at'               => $postedDate,
                'updated_at'               => $postedDate,
            ];

            $existing = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $id)
                ->first();

            if ($existing) {
                $existing->forceFill($attrs)->save();
                $stats['jobs_updated']++;
            } else {
                $newJob = new Job();
                $newJob->forceFill($attrs);
                $this->persistNewJob($newJob, $crawler, $stats, function (Job $j): void {
                    $j->jobTypes()->syncWithoutDetaching([3]);
                    $this->dispatchNewJobEvents($j);
                });
            }

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        return $stats;
    }

    protected function extractJobInRwandaDescription(string $html): string
    {
        // Extract the main content area from the Drupal page
        if (preg_match('/<main[^>]*>(.*?)<\/main>/s', $html, $m)) {
            $main = preg_replace('/<script[^>]*>.*?<\/script>/s', '', $m[1]);
            $main = preg_replace('/<style[^>]*>.*?<\/style>/s', '', $main);
            $main = preg_replace('/<!--.*?-->/s', '', $main);
            // Keep basic HTML structure (strip complex elements, keep p/ul/li/b/strong)
            $main = preg_replace('/<(?!\/?(p|ul|ol|li|b|strong|em|br|h[1-6])\b)[^>]+>/i', ' ', $main);
            $main = preg_replace('/\s{3,}/', "\n", $main);

            return trim($main);
        }

        return '';
    }

    // -------------------------------------------------------------------------
    // Keejob Tunisia (keejob.com)
    // -------------------------------------------------------------------------

    protected function fetchKeejobJobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $isFirstRun = empty($existingIds);
        $isFullPull = $this->runMode === 'full';
        $maxPages   = $isFullPull ? 60 : 15;
        $jobs       = [];
        $seenIds    = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $this->saveProgress($page, count($jobs));

            if ($page > 1) {
                sleep($isFullPull ? rand(3, 7) : rand(1, 3));
            }

            $url      = 'https://www.keejob.com/offres-emploi/' . ($page > 1 ? "?page={$page}" : '');
            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept'          => 'text/html,application/xhtml+xml,*/*;q=0.9',
                    'Accept-Language' => 'fr-TN,fr;q=0.9,en;q=0.7',
                    'Referer'         => 'https://www.keejob.com/',
                ])
                ->get($url);

            if (! $response->successful()) {
                break;
            }

            $html = $response->body();

            // Extract job links: /offres-emploi/ID/slug/
            preg_match_all('|href="(/offres-emploi/(\d+)/[^"]+)"|', $html, $m);

            if (empty($m[1])) {
                break;
            }

            $newOnPage = 0;

            foreach ($m[2] as $i => $id) {
                if (isset($seenIds[$id])) {
                    continue;
                }
                $seenIds[$id] = true;
                $jobs[]       = [
                    'id'  => $id,
                    'url' => 'https://www.keejob.com' . $m[1][$i],
                ];

                if (! array_key_exists($id, $existingIds)) {
                    $newOnPage++;
                }
            }

            if (! $isFullPull && ! $isFirstRun && $page > 1 && $newOnPage === 0) {
                break;
            }
        }

        return $jobs;
    }

    protected function importKeejobJobs(JobCrawler $crawler, array $items): array
    {
        $stats = [
            'jobs_found'   => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $newItems = array_values(array_filter(
            $items,
            fn ($i) => ! array_key_exists($i['id'] ?? '', $existingIds)
        ));
        $newTotal = count($newItems);

        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $id  = $item['id'] ?? '';
            $url = $item['url'] ?? '';

            if (! $id || ! $url) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $detail = null;

            try {
                sleep(rand(1, 3));
                $resp = Http::timeout(20)
                    ->withHeaders([
                        'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                        'Accept'          => 'text/html,application/xhtml+xml,*/*;q=0.9',
                        'Accept-Language' => 'fr-TN,fr;q=0.9,en;q=0.7',
                        'Referer'         => 'https://www.keejob.com/offres-emploi/',
                    ])
                    ->get($url);

                if ($resp->successful()) {
                    $detail = $this->extractKeejobDetail($resp->body(), $url);
                }
            } catch (Throwable) {
                // skip
            }

            if (! $detail || empty($detail['title'])) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->firstOrCreateRingierCompany($detail['company'] ?? '', 58);

            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $postedDate = ! empty($detail['date_posted'])
                ? Carbon::parse($detail['date_posted'])
                : Carbon::now();

            $expireDate = $postedDate->copy()->addDays(45);

            $rawDesc = (string) ($detail['description'] ?? '');

            $attrs = [
                'crawler_id'               => $crawler->getKey(),
                'external_source_id'       => $id,
                'external_source_url'      => $url,
                'name'                     => $this->limitGoZambiaField($detail['title'], 110),
                'description'              => Str::limit(trim(strip_tags($rawDesc)), 400, ''),
                'content'                  => $rawDesc,
                'company_id'               => $company->getKey(),
                'address'                  => (string) ($detail['location'] ?? 'Tunisia'),
                'country_id'               => 58, // Tunisia
                'apply_url'                => $url,
                'status'                   => JobStatusEnum::PUBLISHED,
                'moderation_status'        => ModerationStatusEnum::APPROVED,
                'salary_type'              => SalaryTypeEnum::HIDDEN,
                'currency_id'              => 41, // TND
                'career_level_id'          => 3,
                'is_featured'              => false,
                'expire_date'              => $expireDate,
                'application_closing_date' => $expireDate,
                'never_expired'            => false,
                'created_at'               => $postedDate,
                'updated_at'               => $postedDate,
            ];

            $existing = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $id)
                ->first();

            if ($existing) {
                $existing->forceFill($attrs)->save();
                $stats['jobs_updated']++;
            } else {
                $newJob = new Job();
                $newJob->forceFill($attrs);
                $this->persistNewJob($newJob, $crawler, $stats, function (Job $j): void {
                    $j->jobTypes()->syncWithoutDetaching([3]);
                    $this->dispatchNewJobEvents($j);
                });
            }

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        return $stats;
    }

    protected function extractKeejobDetail(string $html, string $url): array
    {
        // Title from H1
        $title = '';
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/', $html, $m)) {
            $title = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Company from meta author
        $company = '';
        if (preg_match('/<meta[^>]+name="author"[^>]+content="([^"]+)"/i', $html, $m)) {
            $company = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Location from OG title: "... chez Company à Location sur Keejob"
        $location = '';
        if (preg_match('/property="og:title"[^>]+content="[^"]+\s+à\s+(.+?)\s+sur\s+Keejob/i', $html, $m)) {
            $location = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Published date: "Publiée le DD mois YYYY"
        $datePosted = null;
        if (preg_match('/Publiée? le\s+(\d{1,2})\s+([a-zéûôàè]+)\s+(\d{4})/iu', $html, $m)) {
            $frMonths = [
                'janvier' => 1, 'février' => 2, 'mars' => 3, 'avril' => 4,
                'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8,
                'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12,
            ];
            $monthNum = $frMonths[mb_strtolower($m[2])] ?? null;
            if ($monthNum) {
                $datePosted = sprintf('%04d-%02d-%02d', (int) $m[3], $monthNum, (int) $m[1]);
            }
        }

        // Description from the inline script block: {"title":"...","description":"<html>"}
        $description = '';
        if (preg_match('/<script[^>]*>\s*\{[^}]*"title"\s*:[^}]*"description"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $html, $m)) {
            $decoded = json_decode('"' . $m[1] . '"');
            if ($decoded !== null) {
                $description = $decoded;
            }
        }

        return compact('title', 'company', 'location', 'description') + [
            'date_posted' => $datePosted,
        ];
    }
}
