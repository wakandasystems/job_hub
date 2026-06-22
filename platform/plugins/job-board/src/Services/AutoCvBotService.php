<?php

namespace Botble\JobBoard\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Botble\JobBoard\Models\AutoCvMessage;
use Botble\JobBoard\Models\AutoCvSession;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\Media\Facades\RvMedia;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

class AutoCvBotService
{
    private const DEFAULT_MODEL = 'gpt-4o-mini';

    /** The AI assistant's name, shown to candidates — never refer to it as a "bot". */
    private const PERSONA_NAME = 'Nakia';

    /** [input $ per million tokens, output $ per million tokens]. Cheapest first — keep that order, the admin dropdown follows it. */
    private const MODEL_PRICING_PER_MILLION = [
        'gpt-4o-mini' => [0.15, 0.60],
        'gpt-4.1-mini' => [0.40, 1.60],
        'gpt-5.4-mini' => [0.75, 4.50],
        'gpt-4.1' => [2.00, 8.00],
        'gpt-4o' => [2.50, 10.00],
        'gpt-5.4' => [2.50, 15.00],
    ];

    /** The model actually used for a request — configurable from the CV Bot admin page, falls back to the default if unset or invalid. */
    public static function aiModel(): string
    {
        $model = (string) setting('auto_cv_bot_ai_model', self::DEFAULT_MODEL);

        return array_key_exists($model, self::MODEL_PRICING_PER_MILLION) ? $model : self::DEFAULT_MODEL;
    }

    public static function availableAiModels(): array
    {
        return array_keys(self::MODEL_PRICING_PER_MILLION);
    }

    // Raised from 18 after checking real session data: most genuine interviews already run
    // 20-30 turns, so 18 was firing on the majority of sessions and wasn't a useful signal.
    private const MAX_AI_TURNS = 25;

    // Previously there was no real ceiling at all — just the cosmetic note above. One session
    // spiralled to 42 turns repeating the same question before the candidate gave up ("Am tired
    // of sending all over again my details"). This is a genuine hard stop so no session can run
    // forever even if scoring never reaches 90 for every topic; set comfortably above the longest
    // legitimate session observed (42) so it only kicks in for runaway cases.
    private const HARD_STOP_AI_TURNS = 50;

    /** Pause between consecutive WhatsApp bubbles of the same message, so they read as a natural chat rhythm. */
    private const CHAT_BUBBLE_DELAY_MICROSECONDS = 1_200_000;

    public static function topics(): array
    {
        return [
            'Full name as it should appear on the CV',
            'Bio and contact details: mobile number, email address, where they live (town/city), age, marital status, and LinkedIn profile link if they have one',
            'Job title or type of work being looked for',
            'Short personal profile: strengths and what kind of worker they are',
            'Education, highest qualification first (secondary/high school, college, or university — qualification, field, years)',
            'Work experience (company, job title, dates, what they did)',
            'Internships, attachments, volunteer work, or projects — including a link to GitHub or live work if they have one',
            'Strongest skills, tools, software, machines, or languages they can use',
            'Certificates, licences, trainings, or awards — ask the candidate to describe each one in their own words (name, issuing body, year); only mention sending a photo as a fallback if they say they cannot remember the details',
            'Languages they speak and how well they speak each one (e.g. fluent, okay, a little)',
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
            'awaiting_cv_upload' => true,
        ]);

        $cvUploadQuestion = 'Do you already have a CV you can share?'
            . "\n\nIf you do, go ahead and send it over — a PDF, Word document, or even a clear photo all work."
            . "\n\nIf not, no worries at all! Just reply \"no\" and we'll go through a few easy questions together instead.";

        $opening = $this->generateOpeningMessage($session, $candidateName, $cvUploadQuestion);

        $errorMessage = null;
        $personaImagePath = $this->settingImageLocalPath('auto_cv_bot_persona_image');

        $sent = $personaImagePath
            ? $this->sendWhapiImage($session->whatsapp_number, $personaImagePath, $opening, $errorMessage)
            : $this->sendWhapiMessage($session->whatsapp_number, $opening, $errorMessage);

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
    private function generateOpeningMessage(AutoCvSession $session, ?string $candidateName, string $firstQuestion): string
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            return $this->fallbackOpeningMessage($candidateName, $firstQuestion);
        }

        $personaName = self::PERSONA_NAME;

        $systemPrompt = <<<PROMPT
You are writing the very first WhatsApp message(s) a job candidate in Zambia receives from Wakanda Jobs, introducing yourself before a short interview to build their CV.

Your name is {$personaName}. You are Wakanda Jobs' CV assistant. Never use the word "bot" anywhere. Many candidates feel nervous messaging an automated system for the first time, so your tone must be warm, calm, reassuring, and natural, like a kind person — never robotic, corporate, or scripted.

Real WhatsApp conversations read as several short separate messages, not one long paragraph. Write your reply as 3-6 SHORT separate messages, each just one short sentence or two, with a blank line between every one of them — never write one big paragraph.

Across those short messages:
- Greet the candidate by name if one is given, otherwise a warm general greeting — with a welcoming feel, e.g. a wave emoji and welcoming them to Wakanda Jobs.
- Introduce yourself by name as Wakanda Jobs' CV assistant who will help them create a professional CV step by step, phrased in your own natural words — vary the wording every time, never reuse the exact same sentence twice.
- Reassure them they don't need to make their answers perfect — they can just respond naturally, one simple question at a time.
- Then ask exactly this, keeping it split across separate short messages in the same order shown below, worded naturally in your own way but keeping the meaning of each part exactly the same — never merge separate parts into one sentence, and never drop a part: "{$firstQuestion}"

