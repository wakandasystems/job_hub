<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\JobImageGeneratorService;
use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class SocialPublisherService
{
    private ?string $lastPublerError = null;

    public function publishJob(Job $job): array
    {
        // Silently skip jobs that are not fully approved — no notification sent.
        if (! $job->moderation_status || (string) $job->moderation_status !== \Botble\JobBoard\Enums\ModerationStatusEnum::APPROVED) {
            return [];
        }

        $results = [];

        $automations = SocialAutomation::query()
            ->where('is_active', true)
            ->get();

        $hasCountryMapping = $job->country_id
            && \Botble\JobBoard\Models\PublerCountryMapping::query()
                ->where('country_id', $job->country_id)
                ->where('is_active', true)
                ->exists();

        foreach ($automations as $automation) {
            // Country mappings are the authoritative Publer configuration. Running
            // both paths submits the same job to the same accounts twice.
            if ($automation->platform === 'publer' && $hasCountryMapping) {
                continue;
            }

            try {
                $posted = match ($automation->platform) {
                    'facebook' => $this->postToFacebook($automation, $job),
                    'linkedin' => $this->postToLinkedIn($automation, $job),
                    'whatsapp' => $this->postToWhatsApp($automation, $job),
                    'telegram' => $this->postToTelegram($automation, $job),
                    'whapi'    => $this->postToWhapiChannel($automation, $job),
                    'publer'   => $this->postToPubler($automation, $job),
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

        // Country-mapped Publer posts (one automation per country)
        if ($hasCountryMapping) {
            $this->postToPublerCountryMapping($job, $results);
        }

        return $results;
    }

    /**
     * After a job has been imaged and posted to social channels, send the employer the
     * "your job ad is live" pitch by email — but only for external (crawled) employers,
     * and at most once per employer per calendar day so a single employer posting many
     * jobs in a day does not flood their inbox.
     *
     * Returns ['sent' => bool, 'reason' => string, ...] for logging.
     */
    public function autoPitchEmployerEmail(Job $job, array $publishResults): array
    {
        // 1. Require at least one successful social post.
        $posted = collect($publishResults)->contains(fn ($r) => ($r['success'] ?? false) === true);
        if (! $posted) {
            return ['sent' => false, 'reason' => 'no_social_post'];
        }

        // 2. Only pitch external (crawled) employers. Organic employers posted with us
        //    directly and shouldn't get an unsolicited "activate your account" pitch.
        if ($job->is_organic) {
            return ['sent' => false, 'reason' => 'organic_employer'];
        }

        // 3. Resolve the image to send (employer image first, then WhatsApp image, then
        //    the job image). The pitch email must NEVER go out without an image.
        $imagePath = trim((string) ($job->employer_image ?: $job->whatsapp_image ?: $job->image));
        if ($imagePath === '') {
            return ['sent' => false, 'reason' => 'no_image'];
        }

        $imageUrl = RvMedia::getImageUrl($imagePath);
        if (! $imageUrl) {
            return ['sent' => false, 'reason' => 'no_image'];
        }

        $company = $job->company;
        if (! $company) {
            return ['sent' => false, 'reason' => 'no_company'];
        }

        // 4. Resolve the employer's contact emails (contact_emails first, then email).
        $emails = collect($company->contact_emails ?? [])->filter()->values();
        if ($emails->isEmpty() && $company->email) {
            $emails = collect([$company->email]);
        }
        if ($emails->isEmpty()) {
            return ['sent' => false, 'reason' => 'no_email'];
        }

        // 5. Atomic per-employer, per-day throttle. The UPDATE only affects a row when
        //    this employer has not been pitched today, so concurrent publishes (jobs run
        //    in parallel background processes) can't both win — exactly one email goes out.
        $previousPitchAt = $company->last_employer_pitch_at;

        $claimed = DB::table('jb_companies')
            ->where('id', $company->getKey())
            ->where(function ($query): void {
                $query->whereNull('last_employer_pitch_at')
                    ->orWhere('last_employer_pitch_at', '<', now()->startOfDay());
            })
            ->update(['last_employer_pitch_at' => now()]);

        if (! $claimed) {
            return ['sent' => false, 'reason' => 'already_pitched_today'];
        }

        $to = $emails->first();
        $cc = $emails->slice(1)->values()->all();
        $message = $this->buildEmployerPitchMessage($job);
        $jobUrl  = $this->trackedJobUrl($job, 'employer_email');

        // Local file path (for inline CID embedding) — falls back to the public URL when
        // the file can't be resolved locally (e.g. remote disk).
        $localImagePath = null;
        try {
            if (Storage::disk('public')->exists($imagePath)) {
                $localImagePath = Storage::disk('public')->path($imagePath);
            }
        } catch (\Throwable) {
            // ignore — we'll fall back to the public URL
        }

        try {
            Mail::send([], [], function ($mail) use ($to, $cc, $job, $message, $jobUrl, $imageUrl, $localImagePath): void {
                $mail->to($to)
                    ->subject('Your job ad "' . $job->name . '" is live on Wakanda Jobs! 🚀');

                if ($cc) {
                    $mail->cc($cc);
                }

                // Embed the image inline so it always displays (not blocked like a remote
                // image), with the public URL as a fallback.
                $src = ($localImagePath && is_file($localImagePath))
                    ? $mail->embed($localImagePath)
                    : $imageUrl;

                $mail->html($this->buildEmployerPitchHtml($job, $message, $src, $jobUrl));
            });
        } catch (\Throwable $e) {
            // Send failed (e.g. transient SMTP rate-limit) — release the claim so this
            // employer isn't locked out for the rest of the day with no email actually sent.
            DB::table('jb_companies')
                ->where('id', $company->getKey())
                ->update(['last_employer_pitch_at' => $previousPitchAt]);

            Log::error('Auto employer pitch email failed', [
                'job_id' => $job->getKey(),
                'company_id' => $company->getKey(),
                'error' => $e->getMessage(),
            ]);

            return ['sent' => false, 'reason' => 'send_failed', 'error' => $e->getMessage()];
        }

        return ['sent' => true, 'reason' => 'sent', 'to' => $to, 'cc' => $cc];
    }

    /**
     * Build the HTML body for the employer pitch email: the generated image at the top,
     * the pitch text (with *bold* and links rendered), and a clear job-URL call to action.
     */
    protected function buildEmployerPitchHtml(Job $job, string $message, string $imageSrc, string $jobUrl): string
    {
        $body = e($message);
        $body = preg_replace('/\*(.+?)\*/s', '<strong>$1</strong>', $body);
        $body = preg_replace('~(https?://[^\s]+)~', '<a href="$1" style="color:#7c3aed">$1</a>', $body);
        $body = nl2br($body);

        return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.55;color:#222;max-width:600px;margin:0 auto;padding:8px">'
            . '<img src="' . $imageSrc . '" alt="' . e($job->name) . '" '
            . 'style="display:block;width:100%;max-width:600px;height:auto;border-radius:10px;margin-bottom:18px">'
            . '<div>' . $body . '</div>'
            . '<div style="margin:24px 0 8px">'
            . '<a href="' . e($jobUrl) . '" '
            . 'style="background:#7c3aed;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;display:inline-block;font-weight:bold">'
            . 'View the job ad &rarr;</a></div>'
            . '</div>';
    }

    public function trackedJobUrl(Job $job, string $source): string
    {
        $url = route('public.job', $job->slugable?->key ?? $job->id);

        return $url . '?' . http_build_query([
            'utm_source' => Str::lower(trim($source)),
            'utm_medium' => 'social',
            'utm_campaign' => 'job_alerts',
            'utm_content' => 'job_' . $job->getKey(),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    protected function buildJobMessage(Job $job, string $source = 'social'): string
    {
        $excerpt = Str::limit(strip_tags((string) $job->description), 280);
        $url     = $this->trackedJobUrl($job, $source);
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
        $country  = trim((string) ($job->country?->name ?? 'Zambia'));

        // Company logo URL — include only when the company has an actual uploaded logo
        $companyLogoUrl = null;
        if ($job->company && ! empty($job->company->logo)) {
            try {
                $companyLogoUrl = RvMedia::getImageUrl($job->company->logo);
            } catch (Throwable) {}
        }

        // Map country name to its flag colors for a subtle design accent
        $flagColors = $this->getFlagColors($country);

        // Build a details line to embed in the image
        $details = [];
        if ($company) {
            $details[] = "Company: {$company}";
        }
        $details[] = "Location: {$location}";

        try {
            $salary = $this->nativeSalaryText($job);
            if ($salary) {
                $details[] = "Salary: {$salary}";
            }
        } catch (Throwable) {}

        if ($deadline) {
            $details[] = "Deadline: " . $deadline->format('M j, Y');
        }

        $jobTypes = $job->jobTypes->pluck('name')->filter()->implode(' / ');
        if ($jobTypes) {
            $details[] = "Type: {$jobTypes}";
        }

        $applyEmail = $this->extractApplyEmail($job);

        $detailsText = implode(' | ', $details);

        $prompt  = "Generate a professional job advertisement image for Wakanda Jobs (wakandajobs.com) — an African job platform.";
        $prompt .= " The job being advertised is: {$title}";
        if ($company) {
            $prompt .= " at {$company}";
        }
        $prompt .= ".";

        $prompt .= " Make the image ultra-realistic, professional, and trustworthy — like a Fortune 500 recruitment ad.";

        // Company logo — colours, placement, and branding
        if ($companyLogoUrl) {
            $prompt .= " " . $this->companyLogoLine($company, $companyLogoUrl);
            $prompt .= " (1) Place the real logo in the top-right corner.";
            $prompt .= " (2) Dress the Black African professionals in clothing that incorporates or complements the logo's brand colours, so the people feel part of the company's visual identity.";
            $prompt .= " (3) Any decorative banners, overlays, or graphic shapes should also use the company logo's colours, making the whole composition feel like a branded advertisement for '{$company}'.";
        } else {
            if ($company) {
                $prompt .= " There is no company logo available for '{$company}', so render a clean professional text badge or monogram for the company name in the top-right corner.";
            }
            $prompt .= " Use the Wakanda Jobs purple/violet colour palette as the primary design theme.";
        }

        $prompt .= " Feature Black African professionals dressed appropriately for the role in the scene.";
        $prompt .= $this->sceneDirective($job);
        $prompt .= " " . $this->wakandaLogoLine();
        if ($companyLogoUrl) {
            $prompt .= " The Wakanda Jobs logo should be smaller and secondary to the company branding — this image should feel like a '{$company}' ad first.";
        }

        // Flag accent
        if ($flagColors) {
            $prompt .= " Add a subtle, tasteful country flag accent for {$country}: a thin horizontal band at the very bottom of the image using the flag colors {$flagColors}.";
            $prompt .= " The flag colors should be understated — a quiet nod to the country, not a loud centerpiece.";
        }

        $prompt .= " The image must clearly display the following text overlay: Job Title: {$title}";
        if ($company) {
            $prompt .= " | Company: {$company}";
        }
        $prompt .= " | {$detailsText}.";
        if ($applyEmail) {
            $prompt .= " Also display the apply email prominently on the image: 'Apply: {$applyEmail}'.";
        }
        $prompt .= " Add a 'Apply Now at wakandajobs.com' call-to-action at the bottom.";
        $prompt .= " Overall feel: modern, clean, corporate, inspiring confidence.";
        $prompt .= " IMPORTANT — IMAGE DIMENSIONS: Generate this image at exactly 1080 × 1920 pixels, 9:16 portrait aspect ratio, optimised for TikTok, Instagram Stories, Facebook Stories, and WhatsApp Status. Do NOT generate a landscape or square image.";

        return $prompt;
    }

    /**
     * TikTok photo posts require a 'title' (max 90 chars) — Publer rejects
     * the post without it. Build one from the job title + company.
     */
    protected function buildTikTokPostTitle(Job $job): string
    {
        $title   = trim((string) $job->name);
        $company = trim((string) ($job->company?->name ?? ''));

        $full = $company ? "{$title} @ {$company}" : $title;

        return Str::limit($full, 90, '');
    }

    /**
     * Salary string in the JOB'S OWN currency — unlike the `salary_text` model
     * accessor (which runs amounts through format_price() and converts them to
     * the application's active display currency), social posts must always show
     * the salary the way the employer listed it, e.g. a Uganda job in UGX rather
     * than the platform default (ZMW).
     */
    protected function nativeSalaryText(Job $job): ?string
    {
        if ($job->hide_salary) {
            return null;
        }

        $salaryType = $job->salary_type ?? \Botble\JobBoard\Enums\SalaryTypeEnum::FIXED;

        if ((string) $salaryType !== (string) \Botble\JobBoard\Enums\SalaryTypeEnum::FIXED) {
            return null;
        }

        $from = (float) $job->salary_from;
        $to   = (float) $job->salary_to;

        if (! $from && ! $to) {
            return null;
        }

        $currency = $job->currency && $job->currency->getKey() ? $job->currency : get_application_currency();
        $range    = strtolower($job->salary_range->label());

        if ($from && $to) {
            return trans('plugins/job-board::messages.salary_range_format', [
                'from'  => $this->formatNativePrice($from, $currency),
                'to'    => $this->formatNativePrice($to, $currency),
                'range' => $range,
            ]);
        }

        if ($from) {
            return trans('plugins/job-board::messages.salary_from_format', [
                'price' => $this->formatNativePrice($from, $currency),
                'range' => $range,
            ]);
        }

        return trans('plugins/job-board::messages.salary_upto_format', [
            'price' => $this->formatNativePrice($to, $currency),
            'range' => $range,
        ]);
    }

    /**
     * Mirrors format_price()'s symbol-formatting branch without its currency
     * conversion step — formats the amount using the given currency as-is.
     */
    private function formatNativePrice(float $amount, $currency): string
    {
        $space = setting('job_board_add_space_between_price_and_currency', 0) == 1 ? ' ' : null;

        if ($currency->is_prefix_symbol) {
            return $currency->symbol . $space . human_price_text($amount, $currency);
        }

        return human_price_text($amount, $currency, $currency->symbol);
    }

    public function buildTikTokImagePrompt(Job $job): string
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: 'Zambia'));
        $deadline = $job->application_closing_date ?: $job->expire_date;
        $country  = trim((string) ($job->country?->name ?? 'Zambia'));

        $companyLogoUrl = null;
        if ($job->company && ! empty($job->company->logo)) {
            try {
                $companyLogoUrl = RvMedia::getImageUrl($job->company->logo);
            } catch (Throwable) {}
        }

        $flagColors = $this->getFlagColors($country);

        $details = [];
        if ($company) {
            $details[] = "Company: {$company}";
        }
        $details[] = "Location: {$location}";

        try {
            $salary = $this->nativeSalaryText($job);
            if ($salary) {
                $details[] = "Salary: {$salary}";
            }
        } catch (Throwable) {}

        if ($deadline) {
            $details[] = "Deadline: " . $deadline->format('M j, Y');
        }

        $jobTypes = $job->jobTypes->pluck('name')->filter()->implode(' / ');
        if ($jobTypes) {
            $details[] = "Type: {$jobTypes}";
        }

        $applyEmail = $this->extractApplyEmail($job);

        $detailsText = implode(' | ', $details);

        $prompt  = "Generate a professional TikTok job advertisement image for Wakanda Jobs — an African job platform.";
        $prompt .= " The job being advertised is: {$title}";
        if ($company) {
            $prompt .= " at {$company}";
        }
        $prompt .= ".";

        $prompt .= " Make the image ultra-realistic, professional, and trustworthy — like a Fortune 500 recruitment ad.";

        if ($companyLogoUrl) {
            $prompt .= " " . $this->companyLogoLine($company, $companyLogoUrl);
            $prompt .= " (1) Place the real logo in the top-right corner.";
            $prompt .= " (2) Dress the Black African professionals in clothing that incorporates or complements the logo's brand colours.";
            $prompt .= " (3) Any decorative banners, overlays, or graphic shapes should use the company logo's colours, making the composition feel like a branded advertisement for '{$company}'.";
        } else {
            if ($company) {
                $prompt .= " There is no company logo available for '{$company}', so render a clean professional text badge or monogram for the company name in the top-right corner.";
            }
            $prompt .= " Use the Wakanda Jobs purple/violet colour palette as the primary design theme.";
        }

        $prompt .= " Feature Black African professionals dressed appropriately for the role in the scene.";
        $prompt .= $this->sceneDirective($job);
        $prompt .= " " . $this->wakandaLogoLine();
        if ($companyLogoUrl) {
            $prompt .= " The Wakanda Jobs logo should be smaller and secondary to the company branding — this image should feel like a '{$company}' ad first.";
        }

        if ($flagColors) {
            $prompt .= " Add a subtle, tasteful country flag accent for {$country}: a thin horizontal band at the very bottom of the image using the flag colors {$flagColors}.";
            $prompt .= " The flag colors should be understated — a quiet nod to the country, not a loud centerpiece.";
        }

        $prompt .= " The image must clearly display the following text overlay: Job Title: {$title}";
        if ($company) {
            $prompt .= " | Company: {$company}";
        }
        $prompt .= " | {$detailsText}.";
        if ($applyEmail) {
            $prompt .= " Also display the apply email prominently on the image: 'Apply: {$applyEmail}'.";
        }
        $prompt .= " IMPORTANT — TikTok CTA: Place a high-visibility 'LINK IN BIO TO APPLY 👆' call-to-action banner in the UPPER THIRD of the image (approximately 15–30% from the top) — NOT at the bottom, where TikTok's UI chrome (username, buttons, caption) will cover it. Style it as a wide full-bleed banner or pill with maximum contrast: large bold white text on a solid black or deep-purple background (or vice versa). The text must be at least 80px tall and clearly legible at a glance on a phone screen. Do NOT include any website URL (e.g. wakandajobs.com) anywhere in the image — TikTok description links are not clickable, so the only apply instruction must be the bio-link CTA banner.";
        $prompt .= " Overall feel: modern, clean, corporate, inspiring confidence.";
        $prompt .= " IMPORTANT — IMAGE DIMENSIONS: Generate this image at exactly 1080 × 1920 pixels, 9:16 portrait aspect ratio, optimised for TikTok. Do NOT generate a landscape or square image.";

        return $prompt;
    }

    public function buildCoverImagePrompt(Job $job): string
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: ''));
        $country  = trim((string) ($job->country?->name ?? ''));
        $deadline = $job->application_closing_date ?: $job->expire_date;

        $companyLogoUrl = null;
        if ($job->company && ! empty($job->company->logo)) {
            try { $companyLogoUrl = RvMedia::getImageUrl($job->company->logo); } catch (Throwable) {}
        }

        $details = [];
        if ($company)  $details[] = $company;
        if ($location) $details[] = $location;
        if ($deadline) $details[] = 'Apply by ' . $deadline->format('M j, Y');

        try {
            $salary = $this->nativeSalaryText($job);
            if ($salary) {
                $details[] = $salary;
            }
        } catch (Throwable) {}

        $flagColors = $this->getFlagColors($country);

        $p  = "Generate a professional, high-quality landscape cover/banner image for a job listing page on Wakanda Jobs (wakandajobs.com) — an African job platform.";
        $p .= "\n\n";
        $p .= "═══ JOB DETAILS ═══\n";
        $p .= "Job Title: {$title}\n";
        if ($company)  $p .= "Company: {$company}\n";
        if ($location) $p .= "Location: {$location}\n";
        if ($deadline) $p .= "Deadline: " . $deadline->format('M j, Y') . "\n";
        $p .= "\n";

        $p .= "═══ DESIGN SPECIFICATIONS ═══\n";
        $p .= "• DIMENSIONS: Exactly 1800 × 540 pixels — a wide 10:3 landscape banner matching the desktop job-detail cover area. This will be used as a page-width header image on a job listing page.\n";
        $p .= "• LAYOUT: Use a stylish editorial composition with generous whitespace. Keep the text in a clean left-side content area, feature the professionals naturally across the centre, and place the company logo in a polished right-side brand card. Use soft curves, layered translucent shapes, and restrained colour accents for depth.\n";
        $p .= "• FEEL: Bright, fresh, premium, and welcoming — like a modern African recruitment campaign or polished business magazine cover. Use natural daylight, warm skin tones, crisp detail, and an optimistic atmosphere.\n";
        $p .= "\n";

        $p .= "═══ CONTENT REQUIREMENTS ═══\n";
        $p .= "• Show Black African professionals in a realistic work environment suitable for the role '{$title}'. The people should look confident, aspirational, and engaged.\n";
        $p .= $this->sceneDirective($job, 'bullet');
        $p .= "• LEFT-SIDE TEXT OVERLAYS: Place text on a light cream, soft-white, or very pale tinted panel with strong contrast. Use deep charcoal or rich brand-colour text, not white text on a dark background.\n";
        $p .= "    — Large bold heading: \"{$title}\"\n";
        if ($company) $p .= "    — Subheading: \"at {$company}\"\n";
        if ($details) $p .= "    — Detail row: \"" . implode('  ·  ', $details) . "\"\n";
        $p .= "    — Small CTA strip at bottom: \"Apply at wakandajobs.com\"\n";
        $p .= "\n";

        if ($companyLogoUrl) {
            $p .= "═══ COMPANY LOGO BRANDING ═══\n";
            $p .= $this->companyLogoLine($company, $companyLogoUrl) . "\n";
            $p .= "• Place the real attached logo prominently in the right-side brand card — centred on a clean white or softly tinted surface with a subtle shadow.\n";
            $p .= "• Extract the dominant colours from the attached logo and use them as tasteful accents throughout the image (soft gradients, curved shapes, highlights, and the CTA), while keeping the overall design light and airy.\n";
            $p .= "\n";
        } else {
            if ($company) {
                $p .= "No logo is available for '{$company}'. In the right panel, render a clean typographic badge or monogram with the company initials in a rounded rectangle — use the Wakanda Jobs violet palette.\n\n";
            }
        }

        $p .= "═══ WAKANDA JOBS BRANDING ═══\n";
        $p .= $this->wakandaLogoLine() . "\n";
        $p .= "• Place the Wakanda Jobs logo small in the bottom-left corner or as a watermark.\n";
        $p .= "• Background palette: warm white (#fffdf8), soft lavender (#f3efff), and pale lilac (#e9e1ff), with plenty of bright negative space.\n";
        $p .= "• Accent: Wakanda violet (#7c3aed), a small touch of warm gold (#f4b942), and deep charcoal (#20202a) for readable text. Keep saturated colours controlled and elegant.\n";
        $p .= "\n";

        if ($flagColors) {
            $p .= "═══ COUNTRY ACCENT ═══\n";
            $p .= "• Add a very subtle {$country} flag-colour strip (colours: {$flagColors}) as a thin bottom border — tasteful, not dominant.\n\n";
        }

        $p .= "═══ WHAT NOT TO DO ═══\n";
        $p .= "• Do NOT generate a portrait or square image — it must be a wide 1800×540 landscape banner with a 10:3 aspect ratio.\n";
        $p .= "• Do NOT make the image look like a social media story — it is a webpage header/banner.\n";
        $p .= "• Do NOT use stock-photo clip-art style. Aim for authentic photorealistic quality.\n";
        $p .= "• Do NOT crowd the image with text — keep text minimal, large, and legible.\n";
        $p .= "• Do NOT use dark-mode styling, black or near-black backgrounds, heavy shadows, neon lighting, night scenes, moody cinematic grading, or gloomy purple washes.\n";
        $p .= "• Do NOT use a rigid corporate template. The result should feel bespoke, stylish, bright, and human.\n";

        return $p;
    }

    public function buildFacebookImagePrompt(Job $job): string
    {
        return $this->buildLandscapeSocialPrompt($job, 'Facebook', 1200, 630,
            'Facebook news feed post — wide landscape image that stops the scroll.');
    }

    public function buildLinkedInImagePrompt(Job $job): string
    {
        return $this->buildLandscapeSocialPrompt($job, 'LinkedIn', 1200, 627,
            'LinkedIn post image — professional, corporate landscape format for the LinkedIn feed.');
    }

    public function buildTwitterImagePrompt(Job $job): string
    {
        return $this->buildLandscapeSocialPrompt($job, 'X / Twitter', 1200, 675,
            'X (Twitter) in-stream post image — bold 16:9 landscape format that stands out in the timeline.');
    }

    protected function buildLandscapeSocialPrompt(Job $job, string $platform, int $width, int $height, string $platformContext): string
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: ''));
        $country  = trim((string) ($job->country?->name ?? ''));
        $deadline = $job->application_closing_date ?: $job->expire_date;

        $companyLogoUrl = null;
        if ($job->company && ! empty($job->company->logo)) {
            try { $companyLogoUrl = RvMedia::getImageUrl($job->company->logo); } catch (Throwable) {}
        }

        $details = [];
        if ($company)  $details[] = $company;
        if ($location) $details[] = $location;
        if ($deadline) $details[] = 'Apply by ' . $deadline->format('M j, Y');
        try {
            $salary = $this->nativeSalaryText($job);
            if ($salary) {
                $details[] = $salary;
            }
        } catch (Throwable) {}

        $flagColors = $this->getFlagColors($country);

        $p  = "Generate a professional job advertisement image for Wakanda Jobs (wakandajobs.com) — {$platformContext}\n\n";
        $p .= "═══ JOB DETAILS ═══\n";
        $p .= "Job Title: {$title}\n";
        if ($company)  $p .= "Company: {$company}\n";
        if ($location) $p .= "Location: {$location}\n";
        if ($deadline) $p .= "Deadline: " . $deadline->format('M j, Y') . "\n\n";

        $p .= "═══ DESIGN SPECIFICATIONS ═══\n";
        $p .= "• DIMENSIONS: Exactly {$width} × {$height} pixels — landscape format for {$platform}. Do NOT generate portrait or square.\n";
        $p .= "• LAYOUT: Two-zone horizontal split. Left ~55% has the headline text and professionals. Right ~45% has the company logo and brand colours on a darker panel.\n";
        $p .= "• FEEL: Ultra-realistic, polished corporate photography. Clean modern typography. Confident and inspiring.\n\n";

        $p .= "═══ CONTENT ═══\n";
        $p .= "• Show Black African professionals in a work environment suited to '{$title}'. Confident, aspirational, engaged.\n";
        $p .= $this->sceneDirective($job, 'bullet');
        $p .= "• TEXT OVERLAYS (white or lavender on dark background):\n";
        $p .= "    — Large bold heading: \"{$title}\"\n";
        if ($company) $p .= "    — Subheading: \"at {$company}\"\n";
        if ($details) $p .= "    — Details: \"" . implode('  ·  ', $details) . "\"\n";
        $p .= "    — CTA: \"Apply at wakandajobs.com\"\n\n";

        if ($companyLogoUrl) {
            $p .= "═══ COMPANY LOGO BRANDING ═══\n";
            $p .= $this->companyLogoLine($company, $companyLogoUrl) . "\n";
            $p .= "• Place the real attached logo prominently in the right panel.\n";
            $p .= "• Extract dominant colours from the attached logo and use them as accents across the entire image.\n\n";
        } elseif ($company) {
            $p .= "No logo available for '{$company}'. Render a clean typographic badge/monogram in the right panel using the Wakanda Jobs violet palette.\n\n";
        }

        $p .= "═══ WAKANDA JOBS BRANDING ═══\n";
        $p .= $this->wakandaLogoLine() . "\n";
        $p .= "• Background: deep dark purple (#1a0533 → #0d0219) with violet (#7c3aed) accents.\n";
        $p .= "• Wakanda Jobs logo small in the bottom-left corner.\n\n";

        if ($flagColors) {
            $p .= "• Subtle {$country} flag colours ({$flagColors}) as a thin bottom border accent.\n\n";
        }

        $p .= "═══ WHAT NOT TO DO ═══\n";
        $p .= "• Do NOT generate portrait or square — must be landscape {$width}×{$height}.\n";
        $p .= "• Do NOT use clip-art or stock-photo style. Aim for photorealistic quality.\n";
        $p .= "• Keep text minimal, large, and legible — no text walls.\n";

        return $p;
    }

    public function buildStoryboardPrompt(Job $job): string
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: ''));
        $country  = trim((string) ($job->country?->name ?? ''));
        $deadline = $job->application_closing_date ?: $job->expire_date;
        $deadlineStr = $deadline ? $deadline->format('M j, Y') : '';
        $url      = route('public.job', $job->slugable?->key ?? $job->id);

        $salaryLine = '';
        try {
            $s = $this->nativeSalaryText($job);
            if ($s) {
                $salaryLine = "Salary: {$s}";
            }
        } catch (\Throwable) {}

        $excerpt = trim(\Illuminate\Support\Str::limit(strip_tags(mb_convert_encoding((string) ($job->description ?: $job->content), 'UTF-8', 'UTF-8')), 300));

        $companyLine  = $company  ? "Company  : {$company}" : '';
        $locationLine = $location ? "Location : {$location}" : '';
        $salaryBlock  = $salaryLine ? "{$salaryLine}" : '';
        $deadlineBlock = $deadlineStr ? "Deadline : {$deadlineStr}" : '';
        $salaryOverlay = $salaryLine ? "\n  Text overlay: \"{$salaryLine}\"" : '';
        $deadlineOverlay = $deadlineStr ? "\n  Text overlay: \"Apply before {$deadlineStr}\"" : '';
        $flagAccent   = $country ? "  Bottom accent: {$country} flag colour stripe" : '';
        $companyAt    = $company ? " at {$company}" : '';
        $locationOf   = $location ? " | {$location}" : '';

        // Extract up to 2 benefit bullets from description
        $benefits = [];
        if ($excerpt) {
            $sentences = preg_split('/[.•\n]+/', $excerpt);
            foreach ($sentences as $s) {
                $s = trim($s);
                if (strlen($s) > 15 && strlen($s) < 80) {
                    $benefits[] = $s;
                    if (count($benefits) >= 2) break;
                }
            }
        }
        $benefit1 = $benefits[0] ?? "Join a leading {$country} employer";
        $benefit2 = $benefits[1] ?? "Competitive package + growth opportunity";

        [$hookText, $hookSub] = $this->getVideoHook($job);

        return <<<PROMPT
