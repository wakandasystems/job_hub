<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\SocialBroadcast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmployerBroadcastChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $broadcastId,
    ) {}

    public function handle(): void
    {
        $broadcast = SocialBroadcast::find($this->broadcastId);

        if (! $broadcast || ! in_array($broadcast->status, ['pending', 'scheduled', 'sending'], true)) {
            return;
        }

        $broadcast->update([
            'status' => 'cancelled',
            'results' => [[
                'platform' => 'whapi',
                'name' => 'Employer WhatsApp',
                'success' => false,
                'error' => 'Direct WhatsApp broadcasts are disabled.',
            ]],
        ]);
    }
}
