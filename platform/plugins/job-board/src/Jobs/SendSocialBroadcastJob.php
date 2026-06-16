<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\SocialBroadcast;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SendSocialBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private readonly int $broadcastId) {}

    public function handle(SocialPublisherService $publisher): void
    {
        $broadcast = SocialBroadcast::find($this->broadcastId);

        if (! $broadcast || ! in_array($broadcast->status, ['pending', 'scheduled'], true)) {
            return;
        }

        $imageUrl = $broadcast->image_path ? Storage::disk('public')->url($broadcast->image_path) : null;

        $results    = $publisher->broadcastToChannels($broadcast->message, $imageUrl);
        $anySuccess = collect($results)->contains(fn (array $r) => $r['success']);

        $broadcast->update([
            'status'  => $anySuccess ? 'sent' : 'failed',
            'sent_at' => now(),
            'results' => $results,
        ]);
    }
}
