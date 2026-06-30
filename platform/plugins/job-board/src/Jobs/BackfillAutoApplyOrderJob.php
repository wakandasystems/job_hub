<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Services\AutoApplyOrderBackfillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackfillAutoApplyOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        private readonly int $orderId,
    ) {
    }

    public function handle(AutoApplyOrderBackfillService $service): void
    {
        $order = AutoApplyOrder::query()->with('account')->find($this->orderId);

        if (! $order) {
            return;
        }

        $summary = $service->backfillOrder($order);

        Log::info('AutoApply activation backfill completed', $summary);
    }

    public function failed(Throwable $e): void
    {
        Log::error('AutoApply activation backfill failed', [
            'order_id' => $this->orderId,
            'error' => $e->getMessage(),
        ]);
    }
}
