<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Jobs\SocialPublishJob;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\OpenAiImageService;

class SocialPublishListener
{
    public function handle(JobPublishedEvent $event): void
    {
        // Skip if no active automations exist (avoid dispatching a job for nothing).
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

        // Queued (Horizon) so it survives PHP-FPM worker recycling — unlike the detached
        // exec() this used to be.
        SocialPublishJob::dispatch($event->job->getKey());
    }
}
