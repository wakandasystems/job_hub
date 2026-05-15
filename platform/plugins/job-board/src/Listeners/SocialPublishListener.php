<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Models\SocialAutomation;

class SocialPublishListener
{
    public function handle(JobPublishedEvent $event): void
    {
        // Skip if no active automations exist (avoid spawning a process for nothing).
        if (! SocialAutomation::query()->where('is_active', true)->exists()) {
            return;
        }

        $php    = PHP_BINARY;
        if (str_contains($php, 'fpm') || ! is_executable($php)) {
            $php = '/usr/bin/php';
        }

        $artisan = base_path('artisan');

        \exec(sprintf(
            '%s %s job-board:social-publish %d > /dev/null 2>&1 &',
            escapeshellcmd($php),
            escapeshellarg($artisan),
            $event->job->getKey()
        ));
    }
}
