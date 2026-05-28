<?php

namespace Botble\JobBoard\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use ZipArchive;

class CvScoringService
{
    // ─────────────────────────────────────────────────────────────
    //  Text extraction
    // ─────────────────────────────────────────────────────────────

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

        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return (string) preg_replace('/\s+/', ' ', $text);
    }

    protected function extractPdfText(string $path): string
    {
        if (function_exists('shell_exec')) {
            $escaped = escapeshellarg($path);
            $output  = @shell_exec("pdftotext -layout {$escaped} - 2>/dev/null");
            if ($output && strlen(trim($output)) > 20) {
                return $output;
            }

            $output = @shell_exec("pdftotext {$escaped} - 2>/dev/null");
            if ($output && strlen(trim($output)) > 20) {
                return $output;
            }
        }

        return $this->extractPdfTextNative($path);
    }

    protected function extractPdfTextNative(string $path): string
    {
        $content = @file_get_contents($path);
        if (! $content) {
            return '';
        }

        $text = '';

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

        preg_match_all('/[ -~\n\r\t]{40,}/', $content, $runs);
        foreach ($runs[0] as $run) {
            $clean = preg_replace('/[^\x20-\x7E\s]/', '', $run);
            if (strlen(trim($clean)) > 30) {
                $text .= ' ' . $clean;
            }
        }

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    // ─────────────────────────────────────────────────────────────
    //  Public entry point
    // ─────────────────────────────────────────────────────────────

    public function scoreFile(string $realPath, string $extension): ?array
    {
        $text = $this->extractTextFromFile($realPath, $extension);
        if (Str::length(trim($text)) < 50) {
            return null;
        }

        if ($apiKey = env('ANTHROPIC_API_KEY')) {
            try {
                $result = $this->scoreWithClaude($text, $apiKey);
                if ($result) {
                    return $result;
                }
            } catch (\Throwable) {
                // Fall through to regex scoring
            }
        }

        return $this->score($text);
    }

    protected function currencySymbol(): string
    {
        try {
            $currency = get_application_currency();
            return $currency?->symbol ?? '$';
        } catch (\Throwable) {
            return '$';
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  AI scoring (Claude)
    // ─────────────────────────────────────────────────────────────

    protected function scoreWithClaude(string $text, string $apiKey): ?array
    {
        $truncated = Str::limit($text, 12000, '');

        $prompt = <<<PROMPT
You are a professional CV/résumé analyst. Analyze the following CV text and return ONLY a valid JSON object — no markdown, no extra text.

Scoring rules (maximum 100 points):
- Email address present (real pattern like user@domain.com): 10 pts
- Phone number present (any format): 10 pts
- LinkedIn profile URL or mention: 5 pts
- Professional summary or objective section: 10 pts
- Work experience section with job history: 15 pts
- Education section with qualifications: 10 pts
- Skills or competencies section: 10 pts
- Achievements, results, or accomplishments mentioned: 10 pts
- References section or "available on request": 5 pts
- Quantified impact (numbers, %, revenue, team size): 10 pts
- Appropriate CV length (300–1000 words): 5 pts

Return this exact JSON structure:
{
  "score": <integer 0-100>,
  "checks": {
    "email": <true/false>,
    "phone": <true/false>,
    "linkedin": <true/false>,
    "summary": <true/false>,
    "experience": <true/false>,
    "education": <true/false>,
    "skills": <true/false>,
    "achievements": <true/false>,
    "references": <true/false>,
    "quantified": <true/false>,
    "length_ok": <true/false>
  },
  "feedback": [<array of specific, actionable improvement strings>],
  "missing_points": [{"label": "<section name>", "points": <integer>, "action": "<specific advice>"}],
  "points_to_100": <integer>,
  "words": <integer>
}

CV TEXT:
{$truncated}
PROMPT;

        $client   = new Client(['timeout' => 30]);
        $response = $client->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'json' => [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1024,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true);
        $raw  = $body['content'][0]['text'] ?? '';

        // Strip any markdown code fences if present
        $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $raw = preg_replace('/\s*```$/', '', $raw);

        $data = json_decode(trim($raw), true);
        if (! is_array($data) || ! isset($data['score'])) {
            return null;
        }

        $data['score']          = max(0, min(100, (int) ($data['score'] ?? 0)));
        $data['points_to_100']  = max(0, 100 - $data['score']);
        $data['scored_at']      = now()->toDateTimeString();
        $data['words']          = $data['words'] ?? str_word_count($raw);

        return $data;
    }

    // ─────────────────────────────────────────────────────────────
    //  Regex-based scoring (fallback)
    // ─────────────────────────────────────────────────────────────

    public function score(string $text): array
    {
        $score        = 0;
        $feedback     = [];
        $missing      = [];
        $checks       = [];
        $currency     = $this->currencySymbol();

        // ── Email address ─────────────────────────────────────────
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text)) {
            $score += 10;
            $checks['email'] = true;
        } else {
            $checks['email'] = false;
            $feedback[] = 'No email address found. Add a professional email to your contact details.';
            $missing[]  = ['label' => 'Email address', 'points' => 10, 'action' => 'Add a professional email address (e.g. yourname@email.com) to your contact section.'];
        }

        // ── Phone number ──────────────────────────────────────────
        // Matches international (+27 82 …), local (082 …), US (555-555-5555), etc.
        if (preg_match('/(\+?[\d][\d\s\.\-\(\)]{7,}[\d])/', $text)) {
            $score += 10;
            $checks['phone'] = true;
        } else {
            $checks['phone'] = false;
            $feedback[] = 'No phone number detected. Add a contact number so employers can reach you.';
            $missing[]  = ['label' => 'Phone number', 'points' => 10, 'action' => 'Add a phone number in your contact section (e.g. +27 82 000 0000).'];
        }

        // ── LinkedIn ──────────────────────────────────────────────
        if (preg_match('/linkedin\.com\/in\/|linkedin\.com\/pub\//i', $text) || stripos($text, 'linkedin') !== false) {
            $score += 5;
            $checks['linkedin'] = true;
        } else {
            $checks['linkedin'] = false;
            $feedback[] = 'No LinkedIn profile found. Add your LinkedIn URL to build credibility.';
            $missing[]  = ['label' => 'LinkedIn profile', 'points' => 5, 'action' => 'Add your LinkedIn profile URL (linkedin.com/in/yourname).'];
        }

        // ── Professional summary ──────────────────────────────────
        if (preg_match('/\b(summary|profile|objective|about me|personal statement|professional background|career overview)\b/i', $text)) {
            $score += 10;
            $checks['summary'] = true;
        } else {
            $checks['summary'] = false;
            $feedback[] = 'No professional summary detected. Add a 2–3 sentence overview at the top of your CV.';
            $missing[]  = ['label' => 'Professional summary', 'points' => 10, 'action' => 'Write a short professional summary (2–3 sentences) that describes who you are and what you bring to employers.'];
        }

        // ── Work experience ───────────────────────────────────────
        if (preg_match('/\b(experience|employment|work history|career history|positions? held|previous roles?|job title)\b/i', $text)) {
            $score += 15;
            $checks['experience'] = true;
        } else {
            $checks['experience'] = false;
            $feedback[] = 'No work experience section detected. This is the most critical CV section — add your job history with titles, companies and dates.';
            $missing[]  = ['label' => 'Work experience', 'points' => 15, 'action' => 'Add a Work Experience section with job titles, company names, dates, and key responsibilities.'];
        }

        // ── Education ─────────────────────────────────────────────
        if (preg_match('/\b(education|degree|diploma|certificate|bachelor|master|mba|phd|doctorate|matric|grade 12|n-?level|university|college|graduate|undergraduate|studied)\b/i', $text)) {
            $score += 10;
            $checks['education'] = true;
        } else {
            $checks['education'] = false;
            $feedback[] = 'No education section detected. List your qualifications, institutions and graduation years.';
            $missing[]  = ['label' => 'Education', 'points' => 10, 'action' => 'Add an Education section listing your qualifications, institutions and years of study.'];
        }

        // ── Skills ────────────────────────────────────────────────
        if (preg_match('/\b(skills|competencies|technologies|expertise|proficiencies|technical skills|core skills|key skills|tools)\b/i', $text)) {
            $score += 10;
            $checks['skills'] = true;
        } else {
            $checks['skills'] = false;
            $feedback[] = 'No skills section detected. Add a list of your key technical and soft skills.';
            $missing[]  = ['label' => 'Skills section', 'points' => 10, 'action' => 'Add a Skills section listing your technical skills, software, languages and soft skills.'];
        }

        // ── Achievements / results ────────────────────────────────
        if (preg_match('/\b(achieved|achievement|accomplishment|award|recognition|increased|improved|reduced|delivered|launched|built|led|managed|drove|generated|saved|cut|grew|scaled|transformed)\b/i', $text)) {
            $score += 10;
            $checks['achievements'] = true;
        } else {
            $checks['achievements'] = false;
            $feedback[] = 'No achievement statements found. Use strong action verbs (increased, delivered, led) to show results.';
            $missing[]  = ['label' => 'Achievements / results', 'points' => 10, 'action' => 'Add achievement bullet points using action verbs — e.g. "Led a team of 6 to deliver project 2 weeks ahead of schedule."'];

        }

        // ── References ────────────────────────────────────────────
        if (preg_match('/\b(references?|referees?|available on request|furnished on request)\b/i', $text)) {
            $score += 5;
            $checks['references'] = true;
        } else {
            $checks['references'] = false;
            $feedback[] = 'No references section found. Add 2 professional references or state "References available on request."';
            $missing[]  = ['label' => 'References', 'points' => 5, 'action' => 'Add a References section with 2 professional contacts, or write "References available on request."'];
        }

        // ── Quantified impact ──────────────────────────────────────
        $escapedSymbol = preg_quote($currency, '/');
        if (preg_match('/\b\d+\s*%|' . $escapedSymbol . '[\d,]+|\b\d+\s*(million|billion|thousand|k)\b|\b\d+\s*(people|users|clients|customers|team|staff|employees|projects|countries|stores|branches|months|years)\b/i', $text)) {
            $score += 10;
            $checks['quantified'] = true;
        } else {
            $checks['quantified'] = false;
            $feedback[] = 'No quantified results found. Add numbers to prove your impact — percentages, revenue, team sizes, project scale.';
            $missing[]  = ['label' => 'Measurable impact', 'points' => 10, 'action' => 'Add at least 3 metric-backed statements — e.g. "increased sales by 30%", "managed a team of 8", "saved ' . $currency . '200k in costs."'];
        }

        // ── Word count / length ────────────────────────────────────
        $wordCount = str_word_count($text);
        if ($wordCount >= 300 && $wordCount <= 1000) {
            $score += 5;
            $checks['length_ok'] = true;
        } elseif ($wordCount < 300) {
            $checks['length_ok'] = false;
            $feedback[] = 'Your CV is too brief (' . $wordCount . ' words). Expand on your experience, responsibilities and achievements.';
            $missing[]  = ['label' => 'CV depth', 'points' => 5, 'action' => 'Your CV has only ' . $wordCount . ' words. Aim for 400–800 words covering all key sections.'];
        } else {
            $checks['length_ok'] = false;
            $feedback[] = 'Your CV may be too long (' . $wordCount . ' words). Trim it to 1–2 pages of the most relevant content.';
            $missing[]  = ['label' => 'CV length', 'points' => 5, 'action' => 'Your CV is ' . $wordCount . ' words — consider trimming to the strongest, most recent experience (aim for 1–2 pages).'];
        }

        $score = max(0, min(100, $score));

        // Overall recommendation
        if ($score >= 88) {
            $feedback[] = 'Strong CV. Focus on tailoring it to each specific role for the best results.';
        } elseif ($score >= 75) {
            $feedback[] = 'Good CV structure. A professional polish could sharpen your impact statements.';
        } elseif ($score >= 60) {
            $feedback[] = 'Decent foundation — address the missing sections above to strengthen your application.';
        } else {
            $feedback[] = 'Your CV needs significant work. Consider using our Career Services for a professional rewrite.';
        }

        return [
            'score'          => $score,
            'feedback'       => array_values($feedback),
            'missing_points' => $missing,
            'points_to_100'  => max(0, 100 - $score),
            'words'          => $wordCount,
            'checks'         => $checks,
            'scored_at'      => now()->toDateTimeString(),
        ];
    }
}
