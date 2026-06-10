<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\Job;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveOldCrawledJobsCommand extends Command
{
    protected $signature = 'job-board:archive-old-jobs';

    protected $description = 'Soft-close crawler jobs expired 90+ days, and delete crawler jobs expired 180+ days with no applications/views';

    public function handle(): int
    {
        $now = Carbon::now();

        $closed = Job::query()
            ->where('is_organic', false)
            ->where('status', '!=', JobStatusEnum::CLOSED)
            ->whereNotNull('expire_date')
            ->where('expire_date', '<', $now->copy()->subDays(90))
            ->update(['status' => JobStatusEnum::CLOSED]);

        $this->info("Closed {$closed} crawler job(s) expired 90+ days.");

        $toDelete = Job::query()
            ->where('is_organic', false)
            ->whereNotNull('expire_date')
            ->where('expire_date', '<', $now->copy()->subDays(180))
            ->where('number_of_applied', 0)
            ->where('views', 0)
            ->pluck('id');

        if ($toDelete->isNotEmpty()) {
            foreach ($toDelete->chunk(500) as $chunk) {
                DB::table('jb_jobs_categories')->whereIn('job_id', $chunk)->delete();
                DB::table('jb_jobs_skills')->whereIn('job_id', $chunk)->delete();
                DB::table('jb_jobs_types')->whereIn('job_id', $chunk)->delete();
                DB::table('jb_saved_jobs')->whereIn('job_id', $chunk)->delete();
                Job::query()->whereIn('id', $chunk)->delete();
            }
        }

        $this->info("Deleted {$toDelete->count()} crawler job(s) expired 180+ days with no applications/views.");

        return self::SUCCESS;
    }
}
