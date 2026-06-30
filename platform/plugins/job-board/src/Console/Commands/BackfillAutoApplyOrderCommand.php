<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Services\AutoApplyOrderBackfillService;
use Illuminate\Console\Command;

class BackfillAutoApplyOrderCommand extends Command
{
    protected $signature = 'job-board:backfill-auto-apply-order
        {order_id? : The auto-apply order ID to backfill}
        {--all-approved : Backfill every currently approved auto-apply order sequentially}';

    protected $description = 'Backfill missed matching jobs for approved auto-apply orders';

    public function handle(AutoApplyOrderBackfillService $service): int
    {
        $orderId = $this->argument('order_id');
        $allApproved = (bool) $this->option('all-approved');

        if (! $orderId && ! $allApproved) {
            $this->error('Provide an order ID or use --all-approved.');

            return self::INVALID;
        }

        $query = AutoApplyOrder::query()
            ->with('account')
            ->approved();

        if ($orderId) {
            $query->whereKey((int) $orderId);
        }

        $orders = $query->orderBy('id')->get();

        if ($orders->isEmpty()) {
            $this->error('No matching approved auto-apply orders were found.');

            return self::FAILURE;
        }

        foreach ($orders as $order) {
            $candidate = $order->account?->name ?: ('Account #' . $order->account_id);
            $this->line(sprintf('Backfilling order #%d for %s...', $order->id, $candidate));

            $summary = $service->backfillOrder($order);

            $this->line(sprintf(
                'Matched %d | processed %d | emails %d | manual notices %d | already processed %d | below threshold %d | quota skipped %d | failed %d',
                $summary['matched_total'],
                $summary['processed'],
                $summary['emails_sent'],
                $summary['manual_notified'],
                $summary['already_processed'],
                $summary['below_threshold'],
                $summary['skipped_quota'],
                $summary['failed']
            ));

            if ($summary['missing_account']) {
                $this->warn('Skipped because the order has no candidate account.');
            }

            if ($summary['missing_cv']) {
                $this->warn('Skipped because the candidate has no CV.');
            }

            if ($summary['locked']) {
                $this->warn('Skipped because another backfill is already running for this order.');
            }
        }

        return self::SUCCESS;
    }
}
