<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedOrder extends BaseModel
{
    protected $table = 'jb_featured_orders';

    protected $fillable = [
        'account_id',
        'job_id',
        'package_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'charge_id',
        'approved_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'amount'      => 'float',
        'approved_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(FeaturedPackage::class, 'package_id');
    }

    public function approve(): void
    {
        $package   = $this->package;
        $expiresAt = null;

        if ($package && ! $package->isUnlimitedDuration()) {
            $expiresAt = Carbon::now()->addDays($package->duration_days);
        }

        $this->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'expires_at'  => $expiresAt,
        ]);

        if ($this->job_id && $package) {
            $job = $this->job;
            if ($job) {
                $job->is_featured    = 1;
                $job->featured_until = $expiresAt;
                $job->featured_bid   = max((int) $job->featured_bid, (int) $this->amount);
                $job->save();
            }
        }
    }

    public function isManualPayment(): bool
    {
        return in_array($this->payment_method, ['bank_transfer', 'cod']);
    }

    public static function statuses(): array
    {
        return [
            'pending'   => 'Pending',
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
            'cancelled' => 'Cancelled',
        ];
    }
}
