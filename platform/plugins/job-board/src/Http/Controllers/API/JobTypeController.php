<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Resources\JobTypeResource;
use Botble\JobBoard\Models\JobType;
use Illuminate\Http\Request;

class JobTypeController extends BaseController
{
    public function index(Request $request)
    {
        $jobTypes = JobType::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->oldest('order')
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return $this
            ->httpResponse()
            ->setData(JobTypeResource::collection($jobTypes))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $jobType = JobType::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->find($id);

        if (! $jobType) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.job_type_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new JobTypeResource($jobType))
            ->toApiResponse();
    }
}
