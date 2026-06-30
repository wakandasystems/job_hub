<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Models\AutoApplyPreference;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\AutoApplyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconcileMissedAutoApplyCommand extends Command
{
    public const STATUS_CACHE_KEY = 'job_board:auto_apply_reconcile_status';

    protected $signature = 'job-board:reconcile-missed-auto-apply
        {--hours=12 : Look back this many hours for recently posted jobs}
        {--lag-minutes=5 : Ignore very fresh jobs to avoid racing the publish listener}
        {--job-limit=150 : Max recent jobs to inspect per run}
        {--match-limit=20 : Max missed matches to process per run}
        {--dry-run : Report missed matches without sending}';

    protected $description = 'Catch missed auto-apply matches for recently posted jobs without reprocessing the whole backlog';

    public function handle(AutoApplyService $service): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $lagMinutes = max(1, (int) $this->option('lag-minutes'));
        $jobLimit = max(1, (int) $this->option('job-limit'));
        $matchLimit = max(1, (int) $this->option('match-limit'));
        $dryRun = (bool) $this->option('dry-run');

        $jobs = Job::query()
            ->active()
            ->notClosed()
            ->where('status', JobStatusEnum::PUBLISHED)
            ->where('created_at', '>=', now()->subHours($hours))
            ->where('created_at', '<=', now()->subMinutes($lagMinutes))
            ->with(['company', 'categories', 'skills', 'jobTypes', 'slugable'])
            ->latest('created_at')
            ->limit($jobLimit)
            ->get();

        $preferences = AutoApplyPreference::active()
            ->with('account')
            ->get()
            ->filter(fn (AutoApplyPreference $preference) => $preference->candidateHasCv());

        $stats = [
            'jobs_scanned' => $jobs->count(),
            'preferences_scanned' => $preferences->count(),
            'missed_matches_found' => 0,
            'processed' => 0,
            'manual_notified' => 0,
            'emails_sent' => 0,
            'skipped_existing' => 0,
            'skipped_quota' => 0,
            'skipped_threshold' => 0,
            'errors' => 0,
        ];

        $profileCache = [];

        foreach ($jobs as $job) {
            foreach ($preferences as $preference) {
                if ($stats['processed'] >= $matchLimit) {
                    break 2;
                }

                $account = $preference->account;

                if (! $account) {
                    continue;
                }

                try {
                    if ($service->hasAlreadyApplied($account->id, $job->id)) {
                        $stats['skipped_existing']++;
                        continue;
                    }

                    if (! $this->jobMatchesFilters($job, $preference)) {
                        continue;
                    }

                    $stats['missed_matches_found']++;
                    $applyEmail = $service->resolveJobApplyEmail($job);
                    $isManualApply = $applyEmail === '';

                    if (! $isManualApply && ! $service->hasQuota($account->id)) {
                        $stats['skipped_quota']++;
                        continue;
                    }

                    if ($dryRun) {
                        $this->line(sprintf(
                            '[DRY RUN] account=%d job=%d manual=%s %s',
                            $account->id,
                            $job->id,
                            $isManualApply ? 'yes' : 'no',
                            $job->name
                        ));
                        $stats['processed']++;
                        continue;
                    }

                    if ($isManualApply) {
                        $preview = $service->resolvePreviewForJob($account, $job);

                        if (! $preview || ! isset($preview['score'])) {
                            $stats['errors']++;
                            continue;
                        }

                        $threshold = (int) ($preference->match_score_threshold ?? AutoApplyOrder::globalMatchThreshold());

                        if ((int) $preview['score'] < $threshold) {
                            $stats['skipped_threshold']++;
                            continue;
                        }

                        $result = $service->sendManualApplyPackage($account, $job, (int) $preview['score'], $preview);

                        if (($result['status'] ?? null) === 'manual_notified') {
                            $stats['manual_notified']++;
                            $stats['processed']++;
                        } else {
                            $stats['errors']++;
                        }

                        continue;
                    }

                    $profile = $profileCache[$account->id] ?? null;

                    if (! $profile) {
                        $cvText = $service->extractCvText($account);
                        $profile = $service->buildCandidateProfile($account, $cvText);
                        $profileCache[$account->id] = $profile;
                    }

                    $log = $service->processAutoApply($account, $job, $profile);

                    if (! $log) {
                        $stats['errors']++;
                        continue;
                    }

                    if ($log->status === 'sent') {
                        $stats['emails_sent']++;
                    } elseif ($log->status === 'skipped_low_score') {
                        $stats['skipped_threshold']++;
                    } elseif ($log->status === 'failed') {
                        $stats['errors']++;
                    }

                    $stats['processed']++;
                } catch (Throwable $e) {
                    $stats['errors']++;

                    Log::error('AutoApply reconcile failed', [
                        'job_id' => $job->id,
                        'account_id' => $account->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info(sprintf(
            'Scanned %d recent jobs across %d active preferences. Found %d missed matches, processed %d. Emails sent: %d. Manual notices: %d. Existing skipped: %d. Quota skipped: %d. Threshold skipped: %d. Errors: %d.',
            $stats['jobs_scanned'],
            $stats['preferences_scanned'],
            $stats['missed_matches_found'],
            $stats['processed'],
            $stats['emails_sent'],
            $stats['manual_notified'],
            $stats['skipped_existing'],
            $stats['skipped_quota'],
            $stats['skipped_threshold'],
            $stats['errors']
        ));

        if (! $dryRun) {
            Cache::forever(self::STATUS_CACHE_KEY, [
                'ran_at' => now()->toIso8601String(),
                'hours' => $hours,
                'lag_minutes' => $lagMinutes,
                'job_limit' => $jobLimit,
                'match_limit' => $matchLimit,
                'dry_run' => false,
                'stats' => $stats,
            ]);
        }

        return self::SUCCESS;
    }

    private function jobMatchesFilters(Job $job, AutoApplyPreference $preference): bool
    {
        $keywords = array_filter(array_map('trim', (array) ($preference->keywords ?? [])));

        if ($keywords) {
            $matched = false;

            foreach ($keywords as $keyword) {
                $pattern = '/' . $this->keywordRegexPattern($keyword) . '/iu';

                if (
                    preg_match($pattern, $job->name)
                    || preg_match($pattern, (string) ($job->description ?? ''))
                    || preg_match($pattern, (string) ($job->address ?? ''))
                ) {
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                return false;
            }
        }

        if (! empty($preference->country_ids)) {
            $countryIds = array_filter(array_map('intval', (array) $preference->country_ids));

            if ($countryIds && ! in_array((int) $job->country_id, $countryIds, true)) {
                return false;
            }
        }

        if (! empty($preference->category_ids)) {
            $categoryIds = array_filter(array_map('intval', (array) $preference->category_ids));
            $jobCategoryIds = $job->categories->pluck('id')->map(fn ($id) => (int) $id)->all();

            if ($categoryIds && empty(array_intersect($categoryIds, $jobCategoryIds))) {
                return false;
            }
        }

        if (! $preference->matchesLocation($job->address)) {
            return false;
        }

        if (! empty($preference->job_experience_id) && (int) $job->job_experience_id !== (int) $preference->job_experience_id) {
            return false;
        }

        return $this->companyMatchesFilters($job, $preference);
    }

    private function companyMatchesFilters(Job $job, AutoApplyPreference $preference): bool
    {
        $companyId = (int) $job->company_id;
        $companyName = mb_strtolower(trim((string) $job->company?->name));
        $whitelistedIds = array_values(array_filter(array_map('intval', (array) ($preference->whitelisted_company_ids ?? []))));
        $blacklistedIds = array_values(array_filter(array_map('intval', (array) ($preference->blacklisted_company_ids ?? []))));
        $whitelistedKeywords = $this->normalizeKeywords((array) ($preference->whitelisted_company_keywords ?? []));
        $blacklistedKeywords = $this->normalizeKeywords((array) ($preference->blacklisted_company_keywords ?? []));

        $isWhitelisted = ! $whitelistedIds && ! $whitelistedKeywords;

        if (! $isWhitelisted) {
            if ($companyId > 0 && in_array($companyId, $whitelistedIds, true)) {
                $isWhitelisted = true;
            } elseif ($companyName !== '' && $this->matchesAnyCompanyKeyword($companyName, $whitelistedKeywords)) {
                $isWhitelisted = true;
            }
        }

        if (! $isWhitelisted) {
            return false;
        }

        if ($companyId > 0 && in_array($companyId, $blacklistedIds, true)) {
            return false;
        }

        if ($companyName !== '' && $this->matchesAnyCompanyKeyword($companyName, $blacklistedKeywords)) {
            return false;
        }

        return true;
    }

    private function matchesAnyCompanyKeyword(string $companyName, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($companyName, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeKeywords(array $keywords): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($keyword) => mb_strtolower(trim((string) $keyword)),
            $keywords
        ))));
    }

    private function keywordRegexPattern(string $keyword): string
    {
        $keyword = mb_strtolower(trim($keyword));
        $pattern = preg_quote($keyword, '/');

        if (preg_match('/[a-z]$/i', $keyword)) {
            $pattern .= 's?';
        }

        return '\\b' . $pattern . '\\b';
    }
}
