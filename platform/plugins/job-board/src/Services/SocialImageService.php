<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\PublerCategoryTemplate;
use Botble\JobBoard\Models\PublerCategoryTemplateCategory;
use Botble\JobBoard\Models\PublerCountryMapping;
use Botble\Media\Facades\RvMedia;
use Throwable;

/**
 * Generates branded job-card images using PHP GD + FreeType.
 * No ImageMagick or FFmpeg required.
 *
 * Outputs a JPEG to public/social-gen/ (publicly accessible for Publer URL upload),
 * then returns [local_path, public_url]. Caller must call cleanup() after Publer
 * confirms the upload.
 */
class SocialImageService
{
    // Lato font stack (system-installed)
    private const FONT_BLACK   = '/usr/share/fonts/truetype/lato/Lato-Black.ttf';
    private const FONT_BOLD    = '/usr/share/fonts/truetype/lato/Lato-Bold.ttf';
    private const FONT_REGULAR = '/usr/share/fonts/truetype/lato/Lato-Regular.ttf';

    // Fallback (always present)
    private const FONT_FALLBACK = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

    /**
     * Generate a job card for a country mapping.
     *
     * @param  'square'|'vertical'  $format
     * @return array{0: string, 1: string}|null  [local_path, public_url]
     */
    public function generateForJob(Job $job, PublerCountryMapping $mapping, string $format = 'square'): ?array
    {
        $storedPath = $this->resolveBackgroundPath($job, $format);

        if (! $storedPath) {
            return null;
        }

        $absPath = $this->resolveStoragePath($storedPath);
        if (! $absPath || ! file_exists($absPath)) {
            return null;
        }

        $canvas = $this->loadImage($absPath);
        if (! $canvas) {
            return null;
        }

        $w          = imagesx($canvas);
        $h          = imagesy($canvas);
        $isVertical = $h > $w;

        $this->drawJobOverlay($canvas, $job, $w, $h, $mapping->text_color ?: '#FFFFFF', (int) ($mapping->overlay_opacity ?? 55), $isVertical);

        // Top-left — country logo (same public/country_logos/ + code map the homepage
        // hero banner uses), falling back to the Wakanda Jobs theme logo when the
        // job's country has no dedicated artwork.
        $countryLogoAbs = $this->resolveCountryLogoPath($job->country?->code)
            ?? RvMedia::getRealPath(theme_option('logo'));
        if ($countryLogoAbs && file_exists($countryLogoAbs)) {
            $this->compositeImage($canvas, $countryLogoAbs, $w, $h, 'top-left');
        }

        // Company logo — top-right
        if ($job->company && $job->company->logo) {
            $companyLogoAbs = RvMedia::getRealPath($job->company->logo);
            if ($companyLogoAbs && file_exists($companyLogoAbs)) {
                $this->compositeImage($canvas, $companyLogoAbs, $w, $h, 'top-right');
            }
        }

        // Wakanda Jobs branding watermark — bottom-centre — prefer the country
        // mapping's upload, falling back to the site's theme logo.
        $wmLogoAbs = $mapping->wm_logo ? $this->resolveStoragePath($mapping->wm_logo) : null;
        if (! $wmLogoAbs || ! file_exists($wmLogoAbs)) {
            $wmLogoAbs = RvMedia::getRealPath(theme_option('logo'));
        }
        if ($wmLogoAbs && file_exists($wmLogoAbs)) {
            $this->compositeImage($canvas, $wmLogoAbs, $w, $h, 'bottom-center');
        }

        // Subtle country-flag accent band along the very bottom edge — a quiet nod
        // to where the job is based, same idea as the flag-colour strip in our
        // AI-generated social ad prompts (see getFlagColorHexes()).
        $flagColors = $this->getFlagColorHexes($job->country?->name);
        if ($flagColors) {
            $this->drawFlagAccentBand($canvas, $w, $h, $flagColors);
        }

        [$outPath, $url] = $this->saveToPublic($canvas, $job->id, $format);
        imagedestroy($canvas);

        return [$outPath, $url];
    }

