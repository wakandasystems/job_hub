<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobApplication;
use Botble\JobBoard\Models\JobType;
use Botble\JobBoard\Repositories\Interfaces\AnalyticsInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseController
{
    public function __construct(protected AnalyticsInterface $analyticsRepository)
    {
    }

    public function index()
    {
        $this->pageTitle(trans('plugins/job-board::job-board.reports.title'));

        Assets::addScripts(['counterup', 'equal-height', 'apexchart'])
            ->addStyles(['apexchart'])
            ->addStylesDirectly('vendor/core/core/dashboard/css/dashboard.css')
            ->usingVueJS();

        // Job statistics
        $totalJobs = Job::query()->count();
        $activeJobs = Job::query()->where('status', JobStatusEnum::PUBLISHED)->count();
        $expiredJobs = Job::query()->where('status', JobStatusEnum::CLOSED)->count();
        $featuredJobs = Job::query()->where('is_featured', true)->count();

        // Application statistics
        $totalApplications = JobApplication::query()->count();
        $applicationsByStatus = JobApplication::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        // Company statistics
        $totalCompanies = Company::query()->count();
        $featuredCompanies = Company::query()->where('is_featured', true)->count();

        // Job views statistics
        $mostViewedJobs = Job::query()->latest('views')
            ->limit(10)
            ->get(['id', 'name', 'views']);

        // Application trends (last 30 days)
        $applicationTrends = $this->getApplicationTrends();

        // Job category distribution
        $jobsByCategory = $this->getJobsByCategory();

        // Job type distribution
        $jobsByType = $this->getJobsByType();

        // Geographic distribution of applications
        $applicationsByLocation = $this->getApplicationsByLocation();

        $data = compact(
            'totalJobs',
            'activeJobs',
            'expiredJobs',
            'featuredJobs',
            'totalApplications',
            'applicationsByStatus',
            'totalCompanies',
            'featuredCompanies',
            'mostViewedJobs',
            'applicationTrends',
            'jobsByCategory',
            'jobsByType',
            'applicationsByLocation'
        );

        return view('plugins/job-board::reports', $data);
    }

    protected function getApplicationTrends()
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $applications = JobApplication::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->oldest('date')
            ->get()
            ->keyBy('date');

        $dates = [];
        $counts = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            $dates[] = $date->format('M d');
            $counts[] = $applications->get($formattedDate)?->total ?? 0;
        }

        return [
            'dates' => $dates,
            'counts' => $counts,
        ];
    }

    protected function getJobsByCategory()
    {
        $categories = Category::query()
            ->withCount(['jobs' => function (Builder $query): void {
                $query->where('status', JobStatusEnum::PUBLISHED);
            }])
            ->latest('jobs_count')
            ->limit(10)
            ->get();

        return [
            'labels' => $categories->pluck('name')->toArray(),
            'counts' => $categories->pluck('jobs_count')->toArray(),
        ];
    }

    protected function getJobsByType()
    {
        $jobTypes = JobType::query()
            ->withCount(['jobs' => function (Builder $query): void {
                $query->where('status', JobStatusEnum::PUBLISHED);
            }])
            ->latest('jobs_count')
            ->limit(10)
            ->get();

        return [
            'labels' => $jobTypes->pluck('name')->toArray(),
            'counts' => $jobTypes->pluck('jobs_count')->toArray(),
        ];
    }

    protected function getApplicationsByLocation()
    {
        $applications = JobApplication::query()
            ->join('jb_jobs', 'jb_applications.job_id', '=', 'jb_jobs.id')
            ->join('countries', 'jb_jobs.country_id', '=', 'countries.id')
            ->select('countries.name', DB::raw('count(*) as total'))
            ->groupBy('countries.name')
            ->latest('total')
            ->limit(10)
            ->get();

        return [
            'labels' => $applications->pluck('name')->toArray(),
            'counts' => $applications->pluck('total')->toArray(),
        ];
    }
}
