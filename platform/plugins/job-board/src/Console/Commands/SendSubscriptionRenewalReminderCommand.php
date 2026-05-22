<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\EmployerSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionRenewalReminderCommand extends Command
{
    protected $signature   = 'job-board:subscription-renewal-reminder';
    protected $description = 'Email employers whose subscription expires within 7 days.';

    public function handle(): void
    {
        $subs = EmployerSubscription::query()
            ->expiringSoon(7)
            ->with(['account', 'package'])
            ->get();

        $adminEmail = setting('admin_email') ?: config('mail.from.address');
        $sent = 0;

        foreach ($subs as $sub) {
            $email = $sub->account?->email;
            if (! $email) {
                continue;
            }

            $packageName = $sub->package?->name ?? 'your plan';
            $endsAt      = $sub->ends_at->format('d M Y');
            $renewUrl    = url('/account/subscription');

            try {
                Mail::raw(
                    "Hi {$sub->account->name},\n\n" .
                    "Your {$packageName} subscription on Wakanda Jobs expires on {$endsAt}.\n\n" .
                    "Renew now to keep your job posts live, maintain candidate search access, and avoid any disruption to your hiring.\n\n" .
                    "Renew at: {$renewUrl}\n\n" .
                    "Questions? Reply to this email or contact our support team.\n\n" .
                    "— The Wakanda Jobs Team",
                    fn ($msg) => $msg->to($email)->subject("Your Wakanda Jobs subscription expires on {$endsAt}")
                );
                $sent++;
            } catch (\Throwable) {
            }
        }

        $this->info("Sent {$sent} renewal reminder(s).");
    }
}
