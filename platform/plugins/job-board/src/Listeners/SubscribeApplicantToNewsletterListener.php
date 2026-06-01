<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Events\JobAppliedEvent;
use Botble\Newsletter\Enums\NewsletterStatusEnum;
use Botble\Newsletter\Models\Newsletter;
use Illuminate\Contracts\Queue\ShouldQueue;

class SubscribeApplicantToNewsletterListener implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(JobAppliedEvent $event): void
    {
        $email = strtolower(trim((string) $event->jobApplication->email));

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        Newsletter::firstOrCreate(
            ['email' => $email],
            [
                'name'   => trim($event->jobApplication->first_name . ' ' . $event->jobApplication->last_name) ?: null,
                'status' => NewsletterStatusEnum::SUBSCRIBED,
            ]
        );
    }
}
