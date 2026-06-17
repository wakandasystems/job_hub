<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Models\AutoApplyPreference;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\AutoApplyService;
use Illuminate\Contracts\Queue\ShouldQueue;
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

        // Must have an apply_email to auto-apply via email
        $applyEmail = trim((string) $job->apply_email);
        if ($applyEmail === '') {
            return;
        }

        // Skip expired jobs
        $deadline = $job->expire_date ?? null;
        if ($deadline && now()->gt($deadline)) {
            return;
        }

        $job->loadMissing(['company', 'categories', 'skills', 'jobTypes', 'slugable']);

        $service = app(AutoApplyService::class);

        // Fetch all active auto-apply preferences where account has a CV
        $preferences = AutoApplyPreference::active()
            ->with('account')
            ->get()
            ->filter(fn (AutoApplyPreference $pref) => $pref->candidateHasCv());

        foreach ($preferences as $preference) {
            try {
                $account = $preference->account;

                // Skip if blacklisted company
                $blacklisted = $preference->blacklisted_company_ids ?? [];
                if ($job->company_id && in_array($job->company_id, $blacklisted)) {
                    continue;
                }

                // Skip if already applied (manual or auto)
                if ($service->hasAlreadyApplied($account->id, $job->id)) {
                    continue;
                }

                // Check quota
                if (! $service->hasQuota($account->id)) {
                    continue;
                }

                // Check filter match
                if (! $this->jobMatchesFilters($job, $preference)) {
                    continue;
                }

                // Build candidate profile once
                $cvText = $service->extractCvText($account);
                $profile = $service->buildCandidateProfile($account, $cvText);

                // Process auto-apply (AI + send + log)
                $service->processAutoApply($account, $job, $profile);
            } catch (Throwable $e) {
                \Illuminate\Support\Facades\Log::error('AutoApply: Error processing', [
                    'account_id' => $preference->account_id,
                    'job_id'     => $job->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    private function jobMatchesFilters(Job $job, AutoApplyPreference $preference): bool
    {
        // Keywords — OR logic across title, description
        $keywords = array_filter(array_map('trim', (array) ($preference->keywords ?? [])));
        if ($keywords) {
            $matched = false;
            foreach ($keywords as $kw) {
                $kwPat = '/\b' . preg_quote($kw, '/') . '\b/iu';
                if (preg_match($kwPat, $job->name)
                    || preg_match($kwPat, (string) ($job->description ?? ''))
                    || preg_match($kwPat, (string) ($job->address ?? ''))) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                return false;
            }
        }

        // Categories
        if (! empty($preference->category_ids)) {
            $ids = array_filter(array_map('intval', (array) $preference->category_ids));
            $catIds = $job->categories->pluck('id')->toArray();
            if ($ids && ! empty($catIds) && empty(array_intersect($ids, $catIds))) {
                return false;
            }
        }

        // Countries
        if (! empty($preference->country_ids)) {
            $ids = array_filter(array_map('intval', (array) $preference->country_ids));
            if ($ids && ! in_array((int) $job->country_id, $ids)) {
                return false;
            }
        }

        // Location keyword
        if ($preference->location_keyword) {
            $loc = trim($preference->location_keyword);
            if ($loc !== '' && stripos((string) ($job->address ?? ''), $loc) === false) {
                return false;
            }
        }

        // Experience
        if ($preference->job_experience_id) {
            if ((int) $job->job_experience_id !== (int) $preference->job_experience_id) {
                return false;
            }
        }

        return true;
    }
}
