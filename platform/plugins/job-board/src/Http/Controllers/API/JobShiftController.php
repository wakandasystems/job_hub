<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Resources\JobShiftResource;
use Botble\JobBoard\Models\JobShift;
use Illuminate\Http\Request;

class JobShiftController extends BaseController
{
    public function index(Request $request)
    {
        $jobShifts = JobShift::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->oldest('order')
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return $this
            ->httpResponse()
            ->setData(JobShiftResource::collection($jobShifts))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $jobShift = JobShift::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->find($id);

        if (! $jobShift) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.job_shift_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new JobShiftResource($jobShift))
            ->toApiResponse();
    }
}
