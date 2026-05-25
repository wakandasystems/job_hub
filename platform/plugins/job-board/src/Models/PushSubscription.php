<?php

namespace Botble\JobBoard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $table = 'push_subscriptions';

    protected $fillable = ['account_id', 'country_id', 'endpoint', 'p256dh', 'auth'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
