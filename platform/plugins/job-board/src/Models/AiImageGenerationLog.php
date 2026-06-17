<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiImageGenerationLog extends BaseModel
{
    protected $table = 'jb_ai_image_generation_logs';

    protected $fillable = [
        'job_id',
        'company_id',
        'source_type',
        'source_id',
        'source_title',
        'slot_type',
        'status',
        'model',
        'quality',
        'background',
        'output_format',
        'output_compression',
        'request_size',
        'target_width',
        'target_height',
        'stored_path',
        'latency_ms',
        'input_tokens',
        'input_text_tokens',
        'input_image_tokens',
        'output_tokens',
        'total_tokens',
        'estimated_cost_usd',
        'api_request_id',
        'error_message',
        'response_meta',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'target_width' => 'integer',
        'target_height' => 'integer',
        'latency_ms' => 'integer',
        'input_tokens' => 'integer',
        'input_text_tokens' => 'integer',
        'input_image_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost_usd' => 'decimal:6',
        'response_meta' => 'array',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id')->withDefault();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withDefault();
    }
}
