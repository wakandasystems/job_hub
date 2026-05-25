<?php

namespace App\Listeners;

use Botble\Newsletter\Enums\NewsletterStatusEnum;
use Botble\Newsletter\Models\Newsletter;
use Illuminate\Auth\Events\Registered;

class SubscribeAccountToNewsletter
{
    public function handle(Registered $event): void
    {
        $account = $event->user;

        if (! isset($account->email) || empty($account->email)) {
            return;
        }

        $existing = Newsletter::query()->where('email', $account->email)->first();

        // Never override an explicit unsubscribe
        if ($existing && $existing->status === NewsletterStatusEnum::UNSUBSCRIBED) {
            return;
        }

        if (! $existing) {
            $name = trim(($account->first_name ?? '') . ' ' . ($account->last_name ?? ''))
                ?: ($account->name ?? null);

            Newsletter::query()->create([
                'email'  => $account->email,
                'name'   => $name ?: null,
                'status' => NewsletterStatusEnum::SUBSCRIBED,
            ]);
        }
    }
}
