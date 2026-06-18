<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoApplyPreview extends BaseModel
{
    protected $table = 'jb_auto_apply_previews';

    protected $fillable = [
        'account_id',
        'job_id',
        'ai_model',
        'score',
        'reasons',
        'subject',
        'body',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost_usd',
        'account_profile_synced_at',
    ];

    protected $casts = [
        'reasons'                    => 'array',
        'score'                      => 'integer',
        'prompt_tokens'              => 'integer',
        'completion_tokens'          => 'integer',
        'total_tokens'               => 'integer',
        'cost_usd'                   => 'float',
        'account_profile_synced_at'  => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
}
