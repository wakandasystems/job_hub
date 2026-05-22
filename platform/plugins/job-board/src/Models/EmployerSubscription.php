<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerSubscription extends BaseModel
{
    protected $table = 'jb_employer_subscriptions';

    protected $fillable = [
        'account_id',
        'package_id',
        'billing_cycle',
        'status',
        'amount',
        'currency',
        'payment_method',
        'charge_id',
        'started_at',
        'ends_at',
        'last_renewed_at',
        'cancel_at_period_end',
        'posts_used_this_cycle',
        'notes',
    ];

    protected $casts = [
        'amount'               => 'float',
        'started_at'           => 'datetime',
        'ends_at'              => 'datetime',
        'last_renewed_at'      => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'posts_used_this_cycle'=> 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isExpiringSoon(int $withinDays = 7): bool
    {
        return $this->isActive()
            && $this->ends_at !== null
            && $this->ends_at->isBefore(Carbon::now()->addDays($withinDays));
    }

    public function activate(): void
    {
        $months = $this->billing_cycle === 'annual' ? 12 : 1;

        $this->update([
            'status'           => 'active',
            'started_at'       => now(),
            'ends_at'          => now()->addMonths($months),
            'last_renewed_at'  => now(),
            'posts_used_this_cycle' => 0,
        ]);
    }

    public function cancel(bool $immediately = false): void
    {
        if ($immediately) {
            $this->update(['status' => 'cancelled', 'ends_at' => now()]);
        } else {
            $this->update(['cancel_at_period_end' => true]);
        }
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function ($q): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }

    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        return $query->active()
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now()->addDays($days));
    }

    public static function statuses(): array
    {
        return [
            'pending'   => 'Pending',
            'active'    => 'Active',
            'expired'   => 'Expired',
            'cancelled' => 'Cancelled',
        ];
    }
}
