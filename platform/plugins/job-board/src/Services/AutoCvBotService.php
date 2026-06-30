<?php

namespace Botble\JobBoard\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Botble\JobBoard\Models\AutoCvMessage;
use Botble\JobBoard\Models\AutoCvSession;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\Media\Facades\RvMedia;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
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
    private const SAMPLE_CACHE_VERSION = '2026-06-23-1';
    private const AI_MAX_TOKENS = 3200;
    private const AI_RETRY_MAX_TOKENS = 4200;

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
            'Bio and contact details: phone number (for calls), WhatsApp number, email address, where they live (town/city), age, marital status, and LinkedIn profile link if they have one',
            'Job title or type of work being looked for',
            'Short personal profile: strengths and what kind of worker they are',
            'Education, highest qualification first (secondary/high school, college, or university — qualification, field, years)',
            'Work experience (company, job title, dates, what they did)',
            'Internships, attachments, volunteer work, or projects — including a link to GitHub or live work if they have one',
            'Strongest skills, tools, software, machines, or languages they can use',
            'Certificates, licences, trainings, or awards — ask the candidate to describe each one in their own words (name, issuing body, year); only mention sending a photo as a fallback if they say they cannot remember the details',
            'Languages they speak and how well they speak each one (e.g. fluent, okay, a little)',
            'References (name, role/company, phone, email) or "Available on request"',
            'Anything *additional* that is important: achievements, leadership, availability, preferred location',
        ];
    }

    public function startSession(string $whatsappNumber, ?string $candidateName, ?int $adminId, ?string $salesAgentCode = null): array
    {
        $salesAgentService = app(SalesAgentService::class);
        $agent = $salesAgentService->resolveAgent($salesAgentCode, $whatsappNumber);

        $session = AutoCvSession::query()->create([
            'admin_id' => $adminId,
            'sales_agent_id' => $agent?->getKey(),
            'sales_agent_code' => $agent ? $agent->code : null,
            'candidate_name' => $candidateName,
            'whatsapp_number' => $whatsappNumber,
            'status' => 'collecting',
            'topics' => self::topics(),
            'topics_covered' => [],
            'answers' => [],
            'awaiting_cv_upload' => true,
        ]);

        if ($agent) {
            $salesAgentService->recordReferral($agent, $whatsappNumber, $salesAgentCode, 'cv_bot');
        }

        $cvUploadQuestion = 'Do you already have a CV you can share?'
            . "\n\nIf you do, go ahead and send it over. A PDF, Word document, or even a clear photo will work."
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
- Briefly explain the structure in simple words: there are 12 short CV sections to go through, one small step at a time, so they know what to expect.
- Reassure them they don't need to make their answers perfect — they can just respond naturally, one simple question at a time.
- Then ask exactly this, keeping it split across separate short messages in the same order shown below, worded naturally in your own way but keeping the meaning of each part exactly the same — never merge separate parts into one sentence, and never drop a part: "{$firstQuestion}"

Rules:
- Never describe what will be done with anything the candidate sends — no "I'll extract", "I'll pull information from it", "I'll analyse it", "I'll process it", or similar. Just ask for it, nothing about what happens to it afterwards.
- Never use the word "bot".
- Output ONLY the message text, with each short message separated by a blank line — no preamble, no quotes, no markdown fences, no numbering.
- Never use bullet points, list formatting, or any line that starts with a hyphen.
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
            . "We'll go through 12 short CV sections together, one simple step at a time.\n\n"
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

        if ($session->awaiting_cv_upload) {
            $classification = $this->classifyCvUploadReply($body);

            // 'yes' ("yes I have one") or 'wait' (ambiguous, e.g. "ok one sec") — in both cases
            // stay patient and ask them to actually send the file rather than guessing and moving
            // on, which previously dropped real "yes" replies that didn't match an exact phrase.
            if ($classification === 'yes' || $classification === 'wait') {
                $this->promptToSendCvNow($session, $body, $whapiMessageId, $classification === 'wait');

                return;
            }

            // 'no' (a decline) or 'content' (their CV pasted as plain text) is handled generically
            // by the normal AI turn below: a decline leaves structured_cv empty so the AI naturally
            // starts from topic 1, while pasted CV text gets folded straight into structured_cv per
            // the AI prompt's existing "fold in anything new" instruction.
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

        [$extractedText, $label, $storedRelativePath] = $this->extractAttachmentText($session, $message, $whapiMessageId);
        $isCvUpload = (bool) $session->awaiting_cv_upload;
        $isFinalCvRecheck = (bool) $session->awaiting_final_confirmation;
        $isAdminCvRecheck = (bool) $session->cv_recheck_requested;

        if ($extractedText !== '') {
            $body = match (true) {
                $isCvUpload => trim('Candidate shared their existing CV as a file' . ($label ? " ({$label})" : '')
                    . ". Extract as much real information as possible from it across every topic below, "
                    . "and only ask about whatever is still missing or unclear.\n\nExtracted text:\n\n" . $extractedText),
                // Sent at the final review step, asked for specifically so we can catch anything the
                // earlier interview missed — compare against what's already captured rather than
                // treating it as a fresh, unrelated attachment.
                $isFinalCvRecheck => trim('Candidate sent their CV file again' . ($label ? " ({$label})" : '')
                    . " at the final review step so we can double-check everything was captured. Compare it against "
                    . "the structured CV captured so far, fold in anything present here that is missing or different, "
                    . "and keep everything already confirmed.\n\nExtracted text:\n\n" . $extractedText),
                // An admin manually asked the candidate again mid-conversation (they may have missed
                // the first ask) — treat the same way as the original CV upload, not as a one-off
                // attachment for whatever topic happened to be in progress.
                $isAdminCvRecheck => trim('Candidate shared their existing CV as a file' . ($label ? " ({$label})" : '')
                    . " after being asked again. Extract as much real information as possible from it across every "
                    . "topic below, compare it against what's already captured, and only ask about whatever is still "
                    . "missing or unclear.\n\nExtracted text:\n\n" . $extractedText),
                default => trim('Candidate sent an attachment' . ($label ? " ({$label})" : '') . ". Extracted text:\n\n" . $extractedText),
            };

            if ($isCvUpload) {
                $session->awaiting_cv_upload = false;
            }

            if ($isAdminCvRecheck) {
                $session->cv_recheck_requested = false;
            }

            // Persist the original file path so the admin can preview it.
            if ($storedRelativePath && ($isCvUpload || $isFinalCvRecheck || $isAdminCvRecheck)) {
                $session->candidate_cv_path = $storedRelativePath;
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

        $fallback = match (true) {
            $isCvUpload => "Thanks for sending that — I couldn't read enough from it, sorry. Could you try a clearer "
                . "photo or a PDF/Word document, or just type out your CV details instead and I'll take it from there?",
            $isFinalCvRecheck => "Thanks for sending that — I couldn't read enough from it, sorry. No problem though — "
                . "just reply DONE to confirm everything captured so far is correct, or send any *additional* details you want added.",
            $isAdminCvRecheck => "Thanks for sending that — I couldn't read enough from it, sorry. Could you try a clearer "
                . "photo or a PDF/Word document instead?",
            default => "Thanks, I received the file/photo, but I can't read enough CV details from it.\n\n"
                . 'Please type the certificate, licence, training, or award details in a normal WhatsApp message. '
                . 'Include the name, issuing body, date/year, licence number if any, and expiry date if any.',
        };

        if ($isAdminCvRecheck) {
            $session->cv_recheck_requested = false;
        }

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
        return $this->classifyCvUploadReply($body) === 'yes';
    }

    /**
     * Classifies a candidate's reply while we're waiting for them to either share an existing
     * CV file or decline. The old version only matched an exact whitelist of phrases ("yes i do",
     * "i have one", ...) — a natural reply like "Yes i have" fell through to the generic branch,
     * which silently gave up on ever getting the file and jumped straight to the interview
     * questions. This classifies by intent instead of exact wording, and treats anything short
     * and ambiguous as "keep waiting" rather than abandoning the file request.
     *
     * - 'yes'     — clearly says they have one ("yes", "yeah", "I have it") — prompt them to send it.
     * - 'no'      — clearly declines ("no", "don't have one") — move on to the interview.
     * - 'content' — a long message, almost certainly their CV pasted as text — fold it in directly.
     * - 'wait'    — short and ambiguous ("ok one sec", "sending now") — re-ask rather than guess.
     */
    private function classifyCvUploadReply(string $body): string
    {
        $normalized = trim((string) preg_replace('/[^a-z ]/', '', strtolower($body)));
        $normalized = (string) preg_replace('/\s+/', ' ', $normalized);

        if ($normalized === '') {
            return 'content';
        }

        // A long message is almost certainly a pasted CV (or a detailed explanation), not a
        // one-line yes/no — let the normal AI turn fold it straight into the structured CV.
        if (str_word_count($normalized) > 10) {
            return 'content';
        }

        if (preg_match('/\b(no|nope|nah|dont|doesnt|didnt|wont|cant|none|never)\b/', $normalized)) {
            return 'no';
        }

        if (preg_match('/\b(yes|yeah|yea|yep|yup|sure|ok|okay|have|got)\b/', $normalized)) {
            return 'yes';
        }

        return 'wait';
    }

    private function promptToSendCvNow(AutoCvSession $session, string $inboundBody, ?string $whapiMessageId, bool $ambiguous = false): void
    {
        if (! $this->logInboundMessage($session, $inboundBody, $whapiMessageId)) {
            return;
        }

        $session->forceFill(['last_reply_at' => now()])->save();

        $message = $ambiguous
            ? "Just checking. Do you have an existing CV you'd like to send? A PDF, Word document, or a clear photo will work.\n\nIf not, just reply \"no\" and we'll go through a few quick questions instead."
            : "Great. Go ahead and send it now.\n\nA PDF, Word document, or a clear photo of it will work.";

        $this->sendAndLogOutbound($session, $message);
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

    /**
     * @param string $direction 'inbound' for a real WhatsApp reply, or 'admin' when an admin fed
     *  content in on the candidate's behalf (e.g. an uploaded CV file) — kept distinct so the
     *  transcript stays an honest record of who actually said what.
     */
    private function recordInboundAndProcess(AutoCvSession $session, string $body, ?string $whapiMessageId, ?array $payload = null, string $direction = 'inbound', bool $silent = false): void
    {
        $body = trim($body);

        if ($body === '') {
            return;
        }

        // Whapi can redeliver the same inbound webhook without a whapi_message_id (or with a
        // different one), so the unique-index check below can't catch it — two NULLs don't
        // collide. Without this, the same reply gets run through a second, independent AI turn
        // and produces a second, differently-worded outbound question for one candidate reply
        // (seen in production: one reply, two near-identical follow-up questions a minute apart).
        $isRecentDuplicate = AutoCvMessage::query()
            ->where('session_id', $session->getKey())
            ->where('direction', $direction)
            ->where('body', $body)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->exists();

        if ($isRecentDuplicate) {
            return;
        }

        try {
            AutoCvMessage::query()->create([
                'session_id' => $session->getKey(),
                'direction' => $direction,
                'body' => $body,
                'whapi_message_id' => $whapiMessageId,
                'whapi_payload' => $payload,
            ]);
        } catch (QueryException $exception) {
            // Whapi redelivered a webhook we already processed for this message id.
            return;
        }

        $matchedQuestion = $this->resolveQuestionForReply($session, $body);
        $session = $this->captureLikelyProjectReply($session, $matchedQuestion, $body);
        $session = $this->applyImmediateTopicCloseSignals($session, $matchedQuestion, $body);

        $answers = $session->answers ?: [];
        $answers[] = [
            'question_sent' => $matchedQuestion,
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

        if ($wasAwaitingFinalConfirmation && ($this->isFinalConfirmation($body) || $this->isNaturalFinalCompletionReply($body) || $this->aiSaysCandidateIsDone($session, $body))) {
            $fresh = $session->fresh();
            $this->completeAfterFinalConfirmation($fresh, null, ! empty($fresh->candidate_photo_path));

            return;
        }

        // Candidates often send the extra details and "Done" as two separate WhatsApp
        // messages seconds apart. Don't clear the confirmation flag just because this
        // particular message wasn't "Done" — fold it in as extra detail (below) and
        // re-show the same confirmation question, instead of letting the general
        // interview engine wander off onto an unrelated follow-up that the candidate's
        // next "Done" would then miss.
        $this->processReplyWithAi($session->fresh(), $wasAwaitingFinalConfirmation, $silent);
    }

    private function resolveQuestionForReply(AutoCvSession $session, string $reply): ?string
    {
        $reply = trim($reply);

        if ($reply === '') {
            return $this->unwrapReminderQuestion((string) $session->last_question_text);
        }

        if ($this->isPureClarificationRequest($reply) || $this->looksLikeFrustrationReply($reply)) {
            return $this->unwrapReminderQuestion((string) $session->last_question_text);
        }

        $recentOutbound = AutoCvMessage::query()
            ->where('session_id', $session->getKey())
            ->where('direction', 'outbound')
            ->latest('id')
            ->take(4)
            ->get(['body'])
            ->reverse()
            ->values();

        if ($recentOutbound->isEmpty()) {
            return $session->last_question_text;
        }

        $bestQuestion = $this->unwrapReminderQuestion((string) $session->last_question_text);
        $bestScore = PHP_INT_MIN;
        $count = $recentOutbound->count();
        $latestQuestion = trim($this->unwrapReminderQuestion((string) ($recentOutbound->last()->body ?? $session->last_question_text)));
        $latestScore = PHP_INT_MIN;

        foreach ($recentOutbound as $index => $message) {
            $question = trim($this->unwrapReminderQuestion((string) $message->body));

            if ($question === '') {
                continue;
            }

            $score = $this->questionReplyFitScore($question, $reply);
            $score += $index - $count;

             if ($index === $count - 1) {
                $latestScore = $score;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestQuestion = $question;
            }
        }

        if ($latestQuestion !== '' && $bestQuestion !== $latestQuestion
            && $this->shouldPreferLatestQuestionForReply($reply, $latestQuestion, $bestScore, $latestScore)) {
            return $latestQuestion;
        }

        return $bestQuestion ?: $session->last_question_text;
    }

    private function shouldPreferLatestQuestionForReply(string $reply, string $latestQuestion, int $bestScore, int $latestScore): bool
    {
        if ($latestQuestion === '') {
            return false;
        }

        if ($this->isAdditionalConfirmationQuestion($latestQuestion)
            && $this->isSubstantiveAnswer($reply)
            && ! $this->isNegativeAdditionalClosureReply($reply)) {
            return true;
        }

        return $latestScore >= ($bestScore - 4);
    }

    private function looksLikeClarificationRequest(string $reply): bool
    {
        $normalized = strtolower(trim((string) preg_replace('/\s+/', ' ', $reply)));

        if ($normalized === '') {
            return false;
        }

        if ($normalized === '?' || $normalized === '??') {
            return true;
        }

        return str_contains($normalized, 'what do you mean')
            || str_contains($normalized, 'i dont understand')
            || str_contains($normalized, "i don't understand")
            || str_contains($normalized, 'please explain')
            || str_contains($normalized, 'kindly explain')
            || str_contains($normalized, 'clarify')
            || str_contains($normalized, 'not sure what you mean')
            || str_contains($normalized, 'is it okay')
            || str_contains($normalized, 'is it okay with my number')
            || str_contains($normalized, 'give me an example')
            || str_contains($normalized, 'what type of')
            || str_contains($normalized, 'for example');
    }

    private function isPureClarificationRequest(string $reply): bool
    {
        if (! $this->looksLikeClarificationRequest($reply)) {
            return false;
        }

        $withoutClarification = $this->stripClarificationFragments($reply);

        return ! $this->isSubstantiveAnswer($withoutClarification);
    }

    private function stripClarificationFragments(string $reply): string
    {
        $cleaned = preg_replace('/\bwhat do you mean\b/i', '', $reply) ?? $reply;
        $cleaned = preg_replace("/\bi don'?t understand\b/i", '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\bplease explain\b/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\bkindly explain\b/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\bclarify\b/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\bnot sure what you mean\b/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\bis it okay(?: with my number)?\b/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\bgive me an example\b/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\bwhat type of[^,.?!]*[?.!]?/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\bfor example\b/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\?+/', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s+/', ' ', $cleaned) ?? $cleaned;

        return trim($cleaned, " \t\n\r\0\x0B,.-");
    }

    private function looksLikeFrustrationReply(string $reply): bool
    {
        $normalized = strtolower(trim((string) preg_replace('/\s+/', ' ', $reply)));

        if ($normalized === '') {
            return false;
        }

        return str_contains($normalized, 'already said')
            || str_contains($normalized, 'same question')
            || str_contains($normalized, 'you have been asking me the same')
            || str_contains($normalized, 'asking me the same question')
            || str_contains($normalized, 'i have already said')
            || str_contains($normalized, 'i already said')
            || str_contains($normalized, 'you have been asking me the same question thing');
    }

    private function questionReplyFitScore(string $question, string $reply): int
    {
        $questionLower = strtolower($question);
        $replyLower = strtolower(trim($reply));
        $score = 0;

        if ($replyLower === '') {
            return $score;
        }

        if ($this->isSectionPromptQuestion($question)) {
            $score -= 3;
        }

        if (str_contains($questionLower, 'just a quick reminder') || str_contains($questionLower, 'we are still waiting for this information')) {
            $score -= 5;
        }

        if ($this->looksLikeDateAnswer($replyLower) && preg_match('/\b(date|year|month|start|end|when)\b/', $questionLower)) {
            $score += 8;
        }

        if (preg_match('/\bmarital status\b/', $questionLower) && preg_match('/\b(single|married|divorced|widowed|separated)\b/', $replyLower)) {
            $score += 8;
        }

        if (preg_match('/\bage\b/', $questionLower) && preg_match('/^\d{1,2}$/', trim($replyLower))) {
            $score += 8;
        }

        if (preg_match('/\b(email)\b/', $questionLower) && (str_contains($replyLower, '@') || str_contains($replyLower, 'gmail') || str_contains($replyLower, 'yahoo'))) {
            $score += 8;
        }

        if (preg_match('/\b(linkedin)\b/', $questionLower) && (str_contains($replyLower, 'linkedin') || str_contains($replyLower, 'http') || str_contains($replyLower, 'www.'))) {
            $score += 8;
        }

        if (preg_match('/\b(phone number|phone|mobile)\b/', $questionLower) && preg_match('/\+?\d[\d\s]{6,}/', $replyLower)) {
            $score += 8;
        }

        if (preg_match('/\b(language|languages)\b/', $questionLower) && (str_contains($replyLower, ':') || str_contains($replyLower, 'fluent') || str_contains($replyLower, 'okay'))) {
            $score += 8;
        }

        if (preg_match('/\b(job title|type of work|looking for)\b/', $questionLower) && ! $this->looksLikeDateAnswer($replyLower)) {
            $score += 6;
        }

        if (preg_match('/\b(work experience|experience|employer|company|responsibilit(?:y|ies)|duties)\b/', $questionLower)
            && ($this->isNegativeAdditionalClosureReply($replyLower) || $this->looksLikeDateAnswer($replyLower) || str_contains($replyLower, "\n"))) {
            $score += 6;
        }

        if (preg_match('/\b(certificates|certificate|licen[cs]es|trainings|awards)\b/', $questionLower) && ($this->isNegativeAdditionalClosureReply($replyLower) || str_contains($replyLower, 'certificate') || str_contains($replyLower, 'award'))) {
            $score += 6;
        }

        if (preg_match('/\b(references|reference)\b/', $questionLower) && (str_contains($replyLower, '@') || preg_match('/\+?\d[\d\s]{6,}/', $replyLower))) {
            $score += 6;
        }

        if ((str_contains($replyLower, ',') || str_contains($replyLower, "\n")) && preg_match('/\b(job title|type of work|skills|languages)\b/', $questionLower)) {
            $score += 4;
        }

        if ($this->isNegativeAdditionalClosureReply($replyLower) && $this->isSectionPromptQuestion($question)) {
            $score += 5;
        }

        return $score;
    }

    private function unwrapReminderQuestion(string $question): string
    {
        $question = trim($question);

        if ($question === '') {
            return '';
        }

        $marker = "We are still waiting for this information:";

        if (! str_contains($question, $marker)) {
            return $question;
        }

        $parts = explode($marker, $question, 2);
        $embedded = trim((string) ($parts[1] ?? ''));

        return $embedded !== '' ? $embedded : $question;
    }

    private function isSectionPromptQuestion(string $question): bool
    {
        $normalized = strtolower($question);

        return str_contains($normalized, 'provide a little more information for the')
            && str_contains($normalized, 'section');
    }

    private function looksLikeDateAnswer(string $reply): bool
    {
        return (bool) preg_match('/\b(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|oct|nov|dec)\b/i', $reply)
            || (bool) preg_match('/\b\d{4}\b/', $reply)
            || (bool) preg_match('/\b\d{1,2}(st|nd|rd|th)?\b/i', $reply)
            || str_contains($reply, 'present');
    }

    private function applyImmediateTopicCloseSignals(AutoCvSession $session, ?string $matchedQuestion, string $reply): AutoCvSession
    {
        if ($matchedQuestion === null) {
            return $session;
        }

        $topicNumber = $this->resolveExplicitDeclineTopicNumber($session, $matchedQuestion)
            ?? $this->inferTopicNumberFromQuestion($session, $matchedQuestion)
            ?? $this->directTopicNumberFromQuestionText($matchedQuestion);

        if (! is_int($topicNumber)) {
            return $session;
        }

        if ($topicNumber === 11 && $this->replyMeansAvailableOnRequest($reply)) {
            $session->forceFill(['references_available_on_request' => true])->save();

            return $session->fresh();
        }

        if (! in_array($topicNumber, $this->topicsThatAllowExplicitNone(), true)) {
            return $session;
        }

        if (! $this->replyClosesCurrentTopic($session, $matchedQuestion, $reply)) {
            return $session;
        }

        return $session->fresh();
    }

    private function processReplyWithAi(AutoCvSession $session, bool $forceReconfirm = false, bool $silent = false): void
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

        if (! $this->hasValidAiResponseShape($decoded)) {
            $retryPayload = $payload;
            $retryPayload['max_tokens'] = max((int) ($retryPayload['max_tokens'] ?? 0), self::AI_RETRY_MAX_TOKENS);
            $retryPayload['messages'][] = [
                'role' => 'user',
                'content' => $this->isTruncatedAiResponse($response)
                    ? 'The previous response was cut off before the JSON finished. Return the FULL response again as valid JSON in the exact requested shape, with all required top-level keys present. Do not include markdown or commentary.'
                    : 'The previous response was not valid for the required schema. Return ONLY valid JSON in the exact requested shape. Do not include markdown, commentary, or missing top-level keys.',
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

            if ($this->hasValidAiResponseShape($decoded)) {
                $response = $retryResponse;
            }
        }

        if (! $this->hasValidAiResponseShape($decoded)) {
            $session->forceFill([
                'status' => 'failed',
                'error_message' => $this->isTruncatedAiResponse($response)
                    ? 'OpenAI response was truncated before the required JSON finished.'
                    : 'OpenAI returned an invalid response shape.',
                'error_trace' => Str::limit((string) $response->json('choices.0.message.content', $response->body()), 4000, ''),
            ])->save();

            $this->notifyAdmin($session, 'failed');

            return;
        }

        $askedClosedAdditionalTopicAgain = $this->questionTargetsPreviouslyClosedAdditionalTopic($session, (string) $decoded['next_message']);

        if ($askedClosedAdditionalTopicAgain) {
            $stuckPayload = $payload;
            $stuckPayload['messages'][] = [
                'role' => 'user',
                'content' => "You are trying to reopen a topic the candidate already closed with a clear 'nothing *additional* to add' answer. Do not ask about that same topic again. Keep the topic resolved, preserve everything already captured for it, and compose next_message about a DIFFERENT unresolved topic or field instead.",
            ];

            try {
                $stuckResponse = Http::timeout(90)->withToken($apiKey)->acceptJson()
                    ->post('https://api.openai.com/v1/chat/completions', $stuckPayload);

                $stuckDecoded = $stuckResponse->successful() ? $this->decodeAiJson($stuckResponse) : null;

                if ($this->hasValidAiResponseShape($stuckDecoded) && trim((string) $stuckDecoded['next_message']) !== '') {
                    $decoded = $stuckDecoded;
                    $response = $stuckResponse;
                }
            } catch (Throwable) {
                // Fall through with the original message rather than failing the turn.
            }
        }

        // Stronger safety net: this exact question has already been sent 2+ times before in this
        // session (not just immediately prior) — seen in production spiralling across several
        // turns with real answers given each time, never accepted, until the candidate gave up
        // ("Am tired of sending all over again my details"). Gentle rephrasing has already failed
        // by this point, so force the model to stop asking and move on instead of trying again.
        $priorAskCount = $this->timesQuestionAlreadyAsked($session, (string) $decoded['next_message']);

        // A real answer was already given once before to this same question (even reworded) —
        // seen in production: the candidate fully answered "training during work experience," the
        // topic detoured to education for several turns, and the bot still circled back and asked
        // the identical question again. Waiting for a 3rd repeat before stepping in meant an
        // already-answered question still got asked a 2nd time; if a real answer is on record,
        // there's no good reason to ask again at all.
        $alreadyAnsweredSubstantively = $priorAskCount >= 1 && $this->hasSubstantiveAnswerAlready($session, (string) $decoded['next_message']);

        if ($priorAskCount >= 2 || $alreadyAnsweredSubstantively) {
            $stuckPayload = $payload;
            $stuckPayload['messages'][] = [
                'role' => 'user',
                'content' => "You are about to send the exact question \"{$decoded['next_message']}\" for at least the " . ($priorAskCount + 1) . 'th time, and the candidate already gave a real answer to it before. Stop asking it. Use whatever they most recently said as the final value for that field/entry, even if imperfect or informal (or set it to "Not specified" if you genuinely cannot tell what it is from anything they have said), mark it resolved, and compose next_message about a DIFFERENT topic or field instead.',
            ];

            try {
                $stuckResponse = Http::timeout(90)->withToken($apiKey)->acceptJson()
                    ->post('https://api.openai.com/v1/chat/completions', $stuckPayload);

                $stuckDecoded = $stuckResponse->successful() ? $this->decodeAiJson($stuckResponse) : null;

                if ($this->hasValidAiResponseShape($stuckDecoded) && trim((string) $stuckDecoded['next_message']) !== '') {
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

                if ($this->hasValidAiResponseShape($clarifyDecoded) && trim((string) $clarifyDecoded['next_message']) !== '') {
                    $decoded = $clarifyDecoded;
                    $response = $clarifyResponse;
                }
            } catch (Throwable) {
                // Fall through with the original (repeated) message rather than failing the turn.
            }
        }

        $this->recordAiUsage($session, $response);

        $sanitizedCv = $this->sanitizeCv($decoded['structured_cv'], $session);
        $sanitizedCv = $this->applyLatestTurnFallbacks($session, $sanitizedCv);
        $previewSession = clone $session;
        $previewSession->structured_cv = $sanitizedCv;

        $totalTopics = count($session->topics ?: self::topics());
        $sectionScores = $this->recalculateSectionScores($previewSession, $sanitizedCv, $decoded['section_scores'] ?? []);
        $sectionScores = $this->applyLatestExplicitDecline($sectionScores, $session);
        $sectionScores = $this->applyLatestTurnScoreOverrides($session, $sectionScores);
        $sectionScores = $this->applyMaxTurnsScoreOverrides($session, $sectionScores);

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
        $nextMessage = $this->normalizeContactFollowUpMessage($session, $sanitizedCv, $sectionScores, $nextMessage);
        $nextMessage = $this->normalizeProjectFollowUpMessage($session, $sanitizedCv, $sectionScores, $nextMessage);
        $nextMessage = $this->normalizeReferenceFollowUpMessage($session, $nextMessage);
        $nextMessage = $this->normalizeAdditionalFollowUpMessage($session, $nextMessage);
        $nextMessage = $this->alignNextMessageToWeakSection($session, $sectionScores, $nextMessage);

        $promptSession = clone $session;
        $promptSession->structured_cv = $sanitizedCv;
        $promptSession->section_scores = $sectionScores;

        $forcedClarification = $this->simpleClarificationMessageForLatestTurn($promptSession);

        if ($forcedClarification !== null) {
            $nextMessage = $forcedClarification;
        }

        if ($isComplete && $this->looksLikeCandidateQuestion($nextMessage) && ! $this->looksLikeClosingConfirmationQuestion($nextMessage)) {
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

        if (! $isComplete) {
            $nextMessage = $this->prependProgressUpdate($session, $sectionScores, $topicsCovered, $nextMessage);
        }

        // This reply just closed out a section we'd specifically reopened (e.g. an admin's
        // "ask about this weak section" follow-up) — without an acknowledgement here, jumping
        // straight to the generic "please confirm" prompt reads as if the bot never saw the
        // candidate's answer.
        $justAcknowledgedReopen = $isComplete && ! $forceReconfirm && (bool) $session->reopened_for_missing_detail;

        $session->forceFill([
            'structured_cv' => $sanitizedCv,
            'topics_covered' => $topicsCovered,
            'section_scores' => $sectionScores,
            'suggested_job_positions' => $this->sanitizeJobPositions($decoded['suggested_job_positions'] ?? []),
        ])->save();

        if ($isComplete) {
            $nextMessage = $this->finalConfirmationMessage($session, $justAcknowledgedReopen);
        }

        // Silent admin update — CV and scores are already saved above; skip WhatsApp entirely.
        if ($silent) {
            AutoCvMessage::query()->create([
                'session_id' => $session->getKey(),
                'direction' => 'admin_note',
                'body' => '[Admin silently updated CV data — no message sent to candidate]',
            ]);

            return;
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

        // Safety guard: if the latest candidate reply already has a later outbound question on
        // record, this turn has already been processed. Re-running it would just resend another
        // question for the same reply, which is exactly how duplicate loops can get amplified
        // during manual recovery work.
        if ($session->last_reply_at && $session->last_question_sent_at && $session->last_question_sent_at->gte($session->last_reply_at)) {
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
            $baseQuestion = $this->unwrapReminderQuestion((string) $session->last_question_text);
            $message = "Hi" . ($session->candidate_name ? " {$session->candidate_name}" : '') . ", just a quick reminder to reply when you can so we can finish your CV.\n\n"
                . "We are still waiting for this information:\n{$baseQuestion}";

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

    public function requestSectionInformation(AutoCvSession $session, int $topicNumber, ?string $exactQuestion = null): bool
    {
        $topics = $session->topics ?: self::topics();

        if (! isset($topics[$topicNumber - 1])) {
            return false;
        }

        if ($exactQuestion !== null && $exactQuestion !== '') {
            // Admin clicked "Ask" on a specific sub-field — send that exact question, nothing else.
            $message = $exactQuestion;
        } else {
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

    /**
     * Admin-triggered: ask the candidate again whether they have an existing CV to send, in case
     * they missed the very first ask or the conversation moved on before they got round to it.
     * Unlike awaiting_cv_upload (used for the very first ask), this must NOT change how plain text
     * replies are routed — the candidate may still be mid-interview on something unrelated, and we
     * don't want their next ordinary answer mistaken for a yes/no about the CV. Setting
     * cv_recheck_requested only changes how the *next attachment*, if any, gets interpreted.
     */
    public function requestCvUpload(AutoCvSession $session): bool
    {
        $message = "Hi" . ($session->candidate_name ? " {$session->candidate_name}" : '') . ", quick one. Do you have an existing CV you could send us? "
            . "A PDF, Word document, or a clear photo will work. It'll help us double-check everything is captured correctly.\n\n"
            . "If you don't have one, no worries. Just carry on answering the questions.";

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
            'cv_recheck_requested' => true,
            'last_question_text' => $message,
            'last_question_sent_at' => now(),
            'candidate_reminder_count' => 0,
            'last_candidate_reminder_sent_at' => null,
            'error_message' => null,
            'error_trace' => null,
        ])->save();

        return true;
    }

    /**
     * Admin-triggered: upload a CV file on the candidate's behalf (e.g. they sent it some other
     * way — email, in person) and fold whatever it contains straight into the structured CV,
     * reusing the same extraction pipeline a candidate-sent WhatsApp attachment goes through.
     *
     * @return array{success: bool, message: string}
     */
    public function injectAdminReply(AutoCvSession $session, string $text, bool $silent = false): array
    {
        $text = trim($text);

        if ($text === '') {
            return ['success' => false, 'message' => 'Reply text cannot be empty.'];
        }

        // Re-open the session if it was completed or stuck so the AI processes the new info.
        if (in_array($session->status, ['completed', 'failed'], true)) {
            $session->forceFill([
                'status'                    => 'collecting',
                'completed_at'              => null,
                'awaiting_final_confirmation' => false,
                'reopened_for_missing_detail' => true,
                'reopen_warning_sent_at'    => null,
                'error_message'             => null,
                'error_trace'               => null,
                'docx_path'                 => null,
                'pdf_path'                  => null,
            ])->save();
        }

        $this->recordInboundAndProcess($session, $text, null, null, 'inbound', $silent);

        return [
            'success' => true,
            'message' => $silent
                ? 'CV updated silently — no message was sent to the candidate.'
                : 'Reply injected and being processed by the AI now.',
        ];
    }

    public function processAdminUploadedCv(AutoCvSession $session, UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if (! $path || ! is_file($path)) {
            return ['success' => false, 'message' => 'Could not read the uploaded file.'];
        }

        $mime = strtolower((string) $file->getMimeType());
        $filename = $file->getClientOriginalName();
        $lowerFilename = strtolower($filename);

        $text = trim(match (true) {
            str_contains($mime, 'pdf') || str_ends_with($lowerFilename, '.pdf') => $this->extractPdfText($session, $path),
            str_contains($mime, 'wordprocessingml') || str_contains($mime, 'msword')
                || str_ends_with($lowerFilename, '.docx') || str_ends_with($lowerFilename, '.doc') => $this->extractDocxText($path),
            default => $this->extractImageText($session, $path, $mime),
        });

        if ($text === '') {
            return ['success' => false, 'message' => "Couldn't read any usable text from that file — try a clearer photo or a different PDF/Word document."];
        }

        // Persist the file so the admin can preview it later.
        $ext = strtolower($file->getClientOriginalExtension()) ?: 'bin';
        $safeBase = Str::slug(pathinfo($filename, PATHINFO_FILENAME) ?: 'upload') ?: 'upload';
        $storedRelative = 'auto-cv-bot/session-' . $session->getKey() . '/uploads/' . $safeBase . '-' . now()->format('YmdHis') . '.' . $ext;
        Storage::disk('local')->put($storedRelative, file_get_contents($path));

        $session->forceFill(['cv_recheck_requested' => false, 'candidate_cv_path' => $storedRelative])->save();

        $body = "Admin uploaded the candidate's CV file on their behalf ({$filename}). Extract as much real information "
            . "as possible from it across every topic below, compare it against what's already captured, fold in "
            . "anything missing or different, and only ask about whatever is still missing or unclear.\n\n"
            . "Extracted text:\n\n{$text}";

        $this->recordInboundAndProcess($session, $body, null, null, 'admin');

        return ['success' => true, 'message' => 'CV uploaded and processed — details have been folded into the structured CV.'];
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
        $personaImagePath = $this->settingImageLocalPath('auto_cv_bot_persona_image');

        $introSent = $personaImagePath
            ? $this->sendWhapiImage($whatsappNumber, $personaImagePath, $intro, $errorMessage)
            : $this->sendWhapiMessage($whatsappNumber, $intro, $errorMessage);

        if (! $introSent) {
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
        $cached = $this->normalizeSampleCachePayload(json_decode((string) setting($settingKey, ''), true));
        $signature = $this->sampleDocumentsSignature();

        if ($cached && $this->sampleDocumentsExist($cached['documents']) && ($cached['signature'] ?? '') === $signature) {
            return $cached['documents'];
        }

        if ($cached && ! empty($cached['cv'])) {
            $documents = $this->storeSampleDocuments($cached['cv'], $roleSlug, $role);
            $this->storeSampleCachePayload($settingKey, $cached['cv'], $documents, $signature);

            return $documents;
        }

        $cv = $this->generateSampleCv($role);

        if (! $cv) {
            return null;
        }

        $paths = $this->storeSampleDocuments($cv, $roleSlug, $role);
        $this->storeSampleCachePayload($settingKey, $cv, $paths, $signature);

        return $paths;
    }

    private function normalizeSampleCachePayload(mixed $cached): ?array
    {
        if (! is_array($cached) || $cached === []) {
            return null;
        }

        if (isset($cached['documents']) && is_array($cached['documents'])) {
            return [
                'signature' => (string) ($cached['signature'] ?? ''),
                'cv' => is_array($cached['cv'] ?? null) ? $cached['cv'] : null,
                'documents' => $cached['documents'],
            ];
        }

        // Backward compatibility for the old setting shape that stored only the document paths.
        $looksLikeLegacyDocuments = collect($cached)->every(fn ($row) => is_array($row) && isset($row['pdf_path']));

        if ($looksLikeLegacyDocuments) {
            return [
                'signature' => '',
                'cv' => null,
                'documents' => $cached,
            ];
        }

        return null;
    }

    private function storeSampleCachePayload(string $settingKey, array $cv, array $documents, string $signature): void
    {
        setting()->set($settingKey, json_encode([
            'signature' => $signature,
            'cv' => $cv,
            'documents' => $documents,
        ]))->save();
    }

    private function sampleDocumentsSignature(): string
    {
        $templatePath = platform_path('plugins/job-board/resources/views/candidate-alerts/cv-builder-pdf.blade.php');
        $templateStamp = is_file($templatePath) ? (string) filemtime($templatePath) : 'missing';

        return sha1(implode('|', [
            self::SAMPLE_CACHE_VERSION,
            $templateStamp,
            implode(',', ['premium', 'academic', 'creative', 'ats', 'executive']),
        ]));
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
    "whatsapp": "",
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
            'executive' => 'Executive',
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
     * Admin-triggered: manually send the same "here's what I've got, please confirm everything is
     * correct, reply DONE" check-in that the bot sends automatically once it judges the interview
     * complete. Lets an admin nudge a candidate into that step early, without force-closing the
     * conversation the way endConversationNow() does.
     */
    public function requestFinalConfirmation(AutoCvSession $session): bool
    {
        if ($session->status === 'completed') {
            return false;
        }

        $message = $this->finalConfirmationMessage($session);

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
            'awaiting_final_confirmation' => true,
            'last_question_text' => $message,
            'last_question_sent_at' => now(),
            'candidate_reminder_count' => 0,
            'last_candidate_reminder_sent_at' => null,
            'reopen_warning_sent_at' => null,
            'error_message' => null,
        ])->save();

        return true;
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
- Never use bullet points, list formatting, or any line that starts with a hyphen.
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

        $this->completeAfterFinalConfirmation($session->fresh(), $closingMessage, true);

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
        $trimmed = trim($body);

        if ($trimmed !== '' && str_starts_with($trimmed, '_*Quick progress update:')) {
            return [$trimmed];
        }

        $bubbles = collect(preg_split('/\n{2,}/', $trimmed) ?: [])
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

        [$path, $filename, $mime, $relativePath] = $this->downloadWhapiAttachment($session, $message, $whapiMessageId) + [3 => null];

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

        return [trim(implode("\n\n", $parts)), $filename, $relativePath];
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

        return [Storage::disk('local')->path($relativePath), $filename, $mime, $relativePath];
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

You are given the "Structured CV captured so far" JSON below, built from everything the candidate has told you in earlier turns. Treat every value already present in it as locked in and already confirmed — never blank out, drop, or omit a value that's already there just because the latest reply doesn't restate it, and never ask the candidate again about a specific field of a specific entry (e.g. "field of study" for a named school, or "end year" for a named certificate) once that field already has any value below, even a short, vague, or informal one (e.g. "Studies"). This applies just as much to top-level fields (full name, phone, whatsapp, email, age, marital status, headline, summary) as it does to entries within list-type topics — once any of these has a value below, or its topic already scores 90+ under rule 9, do not ask about it again. A vague existing value is still a captured answer, not a gap — only ask about it again if the candidate's own latest reply raises or corrects it. Only add to or correct this JSON using new information from the latest reply; everything else carries forward unchanged.

Given the full conversation so far and the structured CV captured so far, you must:
1. Build the ENTIRE structured CV JSON by starting from the "Structured CV captured so far" JSON given below and folding in anything new from the candidate's latest reply — do not invent employers, schools, qualifications, dates, references, phone numbers, emails, addresses, ages, or marital status. Use empty strings/arrays only for things genuinely never provided in either the captured JSON or the conversation. Marital status and age are sensitive personal details — only fill them in if the candidate actually states them; if they decline or skip, accept that and score the bio topic on the rest of the contact details instead.
2. Decide which topic numbers (1-12, matching the list above) are not yet adequately covered, and return them as "missing_topics". A topic only counts as "covered" once its own score under rule 9 would be 90 or above (a "green" score) — never remove a topic from "missing_topics" just because you asked about it, or because the candidate gave a vague or partial answer. Keep asking follow-up questions about a topic, one at a time, until you can honestly score it 90+.
3. Read the candidate's latest reply carefully before deciding what to do next:
   - If it is a real, substantive answer (even if informal or incomplete), accept it, extract what you can, and move on per rule 5.
   - If it is a confusion signal, a non-answer, OR a clarification request — e.g. "I don't understand", "huh?", "what do you mean", "skip", "kindly clarify", "can you explain that a bit more", "not sure what you mean by [topic]", "could you give an example", a one-word reply that doesn't address what was asked, an emoji, or anything unrelated — do NOT just reword or resend the same question again, and NEVER reply with text that is the same or almost the same as the question you already asked. A polite request to clarify (even one that names the topic, like "kindly clarify a bit on the projects") still counts here — it is asking you to explain differently, not confirming they have nothing to add. (Exception: a one-word decline like "no"/"none"/"nothing" given in reply to a yes/no "is there anything else to add?" confirmation question IS a real, complete answer — see rule 5 — not a confusion signal.) Instead, explain the SAME topic in the simplest possible everyday language, break it into one tiny piece at a time, and give a short concrete example of the kind of answer you want — make up your own example that fits the ACTUAL topic currently being clarified, never reuse an example written for a different topic. For instance, if the topic being clarified is education, you might say something like "No problem. You can send it like this: *School*: Kitwe Technical College, *Qualification*: Diploma in IT, *Years*: 2019 to 2021. Which school or college did you go to, and what did you study?" — but if the topic is projects, your example must be about a project (e.g. "No problem. You can send it like this: *Project*: a poultry-feeding business plan for school. Have you done anything like that?"), not about education or anything unrelated. Never use square brackets like [School], [Qualification], or [Field of Study] in candidate-facing messages — that looks unnatural in WhatsApp. If you need placeholders or labels in an example, use WhatsApp-friendly emphasis such as *School*, *Qualification*, *Field of study*, *Years*, or _School_, _Qualification_, _Field of study_, _Years_ instead. Keep the topic and "missing_topics" unchanged in this case — you are still waiting for the same topic to be answered, not moving to a new question.
   - Never present a simplified/example explanation as if it were a brand-new question — the candidate should clearly feel you are patiently re-explaining the same thing, not asking something different.
   - If the candidate gives only an abbreviation or acronym for the name of an institution, employer, or certifying body (e.g. "CBU", "UNZA", "NIPA", "BGS"), do not accept it and move straight to asking for other details (such as dates or job title) about that same entry, and do not silently expand it yourself even if you are confident you recognise it. Ask them directly to confirm or spell out the full name, by itself, before asking for any other details about that same entry — unless they explicitly say they don't know the full name or that there isn't a longer version, in which case accept the abbreviation as given and continue with the remaining details. Only put a full institution/employer name in structured_cv once the candidate has actually confirmed or typed it themselves. If the candidate names SEVERAL abbreviated institutions/employers in the same reply (e.g. "I worked at BGS and CBU"), ask them to confirm or spell out the full name for ALL of them together in that one clarifying message — and that message must contain ONLY the request for full names, nothing else. Do not combine it with a request for job titles, dates, or responsibilities; ask those only in a later message, once the full names are confirmed.
4. Topic 2 (bio and contact details) bundles several distinct pieces of information — phone/call number, WhatsApp number, email address, residential address/town or city, age, marital status, and LinkedIn URL. The candidate's WhatsApp number is already known from the session (it is pre-filled in "whatsapp" — do NOT ask for it). Only ask for the call/phone number (in case it differs), then email, then town/city, then age, then marital status, then LinkedIn. NEVER ask for more than one of these in the same message. The system enforces this automatically by intercepting your next_message and replacing it with the correct single-field question — so do not worry about which field to ask next for topic 2; just score it honestly and move on to other topics if the current contact field has been answered or declined.

4b. Topic 7 (internships/attachments, volunteer work, and projects) must also be asked ONE sub-type at a time, in this order: (1) internship or attachment, (2) volunteer work, (3) personal or school project, (4) GitHub or portfolio link. NEVER bundle two or more of these sub-types into a single question. Only ask about a sub-type once the previous one has been answered or clearly declined. The system will also intercept and correct bundled topic-7 questions, but you should avoid writing them in the first place., even though they all belong to the same topic — candidates skip or forget fields when asked for several things at once. WRONG example, never do this: "could you please provide your mobile number, email address, residential address, town or city, age, marital status, and LinkedIn profile" — that is six fields in one message. Ask for them ONE AT A TIME, in plain, friendly, everyday language rather than form-like wording (e.g. "What's the best mobile number to reach you on?" rather than "Please provide your mobile number"; "Which town or city do you live in?" rather than "residential address"). Ask in a sensible order: phone/call number first (the "whatsapp" field is already filled from the session — skip it), then email, then town/city, then age, then marital status, then LinkedIn last as a casual optional ask (e.g. "Do you have a LinkedIn profile? No worries if not, we can skip that one."). Only move to the next field once the candidate has answered or clearly declined the current one — never bundle two unanswered fields into a single follow-up. Topic 2 only counts as covered once every field has been asked about and either answered or explicitly declined. A decline of any one field (e.g. "I don't have an email yet", "no LinkedIn", "I'd rather not say my age") must be treated exactly like a rule 5 "don't know" — accept it immediately, store that field as an empty string, treat it as fully resolved, and never ask about that specific field again for the rest of the conversation, no matter how many turns later or how differently worded the question is. This has gone wrong in production: a candidate explicitly said "I don't have any email address yet" and was still asked "Could you please share your email address when you have one?" a turn later and again the next day — that is exactly what this rule forbids.
5. Topics 5 (education), 6 (work experience), 7 (internships/volunteer/projects), 9 (certificates/awards), and 11 (references) can have more than one entry:
   - For each entry the candidate mentions, make sure you collect every key field before treating that entry as done: for work experience — full employer name, job title, start and end dates (or "present"), and what they did; for education — full institution name, qualification, and the relevant years, plus field of study only when that qualification normally has one; for projects, certifications, and references — the equivalent key details (e.g. dates for projects, issuing body and year for certificates). If the candidate has given some but not all of these for an entry, ask specifically for the missing piece(s) next — do not skip ahead to a different entry or a different topic while details are still missing for the current one. Never name more than one entry (e.g. two different schools or two different employers) in the same question, even if they're missing the exact same field — ask about one entry at a time, fully, before moving to the next.
   - Regional education rule for Zambia/Africa: qualifications such as Grade 7, Grade 9, Grade 12, GCE, O-Level, A-Level, Form 1, Form 4, Form 6, junior secondary, senior secondary, or secondary school certificates are complete qualifications on their own. For these, NEVER ask for "field of study", "stream", or "specialisation" — that sounds wrong and confuses candidates. Only ask for the school name and the year completed (or years attended if they already volunteered a range). Reserve "field of study" questions for college, university, diploma, certificate, degree, or trade/technical programmes where a subject area actually exists.
   - Special rule for topic 11 (references): once the candidate has sent ANY real reference detail at all — even if it is only a phone number, only an email address, or only a company name — NEVER resend the generic opener asking them to "share any references" as if nothing was captured. From that point onward, your next_message must clearly acknowledge that you already captured what they sent, then ask only whether they have any *additional* references to add or whether they are done. Example shape: "I've captured the reference detail you sent. If you have any *additional* references, please send them now. If not, just reply done." This must read like a follow-up confirmation, not like the same question being repeated.
   - If the candidate says they don't know, can't remember, or aren't sure about ONE specific missing piece (e.g. the exact dates of a job or the exact year a certificate was issued) — whether that's their very first reply to that question or after you've already reassured them once that it's okay not to know — accept that immediately as the final answer for that piece. Store it as an empty string in structured_cv (or "Present"/"Not specified" if that fits better), treat that piece as resolved, not missing, and move straight on to the next missing piece or the next entry, or close out the topic per the rule below if nothing else is missing. Do NOT ask that same specific piece of that same entry again afterwards, even in gentler, rephrased, or "just let me know if you're not sure" wording — once a candidate has said they don't know something, asking again (in any phrasing) reads as not listening, not as patience. When scoring this entry's topic under rule 9, treat a piece resolved this way as answered, not missing — do not let a "don't know" piece hold a topic's score below 90 once every other piece of every entry is genuinely known.
   - Once you believe every entry the candidate has mentioned so far for that topic is fully detailed, do NOT immediately mark the topic as covered or move to a different topic. First ask a short confirmation question that clearly shows you were listening by briefly mentioning what has already been captured before asking if there is anything *additional* to add. Example shape: "So far I have your work at X and Y. Is there anything *additional* to add?" Avoid wording that sounds like you simply repeated the last question. Only remove the topic from "missing_topics" once the candidate gives a clear answer confirming there is nothing more — this includes short/one-word declines such as "no", "none", "nothing", "nope", "no more", "that's all", "I'm done", or the same word repeated/emphasised (e.g. "NO!", "nothing else"). Treat ANY one-word or short decline reply to this specific confirmation question as a valid "nothing more" answer, not as confusion under rule 3 — rule 3's "non-answer" handling is for when the candidate doesn't address what was asked at all, not for a plain no/none/nothing reply to a yes/no confirmation question. If they mention another entry instead of declining, treat it the same way (collect its full details, then ask again if that's everything).
   - Topic 12 ("Anything *additional* that is important: achievements, leadership, availability, preferred location") follows the SAME close-out rule even though it is not a list of schools or jobs. If you ask whether there is anything *additional* to add for topic 12 and the candidate replies with a clear decline like "no", "none", "no more", "nothing else", "none available", or "that's all", treat topic 12 as fully resolved immediately and never ask about achievements, leadership, availability, or preferred location again later unless the candidate themselves volunteers a correction or a new detail.
6. Once a topic has a real, complete answer (and, for the list-type topics above, once the candidate has confirmed there is nothing more to add), compose the single next WhatsApp message: a friendly, concise question about ONE topic — and, per rule 4, at most ONE field within that topic — that still needs covering. Never ask about more than one topic, or more than one field of topic 2, at a time. Prefer asking about a topic you haven't touched at all yet; but once every topic (1-12) has been asked about at least once, go back and revisit whichever topic currently has the LOWEST score with a clarifying follow-up — keep cycling back through the weak topics, one at a time, asking for more detail or clarity, until each one reaches 90+. Never skip a topic permanently just because the candidate's first answer to it was thin. Hard constraint: never compose next_message about a topic that already scores 90+ under rule 9 in this very response, and never repeat a question about a specific field or piece the candidate has already explicitly said they don't know per rule 5 — both are fully resolved, not pending, no matter how far back in the conversation they were settled. If you use the word additional in next_message, always format it exactly as *additional*. When asking whether there is more to add for a topic, mention what you have already captured first so the follow-up sounds like a confirmation, not a repeated question. Many candidates have not been to college and some may be applying for senior roles despite that — always use simple, everyday words a primary-school reading level can follow, never jargon or "big" words (say "level" or "how well", not "proficiency"; say "skills, tools, or things you're good at", not "competencies"; say "your last job", not "most recent employment"). If a CV term has no simple everyday equivalent, briefly explain it in plain words the first time you use it.
7. Once every topic (1-12) would score 90 or above under rule 9, set "is_complete" to true and make "next_message" a short, friendly closing line thanking the candidate and saying their CV is being prepared. If even one topic is still below 90, keep "is_complete" false and keep working through rule 6 until it isn't.
8. Rewrite informal answers into professional CV language when filling structured_cv. Use British English spelling. For "responsibilities" in work experience, write each as its own short bullet starting with a strong action verb (e.g. Led, Built, Reduced, Delivered, Automated, Designed, Managed) and include a number, percentage, time saved, or team size whenever the candidate's answer makes one available — never write generic duties-only phrasing like "Responsible for...". Use past tense for previous roles and present tense only for a role the candidate is still currently in.
9. For EVERY topic number 1-12, return a "section_scores" entry keyed by that number, with a "label" (short name of the topic), a "score" from 0-100 rating how complete and useful the information given so far is for that topic (0 = nothing provided, 100 = clear and complete), and "improve" — a short, specific, actionable tip for what would make that section stronger (empty string if score is 90 or above). Score honestly based on what is actually known, not on intentions. IMPORTANT exception: if the candidate has clearly and directly stated they have NONE for a topic where that is a normal, valid answer (e.g. "no work experience, I'm a fresh graduate", "no certificates", "none" for internships/projects), that is a complete answer, not a missing one — score it 90-100, leave the corresponding structured_cv array empty, and move on. Do not keep asking about a topic the candidate has already clearly said does not apply to them. The same applies after an *additional* confirmation follow-up: if the candidate clearly says there is nothing more to add for that topic, treat that topic as closed, score it 90-100, leave "improve" empty, and do not reopen it later unless the candidate brings new information themselves. Same rule for a "don't know" piece resolved per rule 5: once every field of every entry in a topic is either a real value or explicitly resolved as "don't know", score that topic 90-100 and set "improve" to an empty string — never leave "improve" suggesting you still need the very piece the candidate already said they don't know. Hard constraint: never score a topic 90+ if you have never actually asked the candidate about it and they have never volunteered anything for it — a topic with zero turns spent on it and an empty structured_cv entry must score low (well under 50), no matter how many other topics are already done or how long the interview has run. Languages (topic 10) in particular has no valid "none" answer — everyone speaks at least one language — so it must never score 90+ until at least one language has actually been captured. Topic 4 (personal profile) requires a short paragraph of 2-3 sentences that a reader can put straight onto a CV — it must describe who the candidate is as a worker, what they are good at, and what kind of role or environment they are looking for. A bare list of traits alone ("hardworking, honest") is NOT enough; help the candidate turn those traits into sentences if that is all they give. Only score topic 4 at 90+ once the summary in structured_cv contains at least two full sentences that convey both strengths and the candidate's work personality or goal. Do not ask a second time once a proper 2-3 sentence paragraph has been given.
10. Based on everything known so far in structured_cv (skills, education, experience — even if still incomplete), suggest 3-5 realistic job positions in Zambia this candidate could apply for right now, as "suggested_job_positions": an array of {"title": "", "reason": ""} — "reason" is one short sentence on why it fits. Update this list every turn as more information comes in. If there isn't enough information yet to suggest anything sensible, return an empty array. These suggestions must stay tightly grounded in the candidate's real background. Do not suggest software developer, programmer, IT support, data, engineering, finance, legal, medical, or other specialist roles unless the structured_cv contains direct evidence for them in experience, education, certifications, projects, or skills. Never let an example title from this prompt leak into the output unless the candidate's own data genuinely supports it.
11. If the candidate's latest reply signals tiredness, frustration, or impatience with the process itself (e.g. "I'm tired of repeating myself", "have you finished?", "are we done yet", "this is taking forever", "I already told you this") rather than just answering normally, stop drilling for more detail. Apologise briefly and warmly in next_message, accept whatever has already been given for every topic as final (filling genuinely unanswered pieces with "Not specified" rather than asking again), and move things toward wrapping up — do not ask another detailed follow-up question in the same next_message you apologise in.

Formatting "next_message": real WhatsApp conversations read as several short separate messages, not long paragraphs. If next_message naturally contains more than one distinct thought (e.g. an acknowledgement plus a question, or an explanation plus an example plus a question), write each distinct thought as its own short message and separate them with a blank line — the same way a real person sends a few short texts in a row rather than one long paragraph. Never describe what you will do with their information internally — no "I'll note that down", "I'll add that to your CV", "I'll extract...", "I'll process..." or similar; just acknowledge naturally and move on.

Return ONLY valid JSON, no markdown, in this exact shape:
{
  "structured_cv": {
    "full_name": "", "headline": "", "phone": "", "whatsapp": "", "email": "", "address": "", "location": "", "age": "", "marital_status": "", "linkedin": "", "summary": "",
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
  "suggested_job_positions": [],
  "next_message": "",
  "is_complete": false
}
PROMPT;

        $userPrompt = "Candidate name on file: {$session->candidate_name}\n"
            . "Candidate WhatsApp number on file: {$session->whatsapp_number} (this is pre-filled as the \"whatsapp\" field in structured_cv — do NOT ask the candidate for it)\n\n"
            . "Structured CV captured so far (treat as already confirmed — do not lose or re-ask about anything already filled in here):\n"
            . json_encode($session->structured_cv ?: [], JSON_UNESCAPED_SLASHES) . "\n\n"
            . "Conversation so far:\n" . $this->buildTranscript($session);

        return [
            'model' => self::aiModel(),
            'temperature' => 0,
            'max_tokens' => self::AI_MAX_TOKENS,
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

    private function hasValidAiResponseShape(?array $decoded): bool
    {
        return is_array($decoded)
            && is_array($decoded['structured_cv'] ?? null)
            && is_string($decoded['next_message'] ?? null);
    }

    private function isTruncatedAiResponse(Response $response): bool
    {
        return (string) $response->json('choices.0.finish_reason', '') === 'length';
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

    // A closing confirmation question generated by the AI when it believes the interview is done —
    // e.g. "Do you have any additional references? If not, just reply done." These should NOT
    // reset isComplete back to false; they are wrap-up messages, not genuine data-gathering turns.
    private function looksLikeClosingConfirmationQuestion(string $message): bool
    {
        $lower = strtolower($message);

        return str_contains($lower, 'just reply done')
            || str_contains($lower, 'just say done')
            || str_contains($lower, 'reply done')
            || (str_contains($lower, 'if not') && str_contains($lower, 'done'))
            || (str_contains($lower, 'additional') && (str_contains($lower, 'if not') || str_contains($lower, 'just say no')));
    }

    /**
     * Boilerplate words the model's questions are built from, regardless of topic — stripping
     * these leaves just the topic-specific nouns (e.g. "achievements", "leadership", "roles"),
     * which is what actually identifies whether two differently-worded questions are the same ask.
     */
    private const QUESTION_STOPWORDS = [
        'a', 'about', 'additional', 'an', 'and', 'any', 'are', 'as', 'at', 'be', 'briefly', 'by',
        'can', 'could', 'describe', 'detail', 'details', 'did', 'do', 'does', 'explain', 'for',
        'from', 'give', 'had', 'has', 'have', 'help', 'i', 'if', 'in', 'is', 'it', 'its', 'just',
        'kindly', 'know', 'let', 'list', 'me', 'mention', 'more', 'of', 'on', 'or', 'other',
        'please', 'provide', 'share', 'some', 'strengthen', 'tell', 'that', 'the', 'this', 'to',
        'us', 'was', 'we', 'what', 'when', 'where', 'which', 'who', 'will', 'with', 'would', 'you',
        'your', 'youve', 'cv',
    ];

    /** Catches the model echoing the same question back (even reworded) instead of clarifying. */
    private function isRepeatOfLastQuestion(string $message, ?string $lastQuestion): bool
    {
        if (! $lastQuestion) {
            return false;
        }

        return $this->isSameQuestionTopic($message, $lastQuestion);
    }

    private function normalizeQuestionText(string $text): string
    {
        return trim((string) preg_replace('/[^a-z0-9 ]/', '', strtolower($text)));
    }

    /** The topic-specific words left once generic question boilerplate is stripped out. */
    private function questionKeywords(string $text): array
    {
        $normalized = $this->normalizeQuestionText($text);

        if ($normalized === '') {
            return [];
        }

        return collect(explode(' ', $normalized))
            ->filter(fn (string $word) => $word !== '' && ! in_array($word, self::QUESTION_STOPWORDS, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Two questions are "the same" if most of their topic-specific words overlap, even when the
     * model has reworded the surrounding boilerplate (e.g. "any other achievements or leadership
     * roles" vs "any additional achievements or leadership roles" both reduce to the same
     * {achievements, leadership, roles} set). Exact-string matching missed this — each reword
     * dodged the repeat check, so the candidate kept getting asked the same thing.
     */
    private function isSameQuestionTopic(string $a, string $b): bool
    {
        $wordsA = $this->questionKeywords($a);
        $wordsB = $this->questionKeywords($b);

        if ($wordsA === [] || $wordsB === []) {
            return false;
        }

        $smaller = min(count($wordsA), count($wordsB));

        // A single shared word is too weak a signal on its own (e.g. both happen to mention
        // "skills") — require it to be the entire (one-word) topic on both sides.
        if ($smaller === 1) {
            return $wordsA === $wordsB;
        }

        return (count(array_intersect($wordsA, $wordsB)) / $smaller) >= 0.6;
    }

    /**
     * isRepeatOfLastQuestion() only catches an immediate echo of the single most recent question.
     * Seen in production: the same question gets asked again several turns later (after other
     * questions in between, each time with a real answer already given) — a genuine stuck loop
     * that the immediate-repeat check can't see because last_question_text has since moved on.
     * Counts how many times this question (or a reworded version of it) has already been sent
     * anywhere in this session.
     */
    private function timesQuestionAlreadyAsked(AutoCvSession $session, string $message): int
    {
        if ($this->questionKeywords($message) === []) {
            return 0;
        }

        return collect($session->answers ?: [])
            ->filter(fn (array $turn) => $this->isSameQuestionTopic((string) ($turn['question_sent'] ?? ''), $message))
            ->count();
    }

    /**
     * Whether the candidate already gave a real, informative answer (not "yes 2023 to 2024" left
     * dangling, not "I don't know") to this question (or a reworded version of it) anywhere in the
     * session. Seen in production: a candidate fully answered "training during work experience"
     * once, the topic detoured to education for several turns, then the bot circled back and asked
     * the exact same question again verbatim — only the 3rd+ repeat was being caught (see
     * timesQuestionAlreadyAsked()), so a question already answered once still got asked a 2nd time
     * before the safety net kicked in. If a real answer is already on record, there is no good
     * reason to ask again at all, regardless of how many times it's been asked.
     */
    private function hasSubstantiveAnswerAlready(AutoCvSession $session, string $message): bool
    {
        if ($this->questionKeywords($message) === []) {
            return false;
        }

        return collect($session->answers ?: [])
            ->filter(fn (array $turn) => $this->isSameQuestionTopic((string) ($turn['question_sent'] ?? ''), $message))
            ->contains(fn (array $turn) => $this->isSubstantiveAnswer((string) ($turn['reply'] ?? '')));
    }

    private function isSubstantiveAnswer(string $reply): bool
    {
        $normalized = trim((string) preg_replace('/[^a-z0-9 ]/', '', strtolower($reply)));

        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, ['i dont know', 'dont know', 'not sure', 'none', 'na', 'idk', 'no idea', 'not applicable'], true)) {
            return false;
        }

        return str_word_count($normalized) >= 2 || (bool) preg_match('/\d/', $normalized);
    }

    private function applyLatestTurnFallbacks(AutoCvSession $session, array $cv): array
    {
        $latestTurn = collect($session->answers ?: [])->last();

        if (! is_array($latestTurn)) {
            return $cv;
        }

        $question = trim((string) ($latestTurn['question_sent'] ?? ''));
        $reply = trim((string) ($latestTurn['reply'] ?? ''));

        if ($question === '' || $reply === '') {
            return $cv;
        }

        $topicNumber = $this->inferTopicNumberFromQuestion($session, $question)
            ?? $this->directTopicNumberFromQuestionText($question);

        if ($topicNumber === 3 && trim((string) ($cv['headline'] ?? '')) === '' && $this->isSubstantiveAnswer($reply)) {
            $cv['headline'] = Str::limit($this->normaliseHeadlineAnswer($reply), 150, '');
        }

        if ($topicNumber === 11 && $this->replyMeansAvailableOnRequest($reply)) {
            $cv['references'] = [];
        }

        return $cv;
    }

    private function applyLatestTurnScoreOverrides(AutoCvSession $session, array $sectionScores): array
    {
        $latestTurn = collect($session->answers ?: [])->last();

        if (! is_array($latestTurn)) {
            return $sectionScores;
        }

        $question = trim((string) ($latestTurn['question_sent'] ?? ''));
        $reply = trim((string) ($latestTurn['reply'] ?? ''));

        if ($question === '' || $reply === '') {
            return $sectionScores;
        }

        $topicNumber = $this->inferTopicNumberFromQuestion($session, $question)
            ?? $this->directTopicNumberFromQuestionText($question);

        if ($topicNumber === 11 && $this->replyMeansAvailableOnRequest($reply)) {
            $sectionScores['11'] = [
                'label' => $sectionScores['11']['label'] ?? 'References',
                'score' => 100,
                'improve' => '',
            ];
        }

        return $sectionScores;
    }

    private function applyMaxTurnsScoreOverrides(AutoCvSession $session, array $sectionScores): array
    {
        $customQuestions = $session->custom_questions ?: [];

        if (empty($customQuestions)) {
            return $sectionScores;
        }

        // Count turns per topic by keyword-matching each question_sent to a topic number.
        $turnsPerTopic = [];

        foreach ($session->answers ?: [] as $turn) {
            $q = strtolower(trim((string) ($turn['question_sent'] ?? '')));

            if ($q === '') {
                continue;
            }

            $topicNum = null;

            if (str_contains($q, 'reference') || str_contains($q, 'available on request'))                                                    $topicNum = 11;
            elseif (str_contains($q, 'internship') || str_contains($q, 'volunteer') || str_contains($q, 'project') || str_contains($q, 'github')) $topicNum = 7;
            elseif (str_contains($q, 'certificate') || str_contains($q, 'licence') || str_contains($q, 'award') || str_contains($q, 'training'))  $topicNum = 9;
            elseif (str_contains($q, 'language') || str_contains($q, 'how well do you speak'))                                                $topicNum = 10;
            elseif (str_contains($q, 'skill') || str_contains($q, 'tools'))                                                                   $topicNum = 8;
            elseif (str_contains($q, 'experience') || str_contains($q, 'employer') || str_contains($q, 'company') || str_contains($q, 'responsibilities') || str_contains($q, 'duties')) $topicNum = 6;
            elseif (str_contains($q, 'school') || str_contains($q, 'college') || str_contains($q, 'university') || str_contains($q, 'qualification') || str_contains($q, 'diploma') || str_contains($q, 'degree')) $topicNum = 5;
            elseif (str_contains($q, 'sentences') || str_contains($q, 'describe you') || str_contains($q, 'profile') || str_contains($q, 'kind of worker')) $topicNum = 4;
            elseif (str_contains($q, 'job title') || str_contains($q, 'type of work') || str_contains($q, 'role you'))                        $topicNum = 3;
            elseif (str_contains($q, 'mobile') || str_contains($q, 'phone') || str_contains($q, 'email') || str_contains($q, 'town') || str_contains($q, 'city') || str_contains($q, 'how old') || str_contains($q, 'marital') || str_contains($q, 'linkedin') || str_contains($q, 'residential')) $topicNum = 2;
            elseif (str_contains($q, 'full name') || str_contains($q, 'your name'))                                                           $topicNum = 1;
            elseif (str_contains($q, 'additional') || str_contains($q, 'anything else') || str_contains($q, 'achievement') || str_contains($q, 'availability')) $topicNum = 12;

            if ($topicNum !== null) {
                $turnsPerTopic[$topicNum] = ($turnsPerTopic[$topicNum] ?? 0) + 1;
            }
        }

        foreach ($turnsPerTopic as $topicNum => $turns) {
            $maxTurns = (int) ($customQuestions[$topicNum . '_max_turns'] ?? 0);

            if ($maxTurns <= 0 || $turns < $maxTurns) {
                continue;
            }

            // Cap reached — treat this topic as done so the AI moves on.
            $key = (string) $topicNum;
            $sectionScores[$key] = array_merge($sectionScores[$key] ?? [], [
                'score'   => 90,
                'improve' => '',
            ]);
        }

        return $sectionScores;
    }

    private function normaliseHeadlineAnswer(string $reply): string
    {
        $parts = collect(preg_split('/\s*,\s*|\s*\/\s*|\s+\bor\b\s+/iu', $reply) ?: [])
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->values();

        if ($parts->isEmpty()) {
            return trim($reply);
        }

        return $parts
            ->map(fn (string $part) => Str::title(mb_strtolower($part)))
            ->implode(' / ');
    }

    private function questionTargetsPreviouslyClosedAdditionalTopic(AutoCvSession $session, string $message): bool
    {
        if ($this->questionKeywords($message) === []) {
            return false;
        }

        foreach ($this->closedAdditionalPromptQuestions($session) as $closedQuestion) {
            if ($this->isSameQuestionTopic($closedQuestion, $message)) {
                return true;
            }
        }

        $topicNumber = $this->inferTopicNumberFromQuestion($session, $message);

        return $topicNumber !== null && in_array($topicNumber, $this->closedAdditionalTopicNumbers($session), true);
    }

    private function closedAdditionalPromptQuestions(AutoCvSession $session): array
    {
        return collect($session->answers ?: [])
            ->filter(fn (array $turn) => $this->isAdditionalConfirmationQuestion((string) ($turn['question_sent'] ?? '')))
            ->filter(fn (array $turn) => $this->isNegativeAdditionalClosureReply((string) ($turn['reply'] ?? '')))
            ->map(fn (array $turn) => (string) ($turn['question_sent'] ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    private function closedAdditionalTopicNumbers(AutoCvSession $session): array
    {
        return collect($this->closedAdditionalPromptQuestions($session))
            ->map(fn (string $question) => $this->inferTopicNumberFromQuestion($session, $question))
            ->filter(fn ($topicNumber) => is_int($topicNumber))
            ->unique()
            ->values()
            ->all();
    }

    private function isAdditionalConfirmationQuestion(string $question): bool
    {
        $normalized = strtolower(trim((string) preg_replace('/\s+/', ' ', str_replace('*', '', $question))));

        if ($normalized === '' || ! str_contains($normalized, 'additional')) {
            return false;
        }

        return str_contains($normalized, 'anything additional')
            || str_contains($normalized, 'anything more')
            || str_contains($normalized, 'anything else')
            || str_contains($normalized, 'more to add')
            || str_contains($normalized, 'else to add')
            || str_contains($normalized, 'additional to add');
    }

    private function normalizeContactFollowUpMessage(AutoCvSession $session, array $cv, array $sectionScores, string $message): string
    {
        if ($message === '') {
            return $message;
        }

        if ((int) ($sectionScores['2']['score'] ?? 0) >= 90) {
            return $message;
        }

        // If AI is touching topic 2 at all, enforce one-at-a-time regardless of how it phrases it.
        $lower = strtolower($message);
        $contactKeywords = ['phone', 'mobile', 'whatsapp', 'email', 'town', 'city', 'location', 'age', 'marital', 'linkedin', 'contact detail', 'how old', 'residential'];
        $touchesContact = false;
        foreach ($contactKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $touchesContact = true;
                break;
            }
        }

        if (! $touchesContact && $this->inferTopicNumberFromQuestion($session, $message) !== 2) {
            return $message;
        }

        return $this->contactDetailsFollowUpQuestion($session, $cv);
    }

    private function normalizeProjectFollowUpMessage(AutoCvSession $session, array $cv, array $sectionScores, string $message): string
    {
        if ($message === '') {
            return $message;
        }

        if ((int) ($sectionScores['7']['score'] ?? 0) >= 90) {
            return $message;
        }

        if ($this->inferTopicNumberFromQuestion($session, $message) !== 7) {
            return $message;
        }

        // Only intercept when the AI is clearly asking an opening / bundled question
        // (mentions two or more of the sub-types at once). If the AI is drilling into
        // details of a specific entry the candidate already described, let it continue.
        $lower = strtolower($message);
        $typeHits = (int) str_contains($lower, 'internship')
            + (int) str_contains($lower, 'volunteer')
            + (int) str_contains($lower, 'project')
            + (int) str_contains($lower, 'attachment');

        $hasEntries = collect($cv['projects'] ?? [])->filter(fn ($r) => is_array($r))->isNotEmpty();

        if ($typeHits < 2 && $hasEntries) {
            return $message;
        }

        return $this->projectFollowUpQuestion($session, $cv);
    }

    private function projectFollowUpQuestion(AutoCvSession $session, array $cv): string
    {
        $answers = $session->answers ?: [];

        $asked = fn (string ...$words): bool => collect($answers)
            ->contains(function (array $turn) use ($words): bool {
                $q = strtolower((string) ($turn['question_sent'] ?? ''));

                foreach ($words as $word) {
                    if (str_contains($q, $word)) {
                        return true;
                    }
                }

                return false;
            });

        if (! $asked('internship', 'attachment')) {
            return "Did you do any internship or attachment at a company?\nFor example: 6 months at Bankers Den, or 3 months at a hospital.\nIf not, just say no.";
        }

        if (! $asked('volunteer')) {
            return "Did you do any volunteer work?\nFor example: helping at a church, school, or community event.\nIf not, just say no.";
        }

        if (! $asked('project', 'personal project', 'school project')) {
            return "Did you work on any personal or school project?\nFor example: a business plan, a website, or something you built or helped with.\nIf not, just say no.";
        }

        if (! $asked('github', 'portfolio', 'live link', 'online link')) {
            return "Do you have a GitHub profile or a link to any work you've done online?\nFor example: github.com/yourname or a website.\nIf not, just say no.";
        }

        return "Is there any other internship, volunteer work, or project you'd like to add?\nIf not, just say no.";
    }

    private function normalizeReferenceFollowUpMessage(AutoCvSession $session, string $message): string
    {
        if ($message === '') {
            return $message;
        }

        if ($this->inferTopicNumberFromQuestion($session, $message) !== 11) {
            return $message;
        }

        if (! $this->hasSubstantiveAnswerAlready($session, $message)) {
            return $message;
        }

        if ($this->isClearReferenceAdditionalFollowUp($message)) {
            return $message;
        }

        return "I've captured the reference details you've already sent.\n\n"
            . "If you have any *additional* references, please send them now.\n\n"
            . 'If not, just reply done.';
    }

    private function normalizeAdditionalFollowUpMessage(AutoCvSession $session, string $message): string
    {
        if ($message === '') {
            return $message;
        }

        if ($this->inferTopicNumberFromQuestion($session, $message) !== 12) {
            return $message;
        }

        $latestTurn = collect($session->answers ?: [])->last();

        if (! is_array($latestTurn)) {
            return $message;
        }

        $latestQuestion = (string) ($latestTurn['question_sent'] ?? '');
        $latestReply = (string) ($latestTurn['reply'] ?? '');

        if (! $this->isAdditionalConfirmationQuestion($latestQuestion)) {
            return $message;
        }

        if (! $this->isSubstantiveAnswer($latestReply) || $this->isNegativeAdditionalClosureReply($latestReply)) {
            return $message;
        }

        return "Thanks, I've added that.\n\n"
            . "Is there anything else important to add?\n\n"
            . "For example: availability, preferred work area, or achievements.\n\n"
            . 'If not, just say no.';
    }

    private function isClearReferenceAdditionalFollowUp(string $message): bool
    {
        $normalized = strtolower(trim((string) preg_replace('/\s+/', ' ', str_replace('*', '', $message))));

        if ($normalized === '') {
            return false;
        }

        $mentionsCaptured = str_contains($normalized, 'captured')
            || str_contains($normalized, 'got the reference')
            || str_contains($normalized, 'got your reference')
            || str_contains($normalized, 'received the reference')
            || str_contains($normalized, 'received your reference');

        $mentionsAdditional = str_contains($normalized, 'additional');
        $mentionsDone = str_contains($normalized, 'reply done')
            || str_contains($normalized, 'you are done')
            || str_contains($normalized, "you're done")
            || str_contains($normalized, 'if not')
            || str_contains($normalized, 'thats all')
            || str_contains($normalized, "that's all");

        return $mentionsCaptured && $mentionsAdditional && $mentionsDone;
    }

    private function isNegativeAdditionalClosureReply(string $reply): bool
    {
        $normalized = strtolower(trim($reply));

        $normalized = (string) preg_replace('/[^a-z0-9 ]/', '', $normalized);

        if ($normalized === '') {
            return false;
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized) ?: $normalized;

        if (in_array($normalized, [
            'no',
            'none',
            'nothing',
            'nil',
            'nill',
            'na',
            'n a',
            'not applicable',
            'nope',
            'i dont have',
            'i dont have any',
            'i do not have',
            'i do not have any',
            'no more',
            'no more to add',
            'nothing more',
            'nothing else',
            'none available',
            'none for now',
            'thats all',
            'that is all',
            'im done',
            'done',
            'no other additional roles or achievement',
            'no other additional roles or achievements',
            'no other roles or achievement',
            'no other roles or achievements',
            'no other achievements',
            'none available',
        ], true)) {
            return true;
        }

        if (str_word_count($normalized) > 8) {
            return false;
        }

        return str_starts_with($normalized, 'no ')
            || str_starts_with($normalized, 'none ')
            || str_starts_with($normalized, 'nil ')
            || str_starts_with($normalized, 'i dont have')
            || str_starts_with($normalized, 'i do not have')
            || str_starts_with($normalized, 'nothing ')
            || str_contains($normalized, 'no more to add')
            || str_contains($normalized, 'nothing more')
            || str_contains($normalized, ' no more')
            || str_contains($normalized, ' nothing else')
            || str_contains($normalized, ' thats all')
            || str_contains($normalized, ' that is all');
    }

    private function replyMeansAvailableOnRequest(string $reply): bool
    {
        $normalized = strtolower(trim($reply));
        $normalized = (string) preg_replace('/[^a-z0-9 ]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?: $normalized;

        return str_contains($normalized, 'available on request');
    }

    private function replyClosesCurrentTopic(AutoCvSession $session, string $question, string $reply): bool
    {
        if ($this->isNegativeAdditionalClosureReply($reply) || $this->replyMeansAvailableOnRequest($reply)) {
            return true;
        }

        return $this->aiSaysReplyClosesTopic($session, $question, $reply);
    }

    private function aiSaysReplyClosesTopic(AutoCvSession $session, string $question, string $reply): bool
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            return false;
        }

        $question = trim($question);
        $reply = trim($reply);

        if ($question === '' || $reply === '' || mb_strlen($reply) > 120) {
            return false;
        }

        try {
            $response = Http::timeout(20)->withToken($apiKey)->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::aiModel(),
                    'temperature' => 0,
                    'max_tokens' => 40,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Classify whether the candidate reply means there is nothing more to add for the current CV topic. Return only JSON: {"close_topic":true|false}. Treat replies like "none", "no", "nothing", "I dont have", "I do not have any", and "available on request" as closing replies when they clearly answer the question asked.',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'question' => $question,
                                'reply' => $reply,
                            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ]);
        } catch (Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $this->recordAiUsage($session, $response);

        $decoded = $this->decodeAiJson($response);

        return (bool) ($decoded['close_topic'] ?? false);
    }

    private function inferTopicNumberFromQuestion(AutoCvSession $session, string $question): ?int
    {
        $normalizedQuestion = $this->normalizeQuestionForTopicInference($question);

        $directTopic = $this->directTopicNumberFromQuestionText($normalizedQuestion);

        if ($directTopic !== null) {
            return $directTopic;
        }

        $questionKeywords = $this->questionKeywords($normalizedQuestion);

        if ($questionKeywords === []) {
            return null;
        }

        if ($this->isSectionPromptQuestion($normalizedQuestion)) {
            $sectionLabel = $this->extractSectionPromptLabel($normalizedQuestion);

            if ($sectionLabel !== '') {
                $directTopic = $this->inferTopicNumberFromSectionLabel($session, $sectionLabel);

                if ($directTopic !== null) {
                    return $directTopic;
                }
            }
        }

        $bestTopic = null;
        $bestScore = 0.0;
        $topics = $session->topics ?: self::topics();
        $sectionScores = $session->section_scores ?: [];

        foreach ($topics as $index => $topic) {
            $topicKeywords = $this->questionKeywords((string) $topic);
            $labelKeywords = $this->questionKeywords((string) (($sectionScores[(string) ($index + 1)]['label'] ?? '')));
            $candidateKeywords = array_values(array_unique(array_merge($topicKeywords, $labelKeywords)));

            if ($candidateKeywords === []) {
                continue;
            }

            $intersection = count(array_intersect($questionKeywords, $candidateKeywords));
            $smaller = min(count($questionKeywords), count($candidateKeywords));

            if ($intersection === 0 || $smaller === 0) {
                continue;
            }

            $score = $intersection / $smaller;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTopic = $index + 1;
            }
        }

        return $bestScore >= 0.5 ? $bestTopic : null;
    }

    private function directTopicNumberFromQuestionText(string $question): ?int
    {
        $lower = strtolower($question);

        if ($lower === '') {
            return null;
        }

        if (str_contains($lower, 'work experience')
            || (str_contains($lower, 'experience') && (
                str_contains($lower, 'company')
                || str_contains($lower, 'employer')
                || str_contains($lower, 'job title')
                || str_contains($lower, 'responsibilit')
                || str_contains($lower, 'duties')
            ))) {
            return 6;
        }

        if (str_contains($lower, 'project') || preg_match('/\binter?nship\b/', $lower) || str_contains($lower, 'volunteer work') || str_contains($lower, 'attachment')) {
            return 7;
        }

        if (str_contains($lower, 'certificate') || str_contains($lower, 'certificates') || str_contains($lower, 'licence') || str_contains($lower, 'license') || str_contains($lower, 'training') || str_contains($lower, 'award')) {
            return 9;
        }

        if (str_contains($lower, 'reference') || str_contains($lower, 'available on request')) {
            return 11;
        }

        if (str_contains($lower, 'additional') && (str_contains($lower, 'achievement') || str_contains($lower, 'availability') || str_contains($lower, 'preferred location') || str_contains($lower, 'leadership'))) {
            return 12;
        }

        return null;
    }

    private function normalizeQuestionForTopicInference(string $question): string
    {
        $question = trim($question);

        if ($question === '') {
            return '';
        }

        $parts = preg_split("/\n\s*\n/", $question) ?: [$question];
        $parts = array_values(array_filter(array_map('trim', $parts), fn (string $part) => $part !== ''));

        $filtered = array_values(array_filter($parts, function (string $part): bool {
            $lower = strtolower($part);

            if (str_starts_with($lower, 'quick progress update:')) {
                return false;
            }

            if (str_starts_with($lower, 'you\'re doing well.')
                || str_starts_with($lower, 'youre doing well.')
                || str_starts_with($lower, 'we\'re almost done')
                || str_starts_with($lower, 'were almost done')
                || str_starts_with($lower, 'we\'re past the halfway')
                || str_starts_with($lower, 'were past the halfway')
            ) {
                return false;
            }

            return true;
        }));

        return implode("\n\n", $filtered);
    }

    private function extractSectionPromptLabel(string $question): string
    {
        if (! preg_match('/provide a little more information for the (.+?) section/i', $question, $matches)) {
            return '';
        }

        return trim((string) ($matches[1] ?? ''));
    }

    private function inferTopicNumberFromSectionLabel(AutoCvSession $session, string $label): ?int
    {
        $labelKeywords = $this->questionKeywords($label);

        if ($labelKeywords === []) {
            return null;
        }

        $bestTopic = null;
        $bestScore = 0.0;
        $topics = $session->topics ?: self::topics();
        $sectionScores = $session->section_scores ?: [];

        foreach ($topics as $index => $topic) {
            $candidateKeywords = array_values(array_unique(array_merge(
                $this->questionKeywords((string) $topic),
                $this->questionKeywords((string) (($sectionScores[(string) ($index + 1)]['label'] ?? '')))
            )));

            if ($candidateKeywords === []) {
                continue;
            }

            $intersection = count(array_intersect($labelKeywords, $candidateKeywords));
            $smaller = min(count($labelKeywords), count($candidateKeywords));

            if ($intersection === 0 || $smaller === 0) {
                continue;
            }

            $score = $intersection / $smaller;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTopic = $index + 1;
            }
        }

        return $bestScore >= 0.5 ? $bestTopic : null;
    }

    private function captureLikelyProjectReply(AutoCvSession $session, ?string $matchedQuestion, string $reply): AutoCvSession
    {
        if ($matchedQuestion === null || $this->directTopicNumberFromQuestionText($matchedQuestion) !== 7) {
            return $session;
        }

        if ($this->isNegativeAdditionalClosureReply($reply) || $this->isPureClarificationRequest($reply)) {
            return $session;
        }

        $project = $this->extractLikelyProjectFromReply($reply);

        if ($project === null) {
            return $session;
        }

        $cv = (array) ($session->structured_cv ?: []);
        $projects = collect($cv['projects'] ?? [])->filter(fn ($row) => is_array($row))->values();
        $alreadyExists = $projects->contains(function (array $row) use ($project): bool {
            return strtolower(trim((string) ($row['description'] ?? ''))) === strtolower($project['description'])
                || strtolower(trim((string) ($row['name'] ?? ''))) === strtolower($project['name']);
        });

        if ($alreadyExists) {
            return $session;
        }

        $projects->push($project);
        $cv['projects'] = $projects->all();

        $session->forceFill([
            'structured_cv' => $cv,
        ])->save();

        return $session->fresh();
    }

    private function extractLikelyProjectFromReply(string $reply): ?array
    {
        $description = trim($reply);

        if ($description === '') {
            return null;
        }

        $lower = strtolower($description);

        if ($this->isPureClarificationRequest($description) && ! preg_match('/\b(inter?nship|volunteer|project|attachment)\b/i', $description)) {
            return null;
        }

        $description = $this->stripClarificationFragments($description);
        $description = trim($description, " \t\n\r\0\x0B,");

        if ($description === '' || $this->isPureClarificationRequest($description) || ! $this->isSubstantiveAnswer($description)) {
            return null;
        }

        $lower = strtolower($description);
        $name = 'Project';

        if (str_contains($lower, 'wakanda jobs') && str_contains($lower, 'website')) {
            $name = 'Wakanda Jobs website improvement';
        } elseif (str_contains($lower, 'website')) {
            $name = 'Website improvement project';
        } elseif (str_contains($lower, 'brother')) {
            $name = 'Project with brother';
        } elseif (preg_match('/\binter?nship\b/', $lower)) {
            $name = 'Internship';
        } elseif (str_contains($lower, 'volunteer')) {
            $name = 'Volunteer work';
        }

        return [
            'name' => Str::limit($name, 120, ''),
            'description' => Str::limit($description, 500, ''),
            'link' => '',
        ];
    }

    private function finalConfirmationMessage(AutoCvSession $session, bool $acknowledgeUpdate = false): string
    {
        $prefix = $acknowledgeUpdate ? "Thanks, I've added that to your CV.\n\n" : '';
        $summary = $this->buildCvReviewSummary((array) ($session->structured_cv ?? []));
        $totalSections = count($session->topics ?: self::topics());

        return trim($prefix
            . "You're done with all {$totalSections} CV sections now.\n\n"
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

        $contact = implode(' · ', array_filter([$cv['phone'] ?? '', $cv['whatsapp'] ?? '', $cv['email'] ?? '', $cv['location'] ?? '']));
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

    private function isNaturalFinalCompletionReply(string $body): bool
    {
        $normalized = strtolower(trim((string) preg_replace('/\s+/', ' ', $body)));
        $normalized = (string) preg_replace('/[^a-z0-9 ]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?: $normalized;

        if ($normalized === '') {
            return false;
        }

        $completionPhrases = [
            'you can prepare',
            'you can go ahead',
            'go ahead',
            'please continue',
            'you may continue',
            'you can continue',
            'thats everything',
            'that is everything',
            'thats all',
            'that is all',
            'all is okay',
            'everything is okay',
            'everything is fine',
            'you can finish',
            'please finish',
        ];

        foreach ($completionPhrases as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }

        return (str_contains($normalized, 'dont have any old cv')
                || str_contains($normalized, 'do not have any old cv')
                || str_contains($normalized, 'dont have old cv')
                || str_contains($normalized, 'do not have old cv')
                || str_contains($normalized, 'dont have any old cv documents')
                || str_contains($normalized, 'do not have any old cv documents')
                || str_contains($normalized, 'dont have any cv documents')
                || str_contains($normalized, 'do not have any cv documents'))
            && ! $this->looksLikeCvDetailUpdate($normalized);
    }

    private function looksLikeCvDetailUpdate(string $normalizedReply): bool
    {
        return (bool) preg_match('/\b\d{4}\b/', $normalizedReply)
            || str_contains($normalizedReply, '@')
            || preg_match('/\+?\d[\d\s]{6,}/', $normalizedReply)
            || preg_match('/\b(worked|experience|school|college|university|certificate|skill|reference|project|manager|cashier|receptionist)\b/', $normalizedReply);
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

Decide whether their reply is simply confirming they're finished, with no new CV detail in it — no matter the exact wording, tone, gratitude, blessings, or sign-off (e.g. "Its done thank you so much God bless you", "yep all good thanks", "no that's everything, appreciate it", "you can prepare I don't have any old CV documents with me", "go ahead, I don't have an old CV") — versus a reply that contains an actual new detail to add to their CV (a name, date, employer, school, skill, contact, correction, etc.), even if it's mixed in with thanks.

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
        // Also includes failed sessions where the WhatsApp send failed transiently so
        // we retry them here rather than leaving them permanently stuck.
        $normalDue = AutoCvSession::query()
            ->whereIn('status', ['collecting', 'failed'])
            ->where('awaiting_final_confirmation', true)
            ->where('reopened_for_missing_detail', false)
            ->whereNotNull('last_question_sent_at')
            ->where('last_question_sent_at', '<=', now()->subMinutes($timeoutMinutes))
            ->get();

        foreach ($normalDue as $session) {
            $this->withCandidateLock($session, function () use ($session, &$completed) {
                $session->refresh();

                if (
                    in_array($session->status, ['collecting', 'failed'])
                    && $session->awaiting_final_confirmation
                    && ! $session->reopened_for_missing_detail
                ) {
                    $session->forceFill(['status' => 'collecting'])->save();
                    $this->completeAfterFinalConfirmation($session->fresh(), null, ! empty($session->candidate_photo_path));
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
                    $this->completeAfterFinalConfirmation($session, null, ! empty($session->candidate_photo_path));
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
- Never use bullet points, list formatting, or any line that starts with a hyphen.
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

    private function completeAfterFinalConfirmation(AutoCvSession $session, ?string $closingMessage = null, bool $skipPhotoStep = false): void
    {
        $closing = trim((string) $closingMessage) !== ''
            ? trim((string) $closingMessage)
            : "Thank you for all the details!";

        if ($skipPhotoStep) {
            $errorMessage = null;
            $sent = $this->sendWhapiMessage($session->whatsapp_number, $closing, $errorMessage);

            if (! $sent) {
                // Keep status=collecting so completeTimedOutConfirmations retries automatically.
                $session->forceFill([
                    'status'        => 'collecting',
                    'error_message' => $errorMessage,
                ])->save();

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
                'awaiting_cv_photo' => false,
                'reopened_for_missing_detail' => false,
                'reopen_warning_sent_at' => null,
                'last_question_text' => null,
                'last_question_sent_at' => null,
                'conversation_text' => $this->buildTranscript($session),
            ])->save();

            $this->finalizeSession($session->fresh());

            return;
        }

        $photoQuestion = $closing
            . "\n\nOne more thing, would you like to add a photo to your CV?"
            . "\n\nIf so, send a clear photo of yourself now."
            . "\n\nIf not, just reply \"no\" and I'll finish your CV.";

        $errorMessage = null;
        $sent = $this->sendWhapiMessage($session->whatsapp_number, $photoQuestion, $errorMessage);

        if (! $sent) {
            // Keep status=collecting so completeTimedOutConfirmations retries automatically.
            $session->forceFill([
                'status'        => 'collecting',
                'error_message' => $errorMessage,
            ])->save();

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

        if ($this->isPureClarificationRequest($body)) {
            $this->sendAndLogOutbound($session, "If you want a photo on your CV, send a clear photo of yourself now.\n\nIf not, just reply \"no\" and I'll leave the CV without a photo.");

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
        $sections = collect($session->section_scores ?: [])
            ->filter(fn (array $section) => (int) ($section['score'] ?? 0) < 90)
            ->sortBy(fn (array $section) => (int) ($section['score'] ?? 0));

        $topicKey = $sections->keys()->first();
        $section = $sections->first();

        if (! $section || (! is_string($topicKey) && ! is_int($topicKey))) {
            return null;
        }

        $topicNumber = (int) $topicKey;

        return $this->simpleFollowUpForTopic($session, $topicNumber, $section)
            ?: $this->fallbackSectionPrompt($section);
    }

    private function prependProgressUpdate(AutoCvSession $session, array $sectionScores, array $topicsCovered, string $nextMessage): string
    {
        if ($nextMessage === '') {
            return $nextMessage;
        }

        $totalSections = count($session->topics ?: self::topics());
        $completedSections = count($topicsCovered);
        $remainingSections = max(0, $totalSections - $completedSections);
        $estimatedQuestionsLeft = $this->estimateQuestionsRemaining($sectionScores);
        $previousCompletedSections = count($session->topics_covered ?: []);

        $progressLine = "Quick progress update: we've finished {$completedSections} of {$totalSections} CV sections.";

        if ($remainingSections > 0) {
            $progressLine .= ' '
                . $remainingSections . ' '
                . Str::plural('section', $remainingSections)
                . ' left, likely about '
                . $estimatedQuestionsLeft . ' more '
                . Str::plural('question', $estimatedQuestionsLeft)
                . ' to go.';
        }

        $encouragement = $this->progressEncouragement($previousCompletedSections, $completedSections, $totalSections);
        $progressLine = "_*{$progressLine}*_";

        return trim($progressLine
            . ($encouragement !== '' ? "\n\n{$encouragement}" : '')
            . "\n\n{$nextMessage}");
    }

    private function estimateQuestionsRemaining(array $sectionScores): int
    {
        $openSections = collect($sectionScores)
            ->filter(fn (array $section) => (int) ($section['score'] ?? 0) < 90)
            ->values();

        if ($openSections->isEmpty()) {
            return 0;
        }

        $base = $openSections->count();
        $extra = $openSections
            ->filter(fn (array $section) => (int) ($section['score'] ?? 0) < 50)
            ->count();

        return max(1, min(8, $base + $extra));
    }

    private function progressEncouragement(int $previousCompletedSections, int $completedSections, int $totalSections): string
    {
        if ($totalSections <= 0) {
            return '';
        }

        $previousRatio = $previousCompletedSections / $totalSections;
        $currentRatio = $completedSections / $totalSections;

        if ($previousRatio < 0.8 && $currentRatio >= 0.8 && $currentRatio < 1) {
            return "You're doing well. We're almost done now.";
        }

        if ($previousRatio < 0.5 && $currentRatio >= 0.5 && $currentRatio < 0.8) {
            return "You're doing well. We're past the halfway point now.";
        }

        return '';
    }

    private function alignNextMessageToWeakSection(AutoCvSession $session, array $sectionScores, string $nextMessage): string
    {
        if ($nextMessage === '') {
            return $nextMessage;
        }

        $weakestTopicNumber = $this->weakestOpenTopicNumber($sectionScores);

        if ($weakestTopicNumber === null) {
            return $nextMessage;
        }

        $currentTopicNumber = $this->inferTopicNumberFromQuestion($session, $nextMessage);
        $weakestScore = (int) ($sectionScores[(string) $weakestTopicNumber]['score'] ?? 0);
        $currentScore = $currentTopicNumber ? (int) ($sectionScores[(string) $currentTopicNumber]['score'] ?? 0) : 100;

        if ($currentTopicNumber !== null && $this->isSectionPromptQuestion($nextMessage)) {
            return $this->simpleFollowUpForTopic($session, $currentTopicNumber, $sectionScores[(string) $currentTopicNumber] ?? [])
                ?: $nextMessage;
        }

        if ($currentTopicNumber === $weakestTopicNumber || $currentScore < 90) {
            return $nextMessage;
        }

        $shadowSession = clone $session;
        $shadowSession->section_scores = $sectionScores;

        return $this->nextWeakSectionQuestion($shadowSession) ?: $nextMessage;
    }

    private function weakestOpenTopicNumber(array $sectionScores): ?int
    {
        $key = collect($sectionScores)
            ->filter(fn (array $section) => (int) ($section['score'] ?? 0) < 90)
            ->sortBy(fn (array $section) => (int) ($section['score'] ?? 0))
            ->keys()
            ->first();

        return is_string($key) || is_int($key) ? (int) $key : null;
    }

    private function simpleClarificationMessageForLatestTurn(AutoCvSession $session): ?string
    {
        $latestTurn = collect($session->answers ?: [])->last();

        if (! is_array($latestTurn)) {
            return null;
        }

        $reply = trim((string) ($latestTurn['reply'] ?? ''));
        $question = trim((string) ($latestTurn['question_sent'] ?? ''));

        if ($reply === '' || $question === '') {
            return null;
        }

        if (! $this->isPureClarificationRequest($reply) && ! $this->looksLikeFrustrationReply($reply)) {
            return null;
        }

        $topicNumber = $this->inferTopicNumberFromQuestion($session, $question)
            ?? $this->directTopicNumberFromQuestionText($question);

        if (! is_int($topicNumber)) {
            return null;
        }

        $section = $session->section_scores[(string) $topicNumber] ?? [];

        if ((int) ($section['score'] ?? 0) >= 90) {
            return null;
        }

        return $this->simpleFollowUpForTopic($session, $topicNumber, $section, true);
    }

    private function simpleFollowUpForTopic(AutoCvSession $session, int $topicNumber, array $section = [], bool $clarifying = false): ?string
    {
        $prefix = $clarifying ? "No problem.\nLet's do one small step at a time.\n" : '';
        $cv = is_array($session->structured_cv) ? $session->structured_cv : [];
        $label = trim((string) ($section['label'] ?? ''));
        $improve = trim((string) ($section['improve'] ?? ''));

        $customQuestions = is_array($session->custom_questions) ? $session->custom_questions : [];
        $custom = trim((string) ($customQuestions[(string) $topicNumber] ?? ''));

        if ($custom !== '') {
            return $prefix . $custom;
        }

        return match ($topicNumber) {
            2 => $prefix . $this->contactDetailsFollowUpQuestion($session, $cv),
            3 => $prefix . "What job title would you like on your CV?\nFor example: Sales Assistant, Cashier, or Receptionist.",
            4 => $prefix . "Can you write 2 or 3 short sentences about yourself as a worker?\nFor example: what kind of person you are, what you're good at, and what kind of job you're looking for.\nJust write it in your own words — it doesn't have to be perfect.",
            5 => $prefix . $this->educationFollowUpQuestion($cv),
            6 => $prefix . "Do you have any other work experience to add?\nIf not, just say no.",
            7 => $prefix . $this->projectFollowUpQuestion($session, $cv),
            8 => $prefix . "What skills or tools are you good at?\nFor example: customer service, Microsoft Word, cashier work, or tailoring.",
            9 => $prefix . "Do you have any certificates, training, licences, or awards?\nIf not, just say no.",
            10 => $prefix . "Which languages do you speak?\nYou can answer like this: English - good, Bemba - good.",
            11 => $prefix . "Do you want your CV to say: Available on request?\nIf yes, reply with those exact words.",
            12 => $prefix . "Is there anything else important to add?\nFor example: availability, preferred work area, or achievements.\nIf not, just say no.",
            default => $this->fallbackSectionPrompt([
                'label' => $label,
                'improve' => $improve,
            ]),
        };
    }

    private function educationFollowUpQuestion(array $cv): string
    {
        $rows = collect($cv['education'] ?? [])->filter(fn ($row) => is_array($row));

        if ($rows->isEmpty()) {
            return "What is the name of your school, college, or university?\nIf you only reached secondary school, that is okay.";
        }

        // Find the first incomplete row and ask for its next missing field.
        foreach ($rows as $row) {
            $institution = trim((string) ($row['institution'] ?? ''));
            $qualification = trim((string) ($row['qualification'] ?? ''));
            $endYear = trim((string) ($row['end_year'] ?? ''));
            $field = trim((string) ($row['field'] ?? ''));

            if ($institution === '') {
                return "What is the name of your school, college, or university?\nIf you only reached secondary school, that is okay.";
            }

            if ($qualification === '') {
                return "What qualification did you get from {$institution}?\nFor example: Diploma, Degree, Grade 12 Certificate, or Certificate in Accounting.";
            }

            $isSchoolLeaving = $this->isSchoolLeavingQualification($qualification);

            if ($endYear === '') {
                return $isSchoolLeaving
                    ? "What year did you finish at {$institution}?"
                    : "What year did you finish your {$qualification} at {$institution}?";
            }

            if (! $isSchoolLeaving && $field === '') {
                return "What subject or field did you study for your {$qualification}?\nFor example: Business Administration, Nursing, or Information Technology.";
            }
        }

        // All existing rows look complete — ask if there are more.
        return "Do you have any other qualifications or schools to add?\nIf not, just say no.";
    }

    private function contactDetailsFollowUpQuestion(AutoCvSession $session, array $cv): string
    {
        foreach ([
            'phone' => "What's the best number to call you on?\n\nWe already have your WhatsApp — this is just for a separate call number, in case it's different. If it's the same number, just send it again.",
            'email' => "What's the best email address to reach you on?\nIf you don't have one, just say no.",
            'location' => "Which town or city do you live in?",
            'address' => "Do you want to add your residential area as well?\nFor example: Chalala, Libala, or Matero.\nIf not, just say no.",
            'age' => "How old are you, if you don't mind sharing?\nIf you'd rather skip it, just say no.",
            'marital_status' => "Would you like to include your marital status?\nIf not, just say no.",
            'linkedin' => "Do you have a LinkedIn profile?\nIf not, just say no.",
        ] as $field => $question) {
            if ($this->contactFieldIsResolved($session, $cv, $field)) {
                continue;
            }

            return $question;
        }

        return "Do you have a LinkedIn profile to add?\nIf not, just say no.";
    }

    private function contactFieldIsResolved(AutoCvSession $session, array $cv, string $field): bool
    {
        if (trim((string) ($cv[$field] ?? '')) !== '') {
            return true;
        }

        return $this->contactFieldExplicitlyDeclined($session, $field);
    }

    private function contactFieldExplicitlyDeclined(AutoCvSession $session, string $field): bool
    {
        if (! in_array($field, ['email', 'address', 'age', 'marital_status', 'linkedin'], true)) {
            return false;
        }

        return collect($session->answers ?: [])
            ->filter(function (array $turn) use ($field): bool {
                $question = (string) ($turn['question_sent'] ?? '');

                return $this->questionTargetsContactField($question, $field);
            })
            ->contains(function (array $turn): bool {
                $reply = (string) ($turn['reply'] ?? '');

                return $this->isOptionalContactFieldDeclineReply($reply);
            });
    }

    private function questionTargetsContactField(string $question, string $field): bool
    {
        $normalized = strtolower(trim((string) preg_replace('/\s+/', ' ', str_replace('*', '', $question))));

        if ($normalized === '') {
            return false;
        }

        return match ($field) {
            'phone' => str_contains($normalized, 'phone number') || str_contains($normalized, 'reach you on'),
            'email' => str_contains($normalized, 'email'),
            'location' => str_contains($normalized, 'town or city') || str_contains($normalized, 'live in'),
            'address' => str_contains($normalized, 'residential area') || str_contains($normalized, 'area as well'),
            'age' => str_contains($normalized, 'how old') || str_contains($normalized, 'age'),
            'marital_status' => str_contains($normalized, 'marital status'),
            'linkedin' => str_contains($normalized, 'linkedin'),
            default => false,
        };
    }

    private function isOptionalContactFieldDeclineReply(string $reply): bool
    {
        if ($this->isNegativeAdditionalClosureReply($reply)) {
            return true;
        }

        $normalized = strtolower(trim((string) preg_replace('/[^a-z0-9 ]/', '', $reply)));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?: $normalized;

        return in_array($normalized, [
            'skip',
            'prefer not',
            'rather not say',
            'no email',
            'no linkedin',
            'dont have email',
            'do not have email',
            'dont have linkedin',
            'do not have linkedin',
            'dont want to share',
            'do not want to share',
        ], true)
            || str_contains($normalized, 'no email')
            || str_contains($normalized, 'no linkedin')
            || str_contains($normalized, 'rather not')
            || str_contains($normalized, 'prefer not');
    }

    private function fallbackSectionPrompt(array $section): string
    {
        $label = trim((string) ($section['label'] ?? 'this section'));
        $improve = trim((string) ($section['improve'] ?? ''));

        return "Could you please provide a little more information for the {$label} section?"
            . ($improve !== '' ? "\nWhat would help most: {$improve}" : '');
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
        $cv['phone'] = Str::limit(trim((string) ($cv['phone'] ?? '')), 60, '');
        $cv['whatsapp'] = Str::limit(trim((string) ($cv['whatsapp'] ?? $session->whatsapp_number)), 60, '');
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

        $cv['references'] = collect($cv['references'] ?? [])
            ->map(fn (array $row) => $this->sanitizeReferenceRow($row))
            ->filter(fn (array $row) => trim(implode('', array_filter($row))) !== '')
            ->values()
            ->all();

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

    public function refreshDerivedSessionData(AutoCvSession $session, bool $persist = true): AutoCvSession
    {
        $cv = $this->sanitizeCv((array) ($session->structured_cv ?: []), $session);
        $sectionScores = $this->recalculateSectionScores($session, $cv, $session->section_scores ?: []);
        $topicsCovered = collect($sectionScores)
            ->filter(fn (array $section) => (int) ($section['score'] ?? 0) >= 90)
            ->keys()
            ->map(fn (string $key) => (int) $key)
            ->values()
            ->all();

        $session->forceFill([
            'structured_cv' => $cv,
            'section_scores' => $sectionScores,
            'topics_covered' => $topicsCovered,
            'suggested_job_positions' => $this->sanitizeJobPositions($session->suggested_job_positions ?: []),
        ]);

        if ($persist && $session->isDirty()) {
            $session->save();
        }

        return $session;
    }

    private function recalculateSectionScores(AutoCvSession $session, array $cv, mixed $existingScores): array
    {
        $scores = $this->sanitizeSectionScores($existingScores, $session);
        $fallbacks = $this->fallbackSectionScoresFromCv($session, $cv);

        foreach ($fallbacks as $key => $fallback) {
            if (! isset($scores[$key])) {
                continue;
            }

            $existingScore = (int) ($scores[$key]['score'] ?? 0);
            $fallbackScore = (int) ($fallback['score'] ?? 0);

            if ($fallbackScore > $existingScore) {
                $scores[$key]['score'] = $fallbackScore;
                $scores[$key]['improve'] = $fallbackScore >= 90 ? '' : (string) ($fallback['improve'] ?? '');
            } elseif ($existingScore === 0 && $fallbackScore === 0 && trim((string) ($scores[$key]['improve'] ?? '')) === '') {
                $scores[$key]['improve'] = (string) ($fallback['improve'] ?? '');
            }
        }

        // Prevent regression: if a topic was already green (≥90) in the saved session scores,
        // don't let the AI's fresh response for a different topic drag it back below 90. This
        // stops previously-closed sections (e.g. "no internships") from being re-asked after
        // the candidate answers an unrelated follow-up question.
        $savedScores = is_array($session->section_scores) ? $session->section_scores : [];
        foreach ($savedScores as $key => $saved) {
            if (! isset($scores[$key])) {
                continue;
            }
            $savedScore = (int) ($saved['score'] ?? 0);
            if ($savedScore >= 90 && (int) ($scores[$key]['score'] ?? 0) < 90) {
                $scores[$key]['score'] = $savedScore;
                $scores[$key]['improve'] = '';
            }
        }

        $scores = $this->enforceMandatoryLanguagesTopic($scores, $cv, $session);
        $scores = $this->enforceClosedAdditionalTopics($scores, $session);

        // Must run last — overrides even the closed-topic elevation so that a "no internships"
        // decline cannot shut down volunteer/project/github before they have been asked.
        return $this->enforceProjectSubtopicsCoverage($scores, $session);
    }

    private function fallbackSectionScoresFromCv(AutoCvSession $session, array $cv): array
    {
        $experience = collect($cv['experience'] ?? [])->filter(fn ($row) => is_array($row));
        $education = collect($cv['education'] ?? [])->filter(fn ($row) => is_array($row));
        $projects = collect($cv['projects'] ?? [])->filter(fn ($row) => is_array($row));
        $languages = collect($cv['languages'] ?? [])->filter(fn ($row) => is_array($row));
        $references = collect($cv['references'] ?? [])->filter(fn ($row) => is_array($row));
        $certifications = collect($cv['certifications'] ?? [])->filter(fn ($row) => is_array($row) || is_string($row));

        return [
            '1' => $this->scoreScalarSection($cv['full_name'] ?? '', 'Add the candidate\'s full name.'),
            '2' => $this->scoreContactSection($cv, $session),
            '3' => $this->scoreScalarSection($cv['headline'] ?? '', 'Add the job title or type of role they want.'),
            '4' => $this->scoreScalarSection($cv['summary'] ?? '', 'Add a short profile or strengths summary.'),
            '5' => $this->scoreCollectionSection($education, function (array $row): int {
                return $this->educationRowCompletenessScore($row);
            }, 'Add at least one education entry with school, qualification, and years.'),
            '6' => $experience->isNotEmpty()
                ? ['label' => 'Work experience', 'score' => 100, 'improve' => '']
                : $this->scoreCollectionSection($experience, function (array $row): int {
                    $score = $this->rowCompletenessScore($row, ['job_title', 'company', 'start_date', 'end_date']);
                    $hasResponsibilities = collect($row['responsibilities'] ?? [])
                        ->contains(fn ($value) => trim((string) $value) !== '');

                    return min(100, $score + ($hasResponsibilities ? 20 : 0));
                }, 'Add employer, job title, dates, and what they did.'),
            '7' => $this->scoreCollectionSection($projects, function (array $row): int {
                return $this->projectRowCompletenessScore($row);
            }, 'Add any projects, volunteer work, or internships with a short description.'),
            '8' => $this->scoreListSection($cv['skills'] ?? [], 'Add skills, tools, or things the candidate is good at.'),
            '9' => $this->scoreCertificationsSection($certifications),
            '10' => $this->scoreCollectionSection($languages, function (array $row): int {
                return $this->rowCompletenessScore($row, ['language', 'proficiency']);
            }, 'Add the language and how well the candidate speaks it.'),
            '11' => $this->scoreReferencesSection($references, (bool) $session->references_available_on_request),
            '12' => $this->scoreAdditionalSection($cv),
        ];
    }

    private function scoreScalarSection(mixed $value, string $improve): array
    {
        $filled = trim((string) $value) !== '';

        return [
            'score' => $filled ? 100 : 0,
            'improve' => $filled ? '' : $improve,
        ];
    }

    private function scoreContactSection(array $cv, ?AutoCvSession $session = null): array
    {
        $weights = [
            'whatsapp' => 25,
            'phone' => 20,
            'location' => 30,
            'email' => 15,
            'address' => 3,
            'age' => 3,
            'marital_status' => 2,
            'linkedin' => 2,
        ];

        $score = 0;

        foreach ($weights as $field => $weight) {
            $filled = trim((string) ($cv[$field] ?? '')) !== '';
            $resolvedByDecline = $session && $this->contactFieldExplicitlyDeclined($session, $field);

            if ($filled || $resolvedByDecline) {
                $score += $weight;
            }
        }

        return [
            'score' => min(100, $score),
            'improve' => $score >= 90 ? '' : 'Add any missing contact details still worth including.',
        ];
    }

    private function scoreCollectionSection($rows, callable $rowScorer, string $emptyImprove): array
    {
        if ($rows->isEmpty()) {
            return ['score' => 0, 'improve' => $emptyImprove];
        }

        $score = (int) round($rows->map(fn (array $row) => $rowScorer($row))->avg() ?: 0);

        return [
            'score' => max(0, min(100, $score)),
            'improve' => $score >= 90 ? '' : 'Add the remaining missing details for this section.',
        ];
    }

    private function scoreListSection(array $values, string $improve): array
    {
        $count = collect($values)->filter(fn ($value) => trim((string) $value) !== '')->count();

        if ($count === 0) {
            return ['score' => 0, 'improve' => $improve];
        }

        $score = min(100, 60 + ($count * 20));

        return [
            'score' => $score,
            'improve' => $score >= 90 ? '' : 'Add a few more strong, relevant skills.',
        ];
    }

    private function scoreCertificationsSection($rows): array
    {
        if ($rows->isEmpty()) {
            return ['score' => 0, 'improve' => 'Add any certificates, licences, or trainings if they have them.'];
        }

        $score = (int) round($rows->map(function ($row): int {
            if (is_string($row)) {
                return trim($row) !== '' ? 70 : 0;
            }

            return $this->rowCompletenessScore($row, ['name', 'issuing_body', 'date']);
        })->avg() ?: 0);

        return [
            'score' => max(0, min(100, $score)),
            'improve' => $score >= 90 ? '' : 'Add the issuing body or year for each certificate.',
        ];
    }

    private function scoreReferencesSection($rows, bool $availableOnRequest): array
    {
        if ($availableOnRequest) {
            return ['score' => 100, 'improve' => ''];
        }

        if ($rows->isEmpty()) {
            return ['score' => 0, 'improve' => 'Add references or mark them as available on request.'];
        }

        $score = (int) round($rows->map(fn (array $row) => $this->referenceRowCompletenessScore($row))->avg() ?: 0);

        return [
            'score' => max(0, min(100, $score)),
            'improve' => $score >= 90 ? '' : 'Add the missing phone, email, or role details for each reference.',
        ];
    }

    private function scoreAdditionalSection(array $cv): array
    {
        $count = collect($cv['notes_for_admin'] ?? [])->filter(fn ($value) => trim((string) $value) !== '')->count();

        if ($count === 0) {
            return ['score' => 0, 'improve' => 'Add any extra achievements, availability, or preferred location details.'];
        }

        $score = min(100, 70 + ($count * 15));

        return [
            'score' => $score,
            'improve' => $score >= 90 ? '' : 'Add any other useful achievements or availability details.',
        ];
    }

    private function rowCompletenessScore(array $row, array $fields): int
    {
        if ($fields === []) {
            return 0;
        }

        $filled = collect($fields)
            ->filter(fn (string $field) => trim((string) ($row[$field] ?? '')) !== '')
            ->count();

        return (int) round(($filled / count($fields)) * 100);
    }

    private function projectRowCompletenessScore(array $row): int
    {
        $name = trim((string) ($row['name'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $link = trim((string) ($row['link'] ?? ''));

        if ($name !== '' && $description !== '') {
            return 100;
        }

        if ($description !== '') {
            return $link !== '' ? 90 : 80;
        }

        if ($name !== '') {
            return $link !== '' ? 75 : 60;
        }

        return 0;
    }

    private function educationRowCompletenessScore(array $row): int
    {
        $qualification = trim((string) ($row['qualification'] ?? ''));
        $institution = trim((string) ($row['institution'] ?? ''));
        $field = trim((string) ($row['field'] ?? ''));
        $startYear = trim((string) ($row['start_year'] ?? ''));
        $endYear = trim((string) ($row['end_year'] ?? ''));

        if ($this->isSchoolLeavingQualification($qualification)) {
            $required = [
                $institution !== '',
                $qualification !== '',
                $endYear !== '',
            ];

            if ($startYear !== '') {
                $required[] = true;
            }

            $filled = collect($required)->filter()->count();

            return (int) round(($filled / count($required)) * 100);
        }

        return $this->rowCompletenessScore($row, ['institution', 'qualification', 'field', 'start_year', 'end_year']);
    }

    private function isSchoolLeavingQualification(string $qualification): bool
    {
        $normalized = strtolower(trim($qualification));

        if ($normalized === '') {
            return false;
        }

        $normalized = (string) preg_replace('/[^a-z0-9 ]/', ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?: $normalized;

        return (bool) preg_match('/\bgrade\s*(7|9|12)\b/', $normalized)
            || (bool) preg_match('/\bform\s*(1|2|3|4|5|6)\b/', $normalized)
            || str_contains($normalized, 'gce')
            || str_contains($normalized, 'o level')
            || str_contains($normalized, 'olevel')
            || str_contains($normalized, 'a level')
            || str_contains($normalized, 'alevel')
            || str_contains($normalized, 'junior secondary')
            || str_contains($normalized, 'senior secondary')
            || str_contains($normalized, 'secondary school')
            || str_contains($normalized, 'full certificate');
    }

    private function sanitizeReferenceRow(array $row): array
    {
        $normalized = [
            'name' => Str::limit(trim((string) ($row['name'] ?? '')), 120, ''),
            'role' => Str::limit(trim((string) ($row['role'] ?? '')), 120, ''),
            'company' => Str::limit(trim((string) ($row['company'] ?? '')), 160, ''),
            'phone' => Str::limit(trim((string) ($row['phone'] ?? '')), 60, ''),
            'email' => Str::limit(trim((string) ($row['email'] ?? '')), 150, ''),
        ];

        // The AI sometimes puts the employer/company into "role" and leaves "company" blank
        // when a candidate sends references in a simple "Name at Company" format.
        if ($normalized['company'] === '' && $normalized['role'] !== '') {
            $normalized['company'] = $normalized['role'];
            $normalized['role'] = '';
        }

        return $normalized;
    }

    private function referenceRowCompletenessScore(array $row): int
    {
        $required = [
            trim((string) ($row['name'] ?? '')) !== '',
            trim((string) ($row['phone'] ?? '')) !== '',
            trim((string) ($row['email'] ?? '')) !== '',
            trim((string) ($row['role'] ?? '')) !== '' || trim((string) ($row['company'] ?? '')) !== '',
        ];

        $filled = collect($required)->filter()->count();

        return (int) round(($filled / count($required)) * 100);
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

    /**
     * Hard guard: topic 7 cannot be marked complete (≥90) until every one of the four
     * sub-types has been explicitly asked in the conversation. Without this, the AI
     * sometimes scores the whole topic 90+ after only asking about internships, and the
     * volunteer/project/github questions are silently skipped. This runs AFTER
     * enforceClosedAdditionalTopics so it also neutralises a "no internships" decline
     * that would otherwise close the entire topic prematurely.
     */
    private function enforceProjectSubtopicsCoverage(array $sectionScores, AutoCvSession $session): array
    {
        $topicKey = '7';

        if (! isset($sectionScores[$topicKey])) {
            return $sectionScores;
        }

        if ((int) ($sectionScores[$topicKey]['score'] ?? 0) < 90) {
            return $sectionScores;
        }

        $answers = $session->answers ?: [];
        $asked = fn (string ...$words): bool => collect($answers)
            ->contains(function (array $turn) use ($words): bool {
                $q = strtolower((string) ($turn['question_sent'] ?? ''));
                foreach ($words as $word) {
                    if (str_contains($q, $word)) {
                        return true;
                    }
                }

                return false;
            });

        $allSubtypesAsked = $asked('internship', 'attachment')
            && $asked('volunteer')
            && $asked('project', 'personal project', 'school project')
            && $asked('github', 'portfolio', 'live link', 'online link');

        if (! $allSubtypesAsked) {
            $sectionScores[$topicKey]['score'] = 40;
            $sectionScores[$topicKey]['improve'] = 'Ask about each sub-type in order: internship/attachment, volunteer work, personal/school project, then GitHub/portfolio link.';
        }

        return $sectionScores;
    }

    private function enforceClosedAdditionalTopics(array $sectionScores, AutoCvSession $session): array
    {
        foreach ($this->closedAdditionalTopicNumbers($session) as $topicNumber) {
            $key = (string) $topicNumber;

            if (! isset($sectionScores[$key])) {
                continue;
            }

            $sectionScores[$key]['score'] = max(95, (int) ($sectionScores[$key]['score'] ?? 0));
            $sectionScores[$key]['improve'] = '';
        }

        foreach ($this->explicitlyDeclinedTopicNumbers($session) as $topicNumber) {
            $key = (string) $topicNumber;

            if (! isset($sectionScores[$key])) {
                continue;
            }

            $sectionScores[$key]['score'] = max(95, (int) ($sectionScores[$key]['score'] ?? 0));
            $sectionScores[$key]['improve'] = '';
        }

        return $sectionScores;
    }

    private function explicitlyDeclinedTopicNumbers(AutoCvSession $session): array
    {
        return collect($session->answers ?: [])
            ->filter(fn (array $turn) => $this->isNegativeAdditionalClosureReply((string) ($turn['reply'] ?? '')))
            ->map(function (array $turn) use ($session) {
                $question = (string) ($turn['question_sent'] ?? '');

                return $this->resolveExplicitDeclineTopicNumber($session, $question)
                    ?? $this->inferTopicNumberFromQuestion($session, $question)
                    ?? $this->directTopicNumberFromQuestionText($question);
            })
            ->filter(fn ($topicNumber) => is_int($topicNumber) && in_array($topicNumber, $this->topicsThatAllowExplicitNone(), true))
            ->unique()
            ->values()
            ->all();
    }

    private function resolveExplicitDeclineTopicNumber(AutoCvSession $session, string $question): ?int
    {
        $lower = strtolower($question);

        if ($lower === '') {
            return null;
        }

        if (str_contains($lower, 'work experience')
            || (str_contains($lower, 'experience') && (
                str_contains($lower, 'company')
                || str_contains($lower, 'employer')
                || str_contains($lower, 'job title')
                || str_contains($lower, 'responsibilit')
                || str_contains($lower, 'duties')
            ))) {
            return 6;
        }

        if (preg_match('/\binter?nship\b/', $lower) || str_contains($lower, 'project') || str_contains($lower, 'volunteer work') || str_contains($lower, 'attachment')) {
            // Only close the whole topic when all four sub-types have already been asked.
            // A "no" to just the internship question must not skip volunteer/project/github.
            $answers = $session->answers ?: [];
            $asked = fn (string ...$words): bool => collect($answers)
                ->contains(function (array $turn) use ($words): bool {
                    $q = strtolower((string) ($turn['question_sent'] ?? ''));
                    foreach ($words as $word) {
                        if (str_contains($q, $word)) {
                            return true;
                        }
                    }

                    return false;
                });

            if ($asked('internship', 'attachment')
                && $asked('volunteer')
                && $asked('project', 'personal project', 'school project')
                && $asked('github', 'portfolio', 'live link', 'online link')
            ) {
                return 7;
            }

            return null;
        }

        if (str_contains($lower, 'certificate') || str_contains($lower, 'licence') || str_contains($lower, 'license') || str_contains($lower, 'training') || str_contains($lower, 'award')) {
            return 9;
        }

        if (str_contains($lower, 'reference') || str_contains($lower, 'available on request')) {
            return 11;
        }

        if (str_contains($lower, 'anything *additional*')
            || str_contains($lower, 'anything additional')
            || str_contains($lower, 'achievements')
            || str_contains($lower, 'availability')
            || str_contains($lower, 'preferred location')
            || str_contains($lower, 'leadership')
        ) {
            return 12;
        }

        return null;
    }

    private function topicsThatAllowExplicitNone(): array
    {
        return [6, 7, 9, 11, 12];
    }

    private function applyLatestExplicitDecline(array $sectionScores, AutoCvSession $session): array
    {
        $latestTurn = collect($session->answers ?: [])->last();

        if (! is_array($latestTurn)) {
            return $sectionScores;
        }

        $reply = (string) ($latestTurn['reply'] ?? '');

        if (! $this->isNegativeAdditionalClosureReply($reply)) {
            return $sectionScores;
        }

        $question = (string) ($latestTurn['question_sent'] ?? '');
        $topicNumber = $this->resolveExplicitDeclineTopicNumber($session, $question)
            ?? $this->inferTopicNumberFromQuestion($session, $question)
            ?? $this->directTopicNumberFromQuestionText($question);

        if (! is_int($topicNumber) || ! in_array($topicNumber, $this->topicsThatAllowExplicitNone(), true)) {
            return $sectionScores;
        }

        $key = (string) $topicNumber;

        if (! isset($sectionScores[$key])) {
            return $sectionScores;
        }

        $sectionScores[$key]['score'] = max(95, (int) ($sectionScores[$key]['score'] ?? 0));
        $sectionScores[$key]['improve'] = '';

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
            'executive' => 'Executive',
        ];

        // If the candidate actually gave real references, show them on the CV. Only fall back to
        // "Available on request" when none were collected, or when the admin has explicitly
        // overridden it via references_available_on_request.
        $renderCv = $cv;
        $hasReferences = collect($cv['references'] ?? [])
            ->contains(fn ($row) => is_array($row) && trim(implode('', array_filter($row))) !== '');

        if ($session->references_available_on_request || ! $hasReferences) {
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
        $zip->addFromString('word/styles.xml', $design === 'executive' ? $this->docxStylesExecutive() : $this->docxStyles());
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

        if ($design === 'executive') {
            return $this->docxDocumentExecutive($cv);
        } elseif ($design === 'academic') {
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
            ! empty($cv['phone'])    ? 'Tel: ' . $cv['phone']    : '',
            (! empty($cv['whatsapp']) && ($cv['whatsapp'] !== ($cv['phone'] ?? ''))) ? 'WA: ' . $cv['whatsapp'] : (empty($cv['phone']) && ! empty($cv['whatsapp']) ? 'WA: ' . $cv['whatsapp'] : ''),
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

    private function docxDocumentExecutive(array $cv): string
    {
        $body = [];

        // Credentials line: top education entry + first certification name
        $topEd = $cv['education'][0] ?? [];
        $credParts = array_filter([
            trim(implode(' (', array_filter([$topEd['qualification'] ?? '', $topEd['institution'] ?? '']))) . (! empty($topEd['institution']) ? ')' : ''),
            is_array($cv['certifications'][0] ?? null) ? (string) ($cv['certifications'][0]['name'] ?? '') : (string) ($cv['certifications'][0] ?? ''),
        ]);
        $credentials = implode('  •  ', array_filter($credParts));

        $_phone    = trim($cv['phone'] ?? '');
        $_whatsapp = trim($cv['whatsapp'] ?? '');
        $contactItems = array_values(array_filter([
            $cv['location'] ?? ($cv['address'] ?? ''),
            $_phone !== ''    ? 'Tel: ' . $_phone    : '',
            ($_whatsapp !== '' && $_whatsapp !== $_phone) ? 'WA: ' . $_whatsapp : ($_whatsapp !== '' && $_phone === '' ? 'WA: ' . $_whatsapp : ''),
            $cv['email'] ?? '',
            ! empty($cv['linkedin']) ? $cv['linkedin'] : '',
            ! empty($cv['marital_status']) ? $cv['marital_status'] : '',
        ]));

        $body[] = $this->docxExecHeader(
            strtoupper(trim((string) ($cv['full_name'] ?? 'CANDIDATE'))),
            (string) ($cv['headline'] ?? ''),
            $credentials,
            $contactItems
        );
        $body[] = '<w:p><w:pPr><w:spacing w:after="120" w:before="0"/></w:pPr></w:p>';

        if (! empty($cv['summary'])) {
            $body[] = $this->docxExecSectionTitle('PROFESSIONAL PROFILE');
            $body[] = $this->docxExecBody((string) $cv['summary']);
            $body[] = '<w:p><w:pPr><w:spacing w:after="60"/></w:pPr></w:p>';
        }

        if (! empty($cv['experience'])) {
            $body[] = $this->docxExecSectionTitle('PROFESSIONAL EXPERIENCE');
            foreach ($cv['experience'] as $row) {
                $body[] = $this->docxExecJobTitle((string) ($row['job_title'] ?? ''));
                $dates = trim(implode(' \u{2013} ', array_filter([$row['start_date'] ?? '', $row['end_date'] ?? ''])));
                $body[] = $this->docxExecCompanyLine((string) ($row['company'] ?? ''), $dates);
                foreach ((array) ($row['responsibilities'] ?? []) as $resp) {
                    $body[] = $this->docxExecBullet((string) $resp);
                }
                $body[] = '<w:p><w:pPr><w:spacing w:after="60"/></w:pPr></w:p>';
            }
        }

        if (! empty($cv['education'])) {
            $body[] = $this->docxExecSectionTitle('EDUCATION');
            foreach ($cv['education'] as $row) {
                $qual = trim(implode(', ', array_filter([$row['qualification'] ?? '', $row['field'] ?? ''])));
                $years = trim(implode(' \u{2013} ', array_filter([$row['start_year'] ?? '', $row['end_year'] ?? ''])));
                $body[] = $this->docxExecJobTitle($qual);
                $body[] = $this->docxExecCompanyLine((string) ($row['institution'] ?? ''), $years);
                $body[] = '<w:p><w:pPr><w:spacing w:after="40"/></w:pPr></w:p>';
            }
        }

        if (! empty($cv['certifications'])) {
            $body[] = $this->docxExecSectionTitle('CERTIFICATIONS AND TRAINING');
            foreach ($cv['certifications'] as $cert) {
                $text = is_array($cert)
                    ? trim(implode(' \u{2014} ', array_filter([$cert['name'] ?? '', $cert['issuing_body'] ?? '', $cert['date'] ?? ''])))
                    : (string) $cert;
                $body[] = $this->docxExecBullet($text);
            }
            $body[] = '<w:p><w:pPr><w:spacing w:after="60"/></w:pPr></w:p>';
        }

        if (! empty($cv['projects'])) {
            $body[] = $this->docxExecSectionTitle('PROJECTS AND VOLUNTEER WORK');
            foreach ($cv['projects'] as $row) {
                $text = trim(($row['name'] ?? '') . (($row['description'] ?? '') ? ': ' . $row['description'] : ''));
                if ($text !== '') {
                    $body[] = $this->docxExecBullet($text);
                }
            }
            $body[] = '<w:p><w:pPr><w:spacing w:after="60"/></w:pPr></w:p>';
        }

        if (! empty($cv['skills'])) {
            $body[] = $this->docxExecSectionTitle('KEY SKILLS');
            $chunks = array_chunk($cv['skills'], 3);
            foreach ($chunks as $chunk) {
                $body[] = $this->docxExecBullet(implode('     ', $chunk));
            }
            $body[] = '<w:p><w:pPr><w:spacing w:after="60"/></w:pPr></w:p>';
        }

        $languages = collect($cv['languages'] ?? [])
            ->map(fn ($row) => is_array($row) ? trim(implode(' \u{2013} ', array_filter([$row['language'] ?? '', $row['proficiency'] ?? '']))) : (string) $row)
            ->filter()->all();

        if (! empty($languages)) {
            $body[] = $this->docxExecSectionTitle('LANGUAGES');
            foreach ($languages as $lang) {
                $body[] = $this->docxExecBullet($lang);
            }
            $body[] = '<w:p><w:pPr><w:spacing w:after="60"/></w:pPr></w:p>';
        }

        $refs = collect($cv['references'] ?? [])
            ->map(fn ($row) => is_array($row)
                ? implode('  |  ', array_filter([$row['name'] ?? '', $row['role'] ?? '', $row['company'] ?? '', $row['phone'] ?? '', $row['email'] ?? '']))
                : trim((string) $row))
            ->filter()->all();

        if (! empty($refs)) {
            $body[] = $this->docxExecSectionTitle('REFERENCES');
            foreach ($refs as $ref) {
                $body[] = $this->docxExecBullet($ref);
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" mc:Ignorable="w14 wp14">'
            . '<w:body>' . implode('', $body)
            . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="720" w:right="720" w:bottom="1008" w:left="720" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
            . '</w:body></w:document>';
    }

    private function docxExecHeader(string $name, string $headline, string $credentials, array $contactItems): string
    {
        $icons = ['📍', '📞', '✉', '🔗', '🪪'];
        $leftCell = '<w:p><w:pPr><w:spacing w:after="80" w:before="0"/></w:pPr>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:b/><w:bCs/><w:color w:val="FFFFFF"/><w:sz w:val="52"/><w:szCs w:val="52"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->xml($name) . '</w:t></w:r></w:p>';

        if ($headline !== '') {
            $leftCell .= '<w:p><w:pPr><w:spacing w:after="60" w:before="0"/></w:pPr>'
                . '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:color w:val="C9A84C"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr>'
                . '<w:t xml:space="preserve">' . $this->xml($headline) . '</w:t></w:r></w:p>';
        }

        if ($credentials !== '') {
            $leftCell .= '<w:p><w:pPr><w:spacing w:after="0" w:before="60"/></w:pPr>'
                . '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:color w:val="CCCCCC"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr>'
                . '<w:t xml:space="preserve">' . $this->xml($credentials) . '</w:t></w:r></w:p>';
        }

        $rightCell = '';
        foreach (array_values($contactItems) as $i => $item) {
            $icon = $icons[$i] ?? '•';
            $rightCell .= '<w:p><w:pPr><w:spacing w:after="40" w:before="40"/></w:pPr>'
                . '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:color w:val="DDDDDD"/><w:sz w:val="17"/><w:szCs w:val="17"/></w:rPr>'
                . '<w:t xml:space="preserve">' . $this->xml($icon . ' ' . $item) . '</w:t></w:r></w:p>';
        }

        return '<w:tbl>'
            . '<w:tblPr><w:tblW w:type="dxa" w:w="9638"/>'
            . '<w:tblBorders><w:top w:val="none"/><w:left w:val="none"/><w:bottom w:val="none"/><w:right w:val="none"/><w:insideH w:val="none"/><w:insideV w:val="none"/></w:tblBorders>'
            . '</w:tblPr>'
            . '<w:tblGrid><w:gridCol w:w="6838"/><w:gridCol w:w="2800"/></w:tblGrid>'
            . '<w:tr>'
            . '<w:tc><w:tcPr><w:tcW w:type="dxa" w:w="6838"/><w:shd w:fill="1B3A6B" w:val="clear"/>'
            . '<w:tcMar><w:top w:type="dxa" w:w="240"/><w:left w:type="dxa" w:w="280"/><w:bottom w:type="dxa" w:w="240"/><w:right w:type="dxa" w:w="160"/></w:tcMar>'
            . '<w:vAlign w:val="center"/></w:tcPr>' . $leftCell . '</w:tc>'
            . '<w:tc><w:tcPr><w:tcW w:type="dxa" w:w="2800"/><w:shd w:fill="142D55" w:val="clear"/>'
            . '<w:tcMar><w:top w:type="dxa" w:w="200"/><w:left w:type="dxa" w:w="200"/><w:bottom w:type="dxa" w:w="200"/><w:right w:type="dxa" w:w="200"/></w:tcMar>'
            . '<w:vAlign w:val="center"/></w:tcPr>' . ($rightCell ?: '<w:p/>') . '</w:tc>'
            . '</w:tr></w:tbl>';
    }

    private function docxExecSectionTitle(string $title): string
    {
        return '<w:p><w:pPr><w:spacing w:after="60" w:before="200"/></w:pPr>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:b/><w:bCs/><w:color w:val="1B3A6B"/><w:spacing w:val="40"/><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->xml($title) . '</w:t></w:r></w:p>'
            . '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:color="C9A84C" w:sz="8" w:space="1"/></w:pBdr><w:spacing w:after="80" w:before="0"/></w:pPr></w:p>';
    }

    private function docxExecJobTitle(string $title): string
    {
        return '<w:p><w:pPr><w:spacing w:after="30" w:before="160"/></w:pPr>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:b/><w:bCs/><w:color w:val="1B3A6B"/><w:sz w:val="21"/><w:szCs w:val="21"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->xml($title) . '</w:t></w:r></w:p>';
    }

    private function docxExecCompanyLine(string $company, string $dates): string
    {
        $companyRun = '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:b/><w:bCs/><w:color w:val="C9A84C"/><w:sz w:val="19"/><w:szCs w:val="19"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->xml($company) . '</w:t></w:r>';
        $datesRun = $dates !== ''
            ? '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:i/><w:iCs/><w:color w:val="666666"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr>'
                . '<w:t xml:space="preserve">&#9;' . $this->xml($dates) . '</w:t></w:r>'
            : '';

        return '<w:p><w:pPr><w:tabs><w:tab w:val="right" w:pos="9638"/></w:tabs><w:spacing w:after="60" w:before="0"/></w:pPr>'
            . $companyRun . $datesRun . '</w:p>';
    }

    private function docxExecBullet(string $text): string
    {
        return '<w:p><w:pPr><w:spacing w:after="40" w:before="40"/></w:pPr>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:color w:val="2C2C2C"/><w:sz w:val="19"/><w:szCs w:val="19"/></w:rPr>'
            . '<w:t xml:space="preserve">• ' . $this->xml($text) . '</w:t></w:r></w:p>';
    }

    private function docxExecBody(string $text): string
    {
        return '<w:p><w:pPr><w:spacing w:after="140" w:before="100"/></w:pPr>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:color w:val="2C2C2C"/><w:sz w:val="19"/><w:szCs w:val="19"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->xml($text) . '</w:t></w:r></w:p>';
    }

    private function docxStylesExecutive(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr>'
            . '<w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>'
            . '<w:sz w:val="20"/><w:szCs w:val="20"/>'
            . '<w:color w:val="2C2C2C"/>'
            . '</w:rPr></w:rPrDefault><w:pPrDefault><w:pPr>'
            . '<w:spacing w:line="276" w:lineRule="auto" w:after="100"/>'
            . '</w:pPr></w:pPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:styleId="ListParagraph"><w:name w:val="List Paragraph"/></w:style>'
            . '</w:styles>';
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
