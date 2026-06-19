<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateAlertCvBuilderMessage extends BaseModel
{
    protected $table = 'jb_candidate_alert_cv_builder_messages';

    protected $fillable = [
        'session_id',
        'direction',
        'question_index',
        'body',
        'whapi_response',
        'sent_at',
    ];

    protected $casts = [
        'session_id' => 'integer',
        'question_index' => 'integer',
        'whapi_response' => 'array',
        'sent_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CandidateAlertCvBuilderSession::class, 'session_id');
    }
}
