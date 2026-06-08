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
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class SocialPublisherService
{
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
        $p .= "• DIMENSIONS: Exactly 1854 × 848 pixels — wide landscape, 16:9-ish banner ratio. This will be used as a page-width header image on a job listing page.\n";
        $p .= "• LAYOUT: Horizontal split composition. Left ~60% carries the text and people. Right ~40% carries the company logo, a large subtle decorative circle/shape, and brand colours.\n";
        $p .= "• FEEL: Ultra-realistic, professional corporate photography style — like a high-end recruitment agency header. Clean, modern, inspiring.\n";
        $p .= "\n";

        $p .= "═══ CONTENT REQUIREMENTS ═══\n";
        $p .= "• Show Black African professionals in a realistic work environment suitable for the role '{$title}'. The people should look confident, aspirational, and engaged.\n";
        $p .= "• LEFT PANEL TEXT OVERLAYS (use clean white or lavender text on the dark background):\n";
        $p .= "    — Large bold heading: \"{$title}\"\n";
        if ($company) $p .= "    — Subheading: \"at {$company}\"\n";
        if ($details) $p .= "    — Detail row: \"" . implode('  ·  ', $details) . "\"\n";
        $p .= "    — Small CTA strip at bottom: \"Apply at wakandajobs.com\"\n";
        $p .= "\n";

        if ($companyLogoUrl) {
            $p .= "═══ COMPANY LOGO BRANDING ═══\n";
            $p .= $this->companyLogoLine($company, $companyLogoUrl) . "\n";
            $p .= "• Place the real attached logo prominently in the RIGHT PANEL — centred, on a white or light card/pill background so it pops against the dark scene.\n";
            $p .= "• Extract the dominant colours from the attached logo and use them as accent colours throughout the image (background tones, overlays, border highlights).\n";
            $p .= "\n";
        } else {
            if ($company) {
                $p .= "No logo is available for '{$company}'. In the right panel, render a clean typographic badge or monogram with the company initials in a rounded rectangle — use the Wakanda Jobs violet palette.\n\n";
            }
        }

        $p .= "═══ WAKANDA JOBS BRANDING ═══\n";
        $p .= $this->wakandaLogoLine() . "\n";
        $p .= "• Place the Wakanda Jobs logo small in the bottom-left corner or as a watermark.\n";
        $p .= "• Background palette: deep dark purple (#1a0533 → #0d0219 gradient) as the primary dark tone.\n";
        $p .= "• Accent: violet (#7c3aed) thin top bar, lavender (#c4b5fd) for secondary text.\n";
        $p .= "\n";

        if ($flagColors) {
            $p .= "═══ COUNTRY ACCENT ═══\n";
            $p .= "• Add a very subtle {$country} flag-colour strip (colours: {$flagColors}) as a thin bottom border — tasteful, not dominant.\n\n";
        }

        $p .= "═══ WHAT NOT TO DO ═══\n";
        $p .= "• Do NOT generate a portrait or square image — must be wide landscape 1854×848.\n";
        $p .= "• Do NOT make the image look like a social media story — it is a webpage header/banner.\n";
        $p .= "• Do NOT use stock-photo clip-art style. Aim for authentic photorealistic quality.\n";
        $p .= "• Do NOT crowd the image with text — keep text minimal, large, and legible.\n";

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
        $url      = route('public.job', $job->slugable?->key ?? $job->id);
        $excerpt  = trim(Str::limit(strip_tags((string) ($job->description ?: $job->content)), 220));

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
        $linkedin  = "🏷️ Position: {$title}\n";
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
        $facebook  = "🏷️ Position: {$title}\n";
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
        return $this->withPublerPublishLock(
            fn () => $this->publerPostUnlocked(
                $job,
                $apiKey,
                $accountIds,
                $workspaceId,
                $preferredImageField,
                $excludeNetworks
            )
        );
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
            'instagram' => $posts['facebook']  ?? null,
        ];
        $defaultText = $posts['facebook'] ?? $this->buildJobMessage($job);

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
            Log::warning('Publer post failed', [
                'job_id' => $job->getKey(),
                'error'  => $error,
            ]);
        }

        return $success;
    }

    private function withPublerPublishLock(callable $callback): mixed
    {
        return Cache::lock('job-board:publer-publish', 1200)->block(900, function () use ($callback) {
            try {
                return $callback();
            } finally {
                // LinkedIn rejects consecutive posts to one account without a one-minute gap.
                sleep(61);
            }
        });
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
        $this->withPublerPublishLock(function () use ($job, &$results): void {
            $this->postToPublerCountryMappingUnlocked($job, $results);
        });
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
        $defaultText = $posts['facebook'] ?? $this->buildJobMessage($job);

        $networkTextMap = [
            'facebook'  => $posts['facebook']  ?? $defaultText,
            'linkedin'  => $posts['linkedin']  ?? $defaultText,
            'tiktok'    => $posts['tiktok']    ?? $defaultText,
            'twitter'   => $posts['twitter']   ?? $defaultText,
            'instagram' => $posts['facebook']  ?? $defaultText,
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
            foreach ($publerPosts as $publerPost) {
                [$postSuccess, $postError] = $this->publerPublishAndWait($apiKey, $workspaceId, [
                    'bulk' => [
                        'state' => 'scheduled',
                        'posts' => [$publerPost],
                    ],
                ]);

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
        $token      = trim((string) ($settings['token'] ?? ''));
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

    // -------------------------------------------------------------------------
    // WhatsApp Channel via Whapi.io
    // -------------------------------------------------------------------------

    protected function postToWhapiChannel(SocialAutomation $automation, Job $job): bool
    {
        $settings   = $automation->settings ?? [];
        $token      = trim((string) ($settings['token'] ?? ''));
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
        $message = $posts['whatsapp'] ?? $this->buildJobMessage($job);

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
