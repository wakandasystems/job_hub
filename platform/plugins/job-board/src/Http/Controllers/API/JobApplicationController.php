<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Http\Requests\EditJobApplicationRequest;
use Botble\JobBoard\Http\Resources\JobApplicationResource;
use Botble\JobBoard\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JobApplicationController extends BaseController
{
    public function index(Request $request)
    {
        $account = auth('account')->user();

        if ($account->type !== AccountTypeEnum::EMPLOYER) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(trans('plugins/job-board::messages.access_denied_employers_only'));
        }

        $companyIds = $account->companies->pluck('id')->all();

        $applications = JobApplication::query()
            ->whereHas('job', function ($query) use ($companyIds): void {
                $query->whereIn('company_id', $companyIds);
            })
            ->with(['job', 'job.company', 'account'])
            ->when($request->input('job_id'), function ($query, $jobId): void {
                $query->where('job_id', $jobId);
            })
            ->when($request->input('status'), function ($query, $status): void {
                $query->where('status', $status);
            })
            ->when($request->input('company_id'), function ($query, $companyId) use ($companyIds): void {
                if (in_array($companyId, $companyIds)) {
                    $query->whereHas('job', function ($q) use ($companyId): void {
                        $q->where('company_id', $companyId);
                    });
                }
            })
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 50));

        return $this
            ->httpResponse()
            ->setData(JobApplicationResource::collection($applications))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $account = auth('account')->user();

        if ($account->type !== AccountTypeEnum::EMPLOYER) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(trans('plugins/job-board::messages.access_denied_employers_only'));
        }

        $companyIds = $account->companies->pluck('id')->all();

        $application = JobApplication::query()
            ->whereHas('job', function ($query) use ($companyIds): void {
                $query->whereIn('company_id', $companyIds);
            })
            ->with(['job', 'job.company', 'account'])
            ->find($id);

        if (! $application) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.application_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new JobApplicationResource($application))
            ->toApiResponse();
    }

    public function update(int $id, EditJobApplicationRequest $request)
    {
        $account = auth('account')->user();

        if ($account->type !== AccountTypeEnum::EMPLOYER) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(trans('plugins/job-board::messages.access_denied_employers_update'));
        }

        $companyIds = $account->companies->pluck('id')->all();

        $application = JobApplication::query()
            ->whereHas('job', function ($query) use ($companyIds): void {
                $query->whereIn('company_id', $companyIds);
            })
            ->find($id);

        if (! $application) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.application_not_found'));
        }

        $data = $request->validated();
        $application->update($data);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::messages.application_updated_successfully'))
            ->setData(new JobApplicationResource($application))
            ->toApiResponse();
    }

    public function destroy(int $id)
    {
        $account = auth('account')->user();

        if ($account->type !== AccountTypeEnum::EMPLOYER) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(trans('plugins/job-board::messages.access_denied_employers_delete'));
        }

        $companyIds = $account->companies->pluck('id')->all();

        $application = JobApplication::query()
            ->whereHas('job', function ($query) use ($companyIds): void {
                $query->whereIn('company_id', $companyIds);
            })
            ->find($id);

        if (! $application) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.application_not_found'));
        }

        // Delete associated files
        if ($application->resume) {
            Storage::disk('public')->delete($application->resume);
        }

        if ($application->cover_letter) {
            Storage::disk('public')->delete($application->cover_letter);
        }

        $application->delete();

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::messages.application_deleted_successfully'))
            ->toApiResponse();
    }

    public function downloadCv(int $id)
    {
        $account = auth('account')->user();

        if ($account->type !== AccountTypeEnum::EMPLOYER) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(trans('plugins/job-board::messages.access_denied_employers_download'));
        }

        $companyIds = $account->companies->pluck('id')->all();

        $application = JobApplication::query()
            ->whereHas('job', function ($query) use ($companyIds): void {
                $query->whereIn('company_id', $companyIds);
            })
            ->find($id);

        if (! $application) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.application_not_found'));
        }

        if (! $application->resume) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.resume_not_found'));
        }

        $filePath = storage_path('app/public/' . $application->resume);

        if (! file_exists($filePath)) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.resume_file_not_found'));
        }

        return response()->download($filePath);
    }
}
