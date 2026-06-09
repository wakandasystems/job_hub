<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Jobs\SendEmployerBroadcastChunkJob;
use Botble\JobBoard\Jobs\SendSocialBroadcastJob;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Models\SocialBroadcast;
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
            ->limit(15)
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

    public function send(Request $request)
    {
        $validated = $request->validate([
            'message'      => ['required', 'string', 'max:3000'],
            'image_path'   => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'audience'     => ['required', 'in:channels,employers'],
        ]);

        $scheduledAt = $validated['scheduled_at'] ?? null;

        $broadcast = SocialBroadcast::create([
            'message'      => $validated['message'],
            'image_path'   => $validated['image_path'] ?? null,
            'audience'     => $validated['audience'],
            'status'       => $scheduledAt ? 'scheduled' : 'pending',
            'scheduled_at' => $scheduledAt,
            'created_by'   => Auth::id(),
        ]);

        if ($scheduledAt) {
            $job = $broadcast->audience === 'employers'
                ? SendEmployerBroadcastChunkJob::dispatch($broadcast->getKey())
                : SendSocialBroadcastJob::dispatch($broadcast->getKey());
            $job->delay(now()->diffInSeconds($scheduledAt, true));

            return $this->httpResponse()->setMessage(
                'Broadcast scheduled for ' . $broadcast->scheduled_at->format('M j, Y \a\t g:i A') . '.'
            );
        }

        $broadcast->audience === 'employers'
            ? SendEmployerBroadcastChunkJob::dispatch($broadcast->getKey())
            : SendSocialBroadcastJob::dispatch($broadcast->getKey());

        return $this->httpResponse()->setMessage(
            $broadcast->audience === 'employers'
                ? 'Employer WhatsApp broadcast queued.'
                : 'Broadcast queued — it will post to your channels shortly.'
        );
    }

    public function cancel(SocialBroadcast $broadcast)
    {
        if ($broadcast->status !== 'scheduled') {
            return $this->httpResponse()->setError()->setMessage('Only scheduled broadcasts can be cancelled.');
        }

        $broadcast->update(['status' => 'cancelled']);

        return $this->httpResponse()->setMessage('Scheduled broadcast cancelled.');
    }

    public function destroy(SocialBroadcast $broadcast)
    {
        $broadcast->delete();

        return $this->httpResponse()->setMessage('Broadcast removed from history.');
    }
}
