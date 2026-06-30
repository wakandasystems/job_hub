<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\SalesAgentMarketingImage;
use Botble\JobBoard\Services\WhapiSenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

class SendSalesAgentMarketingImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public int $marketingImageId)
    {
        $this->onQueue('default');
    }

    public function handle(WhapiSenderService $sender): void
    {
        $image = SalesAgentMarketingImage::query()->with(['salesAgent', 'campaign'])->find($this->marketingImageId);

        if (! $image || ! $image->salesAgent || ! $image->campaign) {
            return;
        }

        if ($image->status !== 'completed' || ! $image->image_path || ! Storage::disk('public')->exists($image->image_path)) {
            $image->update([
                'error_message' => 'Image is not ready to send.',
            ]);

            return;
        }

        $path = Storage::disk('public')->path($image->image_path);
        $caption = $image->campaign->buildShareMessage($image->salesAgent);

        $errorMessage = null;

        if (! $sender->sendImage($image->salesAgent->phone, $path, $caption, $errorMessage)) {
            $image->update([
                'error_message' => $errorMessage ?: 'Could not send marketing image.',
            ]);

            return;
        }

        $image->update([
            'sent_at' => now(),
            'error_message' => null,
        ]);
    }
}
