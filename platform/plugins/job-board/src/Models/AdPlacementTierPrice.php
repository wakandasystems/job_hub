<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdPlacementTierPrice extends BaseModel
{
    protected $table = 'jb_ad_placement_tier_prices';

    protected $fillable = [
        'ad_placement_id',
        'tier_id',
        'price',
        'currency',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function placement(): BelongsTo
    {
        return $this->belongsTo(AdPlacement::class, 'ad_placement_id');
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(AdPricingTier::class, 'tier_id');
    }
}
