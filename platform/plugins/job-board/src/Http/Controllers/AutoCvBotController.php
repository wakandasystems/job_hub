<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\AutoCvSession;
use Botble\JobBoard\Models\SalesAgent;
use Botble\JobBoard\Services\AutoCvBotService;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

        $personaImageUrl = $this->settingImageUrl('auto_cv_bot_persona_image');
        $confirmationImageUrl = $this->settingImageUrl('auto_cv_bot_confirmation_image');
        $aiModel = AutoCvBotService::aiModel();
        $aiModelOptions = AutoCvBotService::availableAiModels();

        return view(
            'plugins/job-board::auto-cv-bot.index',
            compact('sessions', 'webhookUrl', 'stats', 'personaImageUrl', 'confirmationImageUrl', 'aiModel', 'aiModelOptions')
        );
    }

    public function updateAiModel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'model' => ['required', 'string', Rule::in(AutoCvBotService::availableAiModels())],
        ]);

        setting()->set('auto_cv_bot_ai_model', $data['model'])->save();

        return response()->json(['message' => 'AI model updated.']);
    }

    public function uploadPersonaImage(Request $request): JsonResponse
    {
        return $this->uploadSettingImage($request, 'auto_cv_bot_persona_image', 'Assistant image updated.');
    }

    public function uploadConfirmationImage(Request $request): JsonResponse
    {
        return $this->uploadSettingImage($request, 'auto_cv_bot_confirmation_image', 'Confirmation message image updated.');
    }

    private function uploadSettingImage(Request $request, string $settingKey, string $successMessage): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $result = RvMedia::handleUpload($request->file('image'), 0, 'auto-cv-bot');

        if ($result['error']) {
            return response()->json(['error' => $result['message']], 422);
        }

        $file = $result['data'];

        setting()->set($settingKey, $file->url)->save();

        return response()->json([
            'message' => $successMessage,
            'url' => RvMedia::getImageUrl($file->url),
        ]);
    }

    private function settingImageUrl(string $settingKey): ?string
    {
        $url = trim((string) setting($settingKey, ''));

        return $url !== '' ? RvMedia::getImageUrl($url) : null;
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

    public function updateCvField(AutoCvSession $autoCvSession, Request $request, AutoCvBotService $service): JsonResponse
    {
        $data = $request->validate([
            'field' => ['required', 'string', 'max:190', 'regex:/^[a-z_]+(\.\d+(\.[a-z_]+)?)?$/'],
            'value' => ['nullable', 'string', 'max:5000'],
        ]);

        $scalarFields = [
            'full_name', 'headline', 'phone', 'email', 'location', 'address',
            'age', 'marital_status', 'linkedin', 'summary',
        ];

        $rowFields = [
            'education' => ['qualification', 'institution'],
            'experience' => ['job_title', 'company', 'start_date', 'end_date'],
            'projects' => ['name', 'description', 'link'],
            'references' => ['name', 'role', 'company', 'phone', 'email'],
            'languages' => ['language', 'proficiency'],
            'certifications' => ['name'],
        ];

        $listFields = ['skills', 'certifications'];

        $field = $data['field'];
        $allowed = in_array($field, $scalarFields, true);

        if (! $allowed && preg_match('/^([a-z_]+)\.(\d+)\.([a-z_]+)$/', $field, $matches)) {
            $allowed = in_array($matches[3], $rowFields[$matches[1]] ?? [], true);
        }

        if (! $allowed && preg_match('/^([a-z_]+)\.(\d+)$/', $field, $matches)) {
            $allowed = in_array($matches[1], $listFields, true);
        }

        if (! $allowed) {
            return response()->json(['error' => 'This field cannot be edited.'], 422);
        }

        $cv = $autoCvSession->structured_cv ?: [];
        Arr::set($cv, $field, trim((string) ($data['value'] ?? '')));
        $autoCvSession->forceFill(['structured_cv' => $cv])->save();
        $service->refreshDerivedSessionData($autoCvSession);

        return response()->json([
            'message' => 'Saved.',
            'cv_html' => view('plugins/job-board::auto-cv-bot._cv_preview', ['session' => $autoCvSession])->render(),
            'job_positions_html' => view('plugins/job-board::auto-cv-bot._job_positions', ['session' => $autoCvSession])->render(),
            'improve_html' => view('plugins/job-board::auto-cv-bot._improve', ['session' => $autoCvSession])->render(),
        ]);
    }

    public function clearCvSection(AutoCvSession $autoCvSession, Request $request, AutoCvBotService $service): JsonResponse
    {
        $sectionScalarFields = [
            'name' => ['full_name'],
            'headline' => ['headline'],
            'contact' => ['phone', 'email', 'location', 'address', 'age', 'marital_status', 'linkedin'],
            'summary' => ['summary'],
        ];

        $sectionArrayFields = ['education', 'experience', 'projects', 'skills', 'certifications', 'languages', 'references'];

        $data = $request->validate([
            'section' => ['required', 'string', Rule::in(array_merge(array_keys($sectionScalarFields), $sectionArrayFields, ['photo']))],
        ]);

        $section = $data['section'];

        if ($section === 'photo') {
            $autoCvSession->forceFill(['candidate_photo_path' => null])->save();
            $service->refreshDerivedSessionData($autoCvSession);

            return response()->json([
                'message' => 'Section cleared.',
                'cv_html' => view('plugins/job-board::auto-cv-bot._cv_preview', ['session' => $autoCvSession])->render(),
                'job_positions_html' => view('plugins/job-board::auto-cv-bot._job_positions', ['session' => $autoCvSession])->render(),
                'improve_html' => view('plugins/job-board::auto-cv-bot._improve', ['session' => $autoCvSession])->render(),
            ]);
        }

        $cv = $autoCvSession->structured_cv ?: [];

        if (isset($sectionScalarFields[$section])) {
            foreach ($sectionScalarFields[$section] as $field) {
                $cv[$field] = '';
            }
        } else {
            $cv[$section] = [];
        }

        $autoCvSession->forceFill(['structured_cv' => $cv])->save();
        $service->refreshDerivedSessionData($autoCvSession);

        return response()->json([
            'message' => 'Section cleared.',
            'cv_html' => view('plugins/job-board::auto-cv-bot._cv_preview', ['session' => $autoCvSession])->render(),
            'job_positions_html' => view('plugins/job-board::auto-cv-bot._job_positions', ['session' => $autoCvSession])->render(),
            'improve_html' => view('plugins/job-board::auto-cv-bot._improve', ['session' => $autoCvSession])->render(),
        ]);
    }

    public function toggleReferencesAvailableOnRequest(AutoCvSession $autoCvSession, Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $autoCvSession->forceFill(['references_available_on_request' => $data['enabled']])->save();

        return response()->json([
            'message' => $data['enabled']
                ? 'Generated CVs will show "Available on request" for references.'
                : 'Generated CVs will show the listed references again.',
        ]);
    }

    public function destroy(AutoCvSession $autoCvSession): JsonResponse
    {
        Storage::disk('local')->deleteDirectory('auto-cv-bot/session-' . $autoCvSession->getKey());
        $autoCvSession->delete();

        return response()->json(['message' => 'CV bot session deleted.']);
    }

    private function sessionStats(): array
    {
        $sessions = AutoCvSession::query()->get(['status', 'topics', 'topics_covered', 'answers', 'created_at', 'completed_at']);
        $total = $sessions->count();
        $collecting = $sessions->where('status', 'collecting')->count();
        $paused = $sessions->where('status', 'paused')->count();
        $failed = $sessions->where('status', 'failed')->count();
        $stalled = $sessions->where('status', 'stalled')->count();
        $completed = $sessions->where('status', 'completed')->count();
        $averageProgress = $sessions
            ->map(function (AutoCvSession $session) {
                $totalTopics = count($session->topics ?: []);

                return $totalTopics > 0 ? (count($session->topics_covered ?: []) / $totalTopics) * 100 : 0;
            })
            ->avg();

        // How many back-and-forth turns a real interview takes, and how long it takes wall-clock
        // (mostly candidate response time, not bot processing) — without this there's no way to
        // tell whether a prompt change actually makes the interview shorter or just feels shorter.
        $averageTurns = $sessions->map(fn (AutoCvSession $session) => count($session->answers ?: []))->avg();

        $completedSessions = $sessions->where('status', 'completed')->filter(fn (AutoCvSession $session) => $session->completed_at);
        $averageMinutesToComplete = $completedSessions
            ->map(fn (AutoCvSession $session) => $session->created_at->diffInMinutes($session->completed_at))
            ->avg();

        return [
            'total' => $total,
            'collecting' => $collecting,
            'paused' => $paused,
            'failed' => $failed,
            'stalled' => $stalled,
            'completed' => $completed,
            'average_progress' => $total > 0 ? (int) round($averageProgress) : 0,
            'average_turns' => $total > 0 ? round($averageTurns, 1) : 0,
            'average_time_to_complete' => $averageMinutesToComplete ? $this->formatMinutes((int) round($averageMinutesToComplete)) : '—',
        ];
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes}m";
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 24) {
            return "{$hours}h " . ($minutes % 60) . 'm';
        }

        return intdiv($hours, 24) . 'd ' . ($hours % 24) . 'h';
    }

    public function start(Request $request, AutoCvBotService $service): JsonResponse
    {
        $data = $request->validate([
            'whatsapp_number' => ['required', 'string', 'max:40'],
            'candidate_name' => ['nullable', 'string', 'max:150'],
            'sales_agent_code' => ['nullable', 'string', 'max:30'],
        ]);

        [$session, $error] = $service->startSession(
            trim($data['whatsapp_number']),
            trim((string) ($data['candidate_name'] ?? '')) ?: null,
            Auth::id(),
            trim((string) ($data['sales_agent_code'] ?? '')) ?: null
        );

        if ($error) {
            return response()->json(['error' => $error], 422);
        }

        return response()->json([
            'message' => 'CV bot started. Opening question sent on WhatsApp.',
            'redirect' => route('job-board.auto-cv-bot.show', $session->getKey()),
        ]);
    }

    public function searchAgents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = SalesAgent::query()
            ->select(['id', 'name', 'code', 'phone', 'status'])
            ->orderBy('name');

        $term = trim((string) ($data['q'] ?? ''));

        if ($term !== '') {
            $query->where(function ($subQuery) use ($term): void {
                $subQuery
                    ->where('name', 'like', '%' . $term . '%')
                    ->orWhere('code', 'like', '%' . $term . '%')
                    ->orWhere('phone', 'like', '%' . $term . '%');
            });
        }

        $agents = $query
            ->paginate(3, ['*'], 'page', max(1, (int) ($data['page'] ?? 1)))
            ->through(function (SalesAgent $agent): array {
                return [
                    'id' => $agent->getKey(),
                    'name' => $agent->name,
                    'code' => $agent->code,
                    'phone' => $agent->phone,
                    'status' => $agent->status,
                ];
            });

        return response()->json([
            'data' => $agents->items(),
            'meta' => [
                'current_page' => $agents->currentPage(),
                'last_page' => $agents->lastPage(),
                'per_page' => $agents->perPage(),
                'total' => $agents->total(),
            ],
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

        app(AutoCvBotService::class)->refreshDerivedSessionData($autoCvSession);
        $autoCvSession->load(['messages' => fn ($query) => $query->oldest()]);

        return view('plugins/job-board::auto-cv-bot.show', ['session' => $autoCvSession]);
    }

    public function poll(AutoCvSession $autoCvSession): JsonResponse
    {
        app(AutoCvBotService::class)->refreshDerivedSessionData($autoCvSession);
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

    public function requestCvPhoto(AutoCvSession $autoCvSession, AutoCvBotService $service): JsonResponse
    {
        $sent = $service->requestCvPhoto($autoCvSession);

        if (! $sent) {
            return response()->json(['error' => 'Could not send the photo request on WhatsApp.'], 422);
        }

        return response()->json([
            'message' => 'Photo request sent on WhatsApp. The candidate can reply with a photo.',
        ]);
    }

    public function requestCvUpload(AutoCvSession $autoCvSession, AutoCvBotService $service): JsonResponse
    {
        $sent = $service->requestCvUpload($autoCvSession);

        if (! $sent) {
            return response()->json(['error' => 'Could not send the CV request on WhatsApp.'], 422);
        }

        return response()->json([
            'message' => 'Asked the candidate again for their CV on WhatsApp.',
        ]);
    }

    public function uploadCv(AutoCvSession $autoCvSession, Request $request, AutoCvBotService $service): JsonResponse
    {
        $data = $request->validate([
            'cv_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:20480'],
        ]);

        $result = $service->processAdminUploadedCv($autoCvSession, $data['cv_file']);

        if (! $result['success']) {
            return response()->json(['error' => $result['message']], 422);
        }

        return response()->json(['message' => $result['message']]);
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

    public function requestFinalConfirmation(AutoCvSession $autoCvSession, AutoCvBotService $service): JsonResponse
    {
        $sent = $service->requestFinalConfirmation($autoCvSession);

        if (! $sent) {
            return response()->json(['error' => 'Could not send the confirmation check-in on WhatsApp.'], 422);
        }

        return response()->json([
            'message' => 'Confirmation check-in sent on WhatsApp. The candidate can reply DONE or send more details.',
        ]);
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
