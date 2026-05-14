<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Location\Http\Resources\CityResource;
use Botble\Location\Http\Resources\CountryResource;
use Botble\Location\Http\Resources\StateResource;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Illuminate\Http\Request;

class LocationController extends BaseController
{
    public function countries(Request $request)
    {
        if (! is_plugin_active('location')) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.location_plugin_not_active'));
        }

        $countries = Country::query()
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->oldest('name')
            ->paginate(min($request->integer('per_page', 50), 200));

        return $this
            ->httpResponse()
            ->setData(CountryResource::collection($countries))
            ->toApiResponse();
    }

    public function states(int $countryId, Request $request)
    {
        if (! is_plugin_active('location')) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.location_plugin_not_active'));
        }

        $country = Country::find($countryId);

        if (! $country) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.country_not_found'));
        }

        $states = State::query()
            ->where('country_id', $countryId)
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->oldest('name')
            ->paginate(min($request->integer('per_page', 50), 200));

        return $this
            ->httpResponse()
            ->setData(StateResource::collection($states))
            ->toApiResponse();
    }

    public function cities(int $stateId, Request $request)
    {
        if (! is_plugin_active('location')) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.location_plugin_not_active'));
        }

        $state = State::find($stateId);

        if (! $state) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.state_not_found'));
        }

        $cities = City::query()
            ->where('state_id', $stateId)
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->oldest('name')
            ->paginate(min($request->integer('per_page', 50), 200));

        return $this
            ->httpResponse()
            ->setData(CityResource::collection($cities))
            ->toApiResponse();
    }
}
