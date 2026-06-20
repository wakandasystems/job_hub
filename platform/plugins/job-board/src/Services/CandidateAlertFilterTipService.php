<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\CandidateAlert;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobExperience;
use Botble\JobBoard\Models\JobType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Builds the one-time "you can edit your filters" engagement message sent ~2 days
 * after a VIP alert signup. All facts (current filters, suggested additions, job
 * counts) are computed deterministically in PHP — OpenAI is only asked to phrase
 * them naturally and confidently, never to invent numbers or filter values.
 */
class CandidateAlertFilterTipService
{
    public function buildMessage(CandidateAlert $alert): string
    {
        $filters = $alert->filters ?? [];

        $current      = $this->currentFiltersSummary($filters);
        $suggestions  = $this->suggestAdditions($filters);
        $matchedCount = $this->countMatchingJobs($filters);

        $aiMessage = $this->rephraseWithAi($alert, $current, $suggestions, $matchedCount);

        return $aiMessage ?? $this->fallbackMessage($alert, $current, $suggestions, $matchedCount);
    }

    /** Human-readable lines describing the candidate's current filter selection. */
    private function currentFiltersSummary(array $filters): array
    {
        $lines = [];

        $keywords = array_values(array_filter((array) ($filters['keywords'] ?? [])));
        if ($keywords) {
            $lines[] = 'Keywords: ' . implode(', ', $keywords);
        }

        if (! empty($filters['category_ids'])) {
            $names = Category::whereIn('id', (array) $filters['category_ids'])->pluck('name')->all();
            if ($names) {
                $lines[] = 'Categories: ' . implode(', ', $names);
            }
        }

        if (! empty($filters['job_type_ids'])) {
            $names = JobType::whereIn('id', (array) $filters['job_type_ids'])->pluck('name')->all();
            if ($names) {
                $lines[] = 'Job Types: ' . implode(', ', $names);
            }
        }

        if (! empty($filters['country_ids'])) {
            $names = DB::table('countries')->whereIn('id', (array) $filters['country_ids'])->pluck('name')->all();
            if ($names) {
                $lines[] = 'Countries: ' . implode(', ', $names);
            }
        }

        if (! empty($filters['location_keyword'])) {
            $lines[] = 'Location: ' . $filters['location_keyword'];
        }

        if (! empty($filters['job_experience_id'])) {
            $exp = JobExperience::find($filters['job_experience_id']);
            if ($exp) {
                $lines[] = 'Experience Level: ' . $exp->name;
            }
        }

        if (! empty($filters['company_keywords'])) {
            $lines[] = 'Companies: ' . implode(', ', (array) $filters['company_keywords']);
        }

        if (! $lines) {
            $lines[] = 'No filters set yet — you currently receive every new job posted.';
        }

        return $lines;
    }

    /**
     * Categories/job types the candidate hasn't already selected, ranked by how
     * often they show up among jobs that already match the candidate's keywords
     * and country — i.e. grounded in jobs relevant to them, not sitewide
     * popularity (a software-developer alert shouldn't get suggested "Agriculture"
     * just because it has the most postings overall).
     */
    private function suggestAdditions(array $filters): array
    {
        $excludeCategoryIds = array_map('intval', (array) ($filters['category_ids'] ?? []));
        $excludeJobTypeIds  = array_map('intval', (array) ($filters['job_type_ids'] ?? []));

        $jobsQuery = fn () => $this->matchingJobsBaseQuery($filters);

        $categorySuggestions = DB::table('jb_jobs_categories')
            ->join('jb_categories', 'jb_categories.id', '=', 'jb_jobs_categories.category_id')
            ->joinSub($jobsQuery()->select('id'), 'open_jobs', 'open_jobs.id', '=', 'jb_jobs_categories.job_id')
            ->when($excludeCategoryIds, fn ($q) => $q->whereNotIn('jb_jobs_categories.category_id', $excludeCategoryIds))
            ->select('jb_categories.name', DB::raw('COUNT(*) as job_count'))
            ->groupBy('jb_categories.id', 'jb_categories.name')
            ->orderByDesc('job_count')
            ->limit(3)
            ->get();

        $jobTypeSuggestions = DB::table('jb_jobs_types')
            ->join('jb_job_types', 'jb_job_types.id', '=', 'jb_jobs_types.job_type_id')
            ->joinSub($jobsQuery()->select('id'), 'open_jobs', 'open_jobs.id', '=', 'jb_jobs_types.job_id')
            ->when($excludeJobTypeIds, fn ($q) => $q->whereNotIn('jb_jobs_types.job_type_id', $excludeJobTypeIds))
            ->select('jb_job_types.name', DB::raw('COUNT(*) as job_count'))
            ->groupBy('jb_job_types.id', 'jb_job_types.name')
            ->orderByDesc('job_count')
            ->limit(3)
            ->get();

        return [
            'categories' => $categorySuggestions->map(fn ($r) => ['name' => $r->name, 'job_count' => (int) $r->job_count])->all(),
            'job_types'  => $jobTypeSuggestions->map(fn ($r) => ['name' => $r->name, 'job_count' => (int) $r->job_count])->all(),
        ];
    }

