<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Jobs\GenerateSocialImagesJob;
use Botble\JobBoard\Services\OpenAiImageService;

class GenerateSocialImagesListener
{
    public function handle(JobPublishedEvent $event): void
    {
        // Master toggle off, or no API key → nothing to do (zero spend).
        if (! setting('ai_social_image_enabled') || ! app(OpenAiImageService::class)->isConfigured()) {
            return;
        }

        // Queued (Horizon) so OpenAI calls never block the publish request, and so the
        // job survives PHP-FPM worker recycling — unlike the detached exec() this used to
        // be. --publish chains the social-channel post to run after the image is saved,
        // so the channel post carries the generated image instead of racing ahead
        // text-only. SocialPublishListener defers to this path while AI images are on.
        GenerateSocialImagesJob::dispatch($event->job->getKey(), true);
    }
}
