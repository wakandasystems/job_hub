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
    private const OPENAI_TIMEOUT_SECONDS = 18;

    /** Keep the keyword list focused on broad, realistic search terms. */
    private const MAX_AI_KEYWORDS = 15;

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
- List every realistic, distinct keyword/job title this candidate could currently search for or qualify for based on their CV. Do not artificially limit the count to a small number — a strong CV may justify 15-30 keywords. Order best-first.
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
            $response = Http::connectTimeout(5)
                ->timeout(self::OPENAI_TIMEOUT_SECONDS)
                ->withToken($apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::MODEL,
                    'temperature' => 0.2,
                    'max_tokens' => 1800,
                    'response_format' => ['type' => 'json_object'],
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

                return $this->fallbackAnalysisResult($cvText, $jobTypes, $categories, $experiences, $countries);
            }

            $data = $this->decodeJsonResponse($response);
            if (! is_array($data)) {
                return $this->fallbackAnalysisResult($cvText, $jobTypes, $categories, $experiences, $countries);
            }

            return $this->sanitizeAnalysisResult($data, $cvText, $jobTypes, $categories, $experiences, $countries, $response);
        } catch (Throwable $e) {
            Log::warning('CandidateAlert CV analysis exception', ['error' => $e->getMessage()]);

            return $this->fallbackAnalysisResult($cvText, $jobTypes, $categories, $experiences, $countries);
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

        $keywords = $this->sanitizeKeywords(
            collect($data['keywords'] ?? [])
                ->merge(array_filter([(string) ($data['keyword'] ?? '')]))
                ->all()
        );

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

    private function sanitizeKeywords(array $values): array
    {
        return collect($values)
            ->map(function ($value) {
                $value = trim(preg_replace('/\s+/', ' ', (string) $value));

                if ($value === '') {
                    return null;
                }

                if (str_contains($value, '@')) {
                    return null;
                }

                if (mb_strlen($value) > 45) {
                    return null;
                }

                $wordCount = count(array_filter(preg_split('/\s+/', $value) ?: []));
                if ($wordCount > 4) {
                    return null;
                }

                $lower = Str::lower($value);
                $noisePhrases = [
                    'curriculum vitae',
                    'professional summary',
                    'work experience',
                    'references available',
                    'available on request',
                ];

                foreach ($noisePhrases as $noise) {
                    if (str_contains($lower, $noise)) {
                        return null;
                    }
                }

                return Str::title($value);
            })
            ->filter()
            ->unique(fn ($value) => Str::lower($value))
            ->take(self::MAX_AI_KEYWORDS)
            ->values()
            ->all();
    }

    private function fallbackAnalysisResult(
        string $cvText,
        array $jobTypes,
        array $categories,
        array $experiences,
        array $countries
    ): array {
        $text = trim($cvText);
        $lower = Str::lower($text);
        $keywords = $this->fallbackKeywords($text);
        $jobTypeIds = $this->fallbackJobTypeIds($lower, $jobTypes);
        $categoryIds = $this->fallbackCategoryIds($lower, $categories);
        $countryIds = $this->fallbackCountryIds($text, $countries);
        $experienceId = $this->fallbackExperienceId($lower, $experiences);
        $location = $this->fallbackLocationKeyword($text);
        $summaryBits = [];

        if ($keywords !== []) {
            $summaryBits[] = 'Likely roles: ' . implode(', ', array_slice($keywords, 0, 4));
        }

        if ($location) {
            $summaryBits[] = 'Location signal: ' . $location;
        }

        if ($experienceId && isset($experiences[$experienceId])) {
            $summaryBits[] = 'Experience level looks closest to ' . $experiences[$experienceId];
        }

        return [
            'candidate_type' => 'Heuristic CV analysis',
            'candidate_name' => $this->sanitizeCandidateName(null, $cvText),
            'candidate_phone' => $this->sanitizePhone(null, $cvText),
            'candidate_email' => $this->sanitizeEmail(null, $cvText),
            'keywords' => $keywords,
            'keyword' => $keywords[0] ?? null,
            'job_type_ids' => $jobTypeIds,
            'category_ids' => $categoryIds,
            'job_experience_id' => $experienceId,
            'country_ids' => $countryIds,
            'location_keyword' => $location,
            'summary' => Str::limit(implode('. ', $summaryBits) ?: 'Generated from CV text without an OpenAI response.', 500, ''),
            'signals' => [
                'education' => $this->collectSignalLines($text, ['education', 'grade 12', 'degree', 'diploma', 'certificate', 'certification']),
                'experience' => $this->collectSignalLines($text, ['experience', 'worked', 'employment', 'intern', 'assistant', 'officer']),
                'skills' => $this->collectSignalLines($text, ['skills', 'proficient', 'knowledge', 'software', 'tools', 'microsoft', 'excel']),
            ],
            'confidence' => 55,
            'job_type_names' => array_values(array_intersect_key($jobTypes, array_flip($jobTypeIds))),
            'category_names' => array_values(array_intersect_key($categories, array_flip($categoryIds))),
            'experience_name' => $experienceId ? ($experiences[$experienceId] ?? null) : null,
            'country_names' => array_values(array_intersect_key($countries, array_flip($countryIds))),
            'usage' => [
                'provider' => 'heuristic',
                'model' => null,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'estimated_cost_usd' => 0,
                'processing_ms' => null,
            ],
        ];
    }

    private function fallbackKeywords(string $cvText): array
    {
        $keywords = collect();
        $lines = preg_split('/\R+/', $cvText) ?: [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', (string) $line));

            if ($line === '' || mb_strlen($line) < 4 || mb_strlen($line) > 80) {
                continue;
            }

            if (preg_match('/^([A-Za-z][A-Za-z\/,&\-\s]{2,60})\s+-\s+[A-Za-z]/', $line, $matches)) {
                $keywords->push(trim($matches[1]));
                continue;
            }

            if (preg_match('/^(sales assistant|customer service|receptionist|cashier|office assistant|administrator|admin assistant|driver|security guard|cleaner|general worker|waitress|waiter|chef|cook|software developer|software engineer|web developer|it support|data analyst|graphic designer|accountant|bookkeeper|human resources officer|teacher|nurse|pharmacist|procurement officer|warehouse assistant|marketing officer)$/i', $line)) {
                $keywords->push($line);
            }
        }

        $keywordMap = [
            '/\bsales\b|\bretail\b/' => ['Sales Assistant', 'Sales Representative'],
            '/\bcustomer service\b|\bcall centre\b/' => ['Customer Service', 'Call Centre Agent'],
            '/\breception/i' => ['Receptionist', 'Front Desk'],
            '/\bcashier\b/' => ['Cashier'],
            '/\badmin\b|\badministrator\b|\boffice\b/' => ['Office Assistant', 'Administrator'],
            '/\bdriver\b/' => ['Driver'],
            '/\bsecurity\b/' => ['Security Guard'],
            '/\bcleaner\b|\bhousekeeping\b/' => ['Cleaner', 'Housekeeper'],
            '/\bwaiter\b|\bwaitress\b|\brestaurant\b/' => ['Waiter', 'Waitress'],
            '/\bcook\b|\bchef\b|\bkitchen\b/' => ['Cook', 'Chef'],
            '/\bsoftware\b|\bdeveloper\b|\bprogramming\b|\bjavascript\b|\bphp\b|\bpython\b/' => ['Software Developer', 'Web Developer'],
            '/\bit support\b|\bnetwork\b|\bict\b|\bcomputer science\b/' => ['IT Support', 'Systems Administrator'],
            '/\bdata analyst\b|\bpower bi\b|\bsql\b|\bexcel\b/' => ['Data Analyst'],
            '/\bgraphic design\b|\badobe\b|\bphotoshop\b|\bcanva\b/' => ['Graphic Designer'],
            '/\baccounting\b|\bbookkeeping\b|\bfinance\b/' => ['Accountant', 'Bookkeeper'],
            '/\bhuman resource\b|\bhr\b/' => ['Human Resources Officer'],
            '/\bteacher\b|\btutoring\b|\beducation\b/' => ['Teacher'],
            '/\bnursing\b|\bnurse\b|\bclinical\b/' => ['Nurse'],
            '/\bpharmacy\b|\bpharmacist\b/' => ['Pharmacist'],
            '/\bprocurement\b|\bpurchasing\b/' => ['Procurement Officer'],
            '/\bwarehouse\b|\blogistics\b|\binventory\b/' => ['Warehouse Assistant', 'Logistics Assistant'],
            '/\bmarketing\b|\bsocial media\b/' => ['Marketing Officer', 'Social Media Manager'],
            '/\bagriculture\b|\bfarm\b|\bseed\b/' => ['Agricultural Assistant', 'Field Officer'],
        ];

        $lower = Str::lower($cvText);

        foreach ($keywordMap as $pattern => $items) {
            if (preg_match($pattern, $lower)) {
                foreach ($items as $item) {
                    $keywords->push($item);
                }
            }
        }

        return $this->sanitizeKeywords($keywords->all());
    }

    private function fallbackJobTypeIds(string $text, array $jobTypes): array
    {
        $matches = [];

        foreach ($jobTypes as $id => $name) {
            $label = Str::lower((string) $name);

            if (
                (str_contains($label, 'intern') && preg_match('/\bintern(ship)?\b|\battachment\b/', $text))
                || (str_contains($label, 'part') && str_contains($text, 'part time'))
                || (str_contains($label, 'full') && str_contains($text, 'full time'))
                || (str_contains($label, 'contract') && str_contains($text, 'contract'))
                || (str_contains($label, 'temporary') && str_contains($text, 'temporary'))
            ) {
                $matches[] = (int) $id;
            }
        }

        return array_slice(array_values(array_unique($matches)), 0, 3);
    }

    private function fallbackCategoryIds(string $text, array $categories): array
    {
        return collect($categories)
            ->map(function ($name, $id) use ($text) {
                $label = Str::lower((string) $name);
                $tokens = preg_split('/[^a-z0-9\+\#]+/i', $label) ?: [];
                $score = 0;

                foreach ($tokens as $token) {
                    $token = trim($token);

                    if (mb_strlen($token) < 4) {
                        continue;
                    }

                    if (str_contains($text, $token)) {
                        $score += 2;
                    }
                }

                if ($label !== '' && str_contains($text, $label)) {
                    $score += 5;
                }

                return ['id' => (int) $id, 'score' => $score];
            })
            ->filter(fn ($row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->take(8)
            ->pluck('id')
            ->values()
            ->all();
    }

    private function fallbackCountryIds(string $cvText, array $countries): array
    {
        $text = Str::lower($cvText);
        $matched = [];

        foreach ($countries as $id => $name) {
            $name = trim((string) $name);

            if ($name !== '' && str_contains($text, Str::lower($name))) {
                $matched[] = (int) $id;
            }
        }

        if ($matched === []) {
            $phone = $this->sanitizePhone(null, $cvText);
            $prefixMap = [
                '+260' => 'zambia',
                '+27' => 'south africa',
                '+263' => 'zimbabwe',
                '+254' => 'kenya',
                '+255' => 'tanzania',
                '+256' => 'uganda',
                '+234' => 'nigeria',
                '+233' => 'ghana',
            ];

            foreach ($prefixMap as $prefix => $countryName) {
                if ($phone && str_starts_with($phone, $prefix)) {
                    foreach ($countries as $id => $name) {
                        if (Str::lower((string) $name) === $countryName) {
                            $matched[] = (int) $id;
                            break 2;
                        }
                    }
                }
            }
        }

        return array_slice(array_values(array_unique($matched)), 0, 3);
    }

    private function fallbackLocationKeyword(string $cvText): string
    {
        $cities = [
            'Lusaka', 'Kitwe', 'Ndola', 'Kabwe', 'Livingstone', 'Kasama', 'Chipata', 'Solwezi', 'Mongu',
            'Johannesburg', 'Cape Town', 'Durban', 'Pretoria', 'Harare', 'Bulawayo', 'Nairobi', 'Kampala',
        ];

        foreach ($cities as $city) {
            if (stripos($cvText, $city) !== false) {
                return $city;
            }
        }

        return '';
    }

    private function fallbackExperienceId(string $text, array $experiences): ?int
    {
        $desired = null;

        if (preg_match('/\bintern(ship)?\b|\battachment\b|\bgraduate\b|\btrainee\b/', $text)) {
            $desired = ['intern', 'entry', 'junior'];
        } elseif (preg_match('/\b([5-9]|[1-9][0-9]+)\+?\s+years?\b/', $text)) {
            $desired = ['senior', 'manager', 'lead', 'expert'];
        } elseif (preg_match('/\b([2-4])\+?\s+years?\b/', $text)) {
            $desired = ['mid', 'intermediate'];
        } else {
            $desired = ['entry', 'junior'];
        }

        foreach ($desired as $needle) {
            foreach ($experiences as $id => $name) {
                if (str_contains(Str::lower((string) $name), $needle)) {
                    return (int) $id;
                }
            }
        }

        return null;
    }

    private function collectSignalLines(string $cvText, array $needles): array
    {
        $lines = preg_split('/\R+/', $cvText) ?: [];
        $matches = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', (string) $line));

            if ($line === '' || mb_strlen($line) < 4) {
                continue;
            }

            $lower = Str::lower($line);

            foreach ($needles as $needle) {
                if (str_contains($lower, Str::lower($needle))) {
                    $matches[] = Str::limit($line, 120, '');
                    break;
                }
            }

            if (count($matches) >= 6) {
                break;
            }
        }

        return array_values(array_unique($matches));
    }

    private function estimateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        [$inputRate, $outputRate] = self::MODEL_PRICING_PER_MILLION[$model] ?? self::MODEL_PRICING_PER_MILLION['gpt-4o-mini'];

        return round((($promptTokens * $inputRate) + ($completionTokens * $outputRate)) / 1000000, 6);
    }
}