Create a 4-frame visual storyboard for a 10-second TikTok / Instagram Reels job advertisement.

━━━ JOB DETAILS ━━━
Position : {$title}{$companyAt}
{$locationLine}
{$salaryBlock}
{$deadlineBlock}
Apply at : {$url}

━━━ CANVAS SPECS ━━━
Each frame : 1080 × 1920 px  (9:16 portrait — TikTok / Stories format)
Style      : Ultra-realistic, vibrant, professional — Fortune 500 African recruitment ad
People     : Black African professionals dressed appropriately for the role
Branding   : Wakanda Jobs logo in top-left corner of every frame — fetch from https://www.wakandajobs.com/storage/gemini-generated-image-s1e9dgs1e9dgs1e9.png or https://www.wakandajobs.com/storage/chatgpt-image-may-14-2026-03-00-04-pm.png

━━━ STORYBOARD FRAMES ━━━

FRAME 1 — THE HOOK (0 – 2 s)
  Scene: A young Black African professional stares at their phone with a bored/frustrated expression, then suddenly looks up with wide excited eyes.
  Background: Blurred urban street or office lobby — authentic African city energy.
  Text overlay (large, bold white with drop shadow): "{$hookText}"
  Sub-text: "{$hookSub}"
  Camera feel: Slightly handheld, street-photography realism.

