<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\SalaryReport;
use Botble\JobBoard\Supports\SalaryDataHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SalaryReportController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Salary Reports', route('salary-reports.index'));
    }

    public function index()
    {
        $this->pageTitle('Salary Reports');

        $reports = SalaryReport::query()->withCount('purchases')->latest()->paginate(20);

        $stats = [
            'total'     => SalaryReport::query()->count(),
            'published' => SalaryReport::query()->where('is_published', true)->count(),
            'sold'      => \Botble\JobBoard\Models\SalaryReportPurchase::query()->count(),
            'revenue'   => \Botble\JobBoard\Models\SalaryReportPurchase::query()->sum('amount_paid'),
        ];

        return view('plugins/job-board::salary-reports.index', compact('reports', 'stats'));
    }

    public function create()
    {
        $this->pageTitle('New Salary Report');

        return view('plugins/job-board::salary-reports.edit', ['report' => null]);
    }

    public function store(Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validateReport($request);

        $validated['slug'] = Str::slug($validated['title']) . '-' . $validated['year'];

        $report = SalaryReport::query()->create($validated);

        return $response
            ->setPreviousUrl(route('salary-reports.index'))
            ->setNextUrl(route('salary-reports.edit', $report))
            ->setMessage('Salary report created.');
    }

    public function edit(SalaryReport $salaryReport)
    {
        $this->pageTitle('Edit Report: ' . $salaryReport->title);

        return view('plugins/job-board::salary-reports.edit', ['report' => $salaryReport]);
    }

    public function update(SalaryReport $salaryReport, Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validateReport($request);

        $salaryReport->update($validated);

        return $response
            ->setPreviousUrl(route('salary-reports.index'))
            ->setNextUrl(route('salary-reports.edit', $salaryReport))
            ->setMessage('Report updated.');
    }

    public function destroy(SalaryReport $salaryReport, BaseHttpResponse $response)
    {
        if ($salaryReport->file_path) {
            Storage::disk('local')->delete($salaryReport->file_path);
        }

        $salaryReport->delete();

        return $response
            ->setNextUrl(route('salary-reports.index'))
            ->setMessage('Report deleted.');
    }

    public function generatePdf(SalaryReport $salaryReport, BaseHttpResponse $response)
    {
        $monthsBack = 12;
        $byCategory = SalaryDataHelper::getByCategory($monthsBack);
        $topTitles  = SalaryDataHelper::getTopPayingTitles(20, $monthsBack);
        $byCity     = SalaryDataHelper::getByCity($monthsBack);
        $overall    = SalaryDataHelper::getBenchmark(['months_back' => $monthsBack]);

        $pdf = Pdf::loadView('plugins/job-board::salary-reports.pdf', [
            'report'     => $salaryReport,
            'byCategory' => $byCategory,
            'topTitles'  => $topTitles,
            'byCity'     => $byCity,
            'overall'    => $overall,
            'generatedAt'=> now()->format('d M Y'),
        ])->setPaper('a4');

        $filename = 'salary-reports/' . $salaryReport->slug . '-' . now()->format('Ymd') . '.pdf';
        Storage::disk('local')->put($filename, $pdf->output());

        $salaryReport->update(['file_path' => $filename]);

        return $response
            ->setNextUrl(route('salary-reports.edit', $salaryReport))
            ->setMessage('PDF generated and saved.');
    }

    public function togglePublished(SalaryReport $salaryReport, BaseHttpResponse $response)
    {
        $salaryReport->update(['is_published' => ! $salaryReport->is_published]);

        return $response
            ->setNextUrl(route('salary-reports.index'))
            ->setMessage($salaryReport->is_published ? 'Report published.' : 'Report unpublished.');
    }

    public function downloadPdf(SalaryReport $salaryReport)
    {
        abort_unless(
            $salaryReport->file_path && Storage::disk('local')->exists($salaryReport->file_path),
            404,
            'PDF not yet generated.'
        );

        return Storage::disk('local')->download(
            $salaryReport->file_path,
            Str::slug($salaryReport->title) . '.pdf'
        );
    }

    protected function validateReport(Request $request): array
    {
        return $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'year'          => ['required', 'integer', 'min:2020', 'max:2050'],
            'sector'        => ['nullable', 'string', 'max:100'],
            'price'         => ['required', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'max:10'],
            'is_published'  => ['boolean'],
        ]);
    }
}
