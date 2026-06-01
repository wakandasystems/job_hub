<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\JobCrawlerRunner;
use Illuminate\Console\Command;

class FixCrawledApplyEmailsCommand extends Command
{
    protected $signature = 'job-board:fix-crawled-apply-emails
                            {--dry-run : Preview changes without saving}
                            {--limit=0 : Process at most N jobs (0 = all)}';

    protected $description = 'For crawled jobs: extract contact email from description and switch to Easy Apply; fall back to company website when no email found.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit  = (int) $this->option('limit');

        if ($dryRun) {
            $this->components->warn('DRY RUN — no changes will be saved.');
        }

        $query = Job::query()
            ->whereNotNull('crawler_id')
            ->with('company')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $total     = (clone $query)->count();
        $emailSet  = 0;
        $websiteFallback = 0;
        $noContact = 0;

        $this->components->info("Processing {$total} crawled jobs…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(500, function ($jobs) use ($dryRun, &$emailSet, &$websiteFallback, &$noContact, $bar): void {
            foreach ($jobs as $job) {
                $emails = JobCrawlerRunner::extractAllEmailsFromHtml(
                    $job->getRawOriginal('content') ?: $job->getRawOriginal('description')
                );

                if ($emails) {
                    if (! $dryRun) {
                        $subject = rawurlencode(trim(strip_tags((string) $job->name)) . ' Application');
                        $mailto = 'mailto:' . $emails[0];
                        $params = ['subject=' . $subject];
                        if (count($emails) > 1) {
                            $params[] = 'cc=' . implode(',', array_slice($emails, 1));
                        }
                        $job->apply_email = $emails[0];
                        $job->apply_url   = $mailto . '?' . implode('&', $params);
                        $job->save();
                    }
                    $emailSet++;
                } else {
                    $website = $job->company?->website;
                    if ($website) {
                        if (! $dryRun) {
                            $job->apply_url = $website;
                            $job->save();
                        }
                        $websiteFallback++;
                    } else {
                        $noContact++;
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->components->info("Done.");
        $this->table(
            ['Result', 'Count'],
            [
                ['Easy Apply (email found)',   $emailSet],
                ['Company website fallback',   $websiteFallback],
                ['No contact — unchanged',     $noContact],
            ]
        );

        return self::SUCCESS;
    }
}
