<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateAlertCvBuilderSession extends BaseModel
{
    protected $table = 'jb_candidate_alert_cv_builder_sessions';

    protected $fillable = [
        'candidate_alert_id',
        'admin_id',
        'candidate_name',
        'whatsapp_number',
        'status',
        'current_question_index',
        'questions',
        'conversation_text',
        'structured_cv',
        'docx_path',
        'pdf_path',
        'error_message',
        'last_sent_at',
        'completed_at',
    ];

    protected $casts = [
        'candidate_alert_id' => 'integer',
        'admin_id' => 'integer',
        'current_question_index' => 'integer',
        'questions' => 'array',
        'structured_cv' => 'array',
        'last_sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function candidateAlert(): BelongsTo
    {
        return $this->belongsTo(CandidateAlert::class, 'candidate_alert_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CandidateAlertCvBuilderMessage::class, 'session_id');
    }

    public function aiLogs(): HasMany
    {
        return $this->hasMany(CandidateAlertCvBuilderAiLog::class, 'session_id');
    }
}