Rules:
- Never describe what will be done with anything the candidate sends — no "I'll extract", "I'll pull information from it", "I'll analyse it", "I'll process it", or similar. Just ask for it, nothing about what happens to it afterwards.
- Never use the word "bot".
- Output ONLY the message text, with each short message separated by a blank line — no preamble, no quotes, no markdown fences, no numbering.
PROMPT;

        try {
            $response = Http::timeout(30)->withToken($apiKey)->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::aiModel(),
                    'temperature' => 0.9,
                    'max_tokens' => 280,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => 'Candidate name: ' . ($candidateName ?: '(not given)')],
                    ],
                ]);
        } catch (Throwable) {
            return $this->fallbackOpeningMessage($candidateName, $firstQuestion);
        }

        if (! $response->successful()) {
            return $this->fallbackOpeningMessage($candidateName, $firstQuestion);
        }

        $text = trim((string) $response->json('choices.0.message.content', ''));
        $text = trim((string) preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text));

        if ($text === '') {
            return $this->fallbackOpeningMessage($candidateName, $firstQuestion);
        }

        $this->recordAiUsage($session, $response);

        return $text;
    }

    private function fallbackOpeningMessage(?string $candidateName, string $firstQuestion): string
    {
        return "Hi" . ($candidateName ? " {$candidateName}" : '') . "! 👋 Welcome to Wakanda Jobs. I'm " . self::PERSONA_NAME . ", your CV assistant, here to help you create a professional CV step by step.\n\n"
            . "Don't worry about making your answers perfect — just respond naturally.\n\n"
            . "Let's get started with something simple.\n\n"
            . $firstQuestion;
    }

    public function handleInboundReply(string $fromDigits, string $body, ?string $whapiMessageId): void
    {
        $body = trim($body);

        if ($fromDigits === '' || $body === '') {
            return;
        }

        // Whapi can deliver two webhook requests for the same candidate close together (e.g. they
        // send two WhatsApp messages seconds apart). Without serializing per-candidate, both could
        // be processed by overlapping PHP workers at once, each calling OpenAI off a slightly stale
        // session snapshot — seen in production as two different, conflicting questions sent within
        // seconds of each other. Block briefly rather than processing concurrently.
        Cache::lock("auto-cv-bot:inbound:{$fromDigits}", 120)->block(60, function () use ($fromDigits, $body, $whapiMessageId): void {
            $this->processInboundReplyLocked($fromDigits, $body, $whapiMessageId);
        });
    }

    private function processInboundReplyLocked(string $fromDigits, string $body, ?string $whapiMessageId): void
    {
        $session = $this->findActiveSessionByDigits($fromDigits);

        if (! $session) {
            return;
        }

        if ($session->awaiting_cv_photo) {
            $this->handleCvPhotoTextReply($session, $body, $whapiMessageId);

            return;
        }

        if ($session->awaiting_cv_upload && $this->looksLikeBareYes($body)) {
            $this->promptToSendCvNow($session, $body, $whapiMessageId);

            return;
        }

        if ($session->awaiting_cv_upload) {
            // Whatever they said — a decline ("no") or their CV pasted as plain text — is handled
            // generically by the normal AI turn below: a decline leaves structured_cv empty so the
            // AI naturally starts from topic 1, while pasted CV text gets folded straight into
            // structured_cv per the AI prompt's existing "fold in anything new" instruction.
            $session->awaiting_cv_upload = false;
        }

        $this->recordInboundAndProcess($session, $body, $whapiMessageId);
    }

    public function handleInboundAttachment(string $fromDigits, array $message, ?string $whapiMessageId): void
    {
        if ($fromDigits === '') {
            return;
        }

        // Same per-candidate serialization as handleInboundReply() — an attachment and a text
        // message (or two attachments) arriving within the same second must not be processed
        // by two overlapping AI turns at once.
        Cache::lock("auto-cv-bot:inbound:{$fromDigits}", 120)->block(60, function () use ($fromDigits, $message, $whapiMessageId): void {
            $this->processInboundAttachmentLocked($fromDigits, $message, $whapiMessageId);
        });
    }

    private function processInboundAttachmentLocked(string $fromDigits, array $message, ?string $whapiMessageId): void
    {
        $session = $this->findActiveSessionByDigits($fromDigits);

        if (! $session) {
            return;
        }

        if ($session->awaiting_cv_photo) {
            $this->handleCvPhotoAttachmentReply($session, $message, $whapiMessageId);

            return;
        }

        [$extractedText, $label] = $this->extractAttachmentText($session, $message, $whapiMessageId);
        $isCvUpload = (bool) $session->awaiting_cv_upload;

        if ($extractedText !== '') {
            $body = $isCvUpload
                ? trim('Candidate shared their existing CV as a file' . ($label ? " ({$label})" : '')
                    . ". Extract as much real information as possible from it across every topic below, "
                    . "and only ask about whatever is still missing or unclear.\n\nExtracted text:\n\n" . $extractedText)
                : trim('Candidate sent an attachment' . ($label ? " ({$label})" : '') . ". Extracted text:\n\n" . $extractedText);

            if ($isCvUpload) {
                $session->awaiting_cv_upload = false;
            }

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

        $fallback = $isCvUpload
            ? "Thanks for sending that — I couldn't read enough from it, sorry. Could you try a clearer "
                . "photo or a PDF/Word document, or just type out your CV details instead and I'll take it from there?"
            : "Thanks, I received the file/photo, but I can't read enough CV details from it.\n\n"
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

    private function looksLikeBareYes(string $body): bool
    {
        $normalized = trim((string) preg_replace('/[^a-z ]/', '', strtolower($body)));

        return in_array($normalized, [
            'yes', 'yeah', 'yep', 'yup', 'sure', 'ok', 'okay',
            'i do', 'i have one', 'i have a cv', 'yes i do', 'yes i have one',
        ], true);
    }

    private function promptToSendCvNow(AutoCvSession $session, string $inboundBody, ?string $whapiMessageId): void
    {
        if (! $this->logInboundMessage($session, $inboundBody, $whapiMessageId)) {
            return;
        }

        $session->forceFill(['last_reply_at' => now()])->save();

        $this->sendAndLogOutbound($session, "Great — go ahead and send it now.\n\nA PDF, Word document, or a clear photo of it all work.");
    }

    /** Records an inbound WhatsApp message, ignoring webhook redeliveries of the same message id. */
    private function logInboundMessage(AutoCvSession $session, string $body, ?string $whapiMessageId, ?array $payload = null): bool
    {
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
            return false;
        }

        return true;
    }

    /** Sends a WhatsApp message, logs it, and updates last_question_text — for steps that aren't a full AI turn. */
    private function sendAndLogOutbound(AutoCvSession $session, string $message): bool
    {
        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $message, $errorMessage);

        if (! $sent) {
            $session->forceFill([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ])->save();

            $this->notifyAdmin($session, 'failed');

            return false;
        }

        AutoCvMessage::query()->create([
            'session_id' => $session->getKey(),
            'direction' => 'outbound',
            'body' => $message,
        ]);

        $session->forceFill([
            'last_question_text' => $message,
            'last_question_sent_at' => now(),
        ])->save();

        return true;
    }

    private function findActiveSessionByDigits(string $fromDigits): ?AutoCvSession
    {
        return AutoCvSession::query()
            ->whereIn('status', ['collecting', 'failed', 'stalled'])
            ->latest()
            ->get()
            ->first(fn (AutoCvSession $candidate) => preg_replace('/\D/', '', (string) $candidate->whatsapp_number) === $fromDigits);
    }

    /**
     * Scheduled sweeps (reminders, timeout completions) read a session, decide to send a
     * message, then send it — with no lock, that decision can be stale by the time it runs,
     * racing a live inbound reply being handled under the "auto-cv-bot:inbound:*" lock at the
     * same moment. Seen in production as two conflicting outbound messages (a cron-driven one
     * and an AI-driven one) interleaved within the same minute. Non-blocking: if a live inbound
     * reply currently holds the lock, skip this session for now rather than waiting — the next
     * sweep a minute later will re-evaluate it against the now-current state.
     */
    private function withCandidateLock(AutoCvSession $session, callable $callback): mixed
    {
        $digits = preg_replace('/\D/', '', (string) $session->whatsapp_number) ?: (string) $session->whatsapp_number;

        return Cache::lock("auto-cv-bot:inbound:{$digits}", 120)->get($callback);
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

        $wasAwaitingFinalConfirmation = (bool) $session->awaiting_final_confirmation;

        $session->forceFill([
            'status' => 'collecting',
            'answers' => $answers,
            'last_reply_at' => now(),
            'candidate_reminder_count' => 0,
            'last_candidate_reminder_sent_at' => null,
            'error_message' => null,
            'error_trace' => null,
        ])->save();

        if ($wasAwaitingFinalConfirmation && ($this->isFinalConfirmation($body) || $this->aiSaysCandidateIsDone($session, $body))) {
            $this->completeAfterFinalConfirmation($session->fresh());

            return;
        }

        // Candidates often send the extra details and "Done" as two separate WhatsApp
        // messages seconds apart. Don't clear the confirmation flag just because this
        // particular message wasn't "Done" — fold it in as extra detail (below) and
        // re-show the same confirmation question, instead of letting the general
        // interview engine wander off onto an unrelated follow-up that the candidate's
        // next "Done" would then miss.
        $this->processReplyWithAi($session->fresh(), $wasAwaitingFinalConfirmation);
    }

    private function processReplyWithAi(AutoCvSession $session, bool $forceReconfirm = false): void
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

        // Stronger safety net: this exact question has already been sent 2+ times before in this
        // session (not just immediately prior) — seen in production spiralling across several
        // turns with real answers given each time, never accepted, until the candidate gave up
        // ("Am tired of sending all over again my details"). Gentle rephrasing has already failed
        // by this point, so force the model to stop asking and move on instead of trying again.
        $priorAskCount = $this->timesQuestionAlreadyAsked($session, (string) $decoded['next_message']);

        if ($priorAskCount >= 2) {
            $stuckPayload = $payload;
            $stuckPayload['messages'][] = [
                'role' => 'user',
                'content' => "You are about to send the exact question \"{$decoded['next_message']}\" for at least the " . ($priorAskCount + 1) . 'th time. The candidate has already replied to it every previous time — stop asking it. Use whatever they most recently said as the final value for that field/entry, even if imperfect or informal (or set it to "Not specified" if you genuinely cannot tell what it is from anything they have said), mark it resolved, and compose next_message about a DIFFERENT topic or field instead.',
            ];

            try {
                $stuckResponse = Http::timeout(90)->withToken($apiKey)->acceptJson()
                    ->post('https://api.openai.com/v1/chat/completions', $stuckPayload);

                $stuckDecoded = $stuckResponse->successful() ? $this->decodeAiJson($stuckResponse) : null;

                if (is_array($stuckDecoded) && is_array($stuckDecoded['structured_cv'] ?? null) && is_string($stuckDecoded['next_message'] ?? null) && trim($stuckDecoded['next_message']) !== '') {
                    $decoded = $stuckDecoded;
                    $response = $stuckResponse;
                }
            } catch (Throwable) {
                // Fall through with the original (repeated) message rather than failing the turn.
            }
        } elseif ($this->isRepeatOfLastQuestion((string) $decoded['next_message'], $session->last_question_text)) {
            // Safety net: the model occasionally resends the same question verbatim instead of
            // clarifying — e.g. a candidate politely asking "kindly clarify a bit on the projects"
            // got the exact same question echoed straight back, which feels broken and ignores them.
            // Force one explicit clarification retry rather than letting that go out to the candidate.
            $clarifyPayload = $payload;
            $clarifyPayload['messages'][] = [
                'role' => 'user',
                'content' => 'Your next_message was the same (or almost the same) question you already asked, word for word. The candidate is confused or asked for clarification, not confirming they have nothing to add. Per rule 3, explain the SAME topic in simpler everyday language, break it into one small piece, and give a short concrete example that actually matches that topic — do not resend the same wording, and do not reuse an example written for a different topic.',
            ];

            try {
                $clarifyResponse = Http::timeout(90)->withToken($apiKey)->acceptJson()
                    ->post('https://api.openai.com/v1/chat/completions', $clarifyPayload);

                $clarifyDecoded = $clarifyResponse->successful() ? $this->decodeAiJson($clarifyResponse) : null;

                if (is_array($clarifyDecoded) && is_array($clarifyDecoded['structured_cv'] ?? null) && is_string($clarifyDecoded['next_message'] ?? null) && trim($clarifyDecoded['next_message']) !== '') {
                    $decoded = $clarifyDecoded;
                    $response = $clarifyResponse;
                }
            } catch (Throwable) {
                // Fall through with the original (repeated) message rather than failing the turn.
            }
        }

        $this->recordAiUsage($session, $response);

        $totalTopics = count($session->topics ?: self::topics());
        $sectionScores = $this->sanitizeSectionScores($decoded['section_scores'] ?? [], $session);
        $sectionScores = $this->enforceMandatoryLanguagesTopic($sectionScores, (array) ($decoded['structured_cv'] ?? []), $session);

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

        // Hard ceiling: no matter how many topics are still weak, stop drilling once a session
        // has run this long — finish with whatever was captured rather than risk an endless loop.
        if (! $isComplete && $turnsTaken >= self::HARD_STOP_AI_TURNS) {
            $isComplete = true;
            $decoded['structured_cv']['notes_for_admin'] = array_merge(
                (array) ($decoded['structured_cv']['notes_for_admin'] ?? []),
                ['Hit the hard turn ceiling — some sections may still be weak; please review before sending.']
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

        // The candidate was already at the final "reply DONE" step and sent something else
        // instead — their extra detail has just been folded into structured_cv above, but
        // the next thing they see must be the same confirmation question again, not a fresh
        // interview question, so a quick follow-up "Done" right after still gets caught.
        if ($forceReconfirm) {
            $isComplete = true;
        }

        // This reply just closed out a section we'd specifically reopened (e.g. an admin's
        // "ask about this weak section" follow-up) — without an acknowledgement here, jumping
        // straight to the generic "please confirm" prompt reads as if the bot never saw the
        // candidate's answer.
        $justAcknowledgedReopen = $isComplete && ! $forceReconfirm && (bool) $session->reopened_for_missing_detail;

        $session->forceFill([
            'structured_cv' => $this->sanitizeCv($decoded['structured_cv'], $session),
            'topics_covered' => $topicsCovered,
            'section_scores' => $sectionScores,
            'suggested_job_positions' => $this->sanitizeJobPositions($decoded['suggested_job_positions'] ?? []),
        ])->save();

        if ($isComplete) {
            $nextMessage = $this->finalConfirmationMessage($session, $justAcknowledgedReopen);
        }

        $errorMessage = null;
        $confirmationImagePath = $isComplete ? $this->settingImageLocalPath('auto_cv_bot_confirmation_image') : null;

        $sent = $confirmationImagePath
            ? $this->sendWhapiImage($session->whatsapp_number, $confirmationImagePath, $nextMessage, $errorMessage)
            : $this->sendWhapiMessage($session->whatsapp_number, $nextMessage, $errorMessage);

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
            // A fresh prompt just went out, so any earlier "I'll wait 30 minutes" warning no
            // longer applies — the timeout clock restarts from this message.
            'reopen_warning_sent_at' => null,
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

        return (bool) $this->withCandidateLock($session, function () use ($session) {
            $session->refresh();

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
        });
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
            'awaiting_final_confirmation' => false,
            // Same situation as continueInterview()'s reopen case — we're waiting on real new
            // content for a specific weak section, not just a "Done" confirmation, so this
            // needs the longer 30-minute grace period in completeTimedOutConfirmations()
            // instead of the short 2-minute one for plain confirmations.
            'reopened_for_missing_detail' => true,
            'reopen_warning_sent_at' => null,
        ])->save();

        return true;
    }

    /** Admin-triggered re-ask for the candidate's CV photo — same "Get More Info" pattern as requestSectionInformation(), but the photo step lives outside the normal topic loop. */
    public function requestCvPhoto(AutoCvSession $session): bool
    {
        $message = "Hi" . ($session->candidate_name ? " {$session->candidate_name}" : '') . ", we are finishing up your CV — would you like to add a photo to it?\n\n"
            . "If so, send a clear photo of yourself now (JPG or PNG).\n\n"
            . "If not, just reply \"no\" and I'll leave it as is.";

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
            'awaiting_cv_photo' => true,
            'awaiting_final_confirmation' => false,
            'last_question_text' => $message,
            'last_question_sent_at' => now(),
            'candidate_reminder_count' => 0,
            'last_candidate_reminder_sent_at' => null,
            'error_message' => null,
            'error_trace' => null,
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

    /**
     * Sends a prospect a sample of our 3 CV designs, prefilled with AI-generated (entirely
     * fictitious) content for the given role, so they can judge quality before signing up.
     * The sample documents are generated once per role and cached on disk/in settings —
     * later calls for the same role just resend the cached PDFs.
     */
    public function sendSampleCv(string $whatsappNumber, string $role = 'Accounts/Finance Manager'): array
    {
        $whatsappNumber = trim($whatsappNumber);

        if ($whatsappNumber === '') {
            return [false, 'WhatsApp number is required.'];
        }

        $paths = $this->sampleCvDocumentPaths($role);

        if (! $paths) {
            return [false, 'Could not prepare the sample CV. Check the OpenAI key and try again.'];
        }

        $intro = "Hi! 👋 Here's a quick look at the quality of CVs Wakanda Jobs puts together for candidates — "
            . "this sample is written for a *{$role}* role using AI-generated example content, just to show you the "
            . 'standard you can expect. Sending our CV designs now as PDFs.';

        $errorMessage = null;

        if (! $this->sendWhapiMessage($whatsappNumber, $intro, $errorMessage)) {
            return [false, $errorMessage];
        }

        foreach ($paths as $row) {
            $sent = $this->sendWhapiDocument(
                $whatsappNumber,
                Storage::disk('local')->path($row['pdf_path']),
                $row['filename'],
                $errorMessage
            );

            if (! $sent) {
                return [false, $errorMessage];
            }
        }

        return [true, null];
    }

    private function sampleCvDocumentPaths(string $role): ?array
    {
        $roleSlug = Str::slug($role) ?: 'sample';
        $settingKey = "auto_cv_bot_sample_paths_{$roleSlug}";
        $cached = json_decode((string) setting($settingKey, ''), true);

        if (is_array($cached) && $this->sampleDocumentsExist($cached)) {
            return $cached;
        }

        $cv = $this->generateSampleCv($role);

        if (! $cv) {
            return null;
        }

        $paths = $this->storeSampleDocuments($cv, $roleSlug, $role);

        setting()->set($settingKey, json_encode($paths))->save();

        return $paths;
    }

    private function sampleDocumentsExist(array $paths): bool
    {
        if ($paths === []) {
            return false;
        }

        foreach ($paths as $row) {
            if (empty($row['pdf_path']) || ! Storage::disk('local')->exists($row['pdf_path'])) {
                return false;
            }
        }

        return true;
    }

    private function generateSampleCv(string $role): ?array
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            return null;
        }

        $systemPrompt = <<<PROMPT
