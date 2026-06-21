<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\AutoCvSession;
use Botble\JobBoard\Services\AutoCvBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AutoCvBotController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('CV Bot', route('job-board.auto-cv-bot.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('CV Bot');

        $sessions = AutoCvSession::query()->latest()->paginate(10)->withQueryString();

        $webhookSecret = (string) setting('auto_cv_webhook_secret', '');
        if ($webhookSecret === '') {
            $webhookSecret = Str::random(40);
            setting()->set('auto_cv_webhook_secret', $webhookSecret)->save();
        }

        $webhookUrl = route('public.auto-cv-bot-webhook', $webhookSecret);

        $stats = $this->sessionStats();

        return view('plugins/job-board::auto-cv-bot.index', compact('sessions', 'webhookUrl', 'stats'));
    }

    public function pollSessions(Request $request): JsonResponse
    {
        $sessions = AutoCvSession::query()
            ->latest()
            ->paginate(10, ['*'], 'page', max(1, (int) $request->integer('page', 1)))
            ->withQueryString();

        return response()->json([
            'stats_html' => view('plugins/job-board::auto-cv-bot._session_stats', ['stats' => $this->sessionStats()])->render(),
            'rows_html' => view('plugins/job-board::auto-cv-bot._session_rows', ['sessions' => $sessions])->render(),
            'pagination_html' => view('plugins/job-board::auto-cv-bot._session_pagination', ['sessions' => $sessions])->render(),
        ]);
    }

    public function pause(AutoCvSession $autoCvSession): JsonResponse
    {
        if (! in_array($autoCvSession->status, ['completed', 'paused'], true)) {
            $autoCvSession->forceFill(['status' => 'paused'])->save();
        }

        return response()->json(['message' => 'CV bot session paused.']);
    }

    public function resume(AutoCvSession $autoCvSession): JsonResponse
    {
        if ($autoCvSession->status === 'paused') {
            $autoCvSession->forceFill([
                'status' => 'collecting',
                'candidate_reminder_count' => 0,
                'last_candidate_reminder_sent_at' => null,
            ])->save();
        }

        return response()->json(['message' => 'CV bot session resumed.']);
    }

    public function destroy(AutoCvSession $autoCvSession): JsonResponse
    {
        Storage::disk('local')->deleteDirectory('auto-cv-bot/session-' . $autoCvSession->getKey());
        $autoCvSession->delete();

        return response()->json(['message' => 'CV bot session deleted.']);
    }

    private function sessionStats(): array
    {
        $sessions = AutoCvSession::query()->get(['status', 'topics', 'topics_covered']);
        $total = $sessions->count();
        $collecting = $sessions->where('status', 'collecting')->count();
        $paused = $sessions->where('status', 'paused')->count();
        $failed = $sessions->where('status', 'failed')->count();
        $completed = $sessions->where('status', 'completed')->count();
        $averageProgress = $sessions
            ->map(function (AutoCvSession $session) {
                $totalTopics = count($session->topics ?: []);

                return $totalTopics > 0 ? (count($session->topics_covered ?: []) / $totalTopics) * 100 : 0;
            })
            ->avg();

        return [
            'total' => $total,
            'collecting' => $collecting,
            'paused' => $paused,
            'failed' => $failed,
            'completed' => $completed,
            'average_progress' => $total > 0 ? (int) round($averageProgress) : 0,
        ];
    }

    public function start(Request $request, AutoCvBotService $service): JsonResponse
    {
        $data = $request->validate([
            'whatsapp_number' => ['required', 'string', 'max:40'],
            'candidate_name' => ['nullable', 'string', 'max:150'],
        ]);

        [$session, $error] = $service->startSession(
            trim($data['whatsapp_number']),
            trim((string) ($data['candidate_name'] ?? '')) ?: null,
            Auth::id()
        );

        if ($error) {
            return response()->json(['error' => $error], 422);
        }

        return response()->json([
            'message' => 'CV bot started. Opening question sent on WhatsApp.',
            'redirect' => route('job-board.auto-cv-bot.show', $session->getKey()),
        ]);
    }

    public function sendSampleCv(Request $request, AutoCvBotService $service): JsonResponse
    {
        $data = $request->validate([
            'whatsapp_number' => ['required', 'string', 'max:40'],
        ]);

        [$sent, $error] = $service->sendSampleCv(trim($data['whatsapp_number']));

        if (! $sent) {
            return response()->json(['error' => $error ?: 'Could not send the sample CV.'], 422);
        }

        return response()->json(['message' => 'Sample CV sent on WhatsApp.']);
    }

    public function show(AutoCvSession $autoCvSession)
    {
        $this->pageTitle('CV Bot — ' . ($autoCvSession->candidate_name ?: $autoCvSession->whatsapp_number));

        $autoCvSession->load(['messages' => fn ($query) => $query->oldest()]);

        return view('plugins/job-board::auto-cv-bot.show', ['session' => $autoCvSession]);
    }

    public function poll(AutoCvSession $autoCvSession): JsonResponse
    {
        $autoCvSession->load(['messages' => fn ($query) => $query->oldest()]);

        return response()->json([
            'status' => $autoCvSession->status,
            'message_count' => $autoCvSession->messages->count(),
            'banner_html' => view('plugins/job-board::auto-cv-bot._banner', ['session' => $autoCvSession])->render(),
            'transcript_html' => view('plugins/job-board::auto-cv-bot._transcript', ['session' => $autoCvSession])->render(),
            'cv_html' => view('plugins/job-board::auto-cv-bot._cv_preview', ['session' => $autoCvSession])->render(),
            'improve_html' => view('plugins/job-board::auto-cv-bot._improve', ['session' => $autoCvSession])->render(),
            'job_positions_html' => view('plugins/job-board::auto-cv-bot._job_positions', ['session' => $autoCvSession])->render(),
            'downloads_html' => view('plugins/job-board::auto-cv-bot._downloads', ['session' => $autoCvSession])->render(),
            'has_downloads' => (bool) ($autoCvSession->docx_path || $autoCvSession->pdf_path),
            'ai_usage_html' => view('plugins/job-board::auto-cv-bot._ai_usage', ['session' => $autoCvSession])->render(),
        ]);
    }

    public function resendQuestion(AutoCvSession $autoCvSession, AutoCvBotService $service): JsonResponse
    {
        $sent = $service->resendCurrentQuestion($autoCvSession);

        if (! $sent) {
            return response()->json(['error' => 'Could not resend — session is not awaiting a reply, or WhatsApp send failed.'], 422);
        }

        return response()->json(['message' => 'Question resent on WhatsApp.']);
    }

    public function requestSectionInformation(AutoCvSession $autoCvSession, Request $request, AutoCvBotService $service): JsonResponse
    {
        $data = $request->validate([
            'topic_number' => ['required', 'integer', 'between:1,12'],
        ]);

        $sent = $service->requestSectionInformation($autoCvSession, (int) $data['topic_number']);

        if (! $sent) {
            return response()->json(['error' => 'Could not send the section request on WhatsApp.'], 422);
        }

        return response()->json([
            'message' => 'Section request sent on WhatsApp. The candidate can reply with more details.',
        ]);
    }

    public function retryGeneration(AutoCvSession $autoCvSession, AutoCvBotService $service): JsonResponse
    {
        $completed = $service->retryDocumentGeneration($autoCvSession);

        if (! $completed) {
            return response()->json(['error' => 'Could not regenerate the CV. Check the copied diagnostic details.'], 422);
        }

        return response()->json(['message' => 'CV regenerated successfully.']);
    }

    public function generateDocuments(AutoCvSession $autoCvSession, AutoCvBotService $service): JsonResponse
    {
        $generated = $service->generateDocumentsNow($autoCvSession);

        if (! $generated) {
            return response()->json(['error' => 'Could not generate a CV yet. The session needs at least some usable CV details.'], 422);
        }

        $autoCvSession->refresh();

        return response()->json([
            'message' => 'Premium CV documents generated.',
            'downloads_html' => view('plugins/job-board::auto-cv-bot._downloads', ['session' => $autoCvSession])->render(),
            'has_downloads' => (bool) ($autoCvSession->docx_path || $autoCvSession->pdf_path),
            'ai_usage_html' => view('plugins/job-board::auto-cv-bot._ai_usage', ['session' => $autoCvSession])->render(),
        ]);
    }

    public function askCandidateToResend(AutoCvSession $autoCvSession, AutoCvBotService $service): JsonResponse
    {
        $sent = $service->askCandidateToResendLastMessage($autoCvSession);

        if (! $sent) {
            return response()->json(['error' => 'Could not ask the candidate to resend their last message.'], 422);
        }

        return response()->json(['message' => 'Asked the candidate to resend their last message.']);
    }

    public function continueInterview(AutoCvSession $autoCvSession, AutoCvBotService $service): JsonResponse
    {
        $sent = $service->continueInterview($autoCvSession);

        if (! $sent) {
            return response()->json(['error' => 'Could not find a question to continue the interview.'], 422);
        }

        return response()->json(['message' => 'Interview reopened and the last question was resent.']);
    }

    public function endConversation(AutoCvSession $autoCvSession, Request $request, AutoCvBotService $service): JsonResponse
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $completed = $service->endConversationNow($autoCvSession, $data['message'] ?? null);

        if (! $completed) {
            return response()->json(['error' => $autoCvSession->fresh()->error_message ?: 'Could not end the conversation on WhatsApp.'], 422);
        }

        $autoCvSession->refresh();

        return response()->json([
            'message' => 'Conversation ended and CV generated.',
            'banner_html' => view('plugins/job-board::auto-cv-bot._banner', ['session' => $autoCvSession])->render(),
            'transcript_html' => view('plugins/job-board::auto-cv-bot._transcript', ['session' => $autoCvSession])->render(),
            'downloads_html' => view('plugins/job-board::auto-cv-bot._downloads', ['session' => $autoCvSession])->render(),
            'has_downloads' => (bool) ($autoCvSession->docx_path || $autoCvSession->pdf_path),
            'ai_usage_html' => view('plugins/job-board::auto-cv-bot._ai_usage', ['session' => $autoCvSession])->render(),
        ]);
    }

    public function sendDocuments(AutoCvSession $autoCvSession, AutoCvBotService $service): JsonResponse
    {
        $sent = $service->sendDocumentsToCandidate($autoCvSession);

        if (! $sent) {
            return response()->json(['error' => $autoCvSession->fresh()->error_message ?: 'Could not send CV documents to the candidate.'], 422);
        }

        return response()->json(['message' => 'CV documents sent to the candidate.']);
    }

    public function download(AutoCvSession $autoCvSession, string $format, ?string $design = null)
    {
        abort_unless(in_array($format, ['docx', 'pdf'], true), 404);

        $design = $design ?: 'premium';
        $paths = $autoCvSession->cv_document_paths ?: [];
        $path = $paths[$design][$format . '_path'] ?? null;
        $path = $path ?: ($format === 'docx' ? $autoCvSession->docx_path : $autoCvSession->pdf_path);

        abort_unless($path && Storage::disk('local')->exists($path), 404, 'CV file not found.');

        $name = (string) ($autoCvSession->structured_cv['full_name'] ?? $autoCvSession->candidate_name ?? 'Candidate');
        $safeName = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', trim($name)), '_') ?: 'Candidate';

        return Storage::disk('local')->download($path, "{$safeName}_CV_" . Str::headline($design) . ".{$format}");
    }

    public function preview(AutoCvSession $autoCvSession, ?string $design = null)
    {
        $design = $design ?: 'premium';
        $paths = $autoCvSession->cv_document_paths ?: [];
        $path = $paths[$design]['pdf_path'] ?? null;
        $path = $path ?: $autoCvSession->pdf_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404, 'CV file not found.');

        return Storage::disk('local')->response($path, null, [], 'inline');
    }
}
