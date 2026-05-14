<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Resources\JobExperienceResource;
use Botble\JobBoard\Models\JobExperience;
use Illuminate\Http\Request;

class JobExperienceController extends BaseController
{
    public function index(Request $request)
    {
        $jobExperiences = JobExperience::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->oldest('order')
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return $this
            ->httpResponse()
            ->setData(JobExperienceResource::collection($jobExperiences))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $jobExperience = JobExperience::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->find($id);

        if (! $jobExperience) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.job_experience_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new JobExperienceResource($jobExperience))
            ->toApiResponse();
    }
}
