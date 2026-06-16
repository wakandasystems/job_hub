<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdPricingTier extends BaseModel
{
    protected $table = 'jb_ad_pricing_tiers';

    protected $fillable = [
        'name',
        'country_ids',
        'sort_order',
    ];

    protected $casts = [
        'country_ids' => 'array',
        'sort_order' => 'integer',
    ];

    public function tierPrices(): HasMany
    {
        return $this->hasMany(AdPlacementTierPrice::class, 'tier_id');
    }
}
