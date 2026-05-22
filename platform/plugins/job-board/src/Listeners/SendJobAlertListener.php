<?php

namespace Botble\JobBoard\Listeners;

use Botble\Base\Facades\EmailHandler;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\JobAlert;
use Botble\JobBoard\Models\JobAlertQuota;
use Botble\JobBoard\Models\SocialAutomation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SendJobAlertListener
{
    public function handle(JobPublishedEvent $event): void
    {
        $job = $event->job;
        $job->loadMissing(['categories', 'skills', 'tags', 'company']);

        // ----------------------------------------------------------------
        // Legacy behavior: match by favoriteTags / favoriteSkills → email
        // ----------------------------------------------------------------
        $tagIds   = $job->tags->pluck('id')->all();
        $skillIds = $job->skills->pluck('id')->all();

        $legacyAccounts = Account::query()
            ->where('type', AccountTypeEnum::JOB_SEEKER)
            ->where(function ($query) use ($tagIds, $skillIds): void {
                $query
                    ->whereHas('favoriteTags', function ($query) use ($tagIds): void {
                        $query->whereIn('jb_account_favorite_tags.tag_id', $tagIds);
                    })
                    ->orWhereHas('favoriteSkills', function ($query) use ($skillIds): void {
                        $query->whereIn('jb_account_favorite_skills.skill_id', $skillIds);
                    });
            })
            ->get();

        foreach ($legacyAccounts as $account) {
            EmailHandler::setModule(JOB_BOARD_MODULE_SCREEN_NAME)
                ->setVariableValues($this->jobAlertEmailVariables($account, $job, false))
                ->sendUsingTemplate('job-seeker-job-alert', $account->email);
        }

        // ----------------------------------------------------------------
        // New alert rules from jb_job_alerts
        // ----------------------------------------------------------------
        $activeAlerts = JobAlert::query()
            ->where('is_active', true)
            ->with('account')
            ->get();

        $jobText       = Str::lower($job->name . ' ' . strip_tags((string) ($job->content ?? '')));
        $jobCategoryIds = $job->categories->pluck('id');

        foreach ($activeAlerts as $alert) {
            if (! $this->jobMatchesAlert($job, $alert, $jobText, $jobCategoryIds)) {
                continue;
            }

            $account = $alert->account;
            if (! $account) {
                continue;
            }

            // Quota check: free tier (3/month) + any paid quota
            if (! $this->accountCanReceiveAlert($account->id)) {
                continue;
            }

            // Deduct from quota
            $this->deductAlertQuota($account->id);

            // Email
            if ($alert->notify_email) {
                try {
                    EmailHandler::setModule(JOB_BOARD_MODULE_SCREEN_NAME)
                        ->setVariableValues($this->jobAlertEmailVariables($account, $job))
                        ->sendUsingTemplate('job-seeker-job-alert', $account->email);
                } catch (Throwable) {
                    // Silently continue
                }
            }

            // Telegram
            if ($alert->notify_telegram && $account->telegram_chat_id) {
                $token = setting('telegram_bot_token');
                if ($token) {
                    try {
                        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                            'chat_id'    => $account->telegram_chat_id,
                            'text'       => "New job: {$job->name}\n" . ($job->url ?? ''),
                            'parse_mode' => 'HTML',
                        ]);
                    } catch (Throwable) {
                        // Silently continue
                    }
                }
            }

            // WhatsApp
            if ($alert->notify_whatsapp && $account->whatsapp_number) {
                try {
                    $automation = SocialAutomation::query()
                        ->where('platform', 'whatsapp')
                        ->where('is_active', true)
                        ->first();

                    if ($automation) {
                        $settings = $automation->settings ?? [];
                        $phoneId  = trim((string) ($settings['phone_number_id'] ?? ''));
                        $token    = trim((string) ($settings['access_token'] ?? ''));

                        if ($phoneId && $token) {
                            Http::timeout(20)
                                ->withToken($token)
                                ->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", [
                                    'messaging_product' => 'whatsapp',
                                    'to'   => $account->whatsapp_number,
                                    'type' => 'text',
                                    'text' => ['body' => "New job: {$job->name}\n" . ($job->url ?? '')],
                                ]);
                        }
                    }
                } catch (Throwable) {
                    // Silently continue
                }
            }
        }
    }

    protected function jobMatchesAlert($job, JobAlert $alert, string $jobText, $jobCategoryIds): bool
    {
        // Keyword match
        if ($alert->keyword !== null && $alert->keyword !== '') {
            if (! Str::contains($jobText, Str::lower($alert->keyword))) {
                return false;
            }
        }

        // Category match (any of the selected categories must match)
        $categoryIds = array_filter((array) ($alert->category_ids ?: ($alert->category_id ? [$alert->category_id] : [])));
        if (! empty($categoryIds)) {
            $matched = collect($categoryIds)->contains(fn ($id) => $jobCategoryIds->contains((int) $id));
            if (! $matched) {
                return false;
            }
        }

        // Country match
        if ($alert->country_id !== null) {
            if ($job->country_id != $alert->country_id) {
                return false;
            }
        }

        // State match
        if ($alert->state_id !== null) {
            if ($job->state_id != $alert->state_id) {
                return false;
            }
        }

        // City match
        if ($alert->city_id !== null) {
            if ($job->city_id != $alert->city_id) {
                return false;
            }
        }

        return true;
    }

    protected function accountCanReceiveAlert(int $accountId): bool
    {
        $period    = JobAlertQuota::currentPeriod();
        $freeLimit = (int) setting('job_alert_free_monthly_limit', 3);

        // Count total sent this month (free + paid rows combined)
        $totalSent = JobAlertQuota::query()
            ->where('account_id', $accountId)
            ->where('period', $period)
            ->sum('alerts_sent');

        // Check for an active paid/unlimited quota row (approved orders only)
        $paidRow = JobAlertQuota::query()
            ->activePaid()
            ->where('account_id', $accountId)
            ->where('period', $period)
            ->where(function ($q): void {
                $q->where('alerts_allowed', -1) // unlimited
                  ->orWhereRaw('alerts_sent < alerts_allowed');
            })
            ->first();

        if ($paidRow) {
            return true;
        }

        // Fall back to free tier
        $freeSent = JobAlertQuota::query()
            ->where('account_id', $accountId)
            ->where('period', $period)
            ->whereNull('package_id')
            ->value('alerts_sent') ?? 0;

        return $freeSent < $freeLimit;
    }

    protected function deductAlertQuota(int $accountId): void
    {
        $period = JobAlertQuota::currentPeriod();

        // Prefer deducting from paid quota first (approved orders only)
        $paidRow = JobAlertQuota::query()
            ->activePaid()
            ->where('account_id', $accountId)
            ->where('period', $period)
            ->where(function ($q): void {
                $q->where('alerts_allowed', -1)
                  ->orWhereRaw('alerts_sent < alerts_allowed');
            })
            ->first();

        if ($paidRow) {
            $paidRow->increment('alerts_sent');
            return;
        }

        // Deduct from free tier row (create if first time this month)
        DB::table('jb_job_alert_quotas')
            ->updateOrInsert(
                ['account_id' => $accountId, 'period' => $period, 'package_id' => null],
                ['alerts_allowed' => (int) setting('job_alert_free_monthly_limit', 3), 'alerts_sent' => DB::raw('COALESCE(alerts_sent, 0) + 1'), 'updated_at' => now(), 'created_at' => now()]
            );
    }

    protected function jobAlertEmailVariables(Account $account, $job, bool $includeQuota = true): array
    {
        return [
            'job_name' => $job->name,
            'job_url' => $job->url,
            'company_name' => ($job->hide_company ?? false) ? $job->company->name : '',
            'account_name' => $account->name,
            'job_alert_source_message' => 'This email was sent from your Wakanda Jobs Job Alerts.',
            'job_alert_quota_message' => $includeQuota ? $this->lowQuotaMessage($account->id) : '',
            'job_alert_packages_url' => route('public.account.job-alert.packages.index'),
        ];
    }

    protected function lowQuotaMessage(int $accountId): string
    {
        $remaining = $this->remainingAlertQuota($accountId);

        if ($remaining === null || $remaining > 2) {
            return '';
        }

        if ($remaining === 0) {
            return 'You have no job alerts remaining for this month. Buy more alerts to keep receiving matching jobs.';
        }

        return "You have {$remaining} job alert" . ($remaining === 1 ? '' : 's') . ' remaining this month. Buy more alerts before you run out.';
    }

    protected function remainingAlertQuota(int $accountId): ?int
    {
        $period = JobAlertQuota::currentPeriod();

        $hasUnlimited = JobAlertQuota::query()
            ->activePaid()
            ->where('account_id', $accountId)
            ->where('period', $period)
            ->where('alerts_allowed', -1)
            ->exists();

        if ($hasUnlimited) {
            return null;
        }

        $paidRemaining = (int) JobAlertQuota::query()
            ->activePaid()
            ->where('account_id', $accountId)
            ->where('period', $period)
            ->where('alerts_allowed', '>', 0)
            ->selectRaw('COALESCE(SUM(GREATEST(alerts_allowed - alerts_sent, 0)), 0) as remaining')
            ->value('remaining');

        $freeLimit = (int) setting('job_alert_free_monthly_limit', 3);
        $freeSent = (int) (JobAlertQuota::query()
            ->where('account_id', $accountId)
            ->where('period', $period)
            ->whereNull('package_id')
            ->value('alerts_sent') ?? 0);

        return $paidRemaining + max($freeLimit - $freeSent, 0);
    }
}
