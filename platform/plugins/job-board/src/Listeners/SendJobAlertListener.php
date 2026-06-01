<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\JobAlert;
use Botble\JobBoard\Models\JobAlertNotification;
use Botble\JobBoard\Models\JobAlertQuota;
use Botble\JobBoard\Models\PushSubscription;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\JobImageGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class SendJobAlertListener implements ShouldQueue
{
    public string $queue = 'emails';
    public int $tries = 2;
    public function handle(JobPublishedEvent $event): void
    {
        $job = $event->job;
        $job->loadMissing(['categories', 'skills', 'tags', 'company', 'country']);

        // ----------------------------------------------------------------
        // Legacy behavior: match by favoriteTags / favoriteSkills -> email
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
            JobAlertNotification::firstOrCreate([
                'account_id'   => $account->id,
                'job_id'       => $job->getKey(),
                'job_alert_id' => null,
            ]);
            $this->sendAccountPush($account->id, $job);
        }

        // ----------------------------------------------------------------
        // New alert rules from jb_job_alerts
        // ----------------------------------------------------------------
        $activeAlerts = JobAlert::query()
            ->where('is_active', true)
            ->with('account')
            ->get();

        $jobText       = Str::lower($job->name);
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

            // Flag in DB (replaces email)
            JobAlertNotification::firstOrCreate([
                'account_id'   => $account->id,
                'job_id'       => $job->getKey(),
                'job_alert_id' => $alert->getKey(),
            ]);

            // Push notification to this specific account's subscriptions
            $this->sendAccountPush($account->id, $job);

            // Telegram
            if ($alert->notify_telegram && $account->telegram_chat_id) {
                $token = setting('telegram_bot_token');
                if ($token) {
                    try {
                        $this->sendTelegramJobAlert($token, (string) $account->telegram_chat_id, $job);
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

    protected function sendTelegramJobAlert(string $token, string $chatId, $job): void
    {
        $text = "New job: {$job->name}\n" . ($job->url ?? '');
        $imagePath = null;

        try {
            $imagePath = app(JobImageGeneratorService::class)->generate($job);
        } catch (Throwable) {
            $imagePath = null;
        }

        if ($imagePath && file_exists($imagePath)) {
            try {
                $response = Http::timeout(30)
                    ->attach('photo', file_get_contents($imagePath), 'job-alert.jpg')
                    ->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                        'chat_id' => $chatId,
                        'caption' => Str::limit($text, 1020, '...'),
                    ]);

                if ($response->successful() && data_get($response->json(), 'ok')) {
                    return;
                }
            } finally {
                @unlink($imagePath);
            }
        }

        Http::timeout(20)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => $text,
        ]);
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

    protected function sendAccountPush(int $accountId, $job): void
    {
        $subscriptions = PushSubscription::query()
            ->where('account_id', $accountId)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject'    => config('services.vapid.subject'),
                    'publicKey'  => config('services.vapid.public_key'),
                    'privateKey' => config('services.vapid.private_key'),
                ],
            ]);

            $payload = json_encode([
                'title' => $job->name,
                'body'  => $job->company?->name
                    ? 'New job at ' . $job->company->name
                    : 'A new matching job was posted!',
                'url'   => '/jobs/' . ($job->slugable?->key ?? $job->getKey()),
                'icon'  => '/push-icon.png',
                'badge' => '/push-icon.png',
            ]);

            $staleEndpoints = [];

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'keys'     => ['p256dh' => $sub->p256dh, 'auth' => $sub->auth],
                    ]),
                    $payload
                );
            }

            foreach ($webPush->flush() as $report) {
                if (! $report->isSuccess()) {
                    $statusCode = $report->getResponse()?->getStatusCode();
                    if (in_array($statusCode, [404, 410])) {
                        $staleEndpoints[] = $report->getEndpoint();
                    }
                }
            }

            if (! empty($staleEndpoints)) {
                PushSubscription::whereIn('endpoint', $staleEndpoints)->delete();
            }
        } catch (Throwable) {
            // Best-effort
        }
    }
}
