<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\Account;
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
            return;
        }

        try {
            $cvText = $service->extractCvText($account);
            $profile = $service->buildCandidateProfile($account, $cvText);
            $service->processAutoApply($account, $job, $profile);
        } catch (Throwable $e) {
            Log::error('AutoApply: Admin-triggered send failed', [
                'account_id' => $this->accountId,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
