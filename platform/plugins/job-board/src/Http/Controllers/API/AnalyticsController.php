<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Analytics;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends BaseController
{
    public function jobAnalytics(int $id, Request $request)
    {
        $job = Job::find($id);

        if (! $job) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.job_not_found'));
        }

        $period = $request->input('period', '30'); // days
        $startDate = Carbon::now()->subDays((int) $period);

        $analytics = Analytics::query()
            ->where('reference_type', Job::class)
            ->where('reference_id', $id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->oldest('date')
            ->get();

        $totalViews = $job->views;
        $totalApplications = $job->number_of_applied;
        $periodViews = $analytics->sum('views');

        return $this
            ->httpResponse()
            ->setData([
                'job_id' => $id,
                'job_title' => $job->name,
                'total_views' => $totalViews,
                'total_applications' => $totalApplications,
                'period_views' => $periodViews,
                'period_days' => $period,
                'daily_analytics' => $analytics,
                'conversion_rate' => $totalViews > 0 ? round(($totalApplications / $totalViews) * 100, 2) : 0,
            ])
            ->toApiResponse();
    }

    public function companyAnalytics(int $id, Request $request)
    {
        $company = Company::find($id);

        if (! $company) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.company_not_found'));
        }

        $period = $request->input('period', '30'); // days
        $startDate = Carbon::now()->subDays((int) $period);

        // Get company analytics
        $analytics = Analytics::query()
            ->where('reference_type', Company::class)
            ->where('reference_id', $id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->oldest('date')
            ->get();

        // Get job analytics for this company
        $jobAnalytics = Analytics::query()
            ->where('reference_type', Job::class)
            ->whereIn('reference_id', $company->jobs()->pluck('id'))
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->oldest('date')
            ->get();

        $totalJobs = $company->jobs()->count();
        $activeJobs = $company->activeJobs()->count();
        $totalApplications = $company->jobs()->sum('number_of_applied');
        $periodViews = $analytics->sum('views');
        $periodJobViews = $jobAnalytics->sum('views');

        return $this
            ->httpResponse()
            ->setData([
                'company_id' => $id,
                'company_name' => $company->name,
                'total_jobs' => $totalJobs,
                'active_jobs' => $activeJobs,
                'total_applications' => $totalApplications,
                'period_company_views' => $periodViews,
                'period_job_views' => $periodJobViews,
                'period_days' => $period,
                'daily_company_analytics' => $analytics,
                'daily_job_analytics' => $jobAnalytics,
            ])
            ->toApiResponse();
    }
}
