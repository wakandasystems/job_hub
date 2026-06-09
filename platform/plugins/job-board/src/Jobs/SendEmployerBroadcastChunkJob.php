<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Models\SocialBroadcast;
use Botble\JobBoard\Supports\EmployerContactAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendEmployerBroadcastChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CHUNK_SIZE = 50;

    public int $tries = 2;
    public array $backoff = [60];

    public function __construct(
        private readonly int $broadcastId,
        private readonly int $offset = 0,
    ) {}

    public function handle(EmployerContactAudience $audience): void
    {
        $broadcast = SocialBroadcast::find($this->broadcastId);

        if (! $broadcast || ! in_array($broadcast->status, ['pending', 'scheduled', 'sending'], true)) {
            return;
        }

        $automation = SocialAutomation::query()
            ->where('platform', 'whapi')
            ->where('is_active', true)
            ->first();
        $settings = $automation?->settings ?? [];
        $token = trim((string) ($settings['token'] ?? ''));
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        if ($token === '') {
            $broadcast->update([
                'status' => 'failed',
                'results' => [['platform' => 'whapi', 'name' => 'Employer WhatsApp', 'success' => false, 'error' => 'No active Whapi token configured.']],
            ]);

            return;
        }

        $contacts = $audience->phones();
        $total = $contacts->count();
        $chunk = $contacts->slice($this->offset, self::CHUNK_SIZE);
        $imageUrl = $broadcast->image_path ? Storage::disk('public')->url($broadcast->image_path) : null;
        $sent = 0;
        $failed = 0;

        $broadcast->update([
            'status' => 'sending',
            'recipient_count' => $total,
        ]);

        foreach ($chunk as $contact) {
            $endpoint = $imageUrl ? 'messages/image' : 'messages/text';
            $payload = $imageUrl
                ? ['to' => $contact->phone . '@s.whatsapp.net', 'media' => $imageUrl, 'caption' => $broadcast->message]
                : ['to' => $contact->phone . '@s.whatsapp.net', 'body' => $broadcast->message];

            try {
                $response = Http::timeout(20)
                    ->withToken($token)
                    ->post("{$gatewayUrl}/{$endpoint}", $payload);

                $response->successful() ? $sent++ : $failed++;
            } catch (Throwable) {
                $failed++;
            }
        }

        $broadcast->increment('sent_count', $sent);
        $broadcast->increment('failed_count', $failed);

        $nextOffset = $this->offset + $chunk->count();
        if ($nextOffset < $total) {
            self::dispatch($broadcast->getKey(), $nextOffset);
            return;
        }

        $broadcast->refresh();
        $broadcast->update([
            'status' => $broadcast->sent_count > 0 ? 'sent' : 'failed',
            'sent_at' => now(),
            'results' => [[
                'platform' => 'whapi',
                'name' => 'Employer WhatsApp',
                'success' => $broadcast->sent_count > 0,
                'sent' => $broadcast->sent_count,
                'failed' => $broadcast->failed_count,
            ]],
        ]);
    }
}
