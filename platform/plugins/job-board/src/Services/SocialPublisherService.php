<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Illuminate\Support\Facades\Http;
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
        $url     = route('jobs.show', $job->slug ?? $job->id);
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
}
