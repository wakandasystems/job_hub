<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\Account;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendProfileRefreshReminderCommand extends Command
{
    protected $signature   = 'job-board:profile-refresh-reminder {--days=90 : Days of inactivity before reminder}';
    protected $description = 'Email job seekers whose Talent Hub profile has not been updated in N days.';

    public function handle(): void
    {
        $days      = (int) $this->option('days');
        $staleDays = (int) setting('talent_hub_profile_stale_days', $days);
        $cutoff    = Carbon::now()->subDays($staleDays);

        $accounts = Account::query()
            ->where('type', 'job_seeker')
            ->where('is_public_profile', true)
            ->where('talent_hub_consent', true)
            ->where(function ($q) use ($cutoff): void {
                $q->whereNull('profile_updated_at')
                  ->orWhere('profile_updated_at', '<', $cutoff);
            })
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->get();

        $sent = 0;
        $settingsUrl = url('/account/settings');

        foreach ($accounts as $account) {
            if (! $account->email) {
                continue;
            }

            try {
                Mail::raw(
                    "Hi {$account->first_name},\n\n" .
                    "It's been a while since you last updated your Wakanda Jobs profile!\n\n" .
                    "Employers search the Talent Hub daily for candidates like you. " .
                    "Keep your profile fresh — update your skills, availability, and experience so you don't miss out.\n\n" .
                    "Update your profile now:\n{$settingsUrl}\n\n" .
                    "If you're no longer looking for opportunities, you can turn off 'Allow employers to find me in the Talent Hub' in your settings.\n\n" .
                    "— The Wakanda Jobs Team",
                    fn ($msg) => $msg
                        ->to($account->email)
                        ->subject('Your Wakanda Jobs profile needs an update — stay visible to employers')
                );
                $sent++;
            } catch (\Throwable) {
            }
        }

        $this->info("Sent {$sent} profile refresh reminder(s) to candidates inactive for {$staleDays}+ days.");
    }
}
