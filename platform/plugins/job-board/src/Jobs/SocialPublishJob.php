<?php

namespace Botble\JobBoard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;

/**
 * Queued replacement for the old `exec('... job-board:social-publish ... &')`
 * detached process — see GenerateSocialImagesJob for why detached exec() was
 * unreliable.
 */
class SocialPublishJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $backoff = 60;

    public int $timeout = 180;

    public function __construct(public int $jobId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Artisan::call('job-board:social-publish', [
            'jobId' => $this->jobId,
        ]);
    }
}
