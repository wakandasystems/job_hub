<?php

namespace Botble\JobBoard\Listeners;

use Botble\Base\Facades\EmailHandler;
use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Models\Account;
use Botble\Newsletter\Models\Newsletter;
use Botble\Newsletter\Enums\NewsletterStatusEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Throwable;

class SendNewsletterJobAlertListener implements ShouldQueue
{
    public string $queue = 'emails';
    public int $tries = 2;
    public function handle(JobPublishedEvent $event): void
    {
        if (! setting('job_board_newsletter_broadcast_enabled', false)) {
            return;
        }

        $job = $event->job;
        $job->loadMissing(['company', 'country']);

        // Get all active newsletter subscribers
        $subscribers = Newsletter::query()
            ->where('status', NewsletterStatusEnum::SUBSCRIBED)
            ->get();

        if ($subscribers->isEmpty()) {
            return;
        }

        // Get emails of users who already have accounts (they get job alerts via the other listener)
        $accountEmails = Account::query()
            ->pluck('email')
            ->map(fn ($email) => strtolower($email))
            ->toArray();

        // Get emails already sent for this job (prevent duplicates)
        $alreadySent = DB::table('jb_newsletter_job_sends')
            ->where('job_id', $job->id)
            ->pluck('email')
            ->map(fn ($email) => strtolower($email))
            ->toArray();

        $signUpUrl = 'https://www.wakandajobs.com/register';

        foreach ($subscribers as $subscriber) {
            $email = strtolower($subscriber->email);

            // Skip if they already have an account (they get proper job alerts)
            if (in_array($email, $accountEmails)) {
                continue;
            }

            // Skip if already sent for this job (duplicate prevention)
            if (in_array($email, $alreadySent)) {
                continue;
            }

            $unsubscribeUrl = URL::signedRoute('public.newsletter.unsubscribe', ['user' => $subscriber->id]);

            try {
                EmailHandler::setModule(JOB_BOARD_MODULE_SCREEN_NAME)
                    ->setVariableValues([
                        'job_name' => $job->name,
                        'job_url' => $job->url,
                        'company_name' => !($job->hide_company ?? false) ? ($job->company->name ?? '') : '',
                        'subscriber_name' => $subscriber->name ?: 'Job Seeker',
                        'job_location' => $job->location ?? '',
                        'job_country' => $job->country->name ?? '',
                        'job_deadline' => ($job->application_closing_date ?? $job->expire_date)?->format('M j, Y') ?? '',
                        'job_description' => \Illuminate\Support\Str::limit(strip_tags($job->description ?? $job->content ?? ''), 400, '...'),
                        'sign_up_url' => $signUpUrl,
                        'unsubscribe_url' => $unsubscribeUrl,
                    ])
                    ->sendUsingTemplate('newsletter-job-alert', $subscriber->email);

                // Record the send to prevent duplicates
                DB::table('jb_newsletter_job_sends')->insert([
                    'job_id' => $job->id,
                    'email' => $email,
                    'sent_at' => now(),
                ]);
            } catch (Throwable) {
                // Silently continue - don't block other sends
            }
        }
    }
}
