<?php

namespace Botble\JobBoard\Http\Resources;

use Botble\JobBoard\Models\Company;
use Botble\Location\Http\Resources\CityResource;
use Botble\Location\Http\Resources\CountryResource;
use Botble\Location\Http\Resources\StateResource;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'content' => $this->content,
            'address' => $this->address,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'year_founded' => $this->year_founded,
            'number_of_offices' => $this->number_of_offices,
            'number_of_employees' => $this->number_of_employees,
            'annual_revenue' => $this->annual_revenue,
            'ceo' => $this->ceo,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'postal_code' => $this->postal_code,
            'tax_id' => $this->tax_id,
            'unique_id' => $this->unique_id,
            'accounts' => AccountResource::collection($this->whenLoaded('accounts')),
            'logo' => RvMedia::getImageUrl($this->logo),
            'logo_thumb' => $this->logo_thumb,
            'cover_image_url' => $this->cover_image_url,
            'url' => $this->url,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'facebook' => $this->facebook,
            'twitter' => $this->twitter,
            'linkedin' => $this->linkedin,
            'instagram' => $this->instagram,
            'active_jobs_count' => $this->whenCounted('activeJobs'),
            'reviews_count' => $this->whenCounted('reviews'),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
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
