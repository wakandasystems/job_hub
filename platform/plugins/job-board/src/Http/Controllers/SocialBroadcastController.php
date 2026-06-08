<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Jobs\SendSocialBroadcastJob;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Models\SocialBroadcast;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Http\Request;
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

    public function index()
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

        return view('plugins/job-board::broadcasts.index', compact('channels', 'broadcasts'));
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

    public function send(Request $request)
    {
        $validated = $request->validate([
            'message'      => ['required', 'string', 'max:3000'],
            'image_path'   => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        $scheduledAt = $validated['scheduled_at'] ?? null;

        $broadcast = SocialBroadcast::create([
            'message'      => $validated['message'],
            'image_path'   => $validated['image_path'] ?? null,
            'status'       => $scheduledAt ? 'scheduled' : 'pending',
            'scheduled_at' => $scheduledAt,
            'created_by'   => Auth::id(),
        ]);

        if ($scheduledAt) {
            SendSocialBroadcastJob::dispatch($broadcast->getKey())
                ->delay(now()->diffInSeconds($scheduledAt, true));

            return $this->httpResponse()->setMessage(
                'Broadcast scheduled for ' . $broadcast->scheduled_at->format('M j, Y \a\t g:i A') . '.'
            );
        }

        SendSocialBroadcastJob::dispatch($broadcast->getKey());

        return $this->httpResponse()->setMessage('Broadcast queued — it will post to your channels shortly.');
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
