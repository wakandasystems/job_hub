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

    public function whapiSendYesterdayJobs(SocialPublisherService $publisher)
    {
        $automations = SocialAutomation::query()
            ->where('platform', 'whapi')
            ->where('is_active', true)
            ->get();

        if ($automations->isEmpty()) {
            return $this->httpResponse()->setError()->setMessage('No active Whapi automations configured.');
        }

        $totalQueued = 0;

        foreach ($automations as $automation) {
            $settings  = $automation->settings ?? [];
            $token     = trim((string) ($settings['token'] ?? ''));
            $channelId = trim((string) ($settings['channel_id'] ?? ''));
            $countryId = isset($settings['country_id']) && $settings['country_id'] !== ''
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

            // Dispatch to the queue so the HTTP request doesn't time out
            $automationId  = $automation->getKey();
            $capturedToken = $token;
            $capturedCh    = $channelId;
            $capturedGw    = $gatewayUrl;

            dispatch(function () use ($jobs, $capturedToken, $capturedCh, $capturedGw, $publisher): void {
                foreach ($jobs as $job) {
                    try {
                        $posts = $publisher->buildPlatformPosts($job);
                        $msg   = $posts['whatsapp'] ?? $job->name;

                        Http::timeout(20)->withToken($capturedToken)->post("{$capturedGw}/messages/text", [
                            'to'   => $capturedCh,
                            'body' => $msg,
                        ]);
                    } catch (\Throwable) {
                        // Silently continue on individual failures
                    }
                    sleep(1);
                }
            })->onQueue('emails');

            $totalQueued += $jobs->count();
        }

        return $this->httpResponse()->setMessage("Queued {$totalQueued} job(s) for WhatsApp Channel delivery.");
    }
}
