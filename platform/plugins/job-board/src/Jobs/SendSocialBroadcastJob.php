<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\SocialBroadcast;
use Botble\JobBoard\Services\BroadcastRecurrenceService;
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

    public function handle(SocialPublisherService $publisher, BroadcastRecurrenceService $recurrence): void
    {
        $broadcast = SocialBroadcast::find($this->broadcastId);

        if (! $broadcast || ! in_array($broadcast->status, ['pending', 'scheduled', 'recurring'], true)) {
            return;
        }

        $imageUrl = $broadcast->image_path ? Storage::disk('public')->url($broadcast->image_path) : null;

        // AI Spice: reword the original template on each send so repeated recurring
        // posts don't read as identical copy-paste. The stored `message` stays the
        // immutable template — only the rendered/sent copy varies.
        $messageToSend = $broadcast->ai_spice
            ? $publisher->rephraseBroadcastMessage($broadcast->message)
            : $broadcast->message;

        $results    = $publisher->broadcastToChannels($messageToSend, $imageUrl);
        $anySuccess = collect($results)->contains(fn (array $r) => $r['success']);

        if (! $broadcast->isRecurring()) {
            $broadcast->update([
                'status'             => $anySuccess ? 'sent' : 'failed',
                'sent_at'            => now(),
                'results'            => $results,
                'last_sent_message'  => $messageToSend,
            ]);

            return;
        }

        $occurrenceCount = $broadcast->occurrence_count + 1;
        $reachedCap      = $broadcast->max_occurrences && $occurrenceCount >= $broadcast->max_occurrences;

        $update = [
            'sent_at'            => now(),
            'results'            => $results,
            'last_sent_message'  => $messageToSend,
            'occurrence_count'   => $occurrenceCount,
        ];

        if ($reachedCap) {
            $update['status']       = 'completed';
            $update['next_run_at']  = null;
        } else {
            $update = array_merge($update, $recurrence->nextRun($broadcast, now()));
        }

        $broadcast->update($update);
    }
}
