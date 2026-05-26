<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Events\JobPublishedEvent;
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

        // Spawn a background process that sleeps a random 2–8 minutes then sends.
        // Running in the background means the job-publish web request is never blocked.
        $php    = is_executable(PHP_BINARY) && ! str_contains(PHP_BINARY, 'fpm') ? PHP_BINARY : '/usr/bin/php';
        $artisan = base_path('artisan');

        exec(sprintf(
            '%s %s job-board:push-notify %d > /dev/null 2>&1 &',
            escapeshellcmd($php),
            escapeshellarg($artisan),
            $job->getKey()
        ));
    }
}
