<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutoApplyPreference extends BaseModel
{
    protected $table = 'jb_auto_apply_preferences';

    protected $fillable = [
        'account_id',
        'is_active',
        'keywords',
        'category_ids',
        'country_ids',
        'location_keyword',
        'job_experience_id',
        'whitelisted_company_ids',
        'whitelisted_company_keywords',
        'blacklisted_company_ids',
        'blacklisted_company_keywords',
        'match_score_threshold',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'keywords'               => 'array',
        'category_ids'           => 'array',
        'country_ids'            => 'array',
        'whitelisted_company_ids' => 'array',
        'whitelisted_company_keywords' => 'array',
        'blacklisted_company_ids' => 'array',
        'blacklisted_company_keywords' => 'array',
        'match_score_threshold'  => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutoApplyLog::class, 'account_id', 'account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if the candidate has a CV uploaded (required for auto-apply).
     */
    public function candidateHasCv(): bool
    {
        return $this->account && trim((string) $this->account->resume) !== '';
    }
}
