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
    private const CRAWLER_SITE_LOGO_HASHES = [
        'bad6132f476edb4e877594f968b6562ebdbf72447810493b8b62487dadcf9db1',
    ];

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

            try {
                $botToken  = setting('telegram_bot_token');
                $adminChat = setting('telegram_admin_chat_id');
                if ($botToken && $adminChat) {
                    $fullError = $exception->getMessage();
                    $msg = "🚨 *Crawler Failed*\n"
                        . "*Agent:* " . $crawler->name . "\n"
                        . "*Error:* " . mb_substr($fullError, 0, 300);

                    $resp = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                        'chat_id'    => $adminChat,
                        'text'       => $msg,
                        'parse_mode' => 'Markdown',
                    ]);

                    $messageId = data_get($resp->json(), 'result.message_id');
                    if ($messageId) {
                        $cacheKey = 'tg_crawler_err_' . Str::uuid();
                        \Illuminate\Support\Facades\Cache::put($cacheKey, $fullError, now()->addDays(7));

                        $copyUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                            'public.telegram-crawler-error-copy',
                            now()->addDays(7),
                            ['cache_key' => $cacheKey, 'crawler_name' => $crawler->name]
                        );

                        Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/editMessageReplyMarkup", [
                            'chat_id'      => $adminChat,
                            'message_id'   => $messageId,
                            'reply_markup' => [
                                'inline_keyboard' => [[
                                    ['text' => '📋 Copy Error', 'url' => $copyUrl],
                                ]],
                            ],
                        ]);
                    }
                }
            } catch (Throwable) {
                // Don't let Telegram failure mask the crawler error
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

    protected function normalizeExternalSourceId(mixed $value): string
    {
        $sourceId = trim((string) $value);

        if (mb_strlen($sourceId) <= 255) {
            return $sourceId;
        }

        return 'sha256:' . hash('sha256', $sourceId);
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

        if ($crawler->parser_type === 'jobsearchzm') {
            return $this->fetchJobSearchZmJobs($crawler);
        }

        if ($crawler->parser_type === 'vacancybox') {
            return $this->fetchVacancyBoxJobs($crawler);
        }

        if ($crawler->parser_type === 'jobzambia') {
            return $this->fetchJobZambiaJobs($crawler);
        }

        if ($crawler->parser_type === 'jobmail') {
            return $this->fetchJobMailJobs($crawler);
        }

        if ($crawler->parser_type === 'myjobmag') {
            return $this->fetchMyJobMagJobs($crawler);
        }

        if ($crawler->parser_type === 'wpjobmanager') {
            return $this->fetchWpJobManagerJobs($crawler);
        }

        if ($crawler->parser_type === 'empregomz') {
            return $this->fetchEmpregoMzJobs($crawler);
        }

        if ($crawler->parser_type === 'noojobmonster') {
            return $this->fetchNooJobMonsterJobs($crawler);
        }

        if ($crawler->parser_type === 'emploitic') {
            return $this->fetchEmploiticJobs($crawler);
        }

        if ($crawler->parser_type === 'ethiojobs') {
            return $this->fetchEthioJobsJobs($crawler);
        }

        if ($crawler->parser_type === 'novojob') {
            return $this->fetchNovojobJobs($crawler);
        }

        if ($crawler->parser_type === 'vacancymail') {
            return $this->fetchVacancyMailJobs($crawler);
        }

        if ($crawler->parser_type === 'jobpoint') {
            return $this->fetchJobPointJobs($crawler);
        }

        if ($crawler->parser_type === 'ijob') {
            return $this->fetchIJobJobs($crawler);
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

        if ($crawler->parser_type === 'jobsearchzm') {
            return $this->importJobSearchZmJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'vacancybox') {
            return $this->importVacancyBoxJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'jobzambia') {
            return $this->importJobZambiaJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'jobmail') {
            return $this->importJobMailJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'myjobmag') {
            return $this->importMyJobMagJobs($crawler, $items);
        }

        if (in_array($crawler->parser_type, ['wpjobmanager', 'empregomz', 'noojobmonster', 'emploitic'], true)) {
            return $this->importStructuredJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'ethiojobs') {
            return $this->importEthioJobsJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'novojob') {
            return $this->importNovojobJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'vacancymail') {
            return $this->importVacancyMailJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'jobpoint') {
            return $this->importJobPointJobs($crawler, $items);
        }

        if ($crawler->parser_type === 'ijob') {
            return $this->importIJobJobs($crawler, $items);
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
                $job->fill($attributes);
                $this->resolveApplyContact($job);
                $job->save();
                $stats['jobs_updated']++;
            } else {
                $newJob = new Job();
                $newJob->forceFill($attributes);
                $this->resolveApplyContact($newJob);
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
                    $attributes = [
                        'name' => $this->limitGoZambiaField(trim((string) ($item['title'] ?? '')), 110),
                    ];

                    // Only refresh expiry if the list data actually carries a real
                    // validThrough date — list extraction never sets this, so without
                    // this guard every run was overwriting real detail-page expiry
                    // dates with a rolling "now + 30 days" placeholder.
                    if (isset($item['validThrough'])) {
                        $expireDate = Carbon::parse($item['validThrough']);
                        $attributes['expire_date'] = $expireDate;
                        $attributes['application_closing_date'] = $expireDate;
                    }

                    $job->forceFill($attributes)->save();
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

            $company = $this->firstOrCreateGoZambiaCompany($crawler, (array) data_get($item, 'employer', []));
            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            // Only new jobs get a detail-page fetch.
            $detailPath = (string) data_get($item, 'job_details_path');
            $detailUrl = $this->absoluteGoZambiaUrl($crawler, $detailPath);

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
                $this->resolveApplyContact($newJob);
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
        $deleted = 0;

        // Pass 1: same external_source_id (exact DB-level duplicates).
        $duplicates = DB::table('jb_jobs')
            ->select('external_source_id')
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->groupBy('external_source_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('external_source_id');

        foreach ($duplicates as $sourceId) {
            $jobs = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $sourceId)
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [(string) JobStatusEnum::PUBLISHED])
                ->latest('updated_at')
                ->latest('id')
                ->get();

            $this->pickDedupKeeper($jobs);

            foreach ($jobs as $job) {
                $job->delete();
                $deleted++;
            }
        }

        // Pass 2: same name + company + address (re-posts with different external IDs).
        $contentDuplicates = DB::table('jb_jobs')
            ->select('name', 'company_id', 'address')
            ->where('crawler_id', $crawler->getKey())
            ->groupBy('name', 'company_id', 'address')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($contentDuplicates as $dup) {
            $jobs = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('name', $dup->name)
                ->where('company_id', $dup->company_id)
                ->where('address', $dup->address)
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [(string) JobStatusEnum::PUBLISHED])
                ->orderBy('id')
                ->get();

            $this->pickDedupKeeper($jobs);

            foreach ($jobs as $job) {
                $job->delete();
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Remove and return the job to keep from a duplicate group, mutating $jobs
     * in place to leave only the ones to delete. Prefers a job that has already
     * been posted to a social channel, so dedup never breaks an already-shared
     * Telegram post-kit link. Falls back to the existing first-in-order job.
     */
    protected function pickDedupKeeper(\Illuminate\Support\Collection $jobs): Job
    {
        $postedJobIds = DB::table('telegram_message_log')
            ->whereIn('job_id', $jobs->pluck('id'))
            ->pluck('job_id')
            ->flip();

        foreach ($jobs as $index => $job) {
            if ($postedJobIds->has($job->id)) {
                return $jobs->pull($index);
            }
        }

        return $jobs->shift();
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
            ?: $this->absoluteGoZambiaUrl($crawler, (string) data_get($item, 'job_details_path'));
        $description = $this->sanitizeGoZambiaHtml((string) data_get($item, 'description'));
        $address = data_get($item, 'job_location.name')
            ?: data_get($item, 'location')
            ?: $this->goZambiaCountryName($crawler);

        return [
            'crawler_id' => $crawler->getKey(),
            'external_source_id' => (string) data_get($item, 'id'),
            'external_source_url' => $sourceUrl,
            'name' => $this->limitGoZambiaField(trim((string) data_get($item, 'title')), 110),
            'description' => Str::limit(trim(strip_tags($description)), 400, ''),
            'content' => $description ?: Str::limit(trim(strip_tags($description)), 400, ''),
            'company_id' => $company->getKey(),
            'address' => $address,
            'country_id' => $this->goZambiaCountryId($crawler),
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

        // Don't send job alerts for jobs that are already past their deadline at import time.
        $deadline = $job->application_closing_date ?? $job->expire_date ?? null;
        if ($deadline && $deadline->isPast()) {
            return;
        }

        event(new JobPublishedEvent($job));
    }

    /**
     * Save a new Job, create its slug, and run $configure (category/types/events).
     * Silently skips on a unique-key violation (1062) — concurrent run already saved it.
     * Also skips when a job with the same name+company+address already exists for this
     * crawler — prevents importing the same listing re-posted with a new external ID.
     * Increments $stats['jobs_created'] on success, 'jobs_skipped' on duplicate.
     */
    /**
     * Extract the first email address from a job's HTML description/content.
     * Returns null when none is found.
     */
    public static function extractEmailFromHtml(?string $html): ?string
    {
        return static::extractAllEmailsFromHtml($html)[0] ?? null;
    }

    /**
     * Extract all unique email addresses from a job's HTML description/content.
     */
    public static function extractAllEmailsFromHtml(?string $html): array
    {
        if (! $html) {
            return [];
        }

        // Replace tags with a space so adjacent elements don't merge (e.g. "email.com<b>Male" → "email.com Male").
        $text = html_entity_decode(preg_replace('/<[^>]+>/', ' ', $html) ?? '');

        preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,6}(?![a-zA-Z0-9])/i', $text, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[0] ?? [])));
    }

    /**
     * Resolve the apply_email and apply_url for a crawled job.
     * If emails are found in the description → build a mailto: URL (with CC if multiple).
     * Otherwise fall back to company website, then the original apply_url.
     */
    public function resolveApplyContact(Job $job): void
    {
        $emails = static::extractAllEmailsFromHtml($job->getRawOriginal('content') ?: $job->getRawOriginal('description'));

        if ($emails) {
            $job->apply_email = $emails[0];
            $subject = rawurlencode(trim(strip_tags((string) $job->name)) . ' Application');
            $mailto = 'mailto:' . $emails[0];
            $params = ['subject=' . $subject];
            if (count($emails) > 1) {
                $params[] = 'cc=' . implode(',', array_slice($emails, 1));
            }
            $job->apply_url = $mailto . '?' . implode('&', $params);
            return;
        }

        // No email — fall back to external source URL, then company website.
        $sourceUrl = trim((string) $job->external_source_url);
        if ($sourceUrl) {
            $job->apply_url = $sourceUrl;
            return;
        }

        $website = $job->company?->website;
        if ($website) {
            $job->apply_url = $website;
        }
    }

    protected function persistNewJob(Job $job, JobCrawler $crawler, array &$stats, callable $configure): void
    {
        // Content-duplicate guard: same title + company across ALL crawlers catches the
        // same job syndicated to multiple sites (e.g. jobzambia + gozambiajobs).
        $contentDupe = Job::query()
            ->where('name', $job->name)
            ->where('company_id', $job->company_id)
            ->exists();

        if ($contentDupe) {
            $stats['jobs_skipped']++;
            return;
        }

        // Content-moderation guard: block jobs that mention our own site or contain
        // watermark text from competitors re-posting our content.
        if ($this->hasBannedContent($job->name . ' ' . $job->description . ' ' . $job->content)) {
            $stats['jobs_skipped']++;
            return;
        }

        $job->external_source_id = $this->normalizeExternalSourceId($job->external_source_id);

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
     * Returns true if $text contains content that must never be imported.
     * This catches jobs scraped from sites that have re-posted our content,
     * including our own anti-scraping watermark messages.
     */
    protected function hasBannedContent(string $text): bool
    {
        $patterns = [
            // Our own site mentioned in crawled content = stolen from us
            '/wakandajobs\.com/i',
            '/wakanda\s+jobs\.com/i',
            // Known offending site
            '/\bjobwebzambia\b/i',
            // Anti-scraping watermark text we've placed on our site
            '/stop\s+trying\s+to\s+copy\s+our\s+site/i',
            '/stop\s+copying\s+our/i',
            '/reposting\s+the\s+same\s+content\s+shortly\s+after/i',
            '/build\s+your\s+own\s+ideas.*create\s+your\s+own\s+content/is',
            '/waiting\s+for\s+us\s+to\s+post\s+jobs/i',
            '/operating\s+from\s+ghana.*copy\s+our/is',
            '/shadowing\s+others.*wakanda/is',
            '/tired\s+of\s+the\s+copying/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send a Telegram alert to the admin personal chat when a banned job is caught.
     */
    protected function sendBannedContentAlert(Job $job, JobCrawler $crawler): void
    {
        try {
            $token  = setting('telegram_bot_token', '');
            $chatId = '5777916704'; // Admin personal chat

            if ($token === '' || $chatId === '') {
                return;
            }

            $excerpt = mb_substr(strip_tags((string) ($job->description ?: $job->content)), 0, 300);
            $msg     = "🚨 *Banned Content Blocked*\n\n"
                . "*Job:* " . $job->name . "\n"
                . "*Crawler:* " . $crawler->name . "\n"
                . "*Source:* " . ($job->external_source_url ?: 'unknown') . "\n\n"
                . "*Excerpt:*\n" . $excerpt;

            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $msg,
                'parse_mode' => 'Markdown',
            ]);
        } catch (Throwable) {
            // Never let the alert prevent normal crawler operation
        }
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

    protected function firstOrCreateGoZambiaCompany(JobCrawler $crawler, array $employer): ?Company
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
            'country_id' => $this->goZambiaCountryId($crawler),
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

        $company = Company::query()
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

        if ($company) {
            return $company;
        }

        // No live company matches — check whether this name/website previously belonged to a
        // company that has since been merged into another one, so we don't recreate a duplicate.
        return app(CompanyMergeService::class)->resolveByNameOrWebsite($name, $website);
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

            if ($this->isCrawlerSiteLogo($path)) {
                return $this->defaultCompanyLogo();
            }

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

    protected function isCrawlerSiteLogo(string $path): bool
    {
        return in_array(hash_file('sha256', $path), self::CRAWLER_SITE_LOGO_HASHES, true);
    }

    protected function defaultCompanyLogo(): ?string
    {
        $logo = trim((string) theme_option(
            'seo_og_image',
            'chatgpt-image-may-14-2026-03-00-04-pm.png'
        ));

        return $logo !== '' ? $logo : null;
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

    protected function goZambiaCountryId(JobCrawler $crawler): int
    {
        $countryId = (int) data_get($crawler->field_mappings, 'country_id');

        return $countryId > 0 ? $countryId : 7;
    }

    protected function goZambiaCountryName(JobCrawler $crawler): string
    {
        $countryName = data_get($crawler->field_mappings, 'country_name');

        if ($countryName) {
            return (string) $countryName;
        }

        return match ($this->goZambiaCountryId($crawler)) {
            11 => 'Botswana',
            38 => 'Malawi',
            44 => 'Namibia',
            53 => 'South Africa',
            60 => 'Zimbabwe',
            default => 'Zambia',
        };
    }

    protected function absoluteGoZambiaUrl(JobCrawler $crawler, string $path): ?string
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

        $parts = parse_url($crawler->source_url);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? 'gozambiajobs.com';

        return sprintf('%s://%s/%s', $scheme, $host, ltrim($path, '/'));
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
        $mappings    = $crawler->field_mappings ?? [];
        $searchPath  = (string) ($mappings['search_path'] ?? 'recherche-jobs-maroc');
        $detailPrefix = (string) ($mappings['detail_path_prefix'] ?? 'offre-emploi-[a-z-]+');
        $baseUrl     = rtrim((string) $crawler->source_url, '/');

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

            // Job URLs: /offre-emploi-maroc/slug-ID, /offre-emploi-cameroun/slug-ID,
            // /offre-emploi-burkina-faso/slug-ID, or (English-network sites)
            // /job-vacancies-liberia/slug-ID — prefix is configurable via field_mappings.detail_path_prefix
            preg_match_all('|href="(/' . $detailPrefix . '/([^"]+))"|', $html, $m);

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

        // Discard garbage locations like "/ Travail En Remote" so the caller's default_location is used
        if ($location !== '' && ! preg_match('/^[\p{L}\s\'-]+$/u', $location)) {
            $location = '';
        }

        // Description from field--name-body div (older AfricaWork theme: emploi.ma, emploi.cm)
        if (preg_match('/class="[^"]*field--name-body[^"]*"[^>]*>(.*?)<\/div>/s', $html, $m)) {
            $description = trim($m[1]);
        } elseif (preg_match('/class="job-description"[^>]*>(.*?)<\/div>/s', $html, $m)) {
            // Newer AfricaWork theme: emploiburkina.com, emploiguinee.com
            $description = trim($m[1]);

            if (preg_match('/<h3 class="job-title">([^<]+)<\/h3>\s*<div class="job-qualifications">(.*?)<\/div>/s', $html, $qm)) {
                $description .= '<h3>' . trim($qm[1]) . '</h3>' . trim($qm[2]);
            }
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

    // -------------------------------------------------------------------------
    // JobSearchZM — WordPress-based job board at jobsearchzm.com
    // -------------------------------------------------------------------------

    protected function fetchJobSearchZmJobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $sitemapUrl = (string) $crawler->source_url;
        if (! str_ends_with(parse_url($sitemapUrl, PHP_URL_PATH) ?: '', '.xml')) {
            $sitemapUrl = 'https://jobsearchzm.com/job_listing-sitemap.xml';
        }
        $headers = $this->jobSearchZmHeaders('application/xml,text/xml;q=0.9,*/*;q=0.8');

        $response = Http::withHeaders($headers)->timeout(30)->get($sitemapUrl);
        $response->throw();

        $sitemapItems = $this->parseJobSearchZmSitemap($response->body());
        $total = count($sitemapItems);
        $jobs = [];
        $maxNewDetails = $this->runMode === 'full' ? 200 : 20;
        $newDetails = 0;

        foreach ($sitemapItems as $index => $item) {
            $sourceId = (string) $item['id'];
            $this->saveProgress($index + 1, count($jobs));

            if (array_key_exists($sourceId, $existingIds)) {
                continue;
            }

            $detail = $this->fetchJobSearchZmJobDetail($item['url']);

            if ($detail) {
                $jobs[] = array_merge($item, $detail);
                $newDetails++;
            }

            if ($newDetails >= $maxNewDetails) {
                break;
            }

            $this->saveMeta([
                'stage' => 'scanning',
                'current_page' => $index + 1,
                'total_pages' => $total,
                'jobs_found_so_far' => count($jobs),
            ]);
        }

        DB::reconnect();

        return $jobs;
    }

    protected function jobSearchZmHeaders(string $accept = 'text/html,application/xhtml+xml'): array
    {
        return [
            'User-Agent' => 'WakandaJobsCrawler/1.0 (+https://www.wakandajobs.com)',
            'Accept' => $accept,
            'Accept-Language' => 'en-US,en;q=0.9',
        ];
    }

    protected function parseJobSearchZmSitemap(string $xml): array
    {
        $document = @simplexml_load_string($xml);

        if (! $document) {
            return [];
        }

        $items = [];

        foreach ($document->url as $urlNode) {
            $url = trim((string) $urlNode->loc);

            if ($url === '' || ! str_contains($url, '/job/')) {
                continue;
            }

            $items[] = [
                'id' => $url,
                'title' => $this->titleFromJobSearchZmUrl($url),
                'url' => $url,
                'company' => 'JobSearchZM',
                'location' => 'Zambia',
                'apply_url' => $url,
                'content' => '',
                'date' => trim((string) $urlNode->lastmod) ?: null,
                'deadline' => null,
            ];
        }

        return array_reverse($items);
    }

    protected function titleFromJobSearchZmUrl(string $url): string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $slug = basename($path);
        $slug = preg_replace('/-\d+$/', '', $slug) ?: $slug;

        return Str::headline(str_replace('-', ' ', $slug));
    }

    protected function fetchJobSearchZmJobDetail(string $url): ?array
    {
        $response = Http::withHeaders($this->jobSearchZmHeaders())->timeout(30)->get($url);

        if (! $response->successful()) {
            return null;
        }

        return $this->parseJobSearchZmJobPage($response->body(), $url);
    }

    protected function parseJobSearchZmJobPage(string $html, string $url): array
    {
        $data = $this->extractJobSearchZmStructuredData($html);

        $title = trim(html_entity_decode((string) data_get($data, 'title'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $company = trim(html_entity_decode((string) data_get($data, 'hiringOrganization.name'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $logo = (string) data_get($data, 'hiringOrganization.logo', '');
        $location = trim(html_entity_decode((string) data_get($data, 'jobLocation.address', 'Zambia'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $description = (string) data_get($data, 'description', '');
        $applyUrl = $this->extractJobSearchZmApplyUrl($html) ?: $url;

        return [
            'title' => $title ?: $this->titleFromJobSearchZmUrl($url),
            'company' => $company ?: 'JobSearchZM',
            'logo' => $logo,
            'location' => $location ?: 'Zambia',
            'apply_url' => $applyUrl,
            'content' => $description ?: $this->extractJobSearchZmArticleHtml($html),
            'date' => data_get($data, 'datePosted') ?: null,
            'deadline' => data_get($data, 'validThrough') ?: null,
        ];
    }

    protected function extractJobSearchZmStructuredData(string $html): array
    {
        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(html_entity_decode(trim($json), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);

            foreach ($this->flattenJobSearchZmJsonLd($decoded) as $entry) {
                if (($entry['@type'] ?? null) === 'JobPosting') {
                    return $entry;
                }
            }
        }

        return [];
    }

    protected function flattenJobSearchZmJsonLd(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = Arr::isAssoc($value) ? [$value] : $value;
        $flat = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $flat[] = $item;

            if (isset($item['@graph'])) {
                $flat = array_merge($flat, $this->flattenJobSearchZmJsonLd($item['@graph']));
            }
        }

        return $flat;
    }

    protected function extractJobSearchZmApplyUrl(string $html): ?string
    {
        if (preg_match('/<section[^>]+class=["\'][^"\']*rw-how-to-apply[^"\']*["\'][\s\S]*?<a[^>]+href=["\']([^"\']+)["\']/i', $html, $match)) {
            return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (preg_match('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>\s*Apply/i', $html, $match)) {
            return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    protected function extractJobSearchZmArticleHtml(string $html): string
    {
        if (preg_match('/<div[^>]+class=["\'][^"\']*job_description[^"\']*["\'][^>]*>(.*?)<\/div>/is', $html, $match)) {
            return trim($match[1]);
        }

        return '';
    }

    protected function importJobSearchZmJobs(JobCrawler $crawler, array $items): array
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

        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $sourceId = (string) ($item['id'] ?? '');
            $title    = trim((string) ($item['title'] ?? ''));

            if ($sourceId === '' || $title === '') {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            // Detect "Multiple Positions" posts and expand them into individual jobs.
            $splitItems = $this->splitMultiPositionJob($item);

            if ($splitItems !== null) {
                foreach ($splitItems as $splitItem) {
                    $splitCompany = $this->firstOrCreateJobSearchZmCompany($splitItem);
                    if (! $splitCompany) {
                        $stats['jobs_skipped']++;
                        continue;
                    }
                    $newJob = new Job();
                    $newJob->forceFill($this->buildJobSearchZmAttributes($crawler, $splitItem, $splitCompany));
                    $this->resolveApplyContact($newJob);
                    $this->persistNewJob($newJob, $crawler, $stats, function (Job $j): void {
                        $j->jobTypes()->syncWithoutDetaching([3]);
                        $this->dispatchNewJobEvents($j);
                    });
                }
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->firstOrCreateJobSearchZmCompany($item);
            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $existing = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $sourceId)
                ->first();

            $attributes = $this->buildJobSearchZmAttributes($crawler, $item, $company);

            if ($existing) {
                $existing->forceFill($attributes)->save();
                $stats['jobs_updated']++;
            } else {
                $newJob = new Job();
                $newJob->forceFill($attributes);
                $this->resolveApplyContact($newJob);
                $this->persistNewJob($newJob, $crawler, $stats, function (Job $j): void {
                    $j->jobTypes()->syncWithoutDetaching([3]);
                    $this->dispatchNewJobEvents($j);
                });
            }

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        $this->saveExistingUpdateProgress(0, $existingTotal, $stats);

        foreach ($existingItems as $index => $item) {
            $sourceId = (string) ($item['id'] ?? '');
            if ($sourceId !== '') {
                $job = Job::query()
                    ->where('crawler_id', $crawler->getKey())
                    ->where('external_source_id', $sourceId)
                    ->first();

                if ($job) {
                    $deadline = $item['deadline'] ?? null;
                    $job->forceFill([
                        'name'                     => $this->limitGoZambiaField($item['title'] ?? $job->name, 110),
                        'expire_date'              => $deadline ? Carbon::parse($deadline) : Carbon::now()->addDays(30),
                        'application_closing_date' => $deadline ? Carbon::parse($deadline) : null,
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

    protected function firstOrCreateJobSearchZmCompany(array $item): ?Company
    {
        $name = trim((string) ($item['company'] ?? ''));
        if ($name === '') {
            return null;
        }

        $company = $this->findGoZambiaCompany($name, null);

        if (! $company) {
            $company = $this->firstOrCreateCompany([
                'name'       => $this->limitGoZambiaField($name, 110),
                'country_id' => 7, // Zambia
                'status'     => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED,
                'is_verified'=> false,
            ], $name);
            SlugHelper::createSlug($company);
        }

        $logoUrl = (string) ($item['logo'] ?? '');
        if (! $company->logo && $logoUrl !== '') {
            $logoPath = str_contains($logoUrl, 'cropped-Job-Search-Zambia')
                ? $this->defaultCompanyLogo()
                : $this->uploadCompanyLogo($logoUrl);

            if ($logoPath) {
                $company->logo = $logoPath;
                $company->save();
            }
        }

        return $company;
    }

    protected function buildJobSearchZmAttributes(JobCrawler $crawler, array $item, Company $company): array
    {
        $date     = $item['date'] ?? null;
        $deadline = $item['deadline'] ?? null;

        $postedDate = $date ? Carbon::parse($date) : Carbon::now();
        $expireDate = $deadline
            ? Carbon::parse($deadline)
            : $postedDate->copy()->addDays(45);

        $rawContent  = (string) ($item['content'] ?? '');
        // When the item was produced by splitMultiPositionJob, 'excerpt' holds only the
        // position-specific HTML so the 400-char description is focused on that role.
        $excerptHtml = (string) ($item['excerpt'] ?? $rawContent);
        $description = Str::limit(trim(strip_tags($excerptHtml)), 400, '');

        return [
            'crawler_id'               => $crawler->getKey(),
            'external_source_id'       => (string) ($item['id'] ?? ''),
            'external_source_url'      => (string) ($item['url'] ?? ''),
            'name'                     => $this->limitGoZambiaField(trim((string) ($item['title'] ?? '')), 110),
            'description'              => $description,
            'content'                  => $rawContent ?: $description,
            'company_id'               => $company->getKey(),
            'address'                  => (string) ($item['location'] ?? 'Zambia'),
            'country_id'               => 7, // Zambia
            'apply_url'                => (string) ($item['apply_url'] ?? $item['url'] ?? ''),
            'status'                   => JobStatusEnum::PUBLISHED,
            'moderation_status'        => ModerationStatusEnum::APPROVED,
            'salary_type'              => \Botble\JobBoard\Enums\SalaryTypeEnum::HIDDEN,
            'career_level_id'          => 3,
            'is_featured'              => false,
            'expire_date'              => $expireDate,
            'application_closing_date' => $expireDate,
            'never_expired'            => false,
            'created_at'               => $postedDate,
            'updated_at'               => $postedDate,
        ];
    }

    /**
     * Detect and split a "Multiple Positions" job item into individual position items.
     *
     * Looks for a "POSITION-SPECIFIC REQUIREMENTS" section in the HTML content.
     * Each <p> containing <u><b>HEADING</b></u> after that marker is treated as one
     * position. Returns null when the content doesn't match the pattern or has < 2 positions.
     *
     * Each returned item inherits all parent fields but with:
     *   - title        = normalised position name
     *   - content      = preamble + this position's block + application procedure
     *   - excerpt      = position-specific text only (used for the 400-char description)
     *   - id           = "{original_id}|{position-slug}"
     *
     * @param  array $item  Raw item as returned by the fetch/parse methods.
     * @return array<int,array>|null
     */
    public function splitMultiPositionJob(array $item): ?array
    {
        $html = (string) ($item['content'] ?? '');

        if (stripos($html, 'POSITION-SPECIFIC REQUIREMENTS') === false) {
            return null;
        }

        // Parse the HTML with DOMDocument.
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<meta charset="utf-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Find the <body> element (loadHTML always wraps content in one).
        $bodyList = $dom->getElementsByTagName('body');
        $body     = $bodyList->length ? $bodyList->item(0) : $dom->documentElement;

        if (! $body) {
            return null;
        }

        // Collect top-level child nodes into an array for indexed access.
        $children = [];
        foreach ($body->childNodes as $node) {
            $children[] = $node;
        }

        $psrIndex     = -1; // "POSITION-SPECIFIC REQUIREMENTS"
        $appProcIndex = -1; // "APPLICATION PROCEDURE"

        foreach ($children as $i => $node) {
            $text = strtoupper(trim((string) ($node->textContent ?? '')));

            if ($psrIndex === -1 && str_contains($text, 'POSITION-SPECIFIC REQUIREMENTS')) {
                $psrIndex = $i;
                continue;
            }

            if ($psrIndex !== -1 && $appProcIndex === -1 && str_contains($text, 'APPLICATION PROCEDURE')) {
                // Back-track to include the opening <p> tag of the APPLICATION PROCEDURE section.
                $appProcIndex = $i;
            }
        }

        if ($psrIndex === -1) {
            return null;
        }

        // Build preamble HTML (everything up to and including the PSR heading paragraph).
        $preambleHtml = '';
        for ($i = 0; $i <= $psrIndex; $i++) {
            $preambleHtml .= $dom->saveHTML($children[$i]);
        }

        // Build application procedure HTML (from APP PROC onwards).
        $appProcHtml = '';
        if ($appProcIndex !== -1) {
            for ($i = $appProcIndex; $i < count($children); $i++) {
                $appProcHtml .= $dom->saveHTML($children[$i]);
            }
        }

        // Walk nodes between PSR and APP PROC to extract individual positions.
        $end          = $appProcIndex !== -1 ? $appProcIndex : count($children);
        $positionRange = array_slice($children, $psrIndex + 1, $end - $psrIndex - 1);

        $positions = []; // ['name' => str, 'html' => str (heading + requirements)]
        $current   = null;

        foreach ($positionRange as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                if ($current !== null) {
                    $current['html'] .= $dom->saveHTML($node);
                }
                continue;
            }

            // Position heading: a <p> whose inner content is wrapped in <u><b> (or <b><u>).
            if ($node->nodeName === 'p') {
                $hasUB = $xpath->query('.//u//b | .//b//u', $node)->length > 0;

                if ($hasUB) {
                    if ($current) {
                        $positions[] = $current;
                    }

                    // Normalise "ADMIN/SITE CLERKS" → "Admin/Site Clerks" (capitalise after / - ( ) too).
                    $rawName = preg_replace('/\s+/', ' ', trim((string) ($node->textContent ?? '')));
                    $posName = ucwords(strtolower($rawName), " \t\r\n\f\v/-()'\"");

                    $current = [
                        'name' => $posName,
                        'html' => $dom->saveHTML($node),
                    ];
                    continue;
                }
            }

            if ($current !== null) {
                $current['html'] .= $dom->saveHTML($node);
            }
        }

        if ($current) {
            $positions[] = $current;
        }

        if (count($positions) < 2) {
            return null;
        }

        $sourceId = (string) ($item['id'] ?? '');

        $results = [];
        foreach ($positions as $pos) {
            $posSlug    = Str::slug($pos['name']);
            $posContent = $preambleHtml . $pos['html'] . $appProcHtml;

            $results[] = array_merge($item, [
                'title'   => $pos['name'],
                'content' => $posContent,
                'excerpt' => $pos['html'], // position-specific section only → drives 400-char description
                'id'      => $sourceId !== '' ? $sourceId . '|' . $posSlug : '',
            ]);
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // VacancyBox (vacancybox.co.zw) — WordPress WP Job Manager, XML sitemaps
    // -------------------------------------------------------------------------

    protected function fetchVacancyBoxJobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $base = rtrim((string) $crawler->source_url, '/');
        if (! $base) {
            $base = 'https://vacancybox.co.zw';
        }

        // Discover all job_listing sitemap pages via the sitemap index.
        $sitemapUrls = $this->discoverVacancyBoxSitemaps($base);

        // Process sitemaps newest-first for incremental runs.
        $sitemapUrls = array_reverse($sitemapUrls);

        $jobs = [];
        $maxNewDetails = $this->runMode === 'full' ? 300 : 30;
        $newDetails = 0;
        $totalSitemaps = count($sitemapUrls);

        foreach ($sitemapUrls as $sitemapIndex => $sitemapUrl) {
            $sitemapItems = $this->fetchVacancyBoxSitemap($sitemapUrl);

            // Sitemaps list oldest-first; reverse so we process newest jobs first.
            $sitemapItems = array_reverse($sitemapItems);

            foreach ($sitemapItems as $item) {
                $sourceId = $item['id'];

                $this->saveMeta([
                    'stage'             => 'scanning',
                    'current_page'      => $sitemapIndex + 1,
                    'total_pages'       => $totalSitemaps,
                    'jobs_found_so_far' => count($jobs),
                ]);

                if (array_key_exists($sourceId, $existingIds)) {
                    if (! $this->disableEarlyStop && $this->runMode !== 'full' && count($jobs) > 0) {
                        return $jobs;
                    }
                    continue;
                }

                $detail = $this->fetchVacancyBoxJobDetail($item['url']);

                if ($detail) {
                    $jobs[] = array_merge($item, $detail);
                    $newDetails++;
                }

                if ($newDetails >= $maxNewDetails) {
                    return $jobs;
                }
            }

            DB::reconnect();
        }

        return $jobs;
    }

    protected function discoverVacancyBoxSitemaps(string $base): array
    {
        $indexUrl = $base . '/sitemap_index.xml';
        $headers  = $this->vacancyBoxHeaders('application/xml,text/xml;q=0.9,*/*;q=0.8');

        try {
            $resp = Http::withHeaders($headers)->timeout(20)->get($indexUrl);
        } catch (Throwable) {
            return [$base . '/job_listing-sitemap.xml'];
        }

        if (! $resp->successful()) {
            return [$base . '/job_listing-sitemap.xml'];
        }

        $xml = @simplexml_load_string($resp->body());
        if (! $xml) {
            return [$base . '/job_listing-sitemap.xml'];
        }

        $urls = [];
        foreach ($xml->sitemap as $node) {
            $loc = trim((string) $node->loc);
            if (str_contains($loc, 'job_listing-sitemap')) {
                $urls[] = $loc;
            }
        }

        return $urls ?: [$base . '/job_listing-sitemap.xml'];
    }

    protected function fetchVacancyBoxSitemap(string $url): array
    {
        $headers = $this->vacancyBoxHeaders('application/xml,text/xml;q=0.9,*/*;q=0.8');

        try {
            $resp = Http::withHeaders($headers)->timeout(20)->get($url);
        } catch (Throwable) {
            return [];
        }

        if (! $resp->successful()) {
            return [];
        }

        $xml = @simplexml_load_string($resp->body());
        if (! $xml) {
            return [];
        }

        $items = [];
        foreach ($xml->url as $node) {
            $loc = trim((string) $node->loc);
            if (! str_contains($loc, '/job/')) {
                continue;
            }

            $lastmod = trim((string) $node->lastmod) ?: null;

            $items[] = [
                'id'      => $loc,
                'url'     => $loc,
                'lastmod' => $lastmod,
            ];
        }

        return $items;
    }

    protected function fetchVacancyBoxJobDetail(string $url): ?array
    {
        $headers = $this->vacancyBoxHeaders();

        try {
            $resp = Http::withHeaders($headers)->timeout(20)->get($url);
        } catch (Throwable) {
            return null;
        }

        if (! $resp->successful()) {
            return null;
        }

        return $this->parseVacancyBoxJobPage($resp->body(), $url);
    }

    protected function parseVacancyBoxJobPage(string $html, string $url): ?array
    {
        $data = $this->extractVacancyBoxJsonLd($html);

        if (empty($data)) {
            return null;
        }

        $title    = trim(html_entity_decode((string) ($data['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $company  = trim(html_entity_decode((string) data_get($data, 'hiringOrganization.name', ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $logo     = (string) data_get($data, 'hiringOrganization.logo', '');

        $rawAddress = data_get($data, 'jobLocation.address', 'Zimbabwe');
        if (is_array($rawAddress)) {
            $rawAddress = $rawAddress['addressLocality'] ?? $rawAddress['addressRegion'] ?? $rawAddress['addressCountry'] ?? 'Zimbabwe';
        }
        $location = trim(html_entity_decode((string) $rawAddress, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $content  = html_entity_decode((string) ($data['description'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $datePosted = (string) ($data['datePosted'] ?? '');

        // Try to extract deadline from description text (e.g. "DUE: 26 MAY 2026")
        $deadline = null;
        if (preg_match('/DUE[:\s]+(\d{1,2}\s+\w+\s+\d{4}|\w+\s+\d{1,2},?\s+\d{4})/i', strip_tags($content), $m)) {
            try {
                $deadline = Carbon::parse($m[1])->toDateString();
            } catch (Throwable) {
                $deadline = null;
            }
        }

        if ($title === '') {
            return null;
        }

        return [
            'title'     => $title,
            'company'   => $company ?: 'VacancyBox',
            'logo'      => $logo,
            'location'  => $location ?: 'Zimbabwe',
            'content'   => $content,
            'apply_url' => $url,
            'date'      => $datePosted ?: null,
            'deadline'  => $deadline,
        ];
    }

    protected function extractVacancyBoxJsonLd(string $html): array
    {
        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return [];
        }

        $candidates = [];

        foreach ($matches[1] as $json) {
            $decoded = json_decode(trim($json), true);
            if (! is_array($decoded)) {
                continue;
            }

            foreach ($this->flattenJobSearchZmJsonLd($decoded) as $entry) {
                if (($entry['@type'] ?? null) === 'JobPosting') {
                    $candidates[] = $entry;
                }
            }
        }

        if (empty($candidates)) {
            return [];
        }

        // Prefer the entry with the most complete data (description + real company name).
        // WP Job Manager's script always has a description; the Yoast stub does not.
        usort($candidates, function (array $a, array $b): int {
            $scoreA = (isset($a['description']) ? 2 : 0) + (isset($a['hiringOrganization']['logo']) ? 1 : 0);
            $scoreB = (isset($b['description']) ? 2 : 0) + (isset($b['hiringOrganization']['logo']) ? 1 : 0);
            return $scoreB <=> $scoreA;
        });

        return $candidates[0];
    }

    protected function vacancyBoxHeaders(string $accept = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'): array
    {
        return [
            'User-Agent'      => 'WakandaJobsCrawler/1.0 (+https://www.wakandajobs.com)',
            'Accept'          => $accept,
            'Accept-Language' => 'en-US,en;q=0.9',
        ];
    }

    protected function importVacancyBoxJobs(JobCrawler $crawler, array $items): array
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
            $sourceId = (string) ($item['id'] ?? '');
            $title    = trim((string) ($item['title'] ?? ''));

            if ($sourceId === '' || $title === '') {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->firstOrCreateVacancyBoxCompany($item);
            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $existing = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $sourceId)
                ->first();

            $attributes = $this->buildVacancyBoxAttributes($crawler, $item, $company);

            if ($existing) {
                $existing->forceFill($attributes)->save();
                $stats['jobs_updated']++;
            } else {
                $newJob = new Job();
                $newJob->forceFill($attributes);
                $this->persistNewJob($newJob, $crawler, $stats, function (Job $j): void {
                    $j->jobTypes()->syncWithoutDetaching([3]);
                    $this->dispatchNewJobEvents($j);
                });
            }

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        return $stats;
    }

    protected function firstOrCreateVacancyBoxCompany(array $item): ?Company
    {
        $name = trim((string) ($item['company'] ?? ''));
        if ($name === '') {
            return null;
        }

        $company = $this->findGoZambiaCompany($name, null);

        if (! $company) {
            $company = $this->firstOrCreateCompany([
                'name'        => $this->limitGoZambiaField($name, 110),
                'country_id'  => 60, // Zimbabwe
                'status'      => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED,
                'is_verified' => false,
            ], $name);
            SlugHelper::createSlug($company);

            if (! empty($item['logo'])) {
                $logoPath = $this->uploadCompanyLogo($item['logo']);
                if ($logoPath) {
                    $company->logo = $logoPath;
                    $company->saveQuietly();
                }
            }
        }

        return $company;
    }

    protected function buildVacancyBoxAttributes(JobCrawler $crawler, array $item, Company $company): array
    {
        $date     = $item['date'] ?? null;
        $deadline = $item['deadline'] ?? null;

        $postedDate = $date ? Carbon::parse($date) : Carbon::now();
        $expireDate = $deadline
            ? Carbon::parse($deadline)
            : $postedDate->copy()->addDays(45);

        $rawContent  = (string) ($item['content'] ?? '');
        $description = Str::limit(trim(strip_tags($rawContent)), 400, '');

        return [
            'crawler_id'               => $crawler->getKey(),
            'external_source_id'       => (string) ($item['id'] ?? ''),
            'external_source_url'      => (string) ($item['url'] ?? $item['id'] ?? ''),
            'name'                     => $this->limitGoZambiaField($item['title'] ?? '', 110),
            'description'              => $description,
            'content'                  => $rawContent ?: $description,
            'company_id'               => $company->getKey(),
            'address'                  => (string) ($item['location'] ?? 'Zimbabwe'),
            'country_id'               => 60, // Zimbabwe
            'apply_url'                => (string) ($item['apply_url'] ?? $item['url'] ?? ''),
            'status'                   => JobStatusEnum::PUBLISHED,
            'moderation_status'        => ModerationStatusEnum::APPROVED,
            'salary_type'              => SalaryTypeEnum::HIDDEN,
            'career_level_id'          => 3,
            'is_featured'              => false,
            'expire_date'              => $expireDate,
            'application_closing_date' => $expireDate,
            'never_expired'            => false,
            'created_at'               => $postedDate,
            'updated_at'               => $postedDate,
        ];
    }

    // -------------------------------------------------------------------------
    // JobZambia (jobzambia.com) — WP Job Manager AJAX endpoint + detail pages
    // -------------------------------------------------------------------------

    protected function fetchJobZambiaJobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $isFirstRun = empty($existingIds);

        $parsed  = parse_url((string) $crawler->source_url);
        $base    = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'jobzambia.com');
        $ajaxUrl = $base . '/jm-ajax/get_listings/';

        $jobs    = [];
        $seenIds = [];
        $maxNew  = $this->runMode === 'full' ? 300 : 40;
        $newFetched = 0;

        for ($page = 1; $page <= 20; $page++) {
            $this->saveMeta([
                'stage'             => 'scanning',
                'current_page'      => $page,
                'jobs_found_so_far' => count($jobs),
            ]);

            if ($page > 1) {
                usleep(500_000);
            }

            try {
                $resp = Http::withHeaders($this->jobZambiaHeaders('application/json,*/*;q=0.8'))
                    ->asForm()
                    ->timeout(20)
                    ->post($ajaxUrl, [
                        'search_keywords'  => '',
                        'search_location'  => '',
                        'per_page'         => 16,
                        'orderby'          => 'featured',
                        'order'            => 'DESC',
                        'page'             => $page,
                        'show_pagination'  => 'false',
                        'post_id'          => 13,
                    ]);
            } catch (Throwable) {
                break;
            }

            if (! $resp->successful()) {
                break;
            }

            $json = $resp->json();

            if (empty($json['found_jobs']) || empty($json['html'])) {
                break;
            }

            $maxPages = (int) ($json['max_num_pages'] ?? 1);
            $cards    = $this->parseJobZambiaListingCards($json['html'], $base);

            if (empty($cards)) {
                break;
            }

            $newOnPage = 0;

            foreach ($cards as $card) {
                $sourceId = $card['id'];

                if (isset($seenIds[$sourceId])) {
                    continue;
                }
                $seenIds[$sourceId] = true;

                if (array_key_exists($sourceId, $existingIds)) {
                    continue;
                }

                $detail = $this->fetchJobZambiaDetail($card['url']);
                if (! $detail) {
                    continue;
                }

                $jobs[] = array_merge($card, $detail);
                $newOnPage++;
                $newFetched++;

                if ($newFetched >= $maxNew) {
                    return $jobs;
                }
            }

            // Caught up — entire page was already known.
            if (! $this->disableEarlyStop && ! $isFirstRun && $page > 1 && $newOnPage === 0) {
                break;
            }

            if ($page >= $maxPages) {
                break;
            }

            DB::reconnect();
        }

        return $jobs;
    }

    protected function parseJobZambiaListingCards(string $html, string $base): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?><ul>' . $html . '</ul>');
        $xpath = new \DOMXPath($doc);

        $cards = [];

        foreach ($xpath->query('//li[contains(@class,"job_listing")]') as $li) {
            // Post ID from class="post-{id} job_listing ..."
            preg_match('/\bpost-(\d+)\b/', $li->getAttribute('class'), $idMatch);
            $postId = $idMatch[1] ?? null;
            if (! $postId) {
                continue;
            }

            $link    = $xpath->query('.//a', $li)->item(0);
            $url     = $link ? trim($link->getAttribute('href')) : '';
            if (! $url) {
                continue;
            }

            $logo    = '';
            $logoImg = $xpath->query('.//img[contains(@class,"company_logo")]', $li)->item(0);
            if ($logoImg) {
                // Use alt for company name fallback; src for the logo URL.
                $logo = trim($logoImg->getAttribute('src'));
                // Strip -150x150 thumbnail suffix to get full-size image.
                $logo = preg_replace('/-\d+x\d+(\.\w+)$/', '$1', $logo);
            }

            $title   = $this->xpathText($xpath, './/div[contains(@class,"position")]//h3', $li);
            $company = $this->xpathText($xpath, './/div[contains(@class,"company")]/strong', $li);
            $location = $this->xpathText($xpath, './/div[contains(@class,"location")]', $li);

            // ISO date from <time datetime="YYYY-MM-DD">
            $timeNode = $xpath->query('.//time', $li)->item(0);
            $dateStr  = $timeNode ? $timeNode->getAttribute('datetime') : null;

            $cards[] = [
                'id'       => $postId,
                'url'      => $url,
                'logo'     => $logo,
                'title'    => html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'company'  => html_entity_decode($company, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'location' => html_entity_decode($location, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'date'     => $dateStr,
            ];
        }

        return $cards;
    }

    protected function fetchJobZambiaDetail(string $url): ?array
    {
        try {
            $resp = Http::withHeaders($this->jobZambiaHeaders())->timeout(20)->get($url);
        } catch (Throwable) {
            return null;
        }

        if (! $resp->successful()) {
            return null;
        }

        $html  = $resp->body();
        $doc   = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($doc);

        // Description: prefer .job_description, fall back to .entry-content
        $descNode = $xpath->query('//*[contains(@class,"job_description")]')->item(0)
            ?? $xpath->query('//*[contains(@class,"entry-content")]')->item(0);
        $content  = $descNode ? $doc->saveHTML($descNode) : '';

        // Application deadline from body text.
        $deadline = null;
        $plain    = strip_tags($content);
        if (preg_match('/(?:deadline|closing date|apply by|close(?:s|d)?)\s*[:\-]?\s*(\d{1,2}(?:st|nd|rd|th)?\s+\w+,?\s+\d{4}|\w+\s+\d{1,2},?\s+\d{4})/i', $plain, $m)) {
            try {
                $deadline = Carbon::parse($m[1])->toDateString();
            } catch (Throwable) {
                $deadline = null;
            }
        }

        return [
            'content'   => $content,
            'deadline'  => $deadline,
            'apply_url' => $url,
        ];
    }

    protected function xpathText(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): string
    {
        $node = $context
            ? $xpath->query($query, $context)->item(0)
            : $xpath->query($query)->item(0);
        return $node ? trim($node->textContent) : '';
    }

    protected function jobZambiaHeaders(string $accept = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'): array
    {
        return [
            'User-Agent'      => 'WakandaJobsCrawler/1.0 (+https://www.wakandajobs.com)',
            'Accept'          => $accept,
            'Accept-Language' => 'en-US,en;q=0.9',
        ];
    }

    protected function importJobZambiaJobs(JobCrawler $crawler, array $items): array
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
            $sourceId = (string) ($item['id'] ?? '');
            $title    = trim((string) ($item['title'] ?? ''));

            if ($sourceId === '' || $title === '') {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->firstOrCreateJobZambiaCompany($item);
            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $attributes = $this->buildJobZambiaAttributes($crawler, $item, $company);

            $newJob = new Job();
            $newJob->forceFill($attributes);
            $this->persistNewJob($newJob, $crawler, $stats, function (Job $j): void {
                $j->jobTypes()->syncWithoutDetaching([3]);
                $this->dispatchNewJobEvents($j);
            });

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        return $stats;
    }

    protected function firstOrCreateJobZambiaCompany(array $item): ?Company
    {
        $name = trim((string) ($item['company'] ?? ''));
        if ($name === '') {
            return null;
        }

        $company = $this->findGoZambiaCompany($name, null);

        if (! $company) {
            $company = $this->firstOrCreateCompany([
                'name'        => $this->limitGoZambiaField($name, 110),
                'country_id'  => 7, // Zambia
                'status'      => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED,
                'is_verified' => false,
            ], $name);
            SlugHelper::createSlug($company);

            $logoUrl = $item['logo'] ?? null;
            if ($logoUrl) {
                $logoPath = $this->uploadCompanyLogo($logoUrl);
                if ($logoPath) {
                    $company->logo = $logoPath;
                    $company->saveQuietly();
                }
            }
        }

        return $company;
    }

    protected function buildJobZambiaAttributes(JobCrawler $crawler, array $item, Company $company): array
    {
        $date       = $item['date'] ?? null;
        $deadline   = $item['deadline'] ?? null;
        $postedDate = $date ? Carbon::parse($date) : Carbon::now();
        $expireDate = $deadline
            ? Carbon::parse($deadline)
            : $postedDate->copy()->addDays(45);

        $rawContent  = (string) ($item['content'] ?? '');
        $description = Str::limit(trim(strip_tags($rawContent)), 400, '');

        return [
            'crawler_id'               => $crawler->getKey(),
            'external_source_id'       => (string) ($item['id'] ?? ''),
            'external_source_url'      => (string) ($item['url'] ?? ''),
            'name'                     => $this->limitGoZambiaField($item['title'] ?? '', 110),
            'description'              => $description,
            'content'                  => $rawContent ?: $description,
            'company_id'               => $company->getKey(),
            'address'                  => (string) ($item['location'] ?? 'Zambia'),
            'country_id'               => 7, // Zambia
            'apply_url'                => (string) ($item['apply_url'] ?? $item['url'] ?? ''),
            'status'                   => JobStatusEnum::PUBLISHED,
            'moderation_status'        => ModerationStatusEnum::APPROVED,
            'salary_type'              => SalaryTypeEnum::HIDDEN,
            'career_level_id'          => 3,
            'is_featured'              => false,
            'expire_date'              => $expireDate,
            'application_closing_date' => $expireDate,
            'never_expired'            => false,
            'created_at'               => $postedDate,
            'updated_at'               => $postedDate,
        ];
    }

    // -------------------------------------------------------------------------
    // JobMail South Africa (jobmail.co.za)
    // -------------------------------------------------------------------------

    protected function fetchJobMailJobs(JobCrawler $crawler): array
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
        $seenIds    = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $this->saveProgress($page, count($jobs));

            if ($page > 1) {
                sleep($isFullPull ? rand(3, 7) : rand(2, 4));
            }

            $url = $page === 1
                ? 'https://www.jobmail.co.za/jobs'
                : "https://www.jobmail.co.za/jobs/page{$page}";

            $response = $this->jobMailRequest($url);

            if (! $response->successful()) {
                break;
            }

            $pageJobs = $this->extractJobMailList($response->body());

            if (empty($pageJobs)) {
                break;
            }

            $newOnPage = 0;

            foreach ($pageJobs as $job) {
                $id = (string) ($job['id'] ?? '');
                if ($id !== '' && isset($seenIds[$id])) {
                    continue;
                }
                if ($id !== '') {
                    $seenIds[$id] = true;
                }
                $jobs[] = $job;

                if ($id !== '' && ! array_key_exists($id, $existingIds)) {
                    $newOnPage++;
                }
            }

            if (! $isFullPull && ! $isFirstRun && $newOnPage === 0) {
                break;
            }
        }

        return $jobs;
    }

    protected function importJobMailJobs(JobCrawler $crawler, array $items): array
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
        $isFullPull    = $this->runMode === 'full';

        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $sourceId = (string) ($item['id'] ?? '');
            $title    = trim((string) ($item['title'] ?? ''));

            if ($sourceId === '' || $title === '') {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $detailUrl = $this->absoluteJobMailUrl((string) ($item['url'] ?? ''));
            $detail    = [];

            if (! $isFullPull && $detailUrl) {
                try {
                    sleep(rand(1, 3));
                    $detailResponse = $this->jobMailRequest($detailUrl);
                    if ($detailResponse->successful()) {
                        $detail = $this->extractJobMailDetail($detailResponse->body());
                    }
                } catch (Throwable) {
                    // keep list data
                }
            }

            $companyName = trim($detail['company_name'] ?? $item['company'] ?? '');
            if ($companyName === '') {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->findGoZambiaCompany($companyName, null);
            if (! $company) {
                $company = $this->firstOrCreateCompany([
                    'name'        => $this->limitGoZambiaField($companyName, 110),
                    'country_id'  => 53,
                    'status'      => BaseStatusEnum::PUBLISHED,
                    'is_verified' => false,
                ], $companyName);
                SlugHelper::createSlug($company);
            }

            $attrs = $this->buildJobMailAttributes($crawler, $item, $detail, $company, $detailUrl);

            $existing = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $sourceId)
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

        $this->saveExistingUpdateProgress(0, $existingTotal, $stats);

        foreach ($existingItems as $index => $item) {
            $sourceId = (string) ($item['id'] ?? '');
            if ($sourceId !== '') {
                $job = Job::query()
                    ->where('crawler_id', $crawler->getKey())
                    ->where('external_source_id', $sourceId)
                    ->first();

                if ($job) {
                    // Expiry was set on first import; don't override it on re-crawl.
                    $stats['jobs_skipped']++;
                } else {
                    $stats['jobs_skipped']++;
                }
            } else {
                $stats['jobs_skipped']++;
            }

            if ($index % 10 === 9 || $index === $existingTotal - 1) {
                $this->saveExistingUpdateProgress($index + 1, $existingTotal, $stats);
            }
        }

        return $stats;
    }

    protected function extractJobMailList(string $html): array
    {
        $jobs   = [];
        $needle = 'class="results-item';
        $offset = 0;

        while (($pos = strpos($html, $needle, $offset)) !== false) {
            $chunk = substr($html, $pos, 4000);

            if (! preg_match('/id="results-item-(\d+)"/', $chunk, $idM)) {
                $offset = $pos + strlen($needle);
                continue;
            }
            $jobId = $idM[1];

            $title = '';
            if (preg_match('/<h3>([^<]+)<\/h3>/', $chunk, $titleM)) {
                $title = html_entity_decode(trim($titleM[1]));
            }

            $url = '';
            if (preg_match('/id="jobDetailUrl-' . $jobId . '"[^>]+href="([^"]+)"/', $chunk, $urlM)) {
                $url = $urlM[1];
            } elseif (preg_match('/href="([^"]+)"[^>]+id="jobDetailUrl-' . $jobId . '"/', $chunk, $urlM)) {
                $url = $urlM[1];
            }

            $company = '';
            if (preg_match('/<span class="company">\s*([^<]+)\s*<\/span>/', $chunk, $compM)) {
                $company = html_entity_decode(trim($compM[1]));
            }

            $location = '';
            if (preg_match('/class="job-location">([^<]+)</', $chunk, $locM)) {
                $location = html_entity_decode(trim($locM[1]));
            }

            $jobs[] = [
                'id'       => $jobId,
                'title'    => $title,
                'url'      => $url,
                'company'  => $company,
                'location' => $location,
            ];

            $offset = $pos + strlen($needle);
        }

        return $jobs;
    }

    protected function extractJobMailDetail(string $html): array
    {
        preg_match_all('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $matches);
        foreach ($matches[1] as $jsonStr) {
            // JobMail embeds raw CR/LF in the description string — strip to make parseable.
            $clean = preg_replace('/[\x00-\x1F]/', ' ', trim($jsonStr));
            $data  = json_decode($clean, true);
            if (! is_array($data) || ($data['@type'] ?? '') !== 'JobPosting') {
                continue;
            }

            $org      = $data['hiringOrganization'] ?? [];
            $location = $data['jobLocation']['address'] ?? [];
            $parts    = array_filter([
                trim($location['addressLocality'] ?? ''),
                trim($location['addressRegion'] ?? ''),
            ]);

            return [
                'title'        => (string) ($data['title'] ?? ''),
                'company_name' => trim((string) ($org['name'] ?? '')),
                'location'     => implode(', ', $parts),
                'industry'     => (string) ($data['industry'] ?? ''),
                'datePosted'   => $data['datePosted'] ?? null,
                'validThrough' => $data['validThrough'] ?? null,
                'description'  => html_entity_decode((string) ($data['description'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ];
        }

        return [];
    }

    protected function buildJobMailAttributes(JobCrawler $crawler, array $item, array $detail, Company $company, ?string $detailUrl): array
    {
        $postedAt  = $detail['datePosted'] ?? null;
        $expiresAt = $detail['validThrough'] ?? null;

        $postedDate = $postedAt ? Carbon::parse($postedAt) : Carbon::now();
        $expireDate = $expiresAt
            ? Carbon::parse($expiresAt)
            : $postedDate->copy()->addDays(30);

        $rawDesc  = (string) ($detail['description'] ?? '');
        $location = $detail['location'] ?? $item['location'] ?? 'South Africa';

        return [
            'crawler_id'               => $crawler->getKey(),
            'external_source_id'       => (string) ($item['id'] ?? ''),
            'external_source_url'      => $detailUrl ?? $this->absoluteJobMailUrl((string) ($item['url'] ?? '')),
            'name'                     => $this->limitGoZambiaField($detail['title'] ?: ($item['title'] ?? ''), 110),
            'description'              => Str::limit(trim(strip_tags($rawDesc)), 400, ''),
            'content'                  => $rawDesc ?: Str::limit(trim(strip_tags($rawDesc)), 400, ''),
            'company_id'               => $company->getKey(),
            'address'                  => (string) $location,
            'country_id'               => 53, // South Africa
            'apply_url'                => $detailUrl ?? '',
            'status'                   => JobStatusEnum::PUBLISHED,
            'moderation_status'        => ModerationStatusEnum::APPROVED,
            'salary_type'              => SalaryTypeEnum::HIDDEN,
            'currency_id'              => 46, // ZAR
            'career_level_id'          => 3,
            'is_featured'              => false,
            'expire_date'              => $expireDate,
            'application_closing_date' => $expireDate,
            'never_expired'            => false,
            'created_at'               => $postedDate,
            'updated_at'               => $postedDate,
        ];
    }

    protected function absoluteJobMailUrl(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return 'https://www.jobmail.co.za' . (Str::startsWith($path, '/') ? $path : '/' . $path);
    }

    protected function jobMailRequest(string $url): \Illuminate\Http\Client\Response
    {
        return Http::timeout(20)->withHeaders([
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-ZA,en-GB;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer'         => 'https://www.jobmail.co.za/',
        ])->get($url);
    }

    // -------------------------------------------------------------------------
    // MyJobMag (myjobmag.com / myjobmag.co.ke)
    // -------------------------------------------------------------------------

    protected function fetchMyJobMagJobs(JobCrawler $crawler): array
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
        $sourceUrl  = rtrim((string) $crawler->source_url, '/');
        $baseUrl    = $this->myJobMagBaseUrl($sourceUrl);
        $jobs       = [];
        $seenSlugs  = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $this->saveProgress($page, count($jobs));

            if ($page > 1) {
                sleep($isFullPull ? rand(3, 6) : rand(1, 3));
            }

            $url = $page === 1 ? $sourceUrl : $sourceUrl . '/page/' . $page;

            $response = $this->myJobMagRequest($url);

            if (! $response->successful()) {
                break;
            }

            $pageJobs = $this->extractMyJobMagList($response->body(), $baseUrl);

            if (empty($pageJobs)) {
                break;
            }

            $newOnPage = 0;

            foreach ($pageJobs as $job) {
                $slug = (string) ($job['slug'] ?? '');
                if ($slug !== '' && isset($seenSlugs[$slug])) {
                    continue;
                }
                if ($slug !== '') {
                    $seenSlugs[$slug] = true;
                }
                $jobs[] = $job;

                if ($slug !== '' && ! array_key_exists($slug, $existingIds)) {
                    $newOnPage++;
                }
            }

            if (! $isFullPull && ! $isFirstRun && $newOnPage === 0) {
                break;
            }
        }

        return $jobs;
    }

    protected function importMyJobMagJobs(JobCrawler $crawler, array $items): array
    {
        $mappings        = $crawler->field_mappings ?? [];
        $countryId       = (int) ($mappings['country_id'] ?? 46);
        $currencyId      = isset($mappings['currency_id']) ? (int) $mappings['currency_id'] : null;
        $defaultLocation = (string) ($mappings['default_location'] ?? 'Nigeria');

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
            $slug = (string) ($item['slug'] ?? '');
            if ($slug === '' || ! array_key_exists($slug, $existingIds)) {
                $newItems[] = $item;
            }
        }

        $newTotal   = count($newItems);
        $isFullPull = $this->runMode === 'full';

        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $slug = (string) ($item['slug'] ?? '');
            $url  = (string) ($item['url'] ?? '');

            if (! $slug) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $detail = [];

            if (! $isFullPull && $url) {
                try {
                    sleep(rand(1, 3));
                    $detailResponse = $this->myJobMagRequest($url);
                    if ($detailResponse->successful()) {
                        $detail = $this->extractMyJobMagDetail($detailResponse->body());
                    }
                } catch (Throwable) {
                    // use list data
                }
            }

            $title       = trim($detail['title'] ?? $item['title'] ?? '');
            $companyName = trim($detail['company_name'] ?? $item['company'] ?? '');

            if (! $title || ! $companyName) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->findGoZambiaCompany($companyName, null);
            if (! $company) {
                $company = $this->firstOrCreateCompany([
                    'name'        => $this->limitGoZambiaField($companyName, 110),
                    'country_id'  => $countryId,
                    'status'      => BaseStatusEnum::PUBLISHED,
                    'is_verified' => false,
                ], $companyName);
                SlugHelper::createSlug($company);
            }

            $postedAt   = $detail['datePosted'] ?? null;
            $expiresAt  = $detail['validThrough'] ?? null;
            $postedDate = $postedAt ? Carbon::parse($postedAt) : Carbon::now();
            $expireDate = $expiresAt ? Carbon::parse($expiresAt) : $postedDate->copy()->addDays(60);
            $rawDesc    = (string) ($detail['description'] ?? $item['short_desc'] ?? '');
            $location   = (string) ($detail['location'] ?? $defaultLocation);

            $attrs = [
                'crawler_id'               => $crawler->getKey(),
                'external_source_id'       => $slug,
                'external_source_url'      => $url,
                'name'                     => $this->limitGoZambiaField($title, 110),
                'description'              => Str::limit(trim(strip_tags($rawDesc)), 400, ''),
                'content'                  => $rawDesc ?: Str::limit(trim(strip_tags($rawDesc)), 400, ''),
                'company_id'               => $company->getKey(),
                'address'                  => $location,
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

    protected function extractMyJobMagList(string $html, string $baseUrl): array
    {
        $jobs   = [];
        $needle = 'class="job-list-li"';
        $offset = 0;

        while (($pos = strpos($html, $needle, $offset)) !== false) {
            $chunk = substr($html, $pos, 1500);

            if (! preg_match('/<h2[^>]*>\s*<a[^>]+href="([^"]+)"[^>]*>([^<]+)<\/a>/i', $chunk, $m)) {
                $offset = $pos + strlen($needle);
                continue;
            }

            $href  = $m[1];
            $label = html_entity_decode(trim($m[2]));

            $title   = $label;
            $company = '';
            if (preg_match('/^(.+?)\s+at\s+(.+)$/i', $label, $atM)) {
                $title   = trim($atM[1]);
                $company = trim($atM[2]);
            }

            $shortDesc = '';
            if (preg_match('/class="job-desc">([^<]+)</i', $chunk, $descM)) {
                $shortDesc = trim($descM[1]);
            }

            $url  = Str::startsWith($href, 'http') ? $href : rtrim($baseUrl, '/') . $href;
            $slug = basename(parse_url($url, PHP_URL_PATH));

            $jobs[] = [
                'slug'       => $slug,
                'url'        => $url,
                'title'      => $title,
                'company'    => $company,
                'short_desc' => $shortDesc,
            ];

            $offset = $pos + strlen($needle);
        }

        return $jobs;
    }

    protected function extractMyJobMagDetail(string $html): array
    {
        preg_match_all('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $matches);
        foreach ($matches[1] as $jsonStr) {
            $clean = preg_replace('/[\x00-\x1F]/', ' ', trim($jsonStr));
            $data  = json_decode($clean, true);
            if (! is_array($data) || ($data['@type'] ?? '') !== 'JobPosting') {
                continue;
            }

            $org      = $data['hiringOrganization'] ?? [];
            $location = $data['jobLocation']['address'] ?? [];
            $parts    = array_filter([
                trim($location['addressLocality'] ?? ''),
                trim($location['addressRegion'] ?? ''),
            ]);

            $rawDesc = html_entity_decode((string) ($data['description'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return [
                'title'        => (string) ($data['title'] ?? ''),
                'company_name' => trim((string) ($org['name'] ?? '')),
                'location'     => implode(', ', $parts),
                'industry'     => (string) ($data['industry'] ?? $data['occupationalCategory'] ?? ''),
                'datePosted'   => $data['datePosted'] ?? null,
                'validThrough' => $data['validThrough'] ?? null,
                'description'  => $rawDesc,
            ];
        }

        return [];
    }

    protected function myJobMagBaseUrl(string $url): string
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'www.myjobmag.com');
    }

    protected function myJobMagRequest(string $url): \Illuminate\Http\Client\Response
    {
        return Http::timeout(20)->withHeaders([
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Referer'         => $this->myJobMagBaseUrl($url) . '/',
        ])->get($url);
    }

    protected function fetchWpJobManagerJobs(JobCrawler $crawler): array
    {
        $baseUrl = rtrim((string) $crawler->source_url, '/');
        $endpoint = $baseUrl . '/jm-ajax/get_listings/';
        $jobs = [];
        $seen = [];

        for ($page = 1; $page <= 20; $page++) {
            $response = Http::asForm()
                ->timeout(30)
                ->withHeaders(['User-Agent' => 'WakandaJobsCrawler/1.0 (+https://www.wakandajobs.com)'])
                ->post($endpoint, [
                    'page' => $page,
                    'per_page' => 50,
                    'orderby' => 'featured',
                    'order' => 'DESC',
                    'search_keywords' => '',
                    'search_location' => '',
                    'search_categories' => '',
                ]);

            $response->throw();
            $payload = $response->json();
            $html = (string) ($payload['html'] ?? '');

            if ($html === '') {
                break;
            }

            preg_match_all(
                '#<li[^>]*class=["\'][^"\']*\bjob_listing\b[^"\']*["\'][^>]*>\s*<a[^>]+href=["\']([^"\']+)#is',
                $html,
                $matches
            );

            foreach ($matches[1] ?? [] as $url) {
                $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($url === '' || isset($seen[$url])) {
                    continue;
                }

                $seen[$url] = true;
                $jobs[] = ['url' => $url, 'slug' => basename(trim(parse_url($url, PHP_URL_PATH), '/'))];
            }

            $this->saveProgress($page, count($jobs));

            if ($page >= (int) ($payload['max_num_pages'] ?? 1)) {
                break;
            }
        }

        return $jobs;
    }

    protected function fetchEmpregoMzJobs(JobCrawler $crawler): array
    {
        $response = $this->structuredJobRequest((string) $crawler->source_url);
        $response->throw();

        preg_match_all(
            '#href=(?:"|\')?(https://www\.emprego\.co\.mz/vaga/[^"\'\s>]+)#i',
            $response->body(),
            $matches
        );

        $jobs = [];
        foreach (array_values(array_unique($matches[1] ?? [])) as $url) {
            $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $jobs[] = ['url' => $url, 'slug' => basename(trim(parse_url($url, PHP_URL_PATH), '/'))];
        }

        $this->saveProgress(1, count($jobs));

        return $jobs;
    }

    protected function fetchNooJobMonsterJobs(JobCrawler $crawler): array
    {
        $baseUrl = rtrim((string) $crawler->source_url, '/');

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
        $seenSlugs  = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $this->saveProgress($page, count($jobs));

            if ($page > 1) {
                sleep($isFullPull ? rand(2, 5) : rand(1, 3));
            }

            $url = $page === 1 ? "{$baseUrl}/jobs/" : "{$baseUrl}/jobs/page/{$page}/";
            $response = $this->structuredJobRequest($url);

            if (! $response->successful()) {
                break;
            }

            preg_match_all('/job-details-link"\s+href="([^"]+)"/', $response->body(), $m);

            if (empty($m[1])) {
                break;
            }

            $newOnPage = 0;

            foreach ($m[1] as $jobUrl) {
                $jobUrl = html_entity_decode($jobUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $slug   = basename(trim(parse_url($jobUrl, PHP_URL_PATH), '/'));

                if (isset($seenSlugs[$slug])) {
                    continue;
                }
                $seenSlugs[$slug] = true;
                $jobs[]           = ['url' => $jobUrl, 'slug' => $slug];

                if (! array_key_exists($slug, $existingIds)) {
                    $newOnPage++;
                }
            }

            if (! $isFullPull && ! $isFirstRun && $newOnPage === 0) {
                break;
            }
        }

        return $jobs;
    }

    protected function fetchEmploiticJobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->toArray();

        $isFirstRun = empty($existingIds);
        $isFullPull = $this->runMode === 'full';
        $maxItems   = $isFullPull ? 300 : 30;

        $response = $this->structuredJobRequest('https://emploitic.com/sitemap-jobs.xml');

        if (! $response->successful()) {
            return [];
        }

        $xml = @simplexml_load_string($response->body());

        if (! $xml) {
            return [];
        }

        $jobs = [];

        foreach ($xml->url as $node) {
            $url  = trim((string) $node->loc);
            $slug = basename(trim(parse_url($url, PHP_URL_PATH), '/'));

            if ($url === '' || $slug === '') {
                continue;
            }

            if (array_key_exists($slug, $existingIds)) {
                if (! $this->disableEarlyStop && ! $isFullPull && ! $isFirstRun && count($jobs) > 0) {
                    break;
                }
                continue;
            }

            $jobs[] = ['url' => $url, 'slug' => $slug];

            if (count($jobs) >= $maxItems) {
                break;
            }
        }

        $this->saveProgress(1, count($jobs));

        return $jobs;
    }

    protected function importStructuredJobs(JobCrawler $crawler, array $items): array
    {
        $mappings = $crawler->field_mappings ?? [];
        $countryId = (int) ($mappings['country_id'] ?? 0);
        $currencyId = isset($mappings['currency_id']) ? (int) $mappings['currency_id'] : null;
        $defaultLocation = (string) ($mappings['default_location'] ?? '');
        $stats = [
            'jobs_found' => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->all();

        $newItems = array_values(array_filter(
            $items,
            fn (array $item): bool => ! array_key_exists((string) ($item['slug'] ?? ''), $existingIds)
        ));

        $this->saveNewImportProgress(0, count($newItems), $stats);

        foreach ($newItems as $index => $item) {
            $slug = (string) ($item['slug'] ?? '');
            $url = (string) ($item['url'] ?? '');

            if ($slug === '' || $url === '') {
                $stats['jobs_skipped']++;
                continue;
            }

            try {
                $response = $this->structuredJobRequest($url);
                if (! $response->successful()) {
                    $stats['jobs_skipped']++;
                    continue;
                }

                $detail = $this->extractStructuredJobDetail($response->body());
            } catch (Throwable) {
                $stats['jobs_skipped']++;
                continue;
            }

            if (empty($detail['title']) || empty($detail['description'])) {
                $stats['jobs_skipped']++;
                continue;
            }

            $closingDate = ! empty($detail['validThrough'])
                ? Carbon::parse($detail['validThrough'])
                : null;

            if ($closingDate?->isPast()) {
                $stats['jobs_skipped']++;
                continue;
            }

            $company = $this->firstOrCreateStructuredCompany(
                (string) ($detail['company_name'] ?? ''),
                (string) ($detail['company_url'] ?? ''),
                $countryId
            );

            if (! $company) {
                $stats['jobs_skipped']++;
                continue;
            }

            $content = $this->cleanStructuredJobContent((string) $detail['description']);
            $location = trim((string) ($detail['location'] ?? '')) ?: $defaultLocation;
            $attributes = [
                'crawler_id' => $crawler->getKey(),
                'external_source_id' => $slug,
                'external_source_url' => $url,
                'name' => $this->limitGoZambiaField((string) $detail['title'], 110),
                'description' => Str::limit(trim(strip_tags($content)), 400),
                'content' => $content,
                'company_id' => $company->getKey(),
                'country_id' => $countryId ?: null,
                'currency_id' => $currencyId,
                'address' => $location,
                'apply_url' => $url,
                'status' => JobStatusEnum::PUBLISHED,
                'moderation_status' => ModerationStatusEnum::APPROVED,
                'salary_type' => SalaryTypeEnum::HIDDEN,
                'expire_date' => $closingDate ?: Carbon::now()->addDays(30),
                'application_closing_date' => $closingDate,
                'never_expired' => false,
            ];

            if (! empty($detail['datePosted'])) {
                $attributes['created_at'] = Carbon::parse($detail['datePosted']);
            }

            $job = new Job();
            $job->forceFill($attributes);
            $this->resolveApplyContact($job);
            if (! $job->apply_email) {
                $job->apply_url = $url;
            }
            $this->persistNewJob($job, $crawler, $stats, function (Job $job) use ($detail): void {
                $this->assignGoZambiaCategory($job, ['category' => ['name' => $detail['industry'] ?? '']]);
                $job->jobTypes()->syncWithoutDetaching([3]);
                $this->dispatchNewJobEvents($job);
            });

            $this->saveNewImportProgress($index + 1, count($newItems), $stats);
        }

        return $stats;
    }

    protected function extractStructuredJobDetail(string $html): array
    {
        preg_match_all(
            '/<script[^>]+type=(?:["\']application\/ld\+json["\']|application\/ld\+json)[^>]*>(.*?)<\/script>/is',
            $html,
            $matches
        );

        foreach ($matches[1] ?? [] as $json) {
            $data = json_decode(html_entity_decode(trim($json), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            $job = $this->findJobPostingNode($data);

            if (! $job) {
                continue;
            }

            $organization = $job['hiringOrganization'] ?? [];
            $jobLocation = $job['jobLocation'] ?? [];
            // jobLocation may be a single Place object or an array of Place objects (schema.org allows both)
            if (is_array($jobLocation) && array_is_list($jobLocation)) {
                $jobLocation = $jobLocation[0] ?? [];
            }
            $address = data_get($jobLocation, 'address', []);
            $location = is_array($address)
                ? implode(', ', array_filter([
                    $address['addressLocality'] ?? null,
                    $address['addressRegion'] ?? null,
                    $address['addressCountry'] ?? null,
                ]))
                : (string) $address;

            $description = html_entity_decode(
                (string) ($job['description'] ?? ''),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

            if (
                preg_match(
                    '/<div\s+class=(?:"|\')?medium-large-text(?:"|\')?[^>]*>(.*?)<div><\/div>\s*<div\s+class=(?:"|\')?how-to-apply-section/is',
                    $html,
                    $contentMatch
                )
            ) {
                $description = $contentMatch[1];
            }

            return [
                'title' => (string) ($job['title'] ?? ''),
                'company_name' => (string) ($organization['name'] ?? ''),
                'company_url' => (string) ($organization['sameAs'] ?? $organization['url'] ?? ''),
                'location' => $location,
                'industry' => (string) ($job['industry'] ?? $job['occupationalCategory'] ?? ''),
                'datePosted' => $job['datePosted'] ?? null,
                'validThrough' => $job['validThrough'] ?? null,
                'description' => $description,
            ];
        }

        return $this->extractNooJobMonsterDetail($html);
    }

    protected function extractNooJobMonsterDetail(string $html): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return [];
        }

        $xpath = new \DOMXPath($document);
        $titleNode = $xpath->query(
            '//h1[contains(concat(" ", normalize-space(@class), " "), " page-title ")]'
        )?->item(0);
        $descriptionNode = $xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " map-style-2 ") and @itemprop="description"]'
        )?->item(0);

        if (! $titleNode || ! $descriptionNode) {
            return [];
        }

        $titleClone = $titleNode->cloneNode(true);
        if ($titleClone instanceof \DOMElement) {
            foreach (iterator_to_array((new \DOMXPath($titleClone->ownerDocument))->query('.//span', $titleClone)) as $span) {
                $span->parentNode?->removeChild($span);
            }
        }

        $companyNode = $xpath->query(
            '//span[contains(concat(" ", normalize-space(@class), " "), " job-company ")]//span'
        )?->item(0);
        $companyLink = $xpath->query(
            '//span[contains(concat(" ", normalize-space(@class), " "), " job-company ")]//a'
        )?->item(0);
        $postedNode = $xpath->query('//span[contains(@class, "job-date")]//time[@datetime]')?->item(0);
        $closingNode = $xpath->query('//span[contains(@class, "job-date__closing")]')?->item(0);
        $categoryNode = $xpath->query(
            '//span[contains(concat(" ", normalize-space(@class), " "), " job-category ")]//a[1]'
        )?->item(0);

        $locations = [];
        foreach ($xpath->query(
            '(//div[contains(concat(" ", normalize-space(@class), " "), " job-details ")])[1]'
            . '//span[contains(@class, "job-location")]//em'
        ) ?: [] as $locationNode) {
            $location = trim($locationNode->textContent);
            if ($location !== '') {
                $locations[] = $location;
            }
        }

        $validThrough = null;
        $closingText = trim((string) $closingNode?->textContent);
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $closingText, $dateMatch)) {
            $validThrough = "{$dateMatch[3]}-{$dateMatch[2]}-{$dateMatch[1]}";
        }

        return [
            'title' => trim((string) $titleClone?->textContent),
            'company_name' => trim((string) $companyNode?->textContent),
            'company_url' => $companyLink instanceof \DOMElement ? (string) $companyLink->getAttribute('href') : '',
            'location' => implode(', ', array_unique($locations)),
            'industry' => trim((string) $categoryNode?->textContent),
            'datePosted' => $postedNode instanceof \DOMElement ? $postedNode->getAttribute('datetime') : null,
            'validThrough' => $validThrough,
            'description' => $this->domNodeInnerHtml($descriptionNode),
        ];
    }

    protected function domNodeInnerHtml(\DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument?->saveHTML($child) ?: '';
        }

        return trim($html);
    }

    protected function fetchIJobJobs(JobCrawler $crawler): array
    {
        $baseUrl = rtrim((string) $crawler->source_url, '/');
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->all();
        $jobs = [];
        $seen = [];
        $maxPages = $this->runMode === 'full' ? 20 : 5;

        for ($page = 1; $page <= $maxPages; $page++) {
            $url = $page === 1 ? "{$baseUrl}/" : "{$baseUrl}/page/{$page}/";
            $response = $this->structuredJobRequest($url);

            if (! $response->successful()) {
                break;
            }

            preg_match_all(
                '#<h4>\s*<a\s+href="(https?://(?:www\.)?ijob\.co\.za/\d{4}/\d{2}/\d{2}/[^"]+)"#i',
                $response->body(),
                $matches
            );

            if (empty($matches[1])) {
                break;
            }

            $newOnPage = 0;
            foreach ($matches[1] as $jobUrl) {
                $jobUrl = html_entity_decode($jobUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $slug = basename(trim(parse_url($jobUrl, PHP_URL_PATH), '/'));

                if ($slug === '' || isset($seen[$slug])) {
                    continue;
                }

                $seen[$slug] = true;
                $jobs[] = ['url' => $jobUrl, 'slug' => $slug];

                if (! array_key_exists($slug, $existingIds)) {
                    $newOnPage++;
                }
            }

            $this->saveProgress($page, count($jobs));

            if (! empty($existingIds) && $this->runMode !== 'full' && $newOnPage === 0) {
                break;
            }
        }

        return $jobs;
    }

    protected function importIJobJobs(JobCrawler $crawler, array $items): array
    {
        $stats = [
            'jobs_found' => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->all();
        $newItems = array_values(array_filter(
            $items,
            fn (array $item): bool => ! array_key_exists((string) ($item['slug'] ?? ''), $existingIds)
        ));

        $this->saveNewImportProgress(0, count($newItems), $stats);

        foreach ($newItems as $index => $item) {
            $slug = (string) ($item['slug'] ?? '');
            $url = (string) ($item['url'] ?? '');

            try {
                $response = $this->structuredJobRequest($url);
                $detail = $response->successful() ? $this->extractIJobDetail($response->body()) : [];
            } catch (Throwable) {
                $detail = [];
            }

            if ($slug === '' || empty($detail['title']) || empty($detail['description']) || ! empty($detail['excluded'])) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, count($newItems), $stats);
                continue;
            }

            $postedAt = Carbon::parse($detail['datePosted'] ?? 'now');
            $company = $this->firstOrCreateStructuredCompany(
                (string) ($detail['company_name'] ?? 'iJob South Africa'),
                '',
                53
            );

            if (! $company) {
                $stats['jobs_skipped']++;
                continue;
            }

            $content = $this->cleanStructuredJobContent((string) $detail['description']);
            $job = new Job();
            $job->forceFill([
                'crawler_id' => $crawler->getKey(),
                'external_source_id' => $slug,
                'external_source_url' => $url,
                'name' => $this->limitGoZambiaField((string) $detail['title'], 110),
                'description' => Str::limit(trim(strip_tags($content)), 400),
                'content' => $content,
                'company_id' => $company->getKey(),
                'country_id' => 53,
                'currency_id' => 46,
                'address' => (string) ($detail['location'] ?: 'South Africa'),
                'apply_url' => $url,
                'status' => JobStatusEnum::PUBLISHED,
                'moderation_status' => ModerationStatusEnum::APPROVED,
                'salary_type' => SalaryTypeEnum::HIDDEN,
                'career_level_id' => 3,
                'expire_date' => $postedAt->copy()->addDays(30),
                'application_closing_date' => $postedAt->copy()->addDays(30),
                'never_expired' => false,
                'created_at' => $postedAt,
                'updated_at' => $postedAt,
            ]);
            $this->resolveApplyContact($job);

            $this->persistNewJob($job, $crawler, $stats, function (Job $job) use ($detail): void {
                $this->assignGoZambiaCategory($job, ['category' => ['name' => $detail['category'] ?? '']]);
                $job->jobTypes()->syncWithoutDetaching([3]);
                $this->dispatchNewJobEvents($job);
            });

            $this->saveNewImportProgress($index + 1, count($newItems), $stats);
        }

        return $stats;
    }

    protected function extractIJobDetail(string $html): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return [];
        }

        $xpath = new \DOMXPath($document);
        $post = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " blog-post ")]')?->item(0);
        $content = $xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " blog-post-text ")]'
        )?->item(0);
        $title = $content ? $xpath->query('.//h1[1]', $content)?->item(0) : null;

        if (! $post || ! $content || ! $title) {
            return [];
        }

        foreach (iterator_to_array($xpath->query(
            './/div[contains(concat(" ", normalize-space(@class), " "), " code-block ")'
            . ' or contains(concat(" ", normalize-space(@class), " "), " addtoany_share_save_container ")]',
            $content
        )) as $unwanted) {
            $unwanted->parentNode?->removeChild($unwanted);
        }

        $category = trim((string) $xpath->query(
            '//ul[contains(concat(" ", normalize-space(@class), " "), " breadcrums ")]/li/a[2]'
        )?->item(0)?->textContent);
        $excludedCategories = ['bursaries', 'latest news', 'sassa grant', 'soccer', 'university application', 'universiy applications'];
        $titleText = trim($title->textContent);
        $plainText = trim($content->textContent);

        return [
            'title' => $titleText,
            'company_name' => $this->inferIJobCompany($titleText, $plainText),
            'location' => $this->inferIJobLocation($titleText, $plainText),
            'category' => $category,
            'datePosted' => $xpath->query('.//time[1]', $post)?->item(0)?->getAttribute('datetime'),
            'description' => $this->domNodeInnerHtml($content),
            'excluded' => in_array(mb_strtolower($category), $excludedCategories, true),
        ];
    }

    protected function inferIJobCompany(string $title, string $content): string
    {
        if (preg_match('/\b([A-Z][A-Za-z0-9&.\' -]{1,60}?)\s+(?:has announced|is hiring|has opened|is recruiting)\b/', $content, $match)) {
            return trim($match[1]);
        }

        if (preg_match('/^(.{2,70}?)\s+(?:is\s+)?(?:now\s+)?(?:hiring|recruitment|vacancies|learnerships?|opportunities)\b/i', $title, $match)) {
            return trim($match[1]);
        }

        return trim((string) Str::before($title, ' ')) ?: 'iJob South Africa';
    }

    protected function inferIJobLocation(string $title, string $content): string
    {
        $provinces = 'Eastern Cape|Free State|Gauteng|KwaZulu-Natal|Limpopo|Mpumalanga|North West|Northern Cape|Western Cape';

        if (preg_match('/\bin\s+([A-Z][A-Za-z .-]{2,40},\s*(?:' . $provinces . '))\b/', $title, $match)) {
            return trim($match[1]);
        }

        if (preg_match(
            '/\b(?:based|located)\s+in\s+(.{2,60}?)(?:[.;]|\s+and\s+(?:forms|is|offers|provides)\b)/i',
            $content,
            $match
        )) {
            return trim($match[1], " .,\n\r\t");
        }

        return 'South Africa';
    }

    protected function findJobPostingNode(mixed $data): ?array
    {
        if (! is_array($data)) {
            return null;
        }

        if (($data['@type'] ?? null) === 'JobPosting') {
            return $data;
        }

        foreach ($data as $value) {
            if ($job = $this->findJobPostingNode($value)) {
                return $job;
            }
        }

        return null;
    }

    protected function firstOrCreateStructuredCompany(string $name, string $website, int $countryId): ?Company
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $company = Company::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->first();

        if ($company) {
            return $company;
        }

        return $this->firstOrCreateCompany([
            'name' => $this->limitGoZambiaField($name, 110),
            'website' => $website !== '' ? $this->limitGoZambiaField($website, 110) : null,
            'country_id' => $countryId ?: null,
            'status' => BaseStatusEnum::PUBLISHED,
            'is_verified' => true,
            'verified_at' => Carbon::now(),
        ], $name);
    }

    protected function cleanStructuredJobContent(string $content): string
    {
        return trim(strip_tags($content, '<p><br><h2><h3><h4><h5><h6><ul><ol><li><strong><b><em><a>'));
    }

    protected function structuredJobRequest(string $url): \Illuminate\Http\Client\Response
    {
        return Http::timeout(30)->withHeaders([
            'User-Agent' => 'WakandaJobsCrawler/1.0 (+https://www.wakandajobs.com)',
            'Accept' => 'text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9,pt;q=0.8',
        ])->get($url);
    }

    // -------------------------------------------------------------------------
    // EthioJobs (Ethiopia)
    // -------------------------------------------------------------------------

    /** Public R2 bucket that serves EthioJobs company logos/banners. */
    protected const ETHIOJOBS_ASSET_BASE = 'https://pub-f30882b481294faa997a4d11ff77ce65.r2.dev/';

    protected function fetchEthioJobsJobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->all();

        $isFirstRun = empty($existingIds);
        $isFullPull = $this->runMode === 'full';
        // EthioJobs has ~70 pages of 12 jobs; full pull walks them all, incremental
        // only needs enough pages to catch up with new postings.
        $maxPages = $isFullPull ? 80 : 10;

        $jobs = [];
        $seen = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $this->saveProgress($page, count($jobs));

            if ($page > 1) {
                usleep(500_000);
            }

            $response = $this->ethioJobsRequest('https://ethiojobs.net/jobs?page=' . $page);
            if (! $response->successful()) {
                break;
            }

            $pageJobs = $this->extractEthioJobsList($response->body());
            if (empty($pageJobs)) {
                break;
            }

            $newOnPage = 0;

            foreach ($pageJobs as $job) {
                $slug = $this->normalizeExternalSourceId(data_get($job, 'slug'));
                if ($slug === '' || isset($seen[$slug])) {
                    continue;
                }
                $seen[$slug] = true;
                $jobs[] = $job;

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

    protected function ethioJobsRequest(string $url): \Illuminate\Http\Client\Response
    {
        return Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; WakandaJobsCrawler/1.0)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($url);
    }

    public function extractEthioJobsList(string $html): array
    {
        if (! preg_match('/id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            return [];
        }

        $data = json_decode($matches[1], true);

        return (array) data_get($data, 'props.pageProps.jobs.data', []);
    }

    protected function importEthioJobsJobs(JobCrawler $crawler, array $items): array
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

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->all();

        $newItems = [];
        $existingItems = [];

        foreach ($items as $item) {
            $slug = $this->normalizeExternalSourceId(data_get($item, 'slug'));
            if ($slug !== '' && array_key_exists($slug, $existingIds)) {
                $existingItems[] = $item;
            } else {
                $newItems[] = $item;
            }
        }

        $newTotal = count($newItems);
        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $slug = $this->normalizeExternalSourceId(data_get($item, 'slug'));
            $title = trim((string) data_get($item, 'title'));

            if ($slug === '' || $title === '') {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $expiresAt = data_get($item, 'date_expiry');
            if ($expiresAt && Carbon::parse($expiresAt)->isPast()) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->firstOrCreateEthioJobsCompany((array) data_get($item, 'company', []));
            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $newJob = new Job();
            $newJob->forceFill($this->buildEthioJobsAttributes($crawler, $item, $company));
            $this->resolveEthioJobsApplyContact($newJob, $item);

            $this->persistNewJob($newJob, $crawler, $stats, function (Job $j) use ($item): void {
                $category = (string) data_get($item, 'catalogs.0.name');
                $this->assignGoZambiaCategory($j, ['category' => ['name' => $category]]);
                $j->jobTypes()->syncWithoutDetaching([3]);
                $this->dispatchNewJobEvents($j);
            });

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        // Phase 2: refresh existing jobs from list data only (no detail fetch needed).
        $existingTotal = count($existingItems);
        $this->saveExistingUpdateProgress(0, $existingTotal, $stats);

        foreach ($existingItems as $index => $item) {
            $slug = $this->normalizeExternalSourceId(data_get($item, 'slug'));
            $job = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $slug)
                ->first();

            if ($job) {
                $expiresAt = data_get($item, 'date_expiry');
                $job->forceFill([
                    'name' => $this->limitGoZambiaField(trim((string) data_get($item, 'title')), 110),
                    'expire_date' => $expiresAt ? Carbon::parse($expiresAt) : Carbon::now()->addDays(30),
                    'application_closing_date' => $expiresAt ? Carbon::parse($expiresAt) : null,
                ]);

                if ($job->isDirty()) {
                    $job->save();
                    $stats['jobs_updated']++;
                } else {
                    $stats['jobs_skipped']++;
                }
            } else {
                $stats['jobs_skipped']++;
            }

            if ($index % 10 === 9 || $index === $existingTotal - 1) {
                $this->saveExistingUpdateProgress($index + 1, $existingTotal, $stats);
            }
        }

        return $stats;
    }

    protected function buildEthioJobsAttributes(JobCrawler $crawler, array $item, Company $company): array
    {
        $postedAt = data_get($item, 'date_published');
        $postedAtDate = $postedAt
            ? Carbon::parse($postedAt)->setTimezone(date_default_timezone_get())
            : Carbon::now();

        $expiresAt = data_get($item, 'date_expiry');
        $expireDate = $expiresAt ? Carbon::parse($expiresAt) : $postedAtDate->copy()->addDays(30);

        $description = (string) data_get($item, 'description', '');
        $slug = (string) data_get($item, 'slug');

        return [
            'crawler_id' => $crawler->getKey(),
            'external_source_id' => $this->normalizeExternalSourceId($slug),
            'external_source_url' => 'https://ethiojobs.net/job/' . $slug,
            'name' => $this->limitGoZambiaField(trim((string) data_get($item, 'title')), 110),
            'description' => Str::limit(trim(strip_tags($description)), 400, ''),
            'content' => $description,
            'company_id' => $company->getKey(),
            'address' => trim((string) data_get($item, 'state')) ?: 'Ethiopia',
            'country_id' => 27, // Ethiopia
            'currency_id' => 15, // ETB
            'status' => JobStatusEnum::PUBLISHED,
            'moderation_status' => ModerationStatusEnum::APPROVED,
            'salary_type' => SalaryTypeEnum::HIDDEN,
            'career_level_id' => 3, // Experienced Professional
            'is_featured' => false,
            'expire_date' => $expireDate,
            'application_closing_date' => $expiresAt ? Carbon::parse($expiresAt) : null,
            'never_expired' => false,
            'created_at' => $postedAtDate,
            'updated_at' => $postedAtDate,
        ];
    }

    /**
     * EthioJobs provides a direct application_email/career_page_link on the job
     * itself, which is more reliable than scraping the description text.
     */
    protected function resolveEthioJobsApplyContact(Job $job, array $item): void
    {
        $applyEmail = trim((string) data_get($item, 'application_email'));
        $careerLink = trim((string) data_get($item, 'career_page_link'));

        if ($applyEmail !== '') {
            $job->apply_email = $applyEmail;
            $subject = rawurlencode(trim(strip_tags((string) $job->name)) . ' Application');
            $job->apply_url = 'mailto:' . $applyEmail . '?subject=' . $subject;

            return;
        }

        if ($careerLink !== '') {
            $job->apply_url = $careerLink;

            return;
        }

        $this->resolveApplyContact($job);

        // ATS-based listings with no email/website fallback: send candidates to the
        // EthioJobs listing itself so they can apply through its own form.
        if (! $job->apply_email && ! $job->apply_url) {
            $job->apply_url = $job->external_source_url;
        }
    }

    protected function firstOrCreateEthioJobsCompany(array $company): ?Company
    {
        $name = trim((string) data_get($company, 'name'));
        if ($name === '') {
            return null;
        }

        $website = trim((string) data_get($company, 'website')) ?: null;
        $existing = $this->findGoZambiaCompany($name, $website);

        $attributes = [
            'name' => $this->limitGoZambiaField($name, 110),
            'website' => $website ? $this->limitGoZambiaField($website, 110) : null,
            'description' => Str::limit(trim(strip_tags((string) data_get($company, 'description'))), 400, ''),
            'content' => data_get($company, 'description'),
            'country_id' => 27, // Ethiopia
            'status' => BaseStatusEnum::PUBLISHED,
            'is_verified' => true,
            'verified_at' => Carbon::now(),
        ];

        if (! $existing) {
            $existing = $this->firstOrCreateCompany($attributes, $name);
            SlugHelper::createSlug($existing);
        } else {
            $existing->fill(array_filter($attributes, fn ($value) => $value !== null && $value !== ''))->save();
            if (! $existing->slugable) {
                SlugHelper::createSlug($existing);
            }
        }

        if (! $existing->logo && ($logo = data_get($company, 'logo'))) {
            $logoUrl = self::ETHIOJOBS_ASSET_BASE . ltrim((string) $logo, '/');
            $uploadedLogo = $this->uploadCompanyLogo($logoUrl);
            if ($uploadedLogo) {
                $existing->logo = $uploadedLogo;
                $existing->save();
            }
        }

        return $existing;
    }

    // -------------------------------------------------------------------------
    // Novojob (West/North Africa, Joomla-based RSS feeds: Côte d'Ivoire, Senegal,
    // Togo, Benin, Algeria, Morocco, etc.). One crawler per country, source_url
    // pointing at the country's "/rss" feed. country_id/currency_id/default_location
    // come from field_mappings.
    // -------------------------------------------------------------------------

    protected function fetchNovojobJobs(JobCrawler $crawler): array
    {
        $url = (string) $crawler->source_url;

        $response = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; WakandaJobsCrawler/1.0)',
                'Accept' => 'application/rss+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($url);

        if (! $response->successful()) {
            return [];
        }

        return $this->extractNovojobList($response->body());
    }

    public function extractNovojobList(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $rss = simplexml_load_string($xml);
        libxml_use_internal_errors($previous);

        if ($rss === false || ! isset($rss->channel->item)) {
            return [];
        }

        $items = [];

        foreach ($rss->channel->item as $item) {
            $items[] = [
                'title' => trim((string) $item->title),
                'link' => trim((string) $item->link),
                'description' => (string) $item->description,
                'pub_date' => trim((string) $item->pubDate),
            ];
        }

        return $items;
    }

    protected function importNovojobJobs(JobCrawler $crawler, array $items): array
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

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->all();

        $newItems = array_values(array_filter(
            $items,
            fn (array $item) => ! array_key_exists($this->novojobSourceId($item), $existingIds)
        ));

        $newTotal = count($newItems);
        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($newItems as $index => $item) {
            $sourceId = $this->novojobSourceId($item);
            $title = trim((string) ($item['title'] ?? ''));

            if ($sourceId === '' || $title === '') {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $fields = $this->parseNovojobDescription((string) ($item['description'] ?? ''));

            $company = $this->firstOrCreateNovojobCompany($crawler, (string) ($fields['fields']['Entreprise'] ?? ''));
            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $newJob = new Job();
            $newJob->forceFill($this->buildNovojobAttributes($crawler, $item, $fields, $company, $sourceId));
            $this->resolveApplyContact($newJob);
            if (! $newJob->apply_email && ! $newJob->apply_url) {
                $newJob->apply_url = $newJob->external_source_url;
            }

            $this->persistNewJob($newJob, $crawler, $stats, function (Job $j) use ($fields): void {
                $category = (string) ($fields['fields']['Métier / Fonction'] ?? '');
                $this->assignGoZambiaCategory($j, ['category' => ['name' => $category]]);
                $j->jobTypes()->syncWithoutDetaching([3]);
                $this->dispatchNewJobEvents($j);
            });

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        return $stats;
    }

    protected function novojobSourceId(array $item): string
    {
        $link = (string) ($item['link'] ?? '');

        if (preg_match('/(\d+)-[^\/]*$/', $link, $matches)) {
            return $matches[1];
        }

        return $link;
    }

    /**
     * Novojob descriptions start with a <ul> of "Label : Value" pairs (Entreprise,
     * Lieu de résidence, Métier / Fonction, etc.) followed by the free-text body.
     */
    protected function parseNovojobDescription(string $html): array
    {
        $fields = [];

        if (preg_match('/<ul>(.*?)<\/ul>/s', $html, $listMatch)) {
            if (preg_match_all('/<li>\s*<strong>(.*?)<\/strong>(.*?)<\/li>/s', $listMatch[1], $rowMatches, PREG_SET_ORDER)) {
                foreach ($rowMatches as $row) {
                    $label = trim(strip_tags($row[1]), " \t\n\r\0\x0B:");
                    $value = trim(strip_tags(html_entity_decode($row[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                    if ($label !== '' && $value !== '') {
                        $fields[$label] = $value;
                    }
                }
            }
        }

        $body = preg_replace('/^.*?<\/ul>/s', '', $html, 1) ?? $html;
        $body = trim((string) $body);

        return [
            'fields' => $fields,
            'body' => $body,
        ];
    }

    protected function buildNovojobAttributes(JobCrawler $crawler, array $item, array $fields, Company $company, string $sourceId): array
    {
        $postedAt = (string) ($item['pub_date'] ?? '');
        $postedAtDate = $postedAt ? Carbon::parse($postedAt) : Carbon::now();
        $expireDate = $postedAtDate->copy()->addDays(30);

        $body = (string) ($fields['body'] ?? '');
        $description = Str::limit(trim(strip_tags($body)), 400, '');

        $countryId = (int) ($crawler->field_mappings['country_id'] ?? 0) ?: null;
        $currencyId = (int) ($crawler->field_mappings['currency_id'] ?? 0) ?: null;
        $defaultLocation = (string) ($crawler->field_mappings['default_location'] ?? '');

        $address = trim((string) ($fields['fields']['Lieu de résidence'] ?? '')) ?: $defaultLocation;

        return [
            'crawler_id' => $crawler->getKey(),
            'external_source_id' => $sourceId,
            'external_source_url' => (string) ($item['link'] ?? ''),
            'name' => $this->limitGoZambiaField((string) ($item['title'] ?? ''), 110),
            'description' => $description,
            'content' => $body ?: $description,
            'company_id' => $company->getKey(),
            'address' => $address,
            'country_id' => $countryId,
            'currency_id' => $currencyId,
            'status' => JobStatusEnum::PUBLISHED,
            'moderation_status' => ModerationStatusEnum::APPROVED,
            'salary_type' => SalaryTypeEnum::HIDDEN,
            'career_level_id' => 3, // Experienced Professional
            'is_featured' => false,
            'expire_date' => $expireDate,
            'application_closing_date' => $expireDate,
            'never_expired' => false,
            'created_at' => $postedAtDate,
            'updated_at' => $postedAtDate,
        ];
    }

    protected function firstOrCreateNovojobCompany(JobCrawler $crawler, string $name): ?Company
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $company = $this->findGoZambiaCompany($name, null);

        if (! $company) {
            $countryId = (int) ($crawler->field_mappings['country_id'] ?? 0) ?: null;

            $company = $this->firstOrCreateCompany([
                'name' => $this->limitGoZambiaField($name, 110),
                'country_id' => $countryId,
                'status' => BaseStatusEnum::PUBLISHED,
                'is_verified' => false,
            ], $name);
            SlugHelper::createSlug($company);
        }

        return $company;
    }

    // -------------------------------------------------------------------------
    // VacancyMail (vacancymail.co.zw) — paginated HTML listing + JSON-LD detail pages
    // -------------------------------------------------------------------------

    protected function fetchVacancyMailJobs(JobCrawler $crawler): array
    {
        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->all();

        $isFirstRun = empty($existingIds);
        $isFullPull = $this->runMode === 'full';
        $maxPages = $isFullPull ? 30 : 5;
        $maxNewDetails = $isFullPull ? 300 : 30;

        $jobs = [];
        $newDetails = 0;

        for ($page = 1; $page <= $maxPages; $page++) {
            $this->saveProgress($page, count($jobs));

            if ($page > 1) {
                usleep(500_000);
            }

            $response = $this->vacancyMailRequest('https://www.vacancymail.co.zw/jobs/?page=' . $page);
            if (! $response->successful()) {
                break;
            }

            $links = $this->extractVacancyMailListLinks($response->body());
            if (empty($links)) {
                break;
            }

            $newOnPage = 0;

            foreach ($links as $link) {
                $sourceId = $link['id'];

                if (array_key_exists($sourceId, $existingIds)) {
                    continue;
                }

                $newOnPage++;
                usleep(300_000);

                $detail = $this->fetchVacancyMailJobDetail($link['url']);
                if ($detail) {
                    $jobs[] = array_merge(['id' => $sourceId, 'url' => $link['url']], $detail);
                    $newDetails++;
                }

                if ($newDetails >= $maxNewDetails) {
                    return $jobs;
                }
            }

            if (! $isFullPull && ! $isFirstRun && $page > 1 && $newOnPage === 0) {
                break;
            }
        }

        return $jobs;
    }

    protected function vacancyMailRequest(string $url): \Illuminate\Http\Client\Response
    {
        return Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; WakandaJobsCrawler/1.0)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($url);
    }

    public function extractVacancyMailListLinks(string $html): array
    {
        if (! preg_match_all('/<a href="(\/jobs\/[a-z0-9-]+-(\d+)\/)" class="job-listing"/', $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $links = [];
        $seen = [];

        foreach ($matches as $match) {
            $id = $match[2];
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $links[] = ['id' => $id, 'url' => 'https://www.vacancymail.co.zw' . $match[1]];
        }

        return $links;
    }

    protected function fetchVacancyMailJobDetail(string $url): ?array
    {
        $response = $this->vacancyMailRequest($url);

        if (! $response->successful()) {
            return null;
        }

        return $this->parseVacancyMailJobPage($response->body(), $url);
    }

    public function parseVacancyMailJobPage(string $html, string $url): ?array
    {
        $data = $this->extractVacancyBoxJsonLd($html);

        $title = trim(html_entity_decode((string) ($data['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($title === '') {
            return null;
        }

        $company = trim(html_entity_decode((string) data_get($data, 'hiringOrganization.name', ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $logo = (string) data_get($data, 'hiringOrganization.logo', '');
        $website = (string) data_get($data, 'hiringOrganization.sameAs', '');

        $address = data_get($data, 'jobLocation.address', []);
        $location = is_array($address)
            ? trim((string) ($address['addressLocality'] ?? $address['addressRegion'] ?? ''))
            : '';

        $category = '';
        if (preg_match('/<h5><a href="\/categories\/[^"]*">([^<]+)<\/a><\/h5>/', $html, $catMatch)) {
            $category = trim(html_entity_decode($catMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $category = trim(preg_replace('/\s+Jobs$/i', '', $category) ?? $category);
        }

        $content = $this->extractVacancyMailContent($html);
        $description = Str::limit(trim(strip_tags($content)), 400, '');

        return [
            'title' => $title,
            'company' => $company ?: 'VacancyMail',
            'logo' => $logo,
            'website' => $website,
            'location' => $location ?: 'Zimbabwe',
            'category' => $category,
            'content' => $content,
            'description' => $description,
            'apply_url' => $url,
            'date' => (string) ($data['datePosted'] ?? '') ?: null,
            'deadline' => (string) ($data['validThrough'] ?? '') ?: null,
        ];
    }

    /**
     * Concatenates the "Job Description", "Duties and Responsibilities",
     * "Qualifications and Experience" and "How to Apply" sections (the latter
     * usually contains the application email used by resolveApplyContact()).
     */
    protected function extractVacancyMailContent(string $html): string
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_use_internal_errors(false);

        $xpath = new \DOMXPath($dom);
        $sections = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' single-page-section ')]");

        $parts = [];

        foreach ($sections as $section) {
            $headingNodes = $xpath->query('.//h3', $section);
            $heading = $headingNodes->length ? trim($headingNodes->item(0)->textContent) : '';
            if (stripos($heading, 'Similar Jobs') !== false) {
                continue;
            }

            $inner = '';
            foreach ($section->childNodes as $child) {
                $inner .= $dom->saveHTML($child);
            }
            $parts[] = $inner;
        }

        return implode('', $parts);
    }

    protected function importVacancyMailJobs(JobCrawler $crawler, array $items): array
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

        $newTotal = count($items);
        $this->saveNewImportProgress(0, $newTotal, $stats);

        foreach ($items as $index => $item) {
            $sourceId = (string) ($item['id'] ?? '');
            $title = trim((string) ($item['title'] ?? ''));

            if ($sourceId === '' || $title === '') {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $company = $this->firstOrCreateVacancyMailCompany($item);
            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, $newTotal, $stats);
                continue;
            }

            $newJob = new Job();
            $newJob->forceFill($this->buildVacancyMailAttributes($crawler, $item, $company));
            $this->resolveApplyContact($newJob);
            if (! $newJob->apply_email && ! $newJob->apply_url) {
                $newJob->apply_url = $newJob->external_source_url;
            }

            $this->persistNewJob($newJob, $crawler, $stats, function (Job $j) use ($item): void {
                $category = (string) ($item['category'] ?? '');
                $this->assignGoZambiaCategory($j, ['category' => ['name' => $category]]);
                $j->jobTypes()->syncWithoutDetaching([3]);
                $this->dispatchNewJobEvents($j);
            });

            $this->saveNewImportProgress($index + 1, $newTotal, $stats);
        }

        return $stats;
    }

    protected function firstOrCreateVacancyMailCompany(array $item): ?Company
    {
        $name = trim((string) ($item['company'] ?? ''));
        if ($name === '' || $name === 'VacancyMail') {
            return null;
        }

        $website = $this->normalizeGoZambiaCompanyWebsite((string) ($item['website'] ?? ''));
        $company = $this->findGoZambiaCompany($name, $website);

        if (! $company) {
            $company = $this->firstOrCreateCompany([
                'name' => $this->limitGoZambiaField($name, 110),
                'website' => $website ? $this->limitGoZambiaField($website, 110) : null,
                'country_id' => 60, // Zimbabwe
                'status' => BaseStatusEnum::PUBLISHED,
                'is_verified' => false,
            ], $name);
            SlugHelper::createSlug($company);

            if (! empty($item['logo'])) {
                $logoPath = $this->uploadCompanyLogo((string) $item['logo']);
                if ($logoPath) {
                    $company->logo = $logoPath;
                    $company->saveQuietly();
                }
            }
        }

        return $company;
    }

    protected function buildVacancyMailAttributes(JobCrawler $crawler, array $item, Company $company): array
    {
        $postedDate = Carbon::now();
        if (! empty($item['date'])) {
            try {
                $normalized = str_ireplace(['a.m.', 'p.m.'], ['am', 'pm'], (string) $item['date']);
                $postedDate = Carbon::parse($normalized);
            } catch (Throwable) {
            }
        }

        $expireDate = $postedDate->copy()->addDays(30);
        if (! empty($item['deadline'])) {
            try {
                $expireDate = Carbon::parse((string) $item['deadline']);
            } catch (Throwable) {
            }
        }

        $content = (string) ($item['content'] ?? '');
        $description = (string) ($item['description'] ?? '') ?: Str::limit(trim(strip_tags($content)), 400, '');

        return [
            'crawler_id' => $crawler->getKey(),
            'external_source_id' => (string) ($item['id'] ?? ''),
            'external_source_url' => (string) ($item['url'] ?? ''),
            'name' => $this->limitGoZambiaField((string) ($item['title'] ?? ''), 110),
            'description' => $description,
            'content' => $content ?: $description,
            'company_id' => $company->getKey(),
            'address' => (string) ($item['location'] ?? 'Zimbabwe'),
            'country_id' => 60, // Zimbabwe
            'currency_id' => 47, // ZWL
            'apply_url' => (string) ($item['apply_url'] ?? $item['url'] ?? ''),
            'status' => JobStatusEnum::PUBLISHED,
            'moderation_status' => ModerationStatusEnum::APPROVED,
            'salary_type' => SalaryTypeEnum::HIDDEN,
            'career_level_id' => 3, // Experienced Professional
            'is_featured' => false,
            'expire_date' => $expireDate,
            'application_closing_date' => $expireDate,
            'never_expired' => false,
            'created_at' => $postedDate,
            'updated_at' => $postedDate,
        ];
    }

    // -------------------------------------------------------------------------
    // JobPoint Botswana — paginated Inertia listing payload
    // -------------------------------------------------------------------------

    protected function fetchJobPointJobs(JobCrawler $crawler): array
    {
        $baseUrl = rtrim((string) $crawler->source_url, '/');
        $jobs = [];
        $seen = [];

        for ($page = 1; $page <= 70; $page++) {
            $url = $baseUrl . ($page > 1 ? '?page=' . $page : '');
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; WakandaJobsCrawler/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);

            $response->throw();

            $openings = $this->extractJobPointOpenings($response->body());
            $pageItems = $openings['data'] ?? [];

            if (! is_array($pageItems) || empty($pageItems)) {
                break;
            }

            $liveOnPage = 0;

            foreach ($pageItems as $item) {
                if (! is_array($item) || ! $this->isLiveJobPointOpening($item)) {
                    continue;
                }

                $sourceId = (string) ($item['id'] ?? $item['uuid'] ?? '');
                $slug = trim((string) ($item['slug'] ?? ''));

                if ($sourceId === '' || $slug === '' || isset($seen[$sourceId])) {
                    continue;
                }

                $seen[$sourceId] = true;
                $item['source_url'] = $baseUrl . '/' . rawurlencode($slug);
                $jobs[] = $item;
                $liveOnPage++;
            }

            $this->saveProgress($page, count($jobs));

            if ($liveOnPage === 0 || $page >= (int) ($openings['last_page'] ?? $page)) {
                break;
            }
        }

        return $jobs;
    }

    public function extractJobPointOpenings(string $html): array
    {
        if (! preg_match('/<div\s+id="app"\s+data-page="([^"]+)"><\/div>/i', $html, $match)) {
            return [];
        }

        $payload = json_decode(
            html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            true
        );

        $openings = data_get($payload, 'props.openings');

        return is_array($openings) ? $openings : [];
    }

    protected function isLiveJobPointOpening(array $item): bool
    {
        if ((int) ($item['status'] ?? 1) !== 1 || ! empty($item['is_expired'])) {
            return false;
        }

        $deadline = $item['application_deadline'] ?? $item['expired_at'] ?? null;

        if ($deadline) {
            try {
                return ! Carbon::parse((string) $deadline)->isPast();
            } catch (Throwable) {
            }
        }

        return true;
    }

    protected function importJobPointJobs(JobCrawler $crawler, array $items): array
    {
        $stats = [
            'jobs_found' => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        $existingIds = Job::query()
            ->where('crawler_id', $crawler->getKey())
            ->whereNotNull('external_source_id')
            ->pluck('external_source_id')
            ->flip()
            ->all();

        $newItems = array_values(array_filter(
            $items,
            fn (array $item): bool => ! array_key_exists((string) ($item['id'] ?? $item['uuid'] ?? ''), $existingIds)
        ));

        $this->saveNewImportProgress(0, count($newItems), $stats);

        foreach ($newItems as $index => $item) {
            $sourceId = (string) ($item['id'] ?? $item['uuid'] ?? '');
            $title = trim((string) ($item['title'] ?? ''));

            if ($sourceId === '' || $title === '') {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, count($newItems), $stats);
                continue;
            }

            $company = $this->firstOrCreateJobPointCompany($crawler, $item);
            if (! $company) {
                $stats['jobs_skipped']++;
                $this->saveNewImportProgress($index + 1, count($newItems), $stats);
                continue;
            }

            $job = new Job();
            $job->forceFill($this->buildJobPointAttributes($crawler, $item, $company));
            $this->resolveApplyContact($job);
            if (! $job->apply_email) {
                $job->apply_url = $job->external_source_url;
            }

            $this->persistNewJob($job, $crawler, $stats, function (Job $job) use ($item): void {
                $category = (string) data_get($item, 'categories.0.title', '');
                $this->assignGoZambiaCategory($job, ['category' => ['name' => $category]]);
                $job->jobTypes()->syncWithoutDetaching([3]);
                $this->dispatchNewJobEvents($job);
            });

            $this->saveNewImportProgress($index + 1, count($newItems), $stats);
        }

        return $stats;
    }

    protected function firstOrCreateJobPointCompany(JobCrawler $crawler, array $item): ?Company
    {
        $name = trim((string) (
            data_get($item, 'user.meta.company.name')
            ?: ($item['company_name'] ?? null)
            ?: data_get($item, 'user.display_name')
            ?: data_get($item, 'user.name')
            ?: ''
        ));

        if ($name === '') {
            return null;
        }

        $website = $this->normalizeGoZambiaCompanyWebsite(
            (string) data_get($item, 'user.meta.business.site_url', '')
        );
        $company = $this->findGoZambiaCompany($name, $website);

        if (! $company) {
            $countryId = (int) ($crawler->field_mappings['country_id'] ?? 0) ?: null;
            $company = $this->firstOrCreateCompany([
                'name' => $this->limitGoZambiaField($name, 110),
                'website' => $website ? $this->limitGoZambiaField($website, 110) : null,
                'description' => Str::limit(
                    trim((string) data_get($item, 'user.meta.business.description', '')),
                    400,
                    ''
                ),
                'country_id' => $countryId,
                'status' => BaseStatusEnum::PUBLISHED,
                'is_verified' => false,
            ], $name);
            SlugHelper::createSlug($company);

            $logoUrl = (string) (
                data_get($item, 'user.meta.company.logo')
                ?: ($item['logo'] ?? '')
            );
            if ($logoUrl !== '') {
                $logoPath = $this->uploadCompanyLogo($logoUrl);
                if ($logoPath) {
                    $company->logo = $logoPath;
                    $company->saveQuietly();
                }
            }
        }

        return $company;
    }

    protected function buildJobPointAttributes(JobCrawler $crawler, array $item, Company $company): array
    {
        $postedAt = Carbon::now();
        if (! empty($item['created_at'])) {
            try {
                $postedAt = Carbon::parse((string) $item['created_at']);
            } catch (Throwable) {
            }
        }

        $deadline = $postedAt->copy()->addDays(30);
        $deadlineValue = $item['application_deadline'] ?? $item['expired_at'] ?? null;
        if ($deadlineValue) {
            try {
                $deadline = Carbon::parse((string) $deadlineValue);
            } catch (Throwable) {
            }
        }

        $content = $this->jobPointContent($item);
        $description = trim(strip_tags((string) ($item['short_description'] ?? $item['description'] ?? '')));
        $sourceId = (string) ($item['id'] ?? $item['uuid'] ?? '');
        $sourceUrl = (string) ($item['source_url'] ?? '');
        $countryId = (int) ($crawler->field_mappings['country_id'] ?? 0) ?: null;
        $currencyId = (int) ($crawler->field_mappings['currency_id'] ?? 0) ?: null;
        $defaultLocation = (string) ($crawler->field_mappings['default_location'] ?? 'Botswana');

        return [
            'crawler_id' => $crawler->getKey(),
            'external_source_id' => $sourceId,
            'external_source_url' => $sourceUrl,
            'name' => $this->limitGoZambiaField((string) ($item['title'] ?? ''), 110),
            'description' => Str::limit($description ?: trim(strip_tags($content)), 400, ''),
            'content' => $content,
            'company_id' => $company->getKey(),
            'address' => trim((string) ($item['address'] ?? '')) ?: $defaultLocation,
            'country_id' => $countryId,
            'currency_id' => $currencyId,
            'apply_url' => $sourceUrl,
            'status' => JobStatusEnum::PUBLISHED,
            'moderation_status' => ModerationStatusEnum::APPROVED,
            'salary_type' => SalaryTypeEnum::HIDDEN,
            'career_level_id' => 3,
            'is_featured' => false,
            'expire_date' => $deadline,
            'application_closing_date' => $deadline,
            'never_expired' => false,
            'created_at' => $postedAt,
            'updated_at' => $postedAt,
        ];
    }

    protected function jobPointContent(array $item): string
    {
        $parts = [];

        foreach ([
            'description' => null,
            'responsibilities' => 'Responsibilities',
        ] as $field => $heading) {
            $html = trim((string) ($item[$field] ?? ''));
            if ($html === '') {
                continue;
            }

            $parts[] = $heading ? '<h3>' . $heading . '</h3>' . $html : $html;
        }

        $instructions = trim((string) ($item['application_instructions'] ?? ''));
        if ($instructions !== '') {
            $parts[] = '<h3>Application Instructions</h3><p>'
                . nl2br(htmlspecialchars($instructions, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                . '</p>';
        }

        return implode("\n", $parts);
    }
}
