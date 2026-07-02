<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AutoApplyLog;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Models\AutoApplyPreference;
use Botble\JobBoard\Models\AutoApplyPreview;
use Botble\JobBoard\Models\AutoApplyQuota;
use Botble\JobBoard\Models\AutoCvSession;
use Botble\JobBoard\Models\CandidateAlert;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobApplication;
use Botble\JobBoard\Jobs\NotifyCandidateAutoApplySentJob;
use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
- Do NOT use bullet points, numbered lists, or lines that begin with a hyphen.
- Keep the body in plain natural paragraphs so it reads like a human wrote it.

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

    public function isManualNoticeDraft(string $subject, string $body): bool
    {
        $normalizedSubject = trim(mb_strtolower($subject));

        return $normalizedSubject === 'manual apply notice sent to candidate'
            || str_contains($body, 'This job does not include an application email')
            || str_contains($body, '*Cover letter to copy and paste:*');
    }

    /**
     * Send the application email with CV attached, Reply-To set to candidate's email.
     */
    public function sendApplicationEmail(Account $account, Job $job, string $subject, string $body, string $toEmail, string $messageId): bool
    {
        $resumePath = trim((string) $account->resume);
        $attachmentPath = $resumePath !== '' ? RvMedia::getRealPath($resumePath) : null;
        $attachmentFilename = null;
        $messageId = trim($messageId, " \t\n\r\0\x0B<>");

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

        if ($resumePath !== '') {
            $ext = pathinfo($resumePath, PATHINFO_EXTENSION) ?: 'pdf';
            $attachmentFilename = "{$account->first_name}_{$account->last_name}_CV.{$ext}";
        }

        Log::info('AutoApply: Sending application email', [
            'account_id' => $account->id,
            'job_id' => $job->id,
            'to' => $toEmail,
            'reply_to' => $account->email,
            'message_id' => $messageId,
            'resume_path' => $resumePath !== '' ? $resumePath : null,
            'attachment_path' => $attachmentPath,
            'attachment_filename' => $attachmentFilename,
            'attachment_exists' => $attachmentPath ? file_exists($attachmentPath) : false,
        ]);

        try {
            Mail::raw($body, function ($message) use ($account, $subject, $toEmail, $attachmentPath, $resumePath, $messageId, $attachmentFilename) {
                $message->to($toEmail)
                    ->subject($subject)
                    ->replyTo($account->email, "{$account->first_name} {$account->last_name}");

                // Tags the outgoing Message-ID so a later employer reply (which quotes it back via
                // In-Reply-To/References) can be matched to this exact AutoApplyLog row and forwarded
                // to the candidate, even when the employer's auto-responder ignores Reply-To.
                $message->getSymfonyMessage()->getHeaders()->addIdHeader('Message-ID', $messageId);

                if ($attachmentPath && file_exists($attachmentPath)) {
                    $message->attach($attachmentPath, ['as' => $attachmentFilename]);
                }
            });

            Log::info('AutoApply: Application email sent', [
                'account_id' => $account->id,
                'job_id' => $job->id,
                'to' => $toEmail,
                'message_id' => $messageId,
                'attachment_filename' => $attachmentFilename,
                'attachment_exists' => $attachmentPath ? file_exists($attachmentPath) : false,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('AutoApply: Email send failed', [
                'account_id' => $account->id,
                'job_id'     => $job->id,
                'to'         => $toEmail,
                'message_id' => $messageId,
                'attachment_filename' => $attachmentFilename,
                'attachment_exists' => $attachmentPath ? file_exists($attachmentPath) : false,
                'error'      => $e->getMessage(),
            ]);

            $this->notifySendFailureByWhatsApp($account, $job, $toEmail, $attachmentFilename, $attachmentPath ? file_exists($attachmentPath) : false, $e->getMessage());

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

    public function upsertAutoApplyJobApplication(Account $account, Job $job, string $emailBody, ?string $message = null): JobApplication
    {
        $application = JobApplication::query()
            ->where('account_id', $account->id)
            ->where('job_id', $job->id)
            ->first();

        if (! $application) {
            return $this->createJobApplication($account, $job, $emailBody);
        }

        $application->forceFill([
            'first_name' => $account->first_name,
            'last_name' => $account->last_name,
            'phone' => $account->phone,
            'email' => $account->email,
            'resume' => $account->resume,
            'cover_letter' => mb_substr($emailBody, 0, 5000),
            'message' => $message ?: 'Auto-applied via Wakanda Jobs Auto Apply service',
            'status' => 'pending',
            'is_external_apply' => false,
        ])->save();

        return $application;
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

    public function hasAlreadyAppliedForJob(int $accountId, Job $job): bool
    {
        $application = JobApplication::query()
            ->where('account_id', $accountId)
            ->where('job_id', $job->id)
            ->first();

        if ($application) {
            $isManualExternalApplication = (bool) $application->is_external_apply
                && $this->resolveJobApplyEmail($job) !== ''
                && $this->hasManualOnlyAutoApplyLogs($accountId, $job->id);

            if (! $isManualExternalApplication) {
                return true;
            }
        }

        $logs = AutoApplyLog::query()
            ->where('account_id', $accountId)
            ->where('job_id', $job->id);

        if (! $logs->exists()) {
            return false;
        }

        if ($this->resolveJobApplyEmail($job) !== '' && $this->hasManualOnlyAutoApplyLogs($accountId, $job->id)) {
            return false;
        }

        return true;
    }

    public function hasManualOnlyAutoApplyLogs(int $accountId, int $jobId): bool
    {
        $logs = AutoApplyLog::query()
            ->where('account_id', $accountId)
            ->where('job_id', $jobId);

        return (clone $logs)->where('email_sent_to', 'manual-apply-notice')->exists()
            && ! (clone $logs)->where('email_sent_to', '!=', 'manual-apply-notice')->exists();
    }

    /**
     * Full auto-apply flow for a single candidate + job combination.
     * Returns the log entry or null if skipped/failed.
     */
    public function processAutoApply(Account $account, Job $job, string $candidateProfile, ?string $aiModel = null): ?AutoApplyLog
    {
        $toEmail = $this->resolveJobApplyEmail($job);
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
        $messageId = sprintf('auto-apply-%s@%s', (string) Str::uuid(), parse_url(config('app.url'), PHP_URL_HOST) ?: 'wakandajobs.com');
        $sent = $this->sendApplicationEmail($account, $job, $result['subject'], $result['body'], $toEmail, $messageId);

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
            NotifyCandidateAutoApplySentJob::dispatch($account->id, $job->id, $result['subject'], $result['body'])->onQueue('default');
        }

        return AutoApplyLog::create([
            'account_id'        => $account->id,
            'job_id'            => $job->id,
            'email_sent_to'     => $toEmail,
            'message_id'        => $sent ? $messageId : null,
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

    /**
     * Admin-triggered single application flow that reuses the same AI cover-letter generation
     * and manual-notify fallback as Auto Apply, but does not require an Auto Apply quota.
     *
     * @return array{status:string, job_id:int, message:string, score?:int}
     */
    public function sendOnDemandApplication(Account $account, Job $job, ?string $aiModel = null): array
    {
        if (trim((string) $account->resume) === '') {
            return [
                'status' => 'missing_cv',
                'job_id' => $job->id,
                'message' => 'Candidate has no CV uploaded.',
            ];
        }

        if ($this->hasAlreadyAppliedForJob($account->id, $job)) {
            return [
                'status' => 'already_processed',
                'job_id' => $job->id,
                'message' => 'This candidate has already been processed for this job.',
            ];
        }

        $preview = $this->resolvePreviewForJob($account, $job, $aiModel);

        if (! $preview) {
            return [
                'status' => 'generation_failed',
                'job_id' => $job->id,
                'message' => 'Could not generate the cover letter and application email for this candidate.',
            ];
        }

        $score = (int) ($preview['score'] ?? 0);
        $subject = trim((string) ($preview['subject'] ?? ''));
        $body = trim((string) ($preview['body'] ?? ''));
        $model = (string) ($preview['ai_model'] ?? ($aiModel ?: AutoApplyOrder::globalAiModel()));

        if ($subject === '' || $body === '') {
            return [
                'status' => 'generation_failed',
                'job_id' => $job->id,
                'message' => 'The AI response was incomplete, so the application was not sent.',
            ];
        }

        if ($this->containsPlaceholders($subject) || $this->containsPlaceholders($body)) {
            AutoApplyLog::create([
                'account_id' => $account->id,
                'job_id' => $job->id,
                'email_sent_to' => $this->resolveJobApplyEmail($job) ?: 'manual-apply-notice',
                'ai_email_subject' => $subject,
                'ai_email_body' => $body,
                'ai_model_used' => $model,
                'prompt_tokens' => $preview['prompt_tokens'] ?? null,
                'completion_tokens' => $preview['completion_tokens'] ?? null,
                'total_tokens' => $preview['total_tokens'] ?? null,
                'ai_cost_usd' => $preview['cost'] ?? null,
                'match_score' => $score,
                'match_reasons' => $preview['reasons'] ?? [],
                'status' => 'failed',
                'error_message' => 'Email contained placeholders — blocked for safety',
                'sent_at' => now(),
            ]);

            return [
                'status' => 'placeholder_blocked',
                'job_id' => $job->id,
                'message' => 'The generated application still contained placeholders, so it was blocked for safety.',
                'score' => $score,
            ];
        }

        if ($this->resolveJobApplyEmail($job) === '') {
            $manualResult = $this->sendManualApplyPackage($account, $job, $score, $preview);
            $manualResult['score'] = $score;

            return $manualResult;
        }

        $toEmail = $this->resolveJobApplyEmail($job);
        $messageId = sprintf('vip-on-demand-%s@%s', (string) Str::uuid(), parse_url(config('app.url'), PHP_URL_HOST) ?: 'wakandajobs.com');
        $sent = $this->sendApplicationEmail($account, $job, $subject, $body, $toEmail, $messageId);

        if ($sent) {
            $this->createJobApplication($account, $job, $body);
            NotifyCandidateAutoApplySentJob::dispatch($account->id, $job->id, $subject, $body)->onQueue('default');
        }

        AutoApplyLog::create([
            'account_id' => $account->id,
            'job_id' => $job->id,
            'email_sent_to' => $toEmail,
            'message_id' => $sent ? $messageId : null,
            'ai_email_subject' => $subject,
            'ai_email_body' => $body,
            'ai_model_used' => $model,
            'prompt_tokens' => $preview['prompt_tokens'] ?? null,
            'completion_tokens' => $preview['completion_tokens'] ?? null,
            'total_tokens' => $preview['total_tokens'] ?? null,
            'ai_cost_usd' => $preview['cost'] ?? null,
            'match_score' => $score,
            'match_reasons' => $preview['reasons'] ?? [],
            'status' => $sent ? 'sent' : 'failed',
            'error_message' => $sent ? 'On-demand VIP application sent by admin.' : 'SMTP delivery failed',
            'sent_at' => now(),
        ]);

        return [
            'status' => $sent ? 'sent' : 'failed',
            'job_id' => $job->id,
            'message' => $sent
                ? 'Application sent on demand. The cover letter was generated from the customer CV and submitted to the job application email.'
                : 'The application email could not be delivered.',
            'score' => $score,
        ];
    }

    private function notifySendFailureByWhatsApp(Account $account, Job $job, string $toEmail, ?string $attachmentFilename, bool $attachmentExists, string $error): void
    {
        $adminNumber = '+260970766123';
        $sender = app(WhapiSenderService::class);

        $message = "Auto Apply email failed.\n\n"
            . "Candidate: {$account->first_name} {$account->last_name} (ID {$account->id})\n"
            . "Job: {$job->name} (ID {$job->id})\n"
            . "To: {$toEmail}\n"
            . 'Attachment: ' . ($attachmentFilename ?: 'None')
            . ' | Exists: ' . ($attachmentExists ? 'Yes' : 'No') . "\n"
            . 'Error: ' . Str::limit($error, 500, '');

        $errorMessage = null;

        if (! $sender->sendText($adminNumber, $message, $errorMessage)) {
            Log::error('AutoApply: Failed to send WhatsApp failure alert', [
                'account_id' => $account->id,
                'job_id' => $job->id,
                'admin_phone' => $adminNumber,
                'error' => $errorMessage ?: 'Unknown WhatsApp send failure',
            ]);
        }
    }

    /**
     * Resolve the effective application email for a job.
     * Checks apply_email first, then mailto: in apply_url.
     */
    public function resolveJobApplyEmail(Job $job): string
    {
        $email = trim((string) $job->apply_email);
        if ($email !== '') {
            return $email;
        }

        $url = trim((string) $job->apply_url);
        if (preg_match('/^mailto:([^?]+)/i', $url, $m)) {
            return trim(rawurldecode($m[1]));
        }

        return '';
    }

    /**
     * Resolve the best WhatsApp / phone number for a candidate.
     * Checks account fields, then AutoCvSession, then CandidateAlert history.
     */
    public function resolveCandidateWhatsAppNumber(Account $account): string
    {
        $direct = trim((string) (($account->whatsapp_numbers[0] ?? null) ?: ($account->call_numbers[0] ?? null) ?: ($account->whatsapp_number ?: $account->phone)));
        if ($direct !== '') {
            return $direct;
        }

        $fullName = trim((string) $account->name);
        if ($fullName === '') {
            return '';
        }

        $sessionNumber = trim((string) AutoCvSession::query()
            ->where('candidate_name', $fullName)
            ->whereNotNull('whatsapp_number')
            ->latest('id')
            ->value('whatsapp_number'));

        if ($sessionNumber !== '') {
            return $sessionNumber;
        }

        return trim((string) CandidateAlert::query()
            ->where(function ($q) use ($account, $fullName): void {
                $q->where('account_id', $account->id)
                    ->orWhere('candidate_name', $fullName);
            })
            ->whereNotNull('candidate_phone')
            ->latest('id')
            ->value('candidate_phone'));
    }

    public function resolvePreviewForJob(Account $account, Job $job, ?string $aiModel = null): ?array
    {
        $model = $aiModel ?: AutoApplyOrder::globalAiModel();
        $profileSyncedAt = $account->profile_updated_at ?? $account->updated_at;

        $cached = AutoApplyPreview::query()
            ->where('account_id', $account->id)
            ->where('job_id', $job->id)
            ->where('ai_model', $model)
            ->first();

        if (
            $cached
            && $cached->account_profile_synced_at
            && $profileSyncedAt
            && $cached->account_profile_synced_at->gte($profileSyncedAt)
        ) {
            return [
                'score' => $cached->score,
                'reasons' => $cached->reasons ?? [],
                'subject' => $cached->subject,
                'body' => $cached->body,
                'ai_model' => $cached->ai_model,
                'prompt_tokens' => $cached->prompt_tokens,
                'completion_tokens' => $cached->completion_tokens,
                'total_tokens' => $cached->total_tokens,
                'cost' => $cached->cost_usd,
                'cached' => true,
            ];
        }

        $cvText = $this->extractCvText($account);
        $profile = $this->buildCandidateProfile($account, $cvText);
        $result = $this->generateApplicationEmail($account, $job, $profile, $model);

        if (! $result) {
            return null;
        }

        AutoApplyPreview::updateOrCreate(
            ['account_id' => $account->id, 'job_id' => $job->id, 'ai_model' => $model],
            [
                'score' => $result['score'],
                'reasons' => $result['reasons'],
                'subject' => $result['subject'],
                'body' => $result['body'],
                'prompt_tokens' => $result['prompt_tokens'] ?? null,
                'completion_tokens' => $result['completion_tokens'] ?? null,
                'total_tokens' => $result['total_tokens'] ?? null,
                'cost_usd' => $result['cost'] ?? null,
                'account_profile_synced_at' => $profileSyncedAt,
            ]
        );

        $result['cached'] = false;

        return $result;
    }

    public function resolveAutoApplyScore(Account $account, Job $job): ?int
    {
        $log = AutoApplyLog::query()
            ->where('account_id', $account->id)
            ->where('job_id', $job->id)
            ->latest('id')
            ->first();

        if ($log && $log->match_score !== null) {
            return (int) $log->match_score;
        }

        $profileSyncedAt = $account->profile_updated_at ?? $account->updated_at;
        $aiModel = AutoApplyOrder::globalAiModel();

        $cached = AutoApplyPreview::query()
            ->where('account_id', $account->id)
            ->where('job_id', $job->id)
            ->where('ai_model', $aiModel)
            ->first();

        if (
            $cached
            && $cached->score !== null
            && $cached->account_profile_synced_at
            && $profileSyncedAt
            && $cached->account_profile_synced_at->gte($profileSyncedAt)
        ) {
            return (int) $cached->score;
        }

        $result = $this->resolvePreviewForJob($account, $job, $aiModel);

        if (! $result || ! isset($result['score'])) {
            return null;
        }

        return (int) $result['score'];
    }

    public function queueAutoApplyJob(Account $account, Job $job, int $threshold, bool $alreadyProcessed = false): array
    {
        if ($alreadyProcessed) {
            return [
                'status' => 'already_processed',
                'job_id' => $job->id,
            ];
        }

        $score = $this->resolveAutoApplyScore($account, $job);

        if ($score === null) {
            return [
                'status' => 'scoring_failed',
                'job_id' => $job->id,
            ];
        }

        if ($score < $threshold) {
            return [
                'status' => 'below_threshold',
                'job_id' => $job->id,
            ];
        }

        if ($this->resolveJobApplyEmail($job) === '') {
            return $this->sendManualApplyPackage($account, $job, $score);
        }

        $preview = AutoApplyPreview::query()
            ->where('account_id', $account->id)
            ->where('job_id', $job->id)
            ->where('ai_model', AutoApplyOrder::globalAiModel())
            ->latest('id')
            ->first();

        AutoApplyLog::create([
            'account_id' => $account->id,
            'job_id' => $job->id,
            'email_sent_to' => $this->resolveJobApplyEmail($job),
            'ai_email_subject' => $preview?->subject ?: 'Queued for auto apply send',
            'ai_email_body' => $preview?->body ?: '',
            'ai_model_used' => $preview?->ai_model ?: AutoApplyOrder::globalAiModel(),
            'prompt_tokens' => $preview?->prompt_tokens,
            'completion_tokens' => $preview?->completion_tokens,
            'total_tokens' => $preview?->total_tokens,
            'ai_cost_usd' => $preview?->cost_usd,
            'match_score' => $score,
            'match_reasons' => $preview?->reasons ?? [],
            'status' => 'queued',
            'error_message' => 'Application queued for sending.',
            'sent_at' => now(),
        ]);

        \Botble\JobBoard\Jobs\ProcessAutoApplySendJob::dispatch($account->id, $job->id)->onQueue('emails');

        return [
            'status' => 'queued',
            'job_id' => $job->id,
        ];
    }

    private function sendCandidateEmailMessage(Account $account, string $subject, string $body, ?string &$errorMessage = null): bool
    {
        $email = trim((string) $account->email);

        if ($email === '') {
            $errorMessage = 'Candidate has no email address.';

            return false;
        }

        try {
            Mail::raw($body, function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });

            return true;
        } catch (Throwable $exception) {
            $errorMessage = 'Candidate email send failed: ' . $exception->getMessage();

            return false;
        }
    }

    private function buildCandidateJobNotice(Account $account, Job $job): string
    {
        $jobUrl = url('/jobs/' . ($job->slugable?->key ?? $job->id));
        $company = trim((string) $job->company?->name);
        $candidateName = trim((string) ($account->first_name ?: $account->name ?: 'there'));
        $applyEmail = $this->resolveJobApplyEmail($job);

        $message = "Hi {$candidateName},\n\n"
            . "I found a matching job for you: *{$job->name}*" . ($company !== '' ? " at {$company}" : '') . ".\n\n";

        if ($applyEmail === '') {
            $message .= "This job does not include an application email, so I could not auto-apply on your behalf.\n"
                . "Please apply manually using this Wakanda Jobs link:\n{$jobUrl}\n\n"
                . "The cover letter to use is included below.\n\n"
                . "Nakia";
        } else {
            $message .= "I have sent your cover letter in the next message so you can copy and paste it if needed.\n\n"
                . "Wakanda Jobs link:\n{$jobUrl}\n\n"
                . "Nakia";
        }

        return $message;
    }

    private function buildManualCandidatePackageMessage(Account $account, Job $job, string $coverLetter): string
    {
        $notice = $this->buildCandidateJobNotice($account, $job);

        return trim($notice)
            . "\n\n---\n*Cover letter to copy and paste:*\n\n"
            . trim($coverLetter);
    }

    private function runCandidateMessageSequence(Account $account, callable $callback): mixed
    {
        $lockKey = 'job_board:auto_apply:candidate_message_sequence:' . $account->id;
        $lockWaitSeconds = 600;
        $lockAcquired = false;

        try {
            $row = DB::selectOne('SELECT GET_LOCK(?, ?) AS lock_acquired', [$lockKey, $lockWaitSeconds]);
            $lockAcquired = (int) ($row->lock_acquired ?? 0) === 1;

            if (! $lockAcquired) {
                throw new \RuntimeException('Timed out waiting for candidate message sequence lock.');
            }

            return $callback();
        } finally {
            try {
                if ($lockAcquired) {
                    DB::selectOne('SELECT RELEASE_LOCK(?) AS lock_released', [$lockKey]);
                }
            } catch (Throwable) {
            }
        }
    }

    public function sendCandidateJobNoticeAndCover(Account $account, Job $job, ?array $preview = null): array
    {
        return $this->runCandidateMessageSequence($account, function () use ($account, $job, $preview) {
            $preview = $preview ?: $this->resolvePreviewForJob($account, $job);
            $coverLetter = trim((string) ($preview['body'] ?? ''));

            if ($coverLetter === '') {
                return [
                    'success' => false,
                    'notice_whatsapp_sent' => false,
                    'notice_email_sent' => false,
                    'cover_whatsapp_sent' => false,
                    'cover_email_sent' => false,
                    'message' => 'No cover letter was available for this candidate and job yet.',
                ];
            }

            $packageMessage = $this->buildManualCandidatePackageMessage($account, $job, $coverLetter);
            $phone = $this->resolveCandidateWhatsAppNumber($account);
            $whatsAppError = null;
            $emailError = null;
            $whatsappSent = false;
            $emailSent = false;

            if ($phone !== '') {
                $whatsappSent = app(WhapiSenderService::class)->sendText($phone, $packageMessage, $whatsAppError);
            } else {
                $whatsAppError = 'Candidate has no WhatsApp number.';
            }

            $emailSent = $this->sendCandidateEmailMessage(
                $account,
                'Job match found: ' . $job->name,
                $packageMessage,
                $emailError
            );

            if ($whatsappSent) {
                usleep(1500000);
            }

            return [
                'success' => $whatsappSent || $emailSent,
                'notice_whatsapp_sent' => $whatsappSent,
                'notice_email_sent' => $emailSent,
                'cover_whatsapp_sent' => $whatsappSent,
                'cover_email_sent' => $emailSent,
                'message' => ($whatsappSent || $emailSent)
                    ? 'Candidate job notice and cover letter package sent.'
                    : trim(implode(' | ', array_filter([$whatsAppError, $emailError]))),
            ];
        });
    }

    public function sendCoverLetterToCandidate(Account $account, Job $job, ?array $preview = null): array
    {
        $preview = $preview ?: $this->resolvePreviewForJob($account, $job);
        $coverLetter = trim((string) ($preview['body'] ?? ''));

        if ($coverLetter === '') {
            return [
                'success' => false,
                'whatsapp_sent' => false,
                'email_sent' => false,
                'message' => 'No cover letter was available for this candidate and job yet.',
            ];
        }

        $phone = $this->resolveCandidateWhatsAppNumber($account);
        $whatsAppError = null;
        $emailError = null;
        $whatsappSent = false;
        $emailSent = false;

        if ($phone !== '') {
            $whatsappSent = app(WhapiSenderService::class)->sendText($phone, $coverLetter, $whatsAppError);
        } else {
            $whatsAppError = 'Candidate has no WhatsApp number.';
        }

        $emailSent = $this->sendCandidateEmailMessage(
            $account,
            'Cover letter for ' . $job->name,
            $coverLetter,
            $emailError
        );

        $success = $whatsappSent || $emailSent;

        return [
            'success' => $success,
            'whatsapp_sent' => $whatsappSent,
            'email_sent' => $emailSent,
            'cover_letter' => $coverLetter,
            'message' => $success
                ? 'Cover letter sent to the candidate' . ($whatsappSent && $emailSent ? ' by WhatsApp and email.' : ($whatsappSent ? ' by WhatsApp.' : ' by email.'))
                : trim(implode(' | ', array_filter([$whatsAppError, $emailError]))),
            'whatsapp_error' => $whatsAppError,
            'email_error' => $emailError,
        ];
    }

    public function sendAutoApplySuccessToCandidate(
        Account $account,
        Job $job,
        ?string $coverLetterSubject = null,
        ?string $coverLetterBody = null,
        bool $wasRecoveredFromManualNotice = false,
    ): array {
        return $this->runCandidateMessageSequence($account, function () use ($account, $job, $coverLetterSubject, $coverLetterBody, $wasRecoveredFromManualNotice) {
            $jobUrl = url('/jobs/' . ($job->slugable?->key ?? $job->id));
            $company = trim((string) $job->company?->name);
            $candidateName = trim((string) ($account->first_name ?: $account->name ?: 'there'));

            if ($wasRecoveredFromManualNotice) {
                $message = "Hi {$candidateName},\n\n"
                    . "Earlier, this job did not include an application email, so I could not auto-apply at that time.\n"
                    . "The job has now been updated with an application email, and I have just applied for *{$job->name}*" . ($company !== '' ? " at {$company}" : '') . ".\n"
                    . "{$jobUrl}\n\n"
                    . '_Nakia_';
            } else {
                $message = "Hi {$candidateName},\n\n"
                    . "I have just applied for *{$job->name}*" . ($company !== '' ? " at {$company}" : '') . ".\n"
                    . "{$jobUrl}\n\n"
                    . '_Nakia_';
            }

            if ($coverLetterBody) {
                $message .= "\n\n---\n*Here's exactly what we sent on your behalf:*\n\n"
                    . ($coverLetterSubject ? "*Subject:* {$coverLetterSubject}\n\n" : '')
                    . $coverLetterBody;
            }

            $phone = $this->resolveCandidateWhatsAppNumber($account);
            $whatsAppError = null;
            $emailError = null;
            $whatsappSent = false;
            $emailSent = false;

            if ($phone !== '') {
                $whatsappSent = app(WhapiSenderService::class)->sendText($phone, $message, $whatsAppError);
            } else {
                $whatsAppError = 'Candidate has no WhatsApp number.';
            }

            if ($wasRecoveredFromManualNotice) {
                $emailBody = "Hi {$candidateName},\n\n"
                    . "Earlier, this job did not include an application email, so I could not auto-apply at that time.\n"
                    . "The job has now been updated with an application email, and I have just applied for:\n\n"
                    . $job->name . ($company !== '' ? " at {$company}" : '') . "\n{$jobUrl}\n\n"
                    . "No action is needed from you now. I will keep applying to new matching jobs for you for the rest of your plan.\n\n"
                    . 'Nakia';
            } else {
                $emailBody = "Hi {$candidateName},\n\n"
                    . "I have just applied for:\n\n"
                    . $job->name . ($company !== '' ? " at {$company}" : '') . "\n{$jobUrl}\n\n"
                    . "No action is needed from you. I will keep applying to new matching jobs for you for the rest of your plan.\n\n"
                    . 'Nakia';
            }

            if ($coverLetterBody) {
                $emailBody .= "\n\n---\nHere's exactly what we sent on your behalf:\n\n"
                    . ($coverLetterSubject ? "Subject: {$coverLetterSubject}\n\n" : '')
                    . $coverLetterBody;
            }

            $emailSent = $this->sendCandidateEmailMessage(
                $account,
                'I applied for "' . $job->name . '"',
                $emailBody,
                $emailError
            );

            if ($whatsappSent) {
                usleep(1500000);
            }

            return [
                'success' => $whatsappSent || $emailSent,
                'whatsapp_sent' => $whatsappSent,
                'email_sent' => $emailSent,
                'message' => ($whatsappSent || $emailSent)
                    ? 'Candidate auto-apply success notification sent.'
                    : trim(implode(' | ', array_filter([$whatsAppError, $emailError]))),
            ];
        });
    }

    /**
     * Send a manual-apply notice plus a separate cover-letter-only message.
     */
    public function sendManualApplyNotice(Account $account, Job $job, int $score): bool
    {
        return ($this->sendManualApplyPackage($account, $job, $score)['status'] ?? null) === 'manual_notified';
    }

    public function sendManualApplyPackage(Account $account, Job $job, int $score, ?array $preview = null): array
    {
        return $this->runCandidateMessageSequence($account, function () use ($account, $job, $score, $preview) {
            $preview = $preview ?: $this->resolvePreviewForJob($account, $job);
            $coverLetter = trim((string) ($preview['body'] ?? ''));

            if ($coverLetter === '') {
                return [
                    'status' => 'manual_notify_failed',
                    'job_id' => $job->id,
                    'message' => 'No cover letter was available for this candidate and job yet.',
                    'cover_sent' => false,
                ];
            }

            $packageMessage = $this->buildManualCandidatePackageMessage($account, $job, $coverLetter);

            $phone = $this->resolveCandidateWhatsAppNumber($account);
            $whatsAppError = null;
            $emailError = null;
            $whatsappSent = false;
            $emailSent = false;

            if ($phone !== '') {
                $whatsappSent = app(WhapiSenderService::class)->sendText($phone, $packageMessage, $whatsAppError);
            } else {
                $whatsAppError = 'Candidate has no WhatsApp number.';
            }

            $emailSent = $this->sendCandidateEmailMessage(
                $account,
                'Manual application needed: ' . $job->name,
                $packageMessage,
                $emailError
            );

            if ($whatsappSent) {
                usleep(1500000);
            }
            $notified = $whatsappSent || $emailSent;

            try {
                AutoApplyLog::create([
                    'account_id' => $account->id,
                    'job_id' => $job->id,
                    'email_sent_to' => 'manual-apply-notice',
                    'ai_email_subject' => 'Manual apply notice sent to candidate',
                    'ai_email_body' => $packageMessage,
                    'ai_model_used' => $preview['ai_model'] ?? AutoApplyOrder::globalAiModel(),
                    'prompt_tokens' => $preview['prompt_tokens'] ?? null,
                    'completion_tokens' => $preview['completion_tokens'] ?? null,
                    'total_tokens' => $preview['total_tokens'] ?? null,
                    'ai_cost_usd' => $preview['cost'] ?? null,
                    'match_score' => $score,
                    'match_reasons' => $preview['reasons'] ?? [],
                    'status' => $notified ? 'sent' : 'failed',
                    'error_message' => $notified
                        ? 'Candidate notified to apply manually because the job has no application email. Cover letter included in the same candidate message.'
                        : trim(implode(' | ', array_filter([$whatsAppError, $emailError]))),
                    'sent_at' => now(),
                ]);
            } catch (Throwable $e) {
                Log::error('AutoApply: Failed to write manual-apply-notice log', [
                    'account_id' => $account->id,
                    'job_id' => $job->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'status' => $notified ? 'manual_notified' : 'manual_notify_failed',
                'job_id' => $job->id,
                'message' => $notified
                    ? 'Candidate was notified to apply manually, with the cover letter included in the same message for easy copy/paste.'
                    : trim(implode(' | ', array_filter([$whatsAppError, $emailError]))),
                'cover_sent' => $notified,
            ];
        });
    }

    /**
     * Replay a manual-notice auto-apply log now that the job has a real application email.
     *
     * @return array{status:string, job_id?:int, message:string, email_sent?:bool}
     */
    public function replayManualApplyLog(AutoApplyLog $log, ?array $preview = null): array
    {
        $log->loadMissing(['account', 'job.company', 'job.slugable']);

        $account = $log->account;
        $job = $log->job;

        if (! $account || ! $job) {
            return [
                'status' => 'missing_relation',
                'message' => 'The manual auto-apply log no longer has a candidate or job attached.',
                'email_sent' => false,
            ];
        }

        $toEmail = $this->resolveJobApplyEmail($job);

        if ($toEmail === '') {
            return [
                'status' => 'still_manual_only',
                'job_id' => $job->id,
                'message' => 'This job still has no application email.',
                'email_sent' => false,
            ];
        }

        $existingApplication = JobApplication::query()
            ->where('account_id', $account->id)
            ->where('job_id', $job->id)
            ->first();

        if ($existingApplication && ! (bool) $existingApplication->is_external_apply) {
            return [
                'status' => 'already_applied',
                'job_id' => $job->id,
                'message' => 'The candidate already has a job application record for this job.',
                'email_sent' => false,
            ];
        }

        if (! $this->hasQuota($account->id)) {
            $log->forceFill([
                'email_sent_to' => $toEmail,
                'status' => 'failed',
                'error_message' => 'Manual-notice replay skipped because no active auto-apply quota is available for the current billing cycle.',
                'sent_at' => now(),
            ])->save();

            return [
                'status' => 'no_quota',
                'job_id' => $job->id,
                'message' => 'No active auto-apply quota is available for this candidate right now.',
                'email_sent' => false,
            ];
        }

        $preview = $preview ?: [
            'score' => $log->match_score,
            'reasons' => $log->match_reasons ?? [],
            'subject' => $log->ai_email_subject,
            'body' => $log->ai_email_body,
            'ai_model' => $log->ai_model_used ?: AutoApplyOrder::globalAiModel(),
            'prompt_tokens' => $log->prompt_tokens,
            'completion_tokens' => $log->completion_tokens,
            'total_tokens' => $log->total_tokens,
            'cost' => $log->ai_cost_usd,
        ];

        $subject = trim((string) ($preview['subject'] ?? ''));
        $body = trim((string) ($preview['body'] ?? ''));
        $model = trim((string) ($preview['ai_model'] ?? AutoApplyOrder::globalAiModel()));

        if ($this->isManualNoticeDraft($subject, $body)) {
            $subject = '';
            $body = '';
        }

        if ($subject === '' || $body === '') {
            $preview = $this->resolvePreviewForJob($account, $job, $model) ?: [];
            $subject = trim((string) ($preview['subject'] ?? ''));
            $body = trim((string) ($preview['body'] ?? ''));
            $model = trim((string) ($preview['ai_model'] ?? $model));
        }

        if ($subject === '' || $body === '') {
            $log->forceFill([
                'email_sent_to' => $toEmail,
                'status' => 'failed',
                'error_message' => 'Manual-notice replay failed because no AI application draft was available.',
                'sent_at' => now(),
            ])->save();

            return [
                'status' => 'generation_failed',
                'job_id' => $job->id,
                'message' => 'No AI application draft was available for this candidate/job pair.',
                'email_sent' => false,
            ];
        }

        if ($this->containsPlaceholders($subject) || $this->containsPlaceholders($body)) {
            $log->forceFill([
                'email_sent_to' => $toEmail,
                'ai_email_subject' => $subject,
                'ai_email_body' => $body,
                'ai_model_used' => $model,
                'prompt_tokens' => $preview['prompt_tokens'] ?? null,
                'completion_tokens' => $preview['completion_tokens'] ?? null,
                'total_tokens' => $preview['total_tokens'] ?? null,
                'ai_cost_usd' => $preview['cost'] ?? null,
                'match_score' => isset($preview['score']) ? (int) $preview['score'] : $log->match_score,
                'match_reasons' => $preview['reasons'] ?? $log->match_reasons ?? [],
                'status' => 'failed',
                'error_message' => 'Manual-notice replay blocked because the AI draft still contains placeholders.',
                'sent_at' => now(),
            ])->save();

            return [
                'status' => 'placeholder_blocked',
                'job_id' => $job->id,
                'message' => 'The AI draft still contains placeholders, so it was blocked for safety.',
                'email_sent' => false,
            ];
        }

        $messageId = sprintf('auto-apply-replay-%s@%s', (string) Str::uuid(), parse_url(config('app.url'), PHP_URL_HOST) ?: 'wakandajobs.com');
        $sent = $this->sendApplicationEmail($account, $job, $subject, $body, $toEmail, $messageId);

        if ($sent) {
            $this->upsertAutoApplyJobApplication(
                $account,
                $job,
                $body,
                'Auto-applied via Wakanda Jobs Auto Apply service after the job application email was recovered'
            );

            if (! $this->deductQuota($account->id)) {
                $log->forceFill([
                    'email_sent_to' => $toEmail,
                    'message_id' => $messageId,
                    'ai_email_subject' => $subject,
                    'ai_email_body' => $body,
                    'ai_model_used' => $model,
                    'prompt_tokens' => $preview['prompt_tokens'] ?? null,
                    'completion_tokens' => $preview['completion_tokens'] ?? null,
                    'total_tokens' => $preview['total_tokens'] ?? null,
                    'ai_cost_usd' => $preview['cost'] ?? null,
                    'match_score' => isset($preview['score']) ? (int) $preview['score'] : $log->match_score,
                    'match_reasons' => $preview['reasons'] ?? $log->match_reasons ?? [],
                    'status' => 'failed',
                    'error_message' => 'Manual-notice replay sent the application email, but quota deduction failed.',
                    'sent_at' => now(),
                ])->save();

                return [
                    'status' => 'quota_deduction_failed',
                    'job_id' => $job->id,
                    'message' => 'The application email was sent, but quota deduction failed.',
                    'email_sent' => true,
                ];
            }

            NotifyCandidateAutoApplySentJob::dispatch($account->id, $job->id, $subject, $body, true)->onQueue('default');
        }

        $log->forceFill([
            'email_sent_to' => $toEmail,
            'message_id' => $sent ? $messageId : null,
            'ai_email_subject' => $subject,
            'ai_email_body' => $body,
            'ai_model_used' => $model,
            'prompt_tokens' => $preview['prompt_tokens'] ?? null,
            'completion_tokens' => $preview['completion_tokens'] ?? null,
            'total_tokens' => $preview['total_tokens'] ?? null,
            'ai_cost_usd' => $preview['cost'] ?? null,
            'match_score' => isset($preview['score']) ? (int) $preview['score'] : $log->match_score,
            'match_reasons' => $preview['reasons'] ?? $log->match_reasons ?? [],
            'status' => $sent ? 'sent' : 'failed',
            'error_message' => $sent
                ? 'Replayed from manual notice after the crawler recovered an application email for the job.'
                : 'Manual-notice replay could not deliver the application email.',
            'sent_at' => now(),
        ])->save();

        return [
            'status' => $sent ? 'sent' : 'failed',
            'job_id' => $job->id,
            'message' => $sent
                ? 'Manual auto-apply log was replayed successfully.'
                : 'The application email could not be delivered.',
            'email_sent' => $sent,
        ];
    }
}
