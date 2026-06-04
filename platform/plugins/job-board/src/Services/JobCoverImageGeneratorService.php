<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Job;

/**
 * Generates a 1854×848 landscape banner image for the job detail page cover section.
 *
 * Design: split layout — dark purple gradient left panel with job text,
 * lighter right panel with a large geometric accent and branding.
 */
class JobCoverImageGeneratorService
{
    private const W = 1854;
    private const H = 848;

    private const FONT_BLACK   = '/usr/share/fonts/truetype/lato/Lato-Black.ttf';
    private const FONT_BOLD    = '/usr/share/fonts/truetype/lato/Lato-Bold.ttf';
    private const FONT_REGULAR = '/usr/share/fonts/truetype/lato/Lato-Regular.ttf';

    // Brand palette — same as JobImageGeneratorService
    private const BG_LEFT   = [26,  5,  51];   // #1a0533 deep purple
    private const BG_RIGHT  = [15,  3,  35];   // slightly lighter dark
    private const PURPLE    = [83,  15, 147];
    private const VIOLET    = [124, 58, 237];
    private const LAVENDER  = [196, 181, 253];
    private const WHITE     = [255, 255, 255];
    private const GOLD      = [255, 204, 51];   // accent for company badge

    public function generate(Job $job): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $img = imagecreatetruecolor(self::W, self::H);
        if (! $img) {
            return null;
        }

        imagealphablending($img, true);
        imagesavealpha($img, true);

        $this->drawBackground($img);
        $this->drawRightPanel($img);
        $this->drawDecorativeElements($img);
        $this->drawTopAccentBar($img);
        $this->drawLogo($img);
        $this->drawContent($img, $job);
        $this->drawCompanyLogo($img, $job);
        $this->drawBottomBar($img);

        $tmpPath = sys_get_temp_dir() . '/wj_cover_' . $job->getKey() . '_' . time() . '.jpg';
        imagejpeg($img, $tmpPath, 93);
        imagedestroy($img);

