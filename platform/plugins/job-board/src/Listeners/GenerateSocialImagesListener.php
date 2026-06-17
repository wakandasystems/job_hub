<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Services\OpenAiImageService;

class GenerateSocialImagesListener
{
    public function handle(JobPublishedEvent $event): void
    {
        // Master toggle off, or no API key → nothing to do (zero spend).
        if (! setting('ai_social_image_enabled') || ! app(OpenAiImageService::class)->isConfigured()) {
            return;
        }

        $php = PHP_BINARY;
        if (str_contains($php, 'fpm') || ! is_executable($php)) {
            $php = '/usr/bin/php';
        }

        $artisan = base_path('artisan');

        // Run in the background so OpenAI calls never block the publish request.
        // --publish chains the social-channel post to run after the image is saved,
        // so the channel post carries the generated image instead of racing ahead
        // text-only. SocialPublishListener defers to this path while AI images are on.
        \exec(sprintf(
            '%s %s job-board:generate-social-images %d --publish > /dev/null 2>&1 &',
            escapeshellcmd($php),
            escapeshellarg($artisan),
            $event->job->getKey()
        ));
    }
}
