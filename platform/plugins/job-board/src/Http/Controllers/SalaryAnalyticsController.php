<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Supports\SalaryDataHelper;
use Illuminate\Http\Request;

class SalaryAnalyticsController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Salary Analytics', route('salary-analytics.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Salary Analytics');

        $monthsBack = (int) $request->query('months_back', 12);
        if (! in_array($monthsBack, [3, 6, 12, 24])) {
            $monthsBack = 12;
        }

        $filters = ['months_back' => $monthsBack];

        $totalDataPoints = SalaryDataHelper::totalDataPoints($monthsBack);
        $topTitles       = SalaryDataHelper::getTopPayingTitles(20, $monthsBack);
        $byCategory      = SalaryDataHelper::getByCategory($monthsBack);
        $byCity          = SalaryDataHelper::getByCity($monthsBack);
        $trends          = SalaryDataHelper::getTrends(min($monthsBack, 12));

        $overallBenchmark = SalaryDataHelper::getBenchmark($filters);

        $categories = Category::query()->orderBy('name')->pluck('name', 'id');

        return view('plugins/job-board::salary-analytics.index', compact(
            'monthsBack',
            'totalDataPoints',
            'topTitles',
            'byCategory',
            'byCity',
            'trends',
            'overallBenchmark',
            'categories'
        ));
    }
}