    /**
     * Ready-to-paste prompt for an AI image generator (Midjourney, Gemini, DALL·E, etc.)
     * to produce a category background photo. Composition guidance keeps the area where
     * job text gets overlaid (handled by drawJobText/drawGradientOverlay) visually calm.
     */
    public function buildCategoryBackgroundPrompt(string $themeName, string $format): string
    {
        $isVertical = $format === 'vertical';
        $dimensions = $isVertical ? '1080×1920px (9:16 vertical, for TikTok)' : '1080×1080px (1:1 square, for Facebook & LinkedIn)';
        $textZone   = $isVertical
            ? 'the lower half of the frame (where the job title, the icon-badge detail rows — Company, Location, Type, Deadline — and the Wakanda Jobs watermark will be overlaid)'
            : 'the lower third of the frame (where the job title, the icon-badge detail rows — Company, Location, Type, Deadline — and the Wakanda Jobs watermark will be overlaid)';

        return <<<PROMPT
        A professional, photorealistic high-resolution photograph for a job-board social media graphic.

        Theme / setting: {$themeName} — show a real, authentic working environment that represents this field in an African context (natural skin tones, modern but realistic settings, no stock-photo clichés).

        Format: {$dimensions}.

        Composition: keep {$textZone} visually calm and uncluttered — fewer busy details, softer tones — since white text will be overlaid there with a dark gradient. Place the main subject/visual interest in the upper portion of the frame.

        Style: warm natural lighting, shallow depth of field, editorial/documentary photography look, vibrant but not oversaturated colours. Wide enough framing to feel spacious.

        Strictly avoid: any text, letters, numbers, logos, watermarks, borders, or UI elements baked into the image; people looking directly at the camera; cartoon/illustrated/3D-render styles; cluttered or busy backgrounds.
        PROMPT;
    }

    public function cleanup(string $localPath): void
    {
        if (file_exists($localPath)) {
            @unlink($localPath);
        }
    }

    /**
     * Background images are sourced from the job's primary category template.
     * Returns null if the category has no active template for the requested format
     * (image generation is then skipped — no country-level fallback).
     */
    private function resolveBackgroundPath(Job $job, string $format): ?string
    {
        $categoryIds = $job->categories?->pluck('id')->all() ?: [];
        if (! $categoryIds) {
            return null;
        }

        $link = PublerCategoryTemplateCategory::query()
            ->whereIn('category_id', $categoryIds)
            ->whereHas('template', fn ($q) => $q->where('is_active', true))
            ->with('template')
            ->first();

        $template = $link?->template;
        if (! $template instanceof PublerCategoryTemplate) {
            return null;
        }

        return $format === 'vertical' ? $template->template_vertical : $template->template_square;
    }

    // ── Image generation ─────────────────────────────────────────────────────

