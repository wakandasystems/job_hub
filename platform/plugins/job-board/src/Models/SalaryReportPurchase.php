<?php

namespace Botble\JobBoard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryReportPurchase extends Model
{
    protected $table = 'jb_salary_report_purchases';

    protected $fillable = [
        'report_id',
        'buyer_name',
        'buyer_email',
        'buyer_company',
        'amount_paid',
        'currency_code',
        'payment_channel',
        'charge_id',
        'access_token',
        'downloaded_at',
        'expires_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
        'expires_at'    => 'datetime',
        'amount_paid'   => 'float',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(SalaryReport::class, 'report_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