    /** Published jobs matching the candidate's keywords + country only (not category/job type yet). */
    private function matchingJobsBaseQuery(array $filters)
    {
        $query = Job::query()->where('status', JobStatusEnum::PUBLISHED);

        $keywords = array_values(array_filter(array_map('trim', (array) ($filters['keywords'] ?? []))));
        if ($keywords) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere('name', 'like', "%{$kw}%")
                      ->orWhere('description', 'like', "%{$kw}%");
                }
            });
        }

        if (! empty($filters['country_ids'])) {
            $query->whereIn('country_id', array_map('intval', (array) $filters['country_ids']));
        }

        return $query;
    }

    /** How many currently-open jobs match the candidate's existing filters, for context. */
    private function countMatchingJobs(array $filters): int
    {
        $query = $this->matchingJobsBaseQuery($filters);

        if (! empty($filters['category_ids'])) {
            $ids = array_map('intval', (array) $filters['category_ids']);
            $query->whereHas('categories', fn ($q) => $q->whereIn('jb_categories.id', $ids));
        }

        if (! empty($filters['job_type_ids'])) {
            $ids = array_map('intval', (array) $filters['job_type_ids']);
            $query->whereHas('jobTypes', fn ($q) => $q->whereIn('jb_job_types.id', $ids));
        }

        return $query->count();
    }

    private function rephraseWithAi(CandidateAlert $alert, array $current, array $suggestions, int $matchedCount): ?string
    {
        $apiKey = setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (! $apiKey) {
            return null;
        }

        $context = [
            'candidate_name'     => $alert->candidate_name,
            'current_filters'    => $current,
            'matched_jobs_today' => $matchedCount,
            'suggested_categories' => $suggestions['categories'],
            'suggested_job_types'  => $suggestions['job_types'],
        ];

        $systemPrompt = <<<'PROMPT'
You write a single WhatsApp message for Wakanda Jobs (wakandajobs.com), a VIP job-alert subscription service.

The candidate signed up 2 days ago. Write a warm, confident, natural-sounding check-in message that:
1. Reminds them what their alert is currently set to (use the exact facts given — never invent or alter numbers, filter names, or counts).
2. Mentions how many open jobs currently match their alert.
3. If suggested categories/job types are given, suggest adding them by name with their job counts, framed as a helpful tip, not a hard sell.
4. Invites them to reply to this WhatsApp message to update their preferences (there is no app/website link for this — replying is the only way).
5. Ends on a confident, friendly note that reinforces staying subscribed with Wakanda Jobs.

Rules:
- Use the candidate's first name only.
- WhatsApp formatting: *bold* for emphasis, _italic_ for the closing line. 2-4 emoji max, used naturally.
- Under 130 words total.
- Do NOT invent any filter values, categories, job types, or numbers not present in the data given.
- Output ONLY the final message text — no preamble, no quotes, no markdown fences.
PROMPT;

        try {
            $response = Http::timeout(30)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => 'gpt-4o-mini',
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => json_encode($context, JSON_UNESCAPED_UNICODE)],
                    ],
                    'temperature' => 0.7,
                    'max_tokens'  => 500,
                ]);

            if (! $response->successful()) {
                Log::warning('CandidateAlertFilterTip: OpenAI error', ['status' => $response->status(), 'alert_id' => $alert->id]);

                return null;
            }

            $text = trim((string) $response->json('choices.0.message.content', ''));
            $text = preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text ?? '');

            return $text !== '' ? $text : null;
        } catch (Throwable $e) {
            Log::warning('CandidateAlertFilterTip: OpenAI call failed', ['error' => $e->getMessage(), 'alert_id' => $alert->id]);

            return null;
        }
    }

    /** Plain-template fallback if OpenAI is unavailable — still factually complete. */
    private function fallbackMessage(CandidateAlert $alert, array $current, array $suggestions, int $matchedCount): string
    {
        $name = trim((string) explode(' ', $alert->candidate_name)[0]);
        $msg  = "Hi {$name}! 👋 You've been with *Wakanda Jobs VIP Alerts* for 2 days now.\n\n";
        $msg .= "📋 *Your current alert:*\n" . implode("\n", array_map(fn ($l) => "• {$l}", $current)) . "\n\n";
        $msg .= "🔎 Right now, *{$matchedCount}* open job(s) match your alert.\n";

        $tips = [];
        foreach ($suggestions['categories'] as $s) {
            $tips[] = "{$s['name']} ({$s['job_count']} open)";
        }
        foreach ($suggestions['job_types'] as $s) {
            $tips[] = "{$s['name']} ({$s['job_count']} open)";
        }
        if ($tips) {
            $msg .= "\n💡 You could also widen your alert to: " . implode(', ', array_slice($tips, 0, 3)) . ".\n";
        }

        $msg .= "\nReply to this message anytime to update your filters — we're happy to do it for you. 🚀";

        return $msg;
    }
}
