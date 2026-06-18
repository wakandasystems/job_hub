<?php

namespace Botble\JobBoard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckStaleQueueWorkerCommand extends Command
{
    protected $signature = 'job-board:check-stale-queue-worker';
    protected $description = 'Detect a Horizon worker still running pre-deploy code and restart it';

    private const SETTING_KEY = 'job_board_last_seen_stale_worker_failed_job_id';

    // Signature left by a long-running worker process that boots before a deploy
    // adds/changes classes — Composer/PSR-4 resolves fine in fresh PHP processes,
    // but the already-booted worker can't see the new files.
    private const STALE_CODE_PATTERNS = [
        '%does not exist%',
        '%Job is incomplete class%',
    ];

    public function handle(): int
    {
        $lastSeenId = (int) setting(self::SETTING_KEY, 0);

        $query = DB::table('failed_jobs')->where('id', '>', $lastSeenId);

        $query->where(function ($q) {
            foreach (self::STALE_CODE_PATTERNS as $pattern) {
                $q->orWhere('exception', 'like', $pattern);
            }
        });

        $matches = $query->orderBy('id')->get(['id', 'queue', 'exception', 'failed_at']);

        $maxId = (int) DB::table('failed_jobs')->max('id');
        setting()->set(self::SETTING_KEY, max($lastSeenId, $maxId))->save();

        if ($matches->isEmpty()) {
            $this->info('No stale-worker signature found.');

            return self::SUCCESS;
        }

        $this->warn("{$matches->count()} job(s) failed with a stale-code signature — restarting Horizon.");

        Artisan::call('horizon:terminate');

        $adminEmail = setting('admin_email') ?: config('mail.from.address');
        $body = "Horizon was auto-restarted because {$matches->count()} job(s) failed with a stale-code "
            . "signature (worker running pre-deploy code):\n\n";

        foreach ($matches->take(20) as $job) {
            $firstLine = strtok((string) $job->exception, "\n");
            $body .= "#{$job->id} [{$job->queue}] {$job->failed_at}\n{$firstLine}\n\n";
        }

        if ($matches->count() > 20) {
            $body .= '...and ' . ($matches->count() - 20) . " more.\n\n";
        }

        $body .= "If this keeps happening, check that deploys run `php artisan horizon:terminate` "
            . "(see scripts/deploy-production.sh).";

        try {
            Mail::raw(
                $body,
                fn ($msg) => $msg->to($adminEmail)->subject('Wakanda Jobs: Horizon auto-restarted (stale worker)')
            );
        } catch (\Throwable) {
        }

        $this->info('Horizon restart signal sent.');

        return self::SUCCESS;
    }
}
