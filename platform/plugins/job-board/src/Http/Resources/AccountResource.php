<?php

namespace Botble\JobBoard\Http\Resources;

use Botble\JobBoard\Models\Account;
use Botble\Location\Http\Resources\CityResource;
use Botble\Location\Http\Resources\CountryResource;
use Botble\Location\Http\Resources\StateResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Account
 */
class AccountResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar_url,
            'dob' => $this->dob,
            'gender' => $this->gender,
            'description' => $this->description,
            'bio' => $this->bio,
            'address' => $this->address,
            'type' => $this->type,
            'credits' => $this->credits,
            'is_public_profile' => $this->is_public_profile,
            'hide_cv' => $this->hide_cv,
            'available_for_hiring' => $this->available_for_hiring,
            'resume_url' => $this->resume_url,
            'resume_name' => $this->resume_name,
            'cover_letter_url' => $this->cover_letter_url,
            'unique_id' => $this->unique_id,
            'educations' => $this->whenLoaded('educations'),
            'experiences' => $this->whenLoaded('experiences'),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'companies' => CompanyResource::collection($this->whenLoaded('companies')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if (is_plugin_active('location')) {
            $data = array_merge($data, [
                'country' => new CountryResource($this->whenLoaded('country')),
                'state' => new StateResource($this->whenLoaded('state')),
                'city' => new CityResource($this->whenLoaded('city')),
            ]);
        }

        return $data;
    }
}
