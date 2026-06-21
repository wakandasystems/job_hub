<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\OpenAiImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillImageVariantsCommand extends Command
{
    protected $signature = 'job-board:backfill-image-variants
                            {--dry-run : Show what would be generated without saving}
                            {--job= : Backfill a single job ID}';

    protected $description = 'Generate AVIF/WebP/LQIP variants for existing job images.';

    public function handle(OpenAiImageService $service): int
    {
        set_time_limit(0);

        $dryRun = (bool) $this->option('dry-run');
        $jobId  = $this->option('job');

        if ($dryRun) {
            $this->components->warn('DRY RUN — no files will be written.');
        }

        $fields = OpenAiImageService::slotTypes();

        $query = Job::query()->where(function ($q) use ($fields): void {
            foreach ($fields as $field) {
                $q->orWhere(function ($q2) use ($field): void {
                    $q2->whereNotNull($field)->where($field, '!=', '');
                });
            }
        });

        if ($jobId) {
            $query->where('id', (int) $jobId);
        }

        $jobs  = $query->get();
        $total = $jobs->count();

        $this->components->info("Jobs to inspect: {$total}");

        if ($total === 0) {
            $this->components->info('Nothing to do.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('public');

        $bar       = $this->output->createProgressBar($total);
        $generated = 0;
        $skipped   = 0;
        $missing   = 0;
        $failed    = 0;

        $bar->start();

        foreach ($jobs as $job) {
            $bar->advance();

            $variants = (array) $job->image_variants;
            $changed  = false;

            foreach ($fields as $field) {
                $path = trim((string) ($job->{$field} ?? ''));
                if ($path === '') {
                    continue;
                }

                if (! empty($variants[$field])) {
                    $skipped++;
                    continue;
                }

                if (! $disk->exists($path)) {
                    $missing++;
                    continue;
                }

                if ($dryRun) {
                    $generated++;
                    continue;
                }

                try {
                    $result = $service->generateVariants($path);
                } catch (\Throwable) {
                    $result = [];
                }

                if ($result === []) {
                    $failed++;
                    continue;
                }

                $variants[$field] = $result;
                $changed = true;
                $generated++;
            }

            if ($changed && ! $dryRun) {
                $job->image_variants = $variants;
                $job->save();
            }
        }

        $bar->finish();
        $this->newLine();

        $this->table(
            ['Result', 'Count'],
            [
                ['Variants ' . ($dryRun ? 'to generate' : 'generated'), $generated],
                ['Skipped (already present)', $skipped],
                ['Skipped (file missing on disk)', $missing],
                ['Failed', $failed],
            ]
        );

        return self::SUCCESS;
    }
}
