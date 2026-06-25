<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\SocialAutomation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Shared Whapi WhatsApp HTTP sender — extracted so the Auto CV Bot and the Sales Agent
 * poster-sending flow call the same underlying code instead of duplicating it.
 */
class WhapiSenderService
{
    /** @return array{0: ?string, 1: ?string} [token, gatewayUrl] */
    public function getCredentials(): array
    {
        $automation = SocialAutomation::where('platform', 'whapi')->where('is_active', true)->first();

        if (! $automation) {
            return [null, null];
        }

        $settings = $automation->settings ?? [];
        $token = SocialAutomation::whapiToken($automation);
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        return $token ? [$token, $gatewayUrl] : [null, null];
    }

    public function sendText(string $whatsappNumber, string $body, ?string &$errorMessage = null): bool
    {
        [$token, $gatewayUrl] = $this->getCredentials();

        if (! $token) {
            $errorMessage = 'No active Whapi automation configured.';

            return false;
        }

        $jid = preg_replace('/\D/', '', $whatsappNumber) . '@s.whatsapp.net';

        try {
            $response = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                'to' => $jid,
                'body' => $body,
            ]);
        } catch (Throwable $exception) {
            $errorMessage = 'WhatsApp send exception: ' . $exception->getMessage();

            return false;
        }

        if (! $response->successful()) {
            $errorMessage = 'WhatsApp send failed: HTTP ' . $response->status() . ' ' . Str::limit($response->body(), 250, '');

            return false;
        }

        return true;
    }

    public function sendImage(string $whatsappNumber, string $imagePath, string $caption, ?string &$errorMessage = null): bool
    {
        [$token, $gatewayUrl] = $this->getCredentials();

        if (! $token) {
            $errorMessage = 'No active Whapi automation configured.';

            return false;
        }

        if (! is_file($imagePath)) {
            $errorMessage = 'Image not found.';

            return false;
        }

        $jid = preg_replace('/\D/', '', $whatsappNumber) . '@s.whatsapp.net';
        $mime = mime_content_type($imagePath) ?: 'image/jpeg';

        try {
            $response = Http::timeout(30)->withToken($token)->post("{$gatewayUrl}/messages/image", [
                'to' => $jid,
                'media' => 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($imagePath)),
                'caption' => $caption,
            ]);
        } catch (Throwable $exception) {
            $errorMessage = 'WhatsApp image send exception: ' . $exception->getMessage();

            return false;
        }

        if (! $response->successful()) {
            $errorMessage = 'WhatsApp image send failed: HTTP ' . $response->status() . ' ' . Str::limit($response->body(), 250, '');

            return false;
        }

        return true;
    }
}
