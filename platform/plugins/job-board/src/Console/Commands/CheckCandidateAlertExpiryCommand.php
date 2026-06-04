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

    public function handle(): int
    {
        $this->info('Checking candidate alert expiry...');

        [$token, $gatewayUrl] = $this->getWhapiCredentials();

        // --- 1. Day-before expiry warning ---
        $warning = CandidateAlert::where('is_active', true)
            ->where('status', 'active')
            ->where('expiry_warning_sent', false)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', now()->addDay()->toDateString())
            ->get();

        foreach ($warning as $alert) {
            $this->line("  Warning → {$alert->candidate_name}");
            if ($token) {
                $this->sendExpiryWarning($token, $gatewayUrl, $alert);
            }
            $alert->update(['expiry_warning_sent' => true]);
        }

        // --- 2. Mark expired and notify ---
        $expired = CandidateAlert::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $alert) {
            $this->line("  Expired  → {$alert->candidate_name}");
            $alert->update(['status' => 'expired', 'is_active' => false]);

            if ($token && ! $alert->expiry_notice_sent) {
                $this->sendExpiryNotice($token, $gatewayUrl, $alert);
                $alert->update(['expiry_notice_sent' => true]);
            }
        }

        $this->info("Done. Warned: {$warning->count()}, Expired: {$expired->count()}.");

        return self::SUCCESS;
    }

    private function sendExpiryWarning(string $token, string $gatewayUrl, CandidateAlert $alert): void
    {
        $msg  = "⚠️ *Job Alert Expiring Tomorrow!*\n\n";
        $msg .= "Hi {$alert->candidate_name}! 👋\n\n";
        $msg .= "Your VIP Job Alert *\"{$alert->label}\"* expires *tomorrow*.\n\n";
        $msg .= "📅 Expiry: *" . $alert->expires_at?->format('d M Y') . "*\n\n";
        $msg .= "To keep receiving personalised job alerts, please renew your subscription:\n\n";
        $msg .= "• 7 Days  — K20\n";
        $msg .= "• 14 Days — K30\n";
        $msg .= "• 30 Days — K50\n\n";
        $msg .= "Contact us today to renew 👇\n";
        $msg .= "_Wakanda Jobs — wakandajobs.com_";

        $this->dispatchWhatsApp($token, $gatewayUrl, $alert->recipientJid(), $msg);
    }

    private function sendExpiryNotice(string $token, string $gatewayUrl, CandidateAlert $alert): void
    {
        $msg  = "🔴 *Your Job Alert Subscription Has Expired*\n\n";
        $msg .= "Hi {$alert->candidate_name}! 👋\n\n";
        $msg .= "Your VIP Job Alert *\"{$alert->label}\"* expired today.\n\n";
        $msg .= "You will no longer receive automatic job alerts.\n\n";
        $msg .= "🔄 *Renew your subscription to stay on top of opportunities:*\n\n";
        $msg .= "• 7 Days  — K20\n";
        $msg .= "• 14 Days — K30\n";
        $msg .= "• 30 Days — K50\n\n";
        $msg .= "Reply or visit *wakandajobs.com* to renew today! 🚀\n\n";
        $msg .= "_Wakanda Jobs — wakandajobs.com_";

        $this->dispatchWhatsApp($token, $gatewayUrl, $alert->recipientJid(), $msg);
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

    private function getWhapiCredentials(): array
    {
        $automation = SocialAutomation::where('platform', 'whapi')->where('is_active', true)->first();
        if (! $automation) return [null, null];

        $settings   = $automation->settings ?? [];
        $token      = trim((string) ($settings['token'] ?? ''));
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        return $token ? [$token, $gatewayUrl] : [null, null];
    }
}
