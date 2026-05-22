<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;

class FeaturedPackage extends BaseModel
{
    protected $table = 'jb_featured_packages';

    protected $fillable = [
        'name',
        'description',
        'duration_days',
        'price',
        'currency',
        'badge_label',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'price'         => 'float',
        'duration_days' => 'integer',
        'sort_order'    => 'integer',
    ];

    public function isUnlimitedDuration(): bool
    {
        return $this->duration_days === 0;
    }

    public function displayDuration(): string
    {
        return $this->isUnlimitedDuration() ? 'No expiry' : $this->duration_days . ' days';
    }
}
