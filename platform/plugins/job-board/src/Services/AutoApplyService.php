<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AutoApplyLog;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Models\AutoApplyPreference;
use Botble\JobBoard\Models\AutoApplyQuota;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobApplication;
use Botble\JobBoard\Jobs\NotifyCandidateAutoApplySentJob;
use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AutoApplyService
{
    /**
     * Placeholder patterns that indicate OpenAI left a template variable in the output.
     */
    private const PLACEHOLDER_PATTERNS = [
        '/\[.*?\]/',         // [Your Name], [Company Name], etc.
        '/\{.*?\}/',         // {candidate_name}, {job_title}, etc.
        '/<<.*?>>/',         // <<insert here>>
        '/\bINSERT\b/i',
        '/\bPLACEHOLDER\b/i',
        '/\bTODO\b/i',
        '/\bXXX\b/',
    ];

    /**
     * Extract text content from a candidate's uploaded CV (PDF or text file).
     */
    public function extractCvText(Account $account): string
    {
        $resumePath = trim((string) $account->resume);
        if ($resumePath === '') {
            return '';
        }

        $realPath = RvMedia::getRealPath($resumePath);

        // If it's a URL, download it first
        if (filter_var($realPath, FILTER_VALIDATE_URL)) {
            try {
                $tempFile = tempnam(sys_get_temp_dir(), 'cv_');
                file_put_contents($tempFile, file_get_contents($realPath));
                $text = $this->parseFileText($tempFile, $resumePath);
                @unlink($tempFile);

                return $text;
            } catch (Throwable $e) {
                Log::warning('AutoApply: Failed to download CV', ['account_id' => $account->id, 'error' => $e->getMessage()]);

                return '';
            }
        }

        // Local file
        if (! file_exists($realPath)) {
            return '';
        }

        return $this->parseFileText($realPath, $resumePath);
    }

    private function parseFileText(string $filePath, string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            // Try smalot/pdfparser if installed (composer require smalot/pdfparser)
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($filePath);

                    return mb_substr(trim($pdf->getText()), 0, 8000);
                } catch (Throwable $e) {
                    Log::warning('AutoApply: PDF parse failed', ['error' => $e->getMessage()]);
                }
            }

            // Fallback: try pdftotext CLI if available
            $output = [];
            $returnVar = -1;
            @exec('pdftotext ' . escapeshellarg($filePath) . ' - 2>/dev/null', $output, $returnVar);
            if ($returnVar === 0 && ! empty($output)) {
                return mb_substr(trim(implode("\n", $output)), 0, 8000);
            }

            Log::warning('AutoApply: No PDF parser available. Install smalot/pdfparser or pdftotext.');

            return '';
        }

        // Plain text / docx fallback — read raw text
        if (in_array($extension, ['txt', 'text', 'rtf'])) {
            return mb_substr(trim((string) file_get_contents($filePath)), 0, 8000);
        }

        return '';
    }

    /**
     * Build candidate profile text for OpenAI from account fields + CV text.
     */
    public function buildCandidateProfile(Account $account, string $cvText): string
    {
        $parts = [];

        $parts[] = "Name: {$account->first_name} {$account->last_name}";

        if ($account->bio) {
            $parts[] = "Bio: {$account->bio}";
        }

        if ($account->description) {
            $parts[] = "Professional Summary: {$account->description}";
        }

        if ($account->cover_letter) {
            $parts[] = "Cover Letter Template: {$account->cover_letter}";
        }

        if ($account->experience_years) {
            $parts[] = "Experience: {$account->experience_years} years";
        }

        if ($account->education_level) {
            $parts[] = "Education: " . ucfirst(str_replace('_', ' ', $account->education_level));
        }

        if ($account->availability) {
            $parts[] = "Availability: " . ucfirst(str_replace('_', ' ', $account->availability));
        }

        // Load skills/tags if available
        if (method_exists($account, 'skills') && $account->skills->isNotEmpty()) {
            $parts[] = "Skills: " . $account->skills->pluck('name')->implode(', ');
        }

        if ($cvText !== '') {
            $parts[] = "\n--- CV CONTENT ---\n{$cvText}";
        }

        return implode("\n", $parts);
    }

    /**
     * Call OpenAI to score the match and draft the application email.
     *
     * Returns: ['score' => int, 'reasons' => array, 'subject' => string, 'body' => string] or null on failure.
     */
    public function generateApplicationEmail(Account $account, Job $job, string $candidateProfile, ?string $aiModel = null): ?array
    {
        $apiKey = setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (! $apiKey) {
            Log::error('AutoApply: No OpenAI API key configured');

            return null;
        }

        $model = $aiModel ?: AutoApplyOrder::globalAiModel();

        $job->loadMissing(['company', 'categories', 'skills', 'jobTypes']);

        $jobInfo = "Job Title: {$job->name}\n";
        if ($job->company?->name) {
            $jobInfo .= "Company: {$job->company->name}\n";
        }
        if ($job->address) {
            $jobInfo .= "Location: {$job->address}\n";
        }
        $jobTypes = $job->jobTypes->pluck('name')->implode(', ');
        if ($jobTypes) {
            $jobInfo .= "Job Type: {$jobTypes}\n";
        }
        $categories = $job->categories->pluck('name')->implode(', ');
        if ($categories) {
            $jobInfo .= "Categories: {$categories}\n";
        }
        $skills = $job->skills->pluck('name')->implode(', ');
        if ($skills) {
            $jobInfo .= "Required Skills: {$skills}\n";
        }
        if ($job->description) {
            $jobInfo .= "\nJob Description:\n" . mb_substr(strip_tags($job->description), 0, 4000);
        }
        if ($job->content && $job->content !== $job->description) {
            $jobInfo .= "\n\nDetailed Requirements:\n" . mb_substr(strip_tags($job->content), 0, 3000);
        }

        $systemPrompt = <<<'PROMPT'
You are a professional job application assistant. You will be given a candidate's profile/CV and a job posting.

Your task:
1. Score how well the candidate matches the job (0-100).
2. List 2-4 brief reasons for the score.
3. Write a professional, personalized application email.

Rules for the email:
- The email should be 3-4 paragraphs.
- Be specific about how the candidate's experience matches the job requirements.
- Be professional but warm and genuine — not robotic.
- NEVER use placeholders like [Your Name], [Company Name], {name}, etc. Use the actual values provided.
- NEVER include instructions or notes to the candidate — this email will be sent directly.
- The subject line should mention the job title and be compelling.
- End with the candidate's actual name.
- Do NOT include a date, address block, or "Dear Hiring Manager" style headers — start with a greeting.

Respond in this exact JSON format:
{
  "score": <integer 0-100>,
  "reasons": ["reason1", "reason2", ...],
  "subject": "Email subject line",
  "body": "Full email body text"
}
PROMPT;

        $userPrompt = "=== CANDIDATE PROFILE ===\n{$candidateProfile}\n\n=== JOB POSTING ===\n{$jobInfo}";

        try {
            $response = Http::timeout(60)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => $model,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens'  => 1500,
                ]);

            if (! $response->successful()) {
                Log::error('AutoApply: OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            $content = $response->json('choices.0.message.content', '');
            $content = trim($content);

            // Strip markdown code fence if present
            if (str_starts_with($content, '```')) {
                $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
                $content = preg_replace('/\s*```$/', '', $content);
            }

            $parsed = json_decode($content, true);
            if (! $parsed || ! isset($parsed['score'], $parsed['subject'], $parsed['body'])) {
                Log::error('AutoApply: Failed to parse OpenAI response', ['content' => $content]);

                return null;
            }

            $promptTokens = (int) $response->json('usage.prompt_tokens', 0);
            $completionTokens = (int) $response->json('usage.completion_tokens', 0);
            $totalTokens = (int) $response->json('usage.total_tokens', $promptTokens + $completionTokens);

            return [
                'score'             => (int) $parsed['score'],
                'reasons'           => (array) ($parsed['reasons'] ?? []),
                'subject'           => (string) $parsed['subject'],
                'body'              => (string) $parsed['body'],
                'ai_model'          => $model,
                'prompt_tokens'     => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens'      => $totalTokens,
                'cost'              => $this->calculateAiCost($model, $promptTokens, $completionTokens),
            ];
        } catch (Throwable $e) {
            Log::error('AutoApply: OpenAI call failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * USD price per 1M tokens, [input, output]. Update if OpenAI changes pricing.
     */
    private const AI_PRICING_PER_MILLION_TOKENS = [
        'gpt-4o-mini' => [0.15, 0.60],
        'gpt-4o'      => [2.50, 10.00],
    ];

    public function calculateAiCost(string $model, int $promptTokens, int $completionTokens): float
    {
        [$inputRate, $outputRate] = self::AI_PRICING_PER_MILLION_TOKENS[$model] ?? self::AI_PRICING_PER_MILLION_TOKENS['gpt-4o-mini'];

        return round(($promptTokens * $inputRate + $completionTokens * $outputRate) / 1_000_000, 6);
    }

    /**
     * Check email body for leftover placeholders that OpenAI may have inserted.
     */
    public function containsPlaceholders(string $text): bool
    {
        foreach (self::PLACEHOLDER_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send the application email with CV attached, Reply-To set to candidate's email.
     */
    public function sendApplicationEmail(Account $account, Job $job, string $subject, string $body, string $toEmail): bool
    {
        $resumePath = trim((string) $account->resume);
        $attachmentPath = $resumePath !== '' ? RvMedia::getRealPath($resumePath) : null;

        // For URL-based files, download to temp
        $tempFile = null;
        if ($attachmentPath && filter_var($attachmentPath, FILTER_VALIDATE_URL)) {
            try {
                $ext = pathinfo($resumePath, PATHINFO_EXTENSION) ?: 'pdf';
                $tempFile = tempnam(sys_get_temp_dir(), 'cv_') . '.' . $ext;
                file_put_contents($tempFile, file_get_contents($attachmentPath));
                $attachmentPath = $tempFile;
            } catch (Throwable) {
                $attachmentPath = null;
            }
        }

        try {
            Mail::raw($body, function ($message) use ($account, $subject, $toEmail, $attachmentPath, $resumePath) {
                $message->to($toEmail)
                    ->subject($subject)
                    ->replyTo($account->email, "{$account->first_name} {$account->last_name}");

                if ($attachmentPath && file_exists($attachmentPath)) {
                    $ext = pathinfo($resumePath, PATHINFO_EXTENSION) ?: 'pdf';
                    $filename = "{$account->first_name}_{$account->last_name}_CV.{$ext}";
                    $message->attach($attachmentPath, ['as' => $filename]);
                }
            });

            return true;
        } catch (Throwable $e) {
            Log::error('AutoApply: Email send failed', [
                'account_id' => $account->id,
                'job_id'     => $job->id,
                'to'         => $toEmail,
                'error'      => $e->getMessage(),
            ]);

            return false;
        } finally {
            if ($tempFile && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * Create a JobApplication record in the system so it shows in the candidate's applications dashboard.
     */
    public function createJobApplication(Account $account, Job $job, string $emailBody): JobApplication
    {
        return JobApplication::create([
            'first_name'   => $account->first_name,
            'last_name'    => $account->last_name,
            'phone'        => $account->phone,
            'email'        => $account->email,
            'resume'       => $account->resume,
            'cover_letter' => mb_substr($emailBody, 0, 5000),
            'message'      => 'Auto-applied via Wakanda Jobs Auto Apply service',
            'job_id'       => $job->id,
            'account_id'   => $account->id,
            'status'       => 'pending',
        ]);
    }

    /**
     * Check if candidate has remaining auto-apply quota for the current period.
     */
    public function hasQuota(int $accountId): bool
    {
        $quota = AutoApplyQuota::currentForAccount($accountId);

        return $quota?->is_approved && $quota->hasRemaining();
    }

    /**
     * Deduct one application from the candidate's quota.
     */
    public function deductQuota(int $accountId): bool
    {
        $quota = AutoApplyQuota::currentForAccount($accountId);

        if (! $quota || ! $quota->hasRemaining()) {
            return false;
        }

        return $quota->consumeOne();
    }

    /**
     * Check if this candidate has already applied to this job (manual or auto).
     */
    public function hasAlreadyApplied(int $accountId, int $jobId): bool
    {
        // Check manual applications
        if (JobApplication::where('account_id', $accountId)->where('job_id', $jobId)->exists()) {
            return true;
        }

        // Check auto-apply logs
        if (AutoApplyLog::where('account_id', $accountId)->where('job_id', $jobId)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Full auto-apply flow for a single candidate + job combination.
     * Returns the log entry or null if skipped/failed.
     */
    public function processAutoApply(Account $account, Job $job, string $candidateProfile, ?string $aiModel = null): ?AutoApplyLog
    {
        $toEmail = trim((string) $job->apply_email);
        if ($toEmail === '') {
            return null;
        }

        if (! $this->hasQuota($account->id)) {
            return AutoApplyLog::create([
                'account_id' => $account->id,
                'job_id' => $job->id,
                'email_sent_to' => $toEmail,
                'ai_model_used' => $aiModel ?: AutoApplyOrder::globalAiModel(),
                'match_score' => 0,
                'status' => 'failed',
                'error_message' => 'No active auto-apply quota available for the current billing cycle',
                'sent_at' => now(),
            ]);
        }

        // Generate AI email
        $result = $this->generateApplicationEmail($account, $job, $candidateProfile, $aiModel);
        if (! $result) {
            return AutoApplyLog::create([
                'account_id'    => $account->id,
                'job_id'        => $job->id,
                'email_sent_to' => $toEmail,
                'ai_model_used' => $aiModel ?: AutoApplyOrder::globalAiModel(),
                'match_score'   => 0,
                'status'        => 'failed',
                'error_message' => 'OpenAI failed to generate email',
                'sent_at'       => now(),
            ]);
        }

        // Check match score threshold
        $preference = AutoApplyPreference::where('account_id', $account->id)->first();
        $threshold = $preference?->match_score_threshold ?? AutoApplyOrder::globalMatchThreshold();

        if ($result['score'] < $threshold) {
            return AutoApplyLog::create([
                'account_id'        => $account->id,
                'job_id'            => $job->id,
                'email_sent_to'     => $toEmail,
                'ai_email_subject'  => $result['subject'],
                'ai_email_body'     => $result['body'],
                'ai_model_used'     => $aiModel ?: AutoApplyOrder::globalAiModel(),
                'prompt_tokens'     => $result['prompt_tokens'] ?? null,
                'completion_tokens' => $result['completion_tokens'] ?? null,
                'total_tokens'      => $result['total_tokens'] ?? null,
                'ai_cost_usd'       => $result['cost'] ?? null,
                'match_score'       => $result['score'],
                'match_reasons'     => $result['reasons'],
                'status'            => 'skipped_low_score',
                'error_message'     => "Score {$result['score']} below threshold {$threshold}",
                'sent_at'           => now(),
            ]);
        }

        // Safety: check for leftover placeholders
        if ($this->containsPlaceholders($result['body']) || $this->containsPlaceholders($result['subject'])) {
            return AutoApplyLog::create([
                'account_id'        => $account->id,
                'job_id'            => $job->id,
                'email_sent_to'     => $toEmail,
                'ai_email_subject'  => $result['subject'],
                'ai_email_body'     => $result['body'],
                'ai_model_used'     => $aiModel ?: AutoApplyOrder::globalAiModel(),
                'prompt_tokens'     => $result['prompt_tokens'] ?? null,
                'completion_tokens' => $result['completion_tokens'] ?? null,
                'total_tokens'      => $result['total_tokens'] ?? null,
                'ai_cost_usd'       => $result['cost'] ?? null,
                'match_score'       => $result['score'],
                'match_reasons'     => $result['reasons'],
                'status'            => 'failed',
                'error_message'     => 'Email contained placeholders — blocked for safety',
                'sent_at'           => now(),
            ]);
        }

        // Send the email
        $sent = $this->sendApplicationEmail($account, $job, $result['subject'], $result['body'], $toEmail);

        if ($sent) {
            // Create a JobApplication record
            $this->createJobApplication($account, $job, $result['body']);

            // Deduct quota
            if (! $this->deductQuota($account->id)) {
                return AutoApplyLog::create([
                    'account_id'        => $account->id,
                    'job_id'            => $job->id,
                    'email_sent_to'     => $toEmail,
                    'ai_email_subject'  => $result['subject'],
                    'ai_email_body'     => $result['body'],
                    'ai_model_used'     => $aiModel ?: AutoApplyOrder::globalAiModel(),
                    'prompt_tokens'     => $result['prompt_tokens'] ?? null,
                    'completion_tokens' => $result['completion_tokens'] ?? null,
                    'total_tokens'      => $result['total_tokens'] ?? null,
                    'ai_cost_usd'       => $result['cost'] ?? null,
                    'match_score'       => $result['score'],
                    'match_reasons'     => $result['reasons'],
                    'status'            => 'failed',
                    'error_message'     => 'Application email sent but quota deduction failed',
                    'sent_at'           => now(),
                ]);
            }

            // Let the candidate know — queued separately so it never blocks the auto-apply send itself
            NotifyCandidateAutoApplySentJob::dispatch($account->id, $job->id)->onQueue('default');
        }

        return AutoApplyLog::create([
            'account_id'        => $account->id,
            'job_id'            => $job->id,
            'email_sent_to'     => $toEmail,
            'ai_email_subject'  => $result['subject'],
            'ai_email_body'     => $result['body'],
            'ai_model_used'     => $aiModel ?: AutoApplyOrder::globalAiModel(),
            'prompt_tokens'     => $result['prompt_tokens'] ?? null,
            'completion_tokens' => $result['completion_tokens'] ?? null,
            'total_tokens'      => $result['total_tokens'] ?? null,
            'ai_cost_usd'       => $result['cost'] ?? null,
            'match_score'       => $result['score'],
            'match_reasons'     => $result['reasons'],
            'status'            => $sent ? 'sent' : 'failed',
            'error_message'     => $sent ? null : 'SMTP delivery failed',
            'sent_at'           => now(),
        ]);
    }
}
