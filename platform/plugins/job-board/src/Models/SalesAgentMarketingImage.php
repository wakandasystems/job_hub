<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SalesAgentMarketingImage extends BaseModel
{
    protected $table = 'jb_sales_agent_marketing_images';

    protected $fillable = [
        'sales_agent_id',
        'campaign_id',
        'subject_mode',
        'image_path',
        'status',
        'error_message',
        'cost_usd',
        'generation_ms',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'sent_at',
    ];

    protected $casts = [
        'cost_usd' => 'float',
        'generation_ms' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(SalesAgent::class, 'sales_agent_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SalesAgentCampaign::class, 'campaign_id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function generationMeta(): ?string
    {
        $parts = [];

        if ($this->generation_ms) {
            $parts[] = round($this->generation_ms / 1000, 1) . 's';
        }

        if ($this->cost_usd !== null) {
            $parts[] = '$' . number_format($this->cost_usd, 4);
        }

        if ($this->total_tokens) {
            $parts[] = number_format($this->total_tokens) . ' tokens';
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    public static function subjectModes(): array
    {
        return [
            'nakia' => 'Nakia',
            'agent' => "Agent's photo (default)",
            'both' => 'Nakia + Agent',
        ];
    }
}
