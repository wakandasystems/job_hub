<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAlert extends BaseModel
{
    protected $with = [];
    protected $table = 'jb_job_alerts';

    protected $fillable = [
        'account_id',
        'keyword',
        'category_id',
        'category_ids',
        'country_id',
        'state_id',
        'city_id',
        'notify_email',
        'notify_whatsapp',
        'notify_telegram',
        'is_active',
    ];

    protected $casts = [
        'notify_email'     => 'boolean',
        'notify_whatsapp'  => 'boolean',
        'notify_telegram'  => 'boolean',
        'is_active'        => 'boolean',
        'category_ids'     => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(\Botble\Location\Models\Country::class, 'country_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(\Botble\Location\Models\State::class, 'state_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(\Botble\Location\Models\City::class, 'city_id');
    }
}