You are a senior CV writer for Wakanda Jobs (Zambia). Write a complete, impressive, ENTIRELY FICTITIOUS sample
CV for a strong "{$role}" candidate. This is only ever used to demonstrate CV design quality to prospective
candidates — it must never be mistaken for a real person, so invent a believable Zambian name and use clearly
placeholder contact details (email like "sample.cv@wakandajobs.com", phone like "+260 9XX XXX XXX"). Invent
2-3 work experience entries with strong, specific achievements and metrics appropriate for the role. Write in
polished, professional British English.

Education and certifications must be detailed and substantial, since this sample exists to show off depth as
well as design:
- "education": 3 entries, oldest last, covering a believable full academic path for this role — e.g. a
  postgraduate qualification (Master's or professional postgraduate diploma), an undergraduate Bachelor's
  degree, and secondary/high school (Grade 12 / GCE) — each with a real-sounding Zambian or regional institution,
  specific field of study, and start/end years.
- "certifications": 4-6 entries, each a professional body certification or specialised training genuinely
  relevant to a "{$role}" role (e.g. recognised professional accounting/finance designations, software/ERP
  certifications, short executive courses), each with the issuing body and a year.

Return ONLY JSON shaped exactly like this (omit nothing, use empty arrays/strings where genuinely not
applicable, leave "references" empty):
{
  "structured_cv": {
    "full_name": "",
    "headline": "",
    "phone": "",
    "email": "",
    "address": "",
    "location": "",
    "age": "",
    "marital_status": "",
    "linkedin": "",
    "summary": "",
    "skills": ["..."],
    "certifications": [{"name": "", "issuing_body": "", "date": ""}],
    "languages": [{"language": "", "proficiency": ""}],
    "experience": [{"job_title": "", "company": "", "location": "", "start_date": "", "end_date": "", "responsibilities": ["..."]}],
    "education": [{"qualification": "", "field": "", "institution": "", "start_year": "", "end_year": ""}],
    "projects": [{"name": "", "description": ""}],
    "references": []
  }
}
PROMPT;

        try {
            $response = Http::timeout(90)->withToken($apiKey)->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::aiModel(),
                    'temperature' => 0.6,
                    'max_tokens' => 2200,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => 'Generate the sample CV now.'],
                    ],
                ]);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $decoded = $this->decodeAiJson($response);

        if (! is_array($decoded) || ! is_array($decoded['structured_cv'] ?? null)) {
            return null;
        }

        $fakeSession = new AutoCvSession([
            'candidate_name' => $decoded['structured_cv']['full_name'] ?? $role,
            'whatsapp_number' => '',
        ]);

        return $this->sanitizeCv($decoded['structured_cv'], $fakeSession);
    }

    private function storeSampleDocuments(array $cv, string $roleSlug, string $role): array
    {
        $baseDir = "auto-cv-bot/samples/{$roleSlug}";
        $safeRole = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $role), '_') ?: 'Sample';
        $styles = [
            'premium' => 'Premium',
            'academic' => 'Academic',
            'creative' => 'Creative',
            'ats' => 'ATS',
        ];

        // References always read "Available on request" in rendered documents, same as real CVs.
        $renderCv = $cv;
        $renderCv['references'] = [['name' => 'Available on request', 'role' => '', 'company' => '', 'phone' => '', 'email' => '']];

        $paths = [];

        foreach ($styles as $style => $label) {
            $pdfPath = "{$baseDir}/Sample_{$safeRole}_CV_{$label}.pdf";

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
                'pdf_path' => $pdfPath,
                'filename' => "Sample_{$safeRole}_CV_{$label}.pdf",
            ];
        }

        return $paths;
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
            ? $this->generateReopenAfterCompletionMessage($session)
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
            // Reopening means the CV will be regenerated and must reach the candidate again,
            // so the "already sent" guard in finalizeSession() has to be cleared here too.
            'sent_to_candidate_at' => null,
            'candidate_reminder_count' => 0,
            'last_candidate_reminder_sent_at' => null,
            'awaiting_final_confirmation' => $isCompleted,
            // Reopening after completion means we're waiting on real new content (not just a
            // "Done" confirmation), so this session gets the longer 30-minute grace period —
            // see completeTimedOutConfirmations().
            'reopened_for_missing_detail' => $isCompleted,
            'reopen_warning_sent_at' => null,
            'error_message' => null,
            'error_trace' => null,
        ])->save();

        return $this->resendCurrentQuestion($session->fresh());
    }

    /**
     * Reopening a session that was already completed and sent means something was
     * missed — generate a fresh, AI-phrased message each time rather than resending the
     * generic "please confirm" text, so it reads as the assistant naturally picking the
     * conversation back up, not a robotic reset.
     */
    private function generateReopenAfterCompletionMessage(AutoCvSession $session): string
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            return $this->fallbackReopenAfterCompletionMessage($session);
        }

        $personaName = self::PERSONA_NAME;

        $systemPrompt = <<<PROMPT