    /**
     * Renders the dark gradient + job details as a single bottom-anchored block.
     * The block's content (title length, optional fields) is measured first so its
     * height is known up front — then the gradient is sized to exactly cover it and
     * the text is drawn from a consistent baseline above the watermark. This keeps
     * the photo's subject clear and avoids both a visible "gradient seam" and an
     * empty gap under short job titles.
     */
    private function drawJobOverlay(\GdImage $canvas, Job $job, int $w, int $h, string $hexColor, int $overlayOpacityPct, bool $isVertical): void
    {
        $pad   = (int) ($w * 0.065);
        $maxTW = $w - ($pad * 2);

        [$r, $g, $b] = $this->hexToRgb($hexColor);
        $textColor = imagecolorallocatealpha($canvas, $r, $g, $b, 0);
        $muted     = imagecolorallocatealpha($canvas, 205, 208, 214, 0);
        $gold      = imagecolorallocatealpha($canvas, 255, 199, 0, 0);

        // ── Sizes (vertical / square) ────────────────────────────────────────
        $badgeSize = $isVertical ? 27 : 20;
        $titleSize = $isVertical ? 58 : 44;
        $metaSize  = $isVertical ? 30 : 23;

        $titleLineH = (int) ($titleSize * 1.18);
        $metaLineH  = (int) ($metaSize * 1.4);

        $font = [
            'black'   => $this->font('black'),
            'bold'    => $this->font('bold'),
            'regular' => $this->font('regular'),
        ];

        // ── Measure content ──────────────────────────────────────────────────
        $titleLines = $this->wrapText($job->name ?? 'Job', $titleSize, $font['black'], $maxTW);

        $company  = $job->company?->name;
        $location = $this->cleanLocation($job);
        $salary   = $this->displaySalary($job);
        $deadline = $job->application_closing_date ?: $job->expire_date;
        $jobType  = $job->jobTypes->pluck('name')->filter()->unique()->implode(' / ') ?: null;

        // Icon-row detail list — "icon badge + Label: value" per fact, the layout
        // used in polished job-card graphics (company logo, headline, then a clean
        // scannable list of Company / Location / Type / Deadline).
        $rows = [];
        if ($company)  $rows[] = ['icon' => 'building', 'label' => 'Company',  'value' => $company];
        if ($location) $rows[] = ['icon' => 'pin',      'label' => 'Location', 'value' => $location];
        if ($jobType) {
            $rows[] = ['icon' => 'clock', 'label' => 'Type', 'value' => $jobType];
        } elseif ($salary) {
            $rows[] = ['icon' => 'tag', 'label' => 'Salary', 'value' => $salary];
        }
        if ($deadline) $rows[] = ['icon' => 'calendar', 'label' => 'Deadline', 'value' => $deadline->format('j M Y')];

        $iconSize = $isVertical ? 50 : 38;
        $rowGap   = (int) ($iconSize * 0.42);
        $rowH     = max($iconSize, $metaLineH) + $rowGap;

        $badgeToTitleGap = (int) ($titleSize * 1.15);       // clears the title's tall ascender under the small badge

        $blockHeight = $badgeToTitleGap
            + count($titleLines) * $titleLineH
            + (int) ($titleSize * 0.4)                      // gap after title
            + count($rows) * $rowH;

        // ── Bottom-anchor the block above the watermark, never crowding the subject ──
        $bottomSafe   = (int) ($h * ($isVertical ? 0.115 : 0.135));
        $minTop       = (int) ($h * ($isVertical ? 0.36 : 0.42));
        $y            = max($minTop, $h - $bottomSafe - $blockHeight);
        $gradientFrom = max(0, $y - (int) ($h * 0.07));

        $this->drawSmoothGradient($canvas, $w, $h, $gradientFrom, $overlayOpacityPct);

        // ── Badge (tracked-out eyebrow label) + short accent underline ───────
        $badgeText = 'NEW JOB ALERT';
        $tracking  = (int) ($badgeSize * 0.3);
        $accentW   = $this->textTracked($canvas, $badgeText, $badgeSize, $font['bold'], $pad, $y, $gold, $tracking);
        imagefilledrectangle($canvas, $pad, $y + (int) ($badgeSize * 0.55), $pad + $accentW, $y + (int) ($badgeSize * 0.55) + 3, $gold);
        $y += $badgeToTitleGap;

        // ── Job title ─────────────────────────────────────────────────────────
        foreach ($titleLines as $line) {
            $this->text($canvas, $line, $titleSize, $font['black'], $pad, $y, $textColor);
            $y += $titleLineH;
        }
        $y += (int) ($titleSize * 0.4);

        // ── Detail rows: violet icon badge + "Label:  value" ─────────────────
        $accent    = imagecolorallocatealpha($canvas, 124, 58, 237, 0); // Wakanda Jobs violet (#7c3aed)
        $white     = imagecolorallocatealpha($canvas, 255, 255, 255, 0);
        $valueMaxW = $maxTW - $iconSize - (int) ($iconSize * 0.35);

        foreach ($rows as $row) {
            $iconCx = $pad + (int) ($iconSize / 2);
            $iconCy = $y + (int) ($iconSize / 2);
            $this->drawIconBadge($canvas, $row['icon'], $iconCx, $iconCy, $iconSize, $accent, $white);

            $textX  = $pad + $iconSize + (int) ($iconSize * 0.35);
            $textY  = $iconCy + (int) ($metaSize * 0.36);
            $label  = $row['label'] . ':  ';
            $lbox   = imagettfbbox($metaSize, 0, $font['bold'], $label);
            $labelW = $lbox[2] - $lbox[0];
            $value  = $this->truncateToWidth($row['value'], $metaSize, $font['regular'], max(40, $valueMaxW - $labelW));

            $this->text($canvas, $label, $metaSize, $font['bold'], $textX, $textY, $textColor);
            $this->text($canvas, $value, $metaSize, $font['regular'], $textX + $labelW, $textY, $muted);

            $y += $rowH;
        }
    }

