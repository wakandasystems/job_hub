<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class TelegramSocialMessageController extends BaseController
{
    // -------------------------------------------------------------------------
    // Admin Post Kit (no Telegram context — opened from admin job edit page)
    // -------------------------------------------------------------------------

    public function showAdmin(Job $job): \Illuminate\Http\Response
    {
        $job->load(['company', 'slugable', 'country', 'currency', 'jobTypes']);
        $publisher = app(SocialPublisherService::class);

        $aiPrompt = $tiktokImagePrompt = $storyboardPrompt = $geminiPrompt = null;
        $coverImagePrompt = $facebookImagePrompt = $linkedinImagePrompt = $twitterImagePrompt = null;
        $platformPosts = [];
        $companyLogoUrl = $companyName = null;

        try { $aiPrompt = $publisher->buildAiImagePrompt($job); } catch (\Throwable) {}
        try { $tiktokImagePrompt = $publisher->buildTikTokImagePrompt($job); } catch (\Throwable) {}
        try { $storyboardPrompt = $publisher->buildStoryboardPrompt($job); } catch (\Throwable) {}
        try { $geminiPrompt = $publisher->buildGeminiVideoPrompt($job); } catch (\Throwable) {}
        try { $coverImagePrompt = $publisher->buildCoverImagePrompt($job); } catch (\Throwable) {}
        try { $facebookImagePrompt = $publisher->buildFacebookImagePrompt($job); } catch (\Throwable) {}
        try { $linkedinImagePrompt = $publisher->buildLinkedInImagePrompt($job); } catch (\Throwable) {}
        try { $twitterImagePrompt = $publisher->buildTwitterImagePrompt($job); } catch (\Throwable) {}
        try { $platformPosts = $publisher->buildPlatformPosts($job); } catch (\Throwable) {}
        $employerImagePrompt = $employerPitchMessage = null;
        try { $employerImagePrompt = $publisher->buildEmployerImagePrompt($job); } catch (\Throwable) {}
        try { $employerPitchMessage = $publisher->buildEmployerPitchMessage($job); } catch (\Throwable) {}

        if (! $aiPrompt) {
            return response($this->expiredHtml());
        }

        $jobImages = [
            'cover_image'    => null,
            'tiktok_image'   => null,
            'facebook_image' => null,
            'linkedin_image' => null,
            'whatsapp_image' => null,
            'twitter_image'  => null,
            'employer_image' => null,
        ];
        $companyId = null;
        $jobUrl    = null;
        $employerEmail  = null;
        $employerPhone  = null;
        $employerEmails = [];
        $employerLastPitchAt = null;

        foreach (array_keys($jobImages) as $col) {
            if (! empty($job->{$col})) {
                $jobImages[$col] = \Botble\Media\Facades\RvMedia::getImageUrl($job->{$col});
            }
        }
        if ($job->company) {
            $companyId = $job->company->id;
            $employerEmails = collect($job->company->contact_emails ?? [])->filter()->values()->all();
            $employerEmail = $employerEmails[0] ?? $job->company->email;
            $employerPhone = collect($job->company->contact_numbers ?? [])->first() ?: $job->company->phone;
            $employerLastPitchAt = $job->company->last_employer_pitch_at;
            if (! empty($job->company->logo)) {
                $companyLogoUrl = \Botble\Media\Facades\RvMedia::getImageUrl($job->company->logo);
                $companyName    = $job->company->name;
            }
        }
        $jobName = $job->name;
        if (! empty($job->slugable->key)) {
            $jobUrl = rtrim(config('app.url'), '/') . '/jobs/' . $job->slugable->key;
        }

        $uploadParams = array_filter(['job_id' => $job->getKey(), 'company_id' => $companyId]);
        $makeUploadUrl = fn (string $type) => URL::temporarySignedRoute(
            'public.telegram-social-upload',
            now()->addDays(7),
            array_merge($uploadParams, ['type' => $type]),
        );
        $uploadUrls = [
            'company_logo'   => $makeUploadUrl('company_logo'),
            'cover_image'    => $makeUploadUrl('cover_image'),
            'tiktok_image'   => $makeUploadUrl('tiktok_image'),
            'facebook_image' => $makeUploadUrl('facebook_image'),
            'linkedin_image' => $makeUploadUrl('linkedin_image'),
            'whatsapp_image' => $makeUploadUrl('whatsapp_image'),
            'twitter_image'  => $makeUploadUrl('twitter_image'),
            'employer_image' => $makeUploadUrl('employer_image'),
        ];

        $makeGenerateUrl = fn (string $type) => URL::temporarySignedRoute(
            'public.telegram-social-generate',
            now()->addDays(7),
            array_merge(array_filter(['job_id' => $job->getKey()]), ['type' => $type]),
        );
        $openAiConfigured = app(\Botble\JobBoard\Services\OpenAiImageService::class)->isConfigured();
        $generateUrls = [];
        if ($openAiConfigured) {
            foreach (\Botble\JobBoard\Services\OpenAiImageService::slotTypes() as $slotType) {
                $generateUrls[$slotType] = $makeGenerateUrl($slotType);
            }
        }

        $sendToEmployerUrl = URL::temporarySignedRoute(
            'public.telegram-social-send-to-employer',
            now()->addDays(7),
            array_filter(['job_id' => $job->getKey()]),
        );

        $adminEditUrl = route('jobs.edit', $job->getKey());

        $whapiAutomation  = \Botble\JobBoard\Models\SocialAutomation::query()
            ->where('platform', 'whapi')->where('is_active', true)->get()
            ->first(fn ($a) => !($a->settings['country_id'] ?? null) || (int) ($a->settings['country_id']) === (int) $job->country_id);
        $whapiSendUrl     = $whapiAutomation ? route('job-board.automations.whapi-send-job', $job->getKey()) : null;
        $whapiChannelName = $whapiAutomation?->name ?? '';

        $publerAutomation = \Botble\JobBoard\Models\SocialAutomation::query()
            ->where('platform', 'publer')->where('is_active', true)->get()
            ->first(fn ($a) => !($a->settings['country_id'] ?? null) || (int) ($a->settings['country_id']) === (int) $job->country_id);
        $publerSendUrl    = $publerAutomation ? route('job-board.automations.publer-send-job', $job->getKey()) : null;

        return response($this->renderPostKitHtml(
            aiPrompt: $aiPrompt,
            storyboardPrompt: $storyboardPrompt,
            geminiPrompt: $geminiPrompt,
            tiktokImagePrompt: $tiktokImagePrompt,
            coverImagePrompt: $coverImagePrompt,
            facebookImagePrompt: $facebookImagePrompt,
            linkedinImagePrompt: $linkedinImagePrompt,
            twitterImagePrompt: $twitterImagePrompt,
            platformPosts: $platformPosts,
            jobImages: $jobImages,
            companyLogoUrl: $companyLogoUrl,
            companyName: $companyName,
            jobName: $jobName,
            jobUrl: $jobUrl,
            uploadUrls: $uploadUrls,
            step2Url: null,
            heroBadge: '🔧 Admin Post Kit',
            adminEditUrl: $adminEditUrl,
            whapiSendUrl: $whapiSendUrl,
            whapiChannelName: $whapiChannelName,
            publerSendUrl: $publerSendUrl,
            employerImagePrompt: $employerImagePrompt,
            employerPitchMessage: $employerPitchMessage,
            employerEmail: $employerEmail,
            employerPhone: $employerPhone,
            sendToEmployerUrl: $sendToEmployerUrl,
            employerEmails: $employerEmails,
            employerLastPitchAt: $employerLastPitchAt,
            generateUrls: $generateUrls,
            openAiConfigured: $openAiConfigured,
        ));
    }

    // -------------------------------------------------------------------------
    // Step 1: AI image prompt + platform posts (does NOT delete Telegram message)
    // -------------------------------------------------------------------------

    public function show(Request $request)
    {
        $cacheKey = (string) $request->query('cache_key', '');
        $jobId    = $request->query('job_id');

        $cached = $cacheKey ? Cache::get($cacheKey) : null;

        $aiPrompt       = null;
        $step2Url       = null;
        $platformPosts  = [];
        $companyLogoUrl = null;
        $companyName    = null;

        $storyboardPrompt    = null;
        $geminiPrompt        = null;
        $tiktokImagePrompt   = null;
        $coverImagePrompt    = null;
        $facebookImagePrompt = null;
        $linkedinImagePrompt = null;
        $twitterImagePrompt  = null;

        $jobName = null;

        if (is_array($cached)) {
            $aiPrompt          = $cached['ai_prompt'] ?? null;
            $tiktokImagePrompt = $cached['tiktok_image_prompt'] ?? null;
            $storyboardPrompt  = $cached['storyboard_prompt'] ?? null;
            $geminiPrompt      = $cached['gemini_prompt'] ?? null;
            $coverImagePrompt  = $cached['cover_image_prompt'] ?? null;
            $step2Url          = $cached['step2_url'] ?? null;
            $platformPosts     = $cached['platform_posts'] ?? [];
            $companyLogoUrl    = $cached['company_logo_url'] ?? null;
            $companyName       = $cached['company_name'] ?? null;
            $jobName           = $cached['job_name'] ?? null;
        }

        // Fallback: regenerate any missing fields from the job record
        $needsRegeneration = (! $aiPrompt || empty($platformPosts) || ! $storyboardPrompt || ! $geminiPrompt || ! $tiktokImagePrompt || ! $coverImagePrompt || ! $facebookImagePrompt || ! $linkedinImagePrompt || ! $twitterImagePrompt);
        if ($needsRegeneration && $jobId) {
            $job = Job::with(['company', 'slugable', 'country', 'currency', 'jobTypes'])->find($jobId);
            if ($job) {
                $publisher = app(SocialPublisherService::class);
                if (! $aiPrompt) {
                    try { $aiPrompt = $publisher->buildAiImagePrompt($job); } catch (\Throwable) {}
                }
                if (! $tiktokImagePrompt) {
                    try { $tiktokImagePrompt = $publisher->buildTikTokImagePrompt($job); } catch (\Throwable) {}
                }
                if (! $storyboardPrompt) {
                    try { $storyboardPrompt = $publisher->buildStoryboardPrompt($job); } catch (\Throwable) {}
                }
                if (! $geminiPrompt) {
                    try { $geminiPrompt = $publisher->buildGeminiVideoPrompt($job); } catch (\Throwable) {}
                }
                if (! $coverImagePrompt) {
                    try { $coverImagePrompt = $publisher->buildCoverImagePrompt($job); } catch (\Throwable) {}
                }
                if (! $facebookImagePrompt) {
                    try { $facebookImagePrompt = $publisher->buildFacebookImagePrompt($job); } catch (\Throwable) {}
                }
                if (! $linkedinImagePrompt) {
                    try { $linkedinImagePrompt = $publisher->buildLinkedInImagePrompt($job); } catch (\Throwable) {}
                }
                if (! $twitterImagePrompt) {
                    try { $twitterImagePrompt = $publisher->buildTwitterImagePrompt($job); } catch (\Throwable) {}
                }
                if (empty($platformPosts)) {
                    try { $platformPosts = $publisher->buildPlatformPosts($job); } catch (\Throwable) {}
                }
            }
        }

        if (! $aiPrompt) {
            return response($this->expiredHtml());
        }

        // Load job + company images fresh (always live, not from cache)
        $jobImages = [
            'cover_image'    => null,
            'tiktok_image'   => null,
            'facebook_image' => null,
            'linkedin_image' => null,
            'whatsapp_image' => null,
            'twitter_image'  => null,
            'employer_image' => null,
        ];
        $companyId = null;
        $jobUrl    = null;
        $employerImagePrompt   = null;
        $employerPitchMessage  = null;
        $employerEmail         = null;
        $employerPhone         = null;
        $employerEmails        = [];
        $employerLastPitchAt   = null;

        if ($jobId) {
            try {
                $liveJob = Job::with(['company', 'slugable', 'country', 'currency', 'jobTypes'])->find($jobId);
                if ($liveJob) {
                    $livePublisher = app(SocialPublisherService::class);
                    try { $coverImagePrompt = $livePublisher->buildCoverImagePrompt($liveJob); } catch (\Throwable) {}
                    try { $employerImagePrompt  = $livePublisher->buildEmployerImagePrompt($liveJob); } catch (\Throwable) {}
                    try { $employerPitchMessage = $livePublisher->buildEmployerPitchMessage($liveJob); } catch (\Throwable) {}

                    foreach (array_keys($jobImages) as $col) {
                        if (! empty($liveJob->{$col})) {
                            $jobImages[$col] = \Botble\Media\Facades\RvMedia::getImageUrl($liveJob->{$col});
                        }
                    }
                    if ($liveJob->company) {
                        $companyId    = $liveJob->company->id;
                        $employerEmails = collect($liveJob->company->contact_emails ?? [])->filter()->values()->all();
                        $employerEmail = $employerEmails[0] ?? $liveJob->company->email;
                        $employerPhone = collect($liveJob->company->contact_numbers ?? [])->first() ?: $liveJob->company->phone;
                        $employerLastPitchAt = $liveJob->company->last_employer_pitch_at;
                        $liveCompanyLogoUrl = ! empty($liveJob->company->logo)
                            ? \Botble\Media\Facades\RvMedia::getImageUrl($liveJob->company->logo)
                            : null;

                        if ($liveCompanyLogoUrl && $companyLogoUrl !== $liveCompanyLogoUrl) {
                            $companyLogoUrl = $liveCompanyLogoUrl;
                            // Regenerate cached prompts so they always embed the current company logo.
                            try { $aiPrompt         = $livePublisher->buildAiImagePrompt($liveJob); } catch (\Throwable) {}
                            try { $tiktokImagePrompt = $livePublisher->buildTikTokImagePrompt($liveJob); } catch (\Throwable) {}
                            try { $storyboardPrompt  = $livePublisher->buildStoryboardPrompt($liveJob); } catch (\Throwable) {}
                            try { $geminiPrompt       = $livePublisher->buildGeminiVideoPrompt($liveJob); } catch (\Throwable) {}
                        }
                        if (! $companyName) {
                            $companyName = $liveJob->company->name;
                        }
                    }
                    if (! $jobName && $liveJob->name) {
                        $jobName = $liveJob->name;
                    }
                    if (! empty($liveJob->slugable->key)) {
                        $jobUrl = rtrim(config('app.url'), '/') . '/jobs/' . $liveJob->slugable->key;
                    }
                }
            } catch (\Throwable) {}
        }

        // Regenerate step2Url if cache was cleared
        if (! $step2Url) {
            $chatId       = (string) $request->query('chat_id', '');
            $messageId    = (string) $request->query('message_id', '');
            $automationId = $request->query('automation_id');

            if ($chatId !== '' && $messageId !== '') {
                $step2Params = [
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                    'cache_key'  => $cacheKey,
                    'job_id'     => $jobId,
                ];
                if ($automationId !== null) {
                    $step2Params['automation_id'] = $automationId;
                }
                $step2Url = URL::temporarySignedRoute(
                    'public.telegram-social-delete',
                    now()->addDays(7),
                    $step2Params,
                );
            }
        }

        // Generate signed upload URLs (expire in 7 days, same as page)
        $uploadParams = array_filter([
            'job_id'       => $jobId,
            'company_id'   => $companyId,
            'automation_id' => $request->query('automation_id'),
        ]);

        $makeUploadUrl = fn (string $type) => URL::temporarySignedRoute(
            'public.telegram-social-upload',
            now()->addDays(7),
            array_merge($uploadParams, ['type' => $type]),
        );

        $uploadUrls = [
            'company_logo'   => $makeUploadUrl('company_logo'),
            'cover_image'    => $makeUploadUrl('cover_image'),
            'tiktok_image'   => $makeUploadUrl('tiktok_image'),
            'facebook_image' => $makeUploadUrl('facebook_image'),
            'linkedin_image' => $makeUploadUrl('linkedin_image'),
            'whatsapp_image' => $makeUploadUrl('whatsapp_image'),
            'twitter_image'  => $makeUploadUrl('twitter_image'),
            'employer_image' => $makeUploadUrl('employer_image'),
        ];

        // Signed OpenAI generate URLs (one per generatable slot)
        $makeGenerateUrl = fn (string $type) => URL::temporarySignedRoute(
            'public.telegram-social-generate',
            now()->addDays(7),
            array_merge(array_filter(['job_id' => $jobId]), ['type' => $type]),
        );
        $openAiConfigured = app(\Botble\JobBoard\Services\OpenAiImageService::class)->isConfigured();
        $generateUrls = [];
        if ($openAiConfigured && $jobId) {
            foreach (\Botble\JobBoard\Services\OpenAiImageService::slotTypes() as $slotType) {
                $generateUrls[$slotType] = $makeGenerateUrl($slotType);
            }
        }

        // Signed URL for sending the "your job ad is live" pitch to the employer
        $sendToEmployerUrl = URL::temporarySignedRoute(
            'public.telegram-social-send-to-employer',
            now()->addDays(7),
            array_filter(['job_id' => $jobId]),
        );

        // Whapi send-to-channel URL for this job
        $whapiSendUrl = null; $whapiChannelName = '';
        if ($jobId && isset($liveJob)) {
            $wa = \Botble\JobBoard\Models\SocialAutomation::query()
                ->where('platform', 'whapi')->where('is_active', true)->get()
                ->first(fn ($a) => !($a->settings['country_id'] ?? null) || (int) ($a->settings['country_id']) === (int) $liveJob->country_id);
            if ($wa) {
                $whapiSendUrl     = route('job-board.automations.whapi-send-job', $jobId);
                $whapiChannelName = $wa->name;
            }
        }

        // Publer send URL for this job (used for WhatsApp→FB/LinkedIn and TikTok posting)
        $publerSendUrl = null;
        if ($jobId && isset($liveJob)) {
            $pa = \Botble\JobBoard\Models\SocialAutomation::query()
                ->where('platform', 'publer')->where('is_active', true)->get()
                ->first(fn ($a) => !($a->settings['country_id'] ?? null) || (int) ($a->settings['country_id']) === (int) $liveJob->country_id);
            if ($pa) {
                $publerSendUrl = route('job-board.automations.publer-send-job', $jobId);
            }
        }

        $storyboardSafe    = mb_convert_encoding((string) $storyboardPrompt, 'UTF-8', 'UTF-8');
        $geminiSafe        = mb_convert_encoding((string) $geminiPrompt, 'UTF-8', 'UTF-8');
        $tiktokImageSafe   = mb_convert_encoding((string) $tiktokImagePrompt, 'UTF-8', 'UTF-8');
        $coverImageSafe    = mb_convert_encoding((string) $coverImagePrompt, 'UTF-8', 'UTF-8');
        $aiPromptJson      = json_encode($aiPrompt, JSON_UNESCAPED_UNICODE);
        $facebookImageSafe = mb_convert_encoding((string) $facebookImagePrompt, 'UTF-8', 'UTF-8');
        $linkedinImageSafe = mb_convert_encoding((string) $linkedinImagePrompt, 'UTF-8', 'UTF-8');
        $twitterImageSafe  = mb_convert_encoding((string) $twitterImagePrompt, 'UTF-8', 'UTF-8');
        $employerImageSafe = mb_convert_encoding((string) $employerImagePrompt, 'UTF-8', 'UTF-8');
        $employerPitchSafe = mb_convert_encoding((string) $employerPitchMessage, 'UTF-8', 'UTF-8');

        $storyboardJson    = json_encode($storyboardSafe, JSON_UNESCAPED_UNICODE);
        $geminiJson        = json_encode($geminiSafe, JSON_UNESCAPED_UNICODE);
        $tiktokImageJson   = json_encode($tiktokImageSafe, JSON_UNESCAPED_UNICODE);
        $step2UrlJson      = json_encode($step2Url ?? '', JSON_UNESCAPED_UNICODE);
        $uploadUrlsJson    = json_encode($uploadUrls, JSON_UNESCAPED_UNICODE);
        $generateUrlsJson  = json_encode($generateUrls, JSON_UNESCAPED_UNICODE);
        $openAiConfiguredJson = $openAiConfigured ? 'true' : 'false';
        $jobImagesJson     = json_encode($jobImages, JSON_UNESCAPED_UNICODE);
        $companyLogoJson   = json_encode($companyLogoUrl, JSON_UNESCAPED_UNICODE);
        $companyNameJson   = json_encode((string) ($companyName ?? ''), JSON_UNESCAPED_UNICODE);
        $csrfToken         = csrf_token();
        $whapiSendUrlJson  = json_encode($whapiSendUrl ?? null, JSON_UNESCAPED_UNICODE);
        $whapiChannelJson  = json_encode($whapiChannelName ?? '', JSON_UNESCAPED_UNICODE);
        $publerSendUrlJson = json_encode($publerSendUrl ?? null, JSON_UNESCAPED_UNICODE);
        $sendToEmployerUrlJson = json_encode($sendToEmployerUrl, JSON_UNESCAPED_UNICODE);
        $employerPitchJson     = json_encode($employerPitchSafe, JSON_UNESCAPED_UNICODE);
        $employerEmailJson     = json_encode($employerEmail, JSON_UNESCAPED_UNICODE);
        $employerPhoneJson     = json_encode($employerPhone, JSON_UNESCAPED_UNICODE);

        // Per-slot copy prompts (whatsapp reuses the general 9:16 portrait prompt)
        $slotPromptsJson = json_encode([
            'cover_image'    => $coverImageSafe,
            'tiktok_image'   => $tiktokImageSafe,
            'whatsapp_image' => $aiPrompt,
            'facebook_image' => $facebookImageSafe,
            'linkedin_image' => $linkedinImageSafe,
            'twitter_image'  => $twitterImageSafe,
            'employer_image' => $employerImageSafe,
        ], JSON_UNESCAPED_UNICODE);

        // Platform post text per social slot (image URL appended so user knows what to attach)
        $imgRef = fn(?string $url): string => $url ? "\n\n📎 Image: {$url}" : '';
        $slotPostsJson = json_encode([
            'tiktok_image'   => ($platformPosts['tiktok']   ?? '') . $imgRef($jobImages['tiktok_image']   ?: $companyLogoUrl),
            'whatsapp_image' => ($platformPosts['whatsapp']  ?? '') . $imgRef($jobImages['whatsapp_image'] ?: $companyLogoUrl),
            'facebook_image' => ($platformPosts['facebook']  ?? '') . $imgRef($jobImages['facebook_image'] ?: $companyLogoUrl),
            'linkedin_image' => ($platformPosts['linkedin']  ?? '') . $imgRef($jobImages['linkedin_image'] ?: $companyLogoUrl),
            'twitter_image'  => ($platformPosts['twitter']   ?? '') . $imgRef($jobImages['twitter_image']  ?: $companyLogoUrl),
            'employer_image' => $employerPitchSafe . $imgRef($jobImages['employer_image'] ?: $jobImages['whatsapp_image'] ?: $companyLogoUrl),
        ], JSON_UNESCAPED_UNICODE);

        $escapedPrompt      = htmlspecialchars($aiPrompt, ENT_QUOTES, 'UTF-8');
        $escapedStoryboard  = htmlspecialchars($storyboardSafe, ENT_QUOTES, 'UTF-8');
        $escapedGemini      = htmlspecialchars($geminiSafe, ENT_QUOTES, 'UTF-8');
        $escapedTiktokImage = htmlspecialchars($tiktokImageSafe, ENT_QUOTES, 'UTF-8');
        $escapedCoverImage  = htmlspecialchars($coverImageSafe, ENT_QUOTES, 'UTF-8');

        $escapedJobName  = htmlspecialchars((string) ($jobName ?? 'New Job'), ENT_QUOTES, 'UTF-8');
        $escapedCompany  = htmlspecialchars((string) ($companyName ?? ''), ENT_QUOTES, 'UTF-8');
        $escapedJobUrl   = $jobUrl ? htmlspecialchars($jobUrl, ENT_QUOTES, 'UTF-8') : '';
        $heroSubLine     = $escapedCompany ? "{$escapedJobName} &middot; {$escapedCompany}" : $escapedJobName;
        $heroCompanyHtml = $escapedCompany ? "<div class=\"hero-company\">🏢 {$escapedCompany}</div>" : '';
        $jobBtnHtml      = $escapedJobUrl ? "<a href=\"{$escapedJobUrl}\" target=\"_blank\" rel=\"noopener\" class=\"bb-job\">🔗 View Job</a>" : '';
        $copyCompanyNameHtml = ! $companyLogoUrl && $escapedCompany
            ? '<button type="button" class="img-copy-btn" onclick="doCopy(companyName,this,\'Copy company name\')" title="Copy company name">📋 Copy name</button>'
            : '';

        // Employer pitch tab
        $escapedEmployerPitch = htmlspecialchars($employerPitchSafe, ENT_QUOTES, 'UTF-8');
        // Auto-pitch (SocialPublisherService::autoPitchEmployerEmail) already emails the
        // employer once per day after the AI image finishes generating. Disable the manual
        // button when that's already happened today so we don't double-email the employer.
        $employerPitchedToday = $employerLastPitchAt && $employerLastPitchAt->greaterThanOrEqualTo(now()->startOfDay());
        $employerEmailBtnHtml = $employerEmail
            ? '<button class="btn btn-dark send-employer-email-btn" onclick="sendToEmployer(\'email\',this)"' . ($employerPitchedToday ? ' disabled' : '') . '>📧 Email Employer</button>'
            : '';
        $employerAlreadyPitchedHtml = $employerPitchedToday
            ? '<div class="tip-blue">✅ Already emailed employer today at ' . $employerLastPitchAt->format('d M, H:i')
                . '. <a href="#" onclick="this.closest(\'.card\').querySelector(\'.send-employer-email-btn\').disabled=false;this.remove();return false;" style="color:#7c3aed">Send again anyway</a></div>'
            : '';
        $employerWhatsappBtnHtml = $employerPhone
            ? '<button class="btn btn-purple" onclick="sendToEmployer(\'whatsapp\',this)">💬 WhatsApp Employer</button>'
            : '';
        $employerNoContactHtml = (! $employerEmail && ! $employerPhone)
            ? '<div class="tip-amber">⚠️ No contact email or WhatsApp number on file for this employer yet.</div>'
            : '';

        $employerEmailsDisplay = $employerEmails ?: array_filter([$employerEmail]);
        $employerEmailsHtml = '';
        if ($employerEmailsDisplay) {
            $emailChips = implode(' &nbsp;·&nbsp; ', array_map(
                fn ($e) => htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8'),
                $employerEmailsDisplay,
            ));
            $emailNote = count($employerEmailsDisplay) > 1 ? ' (first one used for "Email Employer")' : '';
            $employerEmailsHtml = "<div class=\"tip-blue\">📧 Emails on file{$emailNote}: <strong>{$emailChips}</strong></div>";
        }

        // Attachment tip (Image tab)
        $wjLogo1 = 'https://www.wakandajobs.com/storage/gemini-generated-image-s1e9dgs1e9dgs1e9.png';
        $wjLogo2 = 'https://www.wakandajobs.com/storage/chatgpt-image-may-14-2026-03-00-04-pm.png';
        $attachmentTipHtml  = '<div class="tip-amber">💡 <strong>Attach these to ChatGPT BEFORE pasting the prompt</strong> (ChatGPT cannot fetch URLs):<br>';
        $attachmentTipHtml .= '<a href="' . $wjLogo1 . '" target="_blank" rel="noopener" class="logo-link">WJ Logo 1 →</a>';
        $attachmentTipHtml .= ' &nbsp;|&nbsp; <a href="' . $wjLogo2 . '" target="_blank" rel="noopener" class="logo-link">WJ Logo 2 →</a>';
        if ($companyLogoUrl) {
            $escapedLogoUrl  = htmlspecialchars($companyLogoUrl, ENT_QUOTES, 'UTF-8');
            $escapedCompName = htmlspecialchars((string) $companyName, ENT_QUOTES, 'UTF-8');
            $attachmentTipHtml .= ' &nbsp;|&nbsp; <a href="' . $escapedLogoUrl . '" target="_blank" rel="noopener" class="logo-link">' . $escapedCompName . ' logo →</a>';
        }
        $attachmentTipHtml .= '</div>';

        // Next button (bottom bar)
        $nextBtnHtml = $step2Url
            ? '<a href="' . htmlspecialchars($step2Url, ENT_QUOTES) . '" class="bb-next">Next: Get Post Text →</a>'
            : '<span class="bb-next bb-next--disabled">Next: Get Post Text</span>';


        $faviconUrl = htmlspecialchars((string) \Botble\Base\Facades\AdminHelper::getAdminFaviconUrl(), ENT_QUOTES, 'UTF-8');
        $faviconType = htmlspecialchars((string) setting('admin_favicon_type', 'image/x-icon'), ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Post Kit — Wakanda Jobs</title>
            <meta name="csrf-token" content="{$csrfToken}">
            <link rel="icon shortcut" href="{$faviconUrl}" type="{$faviconType}">
            <meta property="og:image" content="{$faviconUrl}">
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                :root{
                    --p:#7c3aed;--pd:#5b21b6;--pl:#a78bfa;
                    --dark:#0f172a;--dark2:#1e293b;--dark3:#334155;
                    --slate:#f1f5f9;--muted:#64748b;
                }
                body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:var(--slate);min-height:100vh;padding-bottom:90px}
                .page{max-width:640px;margin:0 auto}

                /* ── Hero ── */
                .hero{background:linear-gradient(135deg,#3b0764 0%,#6d28d9 55%,#7c3aed 100%);padding:22px 16px 0;position:relative;overflow:hidden}
                .hero::before{content:'';position:absolute;top:-60px;right:-60px;width:220px;height:220px;background:radial-gradient(circle,rgba(167,139,250,.25) 0%,transparent 70%);pointer-events:none}
                .hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:22px;background:var(--slate);border-radius:22px 22px 0 0}
                .hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);color:#fff;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;border:1px solid rgba(255,255,255,.3);backdrop-filter:blur(6px);margin-bottom:10px}
                .hero h1{color:#fff;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;opacity:.7;margin-bottom:5px;position:relative}
                .hero-job-title{color:#fff;font-size:20px;font-weight:800;line-height:1.25;margin-bottom:4px;position:relative}
                .hero-company{color:rgba(255,255,255,.75);font-size:13px;font-weight:500;margin-bottom:10px;position:relative}
                .hero-job-link{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:12px;font-weight:700;text-decoration:none;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);border-radius:9px;padding:7px 14px;margin-bottom:18px;position:relative;transition:background .15s;backdrop-filter:blur(6px)}
                .hero-job-link:hover{background:rgba(255,255,255,.28);color:#fff}

                /* ── Tab bar ── */
                .tab-bar{position:sticky;top:0;z-index:100;background:var(--slate);padding:10px 16px 0;border-bottom:2px solid #e2e8f0}
                .tab-nav{display:flex;gap:3px;background:#e2e8f0;border-radius:12px;padding:4px}
                .tab-btn{flex:1;padding:8px 6px;background:none;border:none;border-radius:9px;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;transition:all .18s;white-space:nowrap}
                .tab-btn.active{background:#fff;color:var(--p);box-shadow:0 1px 5px rgba(0,0,0,.1)}

                /* ── Tab panes ── */
                .tab-pane{display:none;padding:16px 16px 8px}
                .tab-pane.active{display:block}

                /* ── Cards ── */
                .card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:14px}
                .section-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--p);margin-bottom:10px}

                /* ── Textareas ── */
                textarea{width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:11px 13px;font-size:12.5px;font-family:inherit;resize:vertical;color:#334155;background:#f8fafc;line-height:1.6}
                textarea:focus{outline:none;border-color:var(--p);background:#fff}

                /* ── Buttons ── */
                .btn-row{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
                .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 18px;border:none;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;transition:all .15s;text-decoration:none;flex:1}
                .btn-purple{background:var(--p);color:#fff}
                .btn-purple:hover{background:var(--pd)}
                .btn-purple.ok{background:#16a34a}
                .btn-dark{background:var(--dark);color:#fff}
                .btn-dark:hover{background:var(--dark2);color:#fff}

                /* ── Tips ── */
                .tip-amber{margin-top:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:11px 14px;font-size:12px;color:#92400e;line-height:1.6}
                .tip-blue{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 14px;font-size:12px;color:#1e40af;line-height:1.6;margin-bottom:14px}
                .logo-link{color:var(--p);font-weight:600;word-break:break-all}

                /* ── Image slots ── */
                .img-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
                .img-slot{background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 8px rgba(0,0,0,.06)}
                .img-slot.full-width{grid-column:1/-1}
                .img-slot-head{padding:10px 12px 8px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px}
                .img-copy-btn{margin-left:auto;flex-shrink:0;padding:5px 10px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:700;color:#475569;cursor:pointer;transition:all .15s;white-space:nowrap}
                .img-copy-btn:hover{background:var(--p);color:#fff;border-color:var(--p)}
                .img-copy-btn.ok{background:#16a34a;color:#fff;border-color:#16a34a}
                .img-slot-icon{font-size:16px;flex-shrink:0}
                .img-slot-info{flex:1;min-width:0}
                .img-slot-label{font-size:12px;font-weight:700;color:#1e293b;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
                .img-slot-dim{font-size:10px;color:var(--muted);display:block}
                .img-slot-body{padding:10px 12px 12px;position:relative}

                /* Preview state */
                .img-preview-wrap{position:relative;border-radius:10px;overflow:hidden;background:#0f172a;aspect-ratio:attr(data-ratio);min-height:80px}
                .img-preview-wrap img{width:100%;height:100%;object-fit:cover;display:block}
                .img-preview-overlay{position:absolute;inset:0;background:rgba(0,0,0,.45);opacity:0;transition:opacity .18s;display:flex;align-items:center;justify-content:center}
                .img-preview-wrap:hover .img-preview-overlay{opacity:1}
                .img-replace-btn{background:rgba(255,255,255,.92);color:#1e293b;border:none;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px}

                /* Upload zone state */
                .img-upload-zone{border:2px dashed #cbd5e1;border-radius:10px;padding:18px 12px;text-align:center;cursor:pointer;transition:all .18s;background:#f8fafc;min-height:80px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px}
                .img-upload-zone:hover{border-color:var(--p);background:#faf5ff}
                .img-upload-zone.dragging{border-color:var(--p);background:#f3e8ff}
                .img-upload-zone-icon{font-size:22px;margin-bottom:2px}
                .img-upload-zone-label{font-size:11.5px;font-weight:600;color:#475569}
                .img-upload-zone-sub{font-size:10px;color:var(--muted)}

                /* Card footer buttons */
                .img-slot-footer{display:flex;gap:7px;margin-top:10px;flex-wrap:wrap}
                .img-footer-btn{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s;text-decoration:none;background:#f8fafc;color:#475569;white-space:nowrap}
                .img-footer-btn:hover{border-color:var(--p);color:var(--p);background:#faf5ff}
                .img-footer-btn.ok{background:#16a34a;color:#fff;border-color:#16a34a}
                .img-footer-btn:disabled,.img-footer-btn[aria-disabled="true"]{opacity:.35;pointer-events:none;cursor:default}
                .img-footer-btn.dl{color:#0ea5e9;border-color:#bae6fd;background:#f0f9ff}
                .img-footer-btn.dl:hover{background:#0ea5e9;color:#fff;border-color:#0ea5e9}
                /* Mobile: stack footer buttons full-width so none get clipped by the narrow card */
                @media (max-width:560px){
                    .img-slot-footer{flex-direction:column;gap:6px}
                    .img-footer-btn{flex:none;width:100%;padding:11px 10px;font-size:13px}
                }

                /* Status / progress */
                .img-progress{margin-top:8px;display:none}
                .img-progress-bar-wrap{height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden}
                .img-progress-bar{height:100%;width:0%;background:var(--p);border-radius:99px;transition:width .1s linear}
                .img-progress-bar.done{background:#16a34a}
                .img-progress-bar.fail{background:#dc2626}
                .img-progress-label{margin-top:4px;font-size:11px;font-weight:700;text-align:center;color:var(--p)}
                .img-progress-label.done{color:#16a34a}
                .img-progress-label.fail{color:#dc2626}



                /* ── Video tab ── */
                .video-hero{background:linear-gradient(160deg,#020617 0%,#0d1424 55%,#1a0a2e 100%);border-radius:18px;padding:22px 18px 20px;margin-bottom:14px;position:relative;overflow:hidden}
                .video-hero::before{content:'';position:absolute;top:-50px;right:-50px;width:200px;height:200px;background:radial-gradient(circle,rgba(124,58,237,.25) 0%,transparent 65%);pointer-events:none}
                .video-hero::after{content:'';position:absolute;bottom:-40px;left:-40px;width:160px;height:160px;background:radial-gradient(circle,rgba(245,158,11,.1) 0%,transparent 65%);pointer-events:none}
                .video-hero-eyebrow{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#a78bfa;margin-bottom:6px;position:relative}
                .video-hero-title{font-size:20px;font-weight:800;color:#fff;margin-bottom:4px;position:relative}
                .video-hero-sub{font-size:12px;color:#94a3b8;margin-bottom:18px;position:relative}

                /* Flow diagram */
                .flow{display:flex;align-items:center;gap:0;margin-bottom:18px;overflow-x:auto;padding-bottom:2px;position:relative}
                .flow-node{flex:1;min-width:68px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);border-radius:11px;padding:10px 6px;text-align:center}
                .flow-node-icon{font-size:20px;margin-bottom:4px}
                .flow-node-label{font-size:9.5px;color:#cbd5e1;font-weight:700;line-height:1.3;text-transform:uppercase;letter-spacing:.04em}
                .flow-node-sub{font-size:9px;color:#64748b;margin-top:2px}
                .flow-arrow{color:#a78bfa;font-size:16px;padding:0 5px;flex-shrink:0;font-weight:700}

                /* Frame strip */
                .frame-strip{display:flex;gap:6px;margin-bottom:18px;position:relative}
                .frame-card{flex:1;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:9px 4px;text-align:center}
                .frame-num{width:22px;height:22px;background:var(--p);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;margin:0 auto 4px}
                .frame-tag{font-size:8.5px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.04em;line-height:1.2}
                .frame-time{font-size:8px;color:#475569;margin-top:3px}

                /* Video step cards */
                .vstep{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:17px 16px;margin-bottom:12px;position:relative}
                .vstep-header{display:flex;align-items:center;gap:10px;margin-bottom:8px}
                .vstep-num{width:28px;height:28px;background:var(--p);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0}
                .vstep h4{font-size:13.5px;font-weight:700;color:#f1f5f9;line-height:1.3}
                .vstep-where{font-size:11px;font-weight:600;color:#a78bfa;margin-left:auto;white-space:nowrap}
                .vstep p{font-size:11.5px;color:#94a3b8;margin-bottom:10px;line-height:1.5}
                .vstep textarea{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.15);color:#e2e8f0}
                .vstep textarea:focus{background:rgba(255,255,255,.12);border-color:#a78bfa}
                .vstep .btn-row .btn-purple{background:rgba(124,58,237,.75);border:1px solid rgba(167,139,250,.4)}
                .vstep .btn-row .btn-purple:hover{background:var(--p)}
                .vstep .btn-row .btn-purple.ok{background:#16a34a}
                .vstep-gemini{background:linear-gradient(135deg,rgba(66,133,244,.12) 0%,rgba(52,168,83,.08) 50%,rgba(251,188,4,.08) 100%);border:1px solid rgba(66,133,244,.3)}
                .gemini-badge-wrap{margin-bottom:10px}
                .gemini-badge{display:inline-block;font-size:11px;font-weight:800;letter-spacing:.02em;padding:3px 11px;border-radius:20px;border:1px solid rgba(255,255,255,.2);background:rgba(0,0,0,.3)}
                .g-b{color:#4285F4}.g-e{color:#EA4335}.g-y{color:#FBBC04}.g-g{color:#34A853}
                .gemini-model-tip{font-size:11px;color:#93c5fd;background:rgba(37,99,235,.2);border:1px solid rgba(59,130,246,.3);border-radius:8px;padding:8px 12px;margin-bottom:10px;line-height:1.5}

                /* ── Sticky bottom bar ── */
                .bottom-bar{position:fixed;bottom:0;left:0;right:0;background:rgba(241,245,249,.96);backdrop-filter:blur(14px);border-top:1px solid #e2e8f0;padding:10px 16px 14px;z-index:200}
                .bottom-bar-inner{display:flex;gap:8px;max-width:640px;margin:0 auto;align-items:center}
                .bb-dismiss{padding:12px 16px;background:#dc2626;color:#fff;border:none;border-radius:11px;font-size:13.5px;font-weight:700;cursor:pointer;transition:background .15s;white-space:nowrap}
                .bb-dismiss:hover{background:#b91c1c}
                .bb-dismiss.done{background:#16a34a;cursor:default}
                .bb-next{flex:1;display:flex;align-items:center;justify-content:center;padding:12px 16px;background:var(--dark);color:#fff;border-radius:11px;font-size:13.5px;font-weight:700;text-decoration:none;transition:background .15s}
                .bb-next:hover{background:var(--dark2);color:#fff}
                .bb-next--disabled{opacity:.4;cursor:default;pointer-events:none}
                .bb-close-tip{font-size:11.5px;color:#666;text-align:center;margin-top:6px;max-width:640px;margin-left:auto;margin-right:auto;display:none}
                .bb-job{padding:10px 14px;background:#7c3aed;color:#fff;border:none;border-radius:11px;font-size:13px;font-weight:700;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:background .15s}
                .bb-job:hover{background:#6d28d9;color:#fff}
            </style>
        </head>
        <body>

        <!-- ── Hero ── -->
        <div class="hero">
            <div class="page">
                <div class="hero-badge">✨ Step 1 of 2 — Post Kit</div>
                <h1>Content Creator</h1>
                <div class="hero-job-title">{$escapedJobName}</div>
                {$heroCompanyHtml}
            </div>
        </div>

        <!-- ── Tab bar ── -->
        <div class="tab-bar">
            <div class="page">
                <div class="tab-nav">
                    <button class="tab-btn active" onclick="switchTab('image',this)">🎨 Images</button>
                    <button class="tab-btn" onclick="switchTab('video',this)">🎬 Video</button>
                    <button class="tab-btn" onclick="switchTab('employer',this)">📨 Employer</button>
                </div>
            </div>
        </div>

        <div class="page">

            <!-- ══════════════ IMAGE TAB ══════════════ -->
            <div id="tab-image" class="tab-pane active">

                <!-- Company Logo + Cover Image row -->
                <div class="img-grid">

                    <div class="img-slot full-width" id="slot-company_logo">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">🏢</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">{$escapedCompany}</span>
                                <span class="img-slot-dim">Company Logo · Square or landscape · PNG/WebP</span>
                            </div>
                            {$copyCompanyNameHtml}
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-company_logo" style="display:none">
                                <div class="img-preview-wrap" style="max-height:120px;aspect-ratio:auto">
                                    <img id="img-company_logo" src="" alt="Company logo" style="height:120px;width:auto;margin:0 auto;display:block;object-fit:contain;background:#f8fafc;border-radius:8px">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('company_logo')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-company_logo" class="img-upload-zone" onclick="triggerUpload('company_logo')" ondragover="onDragOver(event,'company_logo')" ondragleave="onDragLeave('company_logo')" ondrop="onDrop(event,'company_logo')">
                                <div class="img-upload-zone-icon">🖼</div>
                                <div class="img-upload-zone-label">Upload Company Logo</div>
                                <div class="img-upload-zone-sub">PNG · JPG · WebP</div>
                            </div>
                            <input type="file" id="file-company_logo" accept="image/*" onchange="handleFileSelect('company_logo',this)" style="display:none">
                            <div class="img-progress" id="progress-company_logo">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-company_logo"></div></div>
                                <div class="img-progress-label" id="label-company_logo"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-company_logo" href="#" download="company-logo" style="display:none">⬇ Download</a>
                            </div>
                        </div>
                    </div>

                    <div class="img-slot full-width" id="slot-cover_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">🖼</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">Job Cover Image</span>
                                <span class="img-slot-dim">1800 × 540 px · landscape banner</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('cover_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-cover_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:10/3">
                                    <img id="img-cover_image" src="" alt="Cover image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('cover_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-cover_image" class="img-upload-zone" onclick="triggerUpload('cover_image')" ondragover="onDragOver(event,'cover_image')" ondragleave="onDragLeave('cover_image')" ondrop="onDrop(event,'cover_image')">
                                <div class="img-upload-zone-icon">🖼</div>
                                <div class="img-upload-zone-label">Upload Job Cover Image</div>
                                <div class="img-upload-zone-sub">1800 × 540 px · PNG · JPG · WebP</div>
                            </div>
                            <input type="file" id="file-cover_image" accept="image/*" onchange="handleFileSelect('cover_image',this)" style="display:none">
                            <div class="img-progress" id="progress-cover_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-cover_image"></div></div>
                                <div class="img-progress-label" id="label-cover_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-cover_image" href="#" download="cover-image" style="display:none">⬇ Download</a>
                            </div>
                        </div>
                    </div>

                    <!-- TikTok image -->
                    <div class="img-slot" id="slot-tiktok_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">🎵</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">TikTok</span>
                                <span class="img-slot-dim">1080 × 1920 · 9:16</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('tiktok_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-tiktok_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:9/16">
                                    <img id="img-tiktok_image" src="" alt="TikTok image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('tiktok_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-tiktok_image" class="img-upload-zone" onclick="triggerUpload('tiktok_image')" ondragover="onDragOver(event,'tiktok_image')" ondragleave="onDragLeave('tiktok_image')" ondrop="onDrop(event,'tiktok_image')">
                                <div class="img-upload-zone-icon">🎵</div>
                                <div class="img-upload-zone-label">Upload TikTok Image</div>
                                <div class="img-upload-zone-sub">1080 × 1920</div>
                            </div>
                            <input type="file" id="file-tiktok_image" accept="image/*" onchange="handleFileSelect('tiktok_image',this)" style="display:none">
                            <div class="img-progress" id="progress-tiktok_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-tiktok_image"></div></div>
                                <div class="img-progress-label" id="label-tiktok_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-tiktok_image" href="#" download="tiktok-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('tiktok_image',this)">📋 Post Text</button>
                                <button class="img-footer-btn" id="repost-tiktok_image" style="display:none" onclick="pkAskSendToPubler()">🔁 Repost</button>
                            </div>
                        </div>
                    </div>

                    <!-- WhatsApp image -->
                    <div class="img-slot" id="slot-whatsapp_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">💬</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">WhatsApp</span>
                                <span class="img-slot-dim">1080 × 1920 · status</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('whatsapp_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-whatsapp_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:9/16">
                                    <img id="img-whatsapp_image" src="" alt="WhatsApp image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('whatsapp_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-whatsapp_image" class="img-upload-zone" onclick="triggerUpload('whatsapp_image')" ondragover="onDragOver(event,'whatsapp_image')" ondragleave="onDragLeave('whatsapp_image')" ondrop="onDrop(event,'whatsapp_image')">
                                <div class="img-upload-zone-icon">💬</div>
                                <div class="img-upload-zone-label">Upload WhatsApp Image</div>
                                <div class="img-upload-zone-sub">1080 × 1920</div>
                            </div>
                            <input type="file" id="file-whatsapp_image" accept="image/*" onchange="handleFileSelect('whatsapp_image',this)" style="display:none">
                            <div class="img-progress" id="progress-whatsapp_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-whatsapp_image"></div></div>
                                <div class="img-progress-label" id="label-whatsapp_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-whatsapp_image" href="#" download="whatsapp-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('whatsapp_image',this)">📋 Post Text</button>
                                <button class="img-footer-btn" id="repost-whatsapp_image" style="display:none" onclick="pkAskSendToChannel()">🔁 Repost</button>
                            </div>
                        </div>
                    </div>

                    <!-- Facebook image -->
                    <div class="img-slot" id="slot-facebook_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">f</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">Facebook</span>
                                <span class="img-slot-dim">1200 × 630 · landscape</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('facebook_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-facebook_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:1200/630">
                                    <img id="img-facebook_image" src="" alt="Facebook image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('facebook_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-facebook_image" class="img-upload-zone" onclick="triggerUpload('facebook_image')" ondragover="onDragOver(event,'facebook_image')" ondragleave="onDragLeave('facebook_image')" ondrop="onDrop(event,'facebook_image')">
                                <div class="img-upload-zone-icon">f</div>
                                <div class="img-upload-zone-label">Upload Facebook Image</div>
                                <div class="img-upload-zone-sub">1200 × 630</div>
                            </div>
                            <input type="file" id="file-facebook_image" accept="image/*" onchange="handleFileSelect('facebook_image',this)" style="display:none">
                            <div class="img-progress" id="progress-facebook_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-facebook_image"></div></div>
                                <div class="img-progress-label" id="label-facebook_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-facebook_image" href="#" download="facebook-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('facebook_image',this)">📋 Post Text</button>
                            </div>
                        </div>
                    </div>

                    <!-- LinkedIn image -->
                    <div class="img-slot" id="slot-linkedin_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">in</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">LinkedIn</span>
                                <span class="img-slot-dim">1200 × 627 · landscape</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('linkedin_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-linkedin_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:1200/627">
                                    <img id="img-linkedin_image" src="" alt="LinkedIn image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('linkedin_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-linkedin_image" class="img-upload-zone" onclick="triggerUpload('linkedin_image')" ondragover="onDragOver(event,'linkedin_image')" ondragleave="onDragLeave('linkedin_image')" ondrop="onDrop(event,'linkedin_image')">
                                <div class="img-upload-zone-icon">in</div>
                                <div class="img-upload-zone-label">Upload LinkedIn Image</div>
                                <div class="img-upload-zone-sub">1200 × 627</div>
                            </div>
                            <input type="file" id="file-linkedin_image" accept="image/*" onchange="handleFileSelect('linkedin_image',this)" style="display:none">
                            <div class="img-progress" id="progress-linkedin_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-linkedin_image"></div></div>
                                <div class="img-progress-label" id="label-linkedin_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-linkedin_image" href="#" download="linkedin-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('linkedin_image',this)">📋 Post Text</button>
                            </div>
                        </div>
                    </div>

                    <!-- Twitter / X image -->
                    <div class="img-slot" id="slot-twitter_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">𝕏</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">X / Twitter</span>
                                <span class="img-slot-dim">1200 × 675 · 16:9</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('twitter_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-twitter_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:16/9">
                                    <img id="img-twitter_image" src="" alt="X / Twitter image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('twitter_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-twitter_image" class="img-upload-zone" onclick="triggerUpload('twitter_image')" ondragover="onDragOver(event,'twitter_image')" ondragleave="onDragLeave('twitter_image')" ondrop="onDrop(event,'twitter_image')">
                                <div class="img-upload-zone-icon">𝕏</div>
                                <div class="img-upload-zone-label">Upload X / Twitter Image</div>
                                <div class="img-upload-zone-sub">1200 × 675</div>
                            </div>
                            <input type="file" id="file-twitter_image" accept="image/*" onchange="handleFileSelect('twitter_image',this)" style="display:none">
                            <div class="img-progress" id="progress-twitter_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-twitter_image"></div></div>
                                <div class="img-progress-label" id="label-twitter_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-twitter_image" href="#" download="twitter-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('twitter_image',this)">📋 Post Text</button>
                            </div>
                        </div>
                    </div>

                </div><!-- /img-grid -->

            </div><!-- /tab-image -->

            <!-- ══════════════ VIDEO TAB ══════════════ -->
            <div id="tab-video" class="tab-pane">
                <div class="video-hero">

                    <div class="video-hero-eyebrow">🎬 10-Second Video Ad</div>
                    <div class="video-hero-title">TikTok · Reels · WhatsApp Status</div>
                    <div class="video-hero-sub">Two AI tools. Four frames. One scroll-stopping video.</div>

                    <div class="flow">
                        <div class="flow-node">
                            <div class="flow-node-icon">💬</div>
                            <div class="flow-node-label">ChatGPT</div>
                            <div class="flow-node-sub">4 frames</div>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node">
                            <div class="flow-node-icon">🖼</div>
                            <div class="flow-node-label">Download</div>
                            <div class="flow-node-sub">all 4 JPEGs</div>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node" style="background:rgba(66,133,244,.15);border-color:rgba(66,133,244,.3)">
                            <div class="flow-node-icon">✨</div>
                            <div class="flow-node-label">Gemini</div>
                            <div class="flow-node-sub">10s video</div>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node" style="background:rgba(52,168,83,.12);border-color:rgba(52,168,83,.3)">
                            <div class="flow-node-icon">🎥</div>
                            <div class="flow-node-label">MP4 done</div>
                            <div class="flow-node-sub">post it!</div>
                        </div>
                    </div>

                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:7px">Video timeline</div>
                    <div class="frame-strip">
                        <div class="frame-card">
                            <div class="frame-num">1</div>
                            <div class="frame-tag">Hook</div>
                            <div class="frame-time">0 – 2 s</div>
                        </div>
                        <div class="frame-card">
                            <div class="frame-num">2</div>
                            <div class="frame-tag">Oppty</div>
                            <div class="frame-time">2 – 5 s</div>
                        </div>
                        <div class="frame-card">
                            <div class="frame-num">3</div>
                            <div class="frame-tag">Details</div>
                            <div class="frame-time">5 – 8 s</div>
                        </div>
                        <div class="frame-card" style="background:rgba(124,58,237,.15);border-color:rgba(124,58,237,.35)">
                            <div class="frame-num">4</div>
                            <div class="frame-tag">CTA 🚀</div>
                            <div class="frame-time">8 – 10 s</div>
                        </div>
                    </div>

                    <div class="vstep">
                        <div class="vstep-header">
                            <div class="vstep-num">1</div>
                            <h4>Storyboard — 4 portrait frames</h4>
                            <span class="vstep-where">→ ChatGPT</span>
                        </div>
                        <p>Paste into ChatGPT (attach Wakanda Jobs logo). It generates 4 sequential 1080×1920 images — one per scene. Download all four.</p>
                        <textarea id="storyboard-ta" readonly rows="8">{$escapedStoryboard}</textarea>
                        <div class="btn-row" style="margin-top:10px">
                            <button class="btn btn-purple" onclick="copyField('storyboard-ta',this,'📋 Copy Storyboard')">📋 Copy Storyboard</button>
                        </div>
                    </div>

                    <div class="vstep vstep-gemini">
                        <div class="gemini-badge-wrap">
                            <span class="gemini-badge"><span class="g-b">G</span><span class="g-e">e</span><span class="g-y">m</span><span class="g-g">i</span><span class="g-b">n</span><span class="g-e">i</span> <span style="color:#fff;opacity:.8">Omni</span> — Video Generation</span>
                        </div>
                        <div class="vstep-header">
                            <div class="vstep-num" style="background:linear-gradient(135deg,#4285F4,#34A853)">2</div>
                            <h4>Animate 4 frames → 10-second video</h4>
                            <span class="vstep-where" style="color:#4ade80">→ Gemini</span>
                        </div>
                        <div class="gemini-model-tip">
                            📌 <strong>Use:</strong> <strong>Gemini 2.0 Flash</strong> (Experimental) with video generation, or <strong>Google Veo 2</strong> via Gemini Advanced.<br>
                            📎 <strong>Attach in order:</strong> Frame 1 → Frame 2 → Frame 3 → Frame 4 → Wakanda Jobs logo PNG — then paste the prompt below.
                        </div>
                        <p>Gemini animates the 4 frames into a punchy 10-second MP4 with transitions, text effects, Amapiano/Afrobeats audio, and the Wakanda Jobs logo as a persistent watermark.</p>
                        <textarea id="gemini-ta" readonly rows="9">{$escapedGemini}</textarea>
                        <div class="btn-row" style="margin-top:10px">
                            <button class="btn btn-purple" onclick="copyField('gemini-ta',this,'📋 Copy Gemini Script')">📋 Copy Gemini Script</button>
                        </div>
                    </div>

                </div><!-- /video-hero -->
            </div>

            <!-- ══════════════ EMPLOYER TAB ══════════════ -->
            <div id="tab-employer" class="tab-pane">
                <div class="tip-blue">📨 Let the employer know their job ad is live and is being professionally marketed across our platforms — builds trust and shows the value of advertising with Wakanda Jobs.</div>

                <div class="img-grid">
                    <div class="img-slot full-width" id="slot-employer_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">📨</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">Employer Update Image</span>
                                <span class="img-slot-dim">1080 × 1350 · 4:5 portrait</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('employer_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-employer_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:1080/1350">
                                    <img id="img-employer_image" src="" alt="Employer update image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('employer_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-employer_image" class="img-upload-zone" onclick="triggerUpload('employer_image')" ondragover="onDragOver(event,'employer_image')" ondragleave="onDragLeave('employer_image')" ondrop="onDrop(event,'employer_image')">
                                <div class="img-upload-zone-icon">📨</div>
                                <div class="img-upload-zone-label">Upload Employer Update Image</div>
                                <div class="img-upload-zone-sub">1080 × 1350</div>
                            </div>
                            <input type="file" id="file-employer_image" accept="image/*" onchange="handleFileSelect('employer_image',this)" style="display:none">
                            <div class="img-progress" id="progress-employer_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-employer_image"></div></div>
                                <div class="img-progress-label" id="label-employer_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-employer_image" href="#" download="employer-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('employer_image',this)">📋 Post Text</button>
                            </div>
                        </div>
                    </div>
                </div><!-- /img-grid -->

                <div class="card">
                    <div class="section-label">📣 Selling Message</div>
                    <textarea id="employer-pitch-ta" readonly rows="12">{$escapedEmployerPitch}</textarea>
                    <div class="btn-row">
                        <button class="btn btn-purple" onclick="copyField('employer-pitch-ta',this,'📋 Copy Message')">📋 Copy Message</button>
                    </div>
                    {$employerEmailsHtml}
                    {$employerNoContactHtml}
                    {$employerAlreadyPitchedHtml}
                    <div class="btn-row">
                        {$employerEmailBtnHtml}
                        {$employerWhatsappBtnHtml}
                    </div>
                    <div id="employer-send-status" style="margin-top:10px;font-size:12.5px;font-weight:600"></div>
                </div>
            </div>

        </div><!-- /page -->

        <!-- ── Sticky bottom bar ── -->
        <div class="bottom-bar">
            <div class="bottom-bar-inner">
                <button class="bb-dismiss" id="dismiss-btn" onclick="dismiss()">🗑 Dismiss</button>
                {$jobBtnHtml}
                {$nextBtnHtml}
            </div>
            <div class="bb-close-tip" id="close-tip"></div>
        </div>

        <script>
            const aiPromptText    = {$aiPromptJson};
            const tiktokImageText = {$tiktokImageJson};
            const storyboardText  = {$storyboardJson};
            const geminiText      = {$geminiJson};
            const step2Url        = {$step2UrlJson};
            const uploadUrls      = {$uploadUrlsJson};
            const generateUrls    = {$generateUrlsJson};
            const openAiConfigured = {$openAiConfiguredJson};
            const jobImages       = {$jobImagesJson};
            const companyLogoUrl  = {$companyLogoJson};
            const companyName     = {$companyNameJson};
            const slotPrompts     = {$slotPromptsJson};
            const slotPosts       = {$slotPostsJson};
            const csrfToken       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const whapiSendUrl    = {$whapiSendUrlJson};
            const whapiChannel    = {$whapiChannelJson};
            const publerSendUrl   = {$publerSendUrlJson};
            const sendToEmployerUrl = {$sendToEmployerUrlJson};
            const employerPitchText = {$employerPitchJson};
            const employerEmail    = {$employerEmailJson};
            const employerPhone    = {$employerPhoneJson};

            // ── Init image previews on page load ──
            (function initImages() {
                const slots = {
                    company_logo:   companyLogoUrl,
                    cover_image:    jobImages.cover_image,
                    tiktok_image:   jobImages.tiktok_image,
                    facebook_image: jobImages.facebook_image,
                    linkedin_image: jobImages.linkedin_image,
                    whatsapp_image: jobImages.whatsapp_image,
                    twitter_image:  jobImages.twitter_image,
                    employer_image: jobImages.employer_image,
                };
                for (const [key, url] of Object.entries(slots)) {
                    if (url) showPreview(key, url);
                }

                // No dedicated employer image yet — borrow the WhatsApp tab image as the default.
                if (!jobImages.employer_image && jobImages.whatsapp_image) {
                    showPreview('employer_image', jobImages.whatsapp_image);
                    const dl = document.getElementById('dl-employer_image');
                    if (dl) { dl.style.display = 'none'; }
                    const label = document.querySelector('#slot-employer_image .img-slot-label');
                    if (label) label.textContent = 'Employer Update Image (using WhatsApp image)';
                }
            })();

            function showPreview(key, url) {
                const img  = document.getElementById('img-' + key);
                const prev = document.getElementById('preview-' + key);
                const zone = document.getElementById('zone-' + key);
                const dl   = document.getElementById('dl-' + key);
                if (!img || !prev) return;
                img.src = url;
                prev.style.display = 'block';
                if (zone) zone.style.display = 'none';
                if (dl) { dl.href = url; dl.style.display = ''; dl.style.opacity = '1'; dl.style.pointerEvents = 'auto'; }
                const rp = document.getElementById('repost-' + key);
                if (rp) rp.style.display = ((key === 'whatsapp_image' && whapiSendUrl) || (key === 'tiktok_image' && publerSendUrl)) ? '' : 'none';
                const slot = document.getElementById('slot-' + key);
                if (slot) { const cb = slot.querySelector('.img-copy-btn'); if (cb) cb.style.display = 'none'; }
            }

            function copySlotPost(key, btn) {
                const text = slotPosts[key];
                if (!text) return;
                doCopy(text, btn, '📋 Post Text');
            }

            function triggerUpload(key) {
                document.getElementById('file-' + key)?.click();
            }

            function onDragOver(e, key) {
                e.preventDefault();
                document.getElementById('zone-' + key)?.classList.add('dragging');
            }
            function onDragLeave(key) {
                document.getElementById('zone-' + key)?.classList.remove('dragging');
            }
            function onDrop(e, key) {
                e.preventDefault();
                onDragLeave(key);
                const file = e.dataTransfer?.files?.[0];
                if (file) doUpload(key, file);
            }

            function handleFileSelect(key, input) {
                const file = input.files?.[0];
                if (file) doUpload(key, file);
            }

            function setProgress(key, pct, state, label) {
                const wrap  = document.getElementById('progress-' + key);
                const bar   = document.getElementById('bar-' + key);
                const lbl   = document.getElementById('label-' + key);
                if (!wrap) return;
                wrap.style.display = 'block';
                bar.style.width = pct + '%';
                bar.className = 'img-progress-bar' + (state ? ' ' + state : '');
                lbl.textContent = label;
                lbl.className = 'img-progress-label' + (state ? ' ' + state : '');
            }

            function hideProgress(key) {
                const wrap = document.getElementById('progress-' + key);
                if (wrap) wrap.style.display = 'none';
            }

            function playUploadSound() {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    [880, 1320].forEach((freq, i) => {
                        const osc  = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.value = freq;
                        const start = ctx.currentTime + i * 0.12;
                        gain.gain.setValueAtTime(0.0001, start);
                        gain.gain.exponentialRampToValueAtTime(0.2, start + 0.02);
                        gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.18);
                        osc.connect(gain).connect(ctx.destination);
                        osc.start(start);
                        osc.stop(start + 0.2);
                    });
                } catch {}
            }

            function doUpload(key, file) {
                const url = uploadUrls[key];
                if (!url) { setProgress(key, 100, 'fail', '❌ Upload not available.'); return; }

                // Instant local preview
                const reader = new FileReader();
                reader.onload = e => showPreview(key, e.target.result);
                reader.readAsDataURL(file);

                setProgress(key, 0, '', 'Uploading… 0%');

                const fd = new FormData();
                fd.append('image', file);
                fd.append('type', key);

                const xhr = new XMLHttpRequest();

                xhr.upload.onprogress = function(e) {
                    if (!e.lengthComputable) return;
                    const pct = Math.round((e.loaded / e.total) * 95); // cap at 95 until server responds
                    setProgress(key, pct, '', 'Uploading… ' + pct + '%');
                };

                xhr.onload = function() {
                    let data = {};
                    try { data = JSON.parse(xhr.responseText); } catch {}
                    if (xhr.status >= 200 && xhr.status < 300 && data.ok !== false) {
                        if (data.url) showPreview(key, data.url);
                        setProgress(key, 100, 'done', '✅ Saved!');
                        if (key === 'whatsapp_image' || key === 'tiktok_image') playUploadSound();
                        setTimeout(() => {
                            if (key === 'whatsapp_image' && whapiSendUrl) pkAskSendToChannel();
                            else if (key === 'tiktok_image' && publerSendUrl) pkAskSendToPubler();
                            else location.reload();
                        }, 1200);
                    } else {
                        const msg = data.message || ('Upload failed (' + xhr.status + ')');
                        setProgress(key, 100, 'fail', '❌ ' + msg);
                    }
                };

                xhr.onerror = function() {
                    setProgress(key, 100, 'fail', '❌ Network error — please retry.');
                };

                xhr.open('POST', url);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                xhr.send(fd);
            }

            // ── Tab switching ──
            function switchTab(name, btn) {
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.getElementById('tab-' + name).classList.add('active');
                btn.classList.add('active');
            }


            function pkLoadSwal() {
                return new Promise(resolve => {
                    if (window.Swal) { resolve(); return; }
                    const link = document.createElement('link'); link.rel = 'stylesheet';
                    link.href = '/vendor/core/core/base/libraries/sweetalert2/sweetalert2.min.css';
                    document.head.appendChild(link);
                    const s = document.createElement('script');
                    s.src = '/vendor/core/core/base/libraries/sweetalert2/sweetalert2.min.js';
                    s.onload = resolve; document.head.appendChild(s);
                });
            }
            function pkAskSendToChannel() {
                pkLoadSwal().then(() => {
                    const publerNote = publerSendUrl ? '<br><small style="color:#555;font-size:11px">Also sends to Facebook &amp; LinkedIn via Publer.</small>' : '';
                    Swal.fire({
                        title: 'Send to WhatsApp Channel?',
                        html: 'Image uploaded. Send this job to <strong>' + whapiChannel + '</strong> now?' + publerNote,
                        icon: 'question', showCancelButton: true,
                        confirmButtonColor: '#25D366', cancelButtonColor: '#6b7280',
                        confirmButtonText: '💬 Yes, Send Now', cancelButtonText: 'No, just save', reverseButtons: true,
                    }).then(result => {
                        if (result.isConfirmed) {
                            Swal.fire({ title: 'Sending…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                            const fd = new FormData(); fd.append('_token', csrfToken);
                            fetch(whapiSendUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(r => r.json())
                                .then(d => {
                                    const ok = d.error !== true;
                                    if (publerSendUrl) {
                                        const pfd = new FormData();
                                        pfd.append('_token', csrfToken);
                                        pfd.append('image_field', 'whatsapp_image');
                                        pfd.append('exclude_networks', 'tiktok');
                                        fetch(publerSendUrl, { method: 'POST', body: pfd, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => {});
                                    }
                                    Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? 'Sent!' : 'Failed', text: d.message, timer: 2500, showConfirmButton: false }).then(() => location.reload());
                                })
                                .catch(() => Swal.fire({ icon: 'error', title: 'Network error' }).then(() => location.reload()));
                        } else { location.reload(); }
                    });
                });
            }
            function pkAskSendToPubler() {
                pkLoadSwal().then(() => {
                    Swal.fire({
                        title: 'Post to Publer (TikTok)?',
                        text: 'Image uploaded. Post this job to your connected TikTok account via Publer?',
                        icon: 'question', showCancelButton: true,
                        confirmButtonColor: '#7c3aed', cancelButtonColor: '#6b7280',
                        confirmButtonText: '🎵 Yes, Post Now', cancelButtonText: 'No, just save', reverseButtons: true,
                    }).then(result => {
                        if (result.isConfirmed) {
                            Swal.fire({ title: 'Posting to Publer…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                            const fd = new FormData();
                            fd.append('_token', csrfToken);
                            fd.append('image_field', 'tiktok_image');
                            fd.append('exclude_networks', 'facebook,linkedin,twitter,instagram');
                            fd.append('retry_background', '1');
                            fetch(publerSendUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(r => r.json())
                                .then(d => {
                                    const ok = d.error !== true;
                                    if (ok) {
                                        Swal.fire({ icon: 'success', title: 'Posted!', text: d.message, timer: 2500, showConfirmButton: false }).then(() => location.reload());
                                        return;
                                    }
                                    const detail = d.data && d.data.error_detail ? d.data.error_detail : d.message;
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'TikTok post failed',
                                        text: d.message + '\\n\\n' + detail,
                                        confirmButtonText: 'Copy error',
                                        showCancelButton: true,
                                        cancelButtonText: 'Close',
                                    }).then(result => {
                                        if (result.isConfirmed) {
                                            const copyButton = document.createElement('button');
                                            doCopy(detail, copyButton, 'Copy error');
                                            return;
                                        }
                                        location.reload();
                                    });
                                })
                                .catch(() => Swal.fire({ icon: 'error', title: 'Network error' }).then(() => location.reload()));
                        } else { location.reload(); }
                    });
                });
            }
            function generateImage(key, btn) {
                const url = generateUrls[key];
                if (!url) { setProgress(key, 100, 'fail', '❌ AI generation not available.'); return; }
                if (btn) { btn.disabled = true; }
                setProgress(key, 92, '', '✨ Generating with AI… ~30s');
                const fd = new FormData();
                fd.append('type', key);
                fd.append('_token', csrfToken);
                fetch(url, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                }).then(function (r) {
                    return r.json().then(function (d) { return { ok: r.ok, d: d }; });
                }).then(function (res) {
                    const d = res.d || {};
                    if (res.ok && d.ok !== false && d.url) {
                        showPreview(key, d.url);
                        setProgress(key, 100, 'done', '✅ Generated!');
                        if (key === 'whatsapp_image' || key === 'tiktok_image') playUploadSound();
                        setTimeout(function () {
                            if (key === 'whatsapp_image' && whapiSendUrl) pkAskSendToChannel();
                            else if (key === 'tiktok_image' && publerSendUrl) pkAskSendToPubler();
                            else location.reload();
                        }, 1300);
                    } else {
                        setProgress(key, 100, 'fail', '❌ ' + (d.message || 'Generation failed.'));
                        if (btn) btn.disabled = false;
                    }
                }).catch(function () {
                    setProgress(key, 100, 'fail', '❌ Network error — please retry.');
                    if (btn) btn.disabled = false;
                });
            }

            // Inject "✨ Generate" buttons into each slot footer (only when OpenAI is configured).
            (function injectGenerateButtons() {
                if (!openAiConfigured) return;
                Object.keys(generateUrls).forEach(function (key) {
                    if (!generateUrls[key]) return;
                    const footer = document.querySelector('#slot-' + key + ' .img-slot-footer');
                    if (!footer || footer.querySelector('.img-footer-btn-gen')) return;
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'img-footer-btn img-footer-btn-gen';
                    b.innerHTML = '✨ Generate';
                    b.style.color = '#7c3aed';
                    b.style.borderColor = '#ddd6fe';
                    b.style.background = '#faf5ff';
                    b.addEventListener('click', function () { generateImage(key, b); });
                    footer.insertBefore(b, footer.firstChild);
                });
            })();

            function copySlotPrompt(key, btn) {
                const text = slotPrompts[key];
                if (!text) return;
                doCopy(text, btn, '📋 Copy');
            }
            function copyField(id, btn, resetLabel) {
                doCopy(document.getElementById(id).value, btn, resetLabel);
            }

            function sendToEmployer(channel, btn) {
                const status = document.getElementById('employer-send-status');
                const ta = document.getElementById('employer-pitch-ta');
                const message = ta ? ta.value : employerPitchText;
                const original = btn.textContent;
                btn.disabled = true;
                btn.textContent = '⏳ Sending…';
                if (status) { status.textContent = ''; }

                const fd = new FormData();
                fd.append('channel', channel);
                fd.append('message', message);
                fd.append('_token', csrfToken);

                fetch(sendToEmployerUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        if (status) {
                            status.textContent = data.message || (data.ok ? 'Sent!' : 'Something went wrong.');
                            status.style.color = data.ok ? '#16a34a' : '#dc2626';
                        }
                        btn.textContent = data.ok ? '✅ Sent' : original;
                        btn.disabled = !!data.ok;
                    })
                    .catch(() => {
                        if (status) { status.textContent = 'Network error — please try again.'; status.style.color = '#dc2626'; }
                        btn.textContent = original;
                        btn.disabled = false;
                    });
            }

            function dismiss() {
                const btn = document.getElementById('dismiss-btn');
                const tip = document.getElementById('close-tip');
                btn.disabled = true;
                btn.textContent = '⏳ Dismissing…';
                if (!step2Url) {
                    btn.classList.add('done');
                    btn.textContent = '✅ Done';
                    tip.textContent = 'You can close this tab.';
                    tip.style.display = 'block';
                    setTimeout(() => window.close(), 600);
                    return;
                }
                fetch(step2Url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(async r => {
                    const d = await r.json().catch(() => ({}));
                    if (!r.ok || d.ok === false) throw new Error(d.message || 'Telegram could not remove the message.');
                    btn.classList.add('done');
                    btn.textContent = '✅ Done';
                    tip.textContent = '✅ Telegram message deleted — you can close this tab.';
                    tip.style.display = 'block';
                    setTimeout(() => window.close(), 600);
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.textContent = '🗑 Retry Dismiss';
                    tip.textContent = err.message || 'Could not dismiss. Please try again.';
                    tip.style.color = '#b91c1c';
                    tip.style.display = 'block';
                });
            }

            function doCopy(text, btn, resetLabel) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(() => showOk(btn, resetLabel)).catch(() => legacyCopy(text, btn, resetLabel));
                } else {
                    legacyCopy(text, btn, resetLabel);
                }
            }
            function legacyCopy(text, btn, resetLabel) {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
                document.body.appendChild(ta);
                ta.focus(); ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                showOk(btn, resetLabel);
            }
            function showOk(btn, resetLabel) {
                btn.textContent = '✅ Copied!';
                btn.classList.add('ok');
                setTimeout(() => { btn.textContent = resetLabel; btn.classList.remove('ok'); }, 2200);
            }
        </script>
        </body>
        </html>
        HTML;

        return response($html);
    }

    // -------------------------------------------------------------------------
    // Image upload handler
    // -------------------------------------------------------------------------

    public function upload(Request $request)
    {
        $type    = $request->query('type', '');
        $jobId   = $request->query('job_id');
        $companyId = $request->query('company_id');

        $allowedTypes = ['company_logo', 'cover_image', 'tiktok_image', 'facebook_image', 'linkedin_image', 'whatsapp_image', 'twitter_image', 'employer_image'];
        if (! in_array($type, $allowedTypes, true)) {
            return response()->json(['ok' => false, 'message' => 'Invalid upload type.'], 422);
        }

        if (! $request->hasFile('image') || ! $request->file('image')->isValid()) {
            return response()->json(['ok' => false, 'message' => 'No valid image file received.'], 422);
        }

        $file = $request->file('image');

        // Validate: image only, max 10 MB
        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            return response()->json(['ok' => false, 'message' => 'Only JPG, PNG, WebP, and GIF images are accepted.'], 422);
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            return response()->json(['ok' => false, 'message' => 'File too large (max 10 MB).'], 422);
        }

        try {
            if ($type === 'company_logo') {
                if (! $companyId) {
                    return response()->json(['ok' => false, 'message' => 'Company not found for this job.'], 422);
                }
                $company = Company::find($companyId);
                if (! $company) {
                    return response()->json(['ok' => false, 'message' => 'Company not found.'], 404);
                }
                $path = $file->store('companies', 'public');
                $company->logo = $path;
                $company->save();
                $url = Storage::disk('public')->url($path);
                return response()->json(['ok' => true, 'url' => $url, 'path' => $path]);
            }

            // All other types are stored on the Job model
            if (! $jobId) {
                return response()->json(['ok' => false, 'message' => 'Job ID is required.'], 422);
            }
            $job = Job::find($jobId);
            if (! $job) {
                return response()->json(['ok' => false, 'message' => 'Job not found.'], 404);
            }

            $folder = match ($type) {
                'cover_image'    => 'job-covers',
                'tiktok_image'   => 'job-social/tiktok',
                'facebook_image' => 'job-social/facebook',
                'linkedin_image' => 'job-social/linkedin',
                'whatsapp_image' => 'job-social/whatsapp',
                'twitter_image'  => 'job-social/twitter',
                'employer_image' => 'job-social/employer',
                default          => 'job-social',
            };

            $path = $file->store($folder, 'public');
            $job->{$type} = $path;
            $job->save();
            $url = Storage::disk('public')->url($path);

            return response()->json(['ok' => true, 'url' => $url, 'path' => $path]);
        } catch (\Throwable $e) {
            Log::error('Social image upload failed', ['type' => $type, 'job_id' => $jobId, 'error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Generate an image with OpenAI (gpt-image-1) for a single slot
    // -------------------------------------------------------------------------

    public function generate(Request $request)
    {
        $type  = $request->input('type', $request->query('type', ''));
        $jobId = $request->input('job_id', $request->query('job_id'));

        $service = app(\Botble\JobBoard\Services\OpenAiImageService::class);

        if (! in_array($type, $service::slotTypes(), true)) {
            return response()->json(['ok' => false, 'message' => 'Invalid image type.'], 422);
        }

        if (! $jobId) {
            return response()->json(['ok' => false, 'message' => 'Job ID is required.'], 422);
        }

        if (! $service->isConfigured()) {
            return response()->json(['ok' => false, 'message' => 'OpenAI API key is not configured.'], 422);
        }

        $job = Job::with(['company', 'slugable', 'country', 'currency', 'jobTypes'])->find($jobId);
        if (! $job) {
            return response()->json(['ok' => false, 'message' => 'Job not found.'], 404);
        }

        $result = $service->generateForJob($job, $type);

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 500);
    }

    // -------------------------------------------------------------------------
    // Send the "your job ad is live" pitch to the employer (email / WhatsApp)
    // -------------------------------------------------------------------------

    public function sendToEmployer(Request $request)
    {
        $jobId   = $request->input('job_id');
        $channel = $request->input('channel'); // 'email' | 'whatsapp'
        $message = trim((string) $request->input('message', ''));

        if (! $jobId || ! in_array($channel, ['email', 'whatsapp'], true) || $message === '') {
            return response()->json(['ok' => false, 'message' => 'Missing job, channel, or message.'], 422);
        }

        $job = Job::with(['company', 'country'])->find($jobId);
        if (! $job || ! $job->company) {
            return response()->json(['ok' => false, 'message' => 'Job or company not found.'], 404);
        }

        $company = $job->company;

        if ($channel === 'email') {
            $allEmails = collect($company->contact_emails ?? [])->filter()->values();
            if ($allEmails->isEmpty() && $company->email) {
                $allEmails = collect([$company->email]);
            }

            $email = $allEmails->first();
            if (! $email) {
                return response()->json(['ok' => false, 'message' => 'No contact email on file for this employer.'], 422);
            }

            $ccEmails = $allEmails->slice(1)->values()->all();

            try {
                Mail::raw($message, function ($mail) use ($email, $ccEmails, $job): void {
                    $mail->to($email)
                        ->subject('Your job ad "' . $job->name . '" is live on Wakanda Jobs! 🚀');

                    if ($ccEmails) {
                        $mail->cc($ccEmails);
                    }
                });
            } catch (\Throwable $e) {
                Log::error('Employer pitch email failed', ['job_id' => $jobId, 'error' => $e->getMessage()]);
                return response()->json(['ok' => false, 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
            }

            // Claim today's pitch slot so SocialPublisherService::autoPitchEmployerEmail
            // (fired once the AI image finishes generating) skips this employer today —
            // otherwise they'd get this same "job is live" pitch twice.
            DB::table('jb_companies')->where('id', $company->getKey())->update(['last_employer_pitch_at' => now()]);

            $message = "Email sent to {$email}.";
            if ($ccEmails) {
                $message .= ' CC: ' . implode(', ', $ccEmails) . '.';
            }

            return response()->json(['ok' => true, 'message' => $message]);
        }

        // WhatsApp via Whapi
        $phone = collect($company->contact_numbers ?? [])->first() ?: $company->phone;
        if (! $phone) {
            return response()->json(['ok' => false, 'message' => 'No WhatsApp contact on file for this employer.'], 422);
        }

        $automation = SocialAutomation::query()
            ->where('platform', 'whapi')->where('is_active', true)->get()
            ->first(fn ($a) => !($a->settings['country_id'] ?? null) || (int) ($a->settings['country_id']) === (int) $job->country_id);

        if (! $automation) {
            return response()->json(['ok' => false, 'message' => 'No active WhatsApp (Whapi) automation configured.'], 422);
        }

        $settings   = $automation->settings ?? [];
        $token      = SocialAutomation::whapiToken($automation);
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';
        $jid        = preg_replace('/\D/', '', (string) $phone) . '@s.whatsapp.net';

        try {
            $ok = false;
            $imagePath = trim((string) ($job->employer_image ?: ($job->whatsapp_image ?? '')));

            if ($imagePath !== '') {
                $imageUrl = \Botble\Media\Facades\RvMedia::getImageUrl($imagePath);
                $resp = Http::timeout(30)->withToken($token)->post("{$gatewayUrl}/messages/image", [
                    'to'      => $jid,
                    'media'   => $imageUrl,
                    'caption' => $message,
                ]);
                $ok = $resp->successful();
            }

            if (! $ok) {
                $resp = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                    'to'   => $jid,
                    'body' => $message,
                ]);
                $ok = $resp->successful();
            }
        } catch (\Throwable $e) {
            Log::error('Employer pitch WhatsApp send failed', ['job_id' => $jobId, 'error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'Failed to send WhatsApp message: ' . $e->getMessage()], 500);
        }

        if (! $ok) {
            return response()->json(['ok' => false, 'message' => 'Whapi rejected the message. Check token and limits.'], 500);
        }

        return response()->json(['ok' => true, 'message' => "WhatsApp message sent to +{$phone}."]);
    }

    // -------------------------------------------------------------------------
    // Step 2: Copy post text & delete the Telegram message
    // -------------------------------------------------------------------------

    public function destroy(Request $request)
    {
        $automationId = $request->query('automation_id');
        $automation   = $automationId ? SocialAutomation::query()->find($automationId) : null;
        $settings     = $automation?->settings ?? [];
        $token        = trim((string) ($settings['bot_token'] ?? setting('telegram_bot_token')));
        $chatId       = (string) $request->query('chat_id');
        $messageId    = (string) $request->query('message_id');
        $cacheKey     = (string) $request->query('cache_key', '');
        $jobId        = $request->query('job_id');

        // Try cache first; support both old string format and new array format
        $cached   = $cacheKey ? Cache::get($cacheKey) : null;
        $postText = is_array($cached) ? ($cached['text'] ?? null) : $cached;

        if (! $postText && $jobId) {
            $job = Job::with(['company', 'slugable', 'country'])->find($jobId);
            if ($job) {
                $postText = app(SocialPublisherService::class)->buildManualSocialPost($job);
                if ($cacheKey) {
                    Cache::put($cacheKey, $postText, now()->addDays(7));
                }
            }
        }

        if (! $postText) {
            return response($this->expiredHtml());
        }

        $deleted = false;
        $deleteMessage = null;

        if ($token && $chatId !== '' && $messageId !== '') {
            try {
                $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/deleteMessage", [
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                ]);

                $payload = $response->json();
                $deleted = $response->successful() && (bool) data_get($payload, 'ok');
                $deleteMessage = data_get($payload, 'description') ?: ($deleted ? null : 'Telegram rejected the delete request.');
            } catch (\Throwable $exception) {
                $deleteMessage = $exception->getMessage();
            }
        } else {
            $deleteMessage = 'Missing Telegram token, chat ID, or message ID.';
        }

        if ($deleted) {
            DB::table('telegram_message_log')
                ->where('chat_id', $chatId)
                ->where('message_id', (string) $messageId)
                ->delete();
        } else {
            Log::warning('Telegram message delete failed from social dismiss page.', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'automation_id' => $automationId,
                'message' => $deleteMessage,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $deleted,
                'message' => $deleted ? 'Telegram message deleted.' : ($deleteMessage ?: 'Telegram could not remove the message.'),
            ], $deleted ? 200 : 422);
        }

        $escapedText = htmlspecialchars((string) $postText, ENT_QUOTES, 'UTF-8');
        $jsonText    = json_encode((string) $postText);

        $html = <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Step 2 — Copy Post Text</title>
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                body{font-family:system-ui,sans-serif;background:#f0f4f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
                .card{background:#fff;border-radius:16px;padding:32px 24px;max-width:560px;width:100%;box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center}
                .step-badge{display:inline-flex;align-items:center;gap:6px;background:#0088cc;color:#fff;font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px;margin-bottom:14px}
                .icon{font-size:48px;margin-bottom:12px}
                h2{font-size:20px;color:#1a1a2e;margin-bottom:8px}
                .sub{color:#666;font-size:14px;margin-bottom:20px}
                textarea{width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:12px;font-size:13px;font-family:inherit;resize:vertical;min-height:110px;color:#444;background:#f8fafc;line-height:1.5;text-align:left}
                .copy-btn{display:inline-block;margin-top:16px;padding:11px 28px;background:#0088cc;color:#fff;border:none;border-radius:10px;font-size:15px;cursor:pointer;transition:background .15s}
                .copy-btn:hover{background:#006da8}
                .copy-btn.copied{background:#16a34a}
            </style>
        </head>
        <body>
            <div class="card">
                <div class="step-badge">✅ Step 2 of 2 — Post Text</div>
                <div class="icon">📝</div>
                <h2>Telegram message removed</h2>
                <p class="sub">Copy this text and paste it on LinkedIn, Facebook, or WhatsApp.</p>
                <textarea id="pt" readonly>{$escapedText}</textarea>
                <br>
                <button class="copy-btn" id="copy-btn" onclick="doCopy()">📋 Copy Text</button>
            </div>
            <script>
                const text = {$jsonText};
                function doCopy() {
                    const btn = document.getElementById('copy-btn');
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(() => showCopied(btn)).catch(() => legacyCopy(btn));
                    } else {
                        legacyCopy(btn);
                    }
                }
                function legacyCopy(btn) {
                    const ta = document.getElementById('pt');
                    ta.focus(); ta.select(); ta.setSelectionRange(0, 99999);
                    document.execCommand('copy');
                    showCopied(btn);
                }
                function showCopied(btn) {
                    btn.textContent = '✅ Copied!';
                    btn.classList.add('copied');
                    setTimeout(() => { btn.textContent = '📋 Copy Text'; btn.classList.remove('copied'); }, 2500);
                }
                window.addEventListener('load', doCopy);
            </script>
        </body>
        </html>
        HTML;

        return response($html);
    }

    private function renderPostKitHtml(
        string $aiPrompt,
        ?string $storyboardPrompt,
        ?string $geminiPrompt,
        ?string $tiktokImagePrompt,
        ?string $coverImagePrompt,
        ?string $facebookImagePrompt,
        ?string $linkedinImagePrompt,
        ?string $twitterImagePrompt,
        array $platformPosts,
        array $jobImages,
        ?string $companyLogoUrl,
        ?string $companyName,
        ?string $jobName,
        ?string $jobUrl,
        array $uploadUrls,
        ?string $step2Url,
        string $heroBadge,
        ?string $adminEditUrl,
        ?string $whapiSendUrl = null,
        ?string $whapiChannelName = null,
        ?string $publerSendUrl = null,
        ?string $employerImagePrompt = null,
        ?string $employerPitchMessage = null,
        ?string $employerEmail = null,
        ?string $employerPhone = null,
        ?string $sendToEmployerUrl = null,
        array $employerEmails = [],
        $employerLastPitchAt = null,
        array $generateUrls = [],
        bool $openAiConfigured = false,
    ): string {
        $storyboardSafe    = mb_convert_encoding((string) $storyboardPrompt, 'UTF-8', 'UTF-8');
        $geminiSafe        = mb_convert_encoding((string) $geminiPrompt, 'UTF-8', 'UTF-8');
        $tiktokImageSafe   = mb_convert_encoding((string) $tiktokImagePrompt, 'UTF-8', 'UTF-8');
        $coverImageSafe    = mb_convert_encoding((string) $coverImagePrompt, 'UTF-8', 'UTF-8');
        $facebookImageSafe = mb_convert_encoding((string) $facebookImagePrompt, 'UTF-8', 'UTF-8');
        $linkedinImageSafe = mb_convert_encoding((string) $linkedinImagePrompt, 'UTF-8', 'UTF-8');
        $twitterImageSafe  = mb_convert_encoding((string) $twitterImagePrompt, 'UTF-8', 'UTF-8');
        $employerImageSafe = mb_convert_encoding((string) $employerImagePrompt, 'UTF-8', 'UTF-8');
        $employerPitchSafe = mb_convert_encoding((string) $employerPitchMessage, 'UTF-8', 'UTF-8');

        $aiPromptJson      = json_encode($aiPrompt, JSON_UNESCAPED_UNICODE);
        $storyboardJson    = json_encode($storyboardSafe, JSON_UNESCAPED_UNICODE);
        $geminiJson        = json_encode($geminiSafe, JSON_UNESCAPED_UNICODE);
        $tiktokImageJson   = json_encode($tiktokImageSafe, JSON_UNESCAPED_UNICODE);
        $step2UrlJson      = json_encode($step2Url ?? '', JSON_UNESCAPED_UNICODE);
        $uploadUrlsJson    = json_encode($uploadUrls, JSON_UNESCAPED_UNICODE);
        $generateUrlsJson  = json_encode($generateUrls, JSON_UNESCAPED_UNICODE);
        $openAiConfiguredJson = $openAiConfigured ? 'true' : 'false';
        $jobImagesJson     = json_encode($jobImages, JSON_UNESCAPED_UNICODE);
        $companyLogoJson   = json_encode($companyLogoUrl, JSON_UNESCAPED_UNICODE);
        $companyNameJson   = json_encode((string) ($companyName ?? ''), JSON_UNESCAPED_UNICODE);
        $whapiSendUrlJson  = json_encode($whapiSendUrl, JSON_UNESCAPED_UNICODE);
        $whapiChannelJson  = json_encode($whapiChannelName ?? '', JSON_UNESCAPED_UNICODE);
        $publerSendUrlJson = json_encode($publerSendUrl, JSON_UNESCAPED_UNICODE);
        $sendToEmployerUrlJson = json_encode($sendToEmployerUrl, JSON_UNESCAPED_UNICODE);
        $employerPitchJson     = json_encode($employerPitchSafe, JSON_UNESCAPED_UNICODE);
        $employerEmailJson     = json_encode($employerEmail, JSON_UNESCAPED_UNICODE);
        $employerPhoneJson     = json_encode($employerPhone, JSON_UNESCAPED_UNICODE);
        $csrfToken       = csrf_token();

        $slotPromptsJson = json_encode([
            'cover_image'    => $coverImageSafe,
            'tiktok_image'   => $tiktokImageSafe,
            'whatsapp_image' => $aiPrompt,
            'facebook_image' => $facebookImageSafe,
            'linkedin_image' => $linkedinImageSafe,
            'twitter_image'  => $twitterImageSafe,
            'employer_image' => $employerImageSafe,
        ], JSON_UNESCAPED_UNICODE);

        $imgRefFn = fn(?string $url): string => $url ? "\n\n📎 Image: {$url}" : '';
        $slotPostsJson = json_encode([
            'tiktok_image'   => ($platformPosts['tiktok']   ?? '') . $imgRefFn($jobImages['tiktok_image']   ?: $companyLogoUrl),
            'whatsapp_image' => ($platformPosts['whatsapp']  ?? '') . $imgRefFn($jobImages['whatsapp_image'] ?: $companyLogoUrl),
            'facebook_image' => ($platformPosts['facebook']  ?? '') . $imgRefFn($jobImages['facebook_image'] ?: $companyLogoUrl),
            'linkedin_image' => ($platformPosts['linkedin']  ?? '') . $imgRefFn($jobImages['linkedin_image'] ?: $companyLogoUrl),
            'twitter_image'  => ($platformPosts['twitter']   ?? '') . $imgRefFn($jobImages['twitter_image']  ?: $companyLogoUrl),
            'employer_image' => $employerPitchSafe . $imgRefFn($jobImages['employer_image'] ?: $jobImages['whatsapp_image'] ?: $companyLogoUrl),
        ], JSON_UNESCAPED_UNICODE);

        $escapedStoryboard  = htmlspecialchars($storyboardSafe, ENT_QUOTES, 'UTF-8');
        $escapedGemini      = htmlspecialchars($geminiSafe, ENT_QUOTES, 'UTF-8');
        $escapedTiktokImage = htmlspecialchars($tiktokImageSafe, ENT_QUOTES, 'UTF-8');
        $escapedCoverImage  = htmlspecialchars($coverImageSafe, ENT_QUOTES, 'UTF-8');

        $escapedJobName  = htmlspecialchars((string) ($jobName ?? 'New Job'), ENT_QUOTES, 'UTF-8');
        $escapedCompany  = htmlspecialchars((string) ($companyName ?? ''), ENT_QUOTES, 'UTF-8');
        $escapedJobUrl   = $jobUrl ? htmlspecialchars($jobUrl, ENT_QUOTES, 'UTF-8') : '';
        $heroCompanyHtml = $escapedCompany ? "<div class=\"hero-company\">🏢 {$escapedCompany}</div>" : '';
        $jobBtnHtml      = $escapedJobUrl ? "<a href=\"{$escapedJobUrl}\" target=\"_blank\" rel=\"noopener\" class=\"bb-job\">🔗 View Job</a>" : '';
        $escapedHeroBadge = htmlspecialchars($heroBadge, ENT_QUOTES, 'UTF-8');
        $copyCompanyNameHtml = ! $companyLogoUrl && $escapedCompany
            ? '<button type="button" class="img-copy-btn" onclick="doCopy(companyName,this,\'Copy company name\')" title="Copy company name">📋 Copy name</button>'
            : '';

        // Employer pitch tab
        $escapedEmployerPitch = htmlspecialchars($employerPitchSafe, ENT_QUOTES, 'UTF-8');
        // Auto-pitch (SocialPublisherService::autoPitchEmployerEmail) already emails the
        // employer once per day after the AI image finishes generating. Disable the manual
        // button when that's already happened today so we don't double-email the employer.
        $employerPitchedToday = $employerLastPitchAt && $employerLastPitchAt->greaterThanOrEqualTo(now()->startOfDay());
        $employerEmailBtnHtml = $employerEmail
            ? '<button class="btn btn-dark send-employer-email-btn" onclick="sendToEmployer(\'email\',this)"' . ($employerPitchedToday ? ' disabled' : '') . '>📧 Email Employer</button>'
            : '';
        $employerAlreadyPitchedHtml = $employerPitchedToday
            ? '<div class="tip-blue">✅ Already emailed employer today at ' . $employerLastPitchAt->format('d M, H:i')
                . '. <a href="#" onclick="this.closest(\'.card\').querySelector(\'.send-employer-email-btn\').disabled=false;this.remove();return false;" style="color:#7c3aed">Send again anyway</a></div>'
            : '';
        $employerWhatsappBtnHtml = $employerPhone
            ? '<button class="btn btn-purple" onclick="sendToEmployer(\'whatsapp\',this)">💬 WhatsApp Employer</button>'
            : '';
        $employerNoContactHtml = (! $employerEmail && ! $employerPhone)
            ? '<div class="tip-amber">⚠️ No contact email or WhatsApp number on file for this employer yet.</div>'
            : '';

        $employerEmailsDisplay = $employerEmails ?: array_filter([$employerEmail]);
        $employerEmailsHtml = '';
        if ($employerEmailsDisplay) {
            $emailChips = implode(' &nbsp;·&nbsp; ', array_map(
                fn ($e) => htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8'),
                $employerEmailsDisplay,
            ));
            $emailNote = count($employerEmailsDisplay) > 1 ? ' (first one used for "Email Employer")' : '';
            $employerEmailsHtml = "<div class=\"tip-blue\">📧 Emails on file{$emailNote}: <strong>{$emailChips}</strong></div>";
        }

        $wjLogo1 = 'https://www.wakandajobs.com/storage/gemini-generated-image-s1e9dgs1e9dgs1e9.png';
        $wjLogo2 = 'https://www.wakandajobs.com/storage/chatgpt-image-may-14-2026-03-00-04-pm.png';
        $attachmentTipHtml  = '<div class="tip-amber">💡 <strong>Attach these to ChatGPT BEFORE pasting the prompt</strong> (ChatGPT cannot fetch URLs):<br>';
        $attachmentTipHtml .= '<a href="' . $wjLogo1 . '" target="_blank" rel="noopener" class="logo-link">WJ Logo 1 →</a>';
        $attachmentTipHtml .= ' &nbsp;|&nbsp; <a href="' . $wjLogo2 . '" target="_blank" rel="noopener" class="logo-link">WJ Logo 2 →</a>';
        if ($companyLogoUrl) {
            $escapedLogoUrl  = htmlspecialchars($companyLogoUrl, ENT_QUOTES, 'UTF-8');
            $escapedCompName = htmlspecialchars((string) $companyName, ENT_QUOTES, 'UTF-8');
            $attachmentTipHtml .= ' &nbsp;|&nbsp; <a href="' . $escapedLogoUrl . '" target="_blank" rel="noopener" class="logo-link">' . $escapedCompName . ' logo →</a>';
        }
        $attachmentTipHtml .= '</div>';

        // Bottom bar — admin gets a back link + close tab; Telegram gets dismiss + next
        if ($adminEditUrl !== null) {
            $escapedAdminUrl = htmlspecialchars($adminEditUrl, ENT_QUOTES, 'UTF-8');
            $dismissBtnHtml  = "<a href=\"{$escapedAdminUrl}\" class=\"bb-dismiss\" style=\"text-decoration:none;display:inline-flex;align-items:center;justify-content:center\">← Back to Edit</a>";
            $nextBtnHtml     = '<button onclick="window.close()" class="bb-next" style="background:#475569">✕ Close Tab</button>';
            $jsDismissFn     = '';
        } else {
            $dismissBtnHtml = '<button class="bb-dismiss" id="dismiss-btn" onclick="dismiss()">🗑 Dismiss</button>';
            $nextBtnHtml    = $step2Url
                ? '<a href="' . htmlspecialchars($step2Url, ENT_QUOTES) . '" class="bb-next">Next: Get Post Text →</a>'
                : '<span class="bb-next bb-next--disabled">Next: Get Post Text</span>';
            $jsDismissFn = <<<'JSFN'

            function dismiss() {
                const btn = document.getElementById('dismiss-btn');
                const tip = document.getElementById('close-tip');
                btn.disabled = true;
                btn.textContent = '⏳ Dismissing…';
                if (!step2Url) {
                    btn.classList.add('done');
                    btn.textContent = '✅ Done';
                    tip.textContent = 'You can close this tab.';
                    tip.style.display = 'block';
                    setTimeout(() => window.close(), 600);
                    return;
                }
                fetch(step2Url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(async r => {
                    const d = await r.json().catch(() => ({}));
                    if (!r.ok || d.ok === false) throw new Error(d.message || 'Telegram could not remove the message.');
                    btn.classList.add('done');
                    btn.textContent = '✅ Done';
                    tip.textContent = '✅ Telegram message deleted — you can close this tab.';
                    tip.style.display = 'block';
                    setTimeout(() => window.close(), 600);
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.textContent = '🗑 Retry Dismiss';
                    tip.textContent = err.message || 'Could not dismiss. Please try again.';
                    tip.style.color = '#b91c1c';
                    tip.style.display = 'block';
                });
            }
JSFN;
        }

        $faviconUrl = htmlspecialchars((string) \Botble\Base\Facades\AdminHelper::getAdminFaviconUrl(), ENT_QUOTES, 'UTF-8');
        $faviconType = htmlspecialchars((string) setting('admin_favicon_type', 'image/x-icon'), ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Post Kit — Wakanda Jobs</title>
            <meta name="csrf-token" content="{$csrfToken}">
            <link rel="icon shortcut" href="{$faviconUrl}" type="{$faviconType}">
            <meta property="og:image" content="{$faviconUrl}">
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                :root{
                    --p:#7c3aed;--pd:#5b21b6;--pl:#a78bfa;
                    --dark:#0f172a;--dark2:#1e293b;--dark3:#334155;
                    --slate:#f1f5f9;--muted:#64748b;
                }
                body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:var(--slate);min-height:100vh;padding-bottom:90px}
                .page{max-width:640px;margin:0 auto}

                /* ── Hero ── */
                .hero{background:linear-gradient(135deg,#3b0764 0%,#6d28d9 55%,#7c3aed 100%);padding:22px 16px 0;position:relative;overflow:hidden}
                .hero::before{content:'';position:absolute;top:-60px;right:-60px;width:220px;height:220px;background:radial-gradient(circle,rgba(167,139,250,.25) 0%,transparent 70%);pointer-events:none}
                .hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:22px;background:var(--slate);border-radius:22px 22px 0 0}
                .hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);color:#fff;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;border:1px solid rgba(255,255,255,.3);backdrop-filter:blur(6px);margin-bottom:10px}
                .hero h1{color:#fff;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;opacity:.7;margin-bottom:5px;position:relative}
                .hero-job-title{color:#fff;font-size:20px;font-weight:800;line-height:1.25;margin-bottom:4px;position:relative}
                .hero-company{color:rgba(255,255,255,.75);font-size:13px;font-weight:500;margin-bottom:10px;position:relative}
                .hero-job-link{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:12px;font-weight:700;text-decoration:none;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);border-radius:9px;padding:7px 14px;margin-bottom:18px;position:relative;transition:background .15s;backdrop-filter:blur(6px)}
                .hero-job-link:hover{background:rgba(255,255,255,.28);color:#fff}

                /* ── Tab bar ── */
                .tab-bar{position:sticky;top:0;z-index:100;background:var(--slate);padding:10px 16px 0;border-bottom:2px solid #e2e8f0}
                .tab-nav{display:flex;gap:3px;background:#e2e8f0;border-radius:12px;padding:4px}
                .tab-btn{flex:1;padding:8px 6px;background:none;border:none;border-radius:9px;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;transition:all .18s;white-space:nowrap}
                .tab-btn.active{background:#fff;color:var(--p);box-shadow:0 1px 5px rgba(0,0,0,.1)}

                /* ── Tab panes ── */
                .tab-pane{display:none;padding:16px 16px 8px}
                .tab-pane.active{display:block}

                /* ── Cards ── */
                .card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:14px}
                .section-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--p);margin-bottom:10px}

                /* ── Textareas ── */
                textarea{width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:11px 13px;font-size:12.5px;font-family:inherit;resize:vertical;color:#334155;background:#f8fafc;line-height:1.6}
                textarea:focus{outline:none;border-color:var(--p);background:#fff}

                /* ── Buttons ── */
                .btn-row{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
                .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 18px;border:none;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;transition:all .15s;text-decoration:none;flex:1}
                .btn-purple{background:var(--p);color:#fff}
                .btn-purple:hover{background:var(--pd)}
                .btn-purple.ok{background:#16a34a}
                .btn-dark{background:var(--dark);color:#fff}
                .btn-dark:hover{background:var(--dark2);color:#fff}

                /* ── Tips ── */
                .tip-amber{margin-top:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:11px 14px;font-size:12px;color:#92400e;line-height:1.6}
                .tip-blue{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 14px;font-size:12px;color:#1e40af;line-height:1.6;margin-bottom:14px}
                .logo-link{color:var(--p);font-weight:600;word-break:break-all}

                /* ── Image slots ── */
                .img-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
                .img-slot{background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 8px rgba(0,0,0,.06)}
                .img-slot.full-width{grid-column:1/-1}
                .img-slot-head{padding:10px 12px 8px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px}
                .img-copy-btn{margin-left:auto;flex-shrink:0;padding:5px 10px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:700;color:#475569;cursor:pointer;transition:all .15s;white-space:nowrap}
                .img-copy-btn:hover{background:var(--p);color:#fff;border-color:var(--p)}
                .img-copy-btn.ok{background:#16a34a;color:#fff;border-color:#16a34a}
                .img-slot-icon{font-size:16px;flex-shrink:0}
                .img-slot-info{flex:1;min-width:0}
                .img-slot-label{font-size:12px;font-weight:700;color:#1e293b;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
                .img-slot-dim{font-size:10px;color:var(--muted);display:block}
                .img-slot-body{padding:10px 12px 12px;position:relative}

                /* Preview state */
                .img-preview-wrap{position:relative;border-radius:10px;overflow:hidden;background:#0f172a;aspect-ratio:attr(data-ratio);min-height:80px}
                .img-preview-wrap img{width:100%;height:100%;object-fit:cover;display:block}
                .img-preview-overlay{position:absolute;inset:0;background:rgba(0,0,0,.45);opacity:0;transition:opacity .18s;display:flex;align-items:center;justify-content:center}
                .img-preview-wrap:hover .img-preview-overlay{opacity:1}
                .img-replace-btn{background:rgba(255,255,255,.92);color:#1e293b;border:none;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px}

                /* Upload zone state */
                .img-upload-zone{border:2px dashed #cbd5e1;border-radius:10px;padding:18px 12px;text-align:center;cursor:pointer;transition:all .18s;background:#f8fafc;min-height:80px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px}
                .img-upload-zone:hover{border-color:var(--p);background:#faf5ff}
                .img-upload-zone.dragging{border-color:var(--p);background:#f3e8ff}
                .img-upload-zone-icon{font-size:22px;margin-bottom:2px}
                .img-upload-zone-label{font-size:11.5px;font-weight:600;color:#475569}
                .img-upload-zone-sub{font-size:10px;color:var(--muted)}

                /* Card footer buttons */
                .img-slot-footer{display:flex;gap:7px;margin-top:10px;flex-wrap:wrap}
                .img-footer-btn{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s;text-decoration:none;background:#f8fafc;color:#475569;white-space:nowrap}
                .img-footer-btn:hover{border-color:var(--p);color:var(--p);background:#faf5ff}
                .img-footer-btn.ok{background:#16a34a;color:#fff;border-color:#16a34a}
                .img-footer-btn:disabled,.img-footer-btn[aria-disabled="true"]{opacity:.35;pointer-events:none;cursor:default}
                .img-footer-btn.dl{color:#0ea5e9;border-color:#bae6fd;background:#f0f9ff}
                .img-footer-btn.dl:hover{background:#0ea5e9;color:#fff;border-color:#0ea5e9}
                /* Mobile: stack footer buttons full-width so none get clipped by the narrow card */
                @media (max-width:560px){
                    .img-slot-footer{flex-direction:column;gap:6px}
                    .img-footer-btn{flex:none;width:100%;padding:11px 10px;font-size:13px}
                }

                /* Status / progress */
                .img-progress{margin-top:8px;display:none}
                .img-progress-bar-wrap{height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden}
                .img-progress-bar{height:100%;width:0%;background:var(--p);border-radius:99px;transition:width .1s linear}
                .img-progress-bar.done{background:#16a34a}
                .img-progress-bar.fail{background:#dc2626}
                .img-progress-label{margin-top:4px;font-size:11px;font-weight:700;text-align:center;color:var(--p)}
                .img-progress-label.done{color:#16a34a}
                .img-progress-label.fail{color:#dc2626}

                /* ── Video tab ── */
                .video-hero{background:linear-gradient(160deg,#020617 0%,#0d1424 55%,#1a0a2e 100%);border-radius:18px;padding:22px 18px 20px;margin-bottom:14px;position:relative;overflow:hidden}
                .video-hero::before{content:'';position:absolute;top:-50px;right:-50px;width:200px;height:200px;background:radial-gradient(circle,rgba(124,58,237,.25) 0%,transparent 65%);pointer-events:none}
                .video-hero::after{content:'';position:absolute;bottom:-40px;left:-40px;width:160px;height:160px;background:radial-gradient(circle,rgba(245,158,11,.1) 0%,transparent 65%);pointer-events:none}
                .video-hero-eyebrow{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#a78bfa;margin-bottom:6px;position:relative}
                .video-hero-title{font-size:20px;font-weight:800;color:#fff;margin-bottom:4px;position:relative}
                .video-hero-sub{font-size:12px;color:#94a3b8;margin-bottom:18px;position:relative}

                /* Flow diagram */
                .flow{display:flex;align-items:center;gap:0;margin-bottom:18px;overflow-x:auto;padding-bottom:2px;position:relative}
                .flow-node{flex:1;min-width:68px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);border-radius:11px;padding:10px 6px;text-align:center}
                .flow-node-icon{font-size:20px;margin-bottom:4px}
                .flow-node-label{font-size:9.5px;color:#cbd5e1;font-weight:700;line-height:1.3;text-transform:uppercase;letter-spacing:.04em}
                .flow-node-sub{font-size:9px;color:#64748b;margin-top:2px}
                .flow-arrow{color:#a78bfa;font-size:16px;padding:0 5px;flex-shrink:0;font-weight:700}

                /* Frame strip */
                .frame-strip{display:flex;gap:6px;margin-bottom:18px;position:relative}
                .frame-card{flex:1;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:9px 4px;text-align:center}
                .frame-num{width:22px;height:22px;background:var(--p);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;margin:0 auto 4px}
                .frame-tag{font-size:8.5px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.04em;line-height:1.2}
                .frame-time{font-size:8px;color:#475569;margin-top:3px}

                /* Video step cards */
                .vstep{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:17px 16px;margin-bottom:12px;position:relative}
                .vstep-header{display:flex;align-items:center;gap:10px;margin-bottom:8px}
                .vstep-num{width:28px;height:28px;background:var(--p);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0}
                .vstep h4{font-size:13.5px;font-weight:700;color:#f1f5f9;line-height:1.3}
                .vstep-where{font-size:11px;font-weight:600;color:#a78bfa;margin-left:auto;white-space:nowrap}
                .vstep p{font-size:11.5px;color:#94a3b8;margin-bottom:10px;line-height:1.5}
                .vstep textarea{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.15);color:#e2e8f0}
                .vstep textarea:focus{background:rgba(255,255,255,.12);border-color:#a78bfa}
                .vstep .btn-row .btn-purple{background:rgba(124,58,237,.75);border:1px solid rgba(167,139,250,.4)}
                .vstep .btn-row .btn-purple:hover{background:var(--p)}
                .vstep .btn-row .btn-purple.ok{background:#16a34a}
                .vstep-gemini{background:linear-gradient(135deg,rgba(66,133,244,.12) 0%,rgba(52,168,83,.08) 50%,rgba(251,188,4,.08) 100%);border:1px solid rgba(66,133,244,.3)}
                .gemini-badge-wrap{margin-bottom:10px}
                .gemini-badge{display:inline-block;font-size:11px;font-weight:800;letter-spacing:.02em;padding:3px 11px;border-radius:20px;border:1px solid rgba(255,255,255,.2);background:rgba(0,0,0,.3)}
                .g-b{color:#4285F4}.g-e{color:#EA4335}.g-y{color:#FBBC04}.g-g{color:#34A853}
                .gemini-model-tip{font-size:11px;color:#93c5fd;background:rgba(37,99,235,.2);border:1px solid rgba(59,130,246,.3);border-radius:8px;padding:8px 12px;margin-bottom:10px;line-height:1.5}

                /* ── Sticky bottom bar ── */
                .bottom-bar{position:fixed;bottom:0;left:0;right:0;background:rgba(241,245,249,.96);backdrop-filter:blur(14px);border-top:1px solid #e2e8f0;padding:10px 16px 14px;z-index:200}
                .bottom-bar-inner{display:flex;gap:8px;max-width:640px;margin:0 auto;align-items:center}
                .bb-dismiss{padding:12px 16px;background:#dc2626;color:#fff;border:none;border-radius:11px;font-size:13.5px;font-weight:700;cursor:pointer;transition:background .15s;white-space:nowrap}
                .bb-dismiss:hover{background:#b91c1c}
                .bb-dismiss.done{background:#16a34a;cursor:default}
                .bb-next{flex:1;display:flex;align-items:center;justify-content:center;padding:12px 16px;background:var(--dark);color:#fff;border-radius:11px;font-size:13.5px;font-weight:700;text-decoration:none;transition:background .15s;border:none;cursor:pointer}
                .bb-next:hover{background:var(--dark2);color:#fff}
                .bb-next--disabled{opacity:.4;cursor:default;pointer-events:none}
                .bb-close-tip{font-size:11.5px;color:#666;text-align:center;margin-top:6px;max-width:640px;margin-left:auto;margin-right:auto;display:none}
                .bb-job{padding:10px 14px;background:#7c3aed;color:#fff;border:none;border-radius:11px;font-size:13px;font-weight:700;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:background .15s}
                .bb-job:hover{background:#6d28d9;color:#fff}
                /* ── Desktop layout ── */
                @media(min-width:900px){
                    body{padding-bottom:80px}
                    .page{max-width:1200px}
                    .hero{padding:36px 48px 0}
                    .hero-job-title{font-size:28px}
                    .hero::after{height:28px;border-radius:28px 28px 0 0}
                    .tab-bar{padding:14px 48px 0}
                    .tab-nav{max-width:500px}
                    .tab-pane{padding:28px 48px 16px}
                    .img-grid{grid-template-columns:repeat(3,1fr);gap:16px}
                    .img-slot.full-width{grid-column:1/-1}
                    .img-slot-label{font-size:13px}
                    .vstep-pair{display:grid;grid-template-columns:1fr 1fr;gap:18px}
                    .vstep-pair>.vstep{margin-bottom:0}
                    .video-hero{padding:30px 28px 26px}
                    .bottom-bar{padding:14px 48px 18px}
                    .bottom-bar-inner{max-width:1200px;gap:16px}
                    .bb-dismiss,.bb-next{font-size:14px;padding:13px 22px}
                    .bb-job{font-size:13.5px;padding:12px 18px}
                }
                @media(min-width:1200px){
                    .page{max-width:1400px}
                    .hero{padding:40px 64px 0}
                    .tab-bar{padding:14px 64px 0}
                    .tab-pane{padding:32px 64px 20px}
                    .img-grid{grid-template-columns:repeat(4,1fr);gap:18px}
                    .bottom-bar{padding:14px 64px 20px}
                    .bottom-bar-inner{max-width:1400px}
                }
            </style>
        </head>
        <body>

        <!-- ── Hero ── -->
        <div class="hero">
            <div class="page">
                <div class="hero-badge">{$escapedHeroBadge}</div>
                <h1>Content Creator</h1>
                <div class="hero-job-title">{$escapedJobName}</div>
                {$heroCompanyHtml}
            </div>
        </div>

        <!-- ── Tab bar ── -->
        <div class="tab-bar">
            <div class="page">
                <div class="tab-nav">
                    <button class="tab-btn active" onclick="switchTab('image',this)">🎨 Images</button>
                    <button class="tab-btn" onclick="switchTab('video',this)">🎬 Video</button>
                    <button class="tab-btn" onclick="switchTab('employer',this)">📨 Employer</button>
                </div>
            </div>
        </div>

        <div class="page">

            <!-- ══════════════ IMAGE TAB ══════════════ -->
            <div id="tab-image" class="tab-pane active">

                <!-- Company Logo + Cover Image row -->
                <div class="img-grid">

                    <div class="img-slot full-width" id="slot-company_logo">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">🏢</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">{$escapedCompany}</span>
                                <span class="img-slot-dim">Company Logo · Square or landscape · PNG/WebP</span>
                            </div>
                            {$copyCompanyNameHtml}
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-company_logo" style="display:none">
                                <div class="img-preview-wrap" style="max-height:120px;aspect-ratio:auto">
                                    <img id="img-company_logo" src="" alt="Company logo" style="height:120px;width:auto;margin:0 auto;display:block;object-fit:contain;background:#f8fafc;border-radius:8px">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('company_logo')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-company_logo" class="img-upload-zone" onclick="triggerUpload('company_logo')" ondragover="onDragOver(event,'company_logo')" ondragleave="onDragLeave('company_logo')" ondrop="onDrop(event,'company_logo')">
                                <div class="img-upload-zone-icon">🖼</div>
                                <div class="img-upload-zone-label">Upload Company Logo</div>
                                <div class="img-upload-zone-sub">PNG · JPG · WebP</div>
                            </div>
                            <input type="file" id="file-company_logo" accept="image/*" onchange="handleFileSelect('company_logo',this)" style="display:none">
                            <div class="img-progress" id="progress-company_logo">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-company_logo"></div></div>
                                <div class="img-progress-label" id="label-company_logo"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-company_logo" href="#" download="company-logo" style="display:none">⬇ Download</a>
                            </div>
                        </div>
                    </div>

                    <div class="img-slot full-width" id="slot-cover_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">🖼</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">Job Cover Image</span>
                                <span class="img-slot-dim">1800 × 540 px · landscape banner</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('cover_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-cover_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:10/3">
                                    <img id="img-cover_image" src="" alt="Cover image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('cover_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-cover_image" class="img-upload-zone" onclick="triggerUpload('cover_image')" ondragover="onDragOver(event,'cover_image')" ondragleave="onDragLeave('cover_image')" ondrop="onDrop(event,'cover_image')">
                                <div class="img-upload-zone-icon">🖼</div>
                                <div class="img-upload-zone-label">Upload Job Cover Image</div>
                                <div class="img-upload-zone-sub">1800 × 540 px · PNG · JPG · WebP</div>
                            </div>
                            <input type="file" id="file-cover_image" accept="image/*" onchange="handleFileSelect('cover_image',this)" style="display:none">
                            <div class="img-progress" id="progress-cover_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-cover_image"></div></div>
                                <div class="img-progress-label" id="label-cover_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-cover_image" href="#" download="cover-image" style="display:none">⬇ Download</a>
                            </div>
                        </div>
                    </div>

                    <!-- TikTok image -->
                    <div class="img-slot" id="slot-tiktok_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">🎵</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">TikTok</span>
                                <span class="img-slot-dim">1080 × 1920 · 9:16</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('tiktok_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-tiktok_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:9/16">
                                    <img id="img-tiktok_image" src="" alt="TikTok image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('tiktok_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-tiktok_image" class="img-upload-zone" onclick="triggerUpload('tiktok_image')" ondragover="onDragOver(event,'tiktok_image')" ondragleave="onDragLeave('tiktok_image')" ondrop="onDrop(event,'tiktok_image')">
                                <div class="img-upload-zone-icon">🎵</div>
                                <div class="img-upload-zone-label">Upload TikTok Image</div>
                                <div class="img-upload-zone-sub">1080 × 1920</div>
                            </div>
                            <input type="file" id="file-tiktok_image" accept="image/*" onchange="handleFileSelect('tiktok_image',this)" style="display:none">
                            <div class="img-progress" id="progress-tiktok_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-tiktok_image"></div></div>
                                <div class="img-progress-label" id="label-tiktok_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-tiktok_image" href="#" download="tiktok-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('tiktok_image',this)">📋 Post Text</button>
                                <button class="img-footer-btn" id="repost-tiktok_image" style="display:none" onclick="pkAskSendToPubler()">🔁 Repost</button>
                            </div>
                        </div>
                    </div>

                    <!-- WhatsApp image -->
                    <div class="img-slot" id="slot-whatsapp_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">💬</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">WhatsApp</span>
                                <span class="img-slot-dim">1080 × 1920 · status</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('whatsapp_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-whatsapp_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:9/16">
                                    <img id="img-whatsapp_image" src="" alt="WhatsApp image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('whatsapp_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-whatsapp_image" class="img-upload-zone" onclick="triggerUpload('whatsapp_image')" ondragover="onDragOver(event,'whatsapp_image')" ondragleave="onDragLeave('whatsapp_image')" ondrop="onDrop(event,'whatsapp_image')">
                                <div class="img-upload-zone-icon">💬</div>
                                <div class="img-upload-zone-label">Upload WhatsApp Image</div>
                                <div class="img-upload-zone-sub">1080 × 1920</div>
                            </div>
                            <input type="file" id="file-whatsapp_image" accept="image/*" onchange="handleFileSelect('whatsapp_image',this)" style="display:none">
                            <div class="img-progress" id="progress-whatsapp_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-whatsapp_image"></div></div>
                                <div class="img-progress-label" id="label-whatsapp_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-whatsapp_image" href="#" download="whatsapp-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('whatsapp_image',this)">📋 Post Text</button>
                                <button class="img-footer-btn" id="repost-whatsapp_image" style="display:none" onclick="pkAskSendToChannel()">🔁 Repost</button>
                            </div>
                        </div>
                    </div>

                    <!-- Facebook image -->
                    <div class="img-slot" id="slot-facebook_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">f</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">Facebook</span>
                                <span class="img-slot-dim">1200 × 630 · landscape</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('facebook_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-facebook_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:1200/630">
                                    <img id="img-facebook_image" src="" alt="Facebook image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('facebook_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-facebook_image" class="img-upload-zone" onclick="triggerUpload('facebook_image')" ondragover="onDragOver(event,'facebook_image')" ondragleave="onDragLeave('facebook_image')" ondrop="onDrop(event,'facebook_image')">
                                <div class="img-upload-zone-icon">f</div>
                                <div class="img-upload-zone-label">Upload Facebook Image</div>
                                <div class="img-upload-zone-sub">1200 × 630</div>
                            </div>
                            <input type="file" id="file-facebook_image" accept="image/*" onchange="handleFileSelect('facebook_image',this)" style="display:none">
                            <div class="img-progress" id="progress-facebook_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-facebook_image"></div></div>
                                <div class="img-progress-label" id="label-facebook_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-facebook_image" href="#" download="facebook-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('facebook_image',this)">📋 Post Text</button>
                            </div>
                        </div>
                    </div>

                    <!-- LinkedIn image -->
                    <div class="img-slot" id="slot-linkedin_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">in</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">LinkedIn</span>
                                <span class="img-slot-dim">1200 × 627 · landscape</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('linkedin_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-linkedin_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:1200/627">
                                    <img id="img-linkedin_image" src="" alt="LinkedIn image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('linkedin_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-linkedin_image" class="img-upload-zone" onclick="triggerUpload('linkedin_image')" ondragover="onDragOver(event,'linkedin_image')" ondragleave="onDragLeave('linkedin_image')" ondrop="onDrop(event,'linkedin_image')">
                                <div class="img-upload-zone-icon">in</div>
                                <div class="img-upload-zone-label">Upload LinkedIn Image</div>
                                <div class="img-upload-zone-sub">1200 × 627</div>
                            </div>
                            <input type="file" id="file-linkedin_image" accept="image/*" onchange="handleFileSelect('linkedin_image',this)" style="display:none">
                            <div class="img-progress" id="progress-linkedin_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-linkedin_image"></div></div>
                                <div class="img-progress-label" id="label-linkedin_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-linkedin_image" href="#" download="linkedin-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('linkedin_image',this)">📋 Post Text</button>
                            </div>
                        </div>
                    </div>

                    <!-- Twitter / X image -->
                    <div class="img-slot" id="slot-twitter_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">𝕏</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">X / Twitter</span>
                                <span class="img-slot-dim">1200 × 675 · 16:9</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('twitter_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-twitter_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:16/9">
                                    <img id="img-twitter_image" src="" alt="X / Twitter image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('twitter_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-twitter_image" class="img-upload-zone" onclick="triggerUpload('twitter_image')" ondragover="onDragOver(event,'twitter_image')" ondragleave="onDragLeave('twitter_image')" ondrop="onDrop(event,'twitter_image')">
                                <div class="img-upload-zone-icon">𝕏</div>
                                <div class="img-upload-zone-label">Upload X / Twitter Image</div>
                                <div class="img-upload-zone-sub">1200 × 675</div>
                            </div>
                            <input type="file" id="file-twitter_image" accept="image/*" onchange="handleFileSelect('twitter_image',this)" style="display:none">
                            <div class="img-progress" id="progress-twitter_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-twitter_image"></div></div>
                                <div class="img-progress-label" id="label-twitter_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-twitter_image" href="#" download="twitter-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('twitter_image',this)">📋 Post Text</button>
                            </div>
                        </div>
                    </div>

                </div><!-- /img-grid -->

            </div><!-- /tab-image -->

            <!-- ══════════════ VIDEO TAB ══════════════ -->
            <div id="tab-video" class="tab-pane">
                <div class="video-hero">

                    <div class="video-hero-eyebrow">🎬 10-Second Video Ad</div>
                    <div class="video-hero-title">TikTok · Reels · WhatsApp Status</div>
                    <div class="video-hero-sub">Two AI tools. Four frames. One scroll-stopping video.</div>

                    <div class="flow">
                        <div class="flow-node">
                            <div class="flow-node-icon">💬</div>
                            <div class="flow-node-label">ChatGPT</div>
                            <div class="flow-node-sub">4 frames</div>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node">
                            <div class="flow-node-icon">🖼</div>
                            <div class="flow-node-label">Download</div>
                            <div class="flow-node-sub">all 4 JPEGs</div>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node" style="background:rgba(66,133,244,.15);border-color:rgba(66,133,244,.3)">
                            <div class="flow-node-icon">✨</div>
                            <div class="flow-node-label">Gemini</div>
                            <div class="flow-node-sub">10s video</div>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node" style="background:rgba(52,168,83,.12);border-color:rgba(52,168,83,.3)">
                            <div class="flow-node-icon">🎥</div>
                            <div class="flow-node-label">MP4 done</div>
                            <div class="flow-node-sub">post it!</div>
                        </div>
                    </div>

                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:7px">Video timeline</div>
                    <div class="frame-strip">
                        <div class="frame-card">
                            <div class="frame-num">1</div>
                            <div class="frame-tag">Hook</div>
                            <div class="frame-time">0 – 2 s</div>
                        </div>
                        <div class="frame-card">
                            <div class="frame-num">2</div>
                            <div class="frame-tag">Oppty</div>
                            <div class="frame-time">2 – 5 s</div>
                        </div>
                        <div class="frame-card">
                            <div class="frame-num">3</div>
                            <div class="frame-tag">Details</div>
                            <div class="frame-time">5 – 8 s</div>
                        </div>
                        <div class="frame-card" style="background:rgba(124,58,237,.15);border-color:rgba(124,58,237,.35)">
                            <div class="frame-num">4</div>
                            <div class="frame-tag">CTA 🚀</div>
                            <div class="frame-time">8 – 10 s</div>
                        </div>
                    </div>

                    <div class="vstep-pair">

                    <div class="vstep">
                        <div class="vstep-header">
                            <div class="vstep-num">1</div>
                            <h4>Storyboard — 4 portrait frames</h4>
                            <span class="vstep-where">→ ChatGPT</span>
                        </div>
                        <p>Paste into ChatGPT (attach Wakanda Jobs logo). It generates 4 sequential 1080×1920 images — one per scene. Download all four.</p>
                        <textarea id="storyboard-ta" readonly rows="8">{$escapedStoryboard}</textarea>
                        <div class="btn-row" style="margin-top:10px">
                            <button class="btn btn-purple" onclick="copyField('storyboard-ta',this,'📋 Copy Storyboard')">📋 Copy Storyboard</button>
                        </div>
                    </div>

                    <div class="vstep vstep-gemini">
                        <div class="gemini-badge-wrap">
                            <span class="gemini-badge"><span class="g-b">G</span><span class="g-e">e</span><span class="g-y">m</span><span class="g-g">i</span><span class="g-b">n</span><span class="g-e">i</span> <span style="color:#fff;opacity:.8">Omni</span> — Video Generation</span>
                        </div>
                        <div class="vstep-header">
                            <div class="vstep-num" style="background:linear-gradient(135deg,#4285F4,#34A853)">2</div>
                            <h4>Animate 4 frames → 10-second video</h4>
                            <span class="vstep-where" style="color:#4ade80">→ Gemini</span>
                        </div>
                        <div class="gemini-model-tip">
                            📌 <strong>Use:</strong> <strong>Gemini 2.0 Flash</strong> (Experimental) with video generation, or <strong>Google Veo 2</strong> via Gemini Advanced.<br>
                            📎 <strong>Attach in order:</strong> Frame 1 → Frame 2 → Frame 3 → Frame 4 → Wakanda Jobs logo PNG — then paste the prompt below.
                        </div>
                        <p>Gemini animates the 4 frames into a punchy 10-second MP4 with transitions, text effects, Amapiano/Afrobeats audio, and the Wakanda Jobs logo as a persistent watermark.</p>
                        <textarea id="gemini-ta" readonly rows="9">{$escapedGemini}</textarea>
                        <div class="btn-row" style="margin-top:10px">
                            <button class="btn btn-purple" onclick="copyField('gemini-ta',this,'📋 Copy Gemini Script')">📋 Copy Gemini Script</button>
                        </div>
                    </div><!-- /.vstep-pair -->

                </div><!-- /video-hero -->
            </div>

            <!-- ══════════════ EMPLOYER TAB ══════════════ -->
            <div id="tab-employer" class="tab-pane">
                <div class="tip-blue">📨 Let the employer know their job ad is live and is being professionally marketed across our platforms — builds trust and shows the value of advertising with Wakanda Jobs.</div>

                <div class="img-grid">
                    <div class="img-slot full-width" id="slot-employer_image">
                        <div class="img-slot-head">
                            <span class="img-slot-icon">📨</span>
                            <div class="img-slot-info">
                                <span class="img-slot-label">Employer Update Image</span>
                                <span class="img-slot-dim">1080 × 1350 · 4:5 portrait</span>
                            </div>
                            <button class="img-copy-btn" onclick="copySlotPrompt('employer_image',this)" title="Copy AI prompt for this image">📋 Copy</button>
                        </div>
                        <div class="img-slot-body">
                            <div id="preview-employer_image" style="display:none">
                                <div class="img-preview-wrap" style="aspect-ratio:1080/1350">
                                    <img id="img-employer_image" src="" alt="Employer update image">
                                    <div class="img-preview-overlay">
                                        <button class="img-replace-btn" onclick="triggerUpload('employer_image')">🔄 Replace</button>
                                    </div>
                                </div>
                            </div>
                            <div id="zone-employer_image" class="img-upload-zone" onclick="triggerUpload('employer_image')" ondragover="onDragOver(event,'employer_image')" ondragleave="onDragLeave('employer_image')" ondrop="onDrop(event,'employer_image')">
                                <div class="img-upload-zone-icon">📨</div>
                                <div class="img-upload-zone-label">Upload Employer Update Image</div>
                                <div class="img-upload-zone-sub">1080 × 1350</div>
                            </div>
                            <input type="file" id="file-employer_image" accept="image/*" onchange="handleFileSelect('employer_image',this)" style="display:none">
                            <div class="img-progress" id="progress-employer_image">
                                <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-employer_image"></div></div>
                                <div class="img-progress-label" id="label-employer_image"></div>
                            </div>
                            <div class="img-slot-footer">
                                <a class="img-footer-btn dl" id="dl-employer_image" href="#" download="employer-image" style="display:none">⬇ Download</a>
                                <button class="img-footer-btn" onclick="copySlotPost('employer_image',this)">📋 Post Text</button>
                            </div>
                        </div>
                    </div>
                </div><!-- /img-grid -->

                <div class="card">
                    <div class="section-label">📣 Selling Message</div>
                    <textarea id="employer-pitch-ta" readonly rows="12">{$escapedEmployerPitch}</textarea>
                    <div class="btn-row">
                        <button class="btn btn-purple" onclick="copyField('employer-pitch-ta',this,'📋 Copy Message')">📋 Copy Message</button>
                    </div>
                    {$employerEmailsHtml}
                    {$employerNoContactHtml}
                    {$employerAlreadyPitchedHtml}
                    <div class="btn-row">
                        {$employerEmailBtnHtml}
                        {$employerWhatsappBtnHtml}
                    </div>
                    <div id="employer-send-status" style="margin-top:10px;font-size:12.5px;font-weight:600"></div>
                </div>
            </div>

        </div><!-- /page -->

        <!-- ── Sticky bottom bar ── -->
        <div class="bottom-bar">
            <div class="bottom-bar-inner">
                {$dismissBtnHtml}
                {$jobBtnHtml}
                {$nextBtnHtml}
            </div>
            <div class="bb-close-tip" id="close-tip"></div>
        </div>

        <script>
            const aiPromptText    = {$aiPromptJson};
            const tiktokImageText = {$tiktokImageJson};
            const storyboardText  = {$storyboardJson};
            const geminiText      = {$geminiJson};
            const step2Url        = {$step2UrlJson};
            const uploadUrls      = {$uploadUrlsJson};
            const generateUrls    = {$generateUrlsJson};
            const openAiConfigured = {$openAiConfiguredJson};
            const jobImages       = {$jobImagesJson};
            const companyLogoUrl  = {$companyLogoJson};
            const companyName     = {$companyNameJson};
            const slotPrompts     = {$slotPromptsJson};
            const slotPosts       = {$slotPostsJson};
            const csrfToken       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const whapiSendUrl    = {$whapiSendUrlJson};
            const whapiChannel    = {$whapiChannelJson};
            const publerSendUrl   = {$publerSendUrlJson};
            const sendToEmployerUrl = {$sendToEmployerUrlJson};
            const employerPitchText = {$employerPitchJson};
            const employerEmail    = {$employerEmailJson};
            const employerPhone    = {$employerPhoneJson};

            // ── Init image previews on page load ──
            (function initImages() {
                const slots = {
                    company_logo:   companyLogoUrl,
                    cover_image:    jobImages.cover_image,
                    tiktok_image:   jobImages.tiktok_image,
                    facebook_image: jobImages.facebook_image,
                    linkedin_image: jobImages.linkedin_image,
                    whatsapp_image: jobImages.whatsapp_image,
                    twitter_image:  jobImages.twitter_image,
                    employer_image: jobImages.employer_image,
                };
                for (const [key, url] of Object.entries(slots)) {
                    if (url) showPreview(key, url);
                }

                // No dedicated employer image yet — borrow the WhatsApp tab image as the default.
                if (!jobImages.employer_image && jobImages.whatsapp_image) {
                    showPreview('employer_image', jobImages.whatsapp_image);
                    const dl = document.getElementById('dl-employer_image');
                    if (dl) { dl.style.display = 'none'; }
                    const label = document.querySelector('#slot-employer_image .img-slot-label');
                    if (label) label.textContent = 'Employer Update Image (using WhatsApp image)';
                }
            })();

            function showPreview(key, url) {
                const img  = document.getElementById('img-' + key);
                const prev = document.getElementById('preview-' + key);
                const zone = document.getElementById('zone-' + key);
                const dl   = document.getElementById('dl-' + key);
                if (!img || !prev) return;
                img.src = url;
                prev.style.display = 'block';
                if (zone) zone.style.display = 'none';
                if (dl) { dl.href = url; dl.style.display = ''; dl.style.opacity = '1'; dl.style.pointerEvents = 'auto'; }
                const rp = document.getElementById('repost-' + key);
                if (rp) rp.style.display = ((key === 'whatsapp_image' && whapiSendUrl) || (key === 'tiktok_image' && publerSendUrl)) ? '' : 'none';
                const slot = document.getElementById('slot-' + key);
                if (slot) { const cb = slot.querySelector('.img-copy-btn'); if (cb) cb.style.display = 'none'; }
            }

            function copySlotPost(key, btn) {
                const text = slotPosts[key];
                if (!text) return;
                doCopy(text, btn, '📋 Post Text');
            }

            function triggerUpload(key) {
                document.getElementById('file-' + key)?.click();
            }

            function onDragOver(e, key) {
                e.preventDefault();
                document.getElementById('zone-' + key)?.classList.add('dragging');
            }
            function onDragLeave(key) {
                document.getElementById('zone-' + key)?.classList.remove('dragging');
            }
            function onDrop(e, key) {
                e.preventDefault();
                onDragLeave(key);
                const file = e.dataTransfer?.files?.[0];
                if (file) doUpload(key, file);
            }

            function handleFileSelect(key, input) {
                const file = input.files?.[0];
                if (file) doUpload(key, file);
            }

            function setProgress(key, pct, state, label) {
                const wrap  = document.getElementById('progress-' + key);
                const bar   = document.getElementById('bar-' + key);
                const lbl   = document.getElementById('label-' + key);
                if (!wrap) return;
                wrap.style.display = 'block';
                bar.style.width = pct + '%';
                bar.className = 'img-progress-bar' + (state ? ' ' + state : '');
                lbl.textContent = label;
                lbl.className = 'img-progress-label' + (state ? ' ' + state : '');
            }

            function hideProgress(key) {
                const wrap = document.getElementById('progress-' + key);
                if (wrap) wrap.style.display = 'none';
            }

            function playUploadSound() {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    [880, 1320].forEach((freq, i) => {
                        const osc  = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.value = freq;
                        const start = ctx.currentTime + i * 0.12;
                        gain.gain.setValueAtTime(0.0001, start);
                        gain.gain.exponentialRampToValueAtTime(0.2, start + 0.02);
                        gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.18);
                        osc.connect(gain).connect(ctx.destination);
                        osc.start(start);
                        osc.stop(start + 0.2);
                    });
                } catch {}
            }

            function doUpload(key, file) {
                const url = uploadUrls[key];
                if (!url) { setProgress(key, 100, 'fail', '❌ Upload not available.'); return; }

                const reader = new FileReader();
                reader.onload = e => showPreview(key, e.target.result);
                reader.readAsDataURL(file);

                setProgress(key, 0, '', 'Uploading… 0%');

                const fd = new FormData();
                fd.append('image', file);
                fd.append('type', key);

                const xhr = new XMLHttpRequest();

                xhr.upload.onprogress = function(e) {
                    if (!e.lengthComputable) return;
                    const pct = Math.round((e.loaded / e.total) * 95);
                    setProgress(key, pct, '', 'Uploading… ' + pct + '%');
                };

                xhr.onload = function() {
                    let data = {};
                    try { data = JSON.parse(xhr.responseText); } catch {}
                    if (xhr.status >= 200 && xhr.status < 300 && data.ok !== false) {
                        if (data.url) showPreview(key, data.url);
                        setProgress(key, 100, 'done', '✅ Saved!');
                        if (key === 'whatsapp_image' || key === 'tiktok_image') playUploadSound();
                        setTimeout(() => {
                            hideProgress(key);
                            if (key === 'whatsapp_image' && whapiSendUrl) pkAskSendToChannel();
                            else if (key === 'tiktok_image' && publerSendUrl) pkAskSendToPubler();
                            else location.reload();
                        }, 1200);
                    } else {
                        const msg = data.message || ('Upload failed (' + xhr.status + ')');
                        setProgress(key, 100, 'fail', '❌ ' + msg);
                    }
                };

                xhr.onerror = function() {
                    setProgress(key, 100, 'fail', '❌ Network error — please retry.');
                };

                xhr.open('POST', url);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                xhr.send(fd);
            }

            function switchTab(name, btn) {
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.getElementById('tab-' + name).classList.add('active');
                btn.classList.add('active');
            }

            function pkLoadSwal() {
                return new Promise(resolve => {
                    if (window.Swal) { resolve(); return; }
                    const link = document.createElement('link'); link.rel = 'stylesheet';
                    link.href = '/vendor/core/core/base/libraries/sweetalert2/sweetalert2.min.css';
                    document.head.appendChild(link);
                    const s = document.createElement('script');
                    s.src = '/vendor/core/core/base/libraries/sweetalert2/sweetalert2.min.js';
                    s.onload = resolve; document.head.appendChild(s);
                });
            }
            function pkAskSendToChannel() {
                pkLoadSwal().then(() => {
                    const publerNote = publerSendUrl ? '<br><small style="color:#555;font-size:11px">Also sends to Facebook &amp; LinkedIn via Publer.</small>' : '';
                    Swal.fire({
                        title: 'Send to WhatsApp Channel?',
                        html: 'Image uploaded. Send this job to <strong>' + whapiChannel + '</strong> now?' + publerNote,
                        icon: 'question', showCancelButton: true,
                        confirmButtonColor: '#25D366', cancelButtonColor: '#6b7280',
                        confirmButtonText: '💬 Yes, Send Now', cancelButtonText: 'No, just save', reverseButtons: true,
                    }).then(result => {
                        if (result.isConfirmed) {
                            Swal.fire({ title: 'Sending…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                            const fd = new FormData(); fd.append('_token', csrfToken);
                            fetch(whapiSendUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(r => r.json())
                                .then(d => {
                                    const ok = d.error !== true;
                                    if (publerSendUrl) {
                                        const pfd = new FormData();
                                        pfd.append('_token', csrfToken);
                                        pfd.append('image_field', 'whatsapp_image');
                                        pfd.append('exclude_networks', 'tiktok');
                                        fetch(publerSendUrl, { method: 'POST', body: pfd, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => {});
                                    }
                                    Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? 'Sent!' : 'Failed', text: d.message, timer: 2500, showConfirmButton: false }).then(() => location.reload());
                                })
                                .catch(() => Swal.fire({ icon: 'error', title: 'Network error' }).then(() => location.reload()));
                        } else { location.reload(); }
                    });
                });
            }
            function pkAskSendToPubler() {
                pkLoadSwal().then(() => {
                    Swal.fire({
                        title: 'Post to Publer (TikTok)?',
                        text: 'Image uploaded. Post this job to your connected TikTok account via Publer?',
                        icon: 'question', showCancelButton: true,
                        confirmButtonColor: '#7c3aed', cancelButtonColor: '#6b7280',
                        confirmButtonText: '🎵 Yes, Post Now', cancelButtonText: 'No, just save', reverseButtons: true,
                    }).then(result => {
                        if (result.isConfirmed) {
                            Swal.fire({ title: 'Posting to Publer…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                            const fd = new FormData();
                            fd.append('_token', csrfToken);
                            fd.append('image_field', 'tiktok_image');
                            fd.append('exclude_networks', 'facebook,linkedin,twitter,instagram');
                            fd.append('retry_background', '1');
                            fetch(publerSendUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(r => r.json())
                                .then(d => {
                                    const ok = d.error !== true;
                                    if (ok) {
                                        Swal.fire({ icon: 'success', title: 'Posted!', text: d.message, timer: 2500, showConfirmButton: false }).then(() => location.reload());
                                        return;
                                    }
                                    const detail = d.data && d.data.error_detail ? d.data.error_detail : d.message;
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'TikTok post failed',
                                        text: d.message + '\\n\\n' + detail,
                                        confirmButtonText: 'Copy error',
                                        showCancelButton: true,
                                        cancelButtonText: 'Close',
                                    }).then(result => {
                                        if (result.isConfirmed) {
                                            const copyButton = document.createElement('button');
                                            doCopy(detail, copyButton, 'Copy error');
                                            return;
                                        }
                                        location.reload();
                                    });
                                })
                                .catch(() => Swal.fire({ icon: 'error', title: 'Network error' }).then(() => location.reload()));
                        } else { location.reload(); }
                    });
                });
            }
            function generateImage(key, btn) {
                const url = generateUrls[key];
                if (!url) { setProgress(key, 100, 'fail', '❌ AI generation not available.'); return; }
                if (btn) { btn.disabled = true; }
                setProgress(key, 92, '', '✨ Generating with AI… ~30s');
                const fd = new FormData();
                fd.append('type', key);
                fd.append('_token', csrfToken);
                fetch(url, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                }).then(function (r) {
                    return r.json().then(function (d) { return { ok: r.ok, d: d }; });
                }).then(function (res) {
                    const d = res.d || {};
                    if (res.ok && d.ok !== false && d.url) {
                        showPreview(key, d.url);
                        setProgress(key, 100, 'done', '✅ Generated!');
                        if (key === 'whatsapp_image' || key === 'tiktok_image') playUploadSound();
                        setTimeout(function () {
                            if (key === 'whatsapp_image' && whapiSendUrl) pkAskSendToChannel();
                            else if (key === 'tiktok_image' && publerSendUrl) pkAskSendToPubler();
                            else location.reload();
                        }, 1300);
                    } else {
                        setProgress(key, 100, 'fail', '❌ ' + (d.message || 'Generation failed.'));
                        if (btn) btn.disabled = false;
                    }
                }).catch(function () {
                    setProgress(key, 100, 'fail', '❌ Network error — please retry.');
                    if (btn) btn.disabled = false;
                });
            }

            // Inject "✨ Generate" buttons into each slot footer (only when OpenAI is configured).
            (function injectGenerateButtons() {
                if (!openAiConfigured) return;
                Object.keys(generateUrls).forEach(function (key) {
                    if (!generateUrls[key]) return;
                    const footer = document.querySelector('#slot-' + key + ' .img-slot-footer');
                    if (!footer || footer.querySelector('.img-footer-btn-gen')) return;
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'img-footer-btn img-footer-btn-gen';
                    b.innerHTML = '✨ Generate';
                    b.style.color = '#7c3aed';
                    b.style.borderColor = '#ddd6fe';
                    b.style.background = '#faf5ff';
                    b.addEventListener('click', function () { generateImage(key, b); });
                    footer.insertBefore(b, footer.firstChild);
                });
            })();

            function copySlotPrompt(key, btn) {
                const text = slotPrompts[key];
                if (!text) return;
                doCopy(text, btn, '📋 Copy');
            }
            function copyField(id, btn, resetLabel) {
                doCopy(document.getElementById(id).value, btn, resetLabel);
            }

            function sendToEmployer(channel, btn) {
                const status = document.getElementById('employer-send-status');
                const ta = document.getElementById('employer-pitch-ta');
                const message = ta ? ta.value : employerPitchText;
                const original = btn.textContent;
                btn.disabled = true;
                btn.textContent = '⏳ Sending…';
                if (status) { status.textContent = ''; }

                const fd = new FormData();
                fd.append('channel', channel);
                fd.append('message', message);
                fd.append('_token', csrfToken);

                fetch(sendToEmployerUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        if (status) {
                            status.textContent = data.message || (data.ok ? 'Sent!' : 'Something went wrong.');
                            status.style.color = data.ok ? '#16a34a' : '#dc2626';
                        }
                        btn.textContent = data.ok ? '✅ Sent' : original;
                        btn.disabled = !!data.ok;
                    })
                    .catch(() => {
                        if (status) { status.textContent = 'Network error — please try again.'; status.style.color = '#dc2626'; }
                        btn.textContent = original;
                        btn.disabled = false;
                    });
            }
            {$jsDismissFn}
            function doCopy(text, btn, resetLabel) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(() => showOk(btn, resetLabel)).catch(() => legacyCopy(text, btn, resetLabel));
                } else {
                    legacyCopy(text, btn, resetLabel);
                }
            }
            function legacyCopy(text, btn, resetLabel) {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
                document.body.appendChild(ta);
                ta.focus(); ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                showOk(btn, resetLabel);
            }
            function showOk(btn, resetLabel) {
                btn.textContent = '✅ Copied!';
                btn.classList.add('ok');
                setTimeout(() => { btn.textContent = resetLabel; btn.classList.remove('ok'); }, 2200);
            }
        </script>
        </body>
        </html>
        HTML;
    }

    private function expiredHtml(): string
    {
        $faviconUrl = htmlspecialchars((string) \Botble\Base\Facades\AdminHelper::getAdminFaviconUrl(), ENT_QUOTES, 'UTF-8');
        $faviconType = htmlspecialchars((string) setting('admin_favicon_type', 'image/x-icon'), ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Link Expired</title>
            <link rel="icon shortcut" href="{$faviconUrl}" type="{$faviconType}">
            <meta property="og:image" content="{$faviconUrl}">
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                body{font-family:system-ui,sans-serif;background:#f0f4f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
                .card{background:#fff;border-radius:16px;padding:32px 24px;max-width:480px;width:100%;box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center}
                .icon{font-size:52px;margin-bottom:16px}
                h2{font-size:20px;color:#1a1a2e;margin-bottom:10px}
                p{color:#666;font-size:14px;line-height:1.6}
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon">⏳</div>
                <h2>This link has expired</h2>
                <p>The post text is no longer available — the job may have been deleted or the link has expired.<br><br>Generate a new post from the social automation panel if needed.</p>
            </div>
        </body>
        </html>
        HTML;
    }
}
