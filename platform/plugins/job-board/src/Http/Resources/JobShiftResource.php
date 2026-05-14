<?php

namespace Botble\JobBoard\Http\Resources;

use Botble\JobBoard\Models\JobShift;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobShift
 */
class JobShiftResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'order' => $this->order,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