    /**
     * Draws a small rounded-square accent badge with a flat-style glyph centred inside —
     * the "icon + label: value" row look used in polished job-card social graphics.
     */
    private function drawIconBadge(\GdImage $canvas, string $icon, int $cx, int $cy, int $size, int $bgColor, int $fgColor): void
    {
        $r = (int) ($size / 2);
        $this->filledRoundedRect($canvas, $cx - $r, $cy - $r, $cx + $r, $cy + $r, (int) ($r * 0.45), $bgColor);

        $g = (int) ($size * 0.27); // glyph unit

        switch ($icon) {
            case 'building':
                imagefilledrectangle($canvas, $cx - $g, $cy - $g, $cx + $g, $cy + $g, $fgColor);
                foreach ([-1, 1] as $row) {
                    foreach ([-1, 1] as $col) {
                        $wx = $cx + $col * (int) ($g * 0.5);
                        $wy = $cy + $row * (int) ($g * 0.5);
                        $ws = (int) ($g * 0.32);
                        imagefilledrectangle($canvas, $wx - $ws, $wy - $ws, $wx + $ws, $wy + $ws, $bgColor);
                    }
                }
                break;

            case 'pin':
                $headCy = $cy - (int) ($g * 0.35);
                imagefilledellipse($canvas, $cx, $headCy, (int) ($g * 1.7), (int) ($g * 1.7), $fgColor);
                imagefilledpolygon($canvas, [
                    $cx - (int) ($g * 0.85), $headCy + (int) ($g * 0.2),
                    $cx + (int) ($g * 0.85), $headCy + (int) ($g * 0.2),
                    $cx, $cy + (int) ($g * 1.5),
                ], $fgColor);
                imagefilledellipse($canvas, $cx, $headCy, (int) ($g * 0.7), (int) ($g * 0.7), $bgColor);
                break;

            case 'calendar':
                $top    = $cy - (int) ($g * 1.1);
                $bottom = $cy + (int) ($g * 1.1);
                $this->filledRoundedRect($canvas, $cx - $g, $top, $cx + $g, $bottom, (int) ($g * 0.25), $fgColor);
                imagefilledrectangle($canvas, $cx - $g, $top, $cx + $g, $top + (int) ($g * 0.6), $bgColor);
                imagefilledrectangle($canvas, $cx - (int) ($g * 0.6), $top - (int) ($g * 0.35), $cx - (int) ($g * 0.3), $top + (int) ($g * 0.15), $fgColor);
                imagefilledrectangle($canvas, $cx + (int) ($g * 0.3), $top - (int) ($g * 0.35), $cx + (int) ($g * 0.6), $top + (int) ($g * 0.15), $fgColor);
                break;

            case 'clock':
                imagefilledellipse($canvas, $cx, $cy, (int) ($g * 2.2), (int) ($g * 2.2), $fgColor);
                imagefilledellipse($canvas, $cx, $cy, (int) ($g * 1.7), (int) ($g * 1.7), $bgColor);
                imagesetthickness($canvas, max(2, (int) ($g * 0.22)));
                imageline($canvas, $cx, $cy, $cx, $cy - (int) ($g * 1.1), $fgColor);
                imageline($canvas, $cx, $cy, $cx + (int) ($g * 0.8), $cy, $fgColor);
                imagesetthickness($canvas, 1);
                break;

            case 'tag':
                imagefilledpolygon($canvas, [
                    $cx - $g,               $cy - (int) ($g * 0.9),
                    $cx + (int) ($g * 0.4), $cy - (int) ($g * 0.9),
                    $cx + $g,               $cy,
                    $cx + (int) ($g * 0.4), $cy + (int) ($g * 0.9),
                    $cx - $g,               $cy + (int) ($g * 0.9),
                ], $fgColor);
                imagefilledellipse($canvas, $cx - (int) ($g * 0.5), $cy, (int) ($g * 0.55), (int) ($g * 0.55), $bgColor);
                break;

            default: // globe — used for the "Apply at wakandajobs.com" footer mark
                imagefilledellipse($canvas, $cx, $cy, (int) ($g * 2.2), (int) ($g * 2.2), $fgColor);
                imagefilledellipse($canvas, $cx, $cy, (int) ($g * 1.0), (int) ($g * 2.2), $bgColor);
                imagesetthickness($canvas, max(2, (int) ($g * 0.2)));
                imageline($canvas, $cx - $g, $cy, $cx + $g, $cy, $bgColor);
                imagesetthickness($canvas, 1);
                break;
        }
    }

