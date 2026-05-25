<?php

namespace Botble\JobBoard\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RegisterTelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:register-webhook {--remove : Remove the webhook instead of registering}';

    protected $description = 'Register (or remove) the Telegram bot webhook for incoming /jobs commands';

    public function handle(): int
    {
        $token = setting('telegram_bot_token', '');

        if ($token === '') {
            $this->components->error('telegram_bot_token is not set in site settings.');

            return 1;
        }

        if ($this->option('remove')) {
            return $this->removeWebhook($token);
        }

        return $this->registerWebhook($token);
    }

    protected function registerWebhook(string $token): int
    {
        // Generate and persist a webhook secret so Telegram requests can be verified.
        $secret = setting('telegram_webhook_secret', '');

        if ($secret === '') {
            $secret = Str::random(32);
            setting()->set('telegram_webhook_secret', $secret)->save();
            $this->components->info('Generated new webhook secret and saved to settings.');
        }

        $webhookUrl = route('public.telegram-webhook');

        $response = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url'          => $webhookUrl,
            'secret_token' => $secret,
            // Only receive the update types we care about.
            'allowed_updates' => ['message', 'channel_post'],
        ]);

        $json = $response->json();

        if ($response->successful() && ($json['ok'] ?? false)) {
            $this->components->info('Webhook registered successfully.');
            $this->line("  URL: {$webhookUrl}");

            return 0;
        }

        $this->components->error('Failed to register webhook: ' . ($json['description'] ?? 'unknown error'));

        return 1;
    }

    protected function removeWebhook(string $token): int
    {
        $response = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/deleteWebhook");
        $json     = $response->json();

        if ($response->successful() && ($json['ok'] ?? false)) {
            $this->components->info('Webhook removed.');

            return 0;
        }

        $this->components->error('Failed to remove webhook: ' . ($json['description'] ?? 'unknown error'));

        return 1;
    }
}
