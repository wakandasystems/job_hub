<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\SalesAgentMarketingImage;
use Botble\JobBoard\Services\OpenAiImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Same rationale as GenerateSlotImageJob — the OpenAI call can run past PHP-FPM's
 * max_execution_time, so this runs on the shared 'image-generate' Horizon queue
 * instead of inline, and the admin UI polls the marketing image row's status.
 */
class GenerateSalesAgentPosterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public int $marketingImageId, public bool $sendAfterGenerate = false)
    {
        $this->onQueue('image-generate');
    }

    public function handle(OpenAiImageService $service): void
    {
        $image = SalesAgentMarketingImage::query()->with(['salesAgent', 'campaign'])->find($this->marketingImageId);

        if (! $image || ! $image->salesAgent || ! $image->campaign) {
            return;
        }

        $result = $service->generateForSalesAgentPoster($image->salesAgent, $image->campaign, $image->subject_mode ?: 'nakia');

        if ($result['ok'] ?? false) {
            $image->update([
                'status' => 'completed',
                'image_path' => $result['path'],
                'cost_usd' => $result['cost_usd'] ?? null,
                'generation_ms' => $result['duration_ms'] ?? null,
                'input_tokens' => $result['input_tokens'] ?? null,
                'output_tokens' => $result['output_tokens'] ?? null,
                'total_tokens' => $result['total_tokens'] ?? null,
                'error_message' => null,
            ]);

            if ($this->sendAfterGenerate) {
                SendSalesAgentMarketingImageJob::dispatch($image->getKey());
            }

            return;
        }

        $image->update([
            'status' => 'failed',
            'error_message' => $result['message'] ?? 'Generation failed.',
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $image = SalesAgentMarketingImage::query()->find($this->marketingImageId);

        $image?->update([
            'status' => 'failed',
            'error_message' => 'Generation failed: ' . $exception->getMessage(),
        ]);
    }
}
