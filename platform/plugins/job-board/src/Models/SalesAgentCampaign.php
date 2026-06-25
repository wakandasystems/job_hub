<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesAgentCampaign extends BaseModel
{
    protected $table = 'jb_sales_agent_campaigns';

    protected $fillable = [
        'name',
        'prompt_template',
        'aspect_ratio',
        'promo_price',
        'promo_original_price',
        'promo_end_date',
        'is_active',
    ];

    protected $casts = [
        'promo_end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function marketingImages(): HasMany
    {
        return $this->hasMany(SalesAgentMarketingImage::class, 'campaign_id');
    }

    public function latestCompletedMarketingImage(): HasOne
    {
        return $this->hasOne(SalesAgentMarketingImage::class, 'campaign_id')
            ->where('status', 'completed')
            ->whereNotNull('image_path')
            ->latestOfMany();
    }

    public function latestMarketingImage(): HasOne
    {
        return $this->hasOne(SalesAgentMarketingImage::class, 'campaign_id')
            ->latestOfMany();
    }
}
