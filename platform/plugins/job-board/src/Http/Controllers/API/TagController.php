<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Resources\JobResource;
use Botble\JobBoard\Http\Resources\TagResource;
use Botble\JobBoard\Models\Tag;
use Botble\JobBoard\Repositories\Interfaces\JobInterface;
use Illuminate\Http\Request;

class TagController extends BaseController
{
    public function __construct(protected JobInterface $jobRepository)
    {
    }

    public function index(Request $request)
    {
        $tags = Tag::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with(['slugable'])
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->oldest('name')
            ->paginate(min($request->integer('per_page', 50), 100));

        return $this
            ->httpResponse()
            ->setData(TagResource::collection($tags))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $tag = Tag::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with(['slugable'])
            ->find($id);

        if (! $tag) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.tag_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new TagResource($tag))
            ->toApiResponse();
    }

    public function jobs(int $id, Request $request)
    {
        $tag = Tag::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->find($id);

        if (! $tag) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.tag_not_found'));
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
            'job_tags' => [$id],
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
}
