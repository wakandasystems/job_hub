<?php

namespace Botble\JobBoard\Http\Resources;

use Botble\JobBoard\Models\Review;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'star' => $this->star,
            'comment' => $this->comment,
            'status' => $this->status,
            'reviewable_type' => $this->reviewable_type,
            'reviewable_id' => $this->reviewable_id,
            'account' => new AccountResource($this->whenLoaded('account')),
            'reviewable' => $this->when($this->relationLoaded('reviewable'), function () {
                if ($this->reviewable_type === 'Botble\JobBoard\Models\Company') {
                    return new CompanyResource($this->reviewable);
                }

                return new AccountResource($this->reviewable);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
