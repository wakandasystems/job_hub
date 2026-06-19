<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Jobs\RetryPublerPostJob;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            'platform' => ['required', Rule::in(['facebook', 'linkedin', 'whatsapp', 'telegram', 'whapi', 'publer'])],
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

    public function saveWhapiToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
        ]);

        setting()->set('whapi_api_token', trim($validated['token']))->save();

        return $this->httpResponse()->setMessage('Shared Whapi API token saved.');
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
        $arrayKeys    = ['account_ids'];
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
        // Allow empty string to clear scalar select fields (e.g. country_id → all countries)
        foreach ($selectKeys as $key) {
            if (array_key_exists($key, $incoming)) {
                $merged[$key] = $incoming[$key] === '' ? null : $incoming[$key];
            }
        }
        // Always override array fields so removing all selections works
        foreach ($arrayKeys as $key) {
            if (array_key_exists($key, $incoming)) {
                $merged[$key] = array_values(array_filter((array) $incoming[$key]));
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
        $token      = SocialAutomation::whapiToken($automation);
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

        $saved = $automationId
            ? SocialAutomation::query()->where('platform', 'whapi')->find($automationId)
            : null;
        if ($token === '') {
            $token = SocialAutomation::whapiToken($saved);
        }

        if ($saved) {
            $savedGw    = rtrim(trim((string) ($saved?->settings['gateway_url'] ?? '')), '/');
            if ($gatewayUrl === 'https://gate.whapi.cloud' && $savedGw !== '') {
                $gatewayUrl = $savedGw;
            }
        }

        if ($token === '') {
            return response()->json(['error' => 'Save the shared Whapi token first.'], 422);
        }

        try {
            $resp = Http::timeout(12)->withToken($token)->get("{$gatewayUrl}/newsletters");

            if (! $resp->successful()) {
                $whapiMessage = trim((string) data_get($resp->json(), 'error.message', ''));
                $error = match ($resp->status()) {
                    401 => 'Whapi rejected the saved API token. Copy the current token from the Whapi channel dashboard, save it here, and reconnect the channel if Whapi shows a QR code.',
                    403 => 'The Whapi token is valid but does not have permission to access newsletters for this channel.',
                    default => "Whapi API returned HTTP {$resp->status()}. Check your token, channel status, and gateway URL.",
                };

                if ($whapiMessage !== '' && strcasecmp($whapiMessage, 'Internal Error') !== 0) {
                    $error .= " Whapi says: {$whapiMessage}";
                }

                return response()->json([
                    'error' => $error,
                ], 422);
            }

            $body = $resp->json();

            // Whapi returns either { newsletters: [...] } or a bare array
            $list = $body['newsletters'] ?? (is_array($body) ? $body : []);

            $currentChannelId = $this->normalizeWhapiChannelId(
                (string) ($saved?->settings['channel_id'] ?? '')
            );
            $configuredChannelIds = SocialAutomation::query()
                ->where('platform', 'whapi')
                ->when($saved, fn ($query) => $query->whereKeyNot($saved->getKey()))
                ->get(['settings'])
                ->map(fn (SocialAutomation $automation) => $this->normalizeWhapiChannelId(
                    (string) ($automation->settings['channel_id'] ?? '')
                ))
                ->filter()
                ->unique()
                ->flip();

            $allChannels = collect($list)
                ->map(fn ($ch) => [
                    'id'   => $ch['id'] ?? $ch['jid'] ?? '',
                    'name' => $ch['name'] ?? $ch['subject'] ?? ($ch['id'] ?? 'Unknown'),
                ])
                ->filter(fn ($ch) => $ch['id'] !== '')
                ->unique(fn ($ch) => $this->normalizeWhapiChannelId((string) $ch['id']))
                ->values();

            $channels = $allChannels
                ->filter(function (array $channel) use ($configuredChannelIds, $currentChannelId): bool {
                    $normalizedId = $this->normalizeWhapiChannelId((string) $channel['id']);

                    return $normalizedId === $currentChannelId || ! $configuredChannelIds->has($normalizedId);
                })
                ->values();

            return response()->json([
                'channels' => $channels,
                'excluded_count' => $allChannels->count() - $channels->count(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Could not reach Whapi: ' . $e->getMessage()], 422);
        }
    }

    protected function normalizeWhapiChannelId(string $channelId): string
    {
        $channelId = strtolower(trim($channelId));

        if ($channelId === '') {
            return '';
        }

        return str_ends_with($channelId, '@newsletter')
            ? $channelId
            : $channelId . '@newsletter';
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
            $token      = SocialAutomation::whapiToken($automation);
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
            $token      = SocialAutomation::whapiToken($automation);
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

    // -------------------------------------------------------------------------
    // Publer helpers
    // -------------------------------------------------------------------------

    public function fetchPublerAccounts(Request $request)
    {
        $automationId = (int) $request->input('automation_id', 0);
        $apiKey       = trim((string) $request->input('api_key', ''));

        // Fall back to saved key when the password field was left blank
        if ($apiKey === '' && $automationId) {
            $saved  = SocialAutomation::find($automationId);
            $apiKey = trim((string) ($saved?->settings['api_key'] ?? ''));
        }

        // Final fallback to global config
        if ($apiKey === '') {
            $apiKey = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
        }

        if ($apiKey === '') {
            return response()->json(['error' => 'Enter (or save) the Publer API key first.'], 422);
        }

        $workspaceId = trim((string) $request->input('workspace_id', ''));
        if ($workspaceId === '' && $automationId) {
            $saved       = SocialAutomation::find($automationId);
            $workspaceId = trim((string) ($saved?->settings['workspace_id'] ?? ''));
        }

        try {
            $publisher = app(SocialPublisherService::class);

            // Fetch workspaces first if no workspace ID provided
            if ($workspaceId === '') {
                $workspaces  = $publisher->fetchPublerWorkspaces($apiKey);
                $workspaceId = $workspaces[0]['id'] ?? '';
            }

            $accounts = $publisher->fetchPublerAccounts($apiKey, $workspaceId);

            if (empty($accounts)) {
                return response()->json(['error' => 'No connected accounts found in Publer. Connect your social accounts at publer.io first.'], 422);
            }

            return response()->json([
                'accounts'     => $accounts,
                'workspace_id' => $workspaceId,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Could not reach Publer: ' . $e->getMessage()], 422);
        }
    }

    public function publerSendJob(Job $job, Request $request, SocialPublisherService $publisher)
    {
        $job->loadMissing(['company', 'slugable', 'country', 'currency', 'jobTypes']);

        $preferredImageField = $request->input('image_field') ?: null;
        $excludeNetworks     = array_filter(array_map('trim', explode(',', (string) $request->input('exclude_networks', ''))));

        $automations = SocialAutomation::query()
            ->where('platform', 'publer')
            ->where('is_active', true)
            ->get()
            ->filter(function (SocialAutomation $a) use ($job) {
                $cid = $a->settings['country_id'] ?? null;
                return ! $cid || (int) $cid === (int) $job->country_id;
            });

        if ($automations->isEmpty()) {
            return $this->httpResponse()->setError()->setMessage('No active Publer automation matches this job\'s country.');
        }

        $sent = 0;
        $errors = [];
        $retryAutomationIds = [];

        foreach ($automations as $automation) {
            $settings    = $automation->settings ?? [];
            $apiKey      = trim((string) ($settings['api_key'] ?? ''));
            if ($apiKey === '') {
                $apiKey = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
            }
            $accountIds  = array_values(array_filter((array) ($settings['account_ids'] ?? [])));
            $workspaceId = trim((string) ($settings['workspace_id'] ?? ''));

            if ($apiKey === '' || empty($accountIds)) {
                continue;
            }

            try {
                if ($publisher->publerPost($job, $apiKey, $accountIds, $workspaceId, $preferredImageField, $excludeNetworks)) {
                    $sent++;
                } else {
                    $errors[] = $publisher->getLastPublerError();
                    $retryAutomationIds[] = $automation->getKey();
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
                $retryAutomationIds[] = $automation->getKey();
            }
        }

        if ($sent > 0) {
            return $this->httpResponse()->setMessage("Job published to Publer via {$sent} automation(s).");
        }

        $retryQueued = false;
        $retryAlreadyQueued = false;
        $retryAt = now()->addMinutes(2);
        $targetAlreadyQueued = collect($errors)->filter()->contains(
            fn ($error) => str_contains((string) $error, '"in_queue":true')
        );
        $hasTikTokDailyLimit = $this->hasTikTokDailyApiLimitError($errors);

        if ($hasTikTokDailyLimit) {
            $retryAt = $this->nextPublerTikTokRetryWindow();
        }

        if ($request->boolean('retry_background') && ! $targetAlreadyQueued) {
            foreach (array_unique($retryAutomationIds) as $automationId) {
                $retryCacheKey = RetryPublerPostJob::retryCacheKey(
                    $job->getKey(),
                    $automationId,
                    $preferredImageField,
                    $excludeNetworks,
                );

                if (! Cache::add($retryCacheKey, true, now()->addHours(48))) {
                    $retryAlreadyQueued = true;

                    continue;
                }

                RetryPublerPostJob::dispatch(
                    $job->getKey(),
                    $automationId,
                    $preferredImageField,
                    $excludeNetworks,
                )
                    ->onQueue('publer')
                    ->delay($retryAt);
                $retryQueued = true;
            }
        }

        $errorDetail = $this->summarizePublerErrors($errors);
        $message = match (true) {
            $retryQueued && $hasTikTokDailyLimit => 'TikTok daily posting limit reached. A Publer retry has been queued for ' . $retryAt->format('d M Y H:i') . '.',
            $retryAlreadyQueued && $hasTikTokDailyLimit => 'TikTok daily posting limit reached. A retry is already queued, so no duplicate was added.',
            $retryQueued => 'Publer needs a posting gap. A background retry has been queued for 2 minutes.',
            $retryAlreadyQueued => 'Publer retry is already queued, so no duplicate was added.',
            $targetAlreadyQueued => 'TikTok was queued, but Publer reported errors for other accounts. No retry was queued to avoid a duplicate.',
            default => 'Failed to publish to Publer.',
        };

        return $this->httpResponse()
            ->setError()
            ->setMessage($message)
            ->setData([
                'error_detail' => $errorDetail ?: 'Publer did not return a detailed error.',
                'retry_queued' => $retryQueued,
            ]);
    }

    private function hasTikTokDailyApiLimitError(array $errors): bool
    {
        return collect($errors)
            ->filter()
            ->contains(function ($error): bool {
                $error = strtolower((string) $error);

                return str_contains($error, 'too many posts via openapi')
                    || str_contains($error, 'last 24 hours');
            });
    }

    private function nextPublerTikTokRetryWindow(): \Illuminate\Support\Carbon
    {
        $retryAt = now()->addDay()->setTime(1, 0);

        if ($retryAt->lessThanOrEqualTo(now()->addMinutes(5))) {
            $retryAt->addDay();
        }

        return $retryAt;
    }

    private function summarizePublerErrors(array $errors): string
    {
        $messages = collect($errors)
            ->filter()
            ->flatMap(function ($error): array {
                $decoded = json_decode((string) $error, true);

                if (! is_array($decoded)) {
                    return [(string) $error];
                }

                $items = array_is_list($decoded) ? $decoded : [$decoded];

                return collect($items)
                    ->map(fn (array $item): string => (string) (
                        data_get($item, 'failure.message')
                        ?: data_get($item, 'post.error')
                        ?: data_get($item, 'post.details.error')
                        ?: data_get($item, 'message')
                        ?: ''
                    ))
                    ->filter()
                    ->all();
            })
            ->map(function (string $message): string {
                $lower = strtolower($message);

                if (str_contains($lower, 'too many posts via openapi') || str_contains($lower, 'last 24 hours')) {
                    return 'TikTok daily OpenAPI posting limit reached. This will be retried by the Publer queue the following day at 01:00.';
                }

                if (str_contains($lower, 'one minute gap') || str_contains($lower, 'another post at this time')) {
                    return 'Publer requires at least one minute between posts. This will be retried shortly.';
                }

                return $message;
            })
            ->unique()
            ->values();

        return $messages->implode("\n");
    }

    public function publerSendPeriodJobs(SocialAutomation $automation, Request $request, SocialPublisherService $publisher)
    {
        $request->validate(['period' => ['required', 'in:today,yesterday,7days,30days,active']]);

        set_time_limit(300);

        $settings    = $automation->settings ?? [];
        $apiKey      = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            $apiKey = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
        }
        $accountIds  = array_values(array_filter((array) ($settings['account_ids'] ?? [])));
        $workspaceId = trim((string) ($settings['workspace_id'] ?? ''));
        $countryId   = isset($settings['country_id']) && $settings['country_id'] !== ''
            ? (int) $settings['country_id']
            : null;

        if ($apiKey === '' || empty($accountIds)) {
            return $this->httpResponse()->setError()->setMessage('Automation is missing API key or account IDs.');
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
                $publisher->publerPost($job, $apiKey, $accountIds, $workspaceId) ? $sent++ : $failed++;
            } catch (\Throwable) {
                $failed++;
            }
            usleep(500000); // 0.5s between posts to respect rate limits
        }

        $msg = "Published {$sent} of {$jobs->count()} job(s) to Publer via {$automation->name}" . ($failed ? " ({$failed} failed)" : '') . '.';

        return $sent > 0
            ? $this->httpResponse()->setMessage($msg)
            : $this->httpResponse()->setError()->setMessage($msg);
    }

    public function publerTestJob(SocialAutomation $automation, Request $request, SocialPublisherService $publisher)
    {
        $request->validate(['job_id' => ['required']]);

        // Accept a raw ID or a full URL — extract the numeric ID from the end
        $raw   = trim((string) $request->input('job_id'));
        $jobId = preg_replace('/\D/', '', basename(rtrim($raw, '/')));

        if (! $jobId) {
            return $this->httpResponse()->setError()->setMessage('Could not parse a job ID from the input.');
        }

        $job = Job::with(['company', 'slugable', 'country', 'currency', 'jobTypes'])->find((int) $jobId);
        if (! $job) {
            return $this->httpResponse()->setError()->setMessage("Job #{$jobId} not found.");
        }

        $settings    = $automation->settings ?? [];
        $apiKey      = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            $apiKey = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
        }
        $accountIds  = array_values(array_filter((array) ($settings['account_ids'] ?? [])));
        $workspaceId = trim((string) ($settings['workspace_id'] ?? ''));

        if ($apiKey === '') {
            return $this->httpResponse()->setError()->setMessage('No Publer API key configured for this automation.');
        }

        if (empty($accountIds)) {
            return $this->httpResponse()->setError()->setMessage('No Publer accounts selected. Edit the automation and add accounts first.');
        }

        try {
            $ok = $publisher->publerPost($job, $apiKey, $accountIds, $workspaceId);
            return $ok
                ? $this->httpResponse()->setMessage("Test post sent for \"{$job->name}\" via {$automation->name}.")
                : $this->httpResponse()->setError()->setMessage('Publer returned an error. Check the API key and account IDs in server logs.');
        } catch (\Throwable $e) {
            return $this->httpResponse()->setError()->setMessage('Error: ' . $e->getMessage());
        }
    }

    public function searchJobs(Request $request)
    {
        $q         = trim((string) $request->input('q', ''));
        $countryId = $request->input('country_id');

        $query = Job::query()
            ->with(['company', 'country'])
            ->where('status', JobStatusEnum::PUBLISHED)
            ->orderByDesc('created_at')
            ->limit(12);

        if ($countryId) {
            $query->where('country_id', (int) $countryId);
        }

        if ($q !== '') {
            if (is_numeric($q)) {
                $query->where('id', (int) $q);
            } else {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                });
            }
        }

        $jobs = $query->get()->map(fn (Job $j) => [
            'id'      => $j->id,
            'title'   => $j->name,
            'company' => $j->company?->name ?? '',
            'country' => $j->country?->name ?? '',
        ]);

        return response()->json(['jobs' => $jobs]);
    }
}
