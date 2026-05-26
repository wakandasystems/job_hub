<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Job;

class JobImageGeneratorService
{
    private const W = 1200;
    private const H = 630;

    private const FONT_BLACK   = '/usr/share/fonts/truetype/lato/Lato-Black.ttf';
    private const FONT_BOLD    = '/usr/share/fonts/truetype/lato/Lato-Bold.ttf';
    private const FONT_REGULAR = '/usr/share/fonts/truetype/lato/Lato-Regular.ttf';
    private const LOGO_PATH    = '/var/www/jobs/storage/app/public/general/logo-light.png';

    // Brand palette
    private const BG_TOP    = [26,  5,  51];   // #1a0533
    private const BG_BOTTOM = [13,  2,  25];   // #0d0219
    private const PURPLE    = [83,  15, 147];   // #530f93
    private const VIOLET    = [124, 58, 237];   // #7c3aed
    private const LAVENDER  = [196, 181, 253];  // #c4b5fd
    private const WHITE     = [255, 255, 255];
    private const FOOTER_BG = [10,  1,  21];    // #0a0115

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
        $this->drawDecorativeCircles($img);
        $this->drawTopBar($img);
        $this->drawLogo($img);
        $this->drawContent($img, $job);
        $this->drawFooter($img);

        $tmpPath = sys_get_temp_dir() . '/wj_job_' . $job->getKey() . '_' . time() . '.jpg';
        imagejpeg($img, $tmpPath, 92);
        imagedestroy($img);

        return file_exists($tmpPath) ? $tmpPath : null;
    }

    // -------------------------------------------------------------------------

    private function drawBackground(\GdImage $img): void
    {
        [$tr, $tg, $tb] = self::BG_TOP;
        [$br, $bg, $bb] = self::BG_BOTTOM;

        for ($y = 0; $y < self::H; $y++) {
            $ratio = $y / self::H;
            $r = (int) ($tr + $ratio * ($br - $tr));
            $g = (int) ($tg + $ratio * ($bg - $tg));
            $b = (int) ($tb + $ratio * ($bb - $tb));
            $c = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, self::W, $y, $c);
        }
    }

    private function drawDecorativeCircles(\GdImage $img): void
    {
        // Large circle top-right
        $c = imagecolorallocatealpha($img, self::PURPLE[0], self::PURPLE[1], self::PURPLE[2], 90);
        imagefilledellipse($img, 1150, -50, 520, 520, $c);

        // Medium circle bottom-left
        $c = imagecolorallocatealpha($img, self::VIOLET[0], self::VIOLET[1], self::VIOLET[2], 100);
        imagefilledellipse($img, -40, 680, 380, 380, $c);

        // Small accent dot mid-right
        $c = imagecolorallocatealpha($img, self::LAVENDER[0], self::LAVENDER[1], self::LAVENDER[2], 110);
        imagefilledellipse($img, 980, 420, 160, 160, $c);
    }

    private function drawTopBar(\GdImage $img): void
    {
        $c = imagecolorallocate($img, ...self::VIOLET);
        imagefilledrectangle($img, 0, 0, self::W, 6, $c);
    }

    private function drawLogo(\GdImage $img): void
    {
        if (! file_exists(self::LOGO_PATH)) {
            // Fallback: draw text logo
            $c = imagecolorallocate($img, ...self::WHITE);
            imagettftext($img, 18, 0, 60, 52, $c, self::FONT_BOLD, 'WAKANDA JOBS');
            return;
        }

        $logo = @imagecreatefrompng(self::LOGO_PATH);
        if (! $logo) {
            return;
        }

        $lW = imagesx($logo);
        $lH = imagesy($logo);
        $scale = min(180 / $lW, 46 / $lH);
        $dW = (int) ($lW * $scale);
        $dH = (int) ($lH * $scale);

        imagecopyresampled($img, $logo, 60, 24, 0, 0, $dW, $dH, $lW, $lH);
        imagedestroy($logo);
    }

    private function drawContent(\GdImage $img, Job $job): void
    {
        $lavender = imagecolorallocate($img, ...self::LAVENDER);
        $violet   = imagecolorallocate($img, ...self::VIOLET);
        $white    = imagecolorallocate($img, ...self::WHITE);

        // Label
        $label = 'JOB OPPORTUNITY';
        imagettftext($img, 13, 0, 60, 130, $lavender, self::FONT_BOLD, $label);

        // Separator line below label
        $sep = imagecolorallocatealpha($img, self::VIOLET[0], self::VIOLET[1], self::VIOLET[2], 60);
        imagefilledrectangle($img, 60, 140, 300, 142, $sep);

        // Job title — wrap at ~48pt, max 2 lines
        $title      = mb_strtoupper($job->name);
        $titleLines = $this->wrapText($title, self::FONT_BLACK, 52, 1050);
        $titleLines = array_slice($titleLines, 0, 2); // cap at 2 lines

        $y = 210;
        foreach ($titleLines as $line) {
            imagettftext($img, 52, 0, 60, $y, $white, self::FONT_BLACK, $line);
            $y += 66;
        }

        // Company
        $company = (! ($job->hide_company ?? false)) ? ($job->company?->name ?? '') : '';
        if ($company !== '') {
            $companyText = 'at ' . $company;
            imagettftext($img, 24, 0, 60, $y + 6, $lavender, self::FONT_BOLD, $companyText);
            $y += 40;
        }

        // Location
        $location = trim((string) ($job->location ?: ($job->country?->name ?? '')));
        if ($location !== '') {
            imagettftext($img, 19, 0, 60, $y + 18, $violet, self::FONT_REGULAR, 'Location: ' . $location);
            $y += 32;
        }

        // Deadline
        $deadline = $job->application_closing_date ?? $job->expire_date ?? null;
        if ($deadline) {
            $deadlineText = 'Apply by ' . $deadline->format('M j, Y');
            imagettftext($img, 19, 0, 60, $y + 18, $violet, self::FONT_REGULAR, $deadlineText);
        }
    }

    private function drawFooter(\GdImage $img): void
    {
        // Footer background
        $bg = imagecolorallocate($img, ...self::FOOTER_BG);
        imagefilledrectangle($img, 0, 562, self::W, self::H, $bg);

        // Top border of footer
        $border = imagecolorallocate($img, ...self::PURPLE);
        imagefilledrectangle($img, 0, 560, self::W, 563, $border);

        // Website
        $lavender = imagecolorallocate($img, ...self::LAVENDER);
        imagettftext($img, 19, 0, 60, 603, $lavender, self::FONT_BOLD, 'www.wakandajobs.com');

        // Hashtags right-aligned
        $violet  = imagecolorallocate($img, ...self::VIOLET);
        $hashtag = '#WakandaJobs  #Hiring  #Jobs';
        $bbox    = imagettfbbox(15, 0, self::FONT_REGULAR, $hashtag);
        $textW   = abs($bbox[2] - $bbox[0]);
        imagettftext($img, 15, 0, self::W - $textW - 60, 603, $violet, self::FONT_REGULAR, $hashtag);
    }

    // -------------------------------------------------------------------------

    private function wrapText(string $text, string $font, int $size, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
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
}
