<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;

class SocialBroadcast extends BaseModel
{
    protected $table = 'jb_social_broadcasts';

    protected $fillable = [
        'message',
        'image_path',
        'audience',
        'recipient_count',
        'sent_count',
        'failed_count',
        'status',
        'scheduled_at',
        'sent_at',
        'results',
        'sent_recipients',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at'    => 'datetime',
        'sent_at'         => 'datetime',
        'results'         => 'array',
        'sent_recipients' => 'array',
    ];
}
