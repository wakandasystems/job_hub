<?php

namespace Botble\JobBoard\Http\Resources;

use Botble\JobBoard\Models\JobSkill;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobSkill
 */
class JobSkillResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
