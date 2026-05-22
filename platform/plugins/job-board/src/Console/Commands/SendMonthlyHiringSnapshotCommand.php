<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\EmployerSubscription;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobApplication;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class SendMonthlyHiringSnapshotCommand extends Command
{
    protected $signature   = 'job-board:monthly-hiring-snapshot';
    protected $description = 'Send monthly hiring activity snapshot to employers with active subscriptions.';

    public function handle(): void
    {
        $accountIds = EmployerSubscription::query()
            ->active()
            ->pluck('account_id')
            ->unique();

        $sent     = 0;
        $from     = Carbon::now()->subMonth()->startOfMonth();
        $to       = Carbon::now()->subMonth()->endOfMonth();
        $monthLabel = $from->format('F Y');

        foreach ($accountIds as $accountId) {
            $account = Account::query()->find($accountId);
            if (! $account?->email) {
                continue;
            }

            $jobCount = Job::query()
                ->byAccount($accountId)
                ->whereBetween('created_at', [$from, $to])
                ->count();

            $applicationCount = JobApplication::query()
                ->whereHas('job', fn (Builder $q) => $q->byAccount($accountId))
                ->whereBetween('jb_applications.created_at', [$from, $to])
                ->count();

            $topJobs = Job::query()
                ->byAccount($accountId)
                ->withCount(['applicants' => fn ($q) => $q->whereBetween('jb_applications.created_at', [$from, $to])])
                ->orderByDesc('applicants_count')
                ->limit(3)
                ->get(['id', 'name']);

            $topJobsList = $topJobs->map(fn ($j) => "  • {$j->name} ({$j->applicants_count} applications)")->implode("\n");

            $body =
                "Hi {$account->name},\n\n" .
                "Here's your hiring snapshot for {$monthLabel}:\n\n" .
                "  Jobs posted:         {$jobCount}\n" .
                "  Applications received: {$applicationCount}\n\n" .
                ($topJobsList ? "Top performing jobs:\n{$topJobsList}\n\n" : '') .
                "Log in to manage applicants and keep your listings fresh:\n" .
                url('/account/dashboard') . "\n\n" .
                "— The Wakanda Jobs Team";

            try {
                Mail::raw($body, fn ($msg) => $msg
                    ->to($account->email)
                    ->subject("Your Wakanda Jobs hiring snapshot — {$monthLabel}")
                );
                $sent++;
            } catch (\Throwable) {
            }
        }

        $this->info("Sent {$sent} monthly snapshot(s) for {$monthLabel}.");
    }
}