You are writing a WhatsApp follow-up message on behalf of Wakanda Jobs. You already finished this candidate's CV and sent it to them, but something was left out, so the conversation is being reopened to add it.

Your name is {$personaName}, Wakanda Jobs' AI assistant — say plainly you are an AI, never the word "bot". Some time has likely passed since you last spoke, so briefly reintroduce yourself in your own natural words — vary the wording every time, never reuse the exact same sentence twice.

Real WhatsApp conversations read as several short separate messages, not one paragraph. Write 2-4 SHORT separate messages, each one short sentence, with a blank line between every one of them, that together:
- Briefly reintroduce yourself.
- Let the candidate know you're aware (or were told) that something didn't make it into their CV.
- Ask them, warmly and simply, what that missing detail is.
- Stay calm, warm, and natural — never robotic, corporate, or like a form.

Rules:
- Never use the word "bot".
- Never describe what you'll do with their answer internally (no "I'll add that", "I'll update it", "I'll process it") — just ask, nothing about what happens afterwards.
- Output ONLY the message text, with each short message separated by a blank line — no preamble, no quotes, no markdown fences, no numbering.
PROMPT;

        try {
            $response = Http::timeout(30)->withToken($apiKey)->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::aiModel(),
                    'temperature' => 0.9,
                    'max_tokens' => 240,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => 'Candidate name: ' . ($session->candidate_name ?: '(not given)')],
                    ],
                ]);
        } catch (Throwable) {
            return $this->fallbackReopenAfterCompletionMessage($session);
        }

        if (! $response->successful()) {
            return $this->fallbackReopenAfterCompletionMessage($session);
        }

        $text = trim((string) $response->json('choices.0.message.content', ''));
        $text = trim((string) preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text));

        if ($text === '') {
            return $this->fallbackReopenAfterCompletionMessage($session);
        }

        $this->recordAiUsage($session, $response);

        return $text;
    }

    private function fallbackReopenAfterCompletionMessage(AutoCvSession $session): string
    {
        $name = trim((string) explode(' ', (string) $session->candidate_name)[0]);

        return 'Hi' . ($name ? " {$name}" : '') . "! It's " . self::PERSONA_NAME . " again, the AI assistant from Wakanda Jobs.\n\n"
            . "We noticed something didn't quite make it into your CV.\n\n"
            . 'What would you like to add?';
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

    /**
     * Sends $body as several separate WhatsApp messages instead of one block, the way a person
     * naturally sends a few short texts in a row — every AI-generated message in this file is
     * written with blank lines between distinct thoughts specifically so it splits well here.
     */
    private function sendWhapiMessage(string $whatsappNumber, string $body, ?string &$errorMessage = null): bool
    {
        [$token, $gatewayUrl] = $this->getWhapiCredentials();

        if (! $token) {
            $errorMessage = 'No active Whapi automation configured.';

            return false;
        }

        $jid = preg_replace('/\D/', '', $whatsappNumber) . '@s.whatsapp.net';

        foreach ($this->splitIntoChatBubbles($body) as $index => $bubble) {
            if ($index > 0) {
                usleep(self::CHAT_BUBBLE_DELAY_MICROSECONDS);
            }

            try {
                $response = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                    'to' => $jid,
                    'body' => $bubble,
                ]);
            } catch (Throwable $exception) {
                $errorMessage = 'WhatsApp send exception: ' . $exception->getMessage();

                return false;
            }

            if (! $response->successful()) {
                $errorMessage = 'WhatsApp send failed: HTTP ' . $response->status() . ' ' . Str::limit($response->body(), 250, '');

                return false;
            }
        }

        return true;
    }

    /** Splits a message on blank lines into separate WhatsApp-bubble-sized chunks. */
    private function splitIntoChatBubbles(string $body): array
    {
        $bubbles = collect(preg_split('/\n{2,}/', trim($body)) ?: [])
            ->map(fn (string $bubble) => trim($bubble))
            ->filter(fn (string $bubble) => $bubble !== '')
            ->values()
            ->all();

        return $bubbles !== [] ? $bubbles : [$body];
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
            $lowerMime = strtolower($mime);
            $lowerFilename = strtolower($filename);

            $text = match (true) {
                str_contains($lowerMime, 'pdf') || str_ends_with($lowerFilename, '.pdf') => $this->extractPdfText($session, $path),
                str_contains($lowerMime, 'wordprocessingml') || str_contains($lowerMime, 'msword')
                    || str_ends_with($lowerFilename, '.docx') || str_ends_with($lowerFilename, '.doc')
                    => $this->extractDocxText($path),
                default => $this->extractImageText($session, $path, $mime),
            };

            if ($text !== '') {
                $parts[] = "Attachment text:\n{$text}";
            } else {
                Log::warning('AutoCvBot: attachment downloaded but no text could be extracted from it', [
                    'session_id' => $session->id,
                    'whapi_message_id' => $whapiMessageId,
                    'filename' => $filename,
                    'mime' => $mime,
                ]);
            }
        } else {
            Log::warning('AutoCvBot: attachment text extraction failed — could not download the file', [
                'session_id' => $session->id,
                'whapi_message_id' => $whapiMessageId,
                'filename' => $filename,
                'mime' => $mime,
            ]);
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
            Log::warning('AutoCvBot: attachment had no media URL or media ID to download', [
                'session_id' => $session->id,
                'whapi_message_id' => $whapiMessageId,
                'filename' => $filename,
            ]);

            return [null, $filename, $mime];
        }

        [$token, $gatewayUrl] = $this->getWhapiCredentials();

        if (! $url && ! $gatewayUrl) {
            Log::warning('AutoCvBot: no Whapi gateway URL configured to resolve a media ID', [
                'session_id' => $session->id,
                'whapi_message_id' => $whapiMessageId,
            ]);

            return [null, $filename, $mime];
        }

        // Whatsapp/Whapi sometimes fires the inbound webhook a moment before the media has
        // fully propagated to their CDN — the very first download attempt can 404/error even
        // though the same request succeeds a couple of seconds later, so retry once before
        // giving up.
        $attempts = 2;
        $response = null;
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            if ($attempt > 1) {
                sleep(3);
            }

            try {
                $request = Http::timeout(60);

                if ($token) {
                    $request = $request->withToken($token);
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
            } catch (Throwable $exception) {
                $lastException = $exception;
                $response = null;

                continue;
            }

            if ($response->successful() && $response->body() !== '') {
                break;
            }
        }

        if ($lastException && (! $response || ! $response->successful())) {
            Log::warning('AutoCvBot: exception downloading attachment from Whapi', [
                'session_id' => $session->id,
                'whapi_message_id' => $whapiMessageId,
                'attempts' => $attempts,
                'error' => $lastException->getMessage(),
            ]);

            return [null, $filename, $mime];
        }

        if (! $response || ! $response->successful() || $response->body() === '') {
            Log::warning('AutoCvBot: attachment download failed or returned empty body', [
                'session_id' => $session->id,
                'whapi_message_id' => $whapiMessageId,
                'attempts' => $attempts,
                'status' => $response?->status(),
                'body_excerpt' => Str::limit((string) $response?->body(), 250, ''),
            ]);

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

    private function extractDocxText(string $path): string
    {
        $zip = new ZipArchive();
        $openResult = $zip->open($path);

        if ($openResult !== true) {
            Log::warning('AutoCvBot: failed to open .docx as a zip archive', [
                'path' => $path,
                'zip_error_code' => $openResult,
            ]);

            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! is_string($xml) || $xml === '') {
            Log::warning('AutoCvBot: .docx had no readable word/document.xml', ['path' => $path]);

            return '';
        }

        $xml = str_replace(['</w:p>', '</w:tr>'], "\n", $xml);
        $xml = str_replace('<w:tab/>', "\t", $xml);
        $text = trim((string) preg_replace('/<[^>]+>/', '', $xml));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1);

        return trim((string) preg_replace('/\n{3,}/', "\n\n", $text));
    }

    private function extractPdfText(AutoCvSession $session, string $path): string
    {
        $text = $this->extractPdfTextLayer($path);

        // Most certificates candidates send are scanned photos saved as PDF, with no real
        // text layer — pdftotext returns empty or near-empty in that case. Fall back to
        // rasterising the first page and reading it the same way we read photos (OCR via
        // OpenAI vision) before giving up.
        if (mb_strlen(trim($text)) >= 20) {
            return $text;
        }

        return $this->extractScannedPdfTextViaVision($session, $path);
    }

    private function extractPdfTextLayer(string $path): string
    {
        if (! is_file($path)) {
            Log::warning('AutoCvBot: pdftotext input file missing', ['path' => $path]);

            return '';
        }

        $binary = '/usr/bin/pdftotext';

        if (! is_executable($binary)) {
            Log::error('AutoCvBot: pdftotext binary not found or not executable', ['binary' => $binary]);

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
            Log::error('AutoCvBot: failed to start pdftotext process', ['path' => $path]);

            @unlink($outputPath);

            return '';
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $text = $exitCode === 0 && is_file($outputPath)
            ? trim((string) file_get_contents($outputPath))
            : '';

        if ($exitCode !== 0) {
            Log::warning('AutoCvBot: pdftotext exited with a non-zero status', [
                'path' => $path,
                'exit_code' => $exitCode,
                'stderr' => Str::limit((string) $stderr, 500, ''),
            ]);
        }

        @unlink($outputPath);

        return Str::limit($text, 6000, '');
    }

    private function extractScannedPdfTextViaVision(AutoCvSession $session, string $path): string
    {
        if (! is_file($path)) {
            return '';
        }

        $binary = '/usr/bin/pdftoppm';

        if (! is_executable($binary)) {
            Log::error('AutoCvBot: pdftoppm binary not found or not executable', ['binary' => $binary]);

            return '';
        }

        $imagePrefix = tempnam(sys_get_temp_dir(), 'auto-cv-pdf-page-');

        if (! $imagePrefix) {
            return '';
        }

        // tempnam() creates an empty placeholder file; pdftoppm -singlefile writes its own
        // "<prefix>.png" next to it, so the placeholder must be removed first.
        @unlink($imagePrefix);
        $imagePath = $imagePrefix . '.png';

        $process = proc_open(
            [$binary, '-singlefile', '-png', '-r', '150', '-f', '1', $path, $imagePrefix],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (! is_resource($process)) {
            Log::error('AutoCvBot: failed to start pdftoppm process', ['path' => $path]);

            return '';
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || ! is_file($imagePath)) {
            Log::warning('AutoCvBot: pdftoppm failed to rasterise scanned PDF page', [
                'path' => $path,
                'exit_code' => $exitCode,
                'stderr' => Str::limit((string) $stderr, 500, ''),
            ]);

            @unlink($imagePath);

            return '';
        }

        $text = $this->extractImageText($session, $imagePath, 'image/png');

        @unlink($imagePath);

        return $text;
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
            'model' => self::aiModel(),
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
                            'text' => 'Read this image and return {"text":"..."} with only useful CV details: certificate/licence/training name, issuing body, dates, licence numbers, expiry dates, qualification names, and any relevant names. "text" must be a single plain string with each detail on its own line (e.g. "Certificate: ...\nIssued by: ...\nDate: ..."), never a nested JSON object. If unreadable, return {"text":""}.',
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
        } catch (Throwable $exception) {
            Log::warning('AutoCvBot: exception calling OpenAI vision for image text extraction', [
                'session_id' => $session->id,
                'error' => $exception->getMessage(),
            ]);

            return '';
        }

        if (! $response->successful()) {
            Log::warning('AutoCvBot: OpenAI vision request failed for image text extraction', [
                'session_id' => $session->id,
                'status' => $response->status(),
                'body_excerpt' => Str::limit($response->body(), 250, ''),
            ]);

            return '';
        }

        $this->recordAiUsage($session, $response);

        $decoded = json_decode((string) $response->json('choices.0.message.content', ''), true);

        if (! is_array($decoded)) {
            Log::warning('AutoCvBot: OpenAI vision response was not valid JSON', [
                'session_id' => $session->id,
                'content_excerpt' => Str::limit((string) $response->json('choices.0.message.content', ''), 250, ''),
            ]);

            return '';
        }

        return Str::limit(trim($this->flattenExtractedText($decoded['text'] ?? '')), 6000, '');
    }

    /**
     * The model is told to return "text" as a single plain string, but vision models
     * sometimes structure the details into a nested object anyway — flatten that into
     * readable lines instead of letting it cast to the literal string "Array".
     */
    private function flattenExtractedText(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return '';
        }

        return collect($value)
            ->map(function ($v, $k) {
                $label = is_string($k) ? Str::headline($k) : null;
                $val = is_array($v) ? $this->flattenExtractedText($v) : (string) $v;

                return $label ? "{$label}: {$val}" : $val;
            })
            ->filter(fn (string $line) => trim($line) !== '')
            ->implode("\n");
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

    private function sendWhapiImage(string $whatsappNumber, string $imagePath, string $caption, ?string &$errorMessage = null): bool
    {
        [$token, $gatewayUrl] = $this->getWhapiCredentials();

        if (! $token) {
            $errorMessage = 'No active Whapi automation configured.';

            return false;
        }

        if (! is_file($imagePath)) {
            $errorMessage = 'Assistant image not found.';

            return false;
        }

        $jid = preg_replace('/\D/', '', $whatsappNumber) . '@s.whatsapp.net';
        $mime = mime_content_type($imagePath) ?: 'image/jpeg';

        // Only the first bubble goes out as the image caption; the rest follow as separate text
        // messages right after, same chat rhythm as a caption-less message split by sendWhapiMessage.
        $bubbles = $this->splitIntoChatBubbles($caption);
        $firstBubble = array_shift($bubbles) ?? '';

        try {
            $response = Http::timeout(30)->withToken($token)->post("{$gatewayUrl}/messages/image", [
                'to' => $jid,
                'media' => 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($imagePath)),
                'caption' => $firstBubble,
            ]);
        } catch (Throwable $exception) {
            $errorMessage = 'WhatsApp image send exception: ' . $exception->getMessage();

            return false;
        }

        if (! $response->successful()) {
            $errorMessage = 'WhatsApp image send failed: HTTP ' . $response->status() . ' ' . Str::limit($response->body(), 250, '');

            return false;
        }

        if ($bubbles === []) {
            return true;
        }

        usleep(self::CHAT_BUBBLE_DELAY_MICROSECONDS);

        return $this->sendWhapiMessage($whatsappNumber, implode("\n\n", $bubbles), $errorMessage);
    }

    /** Local filesystem path to the configured persona image, downloading it first if media is on cloud storage. */
    /** Local filesystem path to the image stored under the given setting key, downloading it first if media is on cloud storage. */
    private function settingImageLocalPath(string $settingKey): ?string
    {
        $url = trim((string) setting($settingKey, ''));

        if ($url === '') {
            return null;
        }

        if (! RvMedia::isUsingCloud()) {
            $path = RvMedia::getRealPath($url);

            return is_file($path) ? $path : null;
        }

        $contents = @file_get_contents(RvMedia::getImageUrl($url));

        if ($contents === false) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'cvbot_img_') . '.' . (pathinfo($url, PATHINFO_EXTENSION) ?: 'jpg');
        file_put_contents($tempPath, $contents);

        return $tempPath;
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

