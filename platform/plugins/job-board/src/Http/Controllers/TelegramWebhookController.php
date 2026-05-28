<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
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

        $message = $update['message'] ?? $update['channel_post'] ?? null;

        if (! $message) {
            return response('ok');
        }

        $text   = trim($message['text'] ?? '');
        $chatId = (string) ($message['chat']['id'] ?? '');

        if ($chatId === '') {
            return response('ok');
        }

        $command = strtolower(preg_replace('/@\S+/', '', $text));

        match (true) {
            in_array($command, ['/jobs', '/today']) => $this->sendDayJobs($chatId, $publisher, today()),
            $command === '/yesterday'               => $this->sendDayJobs($chatId, $publisher, today()->subDay()),
            $command === '/clear'                   => $this->clearChat($chatId),
            $command === '/reset'                   => $this->resetChat($chatId, $publisher),
            default                                 => null,
        };

        return response('ok');
    }

    // -------------------------------------------------------------------------
    // Resolve automation for a chat (token + country filter)
    // -------------------------------------------------------------------------

    protected function resolveAutomation(string $chatId): ?SocialAutomation
    {
        return SocialAutomation::query()
            ->where('platform', 'telegram')
            ->where('is_active', true)
            ->get()
            ->first(function ($a) use ($chatId) {
                return (string) ($a->settings['chat_id'] ?? '') === $chatId;
            });
    }

    // -------------------------------------------------------------------------
    // Send jobs for a given date (today or yesterday)
    // -------------------------------------------------------------------------

    protected function sendDayJobs(string $chatId, SocialPublisherService $publisher, $date): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $automation = $this->resolveAutomation($chatId);
        $countryId  = isset($automation?->settings['country_id'])
            ? (int) $automation->settings['country_id']
            : null;
        $automationId = $automation?->getKey();

        $query = Job::query()
            ->with(['company', 'slugable', 'country', 'currency', 'jobTypes'])
            ->where('status', JobStatusEnum::PUBLISHED)
            ->whereDate('created_at', $date)
            ->orderByDesc('created_at');

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        $jobs  = $query->get();
        $label = $date->isToday() ? 'today' : 'yesterday (' . $date->format('M j') . ')';

        if ($jobs->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => "📭 No published jobs for {$label}.",
            ]);
            return;
        }

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => "📅 Posting *{$jobs->count()} jobs* from {$label}…",
            'parse_mode' => 'Markdown',
        ]);

        foreach ($jobs as $job) {
            $generateImage = ! empty($automation?->settings['generate_image']);
            $publisher->sendTelegramCopyPost($token, $chatId, $job, $automationId, $generateImage);
            usleep(500000); // 0.5 s between posts to avoid Telegram flood limits
        }
    }

    // -------------------------------------------------------------------------
    // /clear — delete all logged bot messages from this chat
    // -------------------------------------------------------------------------

    protected function clearChat(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = DB::table('telegram_message_log')
            ->where('chat_id', $chatId)
            ->orderBy('id')
            ->get(['id', 'message_id']);

        if ($rows->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => '🗑 Nothing to clear — no tracked messages found.',
            ]);
            return;
        }

        $deleted = 0;
        $ids     = [];

        foreach ($rows as $row) {
            $resp = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/deleteMessage", [
                'chat_id'    => $chatId,
                'message_id' => $row->message_id,
            ]);
            if ($resp->successful() && data_get($resp->json(), 'result')) {
                $deleted++;
            }
            $ids[] = $row->id;
        }

        // Always clear the log — messages older than 48h can't be deleted via API anyway.
        DB::table('telegram_message_log')->whereIn('id', $ids)->delete();

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => "🗑 Removed {$deleted} of {$rows->count()} messages from chat.",
        ]);
    }

    // -------------------------------------------------------------------------
    // /reset — clear chat → today's jobs → yesterday's jobs
    // -------------------------------------------------------------------------

    protected function resetChat(string $chatId, SocialPublisherService $publisher): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => "🔄 Starting channel reset:\n1️⃣ Clearing old posts\n2️⃣ Today's jobs\n3️⃣ Yesterday's jobs",
        ]);

        $this->clearChat($chatId);
        $this->sendDayJobs($chatId, $publisher, today());
        $this->sendDayJobs($chatId, $publisher, today()->subDay());
    }
}
