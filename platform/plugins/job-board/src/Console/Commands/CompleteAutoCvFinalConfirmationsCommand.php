<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Services\AutoCvBotService;
use Illuminate\Console\Command;

class CompleteAutoCvFinalConfirmationsCommand extends Command
{
    protected $signature = 'job-board:complete-auto-cv-final-confirmations';

    protected $description = 'Auto-complete Auto CV Bot sessions whose candidate did not reply DONE within 2 minutes';

    public function handle(AutoCvBotService $service): int
    {
        $count = $service->completeTimedOutConfirmations();

        $this->info("Auto-completed {$count} session(s) after final-confirmation timeout.");

        return self::SUCCESS;
    }
}
