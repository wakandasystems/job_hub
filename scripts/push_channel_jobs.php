<?php
require '/var/www/jobs/vendor/autoload.php';
$app = require_once '/var/www/jobs/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\SocialPublisherService;

// Zambia (ID 2) already completed — start from South Africa
$automations = [
    3  => ['country_id' => 53, 'name' => 'South Africa', 'offset' => 750],
    4  => ['country_id' => 46, 'name' => 'Nigeria',       'offset' => 0],
    5  => ['country_id' => 41, 'name' => 'Mauritius',     'offset' => 0],
    6  => ['country_id' => 58, 'name' => 'Tunisia',       'offset' => 0],
    7  => ['country_id' => 30, 'name' => 'Ghana',         'offset' => 0],
    8  => ['country_id' => 33, 'name' => 'Kenya',         'offset' => 0],
    9  => ['country_id' => 42, 'name' => 'Morocco',       'offset' => 0],
    10 => ['country_id' => 15, 'name' => 'Cameroon',      'offset' => 0],
    11 => ['country_id' => 59, 'name' => 'Uganda',        'offset' => 0],
];

$publisher = app(SocialPublisherService::class);

function sendWithRetry($publisher, $token, $chatId, $job, $automationId): bool
{
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        try {
            return $publisher->sendTelegramCopyPost($token, $chatId, $job, $automationId, false, true);
        } catch (Throwable $e) {
            echo "  [retry {$attempt}/3] " . $e->getMessage() . "\n";
            flush();
            sleep($attempt * 5); // 5s, 10s, 15s back-off
        }
    }
    return false;
}

foreach ($automations as $automationId => $info) {
    $automation = SocialAutomation::find($automationId);
    $token  = trim((string) ($automation->settings['bot_token'] ?? setting('telegram_bot_token')));
    $chatId = $automation->settings['chat_id'];

    $jobs = Job::with(['company', 'slugable', 'country'])
        ->where('status', 'published')
        ->where('country_id', $info['country_id'])
        ->where(function ($q) {
            $q->whereNull('expire_date')->orWhere('expire_date', '>=', now());
        })
        ->orderBy('created_at')
        ->skip($info['offset'])
        ->limit(100000)
        ->get();

    $total     = $jobs->count();
    $offsetMsg = $info['offset'] > 0 ? " (resuming from {$info['offset']})" : '';
    echo "[" . date('H:i:s') . "] {$info['name']}: sending {$total} jobs{$offsetMsg}...\n";
    flush();

    $sent = 0;
    foreach ($jobs as $job) {
        sendWithRetry($publisher, $token, $chatId, $job, $automationId);
        $sent++;
        if ($sent % 50 === 0) {
            echo "[" . date('H:i:s') . "] {$info['name']}: {$sent}/{$total}\n";
            flush();
        }
        usleep(1000000); // 1s — safer against Telegram rate limits
    }

    echo "[" . date('H:i:s') . "] {$info['name']}: done ({$sent} sent)\n";
    flush();
    sleep(3);
}

echo "[" . date('H:i:s') . "] All channels done.\n";
