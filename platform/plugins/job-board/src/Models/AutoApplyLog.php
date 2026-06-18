<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoApplyLog extends BaseModel
{
    protected $table = 'jb_auto_apply_logs';

    protected $fillable = [
        'account_id',
        'job_id',
        'email_sent_to',
        'ai_email_subject',
        'ai_email_body',
        'ai_model_used',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'ai_cost_usd',
        'match_score',
        'match_reasons',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'match_reasons'     => 'array',
        'match_score'       => 'integer',
        'prompt_tokens'     => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens'      => 'integer',
        'ai_cost_usd'       => 'float',
        'sent_at'           => 'datetime',
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
