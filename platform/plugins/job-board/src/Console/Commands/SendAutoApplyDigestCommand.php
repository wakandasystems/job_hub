<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\AutoApplyLog;
use Botble\JobBoard\Models\AutoApplyPreference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAutoApplyDigestCommand extends Command
{
    protected $signature = 'auto-apply:weekly-digest';

    protected $description = 'Send weekly digest email to candidates with Auto Apply enabled, summarizing applications sent this week.';

    public function handle(): int
    {
        $this->info('Sending Auto Apply weekly digests...');

        $weekAgo = now()->subWeek();

        $preferences = AutoApplyPreference::active()
            ->with('account')
            ->get()
            ->filter(fn ($p) => $p->account && $p->account->email);

        $sent = 0;

        foreach ($preferences as $preference) {
            $account = $preference->account;

            $logs = AutoApplyLog::where('account_id', $account->id)
                ->where('sent_at', '>=', $weekAgo)
                ->with('job')
                ->latest('sent_at')
                ->get();

            if ($logs->isEmpty()) {
                continue;
            }

            $sentLogs = $logs->where('status', 'sent');
            $skippedLogs = $logs->where('status', 'skipped_low_score');
            $failedLogs = $logs->where('status', 'failed');

            $body = "Hi {$account->first_name},\n\n";
            $body .= "Here's your weekly Auto Apply digest from Wakanda Jobs.\n\n";
            $body .= "--- THIS WEEK'S SUMMARY ---\n";
            $body .= "Applications sent: {$sentLogs->count()}\n";
            if ($skippedLogs->isNotEmpty()) {
                $body .= "Skipped (low match score): {$skippedLogs->count()}\n";
            }
            if ($failedLogs->isNotEmpty()) {
                $body .= "Failed: {$failedLogs->count()}\n";
            }

            if ($sentLogs->isNotEmpty()) {
                $body .= "\n--- APPLICATIONS SENT ---\n";
                foreach ($sentLogs as $log) {
                    $jobName = $log->job?->name ?? 'Unknown Job';
                    $score = $log->match_score;
                    $date = $log->sent_at?->format('d M');
                    $body .= "- {$jobName} (Score: {$score}%) — {$date}\n";
                }
            }

            if ($skippedLogs->isNotEmpty()) {
                $body .= "\n--- SKIPPED (Below Threshold) ---\n";
                foreach ($skippedLogs->take(10) as $log) {
                    $jobName = $log->job?->name ?? 'Unknown Job';
                    $score = $log->match_score;
                    $body .= "- {$jobName} (Score: {$score}%)\n";
                }
            }

            $body .= "\nManage your Auto Apply preferences:\n" . route('public.account.auto-apply.index') . "\n\n";
            $body .= "Wakanda Jobs — wakandajobs.com";

            try {
                Mail::raw($body, function ($msg) use ($account, $sentLogs): void {
                    $msg->to($account->email, "{$account->first_name} {$account->last_name}")
                        ->subject("Auto Apply Weekly Digest — {$sentLogs->count()} applications sent");
                });
                $sent++;
            } catch (Throwable $e) {
                $this->warn("Failed to send digest to {$account->email}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} weekly digest emails.");

        return self::SUCCESS;
    }
}
