<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Models\AutoApplyPreference;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\AutoApplyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAutoApplyListener implements ShouldQueue
{
    public string $queue = 'emails';
    public int $tries = 2;

    public function handle(JobPublishedEvent $event): void
    {
        $job = $event->job;

        if ($job->status != JobStatusEnum::PUBLISHED) {
            return;
        }

        // Skip expired jobs
        $deadline = $job->expire_date ?? null;
        if ($deadline && now()->gt($deadline)) {
            return;
        }

        $job->loadMissing(['company', 'categories', 'skills', 'jobTypes', 'slugable']);

        $service = app(AutoApplyService::class);

        // Resolve the effective application email (checks apply_url mailto: too)
        $applyEmail = $service->resolveJobApplyEmail($job);
        $isManualApply = $applyEmail === '';

        // Fetch all active auto-apply preferences where account has a CV
        $preferences = AutoApplyPreference::active()
            ->with('account')
            ->get()
            ->filter(fn (AutoApplyPreference $pref) => $pref->candidateHasCv());

        foreach ($preferences as $preference) {
            try {
                $account = $preference->account;

                // Skip if already applied (manual or auto)
                if ($service->hasAlreadyAppliedForJob($account->id, $job)) {
                    continue;
                }

                // Check quota (only relevant for email applies, but check early to skip work)
                if (! $isManualApply && ! $service->hasQuota($account->id)) {
                    continue;
                }

                // Apply the same filter rules as buildActiveJobsQuery (PHP direction)
                if (! $this->jobMatchesFilters($job, $preference)) {
                    continue;
                }

                // Build candidate profile
                $cvText  = $service->extractCvText($account);
                $profile = $service->buildCandidateProfile($account, $cvText);

                if ($isManualApply) {
                    // Score first to check threshold, then send WhatsApp notice
                    $result = $service->resolvePreviewForJob($account, $job);
                    if (! $result || ! isset($result['score'])) {
                        continue;
                    }
                    $threshold = (int) ($preference->match_score_threshold ?? AutoApplyOrder::globalMatchThreshold());
                    if ($result['score'] < $threshold) {
                        continue;
                    }
                    $service->sendManualApplyPackage($account, $job, (int) $result['score'], $result);
                } else {
                    // Full auto-apply: AI email generation + send
                    $service->processAutoApply($account, $job, $profile);
                }
            } catch (Throwable $e) {
                Log::error('AutoApply: Error processing on job publish', [
                    'account_id' => $preference->account_id,
                    'job_id'     => $job->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Mirrors buildActiveJobsQuery PHP-side so the same rules apply automatically
     * when a job is published as when an admin uses the Active Jobs modal.
     */
    private function jobMatchesFilters(Job $job, AutoApplyPreference $preference): bool
    {
        // Keywords — OR logic across title, description, address
        $keywords = array_filter(array_map('trim', (array) ($preference->keywords ?? [])));
        if ($keywords) {
            $matched = false;
            foreach ($keywords as $kw) {
                $pat = '/' . $this->keywordRegexPattern($kw) . '/iu';
                if (preg_match($pat, $job->name)
                    || preg_match($pat, (string) ($job->description ?? ''))
                    || preg_match($pat, (string) ($job->address ?? ''))) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                return false;
            }
        }

        // Country IDs
        if (! empty($preference->country_ids)) {
            $ids = array_filter(array_map('intval', (array) $preference->country_ids));
            if ($ids && ! in_array((int) $job->country_id, $ids)) {
                return false;
            }
        }

        // Category IDs — job must belong to at least one preferred category
        if (! empty($preference->category_ids)) {
            $ids    = array_filter(array_map('intval', (array) $preference->category_ids));
            $catIds = $job->categories->pluck('id')->map(fn ($id) => (int) $id)->all();
            if ($ids && empty(array_intersect($ids, $catIds))) {
                return false;
            }
        }

        // Location keyword — substring match on address
        if (! $preference->matchesLocation($job->address)) {
            return false;
        }

        // Experience level
        if (! empty($preference->job_experience_id)) {
            if ((int) $job->job_experience_id !== (int) $preference->job_experience_id) {
                return false;
            }
        }

        // Company whitelist / blacklist
        if (! $this->companyMatchesFilters($job, $preference)) {
            return false;
        }

        return true;
    }

    private function companyMatchesFilters(Job $job, AutoApplyPreference $preference): bool
    {
        $companyId           = (int) $job->company_id;
        $companyName         = mb_strtolower(trim((string) $job->company?->name));
        $whitelistedIds      = array_values(array_filter(array_map('intval', (array) ($preference->whitelisted_company_ids ?? []))));
        $blacklistedIds      = array_values(array_filter(array_map('intval', (array) ($preference->blacklisted_company_ids ?? []))));
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

    /**
     * Same plural-tolerant word-boundary pattern used by buildActiveJobsQuery's REGEXP.
     */
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
