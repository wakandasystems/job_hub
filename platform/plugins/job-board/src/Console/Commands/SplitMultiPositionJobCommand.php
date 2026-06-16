<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Enums\ModerationStatusEnum;
use Botble\JobBoard\Enums\SalaryTypeEnum;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobCrawler;
use Botble\JobBoard\Services\JobCrawlerRunner;
use Botble\Slug\Facades\SlugHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SplitMultiPositionJobCommand extends Command
{
    protected $signature = 'job-board:split-multi-position-job
                            {jobs?* : One or more Job IDs to split (omit for --all)}
                            {--all : Scan every crawled job for multi-position content}
                            {--dry-run : Preview splits without saving}';

    protected $description = 'Split "Multiple Positions" job posts into individual position jobs.';

    public function handle(JobCrawlerRunner $runner): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $jobIds  = (array) $this->argument('jobs');
        $scanAll = (bool) $this->option('all');

        if (empty($jobIds) && ! $scanAll) {
            $this->components->error('Provide one or more Job IDs, or pass --all to scan every crawled job.');
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->components->warn('DRY RUN — no changes will be saved.');
        }

        $query = Job::query()->whereNotNull('crawler_id');

        if (! empty($jobIds)) {
            $query->whereIn('id', $jobIds);
        } else {
            // --all: only jobs with "POSITION-SPECIFIC REQUIREMENTS" in content,
            // to avoid fetching and parsing thousands of normal jobs.
            $query->whereRaw("content LIKE '%POSITION-SPECIFIC REQUIREMENTS%'");
        }

        $jobs  = $query->with('company')->get();
        $total = $jobs->count();

        if ($total === 0) {
            $this->components->info('No matching jobs found.');
            return self::SUCCESS;
        }

        $this->components->info("Checking {$total} job(s)…");

        $split   = 0;
        $created = 0;
        $skipped = 0;

        foreach ($jobs as $job) {
            $item = $this->jobToItem($job);

            $positions = $runner->splitMultiPositionJob($item);

            if ($positions === null) {
                $this->line("  <fg=gray>skip</> [{$job->id}] {$job->name} — no position-specific section found");
                $skipped++;
                continue;
            }

            $this->line("  <fg=green>split</> [{$job->id}] {$job->name} → " . count($positions) . ' positions');

            if ($dryRun) {
                foreach ($positions as $pos) {
                    $this->line("    · {$pos['title']}");
                }
                $split++;
                $created += count($positions);
                continue;
            }

            $crawler = JobCrawler::find($job->crawler_id);
            if (! $crawler) {
                $this->components->warn("Crawler {$job->crawler_id} not found for job {$job->id} — skipped.");
                $skipped++;
                continue;
            }

            $company = $job->company;
            if (! $company) {
                $this->components->warn("Job {$job->id} has no company — skipped.");
                $skipped++;
                continue;
            }

            $posCreated = 0;

            foreach ($positions as $pos) {
                if ($this->createPositionJob($crawler, $company, $job, $pos)) {
                    $posCreated++;
                    $created++;
                }
            }

            if ($posCreated > 0) {
                // Remove the original multi-position parent to avoid duplicates on the site.
                $job->delete();
                $split++;
                $this->line("    <fg=yellow>deleted</> original job [{$job->id}]");
            }
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Multi-position jobs ' . ($dryRun ? 'found' : 'split'), $split],
                ['Individual position jobs ' . ($dryRun ? 'found' : 'created'), $created],
                ['Jobs skipped (no multi-position content)', $skipped],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Convert a Job model back to the flat item array that the splitter expects.
     */
    private function jobToItem(Job $job): array
    {
        return [
            'id'        => (string) ($job->external_source_id ?? ''),
            'title'     => (string) $job->name,
            'content'   => (string) ($job->getRawOriginal('content') ?? ''),
            'location'  => (string) ($job->address ?? ''),
            'apply_url' => (string) ($job->apply_url ?? ''),
            'url'       => (string) ($job->external_source_url ?? ''),
            'date'      => $job->created_at?->toDateString(),
            'deadline'  => $job->application_closing_date?->toDateString()
                         ?? $job->expire_date?->toDateString(),
            'company'   => (string) ($job->company?->name ?? ''),
        ];
    }

    private function createPositionJob(JobCrawler $crawler, Company $company, Job $parent, array $pos): bool
    {
        $sourceId    = (string) ($pos['id'] ?? '');
        $rawContent  = (string) ($pos['content'] ?? '');
        $excerptHtml = (string) ($pos['excerpt'] ?? $rawContent);
        $description = Str::limit(trim(strip_tags($excerptHtml)), 400, '');

        $deadline = $parent->application_closing_date ?? $parent->expire_date ?? Carbon::now()->addDays(45);

        // Guard: don't create a duplicate (same name + company already exists).
        $nameLimit = mb_substr(trim((string) $pos['title']), 0, 110);

        if (Job::query()->where('name', $nameLimit)->where('company_id', $company->id)->exists()) {
            $this->line("    <fg=gray>skip duplicate</> {$nameLimit}");
            return false;
        }

        $job = new Job();
        $job->forceFill([
            'crawler_id'               => $crawler->getKey(),
            'external_source_id'       => $sourceId,
            'external_source_url'      => (string) ($pos['url'] ?? $parent->external_source_url ?? ''),
            'name'                     => $nameLimit,
            'description'              => $description,
            'content'                  => $rawContent ?: $description,
            'company_id'               => $company->getKey(),
            'address'                  => (string) ($pos['location'] ?? $parent->address ?? 'Zambia'),
            'country_id'               => (int) ($parent->country_id ?? 7),
            'apply_url'                => (string) ($pos['apply_url'] ?? $parent->apply_url ?? ''),
            'status'                   => JobStatusEnum::PUBLISHED,
            'moderation_status'        => ModerationStatusEnum::APPROVED,
            'salary_type'              => SalaryTypeEnum::HIDDEN,
            'career_level_id'          => 3,
            'is_featured'              => false,
            'expire_date'              => $deadline instanceof Carbon ? $deadline : Carbon::parse($deadline),
            'application_closing_date' => $deadline instanceof Carbon ? $deadline : Carbon::parse($deadline),
            'never_expired'            => false,
            'created_at'               => $parent->created_at ?? Carbon::now(),
            'updated_at'               => $parent->created_at ?? Carbon::now(),
        ]);

        // Resolve apply email from content.
        $emails = JobCrawlerRunner::extractAllEmailsFromHtml($rawContent);
        if ($emails) {
            $subject    = rawurlencode(trim(strip_tags((string) $job->name)) . ' Application');
            $job->apply_email = $emails[0];
            $params = ['subject=' . $subject];
            if (count($emails) > 1) {
                $params[] = 'cc=' . implode(',', array_slice($emails, 1));
            }
            $job->apply_url = 'mailto:' . $emails[0] . '?' . implode('&', $params);
        }

        try {
            $job->save();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] === 1062) {
                $this->line("    <fg=gray>skip (unique key conflict)</> {$nameLimit}");
                return false;
            }
            throw $e;
        }

        SlugHelper::createSlug($job);

        $job->jobTypes()->syncWithoutDetaching([3]);

        $this->line("    <fg=green>created</> [{$job->id}] {$nameLimit}");

        return true;
    }
}
