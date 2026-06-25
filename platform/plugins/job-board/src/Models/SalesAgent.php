<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Botble\Media\Facades\RvMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesAgent extends BaseModel
{
    protected $table = 'jb_sales_agents';

    protected $fillable = [
        'candidate_account_id',
        'name',
        'phone',
        'email',
        'code',
        'photo',
        'use_marketing_photo',
        'commission_rate',
        'status',
        'notes',
    ];

    public function candidateAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'candidate_account_id');
    }

    protected $casts = [
        'commission_rate' => 'float',
        'use_marketing_photo' => 'bool',
    ];

    public function referrals(): HasMany
    {
        return $this->hasMany(SalesAgentReferral::class, 'sales_agent_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(SalesAgentCommission::class, 'sales_agent_id');
    }

    public function marketingImages(): HasMany
    {
        return $this->hasMany(SalesAgentMarketingImage::class, 'sales_agent_id');
    }

    public function totalRevenue(): float
    {
        return (float) $this->commissions()->sum('amount');
    }

    public function totalCommissionOwed(): float
    {
        return (float) $this->commissions()->where('status', 'unpaid')->sum('commission_amount');
    }

    public function totalCommissionPaid(): float
    {
        return (float) $this->commissions()->where('status', 'paid')->sum('commission_amount');
    }

    public function referralCount(): int
    {
        return $this->referrals()->count();
    }

    public function photoUrl(): ?string
    {
        if ($this->photo) {
            return RvMedia::getImageUrl($this->photo);
        }

        return $this->candidateAccount?->avatar_url ?: null;
    }

    public function hasMarketingPhoto(): bool
    {
        return filled($this->photo);
    }

    public function preferredMarketingSubjectMode(): string
    {
        if ($this->use_marketing_photo && $this->hasMarketingPhoto()) {
            return 'both';
        }

        return 'nakia';
    }
}
