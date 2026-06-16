<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\CandidateAlert;
use Botble\JobBoard\Models\SocialAutomation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class CheckCandidateAlertExpiryCommand extends Command
{
    protected $signature = 'job-board:check-candidate-alert-expiry';
    protected $description = 'Send expiry warnings and disable expired candidate VIP job alerts';

    private const ADMIN_PHONE     = '260970766123';
    private const ADMIN_ALERT_URL = 'https://www.wakandajobs.com/admin/job-board/candidate-alerts';

    public function handle(): int
    {
        $this->info('Checking candidate alert expiry...');

        [$token, $gatewayUrl] = $this->getWhapiCredentials();

        // 1. 2-day warning
        $twoDay = CandidateAlert::where('is_active', true)
            ->where('status', 'active')
            ->where('expiry_warning_sent', false)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', now()->addDays(2)->toDateString())
            ->get();

        foreach ($twoDay as $alert) {
            $this->line("  2-day warning → {$alert->candidate_name}");
            if ($token) {
                $this->sendTwoDayWarning($token, $gatewayUrl, $alert);
            }
            $alert->update(['expiry_warning_sent' => true]);
        }

        // 2. Same-day reminder (expires today, not yet marked expired)
        $sameDay = CandidateAlert::where('is_active', true)
            ->where('status', 'active')
            ->where('expiry_sameday_sent', false)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', now()->toDateString())
            ->get();

        foreach ($sameDay as $alert) {
            $this->line("  Same-day reminder → {$alert->candidate_name}");
            if ($token) {
                $this->sendSameDayReminder($token, $gatewayUrl, $alert);
            }
            $alert->update(['expiry_sameday_sent' => true]);
        }

        // 3. Mark expired + candidate notice + admin notification
        $expired = CandidateAlert::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $alert) {
            $this->line("  Expired → {$alert->candidate_name}");
            $alert->update(['status' => 'expired', 'is_active' => false]);

            if ($token && ! $alert->expiry_notice_sent) {
                $this->sendExpiryNotice($token, $gatewayUrl, $alert);
                $this->sendAdminExpiredNotice($token, $gatewayUrl, $alert);
                $alert->update(['expiry_notice_sent' => true]);
            }
        }

        $this->info("Done. 2-day warnings: {$twoDay->count()}, Same-day: {$sameDay->count()}, Expired: {$expired->count()}.");

        return self::SUCCESS;
    }

    private function sendTwoDayWarning(string $token, string $gatewayUrl, CandidateAlert $alert): void
    {
        $expiry = $alert->expires_at?->format('d M Y');
        $msg    = "⚠️ *Job Alert Expiring in 2 Days!*\n\n";
        $msg   .= "Hi {$alert->candidate_name}! 👋\n\n";
        $msg   .= "Your VIP Job Alert *\"{$alert->label}\"* expires in *2 days* on *{$expiry}*.\n\n";
        $msg   .= "🔄 *Renew now to keep receiving personalised job alerts:*\n\n";
        $msg   .= "• 7 Days  — K20\n";
        $msg   .= "• 14 Days — K30\n";
        $msg   .= "• 30 Days — K50\n\n";
        $msg   .= "Reply *RENEW* or contact us today to stay ahead of new opportunities! 🚀\n\n";
        $msg   .= "_Wakanda Jobs — wakandajobs.com_";

        $this->dispatchWhatsAppToAlert($token, $gatewayUrl, $alert, $msg);
    }

    private function sendSameDayReminder(string $token, string $gatewayUrl, CandidateAlert $alert): void
    {
        $expiry = $alert->expires_at?->format('d M Y');
        $msg    = "🔔 *Your Job Alert Expires Today!*\n\n";
        $msg   .= "Hi {$alert->candidate_name}! 👋\n\n";
        $msg   .= "This is a final reminder — your VIP Job Alert *\"{$alert->label}\"* expires *today ({$expiry})*.\n\n";
        $msg   .= "After today you will stop receiving automatic job matches.\n\n";
        $msg   .= "🔄 *Renew right now to avoid any gap:*\n\n";
        $msg   .= "• 7 Days  — K20\n";
        $msg   .= "• 14 Days — K30\n";
        $msg   .= "• 30 Days — K50\n\n";
        $msg   .= "Reply *RENEW* and we'll sort you out instantly! ✅\n\n";
        $msg   .= "_Wakanda Jobs — wakandajobs.com_";

        $this->dispatchWhatsAppToAlert($token, $gatewayUrl, $alert, $msg);
    }

    private function sendExpiryNotice(string $token, string $gatewayUrl, CandidateAlert $alert): void
    {
        $msg  = "🔴 *Your Job Alert Subscription Has Expired*\n\n";
        $msg .= "Hi {$alert->candidate_name}! 👋\n\n";
        $msg .= "Your VIP Job Alert *\"{$alert->label}\"* has now expired.\n\n";
        $msg .= "You will no longer receive automatic job alerts.\n\n";
        $msg .= "🔄 *Renew your subscription to stay on top of opportunities:*\n\n";
        $msg .= "• 7 Days  — K20\n";
        $msg .= "• 14 Days — K30\n";
        $msg .= "• 30 Days — K50\n\n";
        $msg .= "Reply *RENEW* or visit *wakandajobs.com* to get back on track! 🚀\n\n";
        $msg .= "_Wakanda Jobs — wakandajobs.com_";

        $this->dispatchWhatsAppToAlert($token, $gatewayUrl, $alert, $msg);
    }

    private function sendAdminExpiredNotice(string $token, string $gatewayUrl, CandidateAlert $alert): void
    {
        $adminJid    = self::ADMIN_PHONE . '@s.whatsapp.net';
        $phone       = preg_replace('/\D/', '', $alert->candidate_phone);
        $waLink      = "https://wa.me/{$phone}";
        $expiredOn   = $alert->expires_at?->format('d M Y');
        $duration    = $alert->duration_days ? "{$alert->duration_days}-day" : 'VIP';

        $msg  = "🔴 *VIP Alert Expired — Action Needed*\n\n";
        $msg .= "A candidate's job alert subscription has just expired.\n\n";
        $msg .= "👤 *Name:* {$alert->candidate_name}\n";
        $msg .= "📱 *Phone:* {$alert->candidate_phone}\n";

        if ($alert->candidate_email) {
            $msg .= "📧 *Email:* {$alert->candidate_email}\n";
        }

        $msg .= "📋 *Alert:* {$alert->label}\n";
        $msg .= "📦 *Package:* {$duration} plan\n";
        $msg .= "📅 *Expired:* {$expiredOn}\n\n";
        $msg .= "💬 *Chat with candidate:* {$waLink}\n\n";
        $msg .= "🔗 *Manage alerts:* " . self::ADMIN_ALERT_URL;

        $this->dispatchWhatsApp($token, $gatewayUrl, $adminJid, $msg);
    }

    private function dispatchWhatsApp(string $token, string $gatewayUrl, string $to, string $body): void
    {
        try {
            Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                'to'   => $to,
                'body' => $body,
            ]);
        } catch (Throwable) {}
    }

    private function dispatchWhatsAppToAlert(string $token, string $gatewayUrl, CandidateAlert $alert, string $body): void
    {
        foreach ($alert->recipientJids() as $jid) {
            $this->dispatchWhatsApp($token, $gatewayUrl, $jid, $body);
        }
    }

    private function getWhapiCredentials(): array
    {
        $automation = SocialAutomation::where('platform', 'whapi')->where('is_active', true)->first();
        if (! $automation) return [null, null];

        $settings   = $automation->settings ?? [];
        $token      = SocialAutomation::whapiToken($automation);
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        return $token ? [$token, $gatewayUrl] : [null, null];
    }
}
