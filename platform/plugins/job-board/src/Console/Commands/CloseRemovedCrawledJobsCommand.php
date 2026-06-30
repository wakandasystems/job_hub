<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Enums\JobStatusEnum;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand('job-board:close-removed-crawled-jobs', 'Close crawled jobs whose source pages return 404')]
class CloseRemovedCrawledJobsCommand extends Command
{
    protected $signature = 'job-board:close-removed-crawled-jobs
                            {--dry-run : Report which jobs would be closed without making any changes}';

    protected $description = 'Close crawled jobs whose source pages return 404 (runs twice daily)';

    /**
     * Domain patterns (LIKE syntax) whose crawled jobs should be liveness-checked.
     * Add new WP-Job-Manager-style sites here as they are onboarded.
     */
    private const SOURCE_PATTERNS = [
        'https://jobzambia.com/%',
    ];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $today    = Carbon::today()->toDateString();
        $closed   = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach (self::SOURCE_PATTERNS as $pattern) {
            $jobs = DB::table('jb_jobs')
                ->where('status', JobStatusEnum::PUBLISHED)
                ->whereNotNull('external_source_url')
                ->where('external_source_url', 'like', $pattern)
                ->get(['id', 'name', 'external_source_url']);

            $this->components->info(sprintf(
                'Checking %d published job(s) matching %s',
                $jobs->count(),
                $pattern,
            ));

            foreach ($jobs as $job) {
                $sourceUrl = trim((string) $job->external_source_url);

                if ($sourceUrl === '') {
                    $skipped++;
                    continue;
                }

                try {
                    $response = Http::timeout(12)
                        ->withHeaders([
                            'User-Agent'      => 'WakandaJobsCrawler/1.0 (+https://www.wakandajobs.com)',
                            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                            'Accept-Language' => 'en-US,en;q=0.9',
                        ])
                        ->get($sourceUrl);
                } catch (Throwable $e) {
                    $errors++;
                    $this->components->warn("  NETWORK ERROR #{$job->id} {$job->name}: {$e->getMessage()}");
                    usleep(300_000);
                    continue;
                }

                $status = $response->status();

                if ($status === 404) {
                    if (! $isDryRun) {
                        DB::table('jb_jobs')->where('id', $job->id)->update([
                            'status'                   => JobStatusEnum::CLOSED,
                            'expire_date'              => $today,
                            'application_closing_date' => $today,
                            'updated_at'               => now(),
                        ]);
                    }
                    $closed++;
                    $prefix = $isDryRun ? '[dry-run] would close' : 'closed';
                    $this->components->info("  {$prefix} #{$job->id}: {$job->name}");
                } elseif ($response->successful()) {
                    $skipped++;
                } else {
                    // 5xx or unexpected — skip conservatively; could be transient
                    $skipped++;
                    $this->components->warn("  HTTP {$status} #{$job->id} {$job->name} — skipping (transient?)");
                }

                // 250 ms between requests — respectful to source server
                usleep(250_000);
            }
        }

        $this->components->info(sprintf(
            'Done%s — closed: %d  skipped: %d  errors: %d',
            $isDryRun ? ' (dry run, no changes made)' : '',
            $closed,
            $skipped,
            $errors,
        ));

        return self::SUCCESS;
    }
}
