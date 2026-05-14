<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Http\Resources\AccountResource;
use Botble\JobBoard\Models\Account;
use Illuminate\Http\Request;

class CandidateController extends BaseController
{
    public function index(Request $request)
    {
        $with = ['educations', 'experiences'];

        if (is_plugin_active('location')) {
            $with = array_merge($with, ['country', 'state', 'city']);
        }

        $candidates = Account::query()
            ->where('type', AccountTypeEnum::JOB_SEEKER)
            ->where('is_public_profile', true)
            ->where('available_for_hiring', true)
            ->with($with)
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where(function ($q) use ($keyword): void {
                    $q->where('first_name', 'LIKE', "%{$keyword}%")
                      ->orWhere('last_name', 'LIKE', "%{$keyword}%")
                      ->orWhere('description', 'LIKE', "%{$keyword}%")
                      ->orWhere('bio', 'LIKE', "%{$keyword}%");
                });
            })
            ->when($request->input('city_id'), function ($query, $cityId): void {
                $query->where('city_id', $cityId);
            })
            ->when($request->input('state_id'), function ($query, $stateId): void {
                $query->where('state_id', $stateId);
            })
            ->when($request->input('country_id'), function ($query, $countryId): void {
                $query->where('country_id', $countryId);
            })
            ->latest()
            ->paginate(min($request->integer('per_page', 12), 50));

        return $this
            ->httpResponse()
            ->setData(AccountResource::collection($candidates))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $with = ['educations', 'experiences', 'reviews'];

        if (is_plugin_active('location')) {
            $with = array_merge($with, ['country', 'state', 'city']);
        }

        $candidate = Account::query()
            ->where('type', AccountTypeEnum::JOB_SEEKER)
            ->where('is_public_profile', true)
            ->with($with)
            ->find($id);

        if (! $candidate) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.candidate_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new AccountResource($candidate))
            ->toApiResponse();
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $limit = min($request->integer('limit', 10), 50);

        $with = ['educations', 'experiences'];

        if (is_plugin_active('location')) {
            $with = array_merge($with, ['country', 'state', 'city']);
        }

        $candidates = Account::query()
            ->where('type', AccountTypeEnum::JOB_SEEKER)
            ->where('is_public_profile', true)
            ->where('available_for_hiring', true)
            ->with($with)
            ->where(function ($q) use ($query): void {
                foreach (explode(' ', $query) as $term) {
                    $q->where(function ($subQuery) use ($term): void {
                        $subQuery->where('first_name', 'LIKE', "%{$term}%")
                                 ->orWhere('last_name', 'LIKE', "%{$term}%")
                                 ->orWhere('description', 'LIKE', "%{$term}%")
                                 ->orWhere('bio', 'LIKE', "%{$term}%");
                    });
                }
            })
            ->limit($limit)
            ->latest()
            ->get();

        return $this
            ->httpResponse()
            ->setData(AccountResource::collection($candidates))
            ->toApiResponse();
    }
}
