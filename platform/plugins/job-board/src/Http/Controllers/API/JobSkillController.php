<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Resources\JobSkillResource;
use Botble\JobBoard\Models\JobSkill;
use Illuminate\Http\Request;

class JobSkillController extends BaseController
{
    public function index(Request $request)
    {
        $jobSkills = JobSkill::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->oldest('name')
            ->paginate(min($request->integer('per_page', 50), 100));

        return $this
            ->httpResponse()
            ->setData(JobSkillResource::collection($jobSkills))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $jobSkill = JobSkill::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->find($id);

        if (! $jobSkill) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.job_skill_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new JobSkillResource($jobSkill))
            ->toApiResponse();
    }
}
