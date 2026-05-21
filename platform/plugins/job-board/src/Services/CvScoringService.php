<?php

namespace Botble\JobBoard\Services;

use Illuminate\Support\Str;
use ZipArchive;

class CvScoringService
{
    public function extractTextFromFile(string $realPath, string $extension): string
    {
        $extension = strtolower($extension);

        if ($extension === 'txt') {
            return (string) @file_get_contents($realPath);
        }

        if (in_array($extension, ['docx', 'doc'])) {
            return $this->extractDocxText($realPath);
        }

        if ($extension === 'pdf') {
            return $this->extractPdfText($realPath);
        }

        return '';
    }

    protected function extractDocxText(string $path): string
    {
        if (! class_exists(ZipArchive::class)) {
            return '';
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return '';
        }

        // Strip XML tags, decode entities, normalise whitespace
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return (string) preg_replace('/\s+/', ' ', $text);
    }

    protected function extractPdfText(string $path): string
    {
        if (! function_exists('shell_exec')) {
            return $this->extractPdfTextNative($path);
        }

        $escaped = escapeshellarg($path);
        $output = @shell_exec("pdftotext -layout {$escaped} - 2>/dev/null");
        if ($output && strlen(trim($output)) > 20) {
            return $output;
        }

        $output = @shell_exec("pdftotext {$escaped} - 2>/dev/null");
        if ($output && strlen(trim($output)) > 20) {
            return $output;
        }

        return $this->extractPdfTextNative($path);
    }

    protected function extractPdfTextNative(string $path): string
    {
        $content = @file_get_contents($path);
        if (! $content) {
            return '';
        }

        // Extract readable text from PDF streams (works for uncompressed/ASCII PDFs)
        $text = '';

        // Pull text between parentheses in BT/ET blocks (Type 1 / standard encoding)
        if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $blocks)) {
            foreach ($blocks[1] as $block) {
                preg_match_all('/\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\)/', $block, $strings);
                foreach ($strings[1] as $s) {
                    $decoded = stripcslashes($s);
                    if (ctype_print($decoded) || mb_check_encoding($decoded, 'UTF-8')) {
                        $text .= ' ' . $decoded;
                    }
                }
            }
        }

        // Also grab any long runs of printable ASCII (catches some stream-encoded PDFs)
        preg_match_all('/[ -~\n\r\t]{40,}/', $content, $runs);
        foreach ($runs[0] as $run) {
            $clean = preg_replace('/[^\x20-\x7E\s]/', '', $run);
            if (strlen(trim($clean)) > 30) {
                $text .= ' ' . $clean;
            }
        }

        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    public function score(string $text): array
    {
        $normalized = Str::lower($text);
        $score = 35;
        $feedback = [];

        $checks = [
            'contact details'      => ['email', 'phone', 'linkedin'],
            'professional summary' => ['summary', 'profile', 'objective'],
            'work experience'      => ['experience', 'employment', 'worked', 'responsibilities'],
            'education'            => ['education', 'degree', 'diploma', 'certificate'],
            'skills'               => ['skills', 'competencies', 'tools', 'technologies'],
            'achievements'         => ['achieved', 'improved', 'increased', 'reduced', 'delivered'],
        ];

        foreach ($checks as $label => $keywords) {
            $matched = collect($keywords)->contains(fn (string $kw) => str_contains($normalized, $kw));
            if ($matched) {
                $score += 8;
            } else {
                $feedback[] = 'Add a clear ' . $label . ' section.';
            }
        }

        if (preg_match('/\b\d+%|\$\d+|\b\d+\s*(people|users|clients|projects|months|years)\b/i', $text)) {
            $score += 8;
        } else {
            $feedback[] = 'Quantify impact with numbers, percentages, revenue, team size or project volume.';
        }

        $wordCount = str_word_count($text);
        if ($wordCount >= 250 && $wordCount <= 900) {
            $score += 7;
        } elseif ($wordCount < 250) {
            $feedback[] = 'The CV looks too short. Add more detail about responsibilities, tools and outcomes.';
        } else {
            $feedback[] = 'The CV may be too long. Tighten it to the strongest, most relevant evidence.';
        }

        $score = max(0, min(100, $score));

        if ($score < 60) {
            $feedback[] = 'Your CV needs significant improvement. A professional rewrite is strongly recommended.';
        } elseif ($score < 75) {
            $feedback[] = 'A human CV review is recommended before applying to competitive roles.';
        } elseif ($score < 88) {
            $feedback[] = 'The CV is solid but a professional polish could sharpen your impact statements.';
        } else {
            $feedback[] = 'Strong CV baseline. Focus on tailoring it to each target role.';
        }

        return [
            'score'    => $score,
            'feedback' => array_values(array_unique($feedback)),
            'words'    => $wordCount,
            'scored_at' => now()->toDateTimeString(),
        ];
    }

    public function scoreFile(string $realPath, string $extension): ?array
    {
        $text = $this->extractTextFromFile($realPath, $extension);
        if (Str::length(trim($text)) < 50) {
            return null;
        }
        return $this->score($text);
    }
}
