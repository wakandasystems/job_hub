<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class SocialPublisherService
{
    public function publishJob(Job $job): array
    {
        $results = [];

        $automations = SocialAutomation::query()
            ->where('is_active', true)
            ->get();

        foreach ($automations as $automation) {
            try {
                $posted = match ($automation->platform) {
                    'facebook' => $this->postToFacebook($automation, $job),
                    'linkedin' => $this->postToLinkedIn($automation, $job),
                    'whatsapp' => $this->postToWhatsApp($automation, $job),
                    'telegram' => $this->postToTelegram($automation, $job),
                    default    => false,
                };

                $results[] = [
                    'automation' => $automation->name,
                    'platform'   => $automation->platform,
                    'success'    => $posted,
                    'error'      => null,
                ];
            } catch (Throwable $e) {
                $results[] = [
                    'automation' => $automation->name,
                    'platform'   => $automation->platform,
                    'success'    => false,
                    'error'      => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    protected function buildJobMessage(Job $job): string
    {
        $excerpt = Str::limit(strip_tags((string) $job->description), 280);
        $url     = route('public.job', $job->slugable?->key ?? $job->id);
        $company = $job->company?->name ?? '';
        $location = $job->address ?? 'Zambia';

        $lines = ["🔔 New Job: {$job->name}"];

        if ($company) {
            $lines[] = "🏢 {$company}";
        }

        $lines[] = "📍 {$location}";

        if ($excerpt) {
            $lines[] = '';
            $lines[] = $excerpt;
        }

        $lines[] = '';
        $lines[] = "🔗 Apply: {$url}";

        return implode("\n", $lines);
    }

    protected function buildManualSocialPost(Job $job): string
    {
        $url = route('public.job', $job->slugable?->key ?? $job->id);
        $company = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->address ?: 'Zambia'));
        $deadline = $job->application_closing_date ?: $job->expire_date;

        $lines = [
            'Job Opportunity: ' . $job->name,
        ];

        if ($company !== '') {
            $lines[] = 'Company: ' . $company;
        }

        $lines[] = 'Location: ' . $location;

        if ($deadline) {
            $lines[] = 'Deadline: ' . $deadline->format('M j, Y');
        }

        $lines[] = '';
        $lines[] = Str::limit(strip_tags((string) ($job->description ?: $job->content)), 240);
        $lines[] = '';
        $lines[] = 'Apply here: ' . $url;
        $lines[] = '';
        $lines[] = '#Jobs #ZambiaJobs #Hiring #WakandaJobs';

        return trim(implode("\n", array_filter($lines, fn ($line) => $line !== null)));
    }

    // -------------------------------------------------------------------------
    // Facebook
    // -------------------------------------------------------------------------

    protected function postToFacebook(SocialAutomation $automation, Job $job): bool
    {
        $settings = $automation->settings ?? [];
        $pageId   = trim((string) ($settings['page_id'] ?? ''));
        $token    = trim((string) ($settings['access_token'] ?? ''));

        if ($pageId === '' || $token === '') {
            return false;
        }

        $response = Http::timeout(20)
            ->post("https://graph.facebook.com/v19.0/{$pageId}/feed", [
                'message'      => $this->buildJobMessage($job),
                'access_token' => $token,
            ]);

        return $response->successful() && isset($response->json()['id']);
    }

    // -------------------------------------------------------------------------
    // LinkedIn
    // -------------------------------------------------------------------------

    protected function postToLinkedIn(SocialAutomation $automation, Job $job): bool
    {
        $settings = $automation->settings ?? [];
        $orgId    = trim((string) ($settings['org_id'] ?? ''));
        $token    = trim((string) ($settings['access_token'] ?? ''));

        if ($orgId === '' || $token === '') {
            return false;
        }

        $response = Http::timeout(20)
            ->withToken($token)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->post('https://api.linkedin.com/v2/ugcPosts', [
                'author'          => "urn:li:organization:{$orgId}",
                'lifecycleState'  => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary'   => ['text' => $this->buildJobMessage($job)],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ]);

        return $response->successful();
    }

    // -------------------------------------------------------------------------
    // WhatsApp (Meta Business Cloud API)
    // -------------------------------------------------------------------------

    protected function postToWhatsApp(SocialAutomation $automation, Job $job): bool
    {
        $settings   = $automation->settings ?? [];
        $phoneId    = trim((string) ($settings['phone_number_id'] ?? ''));
        $token      = trim((string) ($settings['access_token'] ?? ''));
        $recipient  = trim((string) ($settings['recipient'] ?? '')); // phone or group

        if ($phoneId === '' || $token === '' || $recipient === '') {
            return false;
        }

        $response = Http::timeout(20)
            ->withToken($token)
            ->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'   => $recipient,
                'type' => 'text',
                'text' => ['body' => $this->buildJobMessage($job)],
            ]);

        return $response->successful();
    }

    // -------------------------------------------------------------------------
    // Telegram copy queue
    // -------------------------------------------------------------------------

    protected function postToTelegram(SocialAutomation $automation, Job $job): bool
    {
        $settings  = $automation->settings ?? [];
        $token     = trim((string) ($settings['bot_token'] ?? setting('telegram_bot_token')));
        $chatId    = trim((string) ($settings['chat_id'] ?? ''));
        $countryId = isset($settings['country_id']) && $settings['country_id'] !== ''
            ? (int) $settings['country_id']
            : null;

        if ($token === '' || $chatId === '') {
            return false;
        }

        if ($countryId !== null && (int) $job->country_id !== $countryId) {
            return false;
        }

        return $this->sendTelegramCopyPost($token, $chatId, $job, $automation->getKey());
    }

    public function sendTelegramCopyPost(string $token, string $chatId, Job $job, ?int $automationId = null): bool
    {
        $postText = $this->buildManualSocialPost($job);
        $message  = $postText;

        $response = Http::timeout(20)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'                  => $chatId,
            'text'                     => $message,
            'disable_web_page_preview' => true,
        ]);

        if (! $response->successful() || ! data_get($response->json(), 'ok')) {
            return false;
        }

        $messageId = data_get($response->json(), 'result.message_id');

        if (! $messageId) {
            return true;
        }

        // Cache post text so the copy-page can put it on the clipboard.
        $cacheKey = 'tg_copy_' . Str::uuid();
        Cache::put($cacheKey, $postText, now()->addDays(7));

        $params = [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'cache_key'  => $cacheKey,
        ];

        if ($automationId !== null) {
            $params['automation_id'] = $automationId;
        }

        $copyUrl = URL::temporarySignedRoute(
            'public.telegram-social-delete',
            now()->addDays(7),
            $params,
        );

        Http::timeout(20)->post("https://api.telegram.org/bot{$token}/editMessageReplyMarkup", [
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '📋 Copy & Dismiss', 'url' => $copyUrl],
                    ],
                ],
            ],
        ]);

        return true;
    }
}