    /** Fills a rectangle with rounded corners (rect ∪ four corner circles — clean for small radii). */
    private function filledRoundedRect(\GdImage $canvas, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        $radius = max(0, min($radius, (int) (($x2 - $x1) / 2), (int) (($y2 - $y1) / 2)));

        imagefilledrectangle($canvas, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($canvas, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        if ($radius > 0) {
            imagefilledellipse($canvas, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($canvas, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($canvas, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($canvas, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        }
    }

    /** Shortens $text with a trailing "…" until it fits within $maxWidth pixels. */
    private function truncateToWidth(string $text, int $size, string $font, int $maxWidth): string
    {
        $bbox = imagettfbbox($size, 0, $font, $text);
        if (($bbox[2] - $bbox[0]) <= $maxWidth) {
            return $text;
        }

        while (mb_strlen($text) > 1) {
            $text = mb_substr($text, 0, -1);
            $bbox = imagettfbbox($size, 0, $font, $text . '…');
            if (($bbox[2] - $bbox[0]) <= $maxWidth) {
                return $text . '…';
            }
        }

        return $text . '…';
    }

    /** Smooth single-direction fade from transparent to $opacityPct% black, eased for a natural look. */
    private function drawSmoothGradient(\GdImage $canvas, int $w, int $h, int $startY, int $opacityPct): void
    {
        $opacityPct = max(0, min(90, $opacityPct));
        $startY     = max(0, min($startY, $h - 1));
        $zoneHeight = $h - $startY;

        if ($zoneHeight <= 0 || $opacityPct <= 0) {
            return;
        }

        imagealphablending($canvas, true);

        $steps = (int) min(100, $zoneHeight);
        for ($i = 0; $i < $steps; $i++) {
            $rowStart = $startY + (int) floor($zoneHeight * $i / $steps);
            $rowEnd   = $startY + (int) floor($zoneHeight * ($i + 1) / $steps);

            $progress  = $steps > 1 ? $i / ($steps - 1) : 1;
            $eased     = $progress ** 1.6; // ease-in: stays light near the subject, deepens toward the bottom
            $alphaPct  = $eased * $opacityPct;
            $alpha     = (int) round(127 - ($alphaPct / 100) * 127);
            $alpha     = max(0, min(127, $alpha));

            $color = imagecolorallocatealpha($canvas, 0, 0, 0, $alpha);
            imagefilledrectangle($canvas, 0, $rowStart, $w, max($rowStart, $rowEnd - 1), $color);
        }
    }

    /**
     * Some crawled jobs store duplicated/free-text addresses (e.g. "South Africa, South Africa").
     * Collapse repeated comma-separated parts so the overlay doesn't echo the same place twice.
     */
    private function cleanLocation(Job $job): ?string
    {
        $raw = trim((string) ($job->getLocationAttribute() ?: $job->address ?: ($job->country?->name ?? '')));

        if ($raw === '') {
            return null;
        }

        $parts  = array_map('trim', explode(',', $raw));
        $unique = [];
        foreach ($parts as $part) {
            if ($part !== '' && ! in_array($part, $unique, true)) {
                $unique[] = $part;
            }
        }

        return implode(', ', $unique);
    }

    /**
     * Hex flag colours for the countries we operate in — paints the subtle bottom
     * accent band. Mirrors the country list in SocialPublisherService::getFlagColors()
     * (used for AI-prompt wording), but as drawable hex values instead of descriptions.
     *
     * @return string[]|null
     */
    private function getFlagColorHexes(?string $country): ?array
    {
        if (! $country) {
            return null;
        }

        $map = [
            'zambia'       => ['#198A00', '#EF7D00', '#000000', '#DE2010'],
            'zimbabwe'     => ['#319208', '#FFD200', '#DE2010', '#000000', '#FFFFFF'],
            'south africa' => ['#DE3831', '#FFFFFF', '#002395', '#007A4D', '#FFB81C', '#000000'],
            'kenya'        => ['#000000', '#BB0000', '#FFFFFF', '#006600'],
            'nigeria'      => ['#008751', '#FFFFFF', '#008751'],
            'ghana'        => ['#CE1126', '#FCD116', '#006B3F'],
            'tanzania'     => ['#1EB53A', '#FCD116', '#000000', '#00A3DD'],
            'uganda'       => ['#000000', '#FCDC04', '#D90000'],
            'rwanda'       => ['#00A1DE', '#FAD201', '#20603D'],
            'malawi'       => ['#000000', '#CE1126', '#21B14C'],
            'mozambique'   => ['#00A859', '#FFFFFF', '#000000', '#FFD200', '#D21034'],
            'botswana'     => ['#75AADB', '#FFFFFF', '#000000'],
            'namibia'      => ['#003580', '#D21034', '#009543', '#FFFFFF', '#FFCE00'],
            'ethiopia'     => ['#078930', '#FCDD09', '#DA121A', '#0F47AF'],
            'cameroon'     => ['#007A5E', '#CE1126', '#FCD116'],
            'senegal'      => ['#00853F', '#FDEF42', '#E31B23'],
            'ivory coast'  => ['#F77F00', '#FFFFFF', '#009E60'],
            'angola'       => ['#CC092F', '#000000', '#FFCB00'],
            'madagascar'   => ['#FFFFFF', '#FC3D32', '#007E3A'],
            'mauritius'    => ['#EA2839', '#1A206D', '#FFD500', '#00A551'],
        ];

        return $map[strtolower(trim($country))] ?? null;
    }

    /**
     * Resolves a job's country to one of the artworks in public/country_logos/ —
     * the exact code → filename map used for the homepage hero banner
     * (see search-box.blade.php $countryLogoMap), so both features stay in sync.
     */
    private function resolveCountryLogoPath(?string $countryCode): ?string
    {
        if (! $countryCode) {
            return null;
        }

        $map = [
            'BW' => 'botswana',
            'CM' => 'cameroon',
            'GH' => 'ghana',
            'KE' => 'kenya',
            'MW' => 'malawi',
            'MA' => 'morocco',
            'NG' => 'nigeria',
            'RW' => 'rwanda',
            'ZA' => 'southafrica',
            'UG' => 'uganda',
            'ZM' => 'zambia',
            'ZW' => 'zimbabwe',
            'MU' => 'mauritius',
        ];

        $slug = $map[strtoupper(trim($countryCode))] ?? null;
        if (! $slug) {
            return null;
        }

        $path = public_path('country_logos/' . $slug . '.jpeg');

        return file_exists($path) ? $path : null;
    }

    /** Thin multi-segment colour band along the very bottom edge — a quiet flag-colour nod to the job's country. */
    private function drawFlagAccentBand(\GdImage $canvas, int $w, int $h, array $hexColors): void
    {
        $bandH = max(4, (int) ($h * 0.012));
        $top   = $h - $bandH;
        $count = count($hexColors);
        $segW  = (int) ceil($w / $count);

        foreach ($hexColors as $i => $hex) {
            [$r, $g, $b] = $this->hexToRgb($hex);
            $color = imagecolorallocate($canvas, $r, $g, $b);
            $x1    = $i * $segW;
            $x2    = min($w, $x1 + $segW) - 1;
            imagefilledrectangle($canvas, $x1, $top, $x2, $h - 1, $color);
        }
    }

    private function displaySalary(Job $job): ?string
    {
        try {
            if ($job->hide_salary || ! $job->salary_text) {
                return null;
            }

            $salary = (string) $job->salary_text;

            return in_array(strtolower($salary), ['attractive', 'negotiable', 'competitive', ''], true) ? null : $salary;
        } catch (Throwable) {
            return null;
        }
    }

    /** Wrapper for imagettftext with a subtle drop-shadow. */
    private function text(\GdImage $img, string $str, int $size, string $font, int $x, int $y, int $color): void
    {
        $shadow = imagecolorallocatealpha($img, 0, 0, 0, 60);
        imagettftext($img, $size, 0, $x + 2, $y + 2, $shadow, $font, $str);
        imagettftext($img, $size, 0, $x, $y, $color, $font, $str);
    }

    /** Draws text letter-by-letter with extra tracking — the tracked-out small-caps look used for badges/eyebrows in polished AI-generated social graphics. Returns the total rendered width. */
    private function textTracked(\GdImage $img, string $str, int $size, string $font, int $x, int $y, int $color, int $trackingPx): int
    {
        $shadow  = imagecolorallocatealpha($img, 0, 0, 0, 60);
        $cursor  = $x;
        $letters = mb_str_split($str);

        foreach ($letters as $letter) {
            imagettftext($img, $size, 0, $cursor + 2, $y + 2, $shadow, $font, $letter);
            imagettftext($img, $size, 0, $cursor, $y, $color, $font, $letter);

            $bbox = imagettfbbox($size, 0, $font, $letter);
            $cursor += ($bbox[2] - $bbox[0]) + ($letter === ' ' ? (int) ($trackingPx * 1.5) : $trackingPx);
        }

        return $cursor - $x;
    }

    /** Word-wrap text to fit $maxWidth pixels; returns array of lines (max 3). */
    private function wrapText(string $text, int $size, string $font, int $maxWidth): array
    {
        $words   = explode(' ', $text);
        $lines   = [];
        $current = '';

        foreach ($words as $word) {
            $test = $current === '' ? $word : "$current $word";
            $bbox = imagettfbbox($size, 0, $font, $test);
            if (($bbox[2] - $bbox[0]) > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
                if (count($lines) >= 2) {
                    break; // will append remaining in last line
                }
            } else {
                $current = $test;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 3);
    }

    private function compositeImage(\GdImage $canvas, string $logoPath, int $cW, int $cH, string $position): void
    {
        $logo = $this->loadImage($logoPath);
        if (! $logo) {
            return;
        }

        $lW = imagesx($logo);
        $lH = imagesy($logo);

        $maxW = (int) ($cW * 0.22);
        if ($lW > $maxW) {
            $ratio   = $maxW / $lW;
            $newW    = $maxW;
            $newH    = (int) ($lH * $ratio);
            $scaled  = imagecreatetruecolor($newW, $newH);
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
            $trans = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
            imagefilledrectangle($scaled, 0, 0, $newW, $newH, $trans);
            imagecopyresampled($scaled, $logo, 0, 0, 0, 0, $newW, $newH, $lW, $lH);
            imagedestroy($logo);
            $logo = $scaled;
            $lW   = $newW;
            $lH   = $newH;
        }

        $margin = (int) ($cW * 0.04);
        [$dX, $dY] = match ($position) {
            'top-right'    => [$cW - $lW - $margin, $margin],
            'top-left'     => [$margin, $margin],
            'bottom-right' => [$cW - $lW - $margin, $cH - $lH - $margin],
            'bottom-left'  => [$margin, $cH - $lH - $margin],
            default        => [(int) (($cW - $lW) / 2), $cH - $lH - $margin],
        };

        imagecopy($canvas, $logo, $dX, $dY, 0, 0, $lW, $lH);
        imagedestroy($logo);
    }

    // ── File helpers ──────────────────────────────────────────────────────────

    private function loadImage(string $path): ?\GdImage
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path) ?: null,
            'png'         => @imagecreatefrompng($path)  ?: null,
            'webp'        => @imagecreatefromwebp($path) ?: null,
            default       => null,
        };
    }

    /**
     * Resolve a stored relative path to an absolute filesystem path.
     * Files are stored under public/social-templates/ (persistent).
     */
    private function resolveStoragePath(string $path): ?string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Stored as relative to public_path
        $candidate = public_path($path);
        if (file_exists($candidate)) {
            return $candidate;
        }

        // Stored as relative to storage/app/public
        $candidate2 = storage_path('app/public/' . $path);
        if (file_exists($candidate2)) {
            return $candidate2;
        }

        return null;
    }

    private function saveToPublic(\GdImage $canvas, int|string $jobId, string $format): array
    {
        $dir = public_path('social-gen');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filename = 'job_' . $jobId . '_' . $format . '_' . substr(md5(uniqid()), 0, 8) . '.jpg';
        $path     = $dir . '/' . $filename;

        imagejpeg($canvas, $path, 90);

        return [$path, url('social-gen/' . $filename)];
    }

    private function font(string $weight): string
    {
        $map = [
            'black'   => self::FONT_BLACK,
            'bold'    => self::FONT_BOLD,
            'regular' => self::FONT_REGULAR,
        ];

        $path = $map[$weight] ?? self::FONT_BOLD;
        return file_exists($path) ? $path : self::FONT_FALLBACK;
    }

    /** @return int[] [r, g, b] */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
