<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateAlert extends BaseModel
{
    protected $table = 'jb_candidate_alerts';

    protected $fillable = [
        'label',
        'candidate_name',
        'candidate_phone',
        'candidate_phone_2',
        'candidate_email',
        'filters',
        'duration_days',
        'price',
        'is_active',
        'status',
        'activated_at',
        'expires_at',
        'expiry_warning_sent',
        'expiry_sameday_sent',
        'expiry_notice_sent',
        'notes',
        'cv_path',
        'cv_analysis',
    ];

    protected $casts = [
        'filters'             => 'array',
        'is_active'           => 'bool',
        'expiry_warning_sent'  => 'bool',
        'expiry_sameday_sent'  => 'bool',
        'expiry_notice_sent'   => 'bool',
        'activated_at'        => 'datetime',
        'expires_at'          => 'datetime',
        'price'               => 'decimal:2',
        'cv_analysis'         => 'array',
    ];

    public static array $durations = [
        7  => ['label' => '1 Week',   'price' => 40.00,  'badge' => 'bg-info text-white'],
        30 => ['label' => '1 Month',  'price' => 100.00, 'badge' => 'bg-primary text-white'],
        60 => ['label' => '2 Months', 'price' => 150.00, 'badge' => 'bg-success text-white'],
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(CandidateAlertLog::class, 'candidate_alert_id');
    }

    public function daysRemaining(): int
    {
        if (! $this->expires_at || $this->status === 'expired') {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    public function recipientJid(): string
    {
        return preg_replace('/\D/', '', $this->candidate_phone) . '@s.whatsapp.net';
    }

    /**
     * All WhatsApp JIDs alerts should be sent to (primary + optional second number).
     */
    public function recipientJids(): array
    {
        $jids = [];

        foreach ([$this->candidate_phone, $this->candidate_phone_2] as $phone) {
            $phone = trim((string) $phone);
            if ($phone === '') {
                continue;
            }

            $jids[] = preg_replace('/\D/', '', $phone) . '@s.whatsapp.net';
        }

        return array_values(array_unique($jids));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }
}
