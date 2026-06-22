<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\PushSubscription;
use Botble\Media\Facades\RvMedia;
use Illuminate\Console\Command;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class SendPushNotificationsCommand extends Command
{
    protected $signature   = 'job-board:push-notify
        {job_id : The Job ID to send notifications for}';
    protected $description = 'Send web push notifications for a published job';

    public function handle(): int
    {
        $job = Job::with(['company', 'slugable'])->find((int) $this->argument('job_id'));

        if (! $job) {
            return self::FAILURE;
        }

        $jobCountryId = $job->country_id;

        $subscriptions = PushSubscription::query()
            ->when($jobCountryId, fn ($q) => $q->where('country_id', $jobCountryId))
            ->get();

        if ($subscriptions->isEmpty()) {
            return self::SUCCESS;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject'    => config('services.vapid.subject'),
                    'publicKey'  => config('services.vapid.public_key'),
                    'privateKey' => config('services.vapid.private_key'),
                ],
            ]);

            $payload = json_encode([
                'title' => $job->name,
                'body'  => $job->company?->name
                    ? 'New job at ' . $job->company->name
                    : 'A new job was just posted!',
                'url'   => route('public.job', $job->slugable?->key ?? $job->id),
                'image' => ! $job->hide_company && $job->company_logo_thumb
                    ? $job->company_logo_thumb
                    : RvMedia::getImageUrl('chatgpt-image-may-14-2026-03-00-04-pm.png'),
                'icon'  => '/push-icon.png',
                'badge' => '/push-icon.png',
            ]);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'keys'     => ['p256dh' => $sub->p256dh, 'auth' => $sub->auth],
                    ]),
                    $payload
                );
            }

            $staleEndpoints = [];

            foreach ($webPush->flush() as $report) {
                if (! $report->isSuccess()) {
                    $statusCode = $report->getResponse()?->getStatusCode();
                    if (in_array($statusCode, [404, 410])) {
                        $staleEndpoints[] = $report->getEndpoint();
                    }
                }
            }

            if (! empty($staleEndpoints)) {
                PushSubscription::whereIn('endpoint', $staleEndpoints)->delete();
            }
        } catch (Throwable) {
            // Best-effort
        }

        return self::SUCCESS;
    }
}
