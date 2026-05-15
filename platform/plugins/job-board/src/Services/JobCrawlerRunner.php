<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Enums\ModerationStatusEnum;
use Botble\JobBoard\Enums\SalaryRangeEnum;
use Botble\JobBoard\Enums\SalaryTypeEnum;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Currency;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobCrawler;
use Botble\JobBoard\Models\JobCrawlerRun;
use Botble\Media\Facades\RvMedia;
use Botble\Slug\Facades\SlugHelper;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Throwable;

class JobCrawlerRunner
{
    protected ?JobCrawlerRun $currentRun = null;

    public function run(JobCrawler $crawler): JobCrawlerRun
    {
        $run = JobCrawlerRun::query()->create([
            'crawler_id' => $crawler->getKey(),
            'status' => 'running',
            'started_at' => Carbon::now(),
            'meta' => ['current_page' => 0, 'total_pages' => 20, 'jobs_found_so_far' => 0],
        ]);

        $this->executeRun($crawler, $run);

        return $run;
    }

    public function executeRun(JobCrawler $crawler, JobCrawlerRun $run): void
    {
        $this->currentRun = $run;

        try {
            $items = $this->fetchItems($crawler);
            $stats = $this->importItems($crawler, $items);

            $run->fill([
                'status' => 'success',
                'finished_at' => Carbon::now(),
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

    protected function saveProgress(int $page, int $jobsFoundSoFar): void
    {
        if (! $this->currentRun) {
            return;
        }

        $this->currentRun->meta = array_merge($this->currentRun->meta ?? [], [
            'current_page' => $page,
            'jobs_found_so_far' => $jobsFoundSoFar,
        ]);
        $this->currentRun->saveQuietly();
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

    protected function fetchGoZambiaJobs(JobCrawler $crawler): array
    {
        $jobs = [];
        $seenIds = [];

        for ($page = 1; $page <= 20; $page++) {
            $this->saveProgress($page, count($jobs));

            $response = $this->goZambiaRequest($this->goZambiaPageUrl($crawler->source_url, $page));

            if ($response->notFound()) {
                break;
            }

            $response->throw();

            $html = $response->body();
            if ($this->goZambiaHasNoMatches($html)) {
                break;
            }

            $pageJobs = $this->extractGoZambiaJobsList($html);
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
        }

        return array_map(function (array $job): array {
            $detailUrl = $this->absoluteGoZambiaUrl((string) data_get($job, 'job_details_path'));

            if ($detailUrl) {
                try {
                    $detailResponse = $this->goZambiaRequest($detailUrl);
                    if ($detailResponse->successful()) {
                        $detailJob = $this->extractGoZambiaDetailJob($detailResponse->body());
                        if ($detailJob) {
                            $job = array_replace_recursive($job, $detailJob);
                        }
                    }
                } catch (Throwable) {
                    // Keep the list payload if a single detail page fails.
                }
            }

            $job['external_source_url'] = $detailUrl ?: $this->absoluteGoZambiaUrl((string) data_get($job, 'job_details_path'));

            return $job;
        }, $jobs);
    }

    protected function importGoZambiaJobs(JobCrawler $crawler, array $items): array
    {
        $stats = [
            'jobs_found' => count($items),
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
        ];

        foreach ($items as $item) {
            $sourceId = (string) data_get($item, 'id');
            $title = trim((string) data_get($item, 'title'));

            if ($sourceId === '' || $title === '') {
                $stats['jobs_skipped']++;

                continue;
            }

            $company = $this->firstOrCreateGoZambiaCompany((array) data_get($item, 'employer', []));
            if (! $company) {
                $stats['jobs_skipped']++;

                continue;
            }

            $description = $this->sanitizeGoZambiaHtml((string) data_get($item, 'description'));
            $sourceUrl = (string) data_get($item, 'external_source_url') ?: $this->absoluteGoZambiaUrl((string) data_get($item, 'job_details_path'));
            $postedAt = data_get($item, 'posted_at');
            $postedAtDate = $postedAt ? Carbon::parse($postedAt) : null;
            $expiresAt = data_get($item, 'validThrough')
                ?: ($postedAtDate ? $postedAtDate->copy()->addDays((int) data_get($item, 'job_expires_in_days', 30)) : null);

            $address = data_get($item, 'job_location.name')
                ?: data_get($item, 'location')
                ?: 'Zambia';

            $attributes = [
                'crawler_id' => $crawler->getKey(),
                'external_source_id' => $sourceId,
                'external_source_url' => $sourceUrl,
                'name' => $this->limitGoZambiaField($title, 110),
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
                'salary_type' => (data_get($item, 'min_compensation') || data_get($item, 'max_compensation')) ? SalaryTypeEnum::FIXED : SalaryTypeEnum::HIDDEN,
                'currency_id' => $this->currencyIdForCode((string) data_get($item, 'compensation_currency')),
                'career_level_id' => 3, // Experienced Professional
                'is_featured' => (bool) data_get($item, 'featured'),
                'latitude' => data_get($item, 'job_location.latitude'),
                'longitude' => data_get($item, 'job_location.longitude'),
                'expire_date' => $expiresAt ? Carbon::parse($expiresAt) : Carbon::now()->addDays(30),
                'application_closing_date' => $expiresAt ? Carbon::parse($expiresAt) : null,
                'never_expired' => false,
                'created_at' => $postedAtDate ?? Carbon::now(),
                'updated_at' => $postedAtDate ?? Carbon::now(),
            ];

            $job = Job::query()
                ->where('crawler_id', $crawler->getKey())
                ->where('external_source_id', $sourceId)
                ->first();

            if ($job) {
                $job->fill($attributes)->save();
                $stats['jobs_updated']++;
            } else {
                $newJob = Job::query()->create($attributes);
                SlugHelper::createSlug($newJob);
                $this->syncJobCategories($newJob);
                $newJob->jobTypes()->syncWithoutDetaching([3]); // Full Time
                $stats['jobs_created']++;
            }
        }

        return $stats;
    }

    protected function firstOrCreateGoZambiaCompany(array $employer): ?Company
    {
        $name = trim((string) data_get($employer, 'name'));
        if ($name === '') {
            return null;
        }

        $website = $this->normalizeGoZambiaCompanyWebsite((string) data_get($employer, 'website'));
        // SafeContent cast encodes HTML entities on write, so match against the stored form
        $storedName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $company = Company::query()
            ->where(function ($q) use ($website, $name, $storedName) {
                if ($website) {
                    $q->where('website', $website);
                }
                $q->orWhere('name', $name)
                  ->orWhere('name', $storedName);
            })
            ->first();

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

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? 'gozambiajobs.com';
        $path = $parts['path'] ?? '/jobs';

        return sprintf('%s://%s%s?%s', $scheme, $host, $path, http_build_query($query));
    }

    protected function goZambiaHasNoMatches(string $html): bool
    {
        return str_contains($html, 'Sorry, we couldn’t find any matches for your search.')
            || str_contains($html, "Sorry, we couldn't find any matches for your search.");
    }

    protected function extractGoZambiaJobsList(string $html): array
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

    protected function extractGoZambiaDetailJob(string $html): ?array
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
