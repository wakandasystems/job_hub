<?php

namespace Botble\JobBoard\Http\Resources;

use Botble\JobBoard\Models\JobApplication;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobApplication
 */
class JobApplicationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'message' => $this->message,
            'status' => $this->status,
            'resume_url' => $this->resume ? asset('storage/' . $this->resume) : null,
            'cover_letter_url' => $this->cover_letter ? asset('storage/' . $this->cover_letter) : null,
            'job' => new JobResource($this->whenLoaded('job')),
            'account' => new AccountResource($this->whenLoaded('account')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
