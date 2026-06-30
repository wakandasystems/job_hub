<?php

namespace Botble\JobBoard\Models;

use Botble\ACL\Models\User;
use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesAgentCampaignVersion extends BaseModel
{
    protected $table = 'jb_sales_agent_campaign_versions';

    protected $fillable = [
        'campaign_id',
        'created_by',
        'restored_from_version_id',
        'label',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SalesAgentCampaign::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function restoredFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'restored_from_version_id');
    }
}
