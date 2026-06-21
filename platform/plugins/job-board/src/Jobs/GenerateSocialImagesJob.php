<?php

namespace Botble\JobBoard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;

/**
 * Queued replacement for the old `exec('... job-board:generate-social-images ... &')`
 * detached process. Detached shell children stay in the PHP-FPM worker's cgroup
 * (KillMode=control-group), so any worker recycle (deploy, optimize:clear, FPM
 * reload) silently killed the whole image-generation → publish → employer-pitch
 * chain. Horizon-backed queue jobs persist in Redis instead, so they survive it.
 */
class GenerateSocialImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $backoff = 60;

    public int $timeout = 300;

    public function __construct(
        public int $jobId,
        public bool $publish = true,
        public int $attempt = 1,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Artisan::call('job-board:generate-social-images', [
            'jobId' => $this->jobId,
            '--publish' => $this->publish,
            '--attempt' => $this->attempt,
        ]);
    }
}
