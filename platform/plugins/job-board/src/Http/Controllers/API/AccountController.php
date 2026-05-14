<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Http\Requests\AccountJobRequest;
use Botble\JobBoard\Http\Requests\SettingRequest;
use Botble\JobBoard\Http\Resources\AccountResource;
use Botble\JobBoard\Http\Resources\CompanyResource;
use Botble\JobBoard\Http\Resources\JobApplicationResource;
use Botble\JobBoard\Http\Resources\JobResource;
use Botble\JobBoard\Http\Resources\TransactionResource;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobApplication;
use Botble\JobBoard\Repositories\Interfaces\JobInterface;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\Request;

/**
 * @group Account Management
 */
class AccountController extends BaseController
{
    public function __construct(protected JobInterface $jobRepository)
    {
    }

    /**
     * Get user profile
     *
     * Get the authenticated user's profile information.
     *
     * @authenticated
     */
    public function profile()
    {
        $account = auth('account')->user();

        $with = ['companies', 'educations', 'experiences'];

        if (is_plugin_active('location')) {
            $with = array_merge($with, ['country', 'state', 'city']);
        }

        $account->load($with);

        return $this
            ->httpResponse()
            ->setData(new AccountResource($account))
            ->toApiResponse();
    }

    /**
     * Update user profile
     *
     * Update the authenticated user's profile information.
     *
     * @authenticated
     */
    public function updateProfile(SettingRequest $request)
    {
        $account = auth('account')->user();

        if (! $account) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(401)
                ->setMessage(trans('plugins/job-board::messages.unauthorized'));
        }

