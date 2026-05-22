<?php

namespace Botble\JobBoard\Supports;

use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Currency;
use Botble\JobBoard\Models\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalaryDataHelper
{
    protected static array $rangeMultipliers = [
        'hourly'  => 160,
        'daily'   => 22,
        'weekly'  => 4.33,
        'monthly' => 1,
        'yearly'  => 1 / 12,
    ];

    protected static ?int $defaultCurrencyId = null;

    protected static function defaultCurrencyId(): int
    {
        if (static::$defaultCurrencyId === null) {
            static::$defaultCurrencyId = (int) Currency::query()->where('is_default', true)->value('id') ?: 1;
        }

        return static::$defaultCurrencyId;
    }

    /**
     * Convert a job's salary midpoint to monthly ZMW.
     * Returns null if the salary is not usable (hidden, missing, zero range).
     */
    public static function normalizeToMonthlyDefault(Job $job): ?float
    {
        if ($job->hide_salary) {
            return null;
        }

        $salaryType = is_object($job->salary_type) ? $job->salary_type->getValue() : $job->salary_type;
        if (in_array($salaryType, ['hidden', 'negotiable', 'competitive'])) {
            return null;
        }

        $from = (float) $job->salary_from;
        $to   = (float) $job->salary_to;

        if ($from <= 0 && $to <= 0) {
            return null;
        }

        $midpoint = match (true) {
            $from > 0 && $to > 0 => ($from + $to) / 2,
            $to > 0              => $to,
            default              => $from,
        };

        // Convert to default currency (ZMW)
        $currencyId = (int) $job->currency_id;
        if ($currencyId && $currencyId !== static::defaultCurrencyId()) {
            $exchangeRate = (float) ($job->currency?->exchange_rate ?? 1);
            if ($exchangeRate > 0) {
                $midpoint = $midpoint / $exchangeRate;
            }
        }

        // Convert to monthly
        $rangeValue   = is_object($job->salary_range) ? $job->salary_range->getValue() : ($job->salary_range ?? 'monthly');
        $multiplier   = static::$rangeMultipliers[$rangeValue] ?? 1;

        return round($midpoint * $multiplier, 2);
    }

    /**
     * Base query: published jobs with usable salary data, loaded with currency.
     */
    protected static function baseQuery(int $monthsBack = 12)
    {
        return Job::query()
            ->with('currency')
            ->where('status', 'published')
            ->where('hide_salary', false)
            ->whereNotIn('salary_type', ['hidden', 'negotiable', 'competitive'])
            ->where(function ($q): void {
                $q->where('salary_from', '>', 0)->orWhere('salary_to', '>', 0);
            })
            ->where('created_at', '>=', now()->subMonths($monthsBack));
    }

    /**
     * Return percentile + stats benchmark for given filters.
     * Filters: keyword, category_id, city_id, career_level_id, months_back.
     */
    public static function getBenchmark(array $filters = []): array
    {
        $monthsBack = (int) ($filters['months_back'] ?? 12);
        $query      = static::baseQuery($monthsBack);

        if (! empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $query->where('name', 'like', "%{$kw}%");
        }

        if (! empty($filters['category_id'])) {
            $query->whereHas('categories', fn ($q) => $q->where('jb_categories.id', $filters['category_id']));
        }

        if (! empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (! empty($filters['career_level_id'])) {
            $query->where('career_level_id', $filters['career_level_id']);
        }

        $values = $query->get()->map(fn (Job $job) => static::normalizeToMonthlyDefault($job))
            ->filter(fn ($v) => $v !== null && $v > 0)
            ->sort()
            ->values();

        $count = $values->count();

        if ($count === 0) {
            return ['count' => 0];
        }

        return [
            'count'  => $count,
            'min'    => (int) $values->first(),
            'max'    => (int) $values->last(),
            'avg'    => (int) $values->average(),
            'median' => (int) static::percentile($values, 50),
            'p25'    => (int) static::percentile($values, 25),
            'p75'    => (int) static::percentile($values, 75),
        ];
    }

    /**
     * Salary stats grouped by category, for the admin dashboard.
     */
    public static function getByCategory(int $monthsBack = 12): Collection
    {
        $jobs = static::baseQuery($monthsBack)
            ->with(['categories', 'currency'])
            ->get();

        $grouped = [];

        foreach ($jobs as $job) {
            $monthly = static::normalizeToMonthlyDefault($job);
            if ($monthly === null) {
                continue;
            }
            foreach ($job->categories as $category) {
                if (! isset($grouped[$category->id])) {
                    $grouped[$category->id] = ['name' => $category->name, 'values' => []];
                }
                $grouped[$category->id]['values'][] = $monthly;
            }
        }

        return collect($grouped)->map(function ($data) {
            $values = collect($data['values'])->sort()->values();
            if ($values->isEmpty()) {
                return null;
            }
            return [
                'name'   => $data['name'],
                'count'  => $values->count(),
                'min'    => (int) $values->first(),
                'max'    => (int) $values->last(),
                'avg'    => (int) $values->average(),
                'median' => (int) static::percentile($values, 50),
            ];
        })->filter()->sortByDesc('median')->values();
    }

    /**
     * Salary stats grouped by city, for the admin dashboard.
     */
    public static function getByCity(int $monthsBack = 12): Collection
    {
        $jobs = Job::query()
            ->with('currency')
            ->where('jb_jobs.status', 'published')
            ->where('jb_jobs.hide_salary', false)
            ->whereNotIn('jb_jobs.salary_type', ['hidden', 'negotiable', 'competitive'])
            ->where(function ($q): void {
                $q->where('jb_jobs.salary_from', '>', 0)->orWhere('jb_jobs.salary_to', '>', 0);
            })
            ->where('jb_jobs.created_at', '>=', now()->subMonths($monthsBack))
            ->leftJoin('cities', 'jb_jobs.city_id', '=', 'cities.id')
            ->select('jb_jobs.*', DB::raw('COALESCE(cities.name, "Other / Remote") as city_label'))
            ->get();

        $grouped = [];
        foreach ($jobs as $job) {
            $label   = $job->city_label ?? 'Other / Remote';
            $monthly = static::normalizeToMonthlyDefault($job);
            if ($monthly === null || $monthly <= 0) {
                continue;
            }
            $grouped[$label][] = $monthly;
        }

        return collect($grouped)->map(function ($values, $cityName) {
            $sorted = collect($values)->sort()->values();
            return [
                'city'   => $cityName,
                'count'  => $sorted->count(),
                'min'    => (int) $sorted->first(),
                'max'    => (int) $sorted->last(),
                'median' => (int) static::percentile($sorted, 50),
            ];
        })->sortByDesc('median')->values();
    }

    /**
     * Top paying job titles by median monthly salary.
     */
    public static function getTopPayingTitles(int $limit = 20, int $monthsBack = 12): Collection
    {
        $jobs = static::baseQuery($monthsBack)->with('currency')->get();

        $grouped = $jobs->groupBy(fn (Job $j) => strtolower(trim($j->name)));

        return $grouped->map(function ($titleJobs, $rawTitle) {
            $values = $titleJobs->map(fn (Job $job) => static::normalizeToMonthlyDefault($job))
                ->filter(fn ($v) => $v !== null && $v > 0)
                ->sort()->values();

            if ($values->count() < 1) {
                return null;
            }

            return [
                'title'  => $titleJobs->first()->name,
                'count'  => $values->count(),
                'min'    => (int) $values->first(),
                'max'    => (int) $values->last(),
                'median' => (int) static::percentile($values, 50),
            ];
        })->filter()->sortByDesc('median')->take($limit)->values();
    }

    /**
     * Monthly median salary trend for the last N months.
     */
    public static function getTrends(int $months = 12): Collection
    {
        $results = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();

            $values = Job::query()
                ->with('currency')
                ->where('status', 'published')
                ->where('hide_salary', false)
                ->whereNotIn('salary_type', ['hidden', 'negotiable', 'competitive'])
                ->where(function ($q): void {
                    $q->where('salary_from', '>', 0)->orWhere('salary_to', '>', 0);
                })
                ->whereBetween('created_at', [$start, $end])
                ->get()
                ->map(fn (Job $job) => static::normalizeToMonthlyDefault($job))
                ->filter(fn ($v) => $v !== null && $v > 0)
                ->sort()->values();

            $results->push([
                'month'  => $month->format('M Y'),
                'count'  => $values->count(),
                'median' => $values->isNotEmpty() ? (int) static::percentile($values, 50) : null,
            ]);
        }

        return $results;
    }

    /**
     * Total count of jobs with usable salary data.
     */
    public static function totalDataPoints(int $monthsBack = 12): int
    {
        return static::baseQuery($monthsBack)->count();
    }

    protected static function percentile(Collection $sorted, float $pct): float
    {
        $count = $sorted->count();
        if ($count === 0) {
            return 0;
        }
        if ($count === 1) {
            return $sorted->first();
        }
        $index = ($pct / 100) * ($count - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);
        if ($lower === $upper) {
            return $sorted[$lower];
        }
        return $sorted[$lower] + ($index - $lower) * ($sorted[$upper] - $sorted[$lower]);
    }
}
