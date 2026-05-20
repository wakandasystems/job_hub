<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAlertQuota extends BaseModel
{
    protected $table = 'jb_job_alert_quotas';

    protected $fillable = [
        'account_id',
        'package_id',
        'period',
        'alerts_allowed',
        'alerts_sent',
        'charge_id',
        'payment_method',
        'is_approved',
    ];

    protected $casts = [
        'alerts_allowed' => 'integer',
        'alerts_sent'    => 'integer',
        'is_approved'    => 'boolean',
    ];

    // For paid quota rows, restrict to admin-approved ones (is_approved = true).
    // Free-tier rows have is_approved = null and are always active.
    public function scopeActivePaid($query)
    {
        return $query->whereNotNull('package_id')->where('is_approved', true);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(JobAlertPackage::class, 'package_id');
    }

    public function hasRemaining(): bool
    {
        if ($this->alerts_allowed === -1) {
            return true;
        }
        return $this->alerts_sent < $this->alerts_allowed;
    }

    public static function currentPeriod(): string
    {
        return now()->format('Y-m');
    }
}
