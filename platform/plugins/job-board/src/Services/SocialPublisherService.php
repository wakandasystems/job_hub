<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\JobImageGeneratorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public function buildAiImagePrompt(Job $job): string
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: 'Zambia'));
        $deadline = $job->application_closing_date ?: $job->expire_date;

        // Build a details line to embed in the image
        $details = [];
        if ($company) {
            $details[] = "Company: {$company}";
        }
        $details[] = "Location: {$location}";

        try {
            if (! $job->hide_salary && $job->salary_text) {
                $salary = (string) $job->salary_text;
                if (! in_array(strtolower($salary), ['attractive', 'negotiable', 'competitive'])) {
                    $details[] = "Salary: {$salary}";
                }
            }
        } catch (Throwable) {
            // salary_text can throw if salary_range has an invalid enum value from crawlers
        }

        if ($deadline) {
            $details[] = "Deadline: " . $deadline->format('M j, Y');
        }

        $jobTypes = $job->jobTypes->pluck('name')->filter()->implode(' / ');
        if ($jobTypes) {
            $details[] = "Type: {$jobTypes}";
        }

        $detailsText = implode(' | ', $details);

        $prompt  = "Since you have learned a lot about wakanda jobs can you generate an ai image ad for it, visit https://www.wakandajobs.com and see what the system is about.";
        $prompt .= " The job being advertised is: {$title}";
        if ($company) {
            $prompt .= " at {$company}";
        }
        $prompt .= ".";
        $prompt .= " Make sure the photo is ultra realistic, professional, and trustworthy — it should look like a legitimate corporate job advertisement.";
        $prompt .= " The people in the image must be Black African professionals dressed appropriately for the role.";
        $prompt .= " The image must clearly display the following text: Job Title: {$title}";
        if ($company) {
            $prompt .= " | Company: {$company}";
        }
        $prompt .= " | {$detailsText}.";
        $prompt .= " Include the Wakanda Jobs logo (attached) prominently and use its colors as the design palette.";
        $prompt .= " The overall feel should be modern, clean, and inspire confidence — like a Fortune 500 recruitment ad.";
        $prompt .= " Add a subtle 'Apply Now at wakandajobs.com' call-to-action at the bottom.";

        return $prompt;
    }

    public function buildPlatformPosts(Job $job): array
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: 'Zambia'));
        $deadline = $job->application_closing_date ?: $job->expire_date;
        $url      = route('public.job', $job->slugable?->key ?? $job->id);
        $excerpt  = trim(Str::limit(strip_tags((string) ($job->description ?: $job->content)), 220));

        $salaryLine = '';
        try {
            if (! $job->hide_salary && $job->salary_text) {
                $s = (string) $job->salary_text;
                if (! in_array(strtolower($s), ['attractive', 'negotiable', 'competitive'])) {
                    $salaryLine = $s;
                }
            }
        } catch (Throwable) {}

        $deadlineStr  = $deadline ? $deadline->format('M j, Y') : '';
        $titleSlug    = str_replace(' ', '', $title);
        $companySlug  = str_replace(' ', '', $company);
        $countryName  = trim((string) ($job->country?->name ?? 'Zambia'));
        $countrySlug  = str_replace(' ', '', $countryName);

        // ── TikTok ──────────────────────────────────────────────────────────
        $tiktok  = "🚨 NEW JOB ALERT 🚨\n\n";
        $tiktok .= "🎯 {$title}";
        if ($company) $tiktok .= " @ {$company}";
        $tiktok .= "\n📍 {$location}";
        if ($salaryLine) $tiktok .= "\n💰 {$salaryLine}";
        if ($deadlineStr) $tiktok .= "\n📅 Deadline: {$deadlineStr}";
        $tiktok .= "\n\nDon't miss this opportunity — apply NOW! 👇";
        $tiktok .= "\n🔗 {$url}";
        $tiktok .= "\n\n#JobsIn{$countrySlug} #{$countrySlug}Jobs #JobTok #Hiring #{$countrySlug}Hiring";
        $tiktok .= " #TikTokJobs #JobAlert #NewJob ##{$titleSlug}";
        if ($companySlug) $tiktok .= " #{$companySlug}";
        $tiktok .= " #WakandaJobs #AfricaJobs #GetHired #CareerGoals #JobOpportunity #NowHiring";

        // ── X / Twitter ─────────────────────────────────────────────────────
        // Hard 280-char limit — keep it tight
        $twitterBody  = "🔔 {$title}";
        if ($company) $twitterBody .= " at {$company}";
        $twitterBody .= "\n📍 {$location}";
        if ($salaryLine) $twitterBody .= " | 💰 {$salaryLine}";
        if ($deadlineStr) $twitterBody .= "\n⏰ Deadline: {$deadlineStr}";
        $twitterBody .= "\n\nApply 👉 {$url}";
        $twitterBody .= "\n\n#{$countrySlug}Jobs #Hiring #WakandaJobs";
        // Trim if over 280
        if (mb_strlen($twitterBody) > 280) {
            $shortTitle = Str::limit($title, 40, '…');
            $twitterBody  = "🔔 {$shortTitle}";
            if ($company) $twitterBody .= " · " . Str::limit($company, 30, '…');
            $twitterBody .= "\n📍 {$location}";
            if ($deadlineStr) $twitterBody .= " | ⏰ {$deadlineStr}";
            $twitterBody .= "\n\nApply 👉 {$url}";
            $twitterBody .= "\n#{$countrySlug}Jobs #WakandaJobs";
        }
        $twitter = $twitterBody;

        // ── LinkedIn ────────────────────────────────────────────────────────
        $linkedin  = "🌟 Exciting Career Opportunity: {$title}\n\n";
        if ($company) $linkedin .= "📢 Hiring Company: {$company}\n";
        $linkedin .= "📍 Location: {$location}\n";
        if ($salaryLine) $linkedin .= "💰 Salary: {$salaryLine}\n";
        if ($deadlineStr) $linkedin .= "📅 Application Deadline: {$deadlineStr}\n";
        $linkedin .= "\n";
        if ($excerpt) $linkedin .= "{$excerpt}\n\n";
        $linkedin .= "👉 View full details and apply: {$url}\n\n";
        $linkedin .= "Found on Wakanda Jobs — Africa's growing job platform connecting top talent with leading employers.\n\n";
        $linkedin .= "#JobOpening #Hiring #CareerOpportunity #WakandaJobs #{$countrySlug}Jobs";
        if ($titleSlug) $linkedin .= " #{$titleSlug}";
        if ($companySlug) $linkedin .= " #{$companySlug}";
        $linkedin .= " #ProfessionalDevelopment #AfricaCareers";

        // ── Facebook ────────────────────────────────────────────────────────
        $facebook  = "👋 Hey {$countryName}! We've got an opportunity you don't want to miss! 🎯\n\n";
        $facebook .= "🏷️ Position: {$title}\n";
        if ($company) $facebook .= "🏢 Company: {$company}\n";
        $facebook .= "📍 Location: {$location}\n";
        if ($salaryLine) $facebook .= "💰 Salary: {$salaryLine}\n";
        if ($deadlineStr) $facebook .= "📅 Deadline: {$deadlineStr}\n";
        if ($excerpt) $facebook .= "\n{$excerpt}\n";
        $facebook .= "\n🔗 Apply here: {$url}\n\n";
        $facebook .= "💬 Tag someone who needs a job!\n";
        $facebook .= "🔁 Share to help someone find their next opportunity!\n\n";
        $facebook .= "#WakandaJobs #{$countrySlug}Jobs #Jobs #Hiring #JobOpportunity #NowHiring";

        // ── WhatsApp Channel ────────────────────────────────────────────────
        $whatsapp  = "🔔 *JOB ALERT*\n\n";
        $whatsapp .= "*Position:* {$title}\n";
        if ($company) $whatsapp .= "*Company:* {$company}\n";
        $whatsapp .= "*Location:* {$location}\n";
        if ($salaryLine) $whatsapp .= "*Salary:* {$salaryLine}\n";
        if ($deadlineStr) $whatsapp .= "*Deadline:* {$deadlineStr}\n";
        if ($excerpt) $whatsapp .= "\n{$excerpt}\n";
        $whatsapp .= "\n*Apply Now 👉* {$url}\n\n";
        $whatsapp .= "_Wakanda Jobs — wakandajobs.com_";

        return compact('tiktok', 'twitter', 'linkedin', 'facebook', 'whatsapp');
    }

    public function buildManualSocialPost(Job $job): string
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
        $generateImage    = ! empty($settings['generate_image']);
        $noInlineButtons  = ! empty($settings['no_inline_buttons']);

        if ($token === '' || $chatId === '') {
            return false;
        }

        if ($countryId !== null && (int) $job->country_id !== $countryId) {
            return false;
        }

        return $this->sendTelegramCopyPost($token, $chatId, $job, $automation->getKey(), $generateImage, $noInlineButtons);
    }

    public function sendTelegramCopyPost(string $token, string $chatId, Job $job, ?int $automationId = null, bool $generateImage = false, bool $noInlineButtons = false): bool
    {
        $postText  = $this->buildManualSocialPost($job);
        $imagePath = null;

        if ($generateImage) {
            try {
                $imagePath = app(JobImageGeneratorService::class)->generate($job);
            } catch (Throwable) {
                $imagePath = null;
            }
        }

        if ($imagePath && file_exists($imagePath)) {
            // sendPhoto: caption is capped at 1024 chars by Telegram.
            $caption  = Str::limit($postText, 1020, '…');
            $response = Http::timeout(30)
                ->attach('photo', file_get_contents($imagePath), 'job_banner.jpg')
                ->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                    'chat_id' => $chatId,
                    'caption' => $caption,
                ]);
            @unlink($imagePath);
        } else {
            $response = Http::timeout(20)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'                  => $chatId,
                'text'                     => $postText,
                'disable_web_page_preview' => true,
            ]);
        }

        if (! $response->successful() || ! data_get($response->json(), 'ok')) {
            return false;
        }

        $messageId = data_get($response->json(), 'result.message_id');

        if (! $messageId) {
            return true;
        }

        // When no inline buttons are needed (e.g. public channel posts) we're done.
        if ($noInlineButtons) {
            return true;
        }

        // Log message ID so /clear can delete it later.
        DB::table('telegram_message_log')->insert([
            'automation_id' => $automationId,
            'chat_id'       => $chatId,
            'message_id'    => (string) $messageId,
            'job_id'        => $job->getKey(),
            'created_at'    => now(),
        ]);

        $cacheKey = 'tg_copy_' . Str::uuid();

        $step2Params = [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'cache_key'  => $cacheKey,
            'job_id'     => $job->getKey(),
        ];
        if ($automationId !== null) {
            $step2Params['automation_id'] = $automationId;
        }

        $step2Url = URL::temporarySignedRoute(
            'public.telegram-social-delete',
            now()->addDays(7),
            $step2Params,
        );

        $step1Params = [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'cache_key'  => $cacheKey,
            'job_id'     => $job->getKey(),
        ];
        if ($automationId !== null) {
            $step1Params['automation_id'] = $automationId;
        }

        $step1Url = URL::temporarySignedRoute(
            'public.telegram-social-prompt',
            now()->addDays(7),
            $step1Params,
        );

        // Build AI prompt safely — never let it prevent the button from appearing
        try {
            $aiPrompt = $this->buildAiImagePrompt($job);
        } catch (Throwable) {
            $aiPrompt = "Generate an ultra-realistic professional African job ad image for: {$job->name} at Wakanda Jobs (wakandajobs.com). Include the job title prominently. Use the Wakanda Jobs logo colors. Black African professionals dressed for the role. Clean, trustworthy, corporate feel.";
        }

        try {
            $platformPosts = $this->buildPlatformPosts($job);
        } catch (Throwable) {
            $platformPosts = [];
        }

        Cache::put($cacheKey, [
            'text'           => $postText,
            'ai_prompt'      => $aiPrompt,
            'step2_url'      => $step2Url,
            'platform_posts' => $platformPosts,
        ], now()->addDays(7));

        try {
            Http::timeout(20)->post("https://api.telegram.org/bot{$token}/editMessageReplyMarkup", [
                'chat_id'      => $chatId,
                'message_id'   => $messageId,
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '🎨 Step 1: AI Image Prompt', 'url' => $step1Url],
                        ],
                    ],
                ],
            ]);
        } catch (Throwable) {
            // Non-fatal: message sent, button failed — log but continue
        }

        return true;
    }

}
