<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Jobs\SendTelegramPdfReportJob;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\SocialPublisherService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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

        // ── Inline-keyboard button press ──────────────────────────────────────
        if (isset($update['callback_query'])) {
            $cq     = $update['callback_query'];
            $chatId = (string) ($cq['message']['chat']['id'] ?? '');
            $data   = $cq['data'] ?? '';
            $cqId   = $cq['id'] ?? '';

            if ($chatId !== '') {
                $this->answerCallback($cqId);
                $this->dispatchCallback($chatId, $data, $publisher);
            }

            return response('ok');
        }

        // ── Text commands ────────────────────────────────────────────────────
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
            in_array($command, ['/jobs', '/today'])  => $this->sendDayJobs($chatId, $publisher, today()),
            $command === '/yesterday'                 => $this->sendDayJobs($chatId, $publisher, today()->subDay()),
            $command === '/clear'                     => $this->clearChat($chatId),
            $command === '/reset'                     => $this->resetChat($chatId, $publisher),
            $command === '/report'                    => $this->sendCrawlerReport($chatId),
            $command === '/reportpdf'                 => $this->sendCrawlerReportPdf($chatId),
            $command === '/countries'                 => $this->sendCountryReport($chatId),
            $command === '/categories'                => $this->sendCategoryReport($chatId),
            $command === '/companies'                 => $this->sendCompanyReport($chatId),
            $command === '/trend'                     => $this->sendTrendReport($chatId),
            $command === '/expiring'                  => $this->sendExpiringReport($chatId),
            $command === '/deadcrawlers'              => $this->sendDeadCrawlers($chatId),
            $command === '/pendingapps'               => $this->sendPendingApplications($chatId),
            $command === '/hotjobs'                   => $this->sendHotJobs($chatId),
            $command === '/fresh'                     => $this->sendFreshListings($chatId),
            $command === '/topcompanies'              => $this->sendTopCompanies($chatId),
            $command === '/newaccounts'               => $this->sendNewAccounts($chatId),
            $command === '/apptrend'                  => $this->sendApplicationTrend($chatId),
            $command === '/traffic'                   => $this->sendTrafficReport($chatId),
            in_array($command, ['/help', '/menu'])    => $this->sendMenu($chatId),
            default                                   => null,
        };

        return response('ok');
    }

    // -------------------------------------------------------------------------
    // Inline keyboard helpers
    // -------------------------------------------------------------------------

    protected function answerCallback(string $callbackQueryId, string $text = ''): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $payload = ['callback_query_id' => $callbackQueryId];
        if ($text !== '') {
            $payload['text'] = $text;
        }

        Http::timeout(5)->post("https://api.telegram.org/bot{$token}/answerCallbackQuery", $payload);
    }

    protected function dispatchCallback(string $chatId, string $data, SocialPublisherService $publisher): void
    {
        match ($data) {
            'today'           => $this->sendDayJobs($chatId, $publisher, today()),
            'yesterday'       => $this->sendDayJobs($chatId, $publisher, today()->subDay()),
            'clear'           => $this->clearChat($chatId),
            'reset'           => $this->resetChat($chatId, $publisher),
            'report'          => $this->sendCrawlerReport($chatId),
            'reportpdf'       => $this->sendCrawlerReportPdf($chatId),
            'report_country'  => $this->sendCountryReport($chatId),
            'report_category' => $this->sendCategoryReport($chatId),
            'report_company'  => $this->sendCompanyReport($chatId),
            'report_trend'    => $this->sendTrendReport($chatId),
            'expiring'        => $this->sendExpiringReport($chatId),
            'dead_crawlers'   => $this->sendDeadCrawlers($chatId),
            'pending_apps'    => $this->sendPendingApplications($chatId),
            'hot_jobs'        => $this->sendHotJobs($chatId),
            'fresh'           => $this->sendFreshListings($chatId),
            'top_companies'   => $this->sendTopCompanies($chatId),
            'new_accounts'    => $this->sendNewAccounts($chatId),
            'app_trend'       => $this->sendApplicationTrend($chatId),
            'traffic'         => $this->sendTrafficReport($chatId),
            default           => null,
        };
    }

    // -------------------------------------------------------------------------
    // /menu | /help — inline keyboard
    // -------------------------------------------------------------------------

    protected function sendMenu(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $keyboard = [
            // ── Post jobs ──────────────────────────────────────────────
            [
                ['text' => '📅 Today',           'callback_data' => 'today'],
                ['text' => '📅 Yesterday',        'callback_data' => 'yesterday'],
            ],
            [
                ['text' => '🔄 Reset',            'callback_data' => 'reset'],
                ['text' => '🗑 Clear',             'callback_data' => 'clear'],
            ],
            // ── Social content ─────────────────────────────────────────
            [
                ['text' => '🔥 Hot Jobs',         'callback_data' => 'hot_jobs'],
                ['text' => '🆕 Fresh (2h)',        'callback_data' => 'fresh'],
            ],
            [
                ['text' => '🏆 Top Companies',    'callback_data' => 'top_companies'],
                ['text' => '📋 Expiring Soon',    'callback_data' => 'expiring'],
            ],
            // ── Reports ────────────────────────────────────────────────
            [
                ['text' => '📊 Crawlers',         'callback_data' => 'report'],
                ['text' => '📄 Crawlers PDF',     'callback_data' => 'reportpdf'],
            ],
            [
                ['text' => '🌍 Countries',        'callback_data' => 'report_country'],
                ['text' => '🏷 Categories',       'callback_data' => 'report_category'],
            ],
            [
                ['text' => '🏢 Companies',        'callback_data' => 'report_company'],
                ['text' => '📈 Job Trend',        'callback_data' => 'report_trend'],
            ],
            [
                ['text' => '💀 Dead Crawlers',    'callback_data' => 'dead_crawlers'],
                ['text' => '📬 Pending Apps',     'callback_data' => 'pending_apps'],
            ],
            // ── Analytics ──────────────────────────────────────────────
            [
                ['text' => '👥 New Accounts',     'callback_data' => 'new_accounts'],
                ['text' => '📥 App Trend',        'callback_data' => 'app_trend'],
            ],
            [
                ['text' => '🔍 Traffic Sources',  'callback_data' => 'traffic'],
            ],
        ];

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'      => $chatId,
            'text'         => "<b>📋 Wakanda Jobs Bot</b>\nChoose an option:",
            'parse_mode'   => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Shared: fetch crawler report rows
    // -------------------------------------------------------------------------

    protected function crawlerReportRows(): \Illuminate\Support\Collection
    {
        $zId = $this->zambiaId();

        return DB::table('jb_job_crawlers as c')
            ->leftJoin('jb_jobs as j', function ($join) use ($zId) {
                $join->on('j.crawler_id', '=', 'c.id')
                     ->where('j.country_id', $zId);
            })
            ->select([
                'c.id',
                'c.name',
                'c.is_active',
                DB::raw("'Zambia' AS country"),
                DB::raw("SUM(CASE WHEN DATE(j.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS two_days"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN 1 ELSE 0 END) AS week"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) THEN 1 ELSE 0 END) AS month"),
                DB::raw("COUNT(j.id) AS all_time"),
            ])
            ->groupBy('c.id', 'c.name', 'c.is_active')
            ->having(DB::raw("COUNT(j.id)"), '>', 0)
            ->orderByDesc('today')
            ->orderByDesc('week')
            ->get();
    }

    // -------------------------------------------------------------------------
    // All reports are scoped to Zambia — returns the Zambia country ID
    // -------------------------------------------------------------------------

    protected function zambiaId(): int
    {
        static $id = null;
        if ($id === null) {
            $id = (int) (DB::table('countries')->whereRaw("LOWER(name) = 'zambia'")->value('id') ?: 7);
        }
        return $id;
    }

    // -------------------------------------------------------------------------
    // Resolve automation for a chat
    // -------------------------------------------------------------------------

    protected function resolveAutomation(string $chatId): ?SocialAutomation
    {
        return SocialAutomation::query()
            ->where('platform', 'telegram')
            ->where('is_active', true)
            ->get()
            ->first(fn ($a) => (string) ($a->settings['chat_id'] ?? '') === $chatId);
    }

    // -------------------------------------------------------------------------
    // /today | /yesterday — post jobs
    // -------------------------------------------------------------------------

    protected function sendDayJobs(string $chatId, SocialPublisherService $publisher, $date): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $automation   = $this->resolveAutomation($chatId);
        $countryId    = isset($automation?->settings['country_id']) ? (int) $automation->settings['country_id'] : null;
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
            $publisher->sendTelegramCopyPost($token, $chatId, $job, $automationId, ! empty($automation?->settings['generate_image']));
            usleep(500000);
        }
    }

    // -------------------------------------------------------------------------
    // /clear
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
                'chat_id' => $chatId, 'message_id' => $row->message_id,
            ]);
            if ($resp->successful() && data_get($resp->json(), 'result')) {
                $deleted++;
            }
            $ids[] = $row->id;
        }

        DB::table('telegram_message_log')->whereIn('id', $ids)->delete();

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => "🗑 Removed {$deleted} of {$rows->count()} messages from chat.",
        ]);
    }

    // -------------------------------------------------------------------------
    // /reset
    // -------------------------------------------------------------------------

    protected function resetChat(string $chatId, SocialPublisherService $publisher): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => "🔄 Starting reset:\n1️⃣ Clearing old posts\n2️⃣ Today's jobs\n3️⃣ Yesterday's jobs",
        ]);

        $this->clearChat($chatId);
        $this->sendDayJobs($chatId, $publisher, today());
        $this->sendDayJobs($chatId, $publisher, today()->subDay());
    }

    // -------------------------------------------------------------------------
    // /report — crawler stats table
    // -------------------------------------------------------------------------

    protected function sendCrawlerReport(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = $this->crawlerReportRows();

        if ($rows->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId, 'text' => '📊 No crawlers with jobs yet.',
            ]);
            return;
        }

        $cW = 12;
        $nW = max(20, $rows->max(fn ($r) => mb_strlen($r->name)));

        $header  = sprintf("%-{$cW}s  %-{$nW}s  %5s  %5s  %5s  %5s  %7s",
            'Country', 'Crawler', 'Today', '2Days', 'Week', 'Month', 'AllTime');
        $divider = str_repeat('─', $cW + $nW + 44);
        $totals  = array_fill_keys(['today', 'two_days', 'week', 'month', 'all_time'], 0);
        $lines   = [];

        foreach ($rows as $row) {
            [$today, $two_days, $week, $month, $all] = [
                (int)$row->today, (int)$row->two_days, (int)$row->week, (int)$row->month, (int)$row->all_time,
            ];
            foreach (['today', 'two_days', 'week', 'month', 'all_time'] as $k) {
                $totals[$k] += (int) $row->{$k};
            }

            $country = $row->country ?? '—';
            if (mb_strlen($country) > $cW) {
                $country = mb_substr($country, 0, $cW - 1) . '.';
            }
            $name = mb_strlen($row->name) > $nW ? mb_substr($row->name, 0, $nW - 1) . '…' : $row->name;

            $lines[] = sprintf("%s %-{$cW}s  %-{$nW}s  %5d  %5d  %5d  %5d  %7d",
                $row->is_active ? '🟢' : '🔴', $country, $name,
                $today, $two_days, $week, $month, $all);
        }

        $totalLine = sprintf("   %-{$cW}s  %-{$nW}s  %5d  %5d  %5d  %5d  %7d",
            '', 'TOTAL', $totals['today'], $totals['two_days'], $totals['week'], $totals['month'], $totals['all_time']);

        $table = implode("\n", [$header, $divider, implode("\n", $lines), $divider, $totalLine]);

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => "<b>📊 Crawler Report — Zambia</b> — " . now()->format('j M Y') . "\n\n<pre>{$table}</pre>",
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /reportpdf — dispatched to queue so webhook responds immediately
    // -------------------------------------------------------------------------

    protected function sendCrawlerReportPdf(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId, 'text' => '⏳ Generating PDF — will arrive in a few seconds…',
        ]);

        SendTelegramPdfReportJob::dispatch($chatId, 'crawlers')->onQueue('default');
    }

    // -------------------------------------------------------------------------
    // /countries — jobs by country
    // -------------------------------------------------------------------------

    protected function sendCountryReport(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = DB::table('jb_jobs as j')
            ->join('countries as co', 'co.id', '=', 'j.country_id')
            ->select([
                'co.name as country',
                DB::raw("SUM(CASE WHEN DATE(j.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS two_days"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN 1 ELSE 0 END) AS week"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) THEN 1 ELSE 0 END) AS month"),
                DB::raw("COUNT(j.id) AS all_time"),
            ])
            ->whereNotNull('j.crawler_id')
            ->where('j.country_id', $this->zambiaId())
            ->groupBy('co.id', 'co.name')
            ->having(DB::raw("COUNT(j.id)"), '>', 0)
            ->orderByDesc('today')
            ->orderByDesc('week')
            ->get();

        if ($rows->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId, 'text' => '🌍 No country data yet.',
            ]);
            return;
        }

        $nW      = max(12, $rows->max(fn ($r) => mb_strlen($r->country)));
        $header  = sprintf("%-{$nW}s  %5s  %5s  %5s  %5s  %7s", 'Country', 'Today', '2Days', 'Week', 'Month', 'AllTime');
        $divider = str_repeat('─', $nW + 36);
        $totals  = array_fill_keys(['today', 'two_days', 'week', 'month', 'all_time'], 0);
        $lines   = [];

        foreach ($rows as $row) {
            foreach (['today', 'two_days', 'week', 'month', 'all_time'] as $k) {
                $totals[$k] += (int) $row->{$k};
            }
            $name    = mb_strlen($row->country) > $nW ? mb_substr($row->country, 0, $nW - 1) . '…' : $row->country;
            $lines[] = sprintf("%-{$nW}s  %5d  %5d  %5d  %5d  %7d",
                $name, $row->today, $row->two_days, $row->week, $row->month, $row->all_time);
        }

        $totalLine = sprintf("%-{$nW}s  %5d  %5d  %5d  %5d  %7d",
            'TOTAL', $totals['today'], $totals['two_days'], $totals['week'], $totals['month'], $totals['all_time']);

        $table = implode("\n", [$header, $divider, implode("\n", $lines), $divider, $totalLine]);

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => "<b>🌍 Country Report — Zambia</b> — " . now()->format('j M Y') . "\n\n<pre>{$table}</pre>",
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /categories — top job categories
    // -------------------------------------------------------------------------

    protected function sendCategoryReport(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = DB::table('jb_jobs as j')
            ->join('jb_jobs_categories as jc', 'jc.job_id', '=', 'j.id')
            ->join('jb_categories as cat', 'cat.id', '=', 'jc.category_id')
            ->select([
                'cat.name as category',
                DB::raw("SUM(CASE WHEN DATE(j.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN 1 ELSE 0 END) AS week"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) THEN 1 ELSE 0 END) AS month"),
                DB::raw("COUNT(j.id) AS all_time"),
            ])
            ->whereNotNull('j.crawler_id')
            ->where('j.country_id', $this->zambiaId())
            ->groupBy('cat.id', 'cat.name')
            ->having(DB::raw("COUNT(j.id)"), '>', 0)
            ->orderByDesc('week')
            ->orderByDesc('all_time')
            ->limit(25)
            ->get();

        if ($rows->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId, 'text' => '🏷 No category data yet.',
            ]);
            return;
        }

        $nW      = max(20, $rows->max(fn ($r) => mb_strlen($r->category)));
        $header  = sprintf("%-{$nW}s  %5s  %5s  %5s  %7s", 'Category', 'Today', 'Week', 'Month', 'AllTime');
        $divider = str_repeat('─', $nW + 30);
        $lines   = [];

        foreach ($rows as $row) {
            $name    = mb_strlen($row->category) > $nW ? mb_substr($row->category, 0, $nW - 1) . '…' : $row->category;
            $lines[] = sprintf("%-{$nW}s  %5d  %5d  %5d  %7d",
                $name, $row->today, $row->week, $row->month, $row->all_time);
        }

        $table = implode("\n", [$header, $divider, implode("\n", $lines)]);

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => "<b>🏷 Category Report — Zambia</b> — " . now()->format('j M Y') . " (top 25)\n\n<pre>{$table}</pre>",
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /companies — most active hiring companies
    // -------------------------------------------------------------------------

    protected function sendCompanyReport(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = DB::table('jb_jobs as j')
            ->join('jb_companies as co', 'co.id', '=', 'j.company_id')
            ->select([
                'co.name as company',
                DB::raw("SUM(CASE WHEN DATE(j.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN 1 ELSE 0 END) AS week"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) THEN 1 ELSE 0 END) AS month"),
                DB::raw("COUNT(j.id) AS all_time"),
            ])
            ->whereNotNull('j.crawler_id')
            ->where('j.country_id', $this->zambiaId())
            ->groupBy('co.id', 'co.name')
            ->orderByDesc('month')
            ->orderByDesc('all_time')
            ->limit(25)
            ->get();

        if ($rows->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId, 'text' => '🏢 No company data yet.',
            ]);
            return;
        }

        $nW      = max(20, min(30, $rows->max(fn ($r) => mb_strlen($r->company))));
        $header  = sprintf("%-{$nW}s  %5s  %5s  %5s  %7s", 'Company', 'Today', 'Week', 'Month', 'AllTime');
        $divider = str_repeat('─', $nW + 30);
        $lines   = [];

        foreach ($rows as $row) {
            $name    = mb_strlen($row->company) > $nW ? mb_substr($row->company, 0, $nW - 1) . '…' : $row->company;
            $lines[] = sprintf("%-{$nW}s  %5d  %5d  %5d  %7d",
                $name, $row->today, $row->week, $row->month, $row->all_time);
        }

        $table = implode("\n", [$header, $divider, implode("\n", $lines)]);

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => "<b>🏢 Top Hiring Companies — Zambia</b> — " . now()->format('j M Y') . " (top 25 by month)\n\n<pre>{$table}</pre>",
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /trend — daily job count for the last 14 days with unicode bar chart
    // -------------------------------------------------------------------------

    protected function sendTrendReport(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $data = DB::table('jb_jobs')
            ->select(DB::raw("DATE(created_at) as day, COUNT(*) as jobs"))
            ->whereNotNull('crawler_id')
            ->where('country_id', $this->zambiaId())
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('day')
            ->pluck('jobs', 'day');

        // Fill all 14 days (including zero-job days)
        $period = CarbonPeriod::create(today()->subDays(13), today());
        $rows   = [];
        foreach ($period as $date) {
            $rows[$date->format('Y-m-d')] = (int) ($data[$date->format('Y-m-d')] ?? 0);
        }

        $maxJobs  = max(1, max($rows));
        $barWidth = 25;
        $lines    = [];
        $total    = array_sum($rows);

        foreach (array_reverse($rows, true) as $day => $jobs) {
            $bars    = (int) round($jobs / $maxJobs * $barWidth);
            $bar     = str_repeat('█', $bars) . str_repeat('░', $barWidth - $bars);
            $label   = Carbon::parse($day)->format('d M');
            $lines[] = sprintf("%s  %4d  %s", $label, $jobs, $bar);
        }

        $header = sprintf("%-6s  %4s  %s", 'Date', 'Jobs', str_repeat('─', $barWidth));
        $table  = implode("\n", [$header, implode("\n", $lines)]);
        $avg    = round($total / 14);

        $text = "<b>📈 Daily Job Trend — Zambia</b> — Last 14 Days\n"
            . "Total: <b>{$total}</b>  ·  Avg/day: <b>{$avg}</b>\n\n"
            . "<pre>{$table}</pre>";

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /expiring — jobs expiring in next 7 days, by country
    // -------------------------------------------------------------------------

    protected function sendExpiringReport(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = DB::table('jb_jobs as j')
            ->leftJoin('countries as co', 'co.id', '=', 'j.country_id')
            ->select([
                DB::raw("COALESCE(co.name, 'Unknown') AS country"),
                DB::raw("SUM(CASE WHEN j.expire_date = CURDATE() THEN 1 ELSE 0 END) AS today"),
                DB::raw("SUM(CASE WHEN j.expire_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY) THEN 1 ELSE 0 END) AS in_3d"),
                DB::raw("COUNT(*) AS in_7d"),
            ])
            ->where('j.status', 'published')
            ->where('j.country_id', $this->zambiaId())
            ->where('j.never_expired', 0)
            ->whereBetween('j.expire_date', [now()->toDateString(), now()->addDays(6)->toDateString()])
            ->groupBy('co.id', 'co.name')
            ->orderByDesc('in_7d')
            ->get();

        if ($rows->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => '📋 No jobs expiring in the next 7 days.',
            ]);
            return;
        }

        $nW     = max(12, $rows->max(fn ($r) => mb_strlen($r->country)));
        $header = sprintf("%-{$nW}s  %6s  %5s  %5s", 'Country', 'Today', '3 Days', '7 Days');
        $divider = str_repeat('─', $nW + 22);
        $lines  = [];
        $totals = ['today' => 0, 'in_3d' => 0, 'in_7d' => 0];

        foreach ($rows as $row) {
            $totals['today'] += (int) $row->today;
            $totals['in_3d'] += (int) $row->in_3d;
            $totals['in_7d'] += (int) $row->in_7d;
            $name    = mb_strlen($row->country) > $nW ? mb_substr($row->country, 0, $nW - 1) . '…' : $row->country;
            $lines[] = sprintf("%-{$nW}s  %6d  %5d  %5d", $name, $row->today, $row->in_3d, $row->in_7d);
        }

        $table = implode("\n", [$header, $divider, implode("\n", $lines), $divider,
            sprintf("%-{$nW}s  %6d  %5d  %5d", 'TOTAL', $totals['today'], $totals['in_3d'], $totals['in_7d'])]);

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => "<b>📋 Expiring Jobs — Zambia</b> — next 7 days\n\n<pre>{$table}</pre>",
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /deadcrawlers — active crawlers silent for 3+ days
    // -------------------------------------------------------------------------

    protected function sendDeadCrawlers(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = DB::table('jb_job_crawlers as c')
            ->leftJoin('jb_jobs as j', 'j.crawler_id', '=', 'c.id')
            ->select([
                'c.name',
                'c.last_run_at',
                'c.last_status',
                DB::raw("MAX(j.created_at) AS last_job_at"),
                DB::raw("DATEDIFF(NOW(), MAX(j.created_at)) AS days_silent"),
            ])
            ->where('c.is_active', 1)
            ->groupBy('c.id', 'c.name', 'c.last_run_at', 'c.last_status')
            ->havingRaw("days_silent >= 3 OR MAX(j.created_at) IS NULL")
            ->orderByDesc('days_silent')
            ->get();

        if ($rows->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => '✅ All active crawlers are healthy — no silent crawlers.',
            ]);
            return;
        }

        $lines = ["<b>💀 Dead Crawlers</b> — active but no new jobs for 3+ days\n"];
        foreach ($rows as $row) {
            $days     = $row->days_silent !== null ? (int) $row->days_silent . 'd silent' : 'never ran';
            $lastJob  = $row->last_job_at ? Carbon::parse($row->last_job_at)->format('d M') : '—';
            $status   = $row->last_status === 'failed' ? ' ⚠️' : '';
            $lines[]  = "• <b>{$row->name}</b>{$status}\n  Last job: {$lastJob} · {$days}";
        }

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => implode("\n\n", $lines),
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /pendingapps — pending job applications summary
    // -------------------------------------------------------------------------

    protected function sendPendingApplications(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $zId = $this->zambiaId();

        $summary = DB::table('jb_applications as a')
            ->join('jb_jobs as j', 'j.id', '=', 'a.job_id')
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN DATE(a.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN a.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN 1 ELSE 0 END) AS week,
                SUM(CASE WHEN a.is_external_apply = 0 THEN 1 ELSE 0 END) AS internal,
                SUM(CASE WHEN a.is_external_apply = 1 THEN 1 ELSE 0 END) AS external
            ")
            ->where('a.status', 'pending')
            ->where('j.country_id', $zId)
            ->first();

        $topJobs = DB::table('jb_applications as a')
            ->join('jb_jobs as j', 'j.id', '=', 'a.job_id')
            ->join('jb_companies as co', 'co.id', '=', 'j.company_id')
            ->select(['j.name as job', 'co.name as company', DB::raw("COUNT(a.id) AS cnt")])
            ->where('a.status', 'pending')
            ->where('j.country_id', $zId)
            ->groupBy('j.id', 'j.name', 'co.name')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        $text = "<b>📬 Pending Applications — Zambia</b>\n\n"
            . "Total pending: <b>{$summary->total}</b>\n"
            . "Today: <b>{$summary->today}</b>  ·  This week: <b>{$summary->week}</b>\n"
            . "Internal (CV): <b>{$summary->internal}</b>  ·  External: <b>{$summary->external}</b>\n\n";

        if ($topJobs->isNotEmpty()) {
            $text .= "<b>Top jobs with pending apps:</b>\n";
            foreach ($topJobs as $row) {
                $text .= "• {$row->job} @ {$row->company} — <b>{$row->cnt}</b>\n";
            }
        }

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /hotjobs — most viewed published jobs
    // -------------------------------------------------------------------------

    protected function sendHotJobs(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = DB::table('jb_jobs as j')
            ->join('jb_companies as co', 'co.id', '=', 'j.company_id')
            ->leftJoin('countries as c', 'c.id', '=', 'j.country_id')
            ->select(['j.name', 'co.name as company', 'j.views', 'c.name as country'])
            ->where('j.status', 'published')
            ->where('j.country_id', $this->zambiaId())
            ->orderByDesc('j.views')
            ->limit(15)
            ->get();

        if ($rows->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId, 'text' => '🔥 No job view data yet.',
            ]);
            return;
        }

        $nW     = min(28, max(20, $rows->max(fn ($r) => mb_strlen($r->name))));
        $cW     = min(18, max(12, $rows->max(fn ($r) => mb_strlen($r->company))));
        $header  = sprintf("%-{$nW}s  %-{$cW}s  %6s", 'Job', 'Company', 'Views');
        $divider = str_repeat('─', $nW + $cW + 10);
        $lines   = [];

        foreach ($rows as $i => $row) {
            $rank    = str_pad((string) ($i + 1), 2, ' ', STR_PAD_LEFT);
            $name    = mb_strlen($row->name) > $nW ? mb_substr($row->name, 0, $nW - 1) . '…' : $row->name;
            $company = mb_strlen($row->company) > $cW ? mb_substr($row->company, 0, $cW - 1) . '…' : $row->company;
            $lines[] = sprintf("%s. %-{$nW}s  %-{$cW}s  %6s", $rank, $name, $company, number_format($row->views));
        }

        $table = implode("\n", [$header, $divider, implode("\n", $lines)]);

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => "<b>🔥 Hot Jobs — Zambia</b> — top 15 by views\n\n<pre>{$table}</pre>",
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /fresh — jobs posted in the last 2 hours
    // -------------------------------------------------------------------------

    protected function sendFreshListings(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = DB::table('jb_jobs as j')
            ->join('jb_companies as co', 'co.id', '=', 'j.company_id')
            ->leftJoin('countries as c', 'c.id', '=', 'j.country_id')
            ->select([
                'j.name', 'co.name as company', 'c.name as country',
                DB::raw("TIMESTAMPDIFF(MINUTE, j.created_at, NOW()) AS mins_ago"),
            ])
            ->where('j.status', 'published')
            ->where('j.country_id', $this->zambiaId())
            ->where('j.created_at', '>=', now()->subHours(2))
            ->orderByDesc('j.created_at')
            ->limit(20)
            ->get();

        if ($rows->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => '🆕 No new jobs in the last 2 hours.',
            ]);
            return;
        }

        $lines = ["<b>🆕 Fresh Listings — Zambia</b> — last 2 hours ({$rows->count()} jobs)\n"];
        foreach ($rows as $row) {
            $age      = $row->mins_ago < 60
                ? "{$row->mins_ago}m ago"
                : round($row->mins_ago / 60, 1) . 'h ago';
            $country  = $row->country ? " · {$row->country}" : '';
            $lines[]  = "• <b>{$row->name}</b>\n  {$row->company}{$country} — <i>{$age}</i>";
        }

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => implode("\n\n", $lines),
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /topcompanies — companies with most new jobs this week (social content)
    // -------------------------------------------------------------------------

    protected function sendTopCompanies(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = DB::table('jb_jobs as j')
            ->join('jb_companies as co', 'co.id', '=', 'j.company_id')
            ->leftJoin('countries as c', 'c.id', '=', 'j.country_id')
            ->select([
                'co.name as company', 'c.name as country',
                DB::raw("SUM(CASE WHEN DATE(j.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today"),
                DB::raw("COUNT(*) AS week"),
            ])
            ->where('j.status', 'published')
            ->where('j.country_id', $this->zambiaId())
            ->where('j.created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('co.id', 'co.name', 'c.name')
            ->orderByDesc('week')
            ->limit(15)
            ->get();

        if ($rows->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId, 'text' => '🏆 No company data this week.',
            ]);
            return;
        }

        $nW     = min(28, max(18, $rows->max(fn ($r) => mb_strlen($r->company))));
        $cW     = min(14, max(8, $rows->max(fn ($r) => mb_strlen($r->country ?? ''))));
        $header  = sprintf("%-{$nW}s  %-{$cW}s  %5s  %5s", 'Company', 'Country', 'Today', 'Week');
        $divider = str_repeat('─', $nW + $cW + 15);
        $lines   = [];

        foreach ($rows as $i => $row) {
            $company = mb_strlen($row->company) > $nW ? mb_substr($row->company, 0, $nW - 1) . '…' : $row->company;
            $country = mb_strlen($row->country ?? '') > $cW ? mb_substr($row->country ?? '', 0, $cW - 1) . '…' : ($row->country ?? '—');
            $lines[] = sprintf("%-{$nW}s  %-{$cW}s  %5d  %5d", $company, $country, $row->today, $row->week);
        }

        $table = implode("\n", [$header, $divider, implode("\n", $lines)]);

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => "<b>🏆 Top Hiring Companies — Zambia</b> — this week\n\n<pre>{$table}</pre>",
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /newaccounts — new job seeker and employer registrations
    // -------------------------------------------------------------------------

    protected function sendNewAccounts(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = DB::table('jb_accounts')
            ->select([
                'type',
                DB::raw("SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today"),
                DB::raw("SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS two_days"),
                DB::raw("SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN 1 ELSE 0 END) AS week"),
                DB::raw("SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) THEN 1 ELSE 0 END) AS month"),
                DB::raw("COUNT(*) AS total"),
            ])
            ->where('country_id', $this->zambiaId())
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $seeker   = $rows['job-seeker'] ?? null;
        $employer = $rows['employer'] ?? null;

        $fmt = fn ($row) => $row
            ? "Today: <b>{$row->today}</b>  ·  2d: <b>{$row->two_days}</b>  ·  Week: <b>{$row->week}</b>  ·  Month: <b>{$row->month}</b>  ·  Total: <b>{$row->total}</b>"
            : 'No data';

        $text = "<b>👥 New Accounts — Zambia</b> — " . now()->format('j M Y') . "\n\n"
            . "👤 <b>Job Seekers</b>\n" . $fmt($seeker) . "\n\n"
            . "🏢 <b>Employers</b>\n" . $fmt($employer);

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /apptrend — application volume per day, last 14 days
    // -------------------------------------------------------------------------

    protected function sendApplicationTrend(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $data = DB::table('jb_applications as a')
            ->join('jb_jobs as j', 'j.id', '=', 'a.job_id')
            ->select(DB::raw("DATE(a.created_at) as day, COUNT(*) as apps"))
            ->where('j.country_id', $this->zambiaId())
            ->where('a.created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('day')
            ->pluck('apps', 'day');

        $period   = CarbonPeriod::create(today()->subDays(13), today());
        $rows     = [];
        foreach ($period as $date) {
            $rows[$date->format('Y-m-d')] = (int) ($data[$date->format('Y-m-d')] ?? 0);
        }

        $maxApps  = max(1, max($rows));
        $barWidth = 25;
        $total    = array_sum($rows);
        $lines    = [];

        foreach (array_reverse($rows, true) as $day => $apps) {
            $bars    = (int) round($apps / $maxApps * $barWidth);
            $bar     = str_repeat('█', $bars) . str_repeat('░', $barWidth - $bars);
            $label   = Carbon::parse($day)->format('d M');
            $lines[] = sprintf("%s  %4d  %s", $label, $apps, $bar);
        }

        $header = sprintf("%-6s  %4s  %s", 'Date', 'Apps', str_repeat('─', $barWidth));
        $table  = implode("\n", [$header, implode("\n", $lines)]);
        $avg    = round($total / 14);

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => "<b>📥 Application Trend — Zambia</b> — Last 14 Days\n"
                . "Total: <b>{$total}</b>  ·  Avg/day: <b>{$avg}</b>\n\n<pre>{$table}</pre>",
            'parse_mode' => 'HTML',
        ]);
    }

    // -------------------------------------------------------------------------
    // /traffic — job view traffic by source (from jb_analytics referer)
    // -------------------------------------------------------------------------

    protected function sendTrafficReport(string $chatId): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        $rows = DB::table('jb_analytics as an')
            ->join('jb_jobs as j', 'j.id', '=', 'an.job_id')
            ->select([
                DB::raw("CASE
                    WHEN an.referer LIKE '%facebook%' OR an.referer LIKE '%fb.com%' OR an.referer LIKE '%fb.me%' THEN 'Facebook'
                    WHEN an.referer LIKE '%whatsapp%' THEN 'WhatsApp'
                    WHEN an.referer LIKE '%telegram%' OR an.referer LIKE '%t.me%' THEN 'Telegram'
                    WHEN an.referer LIKE '%google%' THEN 'Google'
                    WHEN an.referer LIKE '%linkedin%' THEN 'LinkedIn'
                    WHEN an.referer LIKE '%twitter%' OR an.referer LIKE '%x.com%' OR an.referer LIKE '%t.co%' THEN 'Twitter/X'
                    WHEN an.referer LIKE '%instagram%' THEN 'Instagram'
                    WHEN an.referer LIKE '%tiktok%' THEN 'TikTok'
                    WHEN an.referer LIKE '%wakandajobs%' OR an.referer LIKE '%wakandasystems%' THEN 'Internal'
                    WHEN an.referer IS NULL OR an.referer = '' THEN 'Direct'
                    ELSE 'Other'
                END AS source"),
                DB::raw("SUM(CASE WHEN DATE(an.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today"),
                DB::raw("SUM(CASE WHEN an.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN 1 ELSE 0 END) AS week"),
                DB::raw("SUM(CASE WHEN an.created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) THEN 1 ELSE 0 END) AS month"),
                DB::raw("COUNT(*) AS total"),
            ])
            ->where('j.country_id', $this->zambiaId())
            ->where('an.created_at', '>=', now()->subDays(29))
            ->groupBy('source')
            ->orderByDesc('week')
            ->get();

        if ($rows->isEmpty()) {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId, 'text' => '🔍 No analytics data yet.',
            ]);
            return;
        }

        $nW     = max(10, $rows->max(fn ($r) => mb_strlen($r->source)));
        $header  = sprintf("%-{$nW}s  %5s  %5s  %5s  %7s", 'Source', 'Today', 'Week', 'Month', 'Total');
        $divider = str_repeat('─', $nW + 30);
        $lines   = [];
        $totals  = ['today' => 0, 'week' => 0, 'month' => 0, 'total' => 0];

        foreach ($rows as $row) {
            $totals['today'] += (int) $row->today;
            $totals['week']  += (int) $row->week;
            $totals['month'] += (int) $row->month;
            $totals['total'] += (int) $row->total;
            $lines[] = sprintf("%-{$nW}s  %5d  %5d  %5d  %7d",
                $row->source, $row->today, $row->week, $row->month, $row->total);
        }

        $totalLine = sprintf("%-{$nW}s  %5d  %5d  %5d  %7d",
            'TOTAL', $totals['today'], $totals['week'], $totals['month'], $totals['total']);

        $table = implode("\n", [$header, $divider, implode("\n", $lines), $divider, $totalLine]);

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => "<b>🔍 Traffic by Source — Zambia</b> — " . now()->format('j M Y') . "\n\n<pre>{$table}</pre>",
            'parse_mode' => 'HTML',
        ]);
    }

}
