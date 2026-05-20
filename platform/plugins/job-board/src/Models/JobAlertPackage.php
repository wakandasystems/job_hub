<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;

class JobAlertPackage extends BaseModel
{
    protected $table = 'jb_job_alert_packages';

    protected $fillable = [
        'name',
        'description',
        'alerts_per_month',
        'price',
        'currency',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'price'            => 'float',
        'alerts_per_month' => 'integer',
        'sort_order'       => 'integer',
    ];

    public function isUnlimited(): bool
    {
        return $this->alerts_per_month === 0;
    }

    public function displayAlerts(): string
    {
        return $this->isUnlimited() ? 'Unlimited' : (string) $this->alerts_per_month;
    }
}