You are given the "Structured CV captured so far" JSON below, built from everything the candidate has told you in earlier turns. Treat every value already present in it as locked in and already confirmed — never blank out, drop, or omit a value that's already there just because the latest reply doesn't restate it, and never ask the candidate again about a specific field of a specific entry (e.g. "field of study" for a named school, or "end year" for a named certificate) once that field already has any value below, even a short, vague, or informal one (e.g. "Studies"). This applies just as much to top-level fields (full name, phone, email, age, marital status, headline, summary) as it does to entries within list-type topics — once any of these has a value below, or its topic already scores 90+ under rule 9, do not ask about it again. A vague existing value is still a captured answer, not a gap — only ask about it again if the candidate's own latest reply raises or corrects it. Only add to or correct this JSON using new information from the latest reply; everything else carries forward unchanged.

Given the full conversation so far and the structured CV captured so far, you must:
1. Build the ENTIRE structured CV JSON by starting from the "Structured CV captured so far" JSON given below and folding in anything new from the candidate's latest reply — do not invent employers, schools, qualifications, dates, references, phone numbers, emails, addresses, ages, or marital status. Use empty strings/arrays only for things genuinely never provided in either the captured JSON or the conversation. Marital status and age are sensitive personal details — only fill them in if the candidate actually states them; if they decline or skip, accept that and score the bio topic on the rest of the contact details instead.
2. Decide which topic numbers (1-12, matching the list above) are not yet adequately covered, and return them as "missing_topics". A topic only counts as "covered" once its own score under rule 9 would be 90 or above (a "green" score) — never remove a topic from "missing_topics" just because you asked about it, or because the candidate gave a vague or partial answer. Keep asking follow-up questions about a topic, one at a time, until you can honestly score it 90+.
3. Read the candidate's latest reply carefully before deciding what to do next:
   - If it is a real, substantive answer (even if informal or incomplete), accept it, extract what you can, and move on per rule 5.
   - If it is a confusion signal, a non-answer, OR a clarification request — e.g. "I don't understand", "huh?", "what do you mean", "skip", "kindly clarify", "can you explain that a bit more", "not sure what you mean by [topic]", "could you give an example", a one-word reply that doesn't address what was asked, an emoji, or anything unrelated — do NOT just reword or resend the same question again, and NEVER reply with text that is the same or almost the same as the question you already asked. A polite request to clarify (even one that names the topic, like "kindly clarify a bit on the projects") still counts here — it is asking you to explain differently, not confirming they have nothing to add. (Exception: a one-word decline like "no"/"none"/"nothing" given in reply to a yes/no "is there anything else to add?" confirmation question IS a real, complete answer — see rule 5 — not a confusion signal.) Instead, explain the SAME topic in the simplest possible everyday language, break it into one tiny piece at a time, and give a short concrete example of the kind of answer you want — make up your own example that fits the ACTUAL topic currently being clarified, never reuse an example written for a different topic. For instance, if the topic being clarified is education, you might say something like "No problem! For example, you could just say: 'I studied at Kitwe Technical College, Diploma in IT, 2019 to 2021.' What school or college did you go to, and what did you study?" — but if the topic is projects, your example must be about a project (e.g. "No problem! For example, you could just say: 'I built a small poultry-feeding business plan for a school project.' Have you done anything like that?"), not about education or anything unrelated. Keep the topic and "missing_topics" unchanged in this case — you are still waiting for the same topic to be answered, not moving to a new question.
   - Never present a simplified/example explanation as if it were a brand-new question — the candidate should clearly feel you are patiently re-explaining the same thing, not asking something different.
   - If the candidate gives only an abbreviation or acronym for the name of an institution, employer, or certifying body (e.g. "CBU", "UNZA", "NIPA", "BGS"), do not accept it and move straight to asking for other details (such as dates or job title) about that same entry, and do not silently expand it yourself even if you are confident you recognise it. Ask them directly to confirm or spell out the full name, by itself, before asking for any other details about that same entry — unless they explicitly say they don't know the full name or that there isn't a longer version, in which case accept the abbreviation as given and continue with the remaining details. Only put a full institution/employer name in structured_cv once the candidate has actually confirmed or typed it themselves. If the candidate names SEVERAL abbreviated institutions/employers in the same reply (e.g. "I worked at BGS and CBU"), ask them to confirm or spell out the full name for ALL of them together in that one clarifying message — and that message must contain ONLY the request for full names, nothing else. Do not combine it with a request for job titles, dates, or responsibilities; ask those only in a later message, once the full names are confirmed.
