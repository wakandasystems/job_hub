<?php

namespace Botble\JobBoard\Http\Resources;

use Botble\JobBoard\Models\Currency;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Currency
 */
class CurrencyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'symbol' => $this->symbol,
            'is_prefix_symbol' => $this->is_prefix_symbol,
            'order' => $this->order,
            'decimals' => $this->decimals,
            'is_default' => $this->is_default,
            'exchange_rate' => $this->exchange_rate,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
