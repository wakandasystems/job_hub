<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesAgentCommission extends BaseModel
{
    protected $table = 'jb_sales_agent_commissions';

    protected $fillable = [
        'sales_agent_id',
        'order_type',
        'order_id',
        'amount',
        'commission_rate',
        'commission_amount',
        'currency',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'commission_rate' => 'float',
        'commission_amount' => 'float',
        'paid_at' => 'datetime',
    ];

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(SalesAgent::class, 'sales_agent_id');
    }
}