4. Topic 2 (bio and contact details) bundles several distinct pieces of information — mobile number, email address, residential address/town or city, age, marital status, and LinkedIn URL. NEVER ask for more than one of these in the same message, even though they all belong to the same topic — candidates skip or forget fields when asked for several things at once. WRONG example, never do this: "could you please provide your mobile number, email address, residential address, town or city, age, marital status, and LinkedIn profile" — that is six fields in one message. Ask for them ONE AT A TIME, in plain, friendly, everyday language rather than form-like wording (e.g. "What's the best mobile number to reach you on?" rather than "Please provide your mobile number"; "Which town or city do you live in?" rather than "residential address"). Ask in a sensible order: mobile number first (it's essential for contacting them), then email, then town/city, then age, then marital status, then LinkedIn last as a casual optional ask (e.g. "Do you have a LinkedIn profile? No worries if not, we can skip that one."). Only move to the next field once the candidate has answered or clearly declined the current one — never bundle two unanswered fields into a single follow-up. Topic 2 only counts as covered once every field has been asked about and either answered or explicitly declined. A decline of any one field (e.g. "I don't have an email yet", "no LinkedIn", "I'd rather not say my age") must be treated exactly like a rule 5 "don't know" — accept it immediately, store that field as an empty string, treat it as fully resolved, and never ask about that specific field again for the rest of the conversation, no matter how many turns later or how differently worded the question is. This has gone wrong in production: a candidate explicitly said "I don't have any email address yet" and was still asked "Could you please share your email address when you have one?" a turn later and again the next day — that is exactly what this rule forbids.
5. Topics 5 (education), 6 (work experience), 7 (internships/volunteer/projects), 9 (certificates/awards), and 11 (references) can have more than one entry:
   - For each entry the candidate mentions, make sure you collect every key field before treating that entry as done: for work experience — full employer name, job title, start and end dates (or "present"), and what they did; for education — full institution name, qualification, field of study, and start/end years; for projects, certifications, and references — the equivalent key details (e.g. dates for projects, issuing body and year for certificates). If the candidate has given some but not all of these for an entry, ask specifically for the missing piece(s) next — do not skip ahead to a different entry or a different topic while details are still missing for the current one. Never name more than one entry (e.g. two different schools or two different employers) in the same question, even if they're missing the exact same field — ask about one entry at a time, fully, before moving to the next.
   - If the candidate says they don't know, can't remember, or aren't sure about ONE specific missing piece (e.g. the exact dates of a job or the exact year a certificate was issued) — whether that's their very first reply to that question or after you've already reassured them once that it's okay not to know — accept that immediately as the final answer for that piece. Store it as an empty string in structured_cv (or "Present"/"Not specified" if that fits better), treat that piece as resolved, not missing, and move straight on to the next missing piece or the next entry, or close out the topic per the rule below if nothing else is missing. Do NOT ask that same specific piece of that same entry again afterwards, even in gentler, rephrased, or "just let me know if you're not sure" wording — once a candidate has said they don't know something, asking again (in any phrasing) reads as not listening, not as patience. When scoring this entry's topic under rule 9, treat a piece resolved this way as answered, not missing — do not let a "don't know" piece hold a topic's score below 90 once every other piece of every entry is genuinely known.
   - Once you believe every entry the candidate has mentioned so far for that topic is fully detailed, do NOT immediately mark the topic as covered or move to a different topic. First ask a short confirmation question such as "Is that all your work experience, or is there anything else to add?" Only remove the topic from "missing_topics" once the candidate gives a clear answer confirming there is nothing more — this includes short/one-word declines such as "no", "none", "nothing", "nope", "no more", "that's all", "I'm done", or the same word repeated/emphasised (e.g. "NO!", "nothing else"). Treat ANY one-word or short decline reply to this specific confirmation question as a valid "nothing more" answer, not as confusion under rule 3 — rule 3's "non-answer" handling is for when the candidate doesn't address what was asked at all, not for a plain no/none/nothing reply to a yes/no confirmation question. If they mention another entry instead of declining, treat it the same way (collect its full details, then ask again if that's everything).
6. Once a topic has a real, complete answer (and, for the list-type topics above, once the candidate has confirmed there is nothing more to add), compose the single next WhatsApp message: a friendly, concise question about ONE topic — and, per rule 4, at most ONE field within that topic — that still needs covering. Never ask about more than one topic, or more than one field of topic 2, at a time. Prefer asking about a topic you haven't touched at all yet; but once every topic (1-12) has been asked about at least once, go back and revisit whichever topic currently has the LOWEST score with a clarifying follow-up — keep cycling back through the weak topics, one at a time, asking for more detail or clarity, until each one reaches 90+. Never skip a topic permanently just because the candidate's first answer to it was thin. Hard constraint: never compose next_message about a topic that already scores 90+ under rule 9 in this very response, and never repeat a question about a specific field or piece the candidate has already explicitly said they don't know per rule 5 — both are fully resolved, not pending, no matter how far back in the conversation they were settled. Many candidates have not been to college and some may be applying for senior roles despite that — always use simple, everyday words a primary-school reading level can follow, never jargon or "big" words (say "level" or "how well", not "proficiency"; say "skills, tools, or things you're good at", not "competencies"; say "your last job", not "most recent employment"). If a CV term has no simple everyday equivalent, briefly explain it in plain words the first time you use it.
7. Once every topic (1-12) would score 90 or above under rule 9, set "is_complete" to true and make "next_message" a short, friendly closing line thanking the candidate and saying their CV is being prepared. If even one topic is still below 90, keep "is_complete" false and keep working through rule 6 until it isn't.
8. Rewrite informal answers into professional CV language when filling structured_cv. Use British English spelling. For "responsibilities" in work experience, write each as its own short bullet starting with a strong action verb (e.g. Led, Built, Reduced, Delivered, Automated, Designed, Managed) and include a number, percentage, time saved, or team size whenever the candidate's answer makes one available — never write generic duties-only phrasing like "Responsible for...". Use past tense for previous roles and present tense only for a role the candidate is still currently in.
9. For EVERY topic number 1-12, return a "section_scores" entry keyed by that number, with a "label" (short name of the topic), a "score" from 0-100 rating how complete and useful the information given so far is for that topic (0 = nothing provided, 100 = clear and complete), and "improve" — a short, specific, actionable tip for what would make that section stronger (empty string if score is 90 or above). Score honestly based on what is actually known, not on intentions. IMPORTANT exception: if the candidate has clearly and directly stated they have NONE for a topic where that is a normal, valid answer (e.g. "no work experience, I'm a fresh graduate", "no certificates", "none" for internships/projects), that is a complete answer, not a missing one — score it 90-100, leave the corresponding structured_cv array empty, and move on. Do not keep asking about a topic the candidate has already clearly said does not apply to them. Same rule for a "don't know" piece resolved per rule 5: once every field of every entry in a topic is either a real value or explicitly resolved as "don't know", score that topic 90-100 and set "improve" to an empty string — never leave "improve" suggesting you still need the very piece the candidate already said they don't know. Hard constraint: never score a topic 90+ if you have never actually asked the candidate about it and they have never volunteered anything for it — a topic with zero turns spent on it and an empty structured_cv entry must score low (well under 50), no matter how many other topics are already done or how long the interview has run. Languages (topic 10) in particular has no valid "none" answer — everyone speaks at least one language — so it must never score 90+ until at least one language has actually been captured. Topic 4 (personal profile) is inherently soft and subjective, unlike a date or an employer name — a short list of 2-3 genuine traits or strengths in the candidate's own words (e.g. "hardworking, trustworthy, eager to learn") is already a complete answer, score it 90+ immediately. Do not ask "tell me more about your strengths" a second time once any concrete traits have been given — repeating that request when the candidate has already answered reads as not listening, and a short, honest list is a normal, finished answer for this topic, not a thin one.
10. Based on everything known so far in structured_cv (skills, education, experience — even if still incomplete), suggest 3-5 realistic job positions in Zambia this candidate could apply for right now, as "suggested_job_positions": an array of {"title": "", "reason": ""} — "reason" is one short sentence on why it fits. Update this list every turn as more information comes in. If there isn't enough information yet to suggest anything sensible, return an empty array.
11. If the candidate's latest reply signals tiredness, frustration, or impatience with the process itself (e.g. "I'm tired of repeating myself", "have you finished?", "are we done yet", "this is taking forever", "I already told you this") rather than just answering normally, stop drilling for more detail. Apologise briefly and warmly in next_message, accept whatever has already been given for every topic as final (filling genuinely unanswered pieces with "Not specified" rather than asking again), and move things toward wrapping up — do not ask another detailed follow-up question in the same next_message you apologise in.

Formatting "next_message": real WhatsApp conversations read as several short separate messages, not long paragraphs. If next_message naturally contains more than one distinct thought (e.g. an acknowledgement plus a question, or an explanation plus an example plus a question), write each distinct thought as its own short message and separate them with a blank line — the same way a real person sends a few short texts in a row rather than one long paragraph. Never describe what you will do with their information internally — no "I'll note that down", "I'll add that to your CV", "I'll extract...", "I'll process..." or similar; just acknowledge naturally and move on.

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
            . "Structured CV captured so far (treat as already confirmed — do not lose or re-ask about anything already filled in here):\n"
            . json_encode($session->structured_cv ?: [], JSON_UNESCAPED_SLASHES) . "\n\n"
            . "Conversation so far:\n" . $this->buildTranscript($session);

        return [
            'model' => self::aiModel(),
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

    /** Catches the model echoing the exact same question back instead of clarifying. */
    private function isRepeatOfLastQuestion(string $message, ?string $lastQuestion): bool
    {
        if (! $lastQuestion) {
            return false;
        }

        $normalizedMessage = $this->normalizeQuestionText($message);

        return $normalizedMessage !== '' && $normalizedMessage === $this->normalizeQuestionText($lastQuestion);
    }

    private function normalizeQuestionText(string $text): string
    {
        return trim((string) preg_replace('/[^a-z0-9 ]/', '', strtolower($text)));
    }

    /**
     * isRepeatOfLastQuestion() only catches an immediate echo of the single most recent question.
     * Seen in production: the same question gets asked again several turns later (after other
     * questions in between, each time with a real answer already given) — a genuine stuck loop
     * that the immediate-repeat check can't see because last_question_text has since moved on.
     * Counts how many times this exact question has already been sent anywhere in this session.
     */
    private function timesQuestionAlreadyAsked(AutoCvSession $session, string $message): int
    {
        $normalizedMessage = $this->normalizeQuestionText($message);

        if ($normalizedMessage === '') {
            return 0;
        }

        return collect($session->answers ?: [])
            ->filter(fn (array $turn) => $this->normalizeQuestionText((string) ($turn['question_sent'] ?? '')) === $normalizedMessage)
            ->count();
    }

    private function finalConfirmationMessage(AutoCvSession $session, bool $acknowledgeUpdate = false): string
    {
        $prefix = $acknowledgeUpdate ? "Thanks, I've added that to your CV.\n\n" : '';
        $summary = $this->buildCvReviewSummary((array) ($session->structured_cv ?? []));

        return trim($prefix
            . ($summary !== '' ? $summary . "\n\n" : '')
            . "Before I prepare your CV, please confirm that you have provided everything you want included.\n\n"
            . "Reply DONE if everything is complete, or send any extra details you still want added.");
    }

    /** A short, readable recap of what's been captured so far, so the candidate can spot anything missing before confirming. */
    private function buildCvReviewSummary(array $cv): string
    {
        $lines = [];

        $nameLine = trim(implode(' — ', array_filter([$cv['full_name'] ?? '', $cv['headline'] ?? ''])));
        if ($nameLine !== '') {
            $lines[] = $nameLine;
        }

        $contact = implode(' · ', array_filter([$cv['phone'] ?? '', $cv['email'] ?? '', $cv['location'] ?? '']));
        if ($contact !== '') {
            $lines[] = $contact;
        }

        $education = collect($cv['education'] ?? [])
            ->map(fn ($row) => trim(implode(' — ', array_filter([
                trim(implode(' ', array_filter([$row['qualification'] ?? '', $row['field'] ?? '']))),
                $row['institution'] ?? '',
            ]))))
            ->filter()
            ->implode('; ');
        if ($education !== '') {
            $lines[] = "Education: {$education}";
        }

        $certifications = collect($cv['certifications'] ?? [])->filter()->implode('; ');
        if ($certifications !== '') {
            $lines[] = "Certificates: {$certifications}";
        }

        $experience = collect($cv['experience'] ?? [])
            ->map(fn ($row) => trim(implode(' at ', array_filter([$row['job_title'] ?? '', $row['company'] ?? '']))))
            ->filter()
            ->implode('; ');
        if ($experience !== '') {
            $lines[] = "Work experience: {$experience}";
        }

        $projects = collect($cv['projects'] ?? [])->map(fn ($row) => $row['name'] ?? '')->filter()->implode('; ');
        if ($projects !== '') {
            $lines[] = "Projects/volunteer work: {$projects}";
        }

        $skills = collect($cv['skills'] ?? [])->filter()->implode(', ');
        if ($skills !== '') {
            $lines[] = "Skills: {$skills}";
        }

        $languages = collect($cv['languages'] ?? [])
            ->map(fn ($row) => trim(implode(' ', array_filter([$row['language'] ?? '', ($row['proficiency'] ?? '') !== '' ? '(' . $row['proficiency'] . ')' : '']))))
            ->filter()
            ->implode(', ');
        if ($languages !== '') {
            $lines[] = "Languages: {$languages}";
        }

        $references = collect($cv['references'] ?? [])
            ->map(fn ($row) => is_array($row)
                ? implode(' - ', array_filter([$row['name'] ?? '', $row['role'] ?? '', $row['company'] ?? '', $row['phone'] ?? '', $row['email'] ?? '']))
                : trim((string) $row))
            ->filter()
            ->implode('; ');
        $lines[] = 'References: ' . ($references !== '' ? $references : 'Available on request');

        if ($lines === []) {
            return '';
        }

        return "Here's a quick look at what I've got for your CV so far:\n\n" . implode("\n\n", $lines);
    }

    private function isFinalConfirmation(string $body): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $body) ?? $body));
        $normalized = rtrim($normalized, ".!? ");

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

    /**
     * The exact-match list above only catches the obvious cases. Real replies to the final
     * confirmation are often a full sentence ("Its done thank you so much God bless you.") —
     * use AI to judge whether the candidate is simply confirming they're finished, with no new
     * CV detail buried in there, rather than requiring an exact phrase.
     */
    private function aiSaysCandidateIsDone(AutoCvSession $session, string $body): bool
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            return false;
        }

        $systemPrompt = <<<PROMPT
