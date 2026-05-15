<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobCrawler extends BaseModel
{
    public const SCHEDULE_HOURLY = 'hourly';

    public const SCHEDULE_EVERY_30_MINUTES = 'every_30_minutes';

    public const SCHEDULE_EVERY_15_MINUTES = 'every_15_minutes';

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

    public function runningRun(int $staleAfterMinutes = 120): ?JobCrawlerRun
    {
        return $this->runs()
            ->where('status', 'running')
            ->where('started_at', '>=', Carbon::now()->subMinutes($staleAfterMinutes))
            ->latest('id')
            ->first();
    }

    public static function scheduleOptions(): array
    {
        return [
            self::SCHEDULE_HOURLY => 'Hourly',
            self::SCHEDULE_EVERY_30_MINUTES => 'Every 30 minutes',
            self::SCHEDULE_EVERY_15_MINUTES => 'Every 15 minutes',
        ];
    }

    public function scheduleIntervalMinutes(): int
    {
        $schedule = strtolower(trim((string) $this->schedule));

        return match ($schedule) {
            self::SCHEDULE_EVERY_15_MINUTES => 15,
            self::SCHEDULE_EVERY_30_MINUTES => 30,
            self::SCHEDULE_HOURLY => 60,
            default => match (true) {
                str_contains($schedule, '15') && str_contains($schedule, 'minute') => 15,
                str_contains($schedule, '30') && str_contains($schedule, 'minute') => 30,
                str_contains($schedule, 'hour') => 60,
                default => 60,
            },
        };
    }
}
