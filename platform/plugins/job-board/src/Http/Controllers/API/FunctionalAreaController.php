<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Resources\FunctionalAreaResource;
use Botble\JobBoard\Models\FunctionalArea;
use Illuminate\Http\Request;

class FunctionalAreaController extends BaseController
{
    public function index(Request $request)
    {
        $functionalAreas = FunctionalArea::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->oldest('order')
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return $this
            ->httpResponse()
            ->setData(FunctionalAreaResource::collection($functionalAreas))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $functionalArea = FunctionalArea::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->find($id);

        if (! $functionalArea) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.functional_area_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new FunctionalAreaResource($functionalArea))
            ->toApiResponse();
    }
}
