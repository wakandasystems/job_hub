<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AutoApplyQuota extends BaseModel
{
    protected $table = 'jb_auto_apply_quotas';

    protected $fillable = [
        'account_id',
        'order_id',
        'period',
        'quota_key',
        'applications_allowed',
        'applications_sent',
        'is_approved',
        'charge_id',
        'payment_method',
        'plan',
        'cycle_started_at',
        'cycle_ends_at',
    ];

    protected $casts = [
        'applications_allowed' => 'integer',
        'applications_sent'    => 'integer',
        'is_approved'          => 'boolean',
        'cycle_started_at'     => 'datetime',
        'cycle_ends_at'        => 'datetime',
    ];

    public function scopeActivePaid(Builder $query)
    {
        return $query->whereNotNull('plan')->where('is_approved', true);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(AutoApplyOrder::class, 'order_id');
    }

    public function hasRemaining(): bool
    {
        if ($this->applications_allowed === -1) {
            return true;
        }

        return $this->applications_sent < $this->applications_allowed;
    }

    public static function currentPeriod(): string
    {
        return now()->format('Y-m');
    }

    public static function syncForAccount(int $accountId): ?self
    {
        $order = AutoApplyOrder::activeForAccount($accountId);

        if (! $order) {
            self::query()
                ->where('account_id', $accountId)
                ->where('is_approved', true)
                ->update([
                    'is_approved' => false,
                    'updated_at' => now(),
                ]);

            return null;
        }

        $cycle = $order->cycleDetails();

        if (! $cycle) {
            return null;
        }

        self::query()
            ->where('account_id', $accountId)
            ->where('is_approved', true)
            ->where(function (Builder $query) use ($cycle): void {
                $query->whereNull('quota_key')
                    ->orWhere('quota_key', '!=', $cycle['key']);
            })
            ->update([
                'is_approved' => false,
                'updated_at' => now(),
            ]);

        return self::query()->updateOrCreate(
            ['account_id' => $accountId, 'quota_key' => $cycle['key']],
            [
                'order_id' => $order->id,
                'period' => $cycle['period'],
                'plan' => $order->plan,
                'applications_allowed' => $cycle['applications_allowed'],
                'is_approved' => true,
                'charge_id' => $order->charge_id,
                'payment_method' => $order->payment_method,
                'cycle_started_at' => $cycle['start'],
                'cycle_ends_at' => $cycle['end'],
            ]
        );
    }

    public static function currentForAccount(int $accountId): ?self
    {
        return self::syncForAccount($accountId);
    }

    public function consumeOne(): bool
    {
        if ($this->applications_allowed === -1) {
            return $this->increment('applications_sent');
        }

        $updated = DB::table($this->getTable())
            ->where('id', $this->id)
            ->whereColumn('applications_sent', '<', 'applications_allowed')
            ->update([
                'applications_sent' => DB::raw('applications_sent + 1'),
                'updated_at' => now(),
            ]);

        if (! $updated) {
            return false;
        }

        $this->refresh();

        return true;
    }
}
