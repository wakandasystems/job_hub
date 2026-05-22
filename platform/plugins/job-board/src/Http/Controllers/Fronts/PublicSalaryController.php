<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\JobBoard\Models\CareerLevel;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Supports\SalaryDataHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PublicSalaryController extends Controller
{
    public function index()
    {
        $categories   = Category::query()->orderBy('name')->pluck('name', 'id');
        $careerLevels = CareerLevel::query()->orderBy('name')->pluck('name', 'id');

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add('Salary Checker');

        return Theme::scope('job-board.salary-checker', compact('categories', 'careerLevels'))->render();
    }

    public function results(Request $request)
    {
        $validated = $request->validate([
            'keyword'        => ['nullable', 'string', 'max:100'],
            'category_id'    => ['nullable', 'integer'],
            'city'           => ['nullable', 'string', 'max:100'],
            'career_level_id'=> ['nullable', 'integer'],
            'months_back'    => ['nullable', 'integer', 'in:3,6,12,24'],
        ]);

        $filters = array_filter([
            'keyword'         => $validated['keyword'] ?? null,
            'category_id'     => $validated['category_id'] ?? null,
            'career_level_id' => $validated['career_level_id'] ?? null,
            'months_back'     => $validated['months_back'] ?? 12,
        ]);

        // Resolve city_id from city name if provided
        if (! empty($validated['city'])) {
            $cityId = \Botble\Location\Models\City::query()
                ->where('name', 'like', '%' . $validated['city'] . '%')
                ->value('id');
            if ($cityId) {
                $filters['city_id'] = $cityId;
            }
        }

        $benchmark = SalaryDataHelper::getBenchmark($filters);

        if (($benchmark['count'] ?? 0) < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough salary data for this search. Try broadening your filters.',
            ], 422);
        }

        $benchmark['keyword']  = $validated['keyword'] ?? null;
        $benchmark['currency'] = 'ZMW';

        return response()->json(['success' => true, 'data' => $benchmark]);
    }
}
