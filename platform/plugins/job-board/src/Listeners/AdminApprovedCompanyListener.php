<?php

namespace Botble\JobBoard\Listeners;

use Botble\Base\Facades\EmailHandler;
use Botble\JobBoard\Events\AdminApprovedCompanyEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class AdminApprovedCompanyListener implements ShouldQueue
{
    public string $queue = 'emails';
    public int $tries = 2;
    public function handle(AdminApprovedCompanyEvent $event)
    {
        $company = $event->company;

        $mailer = EmailHandler::setModule(JOB_BOARD_MODULE_SCREEN_NAME)
            ->setType('plugins')
            ->setVariableValues([
                'company_name' => $event->company->name,
                'company_url' => $event->company->url,
            ]);

        $company->loadMissing('accounts');

        $emails = $company->accounts->pluck('email')->toArray();

        if ($emails) {
            $mailer->sendUsingTemplate('company-approved', $emails);
        }
    }
}