A candidate building a CV over WhatsApp was just asked: "Reply DONE if everything is complete, or send any extra details you still want added."

Decide whether their reply is simply confirming they're finished, with no new CV detail in it — no matter the exact wording, tone, gratitude, blessings, or sign-off (e.g. "Its done thank you so much God bless you", "yep all good thanks", "no that's everything, appreciate it") — versus a reply that contains an actual new detail to add to their CV (a name, date, employer, school, skill, contact, correction, etc.), even if it's mixed in with thanks.

Return JSON only: {"is_done": true|false}. Set is_done to false if the message contains ANY new CV detail, even alongside a thank-you.
PROMPT;

        try {
            $response = Http::timeout(15)->withToken($apiKey)->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::aiModel(),
                    'temperature' => 0,
                    'max_tokens' => 20,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $body],
                    ],
                ]);
        } catch (Throwable $exception) {
            Log::warning('AutoCvBot: exception classifying final-confirmation reply', [
                'session_id' => $session->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $this->recordAiUsage($session, $response);

        return (bool) ($this->decodeAiJson($response)['is_done'] ?? false);
    }

    /**
     * If a candidate never replies to the final "reply DONE" confirmation, assume they're
     * happy with what they've already given rather than leaving the CV stuck forever — most
     * candidates who reach this point have already provided everything needed.
     */
    private const REOPEN_WARNING_MINUTES = 2;

    private const REOPEN_GRACE_MINUTES = 30;

    public function completeTimedOutConfirmations(int $timeoutMinutes = 2): int
    {
        $completed = 0;

        // Normal "reply DONE" confirmation after a full interview — short timeout, no
        // warning needed, the candidate is only confirming, not writing new content.
        $normalDue = AutoCvSession::query()
            ->where('status', 'collecting')
            ->where('awaiting_final_confirmation', true)
            ->where('reopened_for_missing_detail', false)
            ->whereNotNull('last_question_sent_at')
            ->where('last_question_sent_at', '<=', now()->subMinutes($timeoutMinutes))
            ->get();

        foreach ($normalDue as $session) {
            $this->withCandidateLock($session, function () use ($session, &$completed) {
                $session->refresh();

                if (
                    $session->status === 'collecting'
                    && $session->awaiting_final_confirmation
                    && ! $session->reopened_for_missing_detail
                ) {
                    $this->completeAfterFinalConfirmation($session);
                    $completed++;
                }
            });
        }

        // Reopened because something was missing — give a longer grace period since the
        // candidate needs time to type out real new content, with a heads-up warning partway
        // through rather than going silent and then abruptly finishing without them.
        $reopenedSessions = AutoCvSession::query()
            ->where('status', 'collecting')
            ->where('awaiting_final_confirmation', true)
            ->where('reopened_for_missing_detail', true)
            ->whereNotNull('last_question_sent_at')
            ->get();

        foreach ($reopenedSessions as $session) {
            $this->withCandidateLock($session, function () use ($session, &$completed) {
                $session->refresh();

                if (
                    $session->status !== 'collecting'
                    || ! $session->awaiting_final_confirmation
                    || ! $session->reopened_for_missing_detail
                    || ! $session->last_question_sent_at
                ) {
                    return;
                }

                if (
                    ! $session->reopen_warning_sent_at
                    && $session->last_question_sent_at->lte(now()->subMinutes(self::REOPEN_WARNING_MINUTES))
                ) {
                    $this->sendReopenGraceWarning($session);

                    return;
                }

                if ($session->last_question_sent_at->lte(now()->subMinutes(self::REOPEN_GRACE_MINUTES))) {
                    $this->completeAfterFinalConfirmation($session);
                    $completed++;
                }
            });
        }

        // Asked if they'd like to add a photo, but never replied — don't let the CV sit
        // unfinished forever over an optional extra, just finish it without one. Sending a
        // photo takes real time (finding one, taking one) — give the same generous grace
        // period as the "missing detail" reopen flow, not the short reply-DONE timeout.
        $photoDue = AutoCvSession::query()
            ->where('status', 'collecting')
            ->where('awaiting_cv_photo', true)
            ->whereNotNull('last_question_sent_at')
            ->where('last_question_sent_at', '<=', now()->subMinutes(self::REOPEN_GRACE_MINUTES))
            ->get();

        foreach ($photoDue as $session) {
            $this->withCandidateLock($session, function () use ($session, &$completed) {
                $session->refresh();

                if ($session->status !== 'collecting' || ! $session->awaiting_cv_photo) {
                    return;
                }

                $session->forceFill(['awaiting_cv_photo' => false, 'status' => 'ready'])->save();
                $this->finalizeSession($session->fresh());
                $completed++;
            });
        }

        return $completed;
    }

    private function sendReopenGraceWarning(AutoCvSession $session): void
    {
        $message = $this->generateReopenGraceWarningMessage($session);

        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $message, $errorMessage);

        if (! $sent) {
            return;
        }

        AutoCvMessage::query()->create([
            'session_id' => $session->getKey(),
            'direction' => 'outbound',
            'body' => $message,
        ]);

        $session->forceFill(['reopen_warning_sent_at' => now()])->save();
    }

    private function generateReopenGraceWarningMessage(AutoCvSession $session): string
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            return $this->fallbackReopenGraceWarningMessage($session);
        }

        $personaName = self::PERSONA_NAME;
        $graceMinutes = self::REOPEN_GRACE_MINUTES;

        $systemPrompt = <<<PROMPT
