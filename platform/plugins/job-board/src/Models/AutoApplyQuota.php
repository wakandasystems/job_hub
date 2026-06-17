<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoApplyQuota extends BaseModel
{
    protected $table = 'jb_auto_apply_quotas';

    protected $fillable = [
        'account_id',
        'period',
        'applications_allowed',
        'applications_sent',
        'is_approved',
        'charge_id',
        'payment_method',
        'plan',
    ];

    protected $casts = [
        'applications_allowed' => 'integer',
        'applications_sent'    => 'integer',
        'is_approved'          => 'boolean',
    ];

    public function scopeActivePaid($query)
    {
        return $query->whereNotNull('plan')->where('is_approved', true);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
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
}
