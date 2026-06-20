<?php

namespace Botble\JobBoard\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Botble\JobBoard\Models\AutoCvMessage;
use Botble\JobBoard\Models\AutoCvSession;
use Botble\JobBoard\Models\SocialAutomation;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

class AutoCvBotService
{
    private const MODEL = 'gpt-4o-mini';

    /** The AI assistant's name, shown to candidates — never refer to it as a "bot". */
    private const PERSONA_NAME = 'Nakia';

    private const MODEL_PRICING_PER_MILLION = [
        'gpt-4o-mini' => [0.15, 0.60],
        'gpt-4o' => [2.50, 10.00],
    ];

    private const MAX_AI_TURNS = 18;

    public static function topics(): array
    {
        return [
            'Full name as it should appear on the CV',
            'Bio and contact details: mobile number, email address, residential address, town/city, age, marital status, and LinkedIn profile URL if they have one',
            'Job title or type of work being looked for',
            'Short personal profile: strengths and what kind of worker they are',
            'Education, highest qualification first (school/college, qualification, field, years)',
            'Work experience (company, job title, dates, what they did)',
            'Internships, attachments, volunteer work, or projects — including a link to GitHub or live work if they have one',
            'Strongest skills, tools, software, machines, or languages they can use',
            'Certificates, licences, trainings, or awards',
            'Languages spoken and proficiency level (e.g. fluent, intermediate, basic)',
            'References (name, role/company, phone, email) or "Available on request"',
            'Anything else important: achievements, leadership, availability, preferred location',
        ];
    }

    public function startSession(string $whatsappNumber, ?string $candidateName, ?int $adminId): array
    {
        $session = AutoCvSession::query()->create([
            'admin_id' => $adminId,
            'candidate_name' => $candidateName,
            'whatsapp_number' => $whatsappNumber,
            'status' => 'collecting',
            'topics' => self::topics(),
            'topics_covered' => [],
            'answers' => [],
        ]);

        $opening = $this->generateOpeningMessage($session, $candidateName, self::topics()[0]);

        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $opening, $errorMessage);

        if (! $sent) {
            $session->forceFill([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ])->save();

            $this->notifyAdmin($session, 'failed');

            return [$session->fresh(), $errorMessage];
        }

        AutoCvMessage::query()->create([
            'session_id' => $session->getKey(),
            'direction' => 'outbound',
            'body' => $opening,
        ]);

        $session->forceFill([
            'last_question_text' => $opening,
            'last_question_sent_at' => now(),
        ])->save();

        return [$session->fresh(), null];
    }

    /**
     * Generates a fresh, AI-phrased opening message for every new session so it never
     * reads as a canned script — candidates are often nervous about talking to an
     * automated system, so the tone needs to stay calm, warm, and clearly human-like
     * while still being honest that it's an AI, not a "bot".
     */
    private function generateOpeningMessage(AutoCvSession $session, ?string $candidateName, string $firstTopic): string
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            return $this->fallbackOpeningMessage($candidateName, $firstTopic);
        }

        $personaName = self::PERSONA_NAME;

        $systemPrompt = <<<PROMPT
You are writing the very first WhatsApp message a job candidate in Zambia receives from Wakanda Jobs, introducing yourself before a short interview to build their CV.

Your name is {$personaName}. You are Wakanda Jobs' AI assistant — always say plainly that you are an AI. Never use the word "bot" anywhere. Many candidates feel nervous messaging an automated system for the first time, so your tone must be warm, calm, reassuring, and natural, like a kind person — never robotic, corporate, or scripted.

Write a short message that:
- Greets the candidate by name if one is given, otherwise a warm general greeting.
- Introduces yourself by name as Wakanda Jobs' AI assistant, phrased in your own natural words — vary the wording every time, never reuse the exact same sentence twice.
- Reassures them this is easy and relaxed: a few simple questions, one at a time, and they can just reply naturally in their own words — there's no right or wrong way to answer.
- Ends by asking exactly this first question, worded naturally in your own way but keeping its meaning exactly the same: "{$firstTopic}"

