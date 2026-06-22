<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Jobs\SendPushNotificationsJob;
use Botble\JobBoard\Models\PushSubscription;
use Botble\JobBoard\Services\OpenAiImageService;

class SendPushNotificationListener
{
    public function handle(JobPublishedEvent $event): void
    {
        $job = $event->job;

        // Quick check — no subscribers for this country means nothing to do
        $jobCountryId = $job->country_id;
        $exists = PushSubscription::query()
            ->when($jobCountryId, fn ($q) => $q->where('country_id', $jobCountryId))
            ->exists();

        if (! $exists) {
            return;
        }

        // Jobs that qualify for an AI social image wait: GenerateSocialImagesCommand
        // dispatches the push itself once generation finishes, so the notification
        // carries the image instead of racing ahead without one.
        $service = app(OpenAiImageService::class);
        if (setting('ai_social_image_enabled') && $service->isConfigured() && $service->qualifiesForJob($job)) {
            return;
        }

        SendPushNotificationsJob::dispatch($job->getKey());
    }
}
