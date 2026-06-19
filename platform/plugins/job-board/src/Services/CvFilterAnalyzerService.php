<?php

namespace Botble\JobBoard\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CvFilterAnalyzerService
{
    private const MODEL = 'gpt-4o-mini';

    private const MODEL_PRICING_PER_MILLION = [
        'gpt-4o-mini' => [0.15, 0.60],
        'gpt-4o' => [2.50, 10.00],
    ];

    public function analyzeFromText(
        string $cvText,
        array $jobTypes,
        array $categories,
        array $experiences,
        array $countries
    ): ?array {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));
        if ($apiKey === '') {
            return null;
        }

        $truncated = trim(mb_substr($cvText, 0, 12000));
        if (mb_strlen($truncated) < 50) {
            return null;
        }

        $jobTypeList = $this->formatOptions($jobTypes);
        $experienceList = $this->formatOptions($experiences);
        $countryList = $this->formatOptions($countries);
        $categoryShortlist = $this->buildCategoryShortlist($truncated, $categories);
        $categoryList = $this->formatOptions($categoryShortlist);

        $systemPrompt = <<<'PROMPT'
You analyze CVs for a VIP jobs alert system.

Your task is to infer the kinds of jobs a person can realistically do based on:
- education level
- course/qualification
- work experience
- internships / attachments
- technical skills
- soft skills
- tools/software mentioned

This includes entry-level inference. Examples:
- If the CV suggests high school, school leaver, Grade 12, or waiting for college, suggest realistic starter keywords like data entry, receptionist, waitress, cashier, customer service, office assistant, general worker, intern, sales assistant, cleaner, call centre.
- If the CV suggests computer science, ICT, software engineering, programming projects, or developer tools, suggest realistic tech roles like software developer, software engineer, web developer, IT support, data analyst, systems administrator.

Rules:
- Return ONLY valid JSON.
- Do not invent seniority the CV does not support.
- Prefer broad, searchable job keywords over niche titles.
- Use only IDs that exist in the provided lists.
- Pick up to 10 keywords, ordered best-first.
- Pick up to 3 job types and up to 8 categories.
- If location is unclear, leave it null/empty.

JSON shape:
{
  "candidate_type": "short label",
  "candidate_name": "full name from CV if clear",
  "candidate_phone": "phone number with country code if present",
  "candidate_email": "email if present",
  "keywords": ["keyword1", "keyword2"],
  "job_type_ids": [1, 2],
  "category_ids": [10, 20],
  "job_experience_id": 3,
  "country_ids": [7],
  "location_keyword": "Lusaka",
  "summary": "two sentence summary",
  "signals": {
    "education": ["..."],
    "experience": ["..."],
    "skills": ["..."]
  },
  "confidence": 82
}
PROMPT;

        $userPrompt = <<<PROMPT
AVAILABLE JOB TYPES:
{$jobTypeList}

AVAILABLE CATEGORIES:
{$categoryList}

AVAILABLE EXPERIENCE LEVELS:
{$experienceList}

AVAILABLE COUNTRIES:
{$countryList}

