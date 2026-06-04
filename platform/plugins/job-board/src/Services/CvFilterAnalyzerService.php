<?php

namespace Botble\JobBoard\Services;

use GuzzleHttp\Client;
use Throwable;

class CvFilterAnalyzerService
{
    public function analyzeFromText(
        string $cvText,
        array $jobTypes,     // [id => name]
        array $categories,   // [id => name]
        array $experiences,  // [id => name]
        array $cities        // [id => name]
    ): ?array {
        $apiKey = setting('anthropic_api_key') ?: env('ANTHROPIC_API_KEY');
        if (! $apiKey) {
            return null;
        }

        $truncated = mb_substr(trim($cvText), 0, 6000);
        if (strlen($truncated) < 50) {
            return null;
        }

        $jobTypeList    = collect($jobTypes)->map(fn ($n, $id) => "  {$id}: {$n}")->implode("\n");
        $categoryList   = collect($categories)->map(fn ($n, $id) => "  {$id}: {$n}")->implode("\n");
        $experienceList = collect($experiences)->map(fn ($n, $id) => "  {$id}: {$n}")->implode("\n");
        $cityList       = collect($cities)->map(fn ($n, $id) => "  {$id}: {$n}")->implode("\n");

        $prompt = <<<PROMPT
You are an expert job placement specialist. Analyze this CV/resume and extract the best job search filters from our system.

AVAILABLE JOB TYPES (use exact IDs):
{$jobTypeList}

AVAILABLE CATEGORIES (use exact IDs):
{$categoryList}

AVAILABLE EXPERIENCE LEVELS (use exact IDs):
{$experienceList}

AVAILABLE CITIES (use exact IDs):
{$cityList}

TASK: Based on the CV below, return a JSON object with:
- "keyword": the best 1-3 word job title or skill to search for (string, e.g. "Software Engineer" or "Accountant")
- "job_type_ids": array of matching job type IDs (only IDs from the list above, max 3)
- "category_ids": array of matching category IDs (only IDs from the list above, max 4, most relevant first)
- "job_experience_id": single experience level ID that best matches (or null if unclear)
- "city_id": city ID if candidate's location/preference is mentioned (or null)
- "summary": 2-sentence plain-English summary of what this candidate does and what they're looking for
- "confidence": integer 1-100 rating your confidence in these suggestions

Return ONLY valid JSON. No markdown fences, no explanation outside the JSON.

CV TEXT:
{$truncated}
PROMPT;

        try {
            $client   = new Client(['timeout' => 30]);
            $response = $client->post('https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'json' => [
                    'model'      => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 512,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $raw  = trim($body['content'][0]['text'] ?? '');

            // Strip markdown fences if present
            $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $raw = preg_replace('/\s*```$/m', '', $raw);

            $data = json_decode(trim($raw), true);
            if (! is_array($data)) {
                return null;
            }

            // Sanitise: only keep IDs that actually exist in our lists
            $validTypeIds = array_map('intval', array_keys($jobTypes));
            $validCatIds  = array_map('intval', array_keys($categories));
            $validExpIds  = array_map('intval', array_keys($experiences));
            $validCityIds = array_map('intval', array_keys($cities));

            $typeIds = array_values(array_filter(
                array_map('intval', (array) ($data['job_type_ids'] ?? [])),
                fn ($id) => in_array($id, $validTypeIds)
            ));

            $catIds = array_values(array_filter(
                array_map('intval', (array) ($data['category_ids'] ?? [])),
                fn ($id) => in_array($id, $validCatIds)
            ));

            $expId = isset($data['job_experience_id']) && $data['job_experience_id']
                ? (int) $data['job_experience_id']
                : null;
            if ($expId && ! in_array($expId, $validExpIds)) {
                $expId = null;
            }

            $cityId = isset($data['city_id']) && $data['city_id']
                ? (int) $data['city_id']
                : null;
            if ($cityId && ! in_array($cityId, $validCityIds)) {
                $cityId = null;
            }

            return [
                'keyword'             => substr(trim((string) ($data['keyword'] ?? '')), 0, 100),
                'job_type_ids'        => $typeIds,
                'category_ids'        => $catIds,
                'job_experience_id'   => $expId,
                'city_id'             => $cityId,
                'summary'             => substr(trim((string) ($data['summary'] ?? '')), 0, 400),
                'confidence'          => min(100, max(0, (int) ($data['confidence'] ?? 70))),
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
