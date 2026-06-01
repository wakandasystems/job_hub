<?php

namespace Botble\JobBoard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAlertNotification extends Model
{
    protected $table = 'jb_job_alert_notifications';

    protected $fillable = ['account_id', 'job_id', 'job_alert_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function jobAlert(): BelongsTo
    {
        return $this->belongsTo(JobAlert::class, 'job_alert_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
