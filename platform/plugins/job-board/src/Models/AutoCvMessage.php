<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoCvMessage extends BaseModel
{
    protected $table = 'jb_auto_cv_messages';

    protected $fillable = [
        'session_id',
        'direction',
        'body',
        'whapi_message_id',
        'whapi_payload',
    ];

    protected $casts = [
        'session_id' => 'integer',
        'whapi_payload' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AutoCvSession::class, 'session_id');
    }
}
