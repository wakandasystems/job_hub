<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesAgentCampaignLead extends BaseModel
{
    protected $table = 'jb_sales_agent_campaign_leads';

    protected $fillable = [
        'sales_agent_id',
        'campaign_id',
        'account_id',
        'sales_agent_code',
        'candidate_name',
        'candidate_phone',
        'candidate_email',
        'status',
        'product_type',
        'product_label',
        'promo_price',
        'promo_original_price',
        'customer_notes',
        'admin_notes',
        'public_token',
        'notified_admin_at',
        'contacted_at',
        'paid_at',
        'onboarded_at',
        'rejected_at',
    ];

    protected $casts = [
        'notified_admin_at' => 'datetime',
        'contacted_at' => 'datetime',
        'paid_at' => 'datetime',
        'onboarded_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            'pending' => 'Pending',
            'contacted' => 'Contacted',
            'paid' => 'Paid',
            'onboarded' => 'Onboarded',
            'rejected' => 'Rejected',
        ];
    }

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(SalesAgent::class, 'sales_agent_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SalesAgentCampaign::class, 'campaign_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function statusLabel(): string
    {
        return static::statuses()[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'contacted' => 'info',
            'paid' => 'primary',
            'onboarded' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function resolvedProductLabel(): string
    {
        return trim((string) $this->product_label) !== ''
            ? (string) $this->product_label
            : SalesAgentCampaign::productTypeOptions()[$this->product_type] ?? ucfirst(str_replace('_', ' ', $this->product_type));
    }

    public function onboardingAdminUrl(): ?string
    {
        return match ($this->product_type) {
            'auto_apply' => route('auto-apply-orders.index'),
            'vip_alert' => route('vip-alert-orders.index'),
            'job_alert' => route('job-alert-orders.index'),
            'career_service' => route('career-service-orders.index'),
            default => null,
        };
    }
}
