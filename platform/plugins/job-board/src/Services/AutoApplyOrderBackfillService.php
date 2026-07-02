<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Models\AutoApplyPreference;
use Botble\JobBoard\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutoApplyOrderBackfillService
{
    public function __construct(
        private readonly AutoApplyService $autoApplyService,
    ) {
    }

    public function backfillOrder(AutoApplyOrder $order): array
    {
        $lockKey = 'job_board:auto_apply_backfill_order:' . $order->id;
        $lock = Cache::lock($lockKey, 900);

        if (! $lock->get()) {
            return [
                'order_id' => $order->id,
                'locked' => true,
                'matched_total' => 0,
                'processed' => 0,
                'emails_sent' => 0,
                'manual_notified' => 0,
                'already_processed' => 0,
                'below_threshold' => 0,
                'skipped_quota' => 0,
                'failed' => 0,
                'missing_cv' => false,
                'missing_account' => false,
            ];
        }

        try {
            return $this->runBackfill($order->fresh(['account']));
        } finally {
            $lock->release();
        }
    }

    private function runBackfill(?AutoApplyOrder $order): array
    {
        $summary = [
            'order_id' => $order?->id,
            'locked' => false,
            'matched_total' => 0,
            'processed' => 0,
            'emails_sent' => 0,
            'manual_notified' => 0,
            'already_processed' => 0,
            'below_threshold' => 0,
            'skipped_quota' => 0,
            'failed' => 0,
            'missing_cv' => false,
            'missing_account' => false,
        ];

        if (! $order?->account) {
            $summary['missing_account'] = true;

            return $summary;
        }

        $account = $order->account;

        if (trim((string) $account->resume) === '') {
            $summary['missing_cv'] = true;

            return $summary;
        }

        $preference = AutoApplyPreference::query()->where('account_id', $order->account_id)->first();

        if (! $preference?->is_active) {
            return $summary;
        }

        $threshold = (int) (($preference->match_score_threshold) ?? AutoApplyOrder::globalMatchThreshold());
        $jobs = $this->buildActiveJobsQuery($order)
            ->with(['company', 'categories', 'skills', 'jobTypes', 'slugable'])
            ->get();

        $summary['matched_total'] = $jobs->count();

        if ($jobs->isEmpty()) {
            return $summary;
        }

        foreach ($jobs as $job) {
            $alreadyProcessed = $this->autoApplyService->hasAlreadyAppliedForJob($account->id, $job);
            $result = $this->autoApplyService->queueAutoApplyJob($account, $job, $threshold, $alreadyProcessed);

            if (($result['status'] ?? null) === 'already_processed') {
                $summary['already_processed']++;
                continue;
            }

            if (($result['status'] ?? null) === 'queued') {
                $summary['emails_sent']++;
                $summary['processed']++;
                continue;
            }

            if (($result['status'] ?? null) === 'manual_notified') {
                $summary['manual_notified']++;
                $summary['processed']++;
                continue;
            }

            if (($result['status'] ?? null) === 'below_threshold') {
                $summary['below_threshold']++;
                continue;
            }

            if (($result['status'] ?? null) === 'scoring_failed') {
                $summary['failed']++;
                continue;
            }

            if (($result['status'] ?? null) === 'manual_notify_failed') {
                $summary['failed']++;
                continue;
            }

            $summary['failed']++;
        }

        return $summary;
    }

    private function buildActiveJobsQuery(AutoApplyOrder $autoApplyOrder): Builder
    {
        $query = Job::query()
            ->active()
            ->notClosed()
            ->where('status', JobStatusEnum::PUBLISHED)
            ->orderByRaw(
                '(select count(*) from jb_auto_apply_logs where jb_auto_apply_logs.job_id = jb_jobs.id and jb_auto_apply_logs.account_id = ?) asc',
                [$autoApplyOrder->account_id]
            )
            ->latest();

        $preference = AutoApplyPreference::query()->where('account_id', $autoApplyOrder->account_id)->first();
        $countryIds = $preference?->country_ids ?? [];

        if (! empty($countryIds)) {
            $query->whereIn('country_id', $countryIds);
        }

        $preferenceKeywords = array_values(array_filter(array_map('trim', (array) ($preference?->keywords ?? []))));

        if ($preferenceKeywords) {
            $query->where(function ($q) use ($preferenceKeywords): void {
                foreach ($preferenceKeywords as $kw) {
                    $pattern = $this->keywordRegexPattern($kw);
                    $q->orWhereRaw('LOWER(name) REGEXP ?', [$pattern])
                        ->orWhereRaw('LOWER(description) REGEXP ?', [$pattern])
                        ->orWhereRaw('LOWER(address) REGEXP ?', [$pattern]);
                }
            });
        }

        $this->applyCompanyFiltersToQuery(
            $query,
            array_values(array_filter(array_map('intval', (array) ($preference?->whitelisted_company_ids ?? [])))),
            $this->sanitizeKeywordList($preference?->whitelisted_company_keywords ?? []),
            array_values(array_filter(array_map('intval', (array) ($preference?->blacklisted_company_ids ?? [])))),
            $this->sanitizeKeywordList($preference?->blacklisted_company_keywords ?? [])
        );

        $categoryIds = array_values(array_filter(array_map('intval', (array) ($preference?->category_ids ?? []))));

        if ($categoryIds) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('jb_categories.id', $categoryIds));
        }

        $locationKeywords = $preference?->locationKeywords() ?? [];

        if ($locationKeywords) {
            $query->where(function ($locationQuery) use ($locationKeywords): void {
                foreach ($locationKeywords as $locationKeyword) {
                    $locationQuery->orWhere('address', 'LIKE', '%' . $locationKeyword . '%');
                }
            });
        }

        if (! empty($preference?->job_experience_id)) {
            $query->where('job_experience_id', (int) $preference->job_experience_id);
        }

        return $query;
    }

    private function applyCompanyFiltersToQuery(
        Builder $query,
        array $whitelistedCompanyIds = [],
        array $whitelistedCompanyKeywords = [],
        array $blacklistedCompanyIds = [],
        array $blacklistedCompanyKeywords = []
    ): void {
        $whitelistedCompanyIds = array_values(array_filter(array_map('intval', $whitelistedCompanyIds)));
        $whitelistedCompanyKeywords = $this->sanitizeKeywordList($whitelistedCompanyKeywords);
        $blacklistedCompanyIds = array_values(array_filter(array_map('intval', $blacklistedCompanyIds)));
        $blacklistedCompanyKeywords = $this->sanitizeKeywordList($blacklistedCompanyKeywords);

        if ($whitelistedCompanyIds || $whitelistedCompanyKeywords) {
            $query->where(function (Builder $companyQuery) use ($whitelistedCompanyIds, $whitelistedCompanyKeywords): void {
                if ($whitelistedCompanyIds) {
                    $companyQuery->whereIn('company_id', $whitelistedCompanyIds);
                }

                if ($whitelistedCompanyKeywords) {
                    $companyQuery->orWhereHas('company', function (Builder $companyRelation) use ($whitelistedCompanyKeywords): void {
                        $companyRelation->where(function (Builder $keywordQuery) use ($whitelistedCompanyKeywords): void {
                            foreach ($whitelistedCompanyKeywords as $keyword) {
                                $keywordQuery->orWhere('name', 'LIKE', '%' . $keyword . '%');
                            }
                        });
                    });
                }
            });
        }

        if ($blacklistedCompanyIds) {
            $query->where(function (Builder $companyQuery) use ($blacklistedCompanyIds): void {
                $companyQuery->whereNull('company_id')
                    ->orWhereNotIn('company_id', $blacklistedCompanyIds);
            });
        }

        if ($blacklistedCompanyKeywords) {
            $query->whereDoesntHave('company', function (Builder $companyQuery) use ($blacklistedCompanyKeywords): void {
                $companyQuery->where(function (Builder $keywordQuery) use ($blacklistedCompanyKeywords): void {
                    foreach ($blacklistedCompanyKeywords as $keyword) {
                        $keywordQuery->orWhere('name', 'LIKE', '%' . $keyword . '%');
                    }
                });
            });
        }
    }

    private function sanitizeKeywordList(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($value) => trim(mb_strtolower((string) $value)),
            $values
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
