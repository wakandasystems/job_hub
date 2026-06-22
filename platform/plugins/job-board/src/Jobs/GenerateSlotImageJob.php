<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\OpenAiImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

/**
 * The OpenAI call (up to 180s) plus local AVIF/WebP encoding afterward can run
 * well past PHP-FPM's max_execution_time, tying up one of the few "jobs" pool
 * workers for the whole request. A couple of concurrent Post Kit "Generate"
 * clicks was enough to exhaust the pool and take the whole site down. Running
 * this on Horizon instead releases the PHP-FPM worker immediately; the
 * frontend polls generateStatus() for the result.
 */
class GenerateSlotImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public int $jobId,
        public string $type,
        public string $requestId,
    ) {
        // Its own queue so a manual Post Kit click isn't stuck behind a bulk
        // crawler-publish burst on 'default' — see config/horizon.php.
        $this->onQueue('image-generate');
    }

    public function handle(OpenAiImageService $service): void
    {
        $job = Job::query()->find($this->jobId);

        $result = $job
            ? $service->generateForJob($job, $this->type)
            : ['ok' => false, 'message' => 'Job not found.'];

        Cache::put(self::cacheKey($this->requestId), $result, now()->addMinutes(10));
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put(self::cacheKey($this->requestId), [
            'ok' => false,
            'message' => 'Generation failed: ' . $exception->getMessage(),
        ], now()->addMinutes(10));
    }

    public static function cacheKey(string $requestId): string
    {
        return 'social_image_gen:' . $requestId;
    }
}
