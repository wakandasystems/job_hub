<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\EmployerSubscription;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature   = 'job-board:expire-subscriptions';
    protected $description = 'Mark past-end-date subscriptions as expired and reset post counters on renewal.';

    public function handle(): void
    {
        $expired = EmployerSubscription::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($expired as $sub) {
            $status = $sub->cancel_at_period_end ? 'cancelled' : 'expired';
            $sub->update(['status' => $status]);
            $this->line("Subscription #{$sub->id} → {$status}");
        }

        $this->info("Processed {$expired->count()} subscription(s).");
    }
}
