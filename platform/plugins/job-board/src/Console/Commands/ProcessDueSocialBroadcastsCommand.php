<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Jobs\SendSocialBroadcastJob;
use Botble\JobBoard\Models\SocialBroadcast;
use Illuminate\Console\Command;

class ProcessDueSocialBroadcastsCommand extends Command
{
    protected $signature = 'job-board:process-due-broadcasts';

    protected $description = 'Dispatch recurring social broadcasts whose next scheduled run has come due';

    public function handle(): int
    {
        $due = SocialBroadcast::query()
            ->where('status', 'recurring')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->get();

        foreach ($due as $broadcast) {
            $this->line("Dispatching recurring broadcast #{$broadcast->id} (due {$broadcast->next_run_at}).");
            SendSocialBroadcastJob::dispatch($broadcast->getKey());
        }

        if ($due->isEmpty()) {
            $this->line('No recurring broadcasts due.');
        }

        return self::SUCCESS;
    }
}