FRAME 2 — THE OPPORTUNITY (2 – 5 s)
  Scene: Same professional standing confidently in a modern workplace relevant to the {$title} role. Wide smile, power pose.
  Text overlay (huge, centred, bold): "{$title}"{$salaryOverlay}
  Sub-text: "{$company}{$locationOf}"
  {$flagAccent}
  Camera feel: Slow push-in towards the subject, creating a sense of momentum.

FRAME 3 — THE DETAILS (5 – 8 s)
  Scene: Close-up on the professional's face — focused, determined. Colleagues visible and collaborating behind them.
  Text overlays (stacked list, bold):
    ✅ {$benefit1}
    ✅ {$benefit2}{$deadlineOverlay}
  Bottom tagline: "This is YOUR moment."
  Camera feel: Rack focus from blurred background to sharp face.

FRAME 4 — CALL TO ACTION (8 – 10 s)
  Scene: The professional holds up their phone showing wakandajobs.com, points directly at the camera with a huge confident smile.
  Text overlay (bold, urgent, centred): "APPLY NOW! 🚀"
  Sub-text: "wakandajobs.com"
  Bottom: Wakanda Jobs logo + "Find your next opportunity in {$country}"
  Camera feel: Wide shot → quick zoom to face on final beat.

━━━ OUTPUT INSTRUCTIONS ━━━
• Generate all 4 frames as SEPARATE images, clearly labelled Frame 1 through Frame 4.
• Every image must be exactly 1080 × 1920 px (9:16 portrait). No landscape. No square.
• Keep the same talent, outfit, and colour palette across all 4 frames for visual continuity.
• After generating, I will paste all 4 images into Gemini to animate into a 10-second video.
PROMPT;
    }

    public function buildGeminiVideoPrompt(Job $job): string
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: ''));
        $country  = trim((string) ($job->country?->name ?? ''));
        $deadline = $job->application_closing_date ?: $job->expire_date;
        $deadlineStr = $deadline ? $deadline->format('M j, Y') : '';
        $url      = route('public.job', $job->slugable?->key ?? $job->id);

        $companyAt   = $company  ? " at {$company}" : '';
        $locationOf  = $location ? " | {$location}" : '';
        $jobTitleCTA = "{$title}{$companyAt}";

        [$hookText, $hookSub] = $this->getVideoHook($job);

        return <<<PROMPT
━━━ MISSION ━━━
Turn the 4 attached storyboard frames into a single 10-second scroll-stopping video ad
for TikTok, Instagram Reels, and WhatsApp Status — targeting African job seekers aged 18–40.

━━━ JOB CONTEXT ━━━
Position : {$title}{$companyAt}
Location : {$location}
Country  : {$country}
Apply at : {$url}

