<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\OpenAiImageService;

class SocialPublishListener
{
    public function handle(JobPublishedEvent $event): void
    {
        // Skip if no active automations exist (avoid spawning a process for nothing).
        if (! SocialAutomation::query()->where('is_active', true)->exists()) {
            return;
        }

        // When AI social-image generation is active, defer publishing to
        // GenerateSocialImagesListener, which runs the generate command with --publish
        // so the channel post fires only AFTER the image is saved (no text-only race).
        // This condition mirrors that listener's own guard, so exactly one of the two
        // paths publishes per job.
        if (setting('ai_social_image_enabled') && app(OpenAiImageService::class)->isConfigured()) {
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
