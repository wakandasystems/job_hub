<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Events\JobAppliedEvent;
use Botble\JobBoard\Http\Requests\ApplyJobRequest;
use Botble\JobBoard\Http\Resources\JobResource;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobApplication;
use Botble\JobBoard\Repositories\Interfaces\JobInterface;
use Illuminate\Http\Request;

/**
 * @group Jobs
 */
class JobController extends BaseController
{
    public function __construct(protected JobInterface $jobRepository)
    {
    }

    /**
     * List jobs
     *
     * Get a paginated list of jobs with filtering options.
     */
    public function index(Request $request)
    {
        $with = [
            'slugable',
            'company',
            'company.slugable',
            'company.accounts',
            'jobTypes',
            'categories',
            'tags',
            'skills',
            'currency',
        ];

        if (is_plugin_active('location')) {
            $with = array_merge($with, [
                'state',
                'city',
                'company.country',
                'company.state',
                'company.city',
            ]);
        }

        $filters = [
            'keyword' => $request->input('keyword'),
            'company_id' => $request->input('company_id'),
            'job_categories' => $request->input('categories', []),
            'job_types' => $request->input('job_types', []),
            'job_experiences' => $request->input('job_experiences', []),
            'job_skills' => $request->input('job_skills', []),
            'offered_salary_from' => $request->input('salary_from'),
            'offered_salary_to' => $request->input('salary_to'),
            'date_posted' => $request->input('date_posted'),
            'city_id' => $request->input('city_id'),
            'state_id' => $request->input('state_id'),
            'location' => $request->input('location'),
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

    public function show(int $id)
    {
        $with = [
            'slugable',
            'company',
            'company.slugable',
            'company.accounts',
            'jobTypes',
            'categories',
            'tags',
            'skills',
            'currency',
            'careerLevel',
            'jobExperience',
            'jobShift',
            'functionalArea',
            'degreeLevel',
        ];

        if (is_plugin_active('location')) {
            $with = array_merge($with, [
                'state',
                'city',
                'country',
                'company.country',
                'company.state',
                'company.city',
            ]);
        }

        $job = $this->jobRepository->findById($id, $with);

        if (! $job || $job->status !== JobStatusEnum::PUBLISHED) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.job_not_found'));
        }

        // Increment view count
        $job->increment('views');

        return $this
            ->httpResponse()
            ->setData(new JobResource($job))
            ->toApiResponse();
    }

    public function featured(Request $request)
    {
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

        $limit = min($request->integer('limit', 10), 50);
        $jobs = $this->jobRepository->getFeaturedJobs($limit, $with);

        return $this
            ->httpResponse()
            ->setData(JobResource::collection($jobs))
            ->toApiResponse();
    }

    public function recent(Request $request)
    {
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

        $limit = min($request->integer('limit', 10), 50);
        $jobs = $this->jobRepository->getRecentJobs($limit, $with);

        return $this
            ->httpResponse()
            ->setData(JobResource::collection($jobs))
            ->toApiResponse();
    }

    public function popular(Request $request)
    {
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

        $limit = min($request->integer('limit', 10), 50);
        $jobs = $this->jobRepository->getPopularJobs($limit, $with);

        return $this
            ->httpResponse()
            ->setData(JobResource::collection($jobs))
            ->toApiResponse();
    }

    public function related(int $id, Request $request)
    {
        $job = $this->jobRepository->findById($id);

        if (! $job || $job->status !== JobStatusEnum::PUBLISHED) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.job_not_found'));
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

        $limit = min($request->integer('limit', 5), 20);

        // Get related jobs based on same category or company
        $relatedJobs = Job::query()
            ->where('status', JobStatusEnum::PUBLISHED)
            ->where('id', '!=', $id)
            ->where(function ($query) use ($job): void {
                $query->where('company_id', $job->company_id)
                      ->orWhereHas('categories', function ($q) use ($job): void {
                          $q->whereIn('category_id', $job->categories->pluck('id'));
                      });
            })
            ->with($with)
            ->limit($limit)
            ->latest()
            ->get();

        return $this
            ->httpResponse()
            ->setData(JobResource::collection($relatedJobs))
            ->toApiResponse();
    }

    /**
     * Apply for a job
     *
     * Submit an application for a specific job. Requires authentication.
     *
     * @authenticated
     * @group Jobs
     */
    public function apply(int $id, ApplyJobRequest $request)
    {
        $job = $this->jobRepository->findById($id);

        if (! $job || $job->status !== JobStatusEnum::PUBLISHED) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.job_not_found'));
        }

        if (! $job->isJobOpen()) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(400)
                ->setMessage(trans('plugins/job-board::messages.job_no_longer_accepting'));
        }

        $data = $request->validated();

        // Handle file uploads
        if ($request->hasFile('resume')) {
            $data['resume'] = $request->file('resume')->store('resumes', 'public');
        }

        if ($request->hasFile('cover_letter')) {
            $data['cover_letter'] = $request->file('cover_letter')->store('cover-letters', 'public');
        }

        // Create job application
        $application = JobApplication::create([
            'job_id' => $job->id,
            'account_id' => auth('account')->id(),
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'resume' => $data['resume'] ?? null,
            'cover_letter' => $data['cover_letter'] ?? null,
            'status' => 'pending',
        ]);

        // Increment application count
        $job->increment('number_of_applied');

        // Fire event
        event(new JobAppliedEvent($job, $application));

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::messages.application_submitted_successfully'))
            ->setData(['application_id' => $application->id])
            ->toApiResponse();
    }
}
