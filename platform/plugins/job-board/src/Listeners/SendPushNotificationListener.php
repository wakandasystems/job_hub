<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Jobs\SendPushNotificationsJob;
use Botble\JobBoard\Models\PushSubscription;

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

        SendPushNotificationsJob::dispatch($job->getKey())
            ->delay(now()->addSeconds(random_int(120, 480)));
    }
}
