<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutoCvSession extends BaseModel
{
    protected $table = 'jb_auto_cv_sessions';

    protected $fillable = [
        'admin_id',
        'candidate_name',
        'whatsapp_number',
        'status',
        'topics',
        'topics_covered',
        'section_scores',
        'answers',
        'structured_cv',
        'suggested_job_positions',
        'conversation_text',
        'last_question_text',
        'docx_path',
        'pdf_path',
        'cv_document_paths',
        'ai_total_prompt_tokens',
        'ai_total_completion_tokens',
        'ai_total_cost_usd',
        'ai_calls',
        'error_message',
        'error_trace',
        'last_question_sent_at',
        'last_reply_at',
        'completed_at',
        'admin_notified_at',
        'sent_to_candidate_at',
        'candidate_reminder_count',
        'last_candidate_reminder_sent_at',
        'awaiting_final_confirmation',
    ];

    protected $casts = [
        'admin_id' => 'integer',
        'topics' => 'array',
        'topics_covered' => 'array',
        'section_scores' => 'array',
        'answers' => 'array',
        'structured_cv' => 'array',
        'suggested_job_positions' => 'array',
        'cv_document_paths' => 'array',
        'ai_calls' => 'array',
        'ai_total_prompt_tokens' => 'integer',
        'ai_total_completion_tokens' => 'integer',
        'ai_total_cost_usd' => 'float',
        'last_question_sent_at' => 'datetime',
        'last_reply_at' => 'datetime',
        'completed_at' => 'datetime',
        'admin_notified_at' => 'datetime',
        'sent_to_candidate_at' => 'datetime',
        'candidate_reminder_count' => 'integer',
        'last_candidate_reminder_sent_at' => 'datetime',
        'awaiting_final_confirmation' => 'boolean',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AutoCvMessage::class, 'session_id');
    }
}
