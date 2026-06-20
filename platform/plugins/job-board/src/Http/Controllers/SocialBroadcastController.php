<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Jobs\SendSocialBroadcastJob;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Models\SocialBroadcast;
use Botble\JobBoard\Services\BroadcastRecurrenceService;
use Botble\JobBoard\Services\SocialPublisherService;
use Botble\JobBoard\Supports\EmployerContactAudience;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SocialBroadcastController extends BaseController
{
    private const PLATFORMS = ['facebook', 'linkedin', 'whatsapp', 'whapi', 'publer'];

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/job-board::job-board.name'))
            ->add('Broadcast', route('job-board.automations.broadcast'));
    }

    public function index(EmployerContactAudience $audience)
    {
        $this->pageTitle('Broadcast — Post to All Channels');

        $channels = SocialAutomation::query()
            ->whereIn('platform', self::PLATFORMS)
            ->where('is_active', true)
            ->orderBy('platform')
            ->get(['id', 'platform', 'name']);

        $broadcasts = SocialBroadcast::query()
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $employerPhoneCount = $audience->phones()->count();
        $hasWhapi = $channels->contains('platform', 'whapi');

        return view('plugins/job-board::broadcasts.index', compact(
            'channels',
            'broadcasts',
            'employerPhoneCount',
            'hasWhapi'
        ));
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
        ]);

        $path = $request->file('image')->store('social-broadcasts', 'public');
        $url  = Storage::disk('public')->url($path);

        return response()->json(['ok' => true, 'url' => $url, 'path' => $path]);
    }

    public function employerContacts(Request $request, EmployerContactAudience $audience): JsonResponse
    {
        $perPage = 20;
        $page = max(1, $request->integer('page', 1));
        $contacts = $audience->phones();
        $total = $contacts->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        return response()->json([
            'data' => $contacts
                ->slice(($page - 1) * $perPage, $perPage)
                ->values()
                ->map(fn ($contact) => [
                    'name' => $contact->name,
                    'phone' => $contact->phone,
                    'country_code' => $contact->country_code,
                    'country_name' => $contact->country_name,
                    'edit_url' => $contact->edit_url,
                ]),
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total ? (($page - 1) * $perPage) + 1 : 0,
                'to' => min($page * $perPage, $total),
            ],
        ]);
    }

    public function send(Request $request, BroadcastRecurrenceService $recurrence)
    {
        $validated = $request->validate([
            'message'                    => ['required', 'string', 'max:3000'],
            'image_path'                 => ['nullable', 'string', 'max:255'],
            'scheduled_at'                => ['nullable', 'date', 'after:now'],
            'audience'                    => ['required', 'in:channels'],
            'recurrence_type'             => ['nullable', 'in:fixed_daily,daily_around,random_per_day'],
            'recurrence_time'             => ['required_if:recurrence_type,fixed_daily,daily_around', 'nullable', 'date_format:H:i'],
            'recurrence_jitter_minutes'   => ['nullable', 'integer', 'min:1', 'max:240'],
            'recurrence_times_per_day'    => ['required_if:recurrence_type,random_per_day', 'nullable', 'integer', 'min:1', 'max:6'],
            'recurrence_window_start'     => ['nullable', 'integer', 'min:0', 'max:23'],
            'recurrence_window_end'       => ['nullable', 'integer', 'min:1', 'max:24'],
            'max_occurrences'             => ['nullable', 'integer', 'min:1', 'max:10000'],
            'ai_spice'                    => ['nullable', 'boolean'],
        ]);

        $scheduledAt     = $validated['scheduled_at'] ?? null;
        $recurrenceType  = $validated['recurrence_type'] ?? null;

        $broadcast = SocialBroadcast::create([
            'message'                   => $validated['message'],
            'image_path'                => $validated['image_path'] ?? null,
            'audience'                  => $validated['audience'],
            'status'                    => $recurrenceType ? 'recurring' : ($scheduledAt ? 'scheduled' : 'pending'),
            'scheduled_at'              => $recurrenceType ? null : $scheduledAt,
            'recurrence_type'           => $recurrenceType,
            'recurrence_time'           => $validated['recurrence_time'] ?? null,
            'recurrence_jitter_minutes' => $validated['recurrence_jitter_minutes'] ?? null,
            'recurrence_times_per_day'  => $validated['recurrence_times_per_day'] ?? null,
            'recurrence_window_start'   => $validated['recurrence_window_start'] ?? null,
            'recurrence_window_end'     => $validated['recurrence_window_end'] ?? null,
            'max_occurrences'           => $validated['max_occurrences'] ?? null,
            'ai_spice'                  => (bool) ($validated['ai_spice'] ?? false),
            'created_by'                => Auth::id(),
        ]);

        if ($recurrenceType) {
            $broadcast->update($recurrence->nextRun($broadcast, now()));

            return $this->httpResponse()->setMessage(
                'Broadcast set to repeat — first post goes out around ' . $broadcast->next_run_at->format('M j, Y \a\t g:i A') . '.'
            );
        }

        if ($scheduledAt) {
            $job = SendSocialBroadcastJob::dispatch($broadcast->getKey());
            $job->delay(now()->diffInSeconds($scheduledAt, true));

            return $this->httpResponse()->setMessage(
                'Broadcast scheduled for ' . $broadcast->scheduled_at->format('M j, Y \a\t g:i A') . '.'
            );
        }

        SendSocialBroadcastJob::dispatch($broadcast->getKey());

        return $this->httpResponse()->setMessage('Broadcast queued — it will post to your channels shortly.');
    }

    public function retry(SocialBroadcast $broadcast)
    {
        return $this->httpResponse()
            ->setError()
            ->setMessage('Direct WhatsApp broadcasts are disabled.');
    }

    public function cancel(SocialBroadcast $broadcast)
    {
        if (! in_array($broadcast->status, ['scheduled', 'recurring'], true)) {
            return $this->httpResponse()->setError()->setMessage('Only scheduled or recurring broadcasts can be cancelled.');
        }

        $wasRecurring = $broadcast->isRecurring();
        $broadcast->update(['status' => 'cancelled', 'next_run_at' => null]);

        return $this->httpResponse()->setMessage($wasRecurring ? 'Recurring broadcast cancelled.' : 'Scheduled broadcast cancelled.');
    }

    public function destroy(SocialBroadcast $broadcast)
    {
        $broadcast->delete();

        return $this->httpResponse()->setMessage('Broadcast removed from history.');
    }
}
