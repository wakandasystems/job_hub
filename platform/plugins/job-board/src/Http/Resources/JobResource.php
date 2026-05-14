<?php

namespace Botble\JobBoard\Http\Resources;

use Botble\JobBoard\Models\Job;
use Botble\Location\Http\Resources\CityResource;
use Botble\Location\Http\Resources\CountryResource;
use Botble\Location\Http\Resources\StateResource;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Job
 */
class JobResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
            'content' => $this->content,
            'address' => $this->address,
            'status' => $this->status,
            'apply_url' => $this->apply_url,
            'external_apply_behavior' => $this->external_apply_behavior,
            'is_freelance' => $this->is_freelance,
            'salary_from' => $this->salary_from,
            'salary_to' => $this->salary_to,
            'salary_range' => $this->salary_range,
            'salary_type' => $this->salary_type,
            'salary_text' => $this->salaryText,
            'hide_salary' => $this->hide_salary,
            'number_of_positions' => $this->number_of_positions,
            'expire_date' => $this->expire_date,
            'start_date' => $this->start_date,
            'application_closing_date' => $this->application_closing_date,
            'views' => $this->views,
            'number_of_applied' => $this->number_of_applied,
            'hide_company' => $this->hide_company,
            'is_featured' => $this->is_featured,
            'auto_renew' => $this->auto_renew,
            'never_expired' => $this->never_expired,
            'is_job_open' => $this->isJobOpen(),
            'zip_code' => $this->zip_code,
            'unique_id' => $this->unique_id,
            'image' => $this->company->logo ? RvMedia::getImageUrl(
                $this->company->logo,
                'small',
                false,
                RvMedia::getDefaultImage()
            ) : null,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'career_level' => new CareerLevelResource($this->whenLoaded('careerLevel')),
            'job_experience' => new JobExperienceResource($this->whenLoaded('jobExperience')),
            'job_shift' => new JobShiftResource($this->whenLoaded('jobShift')),
            'functional_area' => new FunctionalAreaResource($this->whenLoaded('functionalArea')),
            'job_types' => JobTypeResource::collection($this->whenLoaded('jobTypes')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'skills' => JobSkillResource::collection($this->whenLoaded('skills')),
            'date' => $this->created_at->translatedFormat('M d, Y'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if (is_plugin_active('location')) {
            $data = array_merge($data, [
                'country' => new CountryResource($this->whenLoaded('country')),
                'state' => new StateResource($this->whenLoaded('state')),
                'city' => new CityResource($this->whenLoaded('city')),
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ]);
        }

        return $data;
    }
}
