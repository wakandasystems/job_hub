<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdPlacement extends BaseModel
{
    protected $table = 'jb_ad_placements';

    protected $fillable = [
        'name',
        'location',
        'description',
        'price',
        'currency',
        'duration_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
        'duration_days' => 'integer',
        'sort_order' => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(AdOrder::class, 'placement_id');
    }

    public function tierPrices(): HasMany
    {
        return $this->hasMany(AdPlacementTierPrice::class, 'ad_placement_id');
    }

    public function isUnlimitedDuration(): bool
    {
        return $this->duration_days === 0;
    }

    public function displayDuration(): string
    {
        return $this->isUnlimitedDuration() ? 'No expiry' : $this->duration_days . ' days';
    }
}