Rules:
- 3-5 short sentences total. Calm and conversational, not corporate.
- Never use the word "bot".
- Output ONLY the message text — no preamble, no quotes, no markdown fences.
PROMPT;

        try {
            $response = Http::timeout(30)->withToken($apiKey)->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::MODEL,
                    'temperature' => 0.9,
                    'max_tokens' => 220,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => 'Candidate name: ' . ($candidateName ?: '(not given)')],
                    ],
                ]);
        } catch (Throwable) {
            return $this->fallbackOpeningMessage($candidateName, $firstTopic);
        }

        if (! $response->successful()) {
            return $this->fallbackOpeningMessage($candidateName, $firstTopic);
        }

        $text = trim((string) $response->json('choices.0.message.content', ''));
        $text = trim((string) preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text));

        if ($text === '') {
            return $this->fallbackOpeningMessage($candidateName, $firstTopic);
        }

        $this->recordAiUsage($session, $response);

        return $text;
    }

    private function fallbackOpeningMessage(?string $candidateName, string $firstTopic): string
    {
        return "Hi" . ($candidateName ? " {$candidateName}" : '') . "! I'm " . self::PERSONA_NAME . ", the AI assistant for Wakanda Jobs. "
            . "There's no pressure here — I'll just ask a few simple questions, one at a time, to help put your CV together. Reply however feels natural to you.\n\n"
            . "To start: {$firstTopic}";
    }

    public function handleInboundReply(string $fromDigits, string $body, ?string $whapiMessageId): void
    {
        $body = trim($body);

        if ($fromDigits === '' || $body === '') {
            return;
        }

        $session = $this->findActiveSessionByDigits($fromDigits);

        if (! $session) {
            return;
        }

        $this->recordInboundAndProcess($session, $body, $whapiMessageId);
    }

    public function handleInboundAttachment(string $fromDigits, array $message, ?string $whapiMessageId): void
    {
        if ($fromDigits === '') {
            return;
        }

        $session = $this->findActiveSessionByDigits($fromDigits);

        if (! $session) {
            return;
        }

        [$extractedText, $label] = $this->extractAttachmentText($session, $message, $whapiMessageId);

        if ($extractedText !== '') {
            $body = trim('Candidate sent an attachment'
                . ($label ? " ({$label})" : '')
                . ". Extracted text:\n\n"
                . $extractedText);

            $this->recordInboundAndProcess($session, $body, $whapiMessageId, $message);

            return;
        }

        try {
            AutoCvMessage::query()->create([
                'session_id' => $session->getKey(),
                'direction' => 'inbound',
                'body' => 'Candidate sent an attachment, but no readable CV text could be extracted from it.',
                'whapi_message_id' => $whapiMessageId,
                'whapi_payload' => $message,
            ]);
        } catch (QueryException $exception) {
            // Whapi redelivered a webhook we already processed for this message id.
            return;
        }

        $fallback = "Thanks, I received the file/photo, but I can't read enough CV details from it.\n\n"
            . 'Please type the certificate, licence, training, or award details in a normal WhatsApp message. '
            . 'Include the name, issuing body, date/year, licence number if any, and expiry date if any.';

        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $fallback, $errorMessage);

        if (! $sent) {
            $session->forceFill([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ])->save();

            $this->notifyAdmin($session, 'failed');

            return;
        }

        AutoCvMessage::query()->create([
            'session_id' => $session->getKey(),
            'direction' => 'outbound',
            'body' => $fallback,
        ]);

        $session->forceFill([
            'status' => 'collecting',
            'last_question_text' => $fallback,
            'last_question_sent_at' => now(),
            'last_reply_at' => now(),
            'candidate_reminder_count' => 0,
            'last_candidate_reminder_sent_at' => null,
            'error_message' => null,
            'error_trace' => null,
        ])->save();
    }

    private function findActiveSessionByDigits(string $fromDigits): ?AutoCvSession
    {
        return AutoCvSession::query()
            ->whereIn('status', ['collecting', 'failed', 'stalled'])
            ->latest()
            ->get()
            ->first(fn (AutoCvSession $candidate) => preg_replace('/\D/', '', (string) $candidate->whatsapp_number) === $fromDigits);
    }

    private function recordInboundAndProcess(AutoCvSession $session, string $body, ?string $whapiMessageId, ?array $payload = null): void
    {
        $body = trim($body);

        if ($body === '') {
            return;
        }

        try {
            AutoCvMessage::query()->create([
                'session_id' => $session->getKey(),
                'direction' => 'inbound',
                'body' => $body,
                'whapi_message_id' => $whapiMessageId,
                'whapi_payload' => $payload,
            ]);
        } catch (QueryException $exception) {
            // Whapi redelivered a webhook we already processed for this message id.
            return;
        }

        $answers = $session->answers ?: [];
        $answers[] = [
            'question_sent' => $session->last_question_text,
            'reply' => $body,
            'at' => now()->toDateTimeString(),
        ];

        $session->forceFill([
            'status' => 'collecting',
            'answers' => $answers,
            'last_reply_at' => now(),
            'candidate_reminder_count' => 0,
            'last_candidate_reminder_sent_at' => null,
            'error_message' => null,
            'error_trace' => null,
        ])->save();

        if ($session->awaiting_final_confirmation && $this->isFinalConfirmation($body)) {
            $this->completeAfterFinalConfirmation($session->fresh());

            return;
        }

        if ($session->awaiting_final_confirmation) {
            $session->forceFill(['awaiting_final_confirmation' => false])->save();
        }

        $this->processReplyWithAi($session->fresh());
    }

    private function processReplyWithAi(AutoCvSession $session): void
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            $session->forceFill([
                'status' => 'failed',
                'error_message' => 'OpenAI API key is not configured.',
            ])->save();

            $this->notifyAdmin($session, 'failed');

            return;
        }

        $payload = $this->buildAiPayload($session);

        try {
            $response = Http::timeout(90)->withToken($apiKey)->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', $payload);
        } catch (Throwable $exception) {
            $session->forceFill([
                'status' => 'failed',
                'error_message' => 'OpenAI request exception: ' . $exception->getMessage(),
                'error_trace' => (string) $exception,
            ])->save();

            $this->notifyAdmin($session, 'failed');

            return;
        }

        if (! $response->successful()) {
            $session->forceFill([
                'status' => 'failed',
                'error_message' => 'OpenAI request failed: HTTP ' . $response->status() . ' ' . Str::limit($response->body(), 250, ''),
            ])->save();

            $this->notifyAdmin($session, 'failed');

            return;
        }

        $decoded = $this->decodeAiJson($response);

        if (! is_array($decoded) || ! is_array($decoded['structured_cv'] ?? null) || ! is_string($decoded['next_message'] ?? null)) {
            $retryPayload = $payload;
            $retryPayload['messages'][] = [
                'role' => 'user',
                'content' => 'The previous response was not valid for the required schema. Return ONLY valid JSON in the exact requested shape. Do not include markdown, commentary, or missing top-level keys.',
            ];

            try {
                $retryResponse = Http::timeout(90)->withToken($apiKey)->acceptJson()
                    ->post('https://api.openai.com/v1/chat/completions', $retryPayload);
            } catch (Throwable $exception) {
                $session->forceFill([
                    'status' => 'failed',
                    'error_message' => 'OpenAI retry exception: ' . $exception->getMessage(),
                    'error_trace' => (string) $exception,
                ])->save();

                $this->notifyAdmin($session, 'failed');

                return;
            }

            if (! $retryResponse->successful()) {
                $session->forceFill([
                    'status' => 'failed',
                    'error_message' => 'OpenAI retry failed: HTTP ' . $retryResponse->status() . ' ' . Str::limit($retryResponse->body(), 250, ''),
                    'error_trace' => $retryResponse->body(),
                ])->save();

                $this->notifyAdmin($session, 'failed');

                return;
            }

            $decoded = $this->decodeAiJson($retryResponse);

            if (is_array($decoded) && is_array($decoded['structured_cv'] ?? null) && is_string($decoded['next_message'] ?? null)) {
                $response = $retryResponse;
            }
        }

        if (! is_array($decoded) || ! is_array($decoded['structured_cv'] ?? null) || ! is_string($decoded['next_message'] ?? null)) {
            $session->forceFill([
                'status' => 'failed',
                'error_message' => 'OpenAI returned an invalid response shape.',
                'error_trace' => Str::limit((string) $response->json('choices.0.message.content', $response->body()), 4000, ''),
            ])->save();

            $this->notifyAdmin($session, 'failed');

            return;
        }

        $this->recordAiUsage($session, $response);

        $totalTopics = count($session->topics ?: self::topics());
        $sectionScores = $this->sanitizeSectionScores($decoded['section_scores'] ?? [], $session);

        // Completion is decided here, not by the model's own "is_complete"/"missing_topics" flags — those
        // have been seen to disagree with the scores in the same response. A topic only counts as covered
        // once its own score reaches the "green" (>= 90) threshold, and the whole interview is only complete
        // once every topic is green, so weak sections keep getting iterated on instead of being glossed over.
        $topicsCovered = collect($sectionScores)
            ->filter(fn (array $section) => $section['score'] >= 90)
            ->keys()
            ->map(fn (string $key) => (int) $key)
            ->values()
            ->all();
        $isComplete = count($topicsCovered) >= $totalTopics;
        $turnsTaken = count($session->answers ?: []);

        if (! $isComplete && $turnsTaken >= self::MAX_AI_TURNS) {
            $decoded['structured_cv']['notes_for_admin'] = array_merge(
                (array) ($decoded['structured_cv']['notes_for_admin'] ?? []),
                ['Reached the usual number of interview turns, but weak sections still need candidate follow-up.']
            );
        }

        $nextMessage = trim((string) $decoded['next_message']);

        if ($isComplete && $this->looksLikeCandidateQuestion($nextMessage)) {
            $isComplete = false;
        }

        // Safety net: the model itself thought it was done (empty next_message) even though our stricter
        // score gate disagrees — trust that signal rather than risk sending a blank WhatsApp message or
        // stalling the session forever.
        if ($nextMessage === '') {
            $isComplete = true;
            $nextMessage = "Thank you for all the details! I'm putting your CV together now.";
        }

        $session->forceFill([
            'structured_cv' => $this->sanitizeCv($decoded['structured_cv'], $session),
            'topics_covered' => $topicsCovered,
            'section_scores' => $sectionScores,
            'suggested_job_positions' => $this->sanitizeJobPositions($decoded['suggested_job_positions'] ?? []),
        ])->save();

        if ($isComplete) {
            $nextMessage = $this->finalConfirmationMessage($session);
        }

        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $nextMessage, $errorMessage);

        if (! $sent) {
            $session->forceFill([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ])->save();

            $this->notifyAdmin($session, 'failed');

            return;
        }

        AutoCvMessage::query()->create([
            'session_id' => $session->getKey(),
            'direction' => 'outbound',
            'body' => $nextMessage,
        ]);

        if (! $isComplete) {
            $session->forceFill([
                'last_question_text' => $nextMessage,
                'last_question_sent_at' => now(),
                'candidate_reminder_count' => 0,
                'last_candidate_reminder_sent_at' => null,
            ])->save();

            return;
        }

        $session->forceFill([
            'status' => 'collecting',
            'last_question_text' => $nextMessage,
            'last_question_sent_at' => now(),
            'awaiting_final_confirmation' => true,
            'candidate_reminder_count' => 0,
            'last_candidate_reminder_sent_at' => null,
        ])->save();
    }

    private function finalizeSession(AutoCvSession $session, int $attempt = 1): void
    {
        try {
            $cv = $this->sanitizeCv((array) $session->structured_cv, $session);
            $paths = $this->storeDocuments($session, $cv);

            $session->forceFill([
                'structured_cv' => $cv,
                'docx_path' => $paths['premium']['docx_path'],
                'pdf_path' => $paths['premium']['pdf_path'],
                'cv_document_paths' => $paths,
                'status' => 'completed',
                'error_message' => null,
                'error_trace' => null,
                'completed_at' => now(),
            ])->save();

            $this->notifyAdmin($session, 'completed');

            if (! $session->sent_to_candidate_at) {
                $this->sendDocumentsToCandidate($session->fresh());
            }
        } catch (Throwable $exception) {
            if ($attempt < 2) {
                clearstatcache();
                sleep(1);
                $this->finalizeSession($session->fresh(), $attempt + 1);

                return;
            }

            $session->forceFill([
                'status' => 'failed',
                'error_message' => 'CV generation failed: ' . $exception->getMessage(),
                'error_trace' => (string) $exception,
            ])->save();

            $this->notifyAdmin($session, 'failed');
        }
    }

    public function resendCurrentQuestion(AutoCvSession $session): bool
    {
        if ($session->status !== 'collecting' || ! $session->last_question_text) {
            return false;
        }

        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $session->last_question_text, $errorMessage);

        if ($sent) {
            AutoCvMessage::query()->create([
                'session_id' => $session->getKey(),
                'direction' => 'outbound',
                'body' => $session->last_question_text,
            ]);

            $session->forceFill([
                'last_question_sent_at' => now(),
                'candidate_reminder_count' => 0,
                'last_candidate_reminder_sent_at' => null,
            ])->save();
        }

        return $sent;
    }

    public function retryAiProcessing(AutoCvSession $session): void
    {
        if (! $session->answers) {
            return;
        }

        $session->forceFill([
            'status' => 'collecting',
            'error_message' => null,
            'error_trace' => null,
        ])->save();

        $this->processReplyWithAi($session->fresh());
    }

    public function sendCandidateReminder(AutoCvSession $session): bool
    {
        if ($session->status !== 'collecting' || ! $session->last_question_text || (int) $session->candidate_reminder_count >= 3) {
            return false;
        }

        $nextCount = (int) $session->candidate_reminder_count + 1;
        $message = "Hi" . ($session->candidate_name ? " {$session->candidate_name}" : '') . ", just a quick reminder to reply when you can so we can finish your CV.\n\n"
            . "We are still waiting for this information:\n{$session->last_question_text}";

        if ($nextCount >= 3) {
            $message .= "\n\nThis is the final reminder. If you do not reply, I'll pause here until you're ready to continue.";
        }

        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $message, $errorMessage);

        if (! $sent) {
            $session->forceFill(['error_message' => $errorMessage])->save();

            return false;
        }

        AutoCvMessage::query()->create([
            'session_id' => $session->getKey(),
            'direction' => 'outbound',
            'body' => $message,
        ]);

        $session->forceFill([
            'candidate_reminder_count' => $nextCount,
            'last_candidate_reminder_sent_at' => now(),
        ])->save();

        return true;
    }

    public function requestSectionInformation(AutoCvSession $session, int $topicNumber): bool
    {
        $topics = $session->topics ?: self::topics();

        if (! isset($topics[$topicNumber - 1])) {
            return false;
        }

        $sectionScores = $session->section_scores ?: [];
        $section = $sectionScores[(string) $topicNumber] ?? [];
        $label = trim((string) ($section['label'] ?? 'this section'));
        $improve = trim((string) ($section['improve'] ?? ''));
        $topic = $topics[$topicNumber - 1];

        $message = "Hi" . ($session->candidate_name ? " {$session->candidate_name}" : '') . ", we are reviewing your CV and would like to strengthen the {$label} section before finalising it.\n\n"
            . "Please reply with any extra details, corrections, or missing information for this section so we can make the CV accurate and professional.";

        if ($improve !== '') {
            $message .= "\n\nWhat would help most: {$improve}";
        }

        $message .= "\n\nSection we are improving: {$topic}";

        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $message, $errorMessage);

        if (! $sent) {
            $session->forceFill(['error_message' => $errorMessage])->save();

            return false;
        }

        AutoCvMessage::query()->create([
            'session_id' => $session->getKey(),
            'direction' => 'outbound',
            'body' => $message,
        ]);

        $topicsCovered = collect($session->topics_covered ?: [])
            ->reject(fn ($coveredTopic) => (int) $coveredTopic === $topicNumber)
            ->values()
            ->all();

        $session->forceFill([
            'status' => 'collecting',
            'topics_covered' => $topicsCovered,
            'last_question_text' => $message,
            'last_question_sent_at' => now(),
            'candidate_reminder_count' => 0,
            'last_candidate_reminder_sent_at' => null,
            'docx_path' => null,
            'pdf_path' => null,
            'error_message' => null,
            'error_trace' => null,
            'completed_at' => null,
        ])->save();

        return true;
    }

    public function retryDocumentGeneration(AutoCvSession $session): bool
    {
        if (empty($session->structured_cv)) {
            return false;
        }

        $this->finalizeSession($session);

        return $session->fresh()->status === 'completed';
    }

    public function generateDocumentsNow(AutoCvSession $session): bool
    {
        $cv = $this->sanitizeCv((array) $session->structured_cv, $session);

        if ($this->cvIsEmpty($cv)) {
            return false;
        }

        try {
            $paths = $this->storeDocuments($session, $cv);

            $session->forceFill([
                'structured_cv' => $cv,
                'docx_path' => $paths['premium']['docx_path'],
                'pdf_path' => $paths['premium']['pdf_path'],
                'cv_document_paths' => $paths,
                'error_message' => null,
                'error_trace' => null,
            ])->save();

            return true;
        } catch (Throwable $exception) {
            $session->forceFill([
                'status' => 'failed',
                'error_message' => 'CV generation failed: ' . $exception->getMessage(),
                'error_trace' => (string) $exception,
            ])->save();

            return false;
        }
    }

    public function sendDocumentsToCandidate(AutoCvSession $session): bool
    {
        $paths = $session->cv_document_paths ?: [];

        if ($paths === [] && ($session->docx_path || $session->pdf_path)) {
            $paths = [
                'premium' => [
                    'label' => 'Premium',
                    'docx_path' => $session->docx_path,
                    'pdf_path' => $session->pdf_path,
                ],
            ];
        }

        if ($paths === []) {
            $session->forceFill(['error_message' => 'No generated CV documents found to send.'])->save();

            return false;
        }

        $errorMessage = null;
        $introSent = $this->sendWhapiMessage(
            $session->whatsapp_number,
            "Hi" . ($session->candidate_name ? " {$session->candidate_name}" : '') . ", your CV drafts are ready. I am sending the available designs now as PDF and Word documents.",
            $errorMessage
        );

        if (! $introSent) {
            $session->forceFill(['error_message' => $errorMessage])->save();

            return false;
        }

        foreach ($paths as $style => $row) {
            foreach (['pdf_path' => 'pdf', 'docx_path' => 'docx'] as $key => $extension) {
                $path = (string) ($row[$key] ?? '');

                if ($path === '' || ! Storage::disk('local')->exists($path)) {
                    continue;
                }

                $sent = $this->sendWhapiDocument(
                    $session->whatsapp_number,
                    Storage::disk('local')->path($path),
                    $this->documentFilename($session, (string) ($row['label'] ?? $style), $extension),
                    $errorMessage
                );

                if (! $sent) {
                    $session->forceFill(['error_message' => $errorMessage])->save();

                    return false;
                }
            }
        }

        $session->forceFill(['sent_to_candidate_at' => now(), 'error_message' => null])->save();

        return true;
    }

    private function documentFilename(AutoCvSession $session, string $label, string $extension): string
    {
        $name = (string) ($session->structured_cv['full_name'] ?? $session->candidate_name ?? 'Candidate');
        $safeName = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', trim($name)), '_') ?: 'Candidate';
        $safeLabel = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $label), '_') ?: 'CV';

        return "{$safeName}_CV_{$safeLabel}.{$extension}";
    }

    public function askCandidateToResendLastMessage(AutoCvSession $session): bool
    {
        $message = "Hi" . ($session->candidate_name ? " {$session->candidate_name}" : '') . ", we had a small challenge processing your last reply.\n\n"
            . "Please send your last message again so we can continue building your CV accurately.";

        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $message, $errorMessage);

        if (! $sent) {
            $session->forceFill(['error_message' => $errorMessage])->save();

            return false;
        }

        AutoCvMessage::query()->create([
            'session_id' => $session->getKey(),
            'direction' => 'outbound',
            'body' => $message,
        ]);

        $session->forceFill([
            'status' => 'collecting',
            'last_question_text' => $message,
            'last_question_sent_at' => now(),
            'candidate_reminder_count' => 0,
            'last_candidate_reminder_sent_at' => null,
            'awaiting_final_confirmation' => false,
            'error_message' => null,
            'error_trace' => null,
        ])->save();

        return true;
    }

    public function continueInterview(AutoCvSession $session): bool
    {
        $isCompleted = $session->status === 'completed';
        $question = null;

        if (! $isCompleted) {
            $question = $session->messages()
                ->where('direction', 'outbound')
                ->latest()
                ->get()
                ->first(fn (AutoCvMessage $message) => $this->looksLikeCandidateQuestion((string) $message->body));
        }

        $message = $isCompleted
            ? $this->finalConfirmationMessage($session)
            : ($question?->body ?: $this->nextWeakSectionQuestion($session));

        if (! $message) {
            return false;
        }

        $session->forceFill([
            'status' => 'collecting',
            'last_question_text' => $message,
            'last_question_sent_at' => now(),
            'docx_path' => null,
            'pdf_path' => null,
            'completed_at' => null,
            'candidate_reminder_count' => 0,
            'last_candidate_reminder_sent_at' => null,
            'awaiting_final_confirmation' => $isCompleted,
            'error_message' => null,
            'error_trace' => null,
        ])->save();

        return $this->resendCurrentQuestion($session->fresh());
    }

    public function endConversationNow(AutoCvSession $session, ?string $closingMessage = null): bool
    {
        AutoCvMessage::query()->create([
            'session_id' => $session->getKey(),
            'direction' => 'inbound',
            'body' => 'DONE',
        ]);

        $session->forceFill(['last_reply_at' => now()])->save();

        $this->completeAfterFinalConfirmation($session->fresh(), $closingMessage);

        return $session->fresh()->status === 'completed';
    }

    public function notifyAdmin(AutoCvSession $session, string $outcome): void
    {
        $adminNumber = (string) setting('auto_cv_admin_whatsapp_number', '+260970766123');

        if (trim($adminNumber) === '') {
            return;
        }

        $name = $session->candidate_name ?: 'Unknown candidate';
        $phone = $session->whatsapp_number;
        $url = route('job-board.auto-cv-bot.show', $session->getKey());

        $body = match ($outcome) {
            'completed' => "✅ CV Bot finished for {$name} ({$phone}). Please check & verify the CV before sending it out: {$url}",
            'stalled' => "⏰ CV Bot stalled — {$name} ({$phone}) hasn't replied since "
                . ($session->last_question_sent_at?->format('d M Y H:i') ?? 'the last question was sent') . ". {$url}",
            default => "⚠️ CV Bot failed for {$name} ({$phone}): " . ($session->error_message ?: 'unknown error') . ". {$url}",
        };

        $ignoredError = null;
        $this->sendWhapiMessage($adminNumber, $body, $ignoredError);

        $session->forceFill(['admin_notified_at' => now()])->save();
    }

    private function sendWhapiMessage(string $whatsappNumber, string $body, ?string &$errorMessage = null): bool
    {
        [$token, $gatewayUrl] = $this->getWhapiCredentials();

        if (! $token) {
            $errorMessage = 'No active Whapi automation configured.';

            return false;
        }

        $jid = preg_replace('/\D/', '', $whatsappNumber) . '@s.whatsapp.net';

        try {
            $response = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                'to' => $jid,
                'body' => $body,
            ]);
        } catch (Throwable $exception) {
            $errorMessage = 'WhatsApp send exception: ' . $exception->getMessage();

            return false;
        }

        if (! $response->successful()) {
            $errorMessage = 'WhatsApp send failed: HTTP ' . $response->status() . ' ' . Str::limit($response->body(), 250, '');

            return false;
        }

        return true;
    }

    private function extractAttachmentText(AutoCvSession $session, array $message, ?string $whapiMessageId): array
    {
        $parts = [];
        $caption = $this->extractAttachmentCaption($message);

        if ($caption !== '') {
            $parts[] = "Caption:\n{$caption}";
        }

        [$path, $filename, $mime] = $this->downloadWhapiAttachment($session, $message, $whapiMessageId);

        if ($path) {
            $text = str_contains(strtolower($mime), 'pdf') || str_ends_with(strtolower($filename), '.pdf')
                ? $this->extractPdfText($path)
                : $this->extractImageText($session, $path, $mime);

            if ($text !== '') {
                $parts[] = "Attachment text:\n{$text}";
            }
        }

        return [trim(implode("\n\n", $parts)), $filename];
    }

    private function extractAttachmentCaption(array $message): string
    {
        foreach ([
            'caption',
            'text.body',
            'image.caption',
            'document.caption',
            'file.caption',
        ] as $key) {
            $value = data_get($message, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    private function downloadWhapiAttachment(AutoCvSession $session, array $message, ?string $whapiMessageId): array
    {
        $url = collect([
            data_get($message, 'media'),
            data_get($message, 'media.url'),
            data_get($message, 'media.link'),
            data_get($message, 'image.url'),
            data_get($message, 'image.link'),
            data_get($message, 'document.url'),
            data_get($message, 'document.link'),
            data_get($message, 'file.url'),
            data_get($message, 'file.link'),
            data_get($message, 'url'),
            data_get($message, 'link'),
        ])->first(fn ($value) => is_string($value) && str_starts_with($value, 'http'));
        $mediaId = collect([
            data_get($message, 'media.id'),
            data_get($message, 'image.id'),
            data_get($message, 'document.id'),
            data_get($message, 'file.id'),
            is_string(data_get($message, 'media')) && ! str_starts_with((string) data_get($message, 'media'), 'http')
                ? data_get($message, 'media')
                : null,
        ])->first(fn ($value) => is_string($value) && trim($value) !== '');

        $filename = trim((string) (data_get($message, 'document.filename')
            ?: data_get($message, 'file.name')
            ?: data_get($message, 'file.filename')
            ?: data_get($message, 'image.filename')
            ?: $whapiMessageId
            ?: Str::random(12)));
        $mime = trim((string) (data_get($message, 'document.mime_type')
            ?: data_get($message, 'document.mimetype')
            ?: data_get($message, 'file.mime_type')
            ?: data_get($message, 'file.mimetype')
            ?: data_get($message, 'image.mime_type')
            ?: data_get($message, 'image.mimetype')
            ?: ''));

        if (! $url && ! $mediaId) {
            return [null, $filename, $mime];
        }

        [$token, $gatewayUrl] = $this->getWhapiCredentials();

        try {
            $request = Http::timeout(60);

            if ($token) {
                $request = $request->withToken($token);
            }

            if (! $url && ! $gatewayUrl) {
                return [null, $filename, $mime];
            }

            $response = $url
                ? $request->get($url)
                : $request->get("{$gatewayUrl}/media/{$mediaId}");

            if (! $url && $response->successful() && is_array($response->json())) {
                $jsonUrl = collect([
                    $response->json('url'),
                    $response->json('link'),
                    $response->json('media'),
                    $response->json('file.url'),
                    $response->json('file.link'),
                ])->first(fn ($value) => is_string($value) && str_starts_with($value, 'http'));

                if ($jsonUrl) {
                    $response = $request->get($jsonUrl);
                }
            }

            if ($url && ! $response->successful() && $token) {
                $response = Http::timeout(60)->get($url);
            }
        } catch (Throwable) {
            return [null, $filename, $mime];
        }

        if (! $response->successful() || $response->body() === '') {
            return [null, $filename, $mime];
        }

        $mime = $mime ?: (string) $response->header('Content-Type', 'application/octet-stream');
        $extension = strtolower(pathinfo(parse_url($filename, PHP_URL_PATH) ?: $filename, PATHINFO_EXTENSION));

        if ($extension === '') {
            $extension = str_contains($mime, 'pdf') ? 'pdf' : (str_starts_with($mime, 'image/') ? Str::after($mime, 'image/') : 'bin');
        }

        $safeBase = Str::slug(pathinfo($filename, PATHINFO_FILENAME) ?: 'attachment') ?: 'attachment';
        $relativePath = 'auto-cv-bot/session-' . $session->getKey() . '/attachments/'
            . $safeBase . '-' . ($whapiMessageId ? Str::slug($whapiMessageId) : Str::random(8)) . '.' . $extension;

        Storage::disk('local')->put($relativePath, $response->body());

        return [Storage::disk('local')->path($relativePath), $filename, $mime];
    }

    private function extractPdfText(string $path): string
    {
        if (! is_file($path)) {
            return '';
        }

        $binary = '/usr/bin/pdftotext';

        if (! is_executable($binary)) {
            return '';
        }

        $outputPath = tempnam(sys_get_temp_dir(), 'auto-cv-pdf-');

        if (! $outputPath) {
            return '';
        }

        $process = proc_open(
            [$binary, '-layout', $path, $outputPath],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (! is_resource($process)) {
            @unlink($outputPath);

            return '';
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $exitCode = proc_close($process);
        $text = $exitCode === 0 && is_file($outputPath)
            ? trim((string) file_get_contents($outputPath))
            : '';

        @unlink($outputPath);

        return Str::limit($text, 6000, '');
    }

    private function extractImageText(AutoCvSession $session, string $path, string $mime): string
    {
        if (! is_file($path)) {
            return '';
        }

        $mime = str_starts_with($mime, 'image/') ? $mime : 'image/jpeg';
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            return '';
        }

        $payload = [
            'model' => self::MODEL,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Extract readable CV-related text from certificates, licences, awards, IDs, or training documents. Return JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Read this image and return {"text":"..."} with only useful CV details: certificate/licence/training name, issuing body, dates, licence numbers, expiry dates, qualification names, and any relevant names. If unreadable, return {"text":""}.',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path)),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(90)->withToken($apiKey)->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', $payload);
        } catch (Throwable) {
            return '';
        }

        if (! $response->successful()) {
            return '';
        }

        $this->recordAiUsage($session, $response);

        $decoded = json_decode((string) $response->json('choices.0.message.content', ''), true);

        if (! is_array($decoded)) {
            return '';
        }

        return Str::limit(trim((string) ($decoded['text'] ?? '')), 6000, '');
    }

    private function sendWhapiDocument(string $whatsappNumber, string $path, string $filename, ?string &$errorMessage = null): bool
    {
        [$token, $gatewayUrl] = $this->getWhapiCredentials();

        if (! $token) {
            $errorMessage = 'No active Whapi automation configured.';

            return false;
        }

        if (! is_file($path)) {
            $errorMessage = 'CV file not found for WhatsApp attachment.';

            return false;
        }

        $jid = preg_replace('/\D/', '', $whatsappNumber) . '@s.whatsapp.net';
        $mime = str_ends_with(strtolower($filename), '.pdf')
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

        try {
            $response = Http::timeout(60)->withToken($token)->post("{$gatewayUrl}/messages/document", [
                'to' => $jid,
                'media' => 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path)),
                'filename' => $filename,
                'caption' => 'CV document: ' . $filename,
            ]);
        } catch (Throwable $exception) {
            $errorMessage = 'WhatsApp document send exception: ' . $exception->getMessage();

            return false;
        }

        if (! $response->successful()) {
            $errorMessage = 'WhatsApp document send failed: HTTP ' . $response->status() . ' ' . Str::limit($response->body(), 250, '');

            return false;
        }

        return true;
    }

    private function getWhapiCredentials(): array
    {
        $automation = SocialAutomation::where('platform', 'whapi')->where('is_active', true)->first();

        if (! $automation) {
            return [null, null];
        }

        $settings = $automation->settings ?? [];
        $token = SocialAutomation::whapiToken($automation);
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        return $token ? [$token, $gatewayUrl] : [null, null];
    }

    private function buildTranscript(AutoCvSession $session): string
    {
        $lines = [];

        foreach ($session->answers ?: [] as $turn) {
            if (! empty($turn['question_sent'])) {
                $lines[] = 'Bot: ' . $turn['question_sent'];
            }
            $lines[] = 'Candidate: ' . $turn['reply'];
        }

        return implode("\n", $lines);
    }

    private function buildAiPayload(AutoCvSession $session): array
    {
        $topics = collect($session->topics ?: self::topics())
            ->map(fn ($topic, $index) => ($index + 1) . '. ' . $topic)
            ->implode("\n");

        $systemPrompt = <<<PROMPT
You are conducting a friendly WhatsApp interview for Wakanda Jobs (Zambia) to collect everything needed to build a candidate's CV.

Topics to cover:
{$topics}

Given the full conversation so far, you must:
1. Re-derive the ENTIRE structured CV JSON from scratch using everything the candidate has said in the whole conversation, not just the latest reply. Never invent employers, schools, qualifications, dates, references, phone numbers, emails, addresses, ages, or marital status. Use empty strings/arrays for anything not yet provided. Marital status and age are sensitive personal details — only fill them in if the candidate actually states them; if they decline or skip, accept that and score the bio topic on the rest of the contact details instead.
2. Decide which topic numbers (1-12, matching the list above) are not yet adequately covered, and return them as "missing_topics". A topic only counts as "covered" once its own score under rule 9 would be 90 or above (a "green" score) — never remove a topic from "missing_topics" just because you asked about it, or because the candidate gave a vague or partial answer. Keep asking follow-up questions about a topic, one at a time, until you can honestly score it 90+.
3. Read the candidate's latest reply carefully before deciding what to do next:
   - If it is a real, substantive answer (even if informal or incomplete), accept it, extract what you can, and move on per rule 5.
   - If it is a confusion signal or a non-answer — e.g. "I don't understand", "huh?", "what do you mean", "skip", a one-word reply that doesn't address what was asked, an emoji, or anything unrelated — do NOT just reword the same question formally again. (Exception: a one-word decline like "no"/"none"/"nothing" given in reply to a yes/no "is there anything else to add?" confirmation question IS a real, complete answer — see rule 5 — not a confusion signal.) Instead, explain the SAME topic in the simplest possible everyday language, break it into one tiny piece at a time, and give a short concrete example of the kind of answer you want (e.g. "No problem! For example, you could just say: 'I studied at Kitwe Technical College, Diploma in IT, 2019 to 2021.' What school or college did you go to, and what did you study?"). Keep the topic and "missing_topics" unchanged in this case — you are still waiting for the same topic to be answered, not moving to a new question.
   - Never present a simplified/example explanation as if it were a brand-new question — the candidate should clearly feel you are patiently re-explaining the same thing, not asking something different.
   - If the candidate gives only an abbreviation or acronym for the name of an institution, employer, or certifying body (e.g. "CBU", "UNZA", "NIPA", "BGS"), do not accept it and move straight to asking for other details (such as dates or job title) about that same entry, and do not silently expand it yourself even if you are confident you recognise it. Ask them directly to confirm or spell out the full name, by itself, before asking for any other details about that same entry — unless they explicitly say they don't know the full name or that there isn't a longer version, in which case accept the abbreviation as given and continue with the remaining details. Only put a full institution/employer name in structured_cv once the candidate has actually confirmed or typed it themselves. If the candidate names SEVERAL abbreviated institutions/employers in the same reply (e.g. "I worked at BGS and CBU"), ask them to confirm or spell out the full name for ALL of them together in that one clarifying message — and that message must contain ONLY the request for full names, nothing else. Do not combine it with a request for job titles, dates, or responsibilities; ask those only in a later message, once the full names are confirmed.
4. Topic 2 (bio and contact details) bundles several distinct pieces of information — mobile number, email address, residential address/town or city, age, marital status, and LinkedIn URL. NEVER ask for more than one of these in the same message, even though they all belong to the same topic — candidates skip or forget fields when asked for several things at once. Ask for them ONE AT A TIME, in plain, friendly, everyday language rather than form-like wording (e.g. "What's the best mobile number to reach you on?" rather than "Please provide your mobile number"; "Which town or city do you live in?" rather than "residential address"). Ask in a sensible order: mobile number first (it's essential for contacting them), then email, then town/city, then age, then marital status, then LinkedIn last as a casual optional ask (e.g. "Do you have a LinkedIn profile? No worries if not, we can skip that one."). Only move to the next field once the candidate has answered or clearly declined the current one — never bundle two unanswered fields into a single follow-up. Topic 2 only counts as covered once every field has been asked about and either answered or explicitly declined.
5. Topics 5 (education), 6 (work experience), 7 (internships/volunteer/projects), 9 (certificates/awards), and 11 (references) can have more than one entry:
   - For each entry the candidate mentions, make sure you collect every key field before treating that entry as done: for work experience — full employer name, job title, start and end dates (or "present"), and what they did; for education — full institution name, qualification, field of study, and start/end years; for projects, certifications, and references — the equivalent key details (e.g. dates for projects, issuing body and year for certificates). If the candidate has given some but not all of these for an entry, ask specifically for the missing piece(s) next — do not skip ahead to a different entry or a different topic while details are still missing for the current one.
   - Once you believe every entry the candidate has mentioned so far for that topic is fully detailed, do NOT immediately mark the topic as covered or move to a different topic. First ask a short confirmation question such as "Is that all your work experience, or is there anything else to add?" Only remove the topic from "missing_topics" once the candidate gives a clear answer confirming there is nothing more — this includes short/one-word declines such as "no", "none", "nothing", "nope", "no more", "that's all", "I'm done", or the same word repeated/emphasised (e.g. "NO!", "nothing else"). Treat ANY one-word or short decline reply to this specific confirmation question as a valid "nothing more" answer, not as confusion under rule 3 — rule 3's "non-answer" handling is for when the candidate doesn't address what was asked at all, not for a plain no/none/nothing reply to a yes/no confirmation question. If they mention another entry instead of declining, treat it the same way (collect its full details, then ask again if that's everything).
6. Once a topic has a real, complete answer (and, for the list-type topics above, once the candidate has confirmed there is nothing more to add), compose the single next WhatsApp message: a friendly, concise question about ONE topic — and, per rule 4, at most ONE field within that topic — that still needs covering. Never ask about more than one topic, or more than one field of topic 2, at a time. Prefer asking about a topic you haven't touched at all yet; but once every topic (1-12) has been asked about at least once, go back and revisit whichever topic currently has the LOWEST score with a clarifying follow-up — keep cycling back through the weak topics, one at a time, asking for more detail or clarity, until each one reaches 90+. Never skip a topic permanently just because the candidate's first answer to it was thin. Many candidates have not been to college and some may be applying for senior roles despite that — always use simple, everyday words a primary-school reading level can follow, never jargon or "big" words (say "level" or "how well", not "proficiency"; say "skills, tools, or things you're good at", not "competencies"; say "your last job", not "most recent employment"). If a CV term has no simple everyday equivalent, briefly explain it in plain words the first time you use it.
7. Once every topic (1-12) would score 90 or above under rule 9, set "is_complete" to true and make "next_message" a short, friendly closing line thanking the candidate and saying their CV is being prepared. If even one topic is still below 90, keep "is_complete" false and keep working through rule 6 until it isn't.
8. Rewrite informal answers into professional CV language when filling structured_cv. Use British English spelling. For "responsibilities" in work experience, write each as its own short bullet starting with a strong action verb (e.g. Led, Built, Reduced, Delivered, Automated, Designed, Managed) and include a number, percentage, time saved, or team size whenever the candidate's answer makes one available — never write generic duties-only phrasing like "Responsible for...". Use past tense for previous roles and present tense only for a role the candidate is still currently in.
9. For EVERY topic number 1-12, return a "section_scores" entry keyed by that number, with a "label" (short name of the topic), a "score" from 0-100 rating how complete and useful the information given so far is for that topic (0 = nothing provided, 100 = clear and complete), and "improve" — a short, specific, actionable tip for what would make that section stronger (empty string if score is 90 or above). Score honestly based on what is actually known, not on intentions. IMPORTANT exception: if the candidate has clearly and directly stated they have NONE for a topic where that is a normal, valid answer (e.g. "no work experience, I'm a fresh graduate", "no certificates", "none" for internships/projects), that is a complete answer, not a missing one — score it 90-100, leave the corresponding structured_cv array empty, and move on. Do not keep asking about a topic the candidate has already clearly said does not apply to them.
10. Based on everything known so far in structured_cv (skills, education, experience — even if still incomplete), suggest 3-5 realistic job positions in Zambia this candidate could apply for right now, as "suggested_job_positions": an array of {"title": "", "reason": ""} — "reason" is one short sentence on why it fits. Update this list every turn as more information comes in. If there isn't enough information yet to suggest anything sensible, return an empty array.

Return ONLY valid JSON, no markdown, in this exact shape:
{
  "structured_cv": {
    "full_name": "", "headline": "", "phone": "", "email": "", "address": "", "location": "", "age": "", "marital_status": "", "linkedin": "", "summary": "",
    "skills": [], "experience": [{"job_title": "", "company": "", "location": "", "start_date": "", "end_date": "", "responsibilities": []}],
    "education": [{"institution": "", "qualification": "", "field": "", "start_year": "", "end_year": ""}],
    "projects": [{"name": "", "description": "", "link": ""}], "certifications": [],
    "languages": [{"language": "", "proficiency": ""}],
    "references": [{"name": "", "role": "", "company": "", "phone": "", "email": ""}], "notes_for_admin": []
  },
  "missing_topics": [1, 5],
  "section_scores": {
    "1": {"label": "Full name", "score": 100, "improve": ""},
    "2": {"label": "Contact details", "score": 60, "improve": "Add an email address"}
  },
  "suggested_job_positions": [
    {"title": "Junior Software Developer", "reason": "Has a relevant IT degree and software development interest."}
  ],
  "next_message": "",
  "is_complete": false
}
PROMPT;

        $userPrompt = "Candidate name on file: {$session->candidate_name}\n"
            . "Candidate WhatsApp number on file: {$session->whatsapp_number}\n\n"
            . "Conversation so far:\n" . $this->buildTranscript($session);

        return [
            'model' => self::MODEL,
            'temperature' => 0,
            'max_tokens' => 2200,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];
    }

    private function decodeAiJson(Response $response): ?array
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

    private function looksLikeCandidateQuestion(string $message): bool
    {
        $message = trim($message);

        if ($message === '') {
            return false;
        }

        if (str_contains($message, '?')) {
            return true;
        }

        return (bool) preg_match('/\b(could you|please provide|please confirm|please list|what|when|where|which|who|do you|can you)\b/i', $message);
    }

    private function finalConfirmationMessage(AutoCvSession $session): string
    {
        return "Before I prepare your CV, please confirm that you have provided everything you want included.\n\n"
            . "Reply DONE if everything is complete, or send any extra details you still want added.";
    }

    private function isFinalConfirmation(string $body): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $body) ?? $body));

        return in_array($normalized, [
            'done',
            'yes',
            'yes done',
            'that is all',
            "that's all",
            'thats all',
            'everything',
            'everything is complete',
            'complete',
            'finish',
            'finished',
        ], true);
    }

    private function completeAfterFinalConfirmation(AutoCvSession $session, ?string $closingMessage = null): void
    {
        $closing = trim((string) $closingMessage) !== ''
            ? trim((string) $closingMessage)
            : "Thank you for all the details! I'm putting your CV together now.";
        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $closing, $errorMessage);

        if (! $sent) {
            $session->forceFill([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ])->save();

            $this->notifyAdmin($session, 'failed');

            return;
        }

        AutoCvMessage::query()->create([
            'session_id' => $session->getKey(),
            'direction' => 'outbound',
            'body' => $closing,
        ]);

        $session->forceFill([
            'status' => 'ready',
            'awaiting_final_confirmation' => false,
            'conversation_text' => $this->buildTranscript($session),
        ])->save();

        $this->finalizeSession($session->fresh());
    }

    private function nextWeakSectionQuestion(AutoCvSession $session): ?string
    {
        $section = collect($session->section_scores ?: [])
            ->filter(fn (array $section) => (int) ($section['score'] ?? 0) < 90)
            ->sortBy(fn (array $section) => (int) ($section['score'] ?? 0))
            ->first();

        if (! $section) {
            return null;
        }

        $label = trim((string) ($section['label'] ?? 'this section'));
        $improve = trim((string) ($section['improve'] ?? ''));

        return "Could you please provide a little more information for the {$label} section?"
            . ($improve !== '' ? "\n\nWhat would help most: {$improve}" : '');
    }

    private function recordAiUsage(AutoCvSession $session, Response $response): void
    {
        $promptTokens = (int) ($response->json('usage.prompt_tokens', 0) ?? 0);
        $completionTokens = (int) ($response->json('usage.completion_tokens', 0) ?? 0);
        $cost = $this->estimateCost(self::MODEL, $promptTokens, $completionTokens);

        $calls = $session->ai_calls ?: [];
        $calls[] = [
            'at' => now()->toDateTimeString(),
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
        ];

        $session->forceFill([
            'ai_calls' => $calls,
            'ai_total_prompt_tokens' => (int) $session->ai_total_prompt_tokens + $promptTokens,
            'ai_total_completion_tokens' => (int) $session->ai_total_completion_tokens + $completionTokens,
            'ai_total_cost_usd' => (float) $session->ai_total_cost_usd + $cost,
        ])->save();
    }

    private function estimateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        [$inputRate, $outputRate] = self::MODEL_PRICING_PER_MILLION[$model] ?? self::MODEL_PRICING_PER_MILLION['gpt-4o-mini'];

        return round((($promptTokens * $inputRate) + ($completionTokens * $outputRate)) / 1000000, 6);
    }

    private function sanitizeCv(array $cv, AutoCvSession $session): array
    {
        $cv['full_name'] = Str::limit(trim((string) ($cv['full_name'] ?? $session->candidate_name)), 150, '');
        $cv['headline'] = Str::limit(trim((string) ($cv['headline'] ?? '')), 150, '');
        $cv['phone'] = Str::limit(trim((string) ($cv['phone'] ?? $session->whatsapp_number)), 60, '');
        $cv['email'] = Str::limit(trim((string) ($cv['email'] ?? '')), 150, '');
        $cv['address'] = Str::limit(trim((string) ($cv['address'] ?? '')), 255, '');
        $cv['location'] = Str::limit(trim((string) ($cv['location'] ?? '')), 150, '');
        $cv['age'] = Str::limit(trim((string) ($cv['age'] ?? '')), 20, '');
        $cv['marital_status'] = Str::limit(trim((string) ($cv['marital_status'] ?? '')), 40, '');
        $cv['linkedin'] = Str::limit(trim((string) ($cv['linkedin'] ?? '')), 200, '');
        $cv['summary'] = Str::limit(trim((string) ($cv['summary'] ?? '')), 1200, '');

        foreach (['skills', 'notes_for_admin'] as $key) {
            $cv[$key] = collect($cv[$key] ?? [])
                ->map(fn ($value) => Str::limit(trim((string) (is_array($value) ? '' : $value)), 180, ''))
                ->filter()
                ->values()
                ->all();
        }

        $cv['certifications'] = collect($cv['certifications'] ?? [])
            ->map(function ($value) {
                if (is_array($value)) {
                    return implode(' - ', array_filter([
                        trim((string) ($value['name'] ?? '')),
                        trim((string) ($value['issuing_body'] ?? '')),
                        trim((string) ($value['date'] ?? $value['year'] ?? '')),
                    ]));
                }

                return trim((string) $value);
            })
            ->map(fn ($value) => Str::limit($value, 220, ''))
            ->filter()
            ->values()
            ->all();

        $cv['languages'] = collect($cv['languages'] ?? [])
            ->map(function ($row) {
                if (is_string($row)) {
                    return ['language' => Str::limit(trim($row), 60, ''), 'proficiency' => ''];
                }

                return is_array($row) ? [
                    'language' => Str::limit(trim((string) ($row['language'] ?? '')), 60, ''),
                    'proficiency' => Str::limit(trim((string) ($row['proficiency'] ?? '')), 40, ''),
                ] : null;
            })
            ->filter(fn ($row) => is_array($row) && $row['language'] !== '')
            ->values()
            ->all();

        foreach (['experience', 'education', 'projects', 'references'] as $key) {
            $cv[$key] = collect($cv[$key] ?? [])
                ->filter(fn ($row) => is_array($row))
                ->values()
                ->all();
        }

        return $cv;
    }

    private function sanitizeSectionScores(mixed $sectionScores, AutoCvSession $session): array
    {
        $topics = $session->topics ?: self::topics();
        $sectionScores = is_array($sectionScores) ? $sectionScores : [];
        $result = [];

        foreach ($topics as $index => $topic) {
            $key = (string) ($index + 1);
            $row = is_array($sectionScores[$key] ?? null) ? $sectionScores[$key] : [];

            $result[$key] = [
                'label' => Str::limit(trim((string) ($row['label'] ?? $topic)), 80, ''),
                'score' => max(0, min(100, (int) ($row['score'] ?? 0))),
                'improve' => Str::limit(trim((string) ($row['improve'] ?? '')), 200, ''),
            ];
        }

        return $result;
    }

    private function sanitizeJobPositions(mixed $positions): array
    {
        return collect(is_array($positions) ? $positions : [])
            ->filter(fn ($row) => is_array($row) && trim((string) ($row['title'] ?? '')) !== '')
            ->map(fn ($row) => [
                'title' => Str::limit(trim((string) $row['title']), 100, ''),
                'reason' => Str::limit(trim((string) ($row['reason'] ?? '')), 200, ''),
            ])
            ->take(5)
            ->values()
            ->all();
    }

    private function storeDocuments(AutoCvSession $session, array $cv): array
    {
        $cv = $this->polishCvForDocuments($session, $cv);
        $baseDir = 'auto-cv-bot/session-' . $session->getKey();
        $name = (string) ($cv['full_name'] ?: $session->candidate_name ?: 'Candidate');
        $safeName = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', trim($name)), '_') ?: 'Candidate';
        $styles = [
            'premium' => 'Premium',
            'academic' => 'Academic',
            'creative' => 'Creative',
        ];

        // The rendered CV document always shows "Available on request" for references — never the
        // candidate's actual contacts — per standard CV/ATS best practice. The real details the candidate
        // gave are still kept in structured_cv for the admin's own records.
        $renderCv = $cv;
        $renderCv['references'] = [['name' => 'Available on request', 'role' => '', 'company' => '', 'phone' => '', 'email' => '']];

        $paths = [];

        foreach ($styles as $style => $label) {
            $docxPath = "{$baseDir}/{$safeName}_CV_{$label}.docx";
            $pdfPath = "{$baseDir}/{$safeName}_CV_{$label}.pdf";

            Storage::disk('local')->put($docxPath, $this->renderDocx($renderCv, $style));

            $pdfCv = $renderCv;
            $pdfCv['languages'] = collect($renderCv['languages'] ?? [])
                ->map(fn ($row) => trim(($row['language'] ?? '') . (($row['proficiency'] ?? '') ? ' (' . $row['proficiency'] . ')' : '')))
                ->filter()
                ->values()
                ->all();

            $pdf = Pdf::loadView('plugins/job-board::candidate-alerts.cv-builder-pdf', [
                'cv' => $pdfCv,
                'generatedAt' => now()->format('d M Y'),
                'design' => $style,
                'designLabel' => $label,
            ])->setPaper('a4');

            Storage::disk('local')->put($pdfPath, $pdf->output());

            $paths[$style] = [
                'label' => $label,
                'docx_path' => $docxPath,
                'pdf_path' => $pdfPath,
            ];
        }

        return $paths;
    }

    private function polishCvForDocuments(AutoCvSession $session, array $cv): array
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            return $cv;
        }

        $payload = [
            'model' => self::MODEL,
            'temperature' => 0.2,
            'max_tokens' => 2600,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a senior CV writer for Zambia. Rewrite the supplied structured CV into polished, professional British English for a premium CV document. Do not invent facts, dates, employers, schools, qualifications, contacts, achievements, tools, references, or metrics. Keep empty fields empty. Improve grammar, clarity, section wording, action verbs, and concise bullets only from the provided data. Return only JSON with a top-level "structured_cv" object in the same schema.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'candidate_name_on_file' => $session->candidate_name,
                        'whatsapp_number_on_file' => $session->whatsapp_number,
                        'structured_cv' => $cv,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
        ];

        try {
            $response = Http::timeout(90)->withToken($apiKey)->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', $payload);
        } catch (Throwable) {
            return $cv;
        }

        if (! $response->successful()) {
            return $cv;
        }

        $decoded = $this->decodeAiJson($response);

        if (! is_array($decoded) || ! is_array($decoded['structured_cv'] ?? null)) {
            return $cv;
        }

        $this->recordAiUsage($session, $response);

        return $this->sanitizeCv($decoded['structured_cv'], $session);
    }

    private function cvIsEmpty(array $cv): bool
    {
        return collect([
            $cv['full_name'] ?? '',
            $cv['headline'] ?? '',
            $cv['summary'] ?? '',
            $cv['email'] ?? '',
            $cv['location'] ?? '',
        ])->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()
            && empty($cv['skills'])
            && empty($cv['experience'])
            && empty($cv['education'])
            && empty($cv['projects'])
            && empty($cv['certifications']);
    }

    private function renderDocx(array $cv, string $design = 'premium'): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('PHP ZipArchive extension is required to generate DOCX files.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'cv-docx-');
        $zip = new ZipArchive();

        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create DOCX archive.');
        }

        $zip->addFromString('[Content_Types].xml', $this->docxContentTypes());
        $zip->addFromString('_rels/.rels', $this->docxRels());
        $zip->addFromString('word/_rels/document.xml.rels', $this->docxDocumentRels());
        $zip->addFromString('word/styles.xml', $this->docxStyles());
        $zip->addFromString('word/document.xml', $this->docxDocument($cv, $design));
        $zip->close();

        $contents = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $contents;
    }

    private function docxDocument(array $cv, string $design = 'premium'): string
    {
        // Section order follows standard ATS-safe CV convention: contact, summary, experience,
        // education, skills, certifications, projects, languages, references.
        $body = [];

        if ($design === 'academic') {
            $body[] = $this->docxParagraph('CURRICULUM VITAE', 'Title');
            $body[] = $this->docxParagraph(strtoupper((string) ($cv['full_name'] ?? 'Candidate')), 'Subtitle');
        } elseif ($design === 'creative') {
            $body[] = $this->docxParagraph(strtoupper((string) ($cv['full_name'] ?? 'Candidate CV')), 'Title');
            $body[] = $this->docxParagraph('Professional Portfolio CV', 'Subtitle');
        } else {
            $body[] = $this->docxParagraph(strtoupper((string) ($cv['full_name'] ?? 'Candidate CV')), 'Title');
        }
        $body[] = $this->docxParagraph(implode(' | ', array_filter([
            $cv['headline'] ?? '',
            $cv['phone'] ?? '',
            $cv['email'] ?? '',
            $cv['location'] ?? '',
        ])), 'Subtitle');
        $bioLine = implode(' | ', array_filter([
            ! empty($cv['linkedin']) ? $cv['linkedin'] : '',
            ! empty($cv['address']) ? 'Address: ' . $cv['address'] : '',
            ! empty($cv['age']) ? 'Age: ' . $cv['age'] : '',
            ! empty($cv['marital_status']) ? 'Marital Status: ' . $cv['marital_status'] : '',
        ]));

        if ($bioLine !== '') {
            $body[] = $this->docxParagraph($bioLine, 'Subtitle');
        }

        $this->appendSection($body, 'Professional Summary', array_filter([(string) ($cv['summary'] ?? '')]));

        $experienceRows = (array) ($cv['experience'] ?? []);
        if ($experienceRows !== []) {
            $body[] = $this->docxParagraph('Work Experience', 'Heading1');

            foreach ($experienceRows as $row) {
                $dates = implode(' to ', array_filter([$row['start_date'] ?? '', $row['end_date'] ?? '']));
                $body[] = $this->docxParagraphRuns([
                    ['text' => trim(implode(' - ', array_filter([$row['job_title'] ?? '', $row['company'] ?? '']))), 'bold' => true],
                    ['text' => $dates ? " ({$dates})" : ''],
                ]);

                foreach ((array) ($row['responsibilities'] ?? []) as $line) {
                    $body[] = $this->docxParagraph('• ' . $line);
                }
            }
        }

        $educationRows = (array) ($cv['education'] ?? []);
        if ($educationRows !== []) {
            $body[] = $this->docxParagraph('Education', 'Heading1');

            foreach ($educationRows as $row) {
                $years = trim(implode(' - ', array_filter([$row['start_year'] ?? '', $row['end_year'] ?? ''])));
                $body[] = $this->docxParagraphRuns([
                    ['text' => trim(implode(' - ', array_filter([$row['qualification'] ?? '', $row['field'] ?? '', $row['institution'] ?? '']))), 'bold' => true],
                    ['text' => $years ? " ({$years})" : ''],
                ]);
            }
        }

        $this->appendSection($body, 'Skills', $cv['skills'] ?? [], true);
        $this->appendSection($body, 'Certifications and Training', $cv['certifications'] ?? [], true);

        $projects = [];
        foreach ($cv['projects'] ?? [] as $row) {
            $line = trim(($row['name'] ?? '') . (($row['description'] ?? '') ? ': ' . $row['description'] : ''));
            $projects[] = trim($line . (($row['link'] ?? '') ? ' — ' . $row['link'] : ''));
        }
        $this->appendSection($body, 'Projects and Volunteer Work', $projects);

        $languages = collect($cv['languages'] ?? [])
            ->map(fn ($row) => trim(implode(' - ', array_filter([$row['language'] ?? '', $row['proficiency'] ?? '']))))
            ->all();
        $this->appendSection($body, 'Languages', $languages, true);

        // References are always shown as "available on request" on the rendered CV — never the
        // candidate's actual contacts — regardless of what was collected during the interview.
        $this->appendSection($body, 'References', ['Available on request']);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" mc:Ignorable="w14 wp14">'
            . '<w:body>' . implode('', $body)
            . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1008" w:right="1008" w:bottom="1008" w:left="1008" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
            . '</w:body></w:document>';
    }

    private function appendSection(array &$body, string $title, array $lines, bool $bullets = false): void
    {
        $lines = array_values(array_filter(array_map(fn ($line) => trim((string) $line), $lines)));

        if ($lines === []) {
            return;
        }

        $body[] = $this->docxParagraph($title, 'Heading1');

        foreach ($lines as $line) {
            $body[] = $this->docxParagraph(($bullets && ! str_starts_with($line, '•') ? '• ' : '') . $line);
        }
    }

    private function docxParagraph(string $text, ?string $style = null): string
    {
        $styleXml = $style ? '<w:pPr><w:pStyle w:val="' . $style . '"/></w:pPr>' : '';

        return '<w:p>' . $styleXml . '<w:r><w:t xml:space="preserve">' . $this->xml($text) . '</w:t></w:r></w:p>';
    }

    private function docxParagraphRuns(array $runs, ?string $style = null): string
    {
        $styleXml = $style ? '<w:pPr><w:pStyle w:val="' . $style . '"/></w:pPr>' : '';
        $runXml = '';

        foreach ($runs as $run) {
            $text = (string) ($run['text'] ?? '');

            if (trim($text) === '') {
                continue;
            }

            $rPr = ! empty($run['bold']) ? '<w:rPr><w:b/></w:rPr>' : '';
            $runXml .= '<w:r>' . $rPr . '<w:t xml:space="preserve">' . $this->xml($text) . '</w:t></w:r>';
        }

        return '<w:p>' . $styleXml . $runXml . '</w:p>';
    }

    private function docxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>';
    }

    private function docxRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>';
    }

    private function docxDocumentRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>';
    }

    private function docxStyles(): string
    {
        // Explicit ATS-safe font and restrained premium colours; still readable in parsers.
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos" w:cs="Arial"/><w:sz w:val="22"/><w:color w:val="1F2937"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:line="276" w:lineRule="auto" w:after="120"/></w:pPr></w:pPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:pPr><w:spacing w:after="40"/></w:pPr><w:rPr><w:b/><w:color w:val="111827"/><w:sz w:val="38"/><w:caps/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:pPr><w:spacing w:after="70"/></w:pPr><w:rPr><w:color w:val="4B5563"/><w:sz w:val="20"/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:pPr><w:spacing w:before="260" w:after="90"/><w:pBdr><w:bottom w:val="single" w:sz="8" w:space="4" w:color="2563EB"/></w:pBdr></w:pPr><w:rPr><w:b/><w:color w:val="1E3A8A"/><w:sz w:val="24"/><w:caps/></w:rPr></w:style>'
            . '</w:styles>';
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
