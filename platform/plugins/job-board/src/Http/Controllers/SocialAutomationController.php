<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class SocialAutomationController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/job-board::job-board.name'))
            ->add('Automations', route('job-board.automations.index'));
    }

    public function index()
    {
        $this->pageTitle('Social Automations');

        $automations = SocialAutomation::query()
            ->orderBy('platform')
            ->orderBy('name')
            ->get()
            ->groupBy('platform');

        $countries = DB::table('countries')->orderBy('name')->pluck('name', 'id');

        return view('plugins/job-board::automations.index', compact('automations', 'countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => ['required', Rule::in(['facebook', 'linkedin', 'whatsapp', 'telegram', 'whapi'])],
            'name'     => ['required', 'string', 'max:150'],
            'settings' => ['nullable', 'array'],
        ]);

        SocialAutomation::query()->create([
            'platform'  => $validated['platform'],
            'name'      => $validated['name'],
            'is_active' => false,
            'settings'  => $validated['settings'] ?? [],
        ]);

        return $this->httpResponse()
            ->setMessage('Automation added successfully.')
            ->setNextUrl(route('job-board.automations.index'));
    }

    public function update(SocialAutomation $automation, Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:150'],
            'settings' => ['nullable', 'array'],
        ]);

        // Merge new settings over existing — blank password fields keep the saved value.
        // Checkbox keys are absent when unchecked, so explicitly set them to 0 if missing.
        // Select keys (like country_id) must always override so clearing the selection works.
        $checkboxKeys = ['generate_image', 'send_image'];
        $selectKeys   = ['country_id'];
        $existing = $automation->settings ?? [];
        $incoming = $validated['settings'] ?? [];
        foreach ($checkboxKeys as $key) {
            $incoming[$key] = isset($incoming[$key]) ? 1 : 0;
        }
        $merged = array_merge($existing, array_filter($incoming, fn ($v) => $v !== null && $v !== ''));
        // Allow checkbox=0 to override existing=1
        foreach ($checkboxKeys as $key) {
            $merged[$key] = $incoming[$key];
        }
        // Allow empty string to clear select fields (e.g. country_id → all countries)
        foreach ($selectKeys as $key) {
            if (array_key_exists($key, $incoming)) {
                $merged[$key] = $incoming[$key] === '' ? null : $incoming[$key];
            }
        }

        $automation->fill([
            'name'     => $validated['name'],
            'settings' => $merged,
        ])->save();

        return $this->httpResponse()
            ->setMessage('Automation updated.')
            ->setNextUrl(route('job-board.automations.index'));
    }

    public function destroy(SocialAutomation $automation)
    {
        $automation->delete();

        return $this->httpResponse()->setMessage('Automation deleted.');
    }

    public function duplicate(SocialAutomation $automation)
    {
        // Strip any existing "(N)" suffix to get the base name
        $base = preg_replace('/\s*\(\d+\)$/', '', $automation->name);

        // Find the highest existing counter for this base name on this platform
        $existing = SocialAutomation::query()
            ->where('platform', $automation->platform)
            ->where(function ($q) use ($base) {
                $q->where('name', $base)
                  ->orWhere('name', 'like', $base . ' (%)');
            })
            ->pluck('name');

        $max = 1;
        foreach ($existing as $name) {
            if (preg_match('/\((\d+)\)$/', $name, $m)) {
                $max = max($max, (int) $m[1]);
            } elseif ($name === $base) {
                $max = max($max, 1);
            }
        }

        $newName = $base . ' (' . ($max + 1) . ')';

        $clone = SocialAutomation::query()->create([
            'platform'  => $automation->platform,
            'name'      => $newName,
            'is_active' => false,
            'settings'  => $automation->settings ?? [],
        ]);

        return $this->httpResponse()
            ->setData(['id' => $clone->getKey(), 'name' => $clone->name])
            ->setMessage("Duplicated as \"{$clone->name}\" (disabled).");
    }

    public function toggle(SocialAutomation $automation)
    {
        $automation->is_active = ! $automation->is_active;
        $automation->save();

        return $this->httpResponse()->setData(['is_active' => $automation->is_active]);
    }

    public function clearAllChats()
    {
        $token = setting('telegram_bot_token', '');

        if ($token === '') {
            return $this->httpResponse()->setError()->setMessage('Telegram bot token is not configured.');
        }

        $rows = DB::table('telegram_message_log')->orderBy('id')->get(['id', 'chat_id', 'message_id']);

        if ($rows->isEmpty()) {
            return $this->httpResponse()->setMessage('No tracked messages to delete.');
        }

        $deleted = 0;
        $ids     = [];

        foreach ($rows as $row) {
            $automationToken = SocialAutomation::query()
                ->where('platform', 'telegram')
                ->whereJsonContains('settings->chat_id', $row->chat_id)
                ->value(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(settings, '$.bot_token'))"));

            $useToken = $automationToken ?: $token;

            $resp = Http::timeout(10)->post("https://api.telegram.org/bot{$useToken}/deleteMessage", [
                'chat_id'    => $row->chat_id,
                'message_id' => $row->message_id,
            ]);

            if ($resp->successful() && data_get($resp->json(), 'result')) {
                $deleted++;
            }
            $ids[] = $row->id;
        }

        DB::table('telegram_message_log')->whereIn('id', $ids)->delete();

        return $this->httpResponse()->setMessage("Deleted {$deleted} of {$rows->count()} tracked messages.");
    }

    public function regenerateTodayJobs(SocialPublisherService $publisher)
    {
        $automations = SocialAutomation::query()
            ->where('platform', 'telegram')
            ->where('is_active', true)
            ->get();

        if ($automations->isEmpty()) {
            return $this->httpResponse()->setError()->setMessage('No active Telegram automations configured.');
        }

        $totalSent = 0;

        foreach ($automations as $automation) {
            $settings  = $automation->settings ?? [];
            $token     = trim((string) ($settings['bot_token'] ?? setting('telegram_bot_token')));
            $chatId    = trim((string) ($settings['chat_id'] ?? ''));
            $countryId = isset($settings['country_id']) && $settings['country_id'] !== ''
                ? (int) $settings['country_id']
                : null;
            $generateImage   = ! empty($settings['generate_image']);
            $noInlineButtons = ! empty($settings['no_inline_buttons']);

            if ($token === '' || $chatId === '') {
                continue;
            }

            $query = Job::query()
                ->with(['company', 'slugable', 'country', 'currency', 'jobTypes'])
                ->where('status', JobStatusEnum::PUBLISHED)
                ->whereDate('created_at', today())
                ->orderByDesc('created_at');

            if ($countryId) {
                $query->where('country_id', $countryId);
            }

            $jobs = $query->get();

            if ($jobs->isEmpty()) {
                continue;
            }

            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => "📅 Regenerating *{$jobs->count()} jobs* from today…",
                'parse_mode' => 'Markdown',
            ]);

            foreach ($jobs as $job) {
                $publisher->sendTelegramCopyPost($token, $chatId, $job, $automation->getKey(), $generateImage, $noInlineButtons);
                usleep(500000);
                $totalSent++;
            }
        }

        return $this->httpResponse()->setMessage("Sent {$totalSent} job(s) to Telegram.");
    }

    public function sendJobs(SocialAutomation $automation, Request $request, SocialPublisherService $publisher)
    {
        $request->validate(['period' => ['required', 'in:today,yesterday,7days,30days,active']]);

        set_time_limit(300);

        $settings   = $automation->settings ?? [];
        $token      = trim((string) ($settings['token'] ?? ''));
        $channelId  = trim((string) ($settings['channel_id'] ?? ''));
        $countryId  = isset($settings['country_id']) && $settings['country_id'] !== ''
            ? (int) $settings['country_id']
            : null;
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        if ($token === '' || $channelId === '') {
            return $this->httpResponse()->setError()->setMessage('Automation is missing token or channel ID.');
        }

        if (! str_ends_with($channelId, '@newsletter')) {
            $channelId .= '@newsletter';
        }

        $period = $request->input('period');

        $query = Job::query()
            ->with(['company', 'slugable', 'country', 'currency', 'jobTypes'])
            ->where('status', JobStatusEnum::PUBLISHED)
            ->orderByDesc('created_at');

        match ($period) {
            'today'     => $query->whereDate('created_at', today()),
            'yesterday' => $query->whereDate('created_at', today()->subDay()),
            '7days'     => $query->where('created_at', '>=', now()->subDays(7)->startOfDay()),
            '30days'    => $query->where('created_at', '>=', now()->subDays(30)->startOfDay()),
            'active'    => $query->where(fn ($q) =>
                               $q->whereNull('expire_date')->orWhere('expire_date', '>=', today())
                           ),
        };

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        $jobs = $query->get();

        if ($jobs->isEmpty()) {
            return $this->httpResponse()->setError()->setMessage('No jobs found for the selected period.');
        }

        $sent = $failed = 0;

        foreach ($jobs as $job) {
            try {
                $posts    = $publisher->buildPlatformPosts($job);
                $msg      = $posts['whatsapp'] ?? $job->name;
                $imgField = trim((string) ($job->whatsapp_image ?? ''));
                $ok       = false;

                if ($imgField !== '') {
                    $imageUrl = \Botble\Media\Facades\RvMedia::getImageUrl($imgField);
                    $resp     = Http::timeout(30)->withToken($token)->post("{$gatewayUrl}/messages/image", [
                        'to'      => $channelId,
                        'media'   => $imageUrl,
                        'caption' => $msg,
                    ]);
                    $ok = $resp->successful();
                }

                if (! $ok) {
                    $resp = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                        'to'   => $channelId,
                        'body' => $msg,
                    ]);
                    $ok = $resp->successful();
                }

                $ok ? $sent++ : $failed++;
            } catch (\Throwable) {
                $failed++;
            }

            usleep(600000);
        }

        $msg = "Sent {$sent} of {$jobs->count()} job(s) to {$automation->name}" . ($failed ? " ({$failed} failed)" : '') . '.';

        return $sent > 0
            ? $this->httpResponse()->setMessage($msg)
            : $this->httpResponse()->setError()->setMessage($msg);
    }

    public function fetchWhapiChannels(Request $request)
    {
        $token        = trim((string) $request->input('token', ''));
        $automationId = (int) $request->input('automation_id', 0);
        $gatewayUrl   = rtrim(trim((string) $request->input('gateway_url', '')), '/') ?: 'https://gate.whapi.cloud';

        // If no token supplied (password field left blank in edit form), use the saved token
        if ($token === '' && $automationId) {
            $saved      = SocialAutomation::find($automationId);
            $token      = trim((string) ($saved?->settings['token'] ?? ''));
            $savedGw    = rtrim(trim((string) ($saved?->settings['gateway_url'] ?? '')), '/');
            if ($gatewayUrl === 'https://gate.whapi.cloud' && $savedGw !== '') {
                $gatewayUrl = $savedGw;
            }
        }

        if ($token === '') {
            return response()->json(['error' => 'Enter (or save) the Whapi token first.'], 422);
        }

        try {
            $resp = Http::timeout(12)->withToken($token)->get("{$gatewayUrl}/newsletters");

            if (! $resp->successful()) {
                return response()->json([
                    'error' => "Whapi API returned HTTP {$resp->status()}. Check your token and gateway URL.",
                ], 422);
            }

            $body = $resp->json();

            // Whapi returns either { newsletters: [...] } or a bare array
            $list = $body['newsletters'] ?? (is_array($body) ? $body : []);

            $channels = collect($list)
                ->map(fn ($ch) => [
                    'id'   => $ch['id'] ?? $ch['jid'] ?? '',
                    'name' => $ch['name'] ?? $ch['subject'] ?? ($ch['id'] ?? 'Unknown'),
                ])
                ->filter(fn ($ch) => $ch['id'] !== '')
                ->values();

            return response()->json(['channels' => $channels]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Could not reach Whapi: ' . $e->getMessage()], 422);
        }
    }

    public function whapiSendYesterdayJobs(Request $request, SocialPublisherService $publisher)
    {
        $limit = max(0, (int) $request->input('limit', 0)); // 0 = all

        $automations = SocialAutomation::query()
            ->where('platform', 'whapi')
            ->where('is_active', true)
            ->get();

        if ($automations->isEmpty()) {
            return $this->httpResponse()->setError()->setMessage('No active Whapi automations configured.');
        }

        $totalSent  = 0;
        $totalJobs  = 0;

        foreach ($automations as $automation) {
            $settings   = $automation->settings ?? [];
            $token      = trim((string) ($settings['token'] ?? ''));
            $channelId  = trim((string) ($settings['channel_id'] ?? ''));
            $countryId  = isset($settings['country_id']) && $settings['country_id'] !== ''
                ? (int) $settings['country_id']
                : null;
            $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

            if ($token === '' || $channelId === '') {
                continue;
            }

            if (! str_ends_with($channelId, '@newsletter')) {
                $channelId .= '@newsletter';
            }

            $query = Job::query()
                ->with(['company', 'slugable', 'country', 'currency', 'jobTypes'])
                ->where('status', JobStatusEnum::PUBLISHED)
                ->whereDate('created_at', today()->subDay())
                ->orderByDesc('created_at');

            if ($countryId) {
                $query->where('country_id', $countryId);
            }

            $jobs = $query->get();

            if ($jobs->isEmpty()) {
                continue;
            }

            if ($limit > 0) {
                $jobs = $jobs->take($limit);
            }

            $totalJobs += $jobs->count();

            foreach ($jobs as $job) {
                try {
                    $posts    = $publisher->buildPlatformPosts($job);
                    $msg      = $posts['whatsapp'] ?? $job->name;
                    $imgField = trim((string) ($job->whatsapp_image ?? ''));
                    $sent     = false;

                    // Use stored whatsapp_image if available
                    if ($imgField !== '') {
                        $imageUrl = \Botble\Media\Facades\RvMedia::getImageUrl($imgField);
                        $resp     = Http::timeout(30)->withToken($token)->post("{$gatewayUrl}/messages/image", [
                            'to'      => $channelId,
                            'media'   => $imageUrl,
                            'caption' => $msg,
                        ]);
                        $sent = $resp->successful();
                    }

                    // Fall back to text-only
                    if (! $sent) {
                        $resp = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                            'to'   => $channelId,
                            'body' => $msg,
                        ]);
                        $sent = $resp->successful();
                    }

                    if ($sent) {
                        $totalSent++;
                    }
                } catch (\Throwable) {
                    // continue on individual failure
                }
                usleep(600000); // 0.6s between posts
            }
        }

        $label = $limit > 0 ? "{$limit}-job test" : 'full';
        return $this->httpResponse()->setMessage("Sent {$totalSent} of {$totalJobs} jobs to WhatsApp Channel ({$label} run).");
    }

    public function whapiSendJob(Job $job, SocialPublisherService $publisher)
    {
        $job->loadMissing(['company', 'slugable', 'country', 'currency', 'jobTypes']);

        $automations = SocialAutomation::query()
            ->where('platform', 'whapi')
            ->where('is_active', true)
            ->get()
            ->filter(function (SocialAutomation $a) use ($job) {
                $cid = $a->settings['country_id'] ?? null;
                return !$cid || (int) $cid === (int) $job->country_id;
            });

        if ($automations->isEmpty()) {
            return $this->httpResponse()->setError()->setMessage('No active Whapi automation matches this job\'s country.');
        }

        $sent = 0;

        foreach ($automations as $automation) {
            $settings   = $automation->settings ?? [];
            $token      = trim((string) ($settings['token'] ?? ''));
            $channelId  = trim((string) ($settings['channel_id'] ?? ''));
            $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

            if ($token === '' || $channelId === '') {
                continue;
            }

            if (! str_ends_with($channelId, '@newsletter')) {
                $channelId .= '@newsletter';
            }

            try {
                $posts    = $publisher->buildPlatformPosts($job);
                $msg      = $posts['whatsapp'] ?? $job->name;
                $imgField = trim((string) ($job->whatsapp_image ?? ''));
                $ok       = false;

                if ($imgField !== '') {
                    $imageUrl = \Botble\Media\Facades\RvMedia::getImageUrl($imgField);
                    $resp     = Http::timeout(30)->withToken($token)->post("{$gatewayUrl}/messages/image", [
                        'to'      => $channelId,
                        'media'   => $imageUrl,
                        'caption' => $msg,
                    ]);
                    $ok = $resp->successful();
                }

                if (! $ok) {
                    $resp = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                        'to'   => $channelId,
                        'body' => $msg,
                    ]);
                    $ok = $resp->successful();
                }

                if ($ok) {
                    $sent++;
                }
            } catch (\Throwable) {
                // continue
            }
        }

        if ($sent > 0) {
            return $this->httpResponse()->setMessage("Job sent to {$sent} WhatsApp Channel(s) successfully.");
        }

        return $this->httpResponse()->setError()->setMessage('Failed to send to WhatsApp Channel. Check token and limits.');
    }
}
