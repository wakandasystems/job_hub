<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobCrawler extends BaseModel
{
    protected $table = 'jb_job_crawlers';

    protected $fillable = [
        'name',
        'source_url',
        'parser_type',
        'schedule',
        'is_active',
        'default_company_id',
        'item_selector',
        'title_selector',
        'company_selector',
        'location_selector',
        'description_selector',
        'content_selector',
        'apply_url_selector',
        'published_at_selector',
        'field_mappings',
        'last_run_at',
        'next_run_at',
        'last_status',
        'last_error',
    ];

    protected $casts = [
        'name' => SafeContent::class,
        'source_url' => SafeContent::class,
        'parser_type' => SafeContent::class,
        'schedule' => SafeContent::class,
        'is_active' => 'bool',
        'field_mappings' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'default_company_id')->withDefault();
    }

    public function runs(): HasMany
    {
        return $this->hasMany(JobCrawlerRun::class, 'crawler_id');
    }
}
