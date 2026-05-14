<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Resources\CategoryResource;
use Botble\JobBoard\Http\Resources\JobResource;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Repositories\Interfaces\CategoryInterface;
use Botble\JobBoard\Repositories\Interfaces\JobInterface;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    public function __construct(
        protected CategoryInterface $categoryRepository,
        protected JobInterface $jobRepository
    ) {
    }

    public function index(Request $request)
    {
        $with = ['slugable', 'metadata'];

        $categories = Category::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with($with)
            ->withCount(['activeJobs'])
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%")
                      ->orWhere('description', 'LIKE', "%{$keyword}%");
            })
            ->oldest('order')
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return $this
            ->httpResponse()
            ->setData(CategoryResource::collection($categories))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $category = Category::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with(['slugable', 'metadata'])
            ->withCount(['activeJobs'])
            ->find($id);

        if (! $category) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.category_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new CategoryResource($category))
            ->toApiResponse();
    }

    public function jobs(int $id, Request $request)
    {
        $category = Category::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with('activeChildren')
            ->find($id);

        if (! $category) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.category_not_found'));
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

        // Get all category IDs including child categories
        $categoryIds = $category->getAllCategoryIds();

        $filters = [
            'job_categories' => $categoryIds,
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
        $limit = min($request->integer('limit', 8), 50);
        $categories = $this->categoryRepository->getFeaturedCategories($limit, ['slugable', 'metadata']);

        return $this
            ->httpResponse()
            ->setData(CategoryResource::collection($categories))
            ->toApiResponse();
    }
}
