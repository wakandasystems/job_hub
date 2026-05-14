<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Resources\PackageResource;
use Botble\JobBoard\Models\Package;
use Illuminate\Http\Request;

class PackageController extends BaseController
{
    public function index(Request $request)
    {
        $packages = Package::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with(['currency'])
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%")
                      ->orWhere('description', 'LIKE', "%{$keyword}%");
            })
            ->oldest('order')
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 50));

        return $this
            ->httpResponse()
            ->setData(PackageResource::collection($packages))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $package = Package::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with(['currency'])
            ->find($id);

        if (! $package) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::general.package_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new PackageResource($package))
            ->toApiResponse();
    }
}
