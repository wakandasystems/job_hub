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
            'allowed_updates' => ['message', 'channel_post', 'callback_query'],
        ]);

        $json = $response->json();

        if ($response->successful() && ($json['ok'] ?? false)) {
            $this->components->info('Webhook registered successfully.');
            $this->line("  URL: {$webhookUrl}");
            $this->registerBotCommands($token);

            return 0;
        }

        $this->components->error('Failed to register webhook: ' . ($json['description'] ?? 'unknown error'));

        return 1;
    }

    protected function registerBotCommands(string $token): void
    {
        $commands = [
            ['command' => 'menu',          'description' => 'Show interactive button menu'],
            ['command' => 'today',         'description' => "Post today's new jobs"],
            ['command' => 'yesterday',     'description' => "Post yesterday's jobs"],
            ['command' => 'hotjobs',       'description' => 'Top jobs by views (great for social)'],
            ['command' => 'fresh',         'description' => 'Jobs posted in the last 2 hours'],
            ['command' => 'topcompanies',  'description' => 'Companies hiring most this week'],
            ['command' => 'report',        'description' => 'Crawler stats: today/2d/week/month/all'],
            ['command' => 'reportpdf',     'description' => 'Crawler report as PDF'],
            ['command' => 'countries',     'description' => 'Jobs by country'],
            ['command' => 'categories',    'description' => 'Top job categories'],
            ['command' => 'trend',         'description' => '14-day job volume trend chart'],
            ['command' => 'expiring',      'description' => 'Jobs expiring in next 7 days'],
            ['command' => 'deadcrawlers',  'description' => 'Crawlers silent for 3+ days'],
            ['command' => 'pendingapps',   'description' => 'Pending job applications'],
            ['command' => 'newaccounts',   'description' => 'New registrations (seekers & employers)'],
            ['command' => 'apptrend',      'description' => '14-day application trend chart'],
            ['command' => 'traffic',       'description' => 'Job view traffic by source'],
            ['command' => 'reset',         'description' => 'Clear + reload today + yesterday'],
            ['command' => 'clear',         'description' => 'Delete all tracked bot messages'],
        ];

        $resp = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/setMyCommands", [
            'commands' => $commands,
        ]);

        if ($resp->successful() && ($resp->json()['ok'] ?? false)) {
            $this->components->info('Bot command menu registered (' . count($commands) . ' commands).');
        } else {
            $this->components->warn('Could not set bot commands: ' . ($resp->json()['description'] ?? 'unknown'));
        }
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
