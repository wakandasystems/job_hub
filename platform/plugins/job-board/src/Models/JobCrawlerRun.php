<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCrawlerRun extends BaseModel
{
    protected $table = 'jb_job_crawler_runs';

    protected $fillable = [
        'crawler_id',
        'status',
        'started_at',
        'finished_at',
        'jobs_found',
        'jobs_created',
        'jobs_updated',
        'jobs_skipped',
        'error_message',
        'error_trace',
        'meta',
    ];

    protected $casts = [
        'status' => SafeContent::class,
        'error_message' => SafeContent::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];

    public function crawler(): BelongsTo
    {
        return $this->belongsTo(JobCrawler::class, 'crawler_id')->withDefault();
    }
}
