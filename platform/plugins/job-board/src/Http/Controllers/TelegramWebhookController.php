<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, SocialPublisherService $publisher)
    {
        $secret = setting('telegram_webhook_secret', '');
        if ($secret !== '' && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return response('', 403);
        }

        $update = $request->json()->all();

        // Support both private/group messages and channel posts.
        $message = $update['message'] ?? $update['channel_post'] ?? null;

        if (! $message) {
            return response('ok');
        }

        $text   = trim($message['text'] ?? '');
        $chatId = (string) ($message['chat']['id'] ?? '');

        if ($chatId === '') {
            return response('ok');
        }

        // /jobs  or  /jobs@BotName  ← handle both forms
        if ($text === '/jobs' || preg_match('#^/jobs(@\S+)?$#i', $text)) {
            $this->sendLastJobs($chatId, $publisher);
        }

        return response('ok');
    }

    protected function sendLastJobs(string $chatId, SocialPublisherService $publisher): void
    {
        $token = setting('telegram_bot_token', '');

        if ($token === '') {
            return;
        }

        $jobs = Job::query()
            ->where('status', JobStatusEnum::PUBLISHED)
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        if ($jobs->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => 'No published jobs found.',
            ]);

            return;
        }

        foreach ($jobs as $job) {
            $publisher->sendTelegramCopyPost($token, $chatId, $job);
        }
    }
}
