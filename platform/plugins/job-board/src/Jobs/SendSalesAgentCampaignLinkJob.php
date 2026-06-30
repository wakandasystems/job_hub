<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\SalesAgent;
use Botble\JobBoard\Models\SalesAgentCampaign;
use Botble\JobBoard\Services\WhapiSenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendSalesAgentCampaignLinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        private readonly int $salesAgentId,
        private readonly int $campaignId,
    ) {
        $this->onQueue('default');
    }

    public function handle(WhapiSenderService $sender): void
    {
        $agent = SalesAgent::query()->find($this->salesAgentId);
        $campaign = SalesAgentCampaign::query()->find($this->campaignId);

        if (! $agent || ! $campaign || $agent->status !== 'active' || ! $campaign->is_active) {
            return;
        }

        $message = $campaign->buildShareMessage($agent);
        $errorMessage = null;

        $marketingImage = $campaign->marketingImages()
            ->where('sales_agent_id', $agent->getKey())
            ->where('status', 'completed')
            ->whereNotNull('image_path')
            ->latest()
            ->first()
            ?? $campaign->latestCompletedMarketingImage()->first();
        $imagePath = $marketingImage?->image_path
            ? Storage::disk('public')->path($marketingImage->image_path)
            : null;

        $sent = ($imagePath && is_file($imagePath))
            ? $sender->sendImage($agent->phone, $imagePath, $message, $errorMessage)
            : $sender->sendText($agent->phone, $message, $errorMessage);

        if (! $sent) {
            Log::warning('SalesAgentCampaign link send failed', [
                'sales_agent_id' => $agent->getKey(),
                'campaign_id' => $campaign->getKey(),
                'with_image' => (bool) ($imagePath && is_file($imagePath ?? '')),
                'error' => $errorMessage ?: 'Unknown WhatsApp failure',
            ]);
        }
    }
}
