<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\AiImageGenerationLog;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\OpenAiImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Re-runs a single failed AiImageGenerationLog attempt (same slot, same job) and,
 * on success, reposts the job to social channels so the corrected image goes out —
 * mirrors what GenerateSocialImagesCommand does on the happy path, but triggered
 * manually from the Logs tab instead of the publish event.
 */
class RetryAiImageGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(
        public int $logId,
        public string $batchKey,
    ) {
        $this->onQueue('social-images');
    }

    public static function batchTotalKey(string $batchKey): string
    {
        return "ai-image-retry:{$batchKey}:total";
    }

    public static function batchDoneKey(string $batchKey): string
    {
        return "ai-image-retry:{$batchKey}:done";
    }

    public static function batchSucceededKey(string $batchKey): string
    {
        return "ai-image-retry:{$batchKey}:succeeded";
    }

    public static function batchFailedKey(string $batchKey): string
    {
        return "ai-image-retry:{$batchKey}:failed";
    }

    public function handle(OpenAiImageService $service): void
    {
        $succeeded = false;

        try {
            $log = AiImageGenerationLog::query()->find($this->logId);
            $job = $log
                ? Job::with(['company', 'slugable', 'country', 'currency', 'jobTypes'])->find($log->job_id)
                : null;

            if ($log && $job) {
                $result = $service->generateForJob($job, $log->slot_type);
                $succeeded = (bool) ($result['ok'] ?? false);

                // A batch retry can hit several failed slots for the same job — repost
                // that job to channels only once per batch, not once per slot.
                if ($succeeded) {
                    $repostLock = 'ai-image-retry-repost:' . $this->batchKey . ':' . $job->getKey();

                    if (Cache::add($repostLock, true, now()->addHour())) {
                        SocialPublishJob::dispatch($job->getKey());
                    }
                }
            }
        } finally {
            Cache::increment(self::batchDoneKey($this->batchKey));
            Cache::increment($succeeded ? self::batchSucceededKey($this->batchKey) : self::batchFailedKey($this->batchKey));
        }
    }
}