━━━ WAKANDA JOBS LOGO USAGE ━━━
Fetch the Wakanda Jobs logo PNG from: https://www.wakandajobs.com/storage/gemini-generated-image-s1e9dgs1e9dgs1e9.png
(alternate: https://www.wakandajobs.com/storage/chatgpt-image-may-14-2026-03-00-04-pm.png). Use it as:
  • Persistent watermark — top-left corner of EVERY frame, ~15% screen width, 80% opacity.
  • CTA feature — in Frame 4 only: animate logo from watermark size → 30% width centred,
    0.4 s ease-in, then pulse gently in sync with the "APPLY NOW!" heartbeat.
  • Add a frosted-dark pill behind the logo so it reads clearly on any background.
  • Never distort, recolour, or crop the logo.

━━━ VIDEO SPECIFICATIONS ━━━
Duration   : 10 seconds exactly — do NOT exceed
Dimensions : 1080 × 1920 px (9:16 portrait — TikTok / Reels / Stories)
Frame rate : 30 fps  |  Codec : H.264 MP4
Colour     : Warm, vibrant, punchy — think golden-hour African light, not washed-out studio

━━━ FRAME TIMING & ANIMATION ━━━

FRAME 1  (0.0 – 2.0 s)  ▸ THE HOOK — Stop the Scroll
  Motion : Sharp 1.15× zoom burst on subject's face (0.1 s), then slow pull-back
  Text   : "{$hookText}" — slams in from left, bold white, drop shadow, micro-overshoot bounce
  Sub    : "{$hookSub}" — fades up softly, 0.4 s after hook text
  Feel   : Handheld camera energy (±3 px shake), real street or office authenticity

FRAME 2  (2.0 – 5.0 s)  ▸ THE OPPORTUNITY — Land the Job
  Cut    : Smash cut (0 frames, instant) — no fade, no dissolve
  Text   : "{$jobTitleCTA}" — drops from top with impact flash, bounces (1.3× → 1.0×, 0.25 s)
  Detail : Company & location lines fade in staggered 0.25 s apart
  Motion : Slow push-in — camera drifts 5% closer over the full 3 s
  Bonus  : Salary (if any) pops in from right with a 💰 emoji flash

FRAME 3  (5.0 – 8.0 s)  ▸ THE PROOF — Build Desire
  Transition : Horizontal swipe wipe from Frame 2 (left → right, 0.18 s)
  Bullets    : ✅ benefit lines pop in one-by-one (0 → 100% scale, 0.2 s each, 0.3 s stagger)
  Sound cue  : Satisfying soft 'tick' on each ✅ entry
  Tagline    : "This is YOUR moment." — italic, warm amber/gold, fades in at 7.5 s
  Focus      : Rack-focus rack: background progressively blurs while face sharpens throughout

FRAME 4  (8.0 – 10.0 s)  ▸ CALL TO ACTION — Seal It
  Cut     : 1-frame white flash (hard cut with flash)
  Logo    : Wakanda Jobs logo scales from top-left watermark → 30% centred (0.4 s ease-in-out)
  CTA     : "APPLY NOW! 🚀" slams in below logo — heartbeat pulse every 0.5 s (1.0 → 1.08 → 1.0)
  URL     : "wakandajobs.com" types out character-by-character (typewriter, 0.06 s/char)
  Finale  : At 9.5 s — freeze frame + bright vignette flash + gold confetti burst
  Hold    : Last frame held 0.5 s

━━━ TRANSITIONS ━━━
F1 → F2 : Smash cut (instant, 0 frames)
F2 → F3 : Horizontal swipe wipe (0.18 s)
F3 → F4 : 1-frame white flash cut

━━━ AUDIO DIRECTION ━━━
Music BG : Amapiano log-drum groove or Afrobeats guitar loop — 122–126 BPM, no vocals, no lyrics.
           Energy builds gently from Frame 1 through Frame 3, peaks at Frame 4's CTA.

Voiceover : Male or female Zambian English accent — warm, confident, clear diction.
            Tone: enthusiastic but professional, like a trusted friend sharing exciting news.
            Delivery: punchy phrases timed to each frame cut, not rushed.
            IMPORTANT — LIP SYNC: The on-screen character must visibly talk (mouth moving,
            realistic lip-sync) in sync with the voiceover throughout the entire video.
            Their facial expressions should match the energy of each line — excited in Frame 1,
            proud in Frame 2, focused in Frame 3, pointing/gesturing in Frame 4.
            Sample script (adapt to fit):
              Frame 1 → "Aye! Looking for a job? Listen up!"
              Frame 2 → "{$title}! This is the one."
              Frame 3 → "Good salary. Great opportunity. Apply before it's gone."
              Frame 4 → "Click the link — your next chapter starts today!"

Sound FX  :
  Frame 1 → notification 'ping' the instant hook text appears
  Frame 2 → deep cinematic bass thud on the job title impact
  Frame 3 → crisp 'tick' on each ✅ bullet pop
  Frame 4 → rising riser build → punchy drum hit on "APPLY NOW" → bright 'ding' on confetti

Outro     : Music and SFX fade out in final 0.4 s — clean, professional end.

━━━ OUTPUT ━━━
• Single MP4, 1080 × 1920, 30 fps, H.264
• Total duration: 10.0 s exactly
• All text must be legible on a mobile screen at arm's length
• Wakanda Jobs watermark visible in every single frame
PROMPT;
    }

    /**
     * Pick the social platforms we'll pitch to the employer, based on the
     * type of role being filled.
     */
    public function employerMarketingPlatforms(Job $job): array
    {
        $jobTypes   = $job->jobTypes->pluck('name')->filter()->map(fn ($n) => strtolower($n))->implode(' ');
        $categories = $job->categories->pluck('name')->filter()->map(fn ($n) => strtolower($n))->implode(' ');
        $text       = $jobTypes . ' ' . $categories . ' ' . strtolower((string) $job->name);

        $platforms = ['WhatsApp Channels', 'Facebook'];

        $tiktokKeywords  = ['retail', 'hospitality', 'sales', 'customer', 'driver', 'creative', 'marketing', 'social media', 'content', 'entry level', 'waiter', 'chef', 'beauty', 'fashion'];
        $linkedinKeywords = ['manager', 'engineer', 'developer', 'accountant', 'finance', 'executive', 'director', 'analyst', 'professional', 'specialist', 'officer', 'administrator', 'consultant', 'lead', 'head of'];

        $addTikTok   = Str::contains($text, $tiktokKeywords);
        $addLinkedIn = Str::contains($text, $linkedinKeywords);

        // For general roles that don't clearly match either bucket, pitch both.
        if (! $addTikTok && ! $addLinkedIn) {
            $addTikTok = $addLinkedIn = true;
        }

        if ($addLinkedIn) {
            $platforms[] = 'LinkedIn';
        }
        if ($addTikTok) {
            $platforms[] = 'TikTok';
        }

        return array_values(array_unique($platforms));
    }

    /**
     * AI image prompt for a "your job is now live" marketing graphic sent to the employer.
     */
    public function buildEmployerImagePrompt(Job $job): string
    {
        $title   = trim((string) $job->name);
        $company = trim((string) ($job->company?->name ?? ''));

        $companyLogoUrl = null;
        if ($job->company && ! empty($job->company->logo)) {
            try {
                $companyLogoUrl = RvMedia::getImageUrl($job->company->logo);
            } catch (Throwable) {}
        }

        $platforms = $this->employerMarketingPlatforms($job);
        $platformsText = implode(', ', $platforms);

        $prompt  = "Generate a polished, professional 'Your Job Ad Is Live!' marketing graphic that Wakanda Jobs (wakandajobs.com) sends to an employer client to show off the marketing campaign for their job posting.";
        $prompt .= " The role being advertised is: {$title}";
        if ($company) {
            $prompt .= " at {$company}";
        }
        $prompt .= ".";

        $prompt .= " DIMENSIONS: 1080 x 1350 pixels (4:5 portrait), suitable for sharing over WhatsApp and email.";
        $prompt .= " STYLE: Clean, premium, corporate-confidence design using the Wakanda Jobs purple/violet brand palette, with modern gradients, soft shapes, and generous whitespace — should feel like a results/marketing report a client would be proud to receive.";

        $prompt .= " HEADLINE TEXT OVERLAY: \"Your Job Ad Is LIVE! 🚀\"";
        $prompt .= " Below it, display the job title \"{$title}\"" . ($company ? " and the company name \"{$company}\"" : '') . ".";

        $prompt .= " CENTERPIECE: A clean grid or row of platform logos/icons representing where this ad is being promoted: {$platformsText}. Each icon should be in its recognizable brand colours, arranged neatly with small labels underneath (e.g. \"{$platformsText}\").";

        $prompt .= " SUPPORTING TEXT: Include a short reassuring line such as \"High-quality, professionally designed ad — marketed across our platforms to reach the right candidates fast.\"";

        if ($companyLogoUrl) {
            $prompt .= " " . $this->companyLogoLine($company, $companyLogoUrl);
            $prompt .= " Place the company logo near the top of the design, alongside the Wakanda Jobs logo.";
        }

        $prompt .= " " . $this->wakandaLogoLine();
        $prompt .= " Add a small \"wakandajobs.com\" footer.";

        return $prompt;
    }

    /**
     * Friendly, "selling" marketing message sent to the employer about their live job ad.
     */
    public function buildEmployerPitchMessage(Job $job): string
    {
        $title   = trim((string) $job->name);
        $company = trim((string) ($job->company?->name ?? ''));
        $platforms = $this->employerMarketingPlatforms($job);
        $whatsappLink = 'https://wa.me/260970766123';

        $jobUrl = null;
        if (! empty($job->slugable->key)) {
            $jobUrl = rtrim(config('app.url'), '/') . '/jobs/' . $job->slugable->key;
        }

        $greeting = $company ? "Hi {$company} team" : 'Hi there';

        $platformList = '';
        foreach ($platforms as $platform) {
            $platformList .= "📱 {$platform}\n";
        }

        // Crawled jobs: the employer hasn't actually posted with us — this is a persuasion / activation pitch.
        if (! $job->is_organic) {
            $msg  = "{$greeting}, 👋\n\n";
            $msg .= "We came across your job ad for *{$title}* online and gave it a free, professionally designed makeover — it's now live on Wakanda Jobs! ✅\n\n";
            $msg .= "We're already showcasing it across:\n";
            $msg .= $platformList;
            $msg .= "\n...because that's where the right candidates for this type of role spend their time. 🎯\n\n";
            $msg .= "This is just a taste of what we can do for you. Activate your free employer account on Wakanda Jobs and let us handle the design, copywriting, and distribution for all your job ads — high-quality, professionally marketed, and reaching candidates fast. 🚀\n\n";

            if ($jobUrl) {
                $msg .= "👉 See your ad: {$jobUrl}\n\n";
            }

            $msg .= "👉 Activate your account or chat with us on WhatsApp: {$whatsappLink}\n\n";
            $msg .= "Wakanda Jobs — Africa's growing job platform. 🌍";

            return $msg;
        }

        $msg  = "{$greeting}, 👋\n\n";
        $msg .= "Great news — your job ad for *{$title}* is now live on Wakanda Jobs! ✅\n\n";
        $msg .= "Our team has polished it into a high-quality, professional ad, and we're actively marketing it across:\n";
        $msg .= $platformList;
        $msg .= "\n...because that's where the right candidates for this type of role spend their time. 🎯\n\n";
        $msg .= "We handle the design, copywriting, and distribution end-to-end — so you can sit back while we bring quality applicants straight to you. 🚀\n\n";

        if ($jobUrl) {
            $msg .= "👉 View your live ad: {$jobUrl}\n\n";
        }

        $msg .= "👉 Need anything? Chat with us on WhatsApp: {$whatsappLink}\n\n";
        $msg .= "Thank you for choosing Wakanda Jobs — Africa's growing job platform. 🌍";

        return $msg;
    }

    private function companyLogoLine(string $company, string $logoUrl): string
    {
        return "⚠️ COMPANY LOGO — CRITICAL INSTRUCTION: I have physically attached the {$company} logo image to this conversation. You MUST use ONLY that attached logo image — do NOT invent, guess, approximate, recreate, or hallucinate any version of this logo. Look at the attached image I sent you and reproduce it exactly. The logo is also available at {$logoUrl} for additional reference, but the attached image is the authoritative source. Place the real logo prominently in the design and extract its dominant colours as the primary palette for the whole image.";
    }

    private function wakandaLogoLine(): string
    {
        $url1 = 'https://www.wakandajobs.com/storage/gemini-generated-image-s1e9dgs1e9dgs1e9.png';
        $url2 = 'https://www.wakandajobs.com/storage/chatgpt-image-may-14-2026-03-00-04-pm.png';
        return "Include the Wakanda Jobs logo (already attached to this conversation) in the top-left corner. The logo is also available at {$url1} or {$url2} for reference.";
    }

    private function extractApplyEmail(Job $job): string
    {
        // Prefer the structured apply_email field
        $email = trim((string) ($job->apply_email ?? ''));
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        // Fall back to first email found in description text
        $text = strip_tags((string) ($job->description ?: $job->content ?: ''));
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m)) {
            return $m[0];
        }

        return '';
    }

    private function titleContains(string $title, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($title, strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }

    private function getVideoHook(Job $job): array
    {
        $title = strtolower(trim((string) $job->name));

        $hooks = match (true) {
            $this->titleContains($title, ['developer', 'engineer', 'software', 'programmer', 'devops', 'frontend', 'backend', 'fullstack', 'data scientist', 'machine learning', 'cybersecurity', 'sysadmin', 'cloud']) => [
                ["Still job-hunting in tech? 💻", "We found something worth stopping for..."],
                ["Your next dev role just dropped 🔥", "This one was built for you..."],
                ["PSA: This tech role won't last long ⏰", "Scroll back up. Trust us."],
                ["Not all tech jobs are the same 👀", "This one's different. Here's why..."],
                ["The role your LinkedIn profile has been waiting for 🚀", "Apply before someone else does..."],
            ],
            $this->titleContains($title, ['accountant', 'accounting', 'finance', 'financial', 'auditor', 'bookkeeper', 'tax', 'cfo', 'treasurer', 'actuary']) => [
                ["Still underselling your finance skills? 💰", "This role was made for someone like you..."],
                ["Your accounting career just leveled up 📊", "See what's waiting for you..."],
                ["The finance role you've been waiting for ✨", "Your next chapter starts here..."],
                ["Numbers don't lie — and neither does this opportunity 📈", "Your skills deserve this role..."],
            ],
            $this->titleContains($title, ['nurse', 'doctor', 'medical', 'healthcare', 'clinical', 'pharmacist', 'dentist', 'therapist', 'midwife', 'health officer', 'radiograph', 'physiother']) => [
                ["A role that actually makes a difference 🏥", "Healthcare professionals — this one's for you..."],
                ["Calling all healthcare heroes 📞", "Your next impactful role is here..."],
                ["You chose this career to change lives ❤️", "Now find a role that matches your calling..."],
                ["Healthcare professionals — your next chapter is here 🌍", "Real work. Real impact."],
            ],
            $this->titleContains($title, ['teacher', 'lecturer', 'tutor', 'educator', 'school', 'training officer', 'instructor', 'academic', 'curriculum']) => [
                ["Educators — this one's for you 📚", "Shape the future. One role at a time."],
                ["The teaching role that changes everything ✏️", "Your next classroom is waiting..."],
                ["You didn't choose education by accident 🌟", "Here's a role that honours that calling..."],
                ["Great teachers deserve great opportunities 🎓", "Apply before the term starts..."],
            ],
            $this->titleContains($title, ['sales', 'business development', 'account manager', 'account executive', 'relationship manager', 'bdm', 'bde']) => [
                ["This role has your name on it 🎯", "Top performers wanted. Are you one?"],
                ["Calling all closers 💼", "This sales role is waiting for its champion..."],
                ["Ready to hit new targets? 🚀", "Your next sales win starts here..."],
                ["The best salespeople don't wait — they apply 🔥", "Don't let this one slip..."],
            ],
            $this->titleContains($title, ['manager', 'director', 'head of', 'chief', 'ceo', 'coo', 'cto', 'vp ', 'president', 'supervisor', 'team lead']) => [
                ["Leaders — your next chapter just arrived 🪑", "This seat was made for you..."],
                ["Ready to lead something bigger? 🌟", "This leadership role is calling your name..."],
                ["The management role worth stopping for 💼", "Real responsibility. Real impact."],
                ["It's time to lead. Not follow. 🚀", "A role that matches your ambition..."],
            ],
            $this->titleContains($title, ['marketing', 'brand manager', 'digital', 'social media', 'content', 'seo', 'campaign', 'communications', 'pr manager', 'copywriter']) => [
                ["Marketing pros — your next big role just dropped 🎯", "Creative talent wanted. See if you qualify..."],
                ["This campaign started with you 🔥", "A marketing role worth writing home about..."],
                ["Stop scrolling. Start applying. 📲", "Your next brand story begins here..."],
                ["Brands are built by people like you 💡", "Here's your next big brief..."],
            ],
            $this->titleContains($title, ['driver', 'logistics', 'transport', 'delivery', 'fleet', 'warehouse', 'supply chain', 'dispatcher']) => [
                ["On the move? So is this opportunity 🚗", "A logistics role worth the trip..."],
                ["This role keeps Africa moving 🌍", "Your next route starts here..."],
                ["Reliable people deserve reliable jobs 💪", "Here's one worth showing up for..."],
            ],
            $this->titleContains($title, ['intern', 'graduate', 'entry level', 'trainee', 'learnership', 'apprentice', 'attaché', 'attachment']) => [
                ["Fresh grad? Your time is NOW 🎓", "Don't wait for experience — build it here..."],
                ["Every expert was once a beginner 🌱", "Your career story starts with this role..."],
                ["The opportunity fresh graduates dream about 🚀", "Apply before someone else does..."],
                ["Your first big break is right here 🌟", "Don't let it scroll past..."],
            ],
            $this->titleContains($title, ['lawyer', 'legal', 'advocate', 'attorney', 'solicitor', 'paralegal', 'compliance']) => [
                ["The legal role worth arguing for ⚖️", "Your next case starts here..."],
                ["Justice. Impact. Career growth. 🏛️", "A legal opportunity worth fighting for..."],
                ["Your legal career just got an upgrade 📜", "See what's waiting for you..."],
            ],
            $this->titleContains($title, ['chef', 'cook', 'kitchen', 'hospitality', 'hotel', 'catering', 'restaurant', 'barista', 'pastry']) => [
                ["Your next kitchen awaits 👨‍🍳", "A hospitality role worth savouring..."],
                ["Passion for food? We've got a role for that 🍽️", "Your culinary career just leveled up..."],
                ["Great chefs deserve great kitchens 🔥", "Apply before the table fills up..."],
            ],
            default => [
                ["You almost scrolled past your dream job 👀", "Keep reading. You'll be glad you did."],
                ["Stop scrolling — this could change everything 🚀", "Your next career move is right here..."],
                ["Your next chapter starts here ✨", "Don't let this one pass you by..."],
                ["Not all job posts are created equal 🔥", "This one's different. Here's why..."],
                ["This job has been waiting for you 🎯", "The right role at the right time..."],
                ["The opportunity you've been looking for just landed 💥", "Apply before it's gone..."],
                ["Real talk — this role is worth your time ⏱️", "Here's everything you need to know..."],
                ["Your 9-to-5 is about to get exciting 🌟", "A role you'll actually look forward to..."],
            ],
        };

        $index = $job->id % count($hooks);
        return $hooks[$index];
    }

    /**
     * Build a haystack of the job's industry signals (title + categories + functional area)
     * used to pick a realistic scene/background instead of always defaulting to an indoor office.
     */
    private function industryHaystack(Job $job): string
    {
        $parts = [(string) $job->name];

        try {
            $parts[] = $job->categories->pluck('name')->filter()->implode(' ');
        } catch (Throwable) {}

        try {
            if ($job->functional_area_id && $job->functionalArea && $job->functionalArea->name) {
                $parts[] = (string) $job->functionalArea->name;
            }
        } catch (Throwable) {}

        // Pad with surrounding spaces so word-boundary keywords (e.g. ' it ', ' port') match cleanly.
        return ' ' . strtolower(trim(implode(' ', array_filter($parts)))) . ' ';
    }

    /**
     * Pick a concrete work-environment scene + appropriate attire for the job's industry, so
     * generated images are diverse (a mine job shows a mine, a farm job shows fields, etc.)
     * rather than everyone indoors in business suits.
     *
     * @return array{scene: string, attire: string}
     */
    private function industryScene(Job $job): array
    {
        $h = $this->industryHaystack($job);

        // Ordered most-specific / most-visual industries first; office/desk roles fall through to the default.
        $groups = [
            [
                ['mining', 'miner', 'mineral', 'quarry', 'geolog', 'metallurg', 'smelter', 'drill', 'blast', 'excavat', 'open pit', 'open-pit'],
                'an active African mine site — an open-pit terraced excavation or underground tunnel, with haul trucks, excavators, conveyor systems or rock walls in the background, under bright natural daylight',
                'mining PPE: hard hats, hi-visibility reflective vests or overalls, safety boots and protective gloves',
            ],
            [
                ['oil and gas', 'oil & gas', 'petroleum', 'petrochemical', 'refinery', 'rig', 'offshore', 'energy', 'utilit', 'power station', 'power plant', 'solar', 'electrical grid', 'pipeline'],
                'an energy / industrial plant setting — an oil & gas facility, power station, solar farm or refinery with pipework and structures in the background under daylight',
                'industrial PPE: flame-resistant coveralls or work uniforms, hard hats, hi-vis vests and safety boots',
            ],
            [
                ['marine', 'maritime', 'seafarer', 'seaport', ' port ', 'harbour', 'harbor', 'vessel', 'cruise ship', 'dockyard'],
                'a marine / port setting — a shipping port with cranes, containers and vessels, or a ship deck in the background under open sky',
                'maritime work attire: hi-vis vests, hard hats and safety boots, or officer uniforms for crew roles',
            ],
            [
                ['construction', 'civil eng', 'quantity surv', 'builder', 'bricklay', 'scaffold', 'concrete', 'building site', 'site manager', 'site engineer', 'road works', 'roadworks', 'infrastructure', 'architect', 'carpenter', 'plumb', 'welding', 'welder'],
                'an active construction site — scaffolding, cranes, partially built structures or roadworks in the background under a bright daytime sky',
                'construction PPE: hard hats, hi-vis vests and work boots; engineers may carry blueprints or a tablet',
            ],
            [
                ['agricultur', 'farm', 'agro', 'crop', 'livestock', 'fish', 'forestry', 'irrigation', 'plantation', 'horticult', 'agronom', 'veterinar', 'poultry', 'dairy'],
                'an outdoor agricultural setting — green fields, crops, greenhouses, livestock or farm machinery in the background under open sky',
                'practical farm work clothing: work shirts, hats and boots; agronomists/vets may inspect crops or animals',
            ],
            [
                ['manufactur', 'production', 'factory', 'fmcg', 'assembly', 'fabricat', 'machinist', 'industrial plant', 'processing plant'],
                'a modern factory / production floor — assembly lines, machinery and industrial equipment in the background',
                'industrial PPE: overalls or work uniforms, hard hats, hi-vis vests, safety goggles and ear protection where relevant',
            ],
            [
                ['automotive', 'motoring', ' mechanic ', 'panel beat', 'auto workshop', 'vehicle service', 'garage'],
                'an automotive workshop — vehicles on lifts, tools and a service bay in the background',
                'mechanic overalls and work uniforms; service advisors in branded polo shirts',
            ],
            [
                ['driver', 'driving', 'logistic', 'transport', 'delivery', 'fleet', 'warehouse', 'supply chain', 'dispatch', 'freight', 'haul', 'trucking', 'courier', 'forklift', 'distribution'],
                'a logistics environment — a warehouse with racking and forklifts, a loading dock, or delivery trucks in the background',
                'hi-vis vests and practical work clothing; drivers beside vehicles, warehouse staff with handheld scanners',
            ],
            [
                ['aviation', 'pilot', 'airline', 'flight', 'aircraft', 'cabin crew', 'airport', 'aerospace'],
                'an aviation setting — an aircraft on the tarmac, a hangar, or an airport terminal in the background',
                'aviation uniforms: pilot or cabin-crew uniforms, or engineering hi-vis for ground/maintenance roles',
            ],
            [
                ['nurse', 'nursing', 'doctor', 'medical', 'healthcare', 'health care', 'clinic', 'hospital', 'pharmac', 'dental', 'dentist', 'midwife', 'surgeon', 'radiograph', 'physiother', 'laborator', 'clinical', 'patient', 'health officer'],
                'a clean modern hospital or clinic interior — bright wards, an examination room or a laboratory in the background',
                'medical scrubs, lab coats or nursing uniforms with stethoscopes — NOT business suits',
            ],
            [
                ['hospitality', 'hotel', 'chef', 'cook', 'kitchen', 'catering', 'restaurant', 'barista', 'waiter', 'waitress', 'housekeep', 'lodge', 'resort', 'culinary', 'pastry', 'tourism', 'travel & tourism'],
                'a hotel, lodge or restaurant setting — an elegant reception, dining area or professional kitchen in the background',
                'hospitality uniforms: chef whites and a toque for kitchen roles, or smart front-desk/waitstaff uniforms for service roles',
            ],
            [
                ['beauty', 'spa', 'wellness', 'salon', 'hairdress', 'cosmetic', 'skincare', 'barber', 'massage', 'fitness', 'gym'],
                'a beauty / wellness setting — a modern salon, spa or fitness studio in the background',
                'salon/spa uniforms or activewear appropriate to the role',
            ],
            [
                ['teacher', 'teaching', 'lecturer', 'tutor', 'educat', 'school', 'academic', 'instructor', 'curriculum', 'professor', 'classroom', 'learnership'],
                'an education setting — a bright classroom, lecture hall or campus with learners in the background',
                'smart-casual professional attire suited to teaching — not necessarily formal suits',
            ],
            [
                ['security', 'guard', 'enforcement', 'defence', 'defense', 'surveillance', 'patrol'],
                'a security setting — a guarded premises entrance, a control room with monitors, or a patrol environment',
                'professional security uniforms; control-room staff at monitoring stations',
            ],
            [
                ['cleaning', 'janitor', 'facilities', 'maintenance', 'groundskeep', 'custodial', 'domestic worker', 'household'],
                'a facilities / maintenance setting — a clean commercial building, office interior or maintained grounds',
                'practical work uniforms or coveralls appropriate to cleaning/maintenance',
            ],
            [
                ['retail', 'shop ', 'store', 'merchandis', 'cashier', 'sales assistant', 'boutique', 'supermarket', 'fashion'],
                'a retail environment — a well-stocked modern store, shop floor or boutique in the background',
                'branded retail staff uniforms or smart-casual attire',
            ],
            [
                ['call centre', 'call center', 'customer service', 'customer care', 'bpo', 'telesales', 'helpdesk', 'contact center', 'contact centre'],
                'a modern call-centre / customer-service floor — rows of headset-wearing agents at workstations in the background',
                'smart-casual office attire with headsets',
            ],
            [
                ['software', 'developer', 'programmer', 'information technology', ' ict', 'data scien', 'data analy', 'cyber', 'devops', 'network engineer', 'system admin', 'web develop', 'machine learning', 'cloud', ' it '],
                'a modern tech office or co-working space — workstations with multiple monitors showing code, in a bright open-plan environment',
                'smart-casual modern tech attire — not formal suits',
            ],
            [
                ['creative', ' media', 'journal', 'graphic design', 'advertis', 'content writer', 'communications', 'public relation', 'photograph', 'videograph', 'film', 'copywrit', 'broadcast'],
                'a creative studio or modern marketing agency — design workstations, mood boards or a media production set in the background',
                'stylish smart-casual creative attire',
            ],
            [
                ['ngo', 'non-profit', 'nonprofit', 'non profit', 'charity', 'humanitarian', 'community', 'social work', 'relief', 'development programme'],
                'a community / field development setting — outreach in a community or a field project that conveys real impact',
                'smart-casual or branded NGO field attire',
            ],
            [
                ['legal', 'lawyer', 'advocate', 'attorney', 'solicitor', 'paralegal', 'litigation', 'court'],
                'a refined law-office setting — an office with law books or a boardroom in the background',
                'formal professional business attire',
            ],
            [
                // Generic engineering / technical roles not caught by a more specific industry above.
                ['engineer', 'technician', 'electrical', 'mechanical', 'telecommunication', 'telecoms', 'technical'],
                'a technical / engineering work environment — an industrial site, plant, workshop or field installation with equipment and machinery in the background',
                'engineering work attire: hard hats, hi-vis vests, safety boots and tools where relevant',
            ],
        ];

        foreach ($groups as [$keywords, $scene, $attire]) {
            foreach ($keywords as $keyword) {
                if (str_contains($h, $keyword)) {
                    return ['scene' => $scene, 'attire' => $attire];
                }
            }
        }

        // Office / admin / HR / management / finance / sales — a bright modern office is appropriate.
        return [
            'scene' => 'a bright, modern professional office in Africa with an open, welcoming atmosphere',
            'attire' => 'smart professional business attire',
        ];
    }

    /**
     * One emphatic instruction block telling the model to match the background and clothing to the
     * job's real industry instead of always rendering an indoor office full of people in suits.
     */
    private function sceneDirective(Job $job, string $style = 'sentence'): string
    {
        ['scene' => $scene, 'attire' => $attire] = $this->industryScene($job);

        if ($style === 'bullet') {
            return "• SCENE & SETTING — match the background to this job's real industry; do NOT default to a generic indoor office with everyone in business suits: set the scene in {$scene}.\n"
                . "• Dress the people in {$attire}, suited to the actual work being done. Keep it authentic and photorealistic for an African workplace.\n";
        }

        return " SCENE & SETTING — match the background to this job's real industry; do NOT default to a generic indoor office with everyone in business suits: set the scene in {$scene}."
            . " Dress the people in {$attire}, suited to the actual work being done, keeping it authentic and photorealistic for an African workplace.";
    }

    private function getFlagColors(string $country): ?string
    {
        $map = [
            'zambia'       => 'green, red, black, and copper/orange',
            'zimbabwe'     => 'green, yellow, red, black, and white',
            'south africa' => 'red, white, blue, green, gold, and black',
            'kenya'        => 'black, red, white, and green',
            'nigeria'      => 'green and white',
            'ghana'        => 'red, gold, green with a black star',
            'tanzania'     => 'green, yellow (gold), black, and blue',
            'uganda'       => 'black, yellow, and red',
            'rwanda'       => 'blue, yellow, and green',
            'malawi'       => 'black, red, and green with a rising red sun',
            'mozambique'   => 'green, white, black, yellow, and red',
            'botswana'     => 'light blue, white, and black',
            'namibia'      => 'blue, red, green, white, and gold',
            'ethiopia'     => 'green, yellow, and red with a blue star',
            'cameroon'     => 'green, red, and yellow with a gold star',
            'senegal'      => 'green, yellow, and red with a green star',
            'ivory coast'  => 'orange, white, and green',
            'angola'       => 'red and black with a gold emblem',
            'madagascar'   => 'white, red, and green',
            'mauritius'    => 'red, blue, yellow, and green',
        ];

        $key = strtolower(trim($country));
        return $map[$key] ?? null;
    }

    public function buildPlatformPosts(Job $job): array
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: 'Zambia'));
        $deadline = $job->application_closing_date ?: $job->expire_date;
        $excerpt  = trim(Str::limit(strip_tags((string) ($job->description ?: $job->content)), 220));
        $facebookUrl = $this->trackedJobUrl($job, 'facebook');
        $instagramUrl = $this->trackedJobUrl($job, 'instagram');
        $linkedinUrl = $this->trackedJobUrl($job, 'linkedin');
        $twitterUrl = $this->trackedJobUrl($job, 'x');
        $whatsappUrl = $this->trackedJobUrl($job, 'whatsapp');

        $salaryLine = '';
        try {
            $s = $this->nativeSalaryText($job);
            if ($s) {
                $salaryLine = $s;
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
        $tiktok .= "\n\nDon't miss this! 👆 Link in bio to apply!";
        $tiktok .= "\n\n#JobsIn{$countrySlug} #{$countrySlug}Jobs #JobTok #Hiring #{$countrySlug}Hiring";
        $tiktok .= " #TikTokJobs #JobAlert #NewJob #{$titleSlug}";
        if ($companySlug) $tiktok .= " #{$companySlug}";
        $tiktok .= " #WakandaJobs #AfricaJobs #GetHired #CareerGoals #JobOpportunity #NowHiring";

        // ── X / Twitter ─────────────────────────────────────────────────────
        // Hard 280-char limit — keep it tight
        $twitterBody  = "🔔 {$title}";
        if ($company) $twitterBody .= " at {$company}";
        $twitterBody .= "\n📍 {$location}";
        if ($salaryLine) $twitterBody .= " | 💰 {$salaryLine}";
        if ($deadlineStr) $twitterBody .= "\n⏰ Deadline: {$deadlineStr}";
        $twitterBody .= "\n\nApply 👉 {$twitterUrl}";
        $twitterBody .= "\n\n#{$countrySlug}Jobs #Hiring #WakandaJobs";
        // Trim if over 280
        if (mb_strlen($twitterBody) > 280) {
            $shortTitle = Str::limit($title, 45, '…');
            $twitterBody  = "🔔 {$shortTitle}";
            $twitterBody .= "\n📍 " . Str::limit($location, 30, '…');
            $twitterBody .= "\n\nApply 👉 {$twitterUrl}";
            $twitterBody .= "\n#WakandaJobs";
        }
        if (mb_strlen($twitterBody) > 280) {
            $twitterBody = "🔔 " . Str::limit($title, 30, '…')
                . "\nApply 👉 {$twitterUrl}"
                . "\n#WakandaJobs";
        }
        $twitter = $twitterBody;

        // ── LinkedIn ────────────────────────────────────────────────────────
        $linkedin  = "🏷️ Position: {$title}\n";
        if ($company) $linkedin .= "📢 Hiring Company: {$company}\n";
        $linkedin .= "📍 Location: {$location}\n";
        if ($salaryLine) $linkedin .= "💰 Salary: {$salaryLine}\n";
        if ($deadlineStr) $linkedin .= "📅 Application Deadline: {$deadlineStr}\n";
        $linkedin .= "\n";
        if ($excerpt) $linkedin .= "{$excerpt}\n\n";
        $linkedin .= "👉 View full details and apply: {$linkedinUrl}\n\n";
        $linkedin .= "Found on Wakanda Jobs — Africa's growing job platform connecting top talent with leading employers.\n\n";
        $linkedin .= "#JobOpening #Hiring #CareerOpportunity #WakandaJobs #{$countrySlug}Jobs";
        if ($titleSlug) $linkedin .= " #{$titleSlug}";
        if ($companySlug) $linkedin .= " #{$companySlug}";
        $linkedin .= " #ProfessionalDevelopment #AfricaCareers";

        // ── Facebook ────────────────────────────────────────────────────────
        $facebook  = "🏷️ Position: {$title}\n";
        if ($company) $facebook .= "🏢 Company: {$company}\n";
        $facebook .= "📍 Location: {$location}\n";
        if ($salaryLine) $facebook .= "💰 Salary: {$salaryLine}\n";
        if ($deadlineStr) $facebook .= "📅 Deadline: {$deadlineStr}\n";
        if ($excerpt) $facebook .= "\n{$excerpt}\n";
        $facebook .= "\n🔗 Apply here: {$facebookUrl}\n\n";
        $facebook .= "💬 Tag someone who needs a job!\n";
        $facebook .= "🔁 Share to help someone find their next opportunity!\n\n";
        $facebook .= "#WakandaJobs #{$countrySlug}Jobs #Jobs #Hiring #JobOpportunity #NowHiring";
        $instagram = str_replace($facebookUrl, $instagramUrl, $facebook);

        // ── WhatsApp Channel ────────────────────────────────────────────────
        $whatsapp  = "🔔 *JOB ALERT*\n\n";
        $whatsapp .= "*Position:* {$title}\n";
        if ($company) $whatsapp .= "*Company:* {$company}\n";
        $whatsapp .= "*Location:* {$location}\n";
        if ($salaryLine) $whatsapp .= "*Salary:* {$salaryLine}\n";
        if ($deadlineStr) $whatsapp .= "*Deadline:* {$deadlineStr}\n";
        if ($excerpt) $whatsapp .= "\n{$excerpt}\n";
        $whatsapp .= "\n*Apply Now 👉* {$whatsappUrl}\n\n";
        $whatsapp .= "_Wakanda Jobs — wakandajobs.com_";

        return compact('tiktok', 'twitter', 'linkedin', 'facebook', 'instagram', 'whatsapp');
    }

    public function buildManualSocialPost(Job $job): string
    {
        $url = $this->trackedJobUrl($job, 'telegram');
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
    // Publer  (base: https://app.publer.com/api/v1)
    // Auth:   Authorization: Bearer-API {key}  +  Publer-Workspace-Id: {id}
    // -------------------------------------------------------------------------

    private const PUBLER_BASE = 'https://app.publer.com/api/v1';

    private function publerHeaders(string $apiKey, string $workspaceId = ''): array
    {
        $headers = [
            'Authorization' => 'Bearer-API ' . $apiKey,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
        if ($workspaceId !== '') {
            $headers['Publer-Workspace-Id'] = $workspaceId;
        }
        return $headers;
    }

    private function publerResolveWorkspace(string $apiKey, string $hint = ''): string
    {
        if ($hint !== '') {
            return $hint;
        }
        $r = Http::timeout(10)->withHeaders($this->publerHeaders($apiKey))->get(self::PUBLER_BASE . '/workspaces');
        $list = $r->json();
        if (! is_array($list) || empty($list)) {
            throw new \RuntimeException('No Publer workspaces found for this API key.');
        }
        return (string) ($list[0]['id'] ?? '');
    }

    protected function postToPubler(SocialAutomation $automation, Job $job): bool
    {
        $settings   = $automation->settings ?? [];
        $apiKey     = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            $apiKey = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
        }
        $accountIds  = array_values(array_filter((array) ($settings['account_ids'] ?? [])));
        $workspaceId = trim((string) ($settings['workspace_id'] ?? ''));
        $countryId   = isset($settings['country_id']) && $settings['country_id'] !== ''
            ? (int) $settings['country_id']
            : null;

        if ($apiKey === '' || empty($accountIds)) {
            return false;
        }

        if ($countryId !== null && (int) $job->country_id !== $countryId) {
            return false;
        }

        return $this->publerPost($job, $apiKey, $accountIds, $workspaceId);
    }

    public function fetchPublerWorkspaces(string $apiKey): array
    {
        $r = Http::timeout(15)->withHeaders($this->publerHeaders($apiKey))->get(self::PUBLER_BASE . '/workspaces');

        if (! $r->successful()) {
            throw new \RuntimeException('Publer API returned HTTP ' . $r->status() . ': ' . $r->body());
        }

        $list = $r->json();
        if (! is_array($list)) {
            $list = [];
        }

        return collect($list)
            ->map(fn ($w) => [
                'id'   => (string) ($w['id'] ?? ''),
                'name' => $w['name'] ?? 'Workspace',
            ])
            ->filter(fn ($w) => $w['id'] !== '')
            ->values()
            ->all();
    }

    public function fetchPublerAccounts(string $apiKey, string $workspaceId = ''): array
    {
        if ($workspaceId === '') {
            $workspaceId = $this->publerResolveWorkspace($apiKey);
        }

        $r = Http::timeout(15)
            ->withHeaders($this->publerHeaders($apiKey, $workspaceId))
            ->get(self::PUBLER_BASE . '/accounts');

        if (! $r->successful()) {
            throw new \RuntimeException('Publer API returned HTTP ' . $r->status() . ': ' . $r->body());
        }

        $list = $r->json();
        if (! is_array($list)) {
            $list = [];
        }

        // Map account type to a readable platform label
        $typeLabels = [
            'fb_page'          => 'Facebook Page',
            'fb_group'         => 'Facebook Group',
            'fb_profile'       => 'Facebook Profile',
            'in_page'          => 'LinkedIn Page',
            'in_profile'       => 'LinkedIn Profile',
            'tiktok'           => 'TikTok',
            'instagram'        => 'Instagram',
            'twitter'          => 'X (Twitter)',
            'pinterest'        => 'Pinterest',
            'youtube'          => 'YouTube',
            'google'           => 'Google Business',
            'telegram'         => 'Telegram',
            'mastodon'         => 'Mastodon',
            'threads'          => 'Threads',
            'bluesky'          => 'Bluesky',
            'wordpress_basic'  => 'WordPress',
            'wordpress_oauth'  => 'WordPress (OAuth)',
        ];

        return collect($list)
            ->map(fn ($acc) => [
                'id'          => (string) ($acc['id'] ?? ''),
                'name'        => $acc['name'] ?? 'Unknown',
                'platform'    => $acc['provider'] ?? '',
                'type'        => $acc['type'] ?? '',
                'type_label'  => $typeLabels[$acc['type'] ?? ''] ?? ucfirst($acc['provider'] ?? $acc['type'] ?? 'Unknown'),
                'picture'     => $acc['picture'] ?? null,
                'locked'      => ! empty($acc['locked']),
            ])
            ->filter(fn ($acc) => $acc['id'] !== '')
            ->values()
            ->all();
    }

    public function publerPost(Job $job, string $apiKey, array $accountIds, string $workspaceId = '', ?string $preferredImageField = null, array $excludeNetworks = []): bool
    {
        $this->lastPublerError = null;

        $publish = fn () => $this->publerPostUnlocked(
            $job,
            $apiKey,
            $accountIds,
            $workspaceId,
            $preferredImageField,
            $excludeNetworks
        );

        return $publish();
    }

    public function getLastPublerError(): ?string
    {
        return $this->lastPublerError;
    }

    protected function publerPostUnlocked(Job $job, string $apiKey, array $accountIds, string $workspaceId = '', ?string $preferredImageField = null, array $excludeNetworks = []): bool
    {
        if (empty($accountIds)) {
            return false;
        }

        if ($workspaceId === '') {
            $workspaceId = $this->publerResolveWorkspace($apiKey);
        }

        $posts = $this->buildPlatformPosts($job);

        $networkTextMap = [
            'facebook'  => $posts['facebook']  ?? null,
            'linkedin'  => $posts['linkedin']  ?? null,
            'tiktok'    => $posts['tiktok']    ?? null,
            'twitter'   => $posts['twitter']   ?? null,
            'instagram' => $posts['instagram'] ?? null,
        ];
        $defaultText = $posts['facebook'] ?? $this->buildJobMessage($job, 'facebook');

        // Resolve image URL — preferred field first, then fallbacks
        $imageUrl = null;
        $imageFields = ['facebook_image', 'whatsapp_image', 'linkedin_image', 'tiktok_image'];
        if ($preferredImageField) {
            $imageFields = array_merge(
                [$preferredImageField],
                array_values(array_filter($imageFields, fn ($f) => $f !== $preferredImageField))
            );
        }
        foreach ($imageFields as $field) {
            $stored = trim((string) ($job->{$field} ?? ''));
            if ($stored !== '') {
                try {
                    $resolved = RvMedia::getImageUrl($stored);
                    if ($resolved) {
                        $imageUrl = $resolved;
                    }
                } catch (Throwable) {}
                break;
            }
        }

        // Upload image to Publer and get a media ID for use in post payload
        $mediaId = null;
        if ($imageUrl) {
            $mediaId = $this->publerUploadMedia($apiKey, $workspaceId, $imageUrl);
        }

        $accountPayloads = [];
        foreach ($accountIds as $accountId) {
            $accountPayloads[] = ['id' => (string) $accountId];
        }

        $postObj = [
            'accounts' => $accountPayloads,
            'networks' => [],
        ];

        // TikTok requires type='photo' with media — skip if no image.
        // Other platforms use type='status'; image is optional.
        foreach (['facebook', 'linkedin', 'tiktok', 'twitter', 'instagram'] as $net) {
            if (in_array($net, $excludeNetworks, true)) {
                continue;
            }

            $text = $networkTextMap[$net] ?? $defaultText;
            if ($net === 'tiktok') {
                if (! $mediaId) {
                    continue; // TikTok does not support text-only posts
                }
                $postObj['networks'][$net] = [
                    'type'    => 'photo',
                    'title'   => $this->buildTikTokPostTitle($job),
                    'text'    => $text,
                    'media'   => [['id' => $mediaId, 'type' => 'image']],
                    'details' => ['auto_add_music' => true, 'privacy' => 'PUBLIC_TO_EVERYONE'],
                ];
                continue;
            }
            $entry = ['type' => $mediaId ? 'photo' : 'status', 'text' => $text];
            if ($mediaId) {
                $entry['media'] = [['id' => $mediaId, 'type' => 'image']];
            }
            $postObj['networks'][$net] = $entry;
        }

        $targetNetworks = array_keys($postObj['networks']);
        $accountPayloads = $this->filterPublerAccountsForNetworks(
            $apiKey,
            $workspaceId,
            $accountIds,
            $targetNetworks,
        );
        $postObj['accounts'] = $accountPayloads;

        if (empty($postObj['accounts'])) {
            $this->lastPublerError = 'No selected Publer account matches: ' . implode(', ', $targetNetworks) . '.';

            return false;
        }

        if (empty($postObj['networks'])) {
            unset($postObj['networks']);
            $postObj['text'] = $defaultText;
        }

        $payload = [
            'bulk' => [
                'state' => 'scheduled',
                'posts' => [$postObj],
            ],
        ];

        [$success, $error] = $this->publerPublishAndWait($apiKey, $workspaceId, $payload);

        if (! $success) {
            $this->lastPublerError = $error;

            Log::warning('Publer post failed', [
                'job_id' => $job->getKey(),
                'error'  => $error,
            ]);
        }

        return $success;
    }

    private function filterPublerAccountsForNetworks(
        string $apiKey,
        string $workspaceId,
        array $accountIds,
        array $targetNetworks,
    ): array {
        if (empty($targetNetworks)) {
            return array_map(fn ($id) => ['id' => (string) $id], $accountIds);
        }

        try {
            $accounts = $this->fetchPublerAccounts($apiKey, $workspaceId);
            $allowedIds = collect($accounts)
                ->filter(function (array $account) use ($targetNetworks): bool {
                    $platform = $this->normalizePublerAccountPlatform($account);

                    return in_array($platform, $targetNetworks, true);
                })
                ->pluck('id')
                ->all();

            return collect($accountIds)
                ->map(fn ($id) => (string) $id)
                ->filter(fn (string $id) => in_array($id, $allowedIds, true))
                ->map(fn (string $id) => ['id' => $id])
                ->values()
                ->all();
        } catch (Throwable $e) {
            Log::warning('Could not filter Publer accounts by network', [
                'networks' => $targetNetworks,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function normalizePublerAccountPlatform(array $account): string
    {
        $platform = strtolower((string) ($account['platform'] ?? ''));
        $type = strtolower((string) ($account['type'] ?? ''));

        if ($platform !== '') {
            return $platform;
        }

        return match (true) {
            str_starts_with($type, 'fb_') => 'facebook',
            str_starts_with($type, 'in_') => 'linkedin',
            default => $type,
        };
    }

    private function publerPublishAndWait(string $apiKey, string $workspaceId, array $payload): array
    {
        $response = Http::timeout(30)
            ->withHeaders($this->publerHeaders($apiKey, $workspaceId))
            ->post(self::PUBLER_BASE . '/posts/schedule/publish', $payload);

        if (! $response->successful()) {
            return [false, 'HTTP ' . $response->status() . ': ' . $response->body()];
        }

        $jobId = (string) ($response->json('job_id') ?? $response->json('data.job_id') ?? '');
        if ($jobId === '') {
            return [false, 'Publer accepted the request but returned no job ID.'];
        }

        for ($attempt = 0; $attempt < 40; $attempt++) {
            usleep(500000);

            $statusResponse = Http::timeout(20)
                ->withHeaders($this->publerHeaders($apiKey, $workspaceId))
                ->get(self::PUBLER_BASE . '/job_status/' . $jobId);

            if (! $statusResponse->successful()) {
                continue;
            }

            $status = strtolower((string) $statusResponse->json('status'));
            if ($status === 'failed') {
                return [false, $statusResponse->body()];
            }

            if (in_array($status, ['complete', 'completed'], true)) {
                $failures = collect((array) $statusResponse->json('payload'))
                    ->filter(function ($item): bool {
                        return strtolower((string) data_get($item, 'status')) === 'failed'
                            || filled(data_get($item, 'post.error'));
                    })
                    ->values()
                    ->all();

                return empty($failures)
                    ? [true, null]
                    : [false, json_encode($failures, JSON_UNESCAPED_SLASHES)];
            }
        }

        return [false, "Publer job {$jobId} did not complete within 20 seconds."];
    }

    /**
     * Upload an image to Publer via POST /media (multipart/form-data).
     * Returns the Publer media ID on success, null on failure.
     * The direct upload endpoint is synchronous — ID is returned immediately.
     */
    private function publerUploadMedia(string $apiKey, string $workspaceId, string $imageUrl): ?string
    {
        // Download the image from our server so we can re-upload it to Publer
        try {
            $download = Http::timeout(30)->get($imageUrl);
            if (! $download->successful()) {
                return null;
            }
            $content     = $download->body();
            $contentType = $download->header('Content-Type') ?: 'image/jpeg';
        } catch (Throwable) {
            return null;
        }

        $filename = basename(parse_url($imageUrl, PHP_URL_PATH)) ?: 'image.jpg';

        // Build headers without Content-Type — multipart sets it automatically
        $headers = ['Authorization' => 'Bearer-API ' . $apiKey, 'Accept' => 'application/json'];
        if ($workspaceId !== '') {
            $headers['Publer-Workspace-Id'] = $workspaceId;
        }

        try {
            $r = Http::timeout(60)
                ->withHeaders($headers)
                ->attach('file', $content, $filename, ['Content-Type' => $contentType])
                ->post(self::PUBLER_BASE . '/media');

            if (! $r->successful()) {
                return null;
            }

            $id = (string) ($r->json('id') ?? '');
            return $id !== '' ? $id : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected function postToPublerCountryMapping(Job $job, array &$results): void
    {
        $this->postToPublerCountryMappingUnlocked($job, $results);
    }

    protected function postToPublerCountryMappingUnlocked(Job $job, array &$results): void
    {
        if (! $job->country_id) {
            return;
        }

        $mapping = \Botble\JobBoard\Models\PublerCountryMapping::where('country_id', $job->country_id)
            ->where('is_active', true)
            ->first();

        if (! $mapping) {
            return;
        }

        $apiKey = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
        if ($apiKey === '') {
            return;
        }

        $networkMap = $mapping->networkToAccountMap();
        if (empty($networkMap)) {
            return;
        }

        $workspaceId = $mapping->workspace_id ?: trim((string) setting('publer_workspace_id', ''));
        if ($workspaceId === '') {
            $workspaceId = $this->publerResolveWorkspace($apiKey);
        }

        $posts       = $this->buildPlatformPosts($job);
        $defaultText = $posts['facebook'] ?? $this->buildJobMessage($job, 'facebook');

        $networkTextMap = [
            'facebook'  => $posts['facebook']  ?? $defaultText,
            'linkedin'  => $posts['linkedin']  ?? $defaultText,
            'tiktok'    => $posts['tiktok']    ?? $defaultText,
            'twitter'   => $posts['twitter']   ?? $defaultText,
            'instagram' => $posts['instagram'] ?? $defaultText,
        ];

        // ── Image resolution & upload ─────────────────────────────────────────
        // Priority: 1) generated template image, 2) job's stored image
        // Images are uploaded to Publer's /media endpoint to get a media ID.
        $tiktokMediaId  = null; // vertical (9:16) uploaded for TikTok
        $squareMediaId  = null; // square / landscape uploaded for FB/LinkedIn/Twitter/Instagram
        $generatedPaths = [];   // track local temp files to clean up after posting

        if ($mapping->hasImageGeneration()) {
            try {
                $imageService = app(\Botble\JobBoard\Services\SocialImageService::class);
                $hasTikTok    = isset($networkMap['tiktok']);

                // Generate vertical for TikTok (or square if no TikTok)
                $primaryFormat = $hasTikTok ? 'vertical' : 'square';
                $result        = $imageService->generateForJob($job, $mapping, $primaryFormat);

                if ($result) {
                    [$localPath, $generatedUrl] = $result;
                    $generatedPaths[] = $localPath;
                    $uploaded         = $this->publerUploadMedia($apiKey, $workspaceId, $generatedUrl);
                    if ($uploaded) {
                        $tiktokMediaId = $uploaded;
                        $squareMediaId = $uploaded; // fallback for non-TikTok until we generate a square
                    }
                }

                // If we have TikTok AND non-TikTok accounts, also generate + upload a square
                if ($hasTikTok && count($networkMap) > 1) {
                    $squareResult = $imageService->generateForJob($job, $mapping, 'square');
                    if ($squareResult) {
                        [$squarePath, $squareUrl] = $squareResult;
                        $generatedPaths[] = $squarePath;
                        $squareUploaded   = $this->publerUploadMedia($apiKey, $workspaceId, $squareUrl);
                        if ($squareUploaded) {
                            $squareMediaId = $squareUploaded;
                        }
                    }
                }
            } catch (Throwable) {}
        }

        // Fallback: upload from the job's stored image fields
        if (! $squareMediaId) {
            foreach (['facebook_image', 'whatsapp_image', 'linkedin_image', 'tiktok_image'] as $field) {
                $stored = trim((string) ($job->{$field} ?? ''));
                if ($stored !== '') {
                    try {
                        $resolved = RvMedia::getImageUrl($stored);
                        if ($resolved) {
                            $uploaded = $this->publerUploadMedia($apiKey, $workspaceId, $resolved);
                            if ($uploaded) {
                                $squareMediaId = $uploaded;
                                if (! $tiktokMediaId) {
                                    $tiktokMediaId = $uploaded;
                                }
                            }
                        }
                    } catch (Throwable) {}
                    break;
                }
            }
        }

        // TikTok requires type='photo' with a media ID — skip if no image was uploaded.
        // All other platforms use type='status' with an optional media attachment.
        $publerPosts = [];
        foreach ($networkMap as $net => $accountId) {
            if ($net === 'tiktok') {
                if (! $tiktokMediaId) {
                    continue; // TikTok does not support text-only posts
                }
                $publerPosts[] = [
                    'accounts' => [['id' => (string) $accountId]],
                    'networks' => [$net => [
                        'type'    => 'photo',
                        'title'   => $this->buildTikTokPostTitle($job),
                        'text'    => $networkTextMap[$net] ?? $defaultText,
                        'media'   => [['id' => $tiktokMediaId, 'type' => 'image']],
                        'details' => ['auto_add_music' => true, 'privacy' => 'PUBLIC_TO_EVERYONE'],
                    ]],
                ];
                continue;
            }

            $entry = ['type' => $squareMediaId ? 'photo' : 'status', 'text' => $networkTextMap[$net] ?? $defaultText];
            if ($squareMediaId) {
                $entry['media'] = [['id' => $squareMediaId, 'type' => 'image']];
            }
            $publerPosts[] = [
                'accounts' => [['id' => (string) $accountId]],
                'networks' => [$net => $entry],
            ];
        }

        try {
            $errors = [];
            usort($publerPosts, static function (array $left, array $right): int {
                $leftIsLinkedIn = isset($left['networks']['linkedin']);
                $rightIsLinkedIn = isset($right['networks']['linkedin']);

                return $leftIsLinkedIn <=> $rightIsLinkedIn;
            });

            foreach ($publerPosts as $publerPost) {
                $publish = fn (): array => $this->publerPublishAndWait($apiKey, $workspaceId, [
                    'bulk' => [
                        'state' => 'scheduled',
                        'posts' => [$publerPost],
                    ],
                ]);

                [$postSuccess, $postError] = $publish();

                if (! $postSuccess) {
                    $errors[] = $postError;
                }
            }

            $success = empty($errors);
            $error = $success ? null : implode("\n", array_filter($errors));

            $results[] = [
                'automation' => 'Publer country mapping',
                'platform'   => 'publer',
                'success'    => $success,
                'error'      => $error,
            ];

            if (! $success) {
                Log::warning('Publer country mapping post failed', [
                    'job_id'    => $job->getKey(),
                    'country_id' => $job->country_id,
                    'error'      => $error,
                ]);
            }
        } catch (Throwable $e) {
            $results[] = [
                'automation' => 'Publer country mapping',
                'platform'   => 'publer',
                'success'    => false,
                'error'      => $e->getMessage(),
            ];

            Log::warning('Publer country mapping post failed', [
                'job_id'     => $job->getKey(),
                'country_id' => $job->country_id,
                'error'      => $e->getMessage(),
            ]);
        } finally {
            // Clean up any generated image files
            if ($generatedPaths) {
                try {
                    $imageService = app(\Botble\JobBoard\Services\SocialImageService::class);
                    foreach ($generatedPaths as $p) {
                        $imageService->cleanup($p);
                    }
                } catch (Throwable) {}
            }
        }
    }

    public function publerPostText(string $text, string $apiKey, string $workspaceId, array $networkToAccountId): bool
    {
        if (empty($networkToAccountId)) {
            return false;
        }

        if ($workspaceId === '') {
            $workspaceId = $this->publerResolveWorkspace($apiKey);
        }

        $accountPayloads = array_map(fn ($id) => ['id' => (string) $id], array_values($networkToAccountId));
        $networksObj     = [];
        foreach (array_keys($networkToAccountId) as $net) {
            $networksObj[$net] = ['type' => 'status', 'text' => $text];
        }

        $payload = [
            'bulk' => [
                'state' => 'scheduled',
                'posts' => [['accounts' => $accountPayloads, 'networks' => $networksObj]],
            ],
        ];

        [$success] = $this->publerPublishAndWait($apiKey, $workspaceId, $payload);

        return $success;
    }

    // -------------------------------------------------------------------------
    // Channel broadcasts — a custom message + optional image, sent directly
    // (no Publer) to every active Facebook / LinkedIn / WhatsApp channel.
    // -------------------------------------------------------------------------

    /**
     * "AI Spice" — reword a recurring broadcast a little differently on each send so
     * repeated posts don't read as identical copy-paste spam. Best-effort: falls back
     * to the original message untouched on any failure or missing API key.
     */
    public function rephraseBroadcastMessage(string $message): string
    {
        $apiKey = setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (! $apiKey) {
            return $message;
        }

        $systemPrompt = <<<'PROMPT'
You rewrite social-media broadcast messages for a job-board company so repeated posts don't read as identical copy-paste spam.

Rules:
- Keep the exact same meaning, facts, and call to action.
- Keep ALL URLs, phone numbers, hashtags, and emoji-led labels EXACTLY character-for-character identical — never alter, shorten, or remove them.
- Vary sentence structure and word choice a little — natural and on-brand, not gimmicky.
- Keep roughly the same length.
- Output ONLY the rewritten message text — no preamble, no quotes, no markdown fences.
PROMPT;

        try {
            $response = Http::timeout(30)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => 'gpt-4o-mini',
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.8,
                    'max_tokens'  => 900,
                ]);

            if (! $response->successful()) {
                return $message;
            }

            $rewritten = trim((string) $response->json('choices.0.message.content', ''));
            $rewritten = preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $rewritten ?? '');

            return $rewritten !== '' ? $rewritten : $message;
        } catch (Throwable) {
            return $message;
        }
    }

    public function broadcastToChannels(string $message, ?string $imageUrl = null): array
    {
        $automations = SocialAutomation::query()
            ->whereIn('platform', ['facebook', 'linkedin', 'whatsapp', 'whapi', 'publer'])
            ->where('is_active', true)
            ->get();

        $results = [];

        foreach ($automations as $automation) {
            $ok = false;
            try {
                $ok = match ($automation->platform) {
                    'facebook' => $this->broadcastToFacebook($automation, $message, $imageUrl),
                    'linkedin' => $this->broadcastToLinkedIn($automation, $message, $imageUrl),
                    'whatsapp' => $this->broadcastToWhatsApp($automation, $message, $imageUrl),
                    'whapi'    => $this->broadcastToWhapiChannel($automation, $message, $imageUrl),
                    'publer'   => $this->broadcastViaPubler($automation, $message, $imageUrl),
                    default    => false,
                };
            } catch (Throwable $e) {
                Log::warning('Channel broadcast failed', [
                    'automation_id' => $automation->getKey(),
                    'platform'      => $automation->platform,
                    'error'         => $e->getMessage(),
                ]);
            }

            $results[] = [
                'automation_id' => $automation->getKey(),
                'platform'      => $automation->platform,
                'name'          => $automation->name,
                'success'       => $ok,
            ];
        }

        return $results;
    }

    protected function broadcastToFacebook(SocialAutomation $automation, string $message, ?string $imageUrl): bool
    {
        $settings = $automation->settings ?? [];
        $pageId   = trim((string) ($settings['page_id'] ?? ''));
        $token    = trim((string) ($settings['access_token'] ?? ''));

        if ($pageId === '' || $token === '') {
            return false;
        }

        if ($imageUrl) {
            $response = Http::timeout(30)
                ->post("https://graph.facebook.com/v19.0/{$pageId}/photos", [
                    'url'          => $imageUrl,
                    'caption'      => $message,
                    'access_token' => $token,
                ]);

            return $response->successful() && isset($response->json()['id']);
        }

        $response = Http::timeout(20)
            ->post("https://graph.facebook.com/v19.0/{$pageId}/feed", [
                'message'      => $message,
                'access_token' => $token,
            ]);

        return $response->successful() && isset($response->json()['id']);
    }

    /**
     * Posts to whatever Facebook / LinkedIn / TikTok pages are connected
     * through this Publer automation's account_ids — same bulk-publish
     * endpoint used for job posts, but with the broadcast's own text/image.
     * TikTok is skipped when there is no image (it rejects text-only posts).
     */
    protected function broadcastViaPubler(SocialAutomation $automation, string $message, ?string $imageUrl): bool
    {
        $settings = $automation->settings ?? [];
        $apiKey   = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            $apiKey = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
        }
        $accountIds  = array_values(array_filter((array) ($settings['account_ids'] ?? [])));
        $workspaceId = trim((string) ($settings['workspace_id'] ?? ''));

        if ($apiKey === '' || empty($accountIds)) {
            return false;
        }

        if ($workspaceId === '') {
            $workspaceId = $this->publerResolveWorkspace($apiKey);
        }

        $mediaId = $imageUrl ? $this->publerUploadMedia($apiKey, $workspaceId, $imageUrl) : null;

        $networks = [];
        foreach (['facebook', 'linkedin', 'tiktok'] as $net) {
            if ($net === 'tiktok') {
                if (! $mediaId) {
                    continue;
                }
                $networks[$net] = [
                    'type'    => 'photo',
                    'title'   => Str::limit($message, 90, ''),
                    'text'    => $message,
                    'media'   => [['id' => $mediaId, 'type' => 'image']],
                    'details' => ['auto_add_music' => true, 'privacy' => 'PUBLIC_TO_EVERYONE'],
                ];
                continue;
            }

            $entry = ['type' => $mediaId ? 'photo' : 'status', 'text' => $message];
            if ($mediaId) {
                $entry['media'] = [['id' => $mediaId, 'type' => 'image']];
            }
            $networks[$net] = $entry;
        }

        if (empty($networks)) {
            return false;
        }

        $payload = [
            'bulk' => [
                'state' => 'published',
                'posts' => [[
                    'accounts' => array_map(fn ($id) => ['id' => (string) $id], $accountIds),
                    'networks' => $networks,
                ]],
            ],
        ];

        $r = Http::timeout(30)
            ->withHeaders($this->publerHeaders($apiKey, $workspaceId))
            ->post(self::PUBLER_BASE . '/posts/schedule/publish', $payload);

        if (! $r->successful()) {
            Log::warning('Publer broadcast failed', [
                'automation_id' => $automation->getKey(),
                'status'        => $r->status(),
                'body'          => $r->body(),
            ]);
        }

        return $r->successful();
    }

    protected function broadcastToLinkedIn(SocialAutomation $automation, string $message, ?string $imageUrl): bool
    {
        $settings = $automation->settings ?? [];
        $orgId    = trim((string) ($settings['org_id'] ?? ''));
        $token    = trim((string) ($settings['access_token'] ?? ''));

        if ($orgId === '' || $token === '') {
            return false;
        }

        $author       = "urn:li:organization:{$orgId}";
        $shareContent = [
            'shareCommentary'    => ['text' => $message],
            'shareMediaCategory' => 'NONE',
        ];

        if ($imageUrl) {
            $asset = $this->linkedinUploadImage($token, $author, $imageUrl);
            if ($asset) {
                $shareContent['shareMediaCategory'] = 'IMAGE';
                $shareContent['media'] = [[
                    'status' => 'READY',
                    'media'  => $asset,
                ]];
            }
        }

        $response = Http::timeout(30)
            ->withToken($token)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->post('https://api.linkedin.com/v2/ugcPosts', [
                'author'          => $author,
                'lifecycleState'  => 'PUBLISHED',
                'specificContent' => ['com.linkedin.ugc.ShareContent' => $shareContent],
                'visibility'      => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
            ]);

        return $response->successful();
    }

    /**
     * LinkedIn images require a 3-step upload: register the upload slot,
     * PUT the binary to the returned URL, then reference the asset URN
     * in the post's media array. Returns the asset URN on success.
     */
    private function linkedinUploadImage(string $token, string $author, string $imageUrl): ?string
    {
        try {
            $register = Http::timeout(20)
                ->withToken($token)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->post('https://api.linkedin.com/v2/assets?action=registerUpload', [
                    'registerUploadRequest' => [
                        'recipes'              => ['urn:li:digitalmediaRecipe:feedshare-image'],
                        'owner'                => $author,
                        'serviceRelationships' => [[
                            'relationshipType' => 'OWNER',
                            'identifier'       => 'urn:li:userGeneratedContent',
                        ]],
                    ],
                ]);

            if (! $register->successful()) {
                return null;
            }

            $value     = $register->json('value');
            $uploadUrl = $value['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'] ?? null;
            $asset     = $value['asset'] ?? null;

            if (! $uploadUrl || ! $asset) {
                return null;
            }

            $download = Http::timeout(30)->get($imageUrl);
            if (! $download->successful()) {
                return null;
            }

            $upload = Http::withToken($token)
                ->withBody($download->body(), $download->header('Content-Type') ?: 'image/jpeg')
                ->put($uploadUrl);

            return $upload->successful() ? $asset : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected function broadcastToWhatsApp(SocialAutomation $automation, string $message, ?string $imageUrl): bool
    {
        $settings  = $automation->settings ?? [];
        $phoneId   = trim((string) ($settings['phone_number_id'] ?? ''));
        $token     = trim((string) ($settings['access_token'] ?? ''));
        $recipient = trim((string) ($settings['recipient'] ?? ''));

        if ($phoneId === '' || $token === '' || $recipient === '') {
            return false;
        }

        $payload = $imageUrl
            ? ['messaging_product' => 'whatsapp', 'to' => $recipient, 'type' => 'image', 'image' => ['link' => $imageUrl, 'caption' => $message]]
            : ['messaging_product' => 'whatsapp', 'to' => $recipient, 'type' => 'text', 'text' => ['body' => $message]];

        $response = Http::timeout(30)
            ->withToken($token)
            ->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", $payload);

        return $response->successful();
    }

    protected function broadcastToWhapiChannel(SocialAutomation $automation, string $message, ?string $imageUrl): bool
    {
        $settings   = $automation->settings ?? [];
        $token      = SocialAutomation::whapiToken($automation);
        $channelId  = trim((string) ($settings['channel_id'] ?? ''));
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        if ($token === '' || $channelId === '') {
            return false;
        }

        if (! str_ends_with($channelId, '@newsletter')) {
            $channelId .= '@newsletter';
        }

        if ($imageUrl) {
            $response = Http::timeout(30)
                ->withToken($token)
                ->post("{$gatewayUrl}/messages/image", [
                    'to'      => $channelId,
                    'media'   => $imageUrl,
                    'caption' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }
        }

        $response = Http::timeout(20)
            ->withToken($token)
            ->post("{$gatewayUrl}/messages/text", [
                'to'   => $channelId,
                'body' => $message,
            ]);

        return $response->successful();
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
                'message'      => $this->buildJobMessage($job, 'facebook'),
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
                        'shareCommentary'   => ['text' => $this->buildJobMessage($job, 'linkedin')],
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
                'text' => ['body' => $this->buildJobMessage($job, 'whatsapp')],
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

    // -------------------------------------------------------------------------
    // WhatsApp Channel via Whapi.io
    // -------------------------------------------------------------------------

    protected function postToWhapiChannel(SocialAutomation $automation, Job $job): bool
    {
        $settings   = $automation->settings ?? [];
        $token      = SocialAutomation::whapiToken($automation);
        $channelId  = trim((string) ($settings['channel_id'] ?? ''));
        $countryId  = isset($settings['country_id']) && $settings['country_id'] !== ''
            ? (int) $settings['country_id']
            : null;
        $sendImage  = ! empty($settings['send_image']);
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        if ($token === '' || $channelId === '') {
            return false;
        }

        if ($countryId !== null && (int) $job->country_id !== $countryId) {
            return false;
        }

        // Ensure newsletter JID suffix
        if (! str_ends_with($channelId, '@newsletter')) {
            $channelId .= '@newsletter';
        }

        $posts   = $this->buildPlatformPosts($job);
        $message = $posts['whatsapp'] ?? $this->buildJobMessage($job, 'whatsapp');

        // 1. Use the job's stored whatsapp_image if available (highest priority)
        $storedImage = trim((string) ($job->whatsapp_image ?? ''));
        if ($storedImage !== '') {
            try {
                $imageUrl = RvMedia::getImageUrl($storedImage);
                $response = Http::timeout(30)
                    ->withToken($token)
                    ->post("{$gatewayUrl}/messages/image", [
                        'to'      => $channelId,
                        'media'   => $imageUrl,
                        'caption' => $message,
                    ]);

                if ($response->successful()) {
                    return true;
                }
            } catch (Throwable) {
                // Fall through
            }
        }

        // 2. Generate AI image if the automation has send_image enabled
        if ($sendImage) {
            $imagePath = null;
            try {
                $imagePath = app(JobImageGeneratorService::class)->generate($job);
            } catch (Throwable) {
                $imagePath = null;
            }

            if ($imagePath && file_exists($imagePath)) {
                try {
                    $base64   = base64_encode(file_get_contents($imagePath));
                    $mediaUri = 'data:image/jpeg;base64,' . $base64;

                    $response = Http::timeout(60)
                        ->withToken($token)
                        ->post("{$gatewayUrl}/messages/image", [
                            'to'      => $channelId,
                            'media'   => $mediaUri,
                            'caption' => $message,
                        ]);

                    if ($response->successful()) {
                        return true;
                    }
                } catch (Throwable) {
                    // Fall through to text-only
                } finally {
                    @unlink($imagePath);
                }
            }
        }

        // 3. Text-only fallback
        $response = Http::timeout(20)
            ->withToken($token)
            ->post("{$gatewayUrl}/messages/text", [
                'to'   => $channelId,
                'body' => $message,
            ]);

        return $response->successful();
    }

    public function sendTelegramCopyPost(string $token, string $chatId, Job $job, ?int $automationId = null, bool $generateImage = false, bool $noInlineButtons = false): bool
    {
        $postText  = $this->buildManualSocialPost($job);
        $imagePath = null;
        $companyLogo = null;

        if ($generateImage) {
            try {
                $imagePath = app(JobImageGeneratorService::class)->generate($job);
            } catch (Throwable) {
                $imagePath = null;
            }
        }

        if (! $imagePath && $job->company && ! empty($job->company->logo)) {
            try {
                $logoUrl = RvMedia::getImageUrl($job->company->logo);
                $logoResponse = Http::timeout(20)->get($logoUrl);

                if ($logoResponse->successful()) {
                    $companyLogo = $this->prepareTelegramCompanyLogo($logoResponse->body());
                }
            } catch (Throwable) {
                $companyLogo = null;
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
        } elseif ($companyLogo) {
            $response = Http::timeout(30)
                ->attach('photo', $companyLogo['content'], $companyLogo['filename'])
                ->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                    'chat_id' => $chatId,
                    'caption' => Str::limit($postText, 1020, '…'),
                ]);
        } else {
            $response = Http::timeout(20)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'                  => $chatId,
                'text'                     => $postText,
                'disable_web_page_preview' => true,
            ]);
        }

        if ((! $response->successful() || ! data_get($response->json(), 'ok')) && ($imagePath || $companyLogo)) {
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
            $logoHint = '';
            if (! empty($job->company?->logo)) {
                try {
                    $logoHint = ' Use colours extracted from the company logo at: ' . RvMedia::getImageUrl($job->company->logo) . ' for the background, environment, and clothing.';
                } catch (Throwable) {}
            }
            $aiPrompt = "Generate an ultra-realistic professional African job ad image for: {$job->name} at Wakanda Jobs (wakandajobs.com). Include the job title prominently.{$logoHint} Black African professionals dressed for the role. Clean, trustworthy, corporate feel.";
        }

        try {
            $platformPosts = $this->buildPlatformPosts($job);
        } catch (Throwable) {
            $platformPosts = [];
        }

        $storyboardPrompt = '';
        try {
            $storyboardPrompt = $this->buildStoryboardPrompt($job);
        } catch (Throwable) {}

        $geminiPrompt = '';
        try {
            $geminiPrompt = $this->buildGeminiVideoPrompt($job);
        } catch (Throwable) {}

        $tiktokImagePrompt = '';
        try {
            $tiktokImagePrompt = $this->buildTikTokImagePrompt($job);
        } catch (Throwable) {}

        // Resolve company logo URL for the UI attachment tip
        $companyLogoUrl = null;
        $companyName    = trim((string) ($job->company?->name ?? ''));
        if ($job->company && ! empty($job->company->logo)) {
            try {
                $companyLogoUrl = RvMedia::getImageUrl($job->company->logo);
            } catch (Throwable) {}
        }

        // Does this employer have a contact email on file? Surfaced as a flag button below.
        $hasEmployerEmail = collect($job->company?->contact_emails ?? [])->filter()->isNotEmpty()
            || ! empty($job->company?->email)
            || ! empty($job->apply_email);

        Cache::put($cacheKey, [
            'text'                => $postText,
            'ai_prompt'           => $aiPrompt,
            'tiktok_image_prompt' => $tiktokImagePrompt,
            'storyboard_prompt'   => $storyboardPrompt,
            'gemini_prompt'       => $geminiPrompt,
            'step2_url'           => $step2Url,
            'platform_posts'      => $platformPosts,
            'company_logo_url'    => $companyLogoUrl,
            'company_name'        => $companyName ?: null,
            'job_name'            => trim((string) $job->name),
        ], now()->addDays(7));

        try {
            Http::timeout(20)->post("https://api.telegram.org/bot{$token}/editMessageReplyMarkup", [
                'chat_id'      => $chatId,
                'message_id'   => $messageId,
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '🎨 Step 1: AI Image Prompt', 'url' => $step1Url],
                            [
                                'text' => $hasEmployerEmail ? '📧 Has Email' : '🚫 No Email',
                                'url' => $step1Url,
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (Throwable) {
            // Non-fatal: message sent, button failed — log but continue
        }

        return true;
    }

    private function prepareTelegramCompanyLogo(string $content): ?array
    {
        if ($content === '' || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($content);
        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

        ob_start();
        $encoded = imagejpeg($canvas, null, 90);
        $jpeg = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        if (! $encoded || ! is_string($jpeg) || $jpeg === '') {
            return null;
        }

        return [
            'content' => $jpeg,
            'filename' => 'company-logo.jpg',
        ];
    }

}
