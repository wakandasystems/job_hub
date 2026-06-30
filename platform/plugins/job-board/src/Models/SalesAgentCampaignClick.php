<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesAgentCampaignClick extends BaseModel
{
    protected $table = 'jb_sales_agent_campaign_clicks';

    protected $fillable = [
        'sales_agent_id',
        'campaign_id',
        'ip_address',
        'user_agent',
        'referer',
    ];

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(SalesAgent::class, 'sales_agent_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SalesAgentCampaign::class, 'campaign_id');
    }
}
