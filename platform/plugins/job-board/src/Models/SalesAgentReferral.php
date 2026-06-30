<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesAgentReferral extends BaseModel
{
    protected $table = 'jb_sales_agent_referrals';

    protected $fillable = [
        'sales_agent_id',
        'phone',
        'account_id',
        'code_used',
        'source',
        'first_used_at',
    ];

    protected $casts = [
        'first_used_at' => 'datetime',
    ];

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(SalesAgent::class, 'sales_agent_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(\Botble\JobBoard\Models\Account::class, 'account_id');
    }
}
