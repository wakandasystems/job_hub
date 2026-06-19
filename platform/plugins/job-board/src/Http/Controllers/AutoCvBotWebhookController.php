<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\JobBoard\Services\AutoCvBotService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AutoCvBotWebhookController extends Controller
{
    public function handle(Request $request, string $secret, AutoCvBotService $service)
    {
        $expected = (string) setting('auto_cv_webhook_secret', '');

        if ($expected === '' || ! hash_equals($expected, $secret)) {
            return response('', 403);
        }

        foreach ((array) $request->json('messages', []) as $message) {
            if (! is_array($message)) {
                continue;
            }

            if ($message['from_me'] ?? false) {
                continue;
            }

            $chatId = (string) ($message['chat_id'] ?? '');
            $digits = preg_replace('/\D/', '', str_replace('@s.whatsapp.net', '', $chatId)) ?? '';
            $messageId = $message['id'] ?? null;
            $type = (string) ($message['type'] ?? '');

            if ($type === 'text') {
                $body = (string) ($message['text']['body'] ?? '');
                $service->handleInboundReply($digits, $body, $messageId ? (string) $messageId : null);

                continue;
            }

            if (
                in_array($type, ['image', 'document', 'file'], true)
                || isset($message['image'])
                || isset($message['document'])
                || isset($message['file'])
                || isset($message['media'])
            ) {
                $service->handleInboundAttachment($digits, $message, $messageId ? (string) $messageId : null);
            }
        }

        return response()->json(['ok' => true]);
    }
}
