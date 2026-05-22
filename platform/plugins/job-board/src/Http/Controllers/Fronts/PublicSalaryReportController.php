<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\JobBoard\Models\SalaryReport;
use Botble\JobBoard\Models\SalaryReportPurchase;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class PublicSalaryReportController extends Controller
{
    public function index()
    {
        $reports = SalaryReport::query()
            ->where('is_published', true)
            ->latest()
            ->get();

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add('Salary Reports');

        return Theme::scope('job-board.salary-reports', compact('reports'))->render();
    }

    public function show(string $slug)
    {
        $report = SalaryReport::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add('Salary Reports', route('salary-reports.public.index'))
            ->add($report->title);

        return Theme::scope('job-board.salary-report-detail', compact('report'))->render();
    }

    public function download(string $token)
    {
        $purchase = SalaryReportPurchase::query()
            ->with('report')
            ->where('access_token', $token)
            ->firstOrFail();

        if ($purchase->isExpired()) {
            abort(410, 'This download link has expired. Please contact support.');
        }

        $report = $purchase->report;

        if (! $report->file_path || ! Storage::disk('local')->exists($report->file_path)) {
            abort(404, 'Report file is not yet available. Please contact support.');
        }

        $purchase->update(['downloaded_at' => now()]);

        return Storage::disk('local')->download(
            $report->file_path,
            \Illuminate\Support\Str::slug($report->title) . '.pdf'
        );
    }
}
