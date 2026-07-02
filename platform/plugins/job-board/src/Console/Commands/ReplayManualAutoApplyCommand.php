<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\AutoApplyLog;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Models\AutoApplyPreference;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobApplication;
use Botble\JobBoard\Services\AutoApplyService;
use Illuminate\Console\Command;

class ReplayManualAutoApplyCommand extends Command
{
    protected $signature = 'job-board:replay-manual-auto-apply
        {--dry-run : Only report eligible manual auto-apply logs}
        {--limit=200 : Maximum number of manual logs to inspect}
        {--account-id= : Restrict to one candidate account}
        {--job-id= : Restrict to one job}
        {--crawler-id= : Restrict to jobs from one crawler}';

    protected $description = 'Replay manual auto-apply notices for jobs that now have a real application email';

    public function handle(AutoApplyService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $accountId = $this->option('account-id');
        $jobId = $this->option('job-id');
        $crawlerId = $this->option('crawler-id');

        $query = AutoApplyLog::query()
            ->where('email_sent_to', 'manual-apply-notice')
            ->with(['account', 'job.company', 'job.slugable'])
            ->latest('id');

        if ($accountId) {
            $query->where('account_id', (int) $accountId);
        }

        if ($jobId) {
            $query->where('job_id', (int) $jobId);
        }

        if ($crawlerId) {
            $query->whereHas('job', fn ($jobQuery) => $jobQuery->where('crawler_id', (int) $crawlerId));
        }

        $logs = $query->limit($limit)->get();

        $stats = [
            'checked' => $logs->count(),
            'eligible' => 0,
            'replayed' => 0,
            'already_applied' => 0,
            'skipped_no_email' => 0,
            'skipped_inactive_job' => 0,
            'skipped_inactive_preference' => 0,
            'skipped_inactive_subscription' => 0,
            'skipped_no_quota' => 0,
            'failed' => 0,
        ];

        foreach ($logs as $log) {
            $account = $log->account;
            $job = $log->job;

            if (! $account || ! $job) {
                $stats['failed']++;
                continue;
            }

            if ($service->resolveJobApplyEmail($job) === '') {
                $stats['skipped_no_email']++;
                continue;
            }

            if (! Job::query()->whereKey($job->id)->active()->notClosed()->where('status', JobStatusEnum::PUBLISHED)->exists()) {
                $stats['skipped_inactive_job']++;
                continue;
            }

            $preference = AutoApplyPreference::query()
                ->where('account_id', $account->id)
                ->where('is_active', true)
                ->first();

            if (! $preference || ! $preference->candidateHasCv()) {
                $stats['skipped_inactive_preference']++;
                continue;
            }

            if (! AutoApplyOrder::activeForAccount($account->id)) {
                $stats['skipped_inactive_subscription']++;
                continue;
            }

            $existingApplication = JobApplication::query()
                ->where('account_id', $account->id)
                ->where('job_id', $job->id)
                ->first();

            if ($existingApplication && ! (bool) $existingApplication->is_external_apply) {
                $stats['already_applied']++;
                continue;
            }

            if (! $service->hasQuota($account->id)) {
                $stats['skipped_no_quota']++;
                continue;
            }

            $stats['eligible']++;

            $this->line(sprintf(
                '%s log=%d account=%d job=%d %s',
                $dryRun ? '[DRY RUN]' : '[REPLAY]',
                $log->id,
                $account->id,
                $job->id,
                $job->name
            ));

            if ($dryRun) {
                continue;
            }

            $result = $service->replayManualApplyLog($log);

            if (($result['status'] ?? null) === 'sent') {
                $stats['replayed']++;
                continue;
            }

            if (($result['status'] ?? null) === 'already_applied') {
                $stats['already_applied']++;
                continue;
            }

            if (($result['status'] ?? null) === 'no_quota') {
                $stats['skipped_no_quota']++;
                continue;
            }

            $stats['failed']++;
            $this->warn(sprintf(
                'Failed log=%d account=%d job=%d: %s',
                $log->id,
                $account->id,
                $job->id,
                $result['message'] ?? 'Unknown replay failure'
            ));
        }

        $this->info(sprintf(
            'Checked %d manual logs. Eligible %d. Replayed %d. Already applied %d. No email %d. Inactive jobs %d. Inactive preferences %d. Inactive subscriptions %d. No quota %d. Failed %d.',
            $stats['checked'],
            $stats['eligible'],
            $stats['replayed'],
            $stats['already_applied'],
            $stats['skipped_no_email'],
            $stats['skipped_inactive_job'],
            $stats['skipped_inactive_preference'],
            $stats['skipped_inactive_subscription'],
            $stats['skipped_no_quota'],
            $stats['failed']
        ));

        return self::SUCCESS;
    }
}
