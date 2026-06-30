<?php

namespace Botble\JobBoard\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CvProfileAnalyzerService
{
    private const MODEL = 'gpt-4o-mini';
    private const OPENAI_TIMEOUT_SECONDS = 20;

    public function analyzeFromText(string $cvText): ?array
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            return $this->fallbackProfile($cvText);
        }

        $truncated = trim(mb_substr($cvText, 0, 14000));

        if (mb_strlen($truncated) < 50) {
            return null;
        }

        $systemPrompt = <<<'PROMPT'
You extract candidate profile data from CV text for a jobs platform admin.

Return ONLY valid JSON. Do not include markdown.
Do not invent facts that are not reasonably supported by the CV.
If a value is unclear, leave it empty.

Allowed enums:
- education_level: "", "high_school", "diploma", "bachelor", "masters", "phd"
- experience_years: "", "0", "1", "2", "3", "5", "10"
- availability: "", "immediate", "one_week", "two_weeks", "one_month", "not_looking"
- language level: "Expert", "Intermediate", "Beginner"

Use concise, professional wording.
For started_at and ended_at, prefer YYYY-MM-DD. If only a year is known, use YYYY-01-01. If ongoing/current/present, leave ended_at empty.

JSON shape:
{
  "headline": "",
  "summary": "",
  "linkedin": "",
  "address": "",
  "location": "",
  "education_level": "",
  "experience_years": "",
  "availability": "",
  "educations": [
    {
      "school": "",
      "specialized": "",
      "description": "",
      "started_at": "",
      "ended_at": ""
    }
  ],
  "experiences": [
    {
      "company": "",
      "position": "",
      "description": "",
      "started_at": "",
      "ended_at": ""
    }
  ],
  "languages": [
    {
      "language": "",
      "level": "",
      "is_native": false
    }
  ],
  "skills": ["..."]
}
PROMPT;

        try {
            $response = Http::connectTimeout(5)
                ->timeout(self::OPENAI_TIMEOUT_SECONDS)
                ->withToken($apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::MODEL,
                    'temperature' => 0.1,
                    'max_tokens' => 2200,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $truncated],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('CV profile analysis failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->fallbackProfile($cvText);
            }

            $decoded = $this->decodeJsonResponse($response);

            if (! is_array($decoded)) {
                return $this->fallbackProfile($cvText);
            }

            return $this->sanitizeProfile($decoded, $cvText);
        } catch (Throwable $exception) {
            Log::warning('CV profile analysis exception', ['error' => $exception->getMessage()]);

            return $this->fallbackProfile($cvText);
        }
    }

    private function decodeJsonResponse(Response $response): ?array
    {
        $contentNode = $response->json('choices.0.message.content', '');
        $content = '';

        if (is_array($contentNode)) {
            $content = collect($contentNode)
                ->map(fn ($item) => is_array($item) ? ($item['text'] ?? '') : '')
                ->implode("\n");
        } else {
            $content = (string) $contentNode;
        }

        $content = trim($content);

        if ($content === '') {
            return null;
        }

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        }

        $decoded = json_decode(trim($content), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function sanitizeProfile(array $profile, string $cvText): array
    {
        $summary = trim((string) ($profile['summary'] ?? ''));
        $headline = trim((string) ($profile['headline'] ?? ''));
        $skills = collect($profile['skills'] ?? [])
            ->map(fn ($value) => Str::limit(trim((string) $value), 80, ''))
            ->filter()
            ->unique(fn ($value) => Str::lower($value))
            ->take(20)
            ->values()
            ->all();

        return [
            'headline' => Str::limit($headline, 160, ''),
            'summary' => Str::limit($summary, 2000, ''),
            'linkedin' => $this->sanitizeLinkedin($profile['linkedin'] ?? null, $cvText),
            'address' => Str::limit(trim((string) ($profile['address'] ?? '')), 250, ''),
            'location' => Str::limit(trim((string) ($profile['location'] ?? '')), 160, ''),
            'education_level' => $this->sanitizeEducationLevel($profile['education_level'] ?? null, $cvText),
            'experience_years' => $this->sanitizeExperienceYears($profile['experience_years'] ?? null),
            'availability' => $this->sanitizeAvailability($profile['availability'] ?? null),
            'educations' => $this->sanitizeEducations($profile['educations'] ?? []),
            'experiences' => $this->sanitizeExperiences($profile['experiences'] ?? []),
            'languages' => $this->sanitizeLanguages($profile['languages'] ?? [], $cvText),
            'skills' => $skills,
        ];
    }

    private function sanitizeLinkedin(mixed $value, string $cvText): string
    {
        $value = trim((string) $value);

        if ($value !== '' && preg_match('/linkedin\.com/i', $value)) {
            return Str::limit($value, 250, '');
        }

        if (preg_match('/https?:\/\/[^\s]*linkedin\.com\/[^\s]+/i', $cvText, $matches)) {
            return Str::limit(trim($matches[0]), 250, '');
        }

        return '';
    }

    private function sanitizeEducationLevel(mixed $value, string $cvText): string
    {
        $value = trim((string) $value);
        $allowed = ['high_school', 'diploma', 'bachelor', 'masters', 'phd'];

        if (in_array($value, $allowed, true)) {
            return $value;
        }

        $text = Str::lower($cvText);

        if (preg_match('/\b(phd|doctorate)\b/', $text)) {
            return 'phd';
        }

        if (preg_match('/\bmasters?\b|\bmba\b/', $text)) {
            return 'masters';
        }

        if (preg_match('/\bbachelor|bsc\b|ba\b|bed\b|beng\b|bcom\b/', $text)) {
            return 'bachelor';
        }

        if (preg_match('/\bdiploma\b|\bcertificate\b|\badvanced certificate\b/', $text)) {
            return 'diploma';
        }

        if (preg_match('/\bgrade 12\b|\bgce\b|\bsecondary school\b|\bhigh school\b/', $text)) {
            return 'high_school';
        }

        return '';
    }

    private function sanitizeExperienceYears(mixed $value): string
    {
        $value = trim((string) $value);
        $allowed = ['0', '1', '2', '3', '5', '10'];

        return in_array($value, $allowed, true) ? $value : '';
    }

    private function sanitizeAvailability(mixed $value): string
    {
        $value = trim((string) $value);
        $allowed = ['immediate', 'one_week', 'two_weeks', 'one_month', 'not_looking'];

        return in_array($value, $allowed, true) ? $value : '';
    }

    private function sanitizeEducations(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                return [
                    'school' => Str::limit(trim((string) ($row['school'] ?? '')), 120, ''),
                    'specialized' => Str::limit(trim((string) ($row['specialized'] ?? '')), 120, ''),
                    'description' => Str::limit(trim((string) ($row['description'] ?? '')), 400, ''),
                    'started_at' => $this->normalizeDateValue($row['started_at'] ?? null),
                    'ended_at' => $this->normalizeDateValue($row['ended_at'] ?? null),
                ];
            })
            ->filter(fn (array $row) => $row['school'] !== '' || $row['specialized'] !== '')
            ->unique(fn (array $row) => Str::lower($row['school'] . '|' . $row['specialized'] . '|' . $row['started_at']))
            ->take(8)
            ->values()
            ->all();
    }

    private function sanitizeExperiences(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                return [
                    'company' => Str::limit(trim((string) ($row['company'] ?? '')), 120, ''),
                    'position' => Str::limit(trim((string) ($row['position'] ?? '')), 120, ''),
                    'description' => Str::limit(trim((string) ($row['description'] ?? '')), 400, ''),
                    'started_at' => $this->normalizeDateValue($row['started_at'] ?? null),
                    'ended_at' => $this->normalizeDateValue($row['ended_at'] ?? null),
                ];
            })
            ->filter(fn (array $row) => $row['company'] !== '' || $row['position'] !== '')
            ->unique(fn (array $row) => Str::lower($row['company'] . '|' . $row['position'] . '|' . $row['started_at']))
            ->take(12)
            ->values()
            ->all();
    }

    private function sanitizeLanguages(mixed $rows, string $cvText): array
    {
        $languages = collect(is_array($rows) ? $rows : [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                $level = trim((string) ($row['level'] ?? ''));

                if (! in_array($level, ['Expert', 'Intermediate', 'Beginner'], true)) {
                    $level = 'Intermediate';
                }

                return [
                    'language' => Str::limit(trim((string) ($row['language'] ?? '')), 80, ''),
                    'level' => $level,
                    'is_native' => (bool) ($row['is_native'] ?? false),
                ];
            })
            ->filter(fn (array $row) => $row['language'] !== '')
            ->unique(fn (array $row) => Str::lower($row['language']))
            ->take(10)
            ->values();

        if ($languages->isEmpty() && preg_match('/\benglish\b/i', $cvText)) {
            $languages->push([
                'language' => 'English',
                'level' => 'Intermediate',
                'is_native' => false,
            ]);
        }

        return $languages->all();
    }

    private function normalizeDateValue(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (preg_match('/\b(current|present|ongoing)\b/i', $value)) {
            return '';
        }

        if (preg_match('/^\d{4}$/', $value)) {
            return $value . '-01-01';
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return $value . '-01';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return '';
        }
    }

    private function fallbackProfile(string $cvText): array
    {
        $summary = '';

        if (preg_match('/professional summary[:\s]+(.{30,350})/i', preg_replace('/\s+/', ' ', $cvText), $matches)) {
            $summary = trim($matches[1]);
        }

        return [
            'headline' => '',
            'summary' => Str::limit($summary, 2000, ''),
            'linkedin' => $this->sanitizeLinkedin(null, $cvText),
            'address' => '',
            'location' => '',
            'education_level' => $this->sanitizeEducationLevel(null, $cvText),
            'experience_years' => '',
            'availability' => '',
            'educations' => [],
            'experiences' => [],
            'languages' => $this->sanitizeLanguages([], $cvText),
            'skills' => [],
        ];
    }
}
