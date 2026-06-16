<?php

namespace Botble\JobBoard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckFailedJobsCommand extends Command
{
    protected $signature = 'job-board:check-failed-jobs';
    protected $description = 'Email the admin a summary when new entries appear in the failed_jobs table';

    private const SETTING_KEY = 'job_board_last_seen_failed_job_id';

    public function handle(): int
    {
        $lastSeenId = (int) setting(self::SETTING_KEY, 0);

        $failedJobs = DB::table('failed_jobs')
            ->where('id', '>', $lastSeenId)
            ->orderBy('id')
            ->get(['id', 'queue', 'exception', 'failed_at']);

        if ($failedJobs->isEmpty()) {
            $this->info('No new failed jobs.');

            return self::SUCCESS;
        }

        $maxId = $failedJobs->max('id');
        $adminEmail = setting('admin_email') ?: config('mail.from.address');

        $body = "{$failedJobs->count()} job(s) have failed since the last check:\n\n";

        foreach ($failedJobs as $job) {
            $firstLine = strtok((string) $job->exception, "\n");
            $body .= "#{$job->id} [{$job->queue}] {$job->failed_at}\n{$firstLine}\n\n";
        }

        $body .= "Run `php artisan queue:failed` for full details, and `php artisan queue:retry <id>` to retry a job.";

        try {
            Mail::raw(
                $body,
                fn ($msg) => $msg->to($adminEmail)->subject("Wakanda Jobs: {$failedJobs->count()} failed queue job(s)")
            );
        } catch (\Throwable) {
        }

        setting()->set(self::SETTING_KEY, $maxId)->save();

        $this->info("Notified admin of {$failedJobs->count()} new failed job(s).");

        return self::SUCCESS;
    }
}
