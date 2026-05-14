<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Resources\CompanyResource;
use Botble\JobBoard\Http\Resources\JobResource;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Repositories\Interfaces\CompanyInterface;
use Botble\JobBoard\Repositories\Interfaces\JobInterface;
use Botble\Language\Facades\Language;
use Illuminate\Http\Request;

/**
 * @group Companies
 */
class CompanyController extends BaseController
{
    public function __construct(
        protected CompanyInterface $companyRepository,
        protected JobInterface $jobRepository
    ) {
    }

    public function index(Request $request)
    {
        $with = ['slugable', 'accounts'];

        if (is_plugin_active('location')) {
            $with = array_merge($with, ['country', 'state', 'city']);
        }

        $companies = Company::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with($with)
            ->withCount(['activeJobs'])
            ->when($request->input('keyword'), function ($query, $keyword): void {
                if (
                    is_plugin_active('language') &&
                    is_plugin_active('language-advanced') &&
                    Language::getCurrentLocale() != Language::getDefaultLocale()
                ) {
                    $query->where(function ($query) use ($keyword): void {
                        $query->where('name', 'LIKE', "%{$keyword}%")
                            ->orWhere('description', 'LIKE', "%{$keyword}%")
                            ->orWhereHas('translations', function ($query) use ($keyword): void {
                                $query->where('name', 'LIKE', "%{$keyword}%")
                                    ->orWhere('description', 'LIKE', "%{$keyword}%");
                            });
                    });
                } else {
                    $query->where('name', 'LIKE', "%{$keyword}%")
                        ->orWhere('description', 'LIKE', "%{$keyword}%");
                }
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
            ->pinFeatured()
            ->orderBy('name')
            ->paginate(min($request->integer('per_page', 12), 50));

        return $this
            ->httpResponse()
            ->setData(CompanyResource::collection($companies))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $with = ['slugable', 'accounts', 'reviews'];

        if (is_plugin_active('location')) {
            $with = array_merge($with, ['country', 'state', 'city']);
        }

        $company = Company::query()
            ->wherePublished()
            ->with($with)
            ->withCount(['activeJobs', 'reviews'])
            ->find($id);

        if (! $company) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.company_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new CompanyResource($company))
            ->toApiResponse();
    }

    public function jobs(int $id, Request $request)
    {
        $company = Company::query()
            ->wherePublished()
            ->find($id);

        if (! $company) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.company_not_found'));
        }

        $with = [
            'slugable',
            'company',
            'company.slugable',
            'jobTypes',
            'categories',
            'currency',
        ];

        if (is_plugin_active('location')) {
            $with = array_merge($with, ['state', 'city']);
        }

        $filters = [
            'condition' => ['jb_jobs.company_id' => $id],
        ];

        $params = [
            'paginate' => [
                'per_page' => min($request->integer('per_page', 12), 50),
                'current_paged' => $request->integer('page', 1),
            ],
            'with' => $with,
        ];

        $jobs = $this->jobRepository->getJobs($filters, $params);

        return $this
            ->httpResponse()
            ->setData(JobResource::collection($jobs))
            ->toApiResponse();
    }

    public function featured(Request $request)
    {
        $with = ['slugable', 'accounts'];

        if (is_plugin_active('location')) {
            $with = array_merge($with, ['country', 'state', 'city']);
        }

        $limit = min($request->integer('limit', 10), 50);

        $companies = Company::query()
            ->wherePublished()
            ->where('is_featured', true)
            ->with($with)
            ->withCount(['activeJobs'])
            ->limit($limit)
            ->latest()
            ->get();

        return $this
            ->httpResponse()
            ->setData(CompanyResource::collection($companies))
            ->toApiResponse();
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $limit = min($request->integer('limit', 10), 50);
        $paginate = $request->boolean('paginate') ? min($request->integer('per_page', 10), 50) : null;

        $companies = $this->companyRepository->getSearch($query, $limit, $paginate);

        return $this
            ->httpResponse()
            ->setData(CompanyResource::collection($companies))
            ->toApiResponse();
    }
}