You are {$personaName}, Wakanda Jobs' AI assistant, writing a brief WhatsApp follow-up. You recently asked this candidate what detail was missing from their CV, but they haven't replied yet.

Real WhatsApp conversations read as a couple of short separate messages, not one paragraph. Write 2-3 SHORT separate messages, each one short sentence, with a blank line between them, that together:
- Reassure them there's no rush.
- Let them know you'll keep waiting up to {$graceMinutes} minutes in total for their reply.
- Say that if you don't hear back by then, you'll go ahead and finish their CV with what you already have.
- Stay calm and natural — never robotic, corporate, or pushy.

Rules:
- Never use the word "bot".
- Output ONLY the message text, with each short message separated by a blank line — no preamble, no quotes, no markdown fences, no numbering.
PROMPT;

        try {
            $response = Http::timeout(30)->withToken($apiKey)->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::aiModel(),
                    'temperature' => 0.9,
                    'max_tokens' => 160,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => 'Candidate name: ' . ($session->candidate_name ?: '(not given)')],
                    ],
                ]);
        } catch (Throwable) {
            return $this->fallbackReopenGraceWarningMessage($session);
        }

        if (! $response->successful()) {
            return $this->fallbackReopenGraceWarningMessage($session);
        }

        $text = trim((string) $response->json('choices.0.message.content', ''));
        $text = trim((string) preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text));

        if ($text === '') {
            return $this->fallbackReopenGraceWarningMessage($session);
        }

        $this->recordAiUsage($session, $response);

        return $text;
    }

    private function fallbackReopenGraceWarningMessage(AutoCvSession $session): string
    {
        $name = trim((string) explode(' ', (string) $session->candidate_name)[0]);

        return 'No rush' . ($name ? ", {$name}" : '') . "! I'll wait up to " . self::REOPEN_GRACE_MINUTES . " minutes for that detail.\n\n"
            . "If I don't hear back by then, I'll go ahead and finish your CV with what we already have.";
    }

    private function completeAfterFinalConfirmation(AutoCvSession $session, ?string $closingMessage = null): void
    {
        $closing = trim((string) $closingMessage) !== ''
            ? trim((string) $closingMessage)
            : "Thank you for all the details!";

        $photoQuestion = $closing
            . "\n\nOne more thing — would you like to add a photo to your CV?"
            . "\n\nIf so, send a clear photo of yourself now."
            . "\n\nIf not, just reply \"no\" and I'll finish your CV.";

        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $photoQuestion, $errorMessage);

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
            'body' => $photoQuestion,
        ]);

        // Status stays "collecting" (not "ready") while we wait for the photo reply, the same
        // way awaiting_cv_upload/awaiting_final_confirmation do — findActiveSessionByDigits()
        // only looks up sessions in collecting/failed/stalled, so a "ready" session here would
        // never be found when the candidate's reply comes in.
        $session->forceFill([
            'status' => 'collecting',
            'awaiting_final_confirmation' => false,
            'awaiting_cv_photo' => true,
            'reopened_for_missing_detail' => false,
            'reopen_warning_sent_at' => null,
            'last_question_text' => $photoQuestion,
            'last_question_sent_at' => now(),
            'conversation_text' => $this->buildTranscript($session),
        ])->save();
    }

    private function handleCvPhotoTextReply(AutoCvSession $session, string $body, ?string $whapiMessageId): void
    {
        if (! $this->logInboundMessage($session, $body, $whapiMessageId)) {
            return;
        }

        $session->forceFill(['last_reply_at' => now()])->save();

        // A bare "yes" here means "yes I'll add a photo", not "yes, finish without one" — the
        // actual photo is still coming as a separate message. Finishing immediately on this
        // reply skipped the photo entirely (seen in production: candidate said "yes", never
        // got to attach the file, CV was finalized with no photo). Stay in awaiting_cv_photo
        // and wait for the attachment; only a decline or anything else ends the photo step.
        if ($this->looksLikeBareYes($body)) {
            $this->sendAndLogOutbound($session, "Great — go ahead and send the photo now (JPG or PNG).");

            return;
        }

        $session->forceFill([
            'awaiting_cv_photo' => false,
            'status' => 'ready',
        ])->save();

        $this->finalizeSession($session->fresh());
    }

    private function handleCvPhotoAttachmentReply(AutoCvSession $session, array $message, ?string $whapiMessageId): void
    {
        [$path, $filename, $mime] = $this->downloadWhapiAttachment($session, $message, $whapiMessageId);
        $isImage = $path && (str_starts_with(strtolower($mime), 'image/') || (bool) preg_match('/\.(jpe?g|png|webp|heic)$/i', $filename));

        if (! $this->logInboundMessage(
            $session,
            $isImage ? "Candidate sent a photo for their CV ({$filename})." : 'Candidate sent an attachment for the CV photo step, but it was not a usable image.',
            $whapiMessageId,
            $message
        )) {
            return;
        }

        if (! $isImage) {
            $session->forceFill(['last_reply_at' => now()])->save();

            $this->sendAndLogOutbound($session, "That doesn't look like a photo I can use, sorry.\n\nCould you send a clear photo (JPG or PNG), or just reply \"no\" to skip this?");

            return;
        }

        $session->forceFill([
            'awaiting_cv_photo' => false,
            'status' => 'ready',
            'candidate_photo_path' => $path,
            'last_reply_at' => now(),
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
        $cost = $this->estimateCost(self::aiModel(), $promptTokens, $completionTokens);

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

    /**
     * Unlike work experience, certificates, or projects, every candidate speaks at least one
     * language — there's no legitimate "doesn't apply to me" answer for this topic. Seen in
     * production: the AI scored this topic 90+ (marking it done) despite zero languages ever
     * being captured and the topic never once being asked about. This is a hard code-level
     * floor the AI's own scoring can't override, so it can never be silently skipped.
     */
    private function enforceMandatoryLanguagesTopic(array $sectionScores, array $structuredCv, AutoCvSession $session): array
    {
        $topics = $session->topics ?: self::topics();
        $languagesKey = null;

        foreach ($topics as $index => $topic) {
            if (str_contains($topic, 'Languages they speak')) {
                $languagesKey = (string) ($index + 1);

                break;
            }
        }

        if ($languagesKey === null || ! isset($sectionScores[$languagesKey])) {
            return $sectionScores;
        }

        $hasLanguages = collect($structuredCv['languages'] ?? [])
            ->contains(fn ($row) => is_array($row) ? trim((string) ($row['language'] ?? '')) !== '' : trim((string) $row) !== '');

        if (! $hasLanguages && $sectionScores[$languagesKey]['score'] >= 90) {
            $sectionScores[$languagesKey]['score'] = 40;
            $sectionScores[$languagesKey]['improve'] = 'Ask what language(s) the candidate speaks and how well.';
        }

        return $sectionScores;
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
            'ats' => 'ATS',
        ];

        // If the candidate actually gave real references, show them on the CV. Only fall back to
        // "Available on request" when none were collected.
        $renderCv = $cv;
        $hasReferences = collect($cv['references'] ?? [])
            ->contains(fn ($row) => is_array($row) && trim(implode('', array_filter($row))) !== '');

        if (! $hasReferences) {
            $renderCv['references'] = [['name' => 'Available on request', 'role' => '', 'company' => '', 'phone' => '', 'email' => '']];
        }

        $photoDataUri = $this->candidatePhotoDataUri($session);
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
                'photoDataUri' => $photoDataUri,
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

    /** Base64 data URI for the candidate's CV photo, ready to embed straight into the PDF header. */
    private function candidatePhotoDataUri(AutoCvSession $session): ?string
    {
        $path = (string) ($session->candidate_photo_path ?? '');

        if ($path === '' || ! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function polishCvForDocuments(AutoCvSession $session, array $cv): array
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            return $cv;
        }

        $payload = [
            'model' => self::aiModel(),
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
        // Section order: contact, summary, education, certifications, work experience,
        // projects, skills, languages, references.
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

        $this->appendSection($body, 'Certifications and Training', $cv['certifications'] ?? [], true);

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

        $projects = [];
        foreach ($cv['projects'] ?? [] as $row) {
            $line = trim(($row['name'] ?? '') . (($row['description'] ?? '') ? ': ' . $row['description'] : ''));
            $projects[] = trim($line . (($row['link'] ?? '') ? ' — ' . $row['link'] : ''));
        }
        $this->appendSection($body, 'Projects and Volunteer Work', $projects);

        $this->appendSection($body, 'Skills', $cv['skills'] ?? [], true);

        $languages = collect($cv['languages'] ?? [])
            ->map(fn ($row) => trim(implode(' - ', array_filter([$row['language'] ?? '', $row['proficiency'] ?? '']))))
            ->all();
        $this->appendSection($body, 'Languages', $languages, true);

        $references = collect($cv['references'] ?? [])
            ->map(fn ($row) => is_array($row)
                ? implode(' - ', array_filter([$row['name'] ?? '', $row['role'] ?? '', $row['company'] ?? '', $row['phone'] ?? '', $row['email'] ?? '']))
                : trim((string) $row))
            ->filter()
            ->all();
        $this->appendSection($body, 'References', $references ?: ['Available on request']);

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
