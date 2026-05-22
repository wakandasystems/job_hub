<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Supports\SalaryDataHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryBenchmarkController extends BaseController
{
    public function benchmarks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword'         => ['nullable', 'string', 'max:100'],
            'category_id'     => ['nullable', 'integer'],
            'city_id'         => ['nullable', 'integer'],
            'career_level_id' => ['nullable', 'integer'],
            'months_back'     => ['nullable', 'integer', 'in:3,6,12,24'],
        ]);

        $filters = array_filter([
            'keyword'         => $validated['keyword'] ?? null,
            'category_id'     => $validated['category_id'] ?? null,
            'city_id'         => $validated['city_id'] ?? null,
            'career_level_id' => $validated['career_level_id'] ?? null,
            'months_back'     => $validated['months_back'] ?? 12,
        ]);

        $data = SalaryDataHelper::getBenchmark($filters);

        if (($data['count'] ?? 0) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No salary data found for the given filters.',
            ], 404);
        }

        return response()->json([
            'success'  => true,
            'currency' => 'ZMW',
            'period'   => 'monthly',
            'data'     => $data,
            'filters'  => $filters,
        ]);
    }

    public function categories(): JsonResponse
    {
        $data = SalaryDataHelper::getByCategory(12);

        return response()->json([
            'success' => true,
            'data'    => $data->values(),
        ]);
    }

    public function topTitles(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $limit = max(1, min($limit, 50));

        $data = SalaryDataHelper::getTopPayingTitles($limit, 12);

        return response()->json([
            'success' => true,
            'data'    => $data->values(),
        ]);
    }
}
