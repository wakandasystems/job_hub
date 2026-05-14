<?php

namespace Botble\JobBoard\Http\Resources;

use Botble\JobBoard\Models\Category;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'url' => $this->url,
            'icon' => $this->icon,
            'icon_image' => $this->icon_image,
            'is_featured' => $this->is_featured,
            'order' => $this->order,
            'active_jobs_count' => $this->whenCounted('activeJobs'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
