<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Resources\CareerLevelResource;
use Botble\JobBoard\Models\CareerLevel;
use Illuminate\Http\Request;

class CareerLevelController extends BaseController
{
    public function index(Request $request)
    {
        $careerLevels = CareerLevel::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->oldest('order')
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return $this
            ->httpResponse()
            ->setData(CareerLevelResource::collection($careerLevels))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $careerLevel = CareerLevel::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->find($id);

        if (! $careerLevel) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.career_level_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new CareerLevelResource($careerLevel))
            ->toApiResponse();
    }
}