CV TEXT:
{$truncated}
PROMPT;

        try {
            $response = Http::timeout(60)
                ->withToken($apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::MODEL,
                    'temperature' => 0.2,
                    'max_tokens' => 1200,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('CandidateAlert CV analysis failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $this->decodeJsonResponse($response);
            if (! is_array($data)) {
                return null;
            }

            return $this->sanitizeAnalysisResult($data, $cvText, $jobTypes, $categories, $experiences, $countries, $response);
        } catch (Throwable $e) {
            Log::warning('CandidateAlert CV analysis exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function buildCategoryShortlist(string $cvText, array $categories, int $limit = 120): array
    {
        $text = Str::lower($cvText);
        $tokens = collect(preg_split('/[^a-z0-9\+\#]+/i', $text) ?: [])
            ->filter(fn ($token) => mb_strlen($token) >= 3)
            ->countBy();

        $scored = collect($categories)
            ->map(function ($name, $id) use ($text, $tokens) {
                $label = Str::lower((string) $name);
                $parts = collect(preg_split('/[^a-z0-9\+\#]+/i', $label) ?: [])
                    ->filter(fn ($token) => mb_strlen($token) >= 3)
                    ->values();

                $score = 0;

                foreach ($parts as $part) {
                    $score += (int) ($tokens[$part] ?? 0);
                }

                if ($label !== '' && str_contains($text, $label)) {
                    $score += 8;
                }

                return [
                    'id' => (int) $id,
                    'name' => (string) $name,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->values();

        $matching = $scored->filter(fn ($row) => $row['score'] > 0)->take($limit);
        if ($matching->count() < 40) {
            $matching = $matching->concat($scored->take(40 - $matching->count()));
        }

        return $matching
            ->unique('id')
            ->take($limit)
            ->mapWithKeys(fn ($row) => [$row['id'] => $row['name']])
            ->all();
    }

    private function formatOptions(array $items): string
    {
        return collect($items)
            ->map(fn ($name, $id) => "{$id}: {$name}")
            ->implode("\n");
    }

    private function decodeJsonResponse(Response $response): ?array
    {
        $content = trim((string) $response->json('choices.0.message.content', ''));
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

    private function sanitizeAnalysisResult(
        array $data,
        string $cvText,
        array $jobTypes,
        array $categories,
        array $experiences,
        array $countries,
        Response $response
    ): array {
        $validTypeIds = array_map('intval', array_keys($jobTypes));
        $validCategoryIds = array_map('intval', array_keys($categories));
        $validExperienceIds = array_map('intval', array_keys($experiences));
        $validCountryIds = array_map('intval', array_keys($countries));

        $keywords = collect($data['keywords'] ?? [])
            ->merge(array_filter([(string) ($data['keyword'] ?? '')]))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => Str::lower($value))
            ->take(10)
            ->values()
            ->all();

        $jobTypeIds = $this->sanitizeIdArray($data['job_type_ids'] ?? [], $validTypeIds, 3);
        $categoryIds = $this->sanitizeIdArray($data['category_ids'] ?? [], $validCategoryIds, 8);
        $countryIds = $this->sanitizeIdArray($data['country_ids'] ?? [], $validCountryIds, 3);

        $experienceId = (int) ($data['job_experience_id'] ?? 0);
        if (! in_array($experienceId, $validExperienceIds, true)) {
            $experienceId = null;
        }

        $promptTokens = (int) $response->json('usage.prompt_tokens', 0);
        $completionTokens = (int) $response->json('usage.completion_tokens', 0);
        $totalTokens = (int) $response->json('usage.total_tokens', $promptTokens + $completionTokens);
        $processingMs = (int) ($response->header('openai-processing-ms') ?: 0);
        $estimatedCostUsd = $this->estimateCost(self::MODEL, $promptTokens, $completionTokens);

        return [
            'candidate_type' => Str::limit(trim((string) ($data['candidate_type'] ?? 'Candidate')), 80, ''),
            'candidate_name' => $this->sanitizeCandidateName($data['candidate_name'] ?? null, $cvText),
            'candidate_phone' => $this->sanitizePhone($data['candidate_phone'] ?? null, $cvText),
            'candidate_email' => $this->sanitizeEmail($data['candidate_email'] ?? null, $cvText),
            'keywords' => $keywords,
            'keyword' => $keywords[0] ?? null,
            'job_type_ids' => $jobTypeIds,
            'category_ids' => $categoryIds,
            'job_experience_id' => $experienceId,
            'country_ids' => $countryIds,
            'location_keyword' => Str::limit(trim((string) ($data['location_keyword'] ?? '')), 100, ''),
            'summary' => Str::limit(trim((string) ($data['summary'] ?? '')), 500, ''),
            'signals' => $this->sanitizeSignals($data['signals'] ?? []),
            'confidence' => max(0, min(100, (int) ($data['confidence'] ?? 70))),
            'job_type_names' => array_values(array_intersect_key($jobTypes, array_flip($jobTypeIds))),
            'category_names' => array_values(array_intersect_key($categories, array_flip($categoryIds))),
            'experience_name' => $experienceId ? ($experiences[$experienceId] ?? null) : null,
            'country_names' => array_values(array_intersect_key($countries, array_flip($countryIds))),
            'usage' => [
                'provider' => 'openai',
                'model' => self::MODEL,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'estimated_cost_usd' => $estimatedCostUsd,
                'processing_ms' => $processingMs ?: null,
            ],
        ];
    }

    private function sanitizeCandidateName(mixed $value, string $cvText): ?string
    {
        $name = trim((string) $value);
        if ($name !== '' && mb_strlen($name) >= 4) {
            return Str::limit($name, 120, '');
        }

        $firstLine = trim((string) Str::of($cvText)->before("\n"));
        if ($firstLine !== '' && preg_match('/^[A-Za-z][A-Za-z\-\.\'\s]{3,120}$/', $firstLine)) {
            return Str::limit($firstLine, 120, '');
        }

        return null;
    }

    private function sanitizePhone(mixed $value, string $cvText): ?string
    {
        $phone = $this->normalizePhone((string) $value);
        if ($phone !== null) {
            return $phone;
        }

        if (preg_match('/(\+?\d[\d\s\-\(\)]{7,}\d)/', $cvText, $matches)) {
            return $this->normalizePhone($matches[1]);
        }

        return null;
    }

    private function sanitizeEmail(mixed $value, string $cvText): ?string
    {
        $email = trim(Str::lower((string) $value));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Str::limit($email, 150, '');
        }

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $cvText, $matches)) {
            $email = Str::lower(trim($matches[0]));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return Str::limit($email, 150, '');
            }
        }

        return null;
    }

    private function normalizePhone(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $hasPlus = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value);

        if (! $digits || strlen($digits) < 8) {
            return null;
        }

        if ($hasPlus) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '00') && strlen($digits) > 10) {
            return '+' . substr($digits, 2);
        }

        return '+' . $digits;
    }

    private function sanitizeIdArray(mixed $values, array $validIds, int $limit): array
    {
        return collect(is_array($values) ? $values : [$values])
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => in_array($value, $validIds, true))
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }

    private function sanitizeSignals(mixed $signals): array
    {
        $signals = is_array($signals) ? $signals : [];
        $normalized = [];

        foreach (['education', 'experience', 'skills'] as $key) {
            $normalized[$key] = collect($signals[$key] ?? [])
                ->map(fn ($value) => Str::limit(trim((string) $value), 120, ''))
                ->filter()
                ->take(6)
                ->values()
                ->all();
        }

        return $normalized;
    }

    private function estimateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        [$inputRate, $outputRate] = self::MODEL_PRICING_PER_MILLION[$model] ?? self::MODEL_PRICING_PER_MILLION['gpt-4o-mini'];

        return round((($promptTokens * $inputRate) + ($completionTokens * $outputRate)) / 1000000, 6);
    }
}
