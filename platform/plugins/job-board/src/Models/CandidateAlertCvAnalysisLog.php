<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateAlertCvAnalysisLog extends BaseModel
{
    protected $table = 'jb_candidate_alert_cv_analysis_logs';

    protected $fillable = [
        'candidate_alert_id',
        'admin_id',
        'original_filename',
        'mime_type',
        'file_size',
        'ai_provider',
        'ai_model',
        'status',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost_usd',
        'processing_ms',
        'extracted_characters',
        'error_message',
    ];

    protected $casts = [
        'candidate_alert_id' => 'integer',
        'admin_id' => 'integer',
        'file_size' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost_usd' => 'decimal:6',
        'processing_ms' => 'integer',
        'extracted_characters' => 'integer',
    ];

    public function candidateAlert(): BelongsTo
    {
        return $this->belongsTo(CandidateAlert::class, 'candidate_alert_id');
    }
}
