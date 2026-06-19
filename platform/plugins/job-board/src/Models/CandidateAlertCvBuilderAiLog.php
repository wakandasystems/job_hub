<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateAlertCvBuilderAiLog extends BaseModel
{
    protected $table = 'jb_candidate_alert_cv_builder_ai_logs';

    protected $fillable = [
        'session_id',
        'admin_id',
        'ai_provider',
        'ai_model',
        'endpoint',
        'status',
        'request_payload',
        'response_payload',
        'response_headers',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost_usd',
        'processing_ms',
        'error_message',
    ];

    protected $casts = [
        'session_id' => 'integer',
        'admin_id' => 'integer',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'response_headers' => 'array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost_usd' => 'decimal:6',
        'processing_ms' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CandidateAlertCvBuilderSession::class, 'session_id');
    }
}
