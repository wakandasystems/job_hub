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

class NotifyCandidateAutoApplySentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        private readonly int $accountId,
        private readonly int $jobId,
        private readonly ?string $coverLetterSubject = null,
        private readonly ?string $coverLetterBody = null,
    ) {
    }

    public function handle(AutoApplyService $service): void
    {
        $account = Account::find($this->accountId);
        $job = Job::with(['company', 'slugable'])->find($this->jobId);

        if (! $account || ! $job) {
            return;
        }

        $service->sendAutoApplySuccessToCandidate(
            $account,
            $job,
            $this->coverLetterSubject,
            $this->coverLetterBody,
        );
    }
}
