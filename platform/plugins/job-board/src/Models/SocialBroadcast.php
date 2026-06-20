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
        'recurrence_type',
        'recurrence_time',
        'recurrence_jitter_minutes',
        'recurrence_times_per_day',
        'recurrence_window_start',
        'recurrence_window_end',
        'max_occurrences',
        'occurrence_count',
        'today_occurrences',
        'today_date',
        'next_run_at',
        'ai_spice',
        'last_sent_message',
        'sent_at',
        'results',
        'sent_recipients',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at'    => 'datetime',
        'sent_at'         => 'datetime',
        'next_run_at'     => 'datetime',
        'today_date'      => 'date',
        'ai_spice'        => 'boolean',
        'results'         => 'array',
        'sent_recipients' => 'array',
    ];

    public function isRecurring(): bool
    {
        return ! empty($this->recurrence_type);
    }
}