        $data = $request->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $avatar = RvMedia::handleUpload($request->file('avatar'), 0, 'accounts');
            if ($avatar['error'] === false) {
                $data['avatar_id'] = $avatar['data']->id;
            }
        }

        // Handle resume upload
        if ($request->hasFile('resume')) {
            $resume = $request->file('resume')->store('resumes', 'public');
            $data['resume'] = $resume;
        }

        // Handle cover letter upload
        if ($request->hasFile('cover_letter')) {
            $coverLetter = $request->file('cover_letter')->store('cover-letters', 'public');
            $data['cover_letter'] = $coverLetter;
        }

        $account->update($data);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::messages.profile_updated_successfully'))
            ->setData(new AccountResource($account))
            ->toApiResponse();
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $account = auth('account')->user();

        $avatar = RvMedia::handleUpload($request->file('avatar'), 0, 'accounts');

        if ($avatar['error'] === false) {
            $account->update(['avatar_id' => $avatar['data']->id]);

            return $this
                ->httpResponse()
                ->setMessage(trans('plugins/job-board::messages.avatar_uploaded_successfully'))
                ->setData(['avatar_url' => $account->avatar_url])
                ->toApiResponse();
        }

        return $this
            ->httpResponse()
            ->setError()
            ->setMessage($avatar['message'])
            ->toApiResponse();
    }

    public function applications(Request $request)
    {
        $account = auth('account')->user();

        $applications = JobApplication::query()
            ->where('account_id', $account->id)
            ->with(['job', 'job.company', 'job.company.slugable'])
            ->when($request->input('status'), function ($query, $status): void {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(min($request->integer('per_page', 12), 50));

        return $this
            ->httpResponse()
            ->setData(JobApplicationResource::collection($applications))
            ->toApiResponse();
    }

    public function showApplication(int $id)
    {
        $account = auth('account')->user();

        $application = JobApplication::query()
            ->where('account_id', $account->id)
            ->with(['job', 'job.company'])
            ->find($id);

        if (! $application) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.application_not_found_simple'));
        }

        return $this
            ->httpResponse()
            ->setData(new JobApplicationResource($application))
            ->toApiResponse();
    }

    public function deleteApplication(int $id)
    {
        $account = auth('account')->user();

        $application = JobApplication::query()
            ->where('account_id', $account->id)
            ->find($id);

        if (! $application) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.application_not_found_simple'));
        }

        $application->delete();

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::messages.application_deleted_successfully'))
            ->toApiResponse();
    }

    public function savedJobs(Request $request)
    {
        $account = auth('account')->user();

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

        $savedJobs = $account->savedJobs()
            ->with($with)
            ->paginate(min($request->integer('per_page', 12), 50));

        return $this
            ->httpResponse()
            ->setData(JobResource::collection($savedJobs))
            ->toApiResponse();
    }

    public function saveJob(int $jobId)
    {
        $account = auth('account')->user();

        $job = Job::find($jobId);

        if (! $job) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.job_not_found'));
        }

        if ($account->savedJobs()->where('job_id', $jobId)->exists()) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(400)
                ->setMessage(trans('plugins/job-board::messages.job_already_saved'));
        }

        $account->savedJobs()->attach($jobId);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::messages.job_saved_successfully'))
            ->toApiResponse();
    }

    public function unsaveJob(int $jobId)
    {
        $account = auth('account')->user();

        $account->savedJobs()->detach($jobId);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::messages.job_removed_from_saved'))
            ->toApiResponse();
    }

    public function companies()
    {
        $account = auth('account')->user();

        if ($account->type !== AccountTypeEnum::EMPLOYER) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(trans('plugins/job-board::messages.access_denied_employers_endpoint'));
        }

        $with = ['accounts'];

        if (is_plugin_active('location')) {
            $with = array_merge($with, ['country', 'state', 'city']);
        }

        $companies = $account->companies()->with($with)->get();

        return $this
            ->httpResponse()
            ->setData(CompanyResource::collection($companies))
            ->toApiResponse();
    }

    public function jobs(Request $request)
    {
        $account = auth('account')->user();

        if ($account->type !== AccountTypeEnum::EMPLOYER) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(trans('plugins/job-board::messages.access_denied_employers_endpoint'));
        }

        $companyIds = $account->companies->pluck('id')->all();

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
            'condition' => [
                'jb_jobs.company_id' => $companyIds,
            ],
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

    public function createJob(AccountJobRequest $request)
    {
        $account = auth('account')->user();

        if (! $account) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(401)
                ->setMessage(trans('plugins/job-board::messages.unauthorized'));
        }

        if ($account->type !== AccountTypeEnum::EMPLOYER) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(trans('plugins/job-board::messages.access_denied_employers_create'));
        }

        if (! $account->canPost()) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(400)
                ->setMessage(trans('plugins/job-board::messages.insufficient_credits'));
        }

        $data = $request->validated();
        $data['author_id'] = $account->id;
        $data['author_type'] = get_class($account);

        $job = Job::create($data);

        // Deduct credits if credit system is enabled
        if (setting('job_board_enable_credits_system', false)) {
            $account->decrement('credits');
        }

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::messages.job_created_successfully'))
            ->setData(new JobResource($job))
            ->toApiResponse();
    }

    public function updateJob(int $id, AccountJobRequest $request)
    {
        $account = auth('account')->user();

        if (! $account) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(401)
                ->setMessage(trans('plugins/job-board::messages.unauthorized'));
        }

        if ($account->type !== AccountTypeEnum::EMPLOYER) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(trans('plugins/job-board::messages.access_denied_employers_update_jobs'));
        }

        $companyIds = $account->companies->pluck('id')->all();

        $job = Job::query()
            ->whereIn('company_id', $companyIds)
            ->find($id);

        if (! $job) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.job_not_found_or_access_denied'));
        }

        $data = $request->validated();
        $job->update($data);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::messages.job_updated_successfully'))
            ->setData(new JobResource($job))
            ->toApiResponse();
    }

    public function deleteJob(int $id)
    {
        $account = auth('account')->user();

        if ($account->type !== AccountTypeEnum::EMPLOYER) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(trans('plugins/job-board::messages.access_denied_employers_delete_jobs'));
        }

        $companyIds = $account->companies->pluck('id')->all();

        $job = Job::query()
            ->whereIn('company_id', $companyIds)
            ->find($id);

        if (! $job) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.job_not_found_or_access_denied'));
        }

        $job->delete();

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::messages.job_deleted_successfully'))
            ->toApiResponse();
    }

    public function transactions(Request $request)
    {
        $account = auth('account')->user();

        $transactions = $account->transactions()
            ->with(['payment'])
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 50));

        return $this
            ->httpResponse()
            ->setData(TransactionResource::collection($transactions))
            ->toApiResponse();
    }

    public function invoices(Request $request)
    {
        $account = auth('account')->user();

        $invoices = $account->invoices()
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 50));

        return $this
            ->httpResponse()
            ->setData($invoices)
            ->toApiResponse();
    }

    public function showInvoice(int $id)
    {
        $account = auth('account')->user();

        $invoice = $account->invoices()->find($id);

        if (! $invoice) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.invoice_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData($invoice)
            ->toApiResponse();
    }
}
