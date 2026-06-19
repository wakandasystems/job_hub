<?php

namespace Botble\JobBoard\Models;

use Botble\ACL\Models\User;
use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyMergeLog extends BaseModel
{
    protected $table = 'jb_company_merge_logs';

    protected $fillable = [
        'winner_company_id',
        'loser_company_id',
        'loser_name',
        'loser_website',
        'winner_snapshot',
        'loser_snapshot',
        'winner_fields_changed',
        'moved_job_ids',
        'moved_review_ids',
        'moved_account_ids',
        'moved_ai_image_log_ids',
        'moved_job_crawler_ids',
        'merged_by',
        'undone_at',
        'undone_by',
    ];

    protected $casts = [
        'winner_snapshot' => 'array',
        'loser_snapshot' => 'array',
        'winner_fields_changed' => 'array',
        'moved_job_ids' => 'array',
        'moved_review_ids' => 'array',
        'moved_account_ids' => 'array',
        'moved_ai_image_log_ids' => 'array',
        'moved_job_crawler_ids' => 'array',
        'undone_at' => 'datetime',
    ];

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'winner_company_id');
    }

    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by')->withDefault();
    }

    public function undoneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by')->withDefault();
    }

    public function isUndone(): bool
    {
        return $this->undone_at !== null;
    }

    /**
     * A merge can only be safely undone if the winner company hasn't itself
     * since been absorbed into another company by a later merge.
     */
    public function isUndoableSafely(): bool
    {
        if ($this->isUndone()) {
            return false;
        }

        return ! static::query()
            ->where('loser_company_id', $this->winner_company_id)
            ->where('created_at', '>', $this->created_at)
            ->whereNull('undone_at')
            ->exists();
    }
}