        return file_exists($tmpPath) ? $tmpPath : null;
    }

    // ── Background ────────────────────────────────────────────────────────────

    private function drawBackground(\GdImage $img): void
    {
        // Left ~60% — dark gradient top-to-bottom
        [$tr, $tg, $tb] = self::BG_LEFT;
        $bottomL = [8, 1, 20];

        for ($y = 0; $y < self::H; $y++) {
            $ratio = $y / self::H;
            $r = (int) ($tr + $ratio * ($bottomL[0] - $tr));
            $g = (int) ($tg + $ratio * ($bottomL[1] - $tg));
            $b = (int) ($tb + $ratio * ($bottomL[2] - $tb));
            $c = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, (int)(self::W * 0.62), $y, $c);
        }

        // Right ~38% — slightly different shade
        [$tr, $tg, $tb] = self::BG_RIGHT;
        $bottomR = [5, 1, 15];

        for ($y = 0; $y < self::H; $y++) {
            $ratio = $y / self::H;
            $r = (int) ($tr + $ratio * ($bottomR[0] - $tr));
            $g = (int) ($tg + $ratio * ($bottomR[1] - $tg));
            $b = (int) ($tb + $ratio * ($bottomR[2] - $tb));
            $c = imagecolorallocate($img, $r, $g, $b);
            imageline($img, (int)(self::W * 0.62), $y, self::W, $y, $c);
        }
    }

    private function drawRightPanel(\GdImage $img): void
    {
        // Subtle diagonal divider between left and right panels
        $div = imagecolorallocatealpha($img, self::VIOLET[0], self::VIOLET[1], self::VIOLET[2], 80);
        $splitX = (int)(self::W * 0.62);
        imagefilledrectangle($img, $splitX, 0, $splitX + 3, self::H, $div);
    }

    private function drawDecorativeElements(\GdImage $img): void
    {
        $splitX = (int)(self::W * 0.62);
        $centerX = $splitX + (int)((self::W - $splitX) / 2);

        // Large circle on the right panel
        $c = imagecolorallocatealpha($img, self::PURPLE[0], self::PURPLE[1], self::PURPLE[2], 85);
        imagefilledellipse($img, $centerX + 80, self::H / 2, 520, 520, $c);

        // Smaller accent circle
        $c = imagecolorallocatealpha($img, self::VIOLET[0], self::VIOLET[1], self::VIOLET[2], 95);
        imagefilledellipse($img, $centerX - 60, (int)(self::H * 0.2), 200, 200, $c);

        // Bottom-left decorative dot on the left panel
        $c = imagecolorallocatealpha($img, self::VIOLET[0], self::VIOLET[1], self::VIOLET[2], 100);
        imagefilledellipse($img, 60, self::H + 40, 260, 260, $c);

        // Top-right tiny dot
        $c = imagecolorallocatealpha($img, self::LAVENDER[0], self::LAVENDER[1], self::LAVENDER[2], 110);
        imagefilledellipse($img, (int)(self::W * 0.88), 80, 140, 140, $c);
    }

    private function drawTopAccentBar(\GdImage $img): void
    {
        $c = imagecolorallocate($img, ...self::VIOLET);
        imagefilledrectangle($img, 0, 0, self::W, 7, $c);
    }

    // ── Content ───────────────────────────────────────────────────────────────

    private function drawLogo(\GdImage $img): void
    {
        $white = imagecolorallocate($img, ...self::WHITE);
        imagettftext($img, 22, 0, 60, 55, $white, self::FONT_BLACK, 'WAKANDA JOBS');

        // Small dot separator
        $violet = imagecolorallocate($img, ...self::VIOLET);
        imagefilledellipse($img, 272, 44, 6, 6, $violet);
        imagettftext($img, 14, 0, 282, 51, $violet, self::FONT_REGULAR, 'wakandajobs.com');
    }

    private function drawContent(\GdImage $img, Job $job): void
    {
        $white   = imagecolorallocate($img, ...self::WHITE);
        $lavender = imagecolorallocate($img, ...self::LAVENDER);
        $violet  = imagecolorallocate($img, ...self::VIOLET);
        $gold    = imagecolorallocate($img, ...self::GOLD);

        // "NOW HIRING" label
        $labelBg = imagecolorallocatealpha($img, self::VIOLET[0], self::VIOLET[1], self::VIOLET[2], 60);
        imagefilledroundedrectangle($img, 60, 100, 220, 128, 8, 8, $labelBg);
        imagettftext($img, 13, 0, 72, 120, $white, self::FONT_BOLD, 'NOW HIRING');

        // Job title — large, wrapping at max 2 lines
        $title = mb_strtoupper($job->name);
        $maxW  = (int)(self::W * 0.58) - 80; // left panel width minus padding
        $lines = array_slice($this->wrapText($title, self::FONT_BLACK, 58, $maxW), 0, 2);

        $y = 210;
        foreach ($lines as $line) {
            imagettftext($img, 58, 0, 60, $y, $white, self::FONT_BLACK, $line);
            $y += 72;
        }

        // Underline after title
        $underline = imagecolorallocatealpha($img, self::VIOLET[0], self::VIOLET[1], self::VIOLET[2], 50);
        imagefilledrectangle($img, 60, $y, 500, $y + 3, $underline);
        $y += 28;

        // Company name
        $company = (! ($job->hide_company ?? false)) ? ($job->company?->name ?? '') : '';
        if ($company !== '') {
            imagettftext($img, 26, 0, 60, $y + 6, $lavender, self::FONT_BOLD, $company);
            $y += 46;
        }

        // Location row
        $location = trim($job->location ?: ($job->country?->name ?? ''));
        if ($location !== '') {
            imagettftext($img, 20, 0, 60, $y + 18, $violet, self::FONT_REGULAR, '📍  ' . $location);
            $y += 36;
        }

        // Deadline row
        $deadline = $job->application_closing_date ?? $job->expire_date ?? null;
        if ($deadline) {
            imagettftext($img, 20, 0, 60, $y + 18, $violet, self::FONT_REGULAR, '⏳  Apply by ' . $deadline->format('M j, Y'));
        }
    }

    private function drawCompanyLogo(\GdImage $img, Job $job): void
    {
        if ($job->hide_company ?? false) {
            return;
        }

        $logoPath = null;
        if ($job->company && ! empty($job->company->logo)) {
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $path = $job->company->logo;
            if ($disk->exists($path)) {
                $logoPath = $disk->path($path);
            }
        }

        $splitX  = (int)(self::W * 0.62);
        $centerX = $splitX + (int)((self::W - $splitX) / 2);
        $centerY = (int)(self::H / 2) + 20;

        if ($logoPath && file_exists($logoPath)) {
            $ext  = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $logo = match ($ext) {
                'png'  => @imagecreatefrompng($logoPath),
                'jpg', 'jpeg' => @imagecreatefromjpeg($logoPath),
                'gif'  => @imagecreatefromgif($logoPath),
                'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($logoPath) : false,
                default => false,
            };

            if ($logo) {
                $lw = imagesx($logo);
                $lh = imagesy($logo);
                $maxSize = 260;
                $scale = min($maxSize / $lw, $maxSize / $lh, 1.0);
                $dw = (int)($lw * $scale);
                $dh = (int)($lh * $scale);
                $dx = $centerX - (int)($dw / 2);
                $dy = $centerY - (int)($dh / 2);

                // White rounded bg for logo
                $bgC = imagecolorallocate($img, 255, 255, 255);
                $pad = 20;
                imagefilledroundedrectangle($img, $dx - $pad, $dy - $pad, $dx + $dw + $pad, $dy + $dh + $pad, 16, 16, $bgC);

                imagecopyresampled($img, $logo, $dx, $dy, 0, 0, $dw, $dh, $lw, $lh);
                imagedestroy($logo);
                return;
            }
        }

        // No logo — show company initials in a circle
        $company = $job->company?->name ?? 'WJ';
        $initials = $this->initials($company);
        $circleBg = imagecolorallocate($img, ...self::VIOLET);
        imagefilledellipse($img, $centerX, $centerY, 220, 220, $circleBg);
        $white = imagecolorallocate($img, ...self::WHITE);
        $bbox  = imagettfbbox(52, 0, self::FONT_BLACK, $initials);
        $tw    = abs($bbox[2] - $bbox[0]);
        $th    = abs($bbox[5] - $bbox[3]);
        imagettftext($img, 52, 0, $centerX - (int)($tw / 2), $centerY + (int)($th / 2), $white, self::FONT_BLACK, $initials);
    }

    private function drawBottomBar(\GdImage $img): void
    {
        // Thin branded bottom strip
        $bg = imagecolorallocate($img, 10, 1, 21);
        imagefilledrectangle($img, 0, self::H - 55, self::W, self::H, $bg);

        $border = imagecolorallocate($img, ...self::PURPLE);
        imagefilledrectangle($img, 0, self::H - 57, self::W, self::H - 54, $border);

        $lavender = imagecolorallocate($img, ...self::LAVENDER);
        imagettftext($img, 16, 0, 60, self::H - 20, $lavender, self::FONT_BOLD, 'www.wakandajobs.com');

        $violet = imagecolorallocate($img, ...self::VIOLET);
        $tag    = '#WakandaJobs  #Hiring  #Jobs  #Africa';
        $bbox   = imagettfbbox(13, 0, self::FONT_REGULAR, $tag);
        $tw     = abs($bbox[2] - $bbox[0]);
        imagettftext($img, 13, 0, self::W - $tw - 60, self::H - 20, $violet, self::FONT_REGULAR, $tag);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function wrapText(string $text, string $font, int $size, int $maxWidth): array
    {
        $words   = explode(' ', $text);
        $lines   = [];
        $current = '';

        foreach ($words as $word) {
            $test = $current === '' ? $word : $current . ' ' . $word;
            $bbox = imagettfbbox($size, 0, $font, $test);
            $w    = abs($bbox[2] - $bbox[0]);

            if ($w <= $maxWidth) {
                $current = $test;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $word;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function initials(string $name): string
    {
        $words = array_filter(explode(' ', $name));
        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1));
        }
        return mb_strtoupper(mb_substr($name, 0, 2));
    }
}

// GD doesn't have imagefilledroundedrectangle — polyfill it
if (! function_exists('imagefilledroundedrectangle')) {
    function imagefilledroundedrectangle(\GdImage $img, int $x1, int $y1, int $x2, int $y2, int $rx, int $ry, int $color): void
    {
        imagefilledrectangle($img, $x1 + $rx, $y1, $x2 - $rx, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $ry, $x2, $y2 - $ry, $color);
        imagefilledellipse($img, $x1 + $rx, $y1 + $ry, $rx * 2, $ry * 2, $color);
        imagefilledellipse($img, $x2 - $rx, $y1 + $ry, $rx * 2, $ry * 2, $color);
        imagefilledellipse($img, $x1 + $rx, $y2 - $ry, $rx * 2, $ry * 2, $color);
        imagefilledellipse($img, $x2 - $rx, $y2 - $ry, $rx * 2, $ry * 2, $color);
    }
}
