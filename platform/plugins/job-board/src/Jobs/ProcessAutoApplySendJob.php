<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AutoApplyLog;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\AutoApplyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAutoApplySendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        private readonly int $accountId,
        private readonly int $jobId,
    ) {
    }

    public function handle(AutoApplyService $service): void
    {
        $account = Account::find($this->accountId);
        $job = Job::find($this->jobId);

        if (! $account || ! $job) {
            AutoApplyLog::where('account_id', $this->accountId)
                ->where('job_id', $this->jobId)
                ->where('status', 'queued')
                ->delete();

            return;
        }

        // The queueAutoApplyJob caller inserts a 'queued' placeholder log so the admin sees
        // the job is pending. Delete it now so processAutoApply can write the real outcome log
        // without hitting the unique (account_id, job_id) constraint.
        AutoApplyLog::where('account_id', $account->id)
            ->where('job_id', $job->id)
            ->where('status', 'queued')
            ->delete();

        try {
            $cvText = $service->extractCvText($account);
            $profile = $service->buildCandidateProfile($account, $cvText);
            $service->processAutoApply($account, $job, $profile);
        } catch (Throwable $e) {
            Log::error('AutoApply: ProcessAutoApplySendJob failed', [
                'account_id' => $this->accountId,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
            ]);

            // Write a failed log so the admin can see what went wrong, then rethrow
            // so Horizon marks the job as failed and retries/alerts accordingly.
            AutoApplyLog::create([
                'account_id'    => $account->id,
                'job_id'        => $job->id,
                'email_sent_to' => $service->resolveJobApplyEmail($job) ?: 'unknown',
                'ai_model_used' => \Botble\JobBoard\Models\AutoApplyOrder::globalAiModel(),
                'match_score'   => 0,
                'status'        => 'failed',
                'error_message' => 'Queue job exception: ' . $e->getMessage(),
                'sent_at'       => now(),
            ]);

            throw $e;
        }
    }
}
