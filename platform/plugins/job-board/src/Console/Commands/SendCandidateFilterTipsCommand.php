<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\CandidateAlert;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\CandidateAlertFilterTipService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * One-time "you can edit your filters" engagement message, sent ~2 days after a
 * VIP candidate alert signup. Runs frequently; each alert is only ever scheduled
 * and sent once (tracked via filter_tip_scheduled_at / filter_tip_sent_at).
 */
class SendCandidateFilterTipsCommand extends Command
{
    protected $signature = 'job-board:send-candidate-filter-tips';

    protected $description = 'Send the one-time "edit your alert filters" tip to VIP candidates ~2 days after signup';

    private const ACTIVE_HOUR_START = 8;

    private const ACTIVE_HOUR_END = 20;

    public function handle(CandidateAlertFilterTipService $tipService): int
    {
        $this->scheduleNewlyEligible();
        $this->sendDue($tipService);

        return self::SUCCESS;
    }

    /** Alerts that just crossed the 2-day mark get a randomised send time picked for them. */
    private function scheduleNewlyEligible(): void
    {
        $alerts = CandidateAlert::active()
            ->whereNull('filter_tip_sent_at')
            ->whereNull('filter_tip_scheduled_at')
            ->where('created_at', '<=', now()->subDays(2))
            ->get();

        foreach ($alerts as $alert) {
            $alert->update(['filter_tip_scheduled_at' => $this->randomSendTime()]);
            $this->line("Scheduled filter tip for alert #{$alert->id} at {$alert->filter_tip_scheduled_at}.");
        }
    }

    private function randomSendTime(): Carbon
    {
        $now = now();
        $todayWindowEnd = $now->copy()->startOfDay()->addHours(self::ACTIVE_HOUR_END);

        // Still time left in today's active window — pick a moment later today.
        if ($now->copy()->addMinutes(30)->lessThan($todayWindowEnd)) {
            return $now->copy()->addMinutes(random_int(30, max(31, $now->diffInMinutes($todayWindowEnd))));
        }

        // Otherwise pick a random moment inside tomorrow's active window.
        $tomorrowStart = $now->copy()->addDay()->startOfDay()->addHours(self::ACTIVE_HOUR_START);
        $windowMinutes = (self::ACTIVE_HOUR_END - self::ACTIVE_HOUR_START) * 60;

        return $tomorrowStart->copy()->addMinutes(random_int(0, $windowMinutes - 1));
    }

    private function sendDue(CandidateAlertFilterTipService $tipService): void
    {
        $due = CandidateAlert::active()
            ->whereNull('filter_tip_sent_at')
            ->whereNotNull('filter_tip_scheduled_at')
            ->where('filter_tip_scheduled_at', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            $this->line('No candidate filter tips due.');

            return;
        }

        [$token, $gatewayUrl] = $this->getWhapiCredentials();
        if (! $token) {
            $this->warn('No active Whapi automation found. Skipping filter tips.');

            return;
        }

        foreach ($due as $alert) {
            $message = $tipService->buildMessage($alert);

            $sentToAny = false;
            foreach ($alert->recipientJids() as $jid) {
                try {
                    $response = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                        'to'   => $jid,
                        'body' => $message,
                    ]);

                    if ($response->successful()) {
                        $sentToAny = true;
                    }
                } catch (Throwable) {
                    // try next number
                }
            }

            $alert->update(['filter_tip_sent_at' => now()]);
            $this->line("Filter tip for alert #{$alert->id}: " . ($sentToAny ? 'sent' : 'failed to send'));

            sleep(random_int(5, 15));
        }
    }

    private function getWhapiCredentials(): array
    {
        $automation = SocialAutomation::where('platform', 'whapi')->where('is_active', true)->first();
        if (! $automation) {
            return [null, null];
        }

        $settings   = $automation->settings ?? [];
        $token      = SocialAutomation::whapiToken($automation);
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        return $token ? [$token, $gatewayUrl] : [null, null];
    }
}
