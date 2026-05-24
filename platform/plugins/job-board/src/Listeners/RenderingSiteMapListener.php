<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\Tag;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Botble\Theme\Facades\SiteMapManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RenderingSiteMapListener
{
    public function handle(RenderingSiteMapEvent $event): void
    {
        if ($key = $event->key) {
            if (str_starts_with($key, 'job-city-')) {
                $this->addPaginatedSitemapItems(
                    $key,
                    'job-city',
                    City::query()->wherePublished(),
                    '0.8',
                    [],
                    ['id', 'name', 'updated_at', 'slug'],
                    'public.jobs-by-city'
                );

                return;
            }

            if (str_starts_with($key, 'job-country-') && ! str_starts_with($key, 'job-country-title-')) {
                $this->addPaginatedSitemapItems(
                    $key,
                    'job-country',
                    Country::query()->wherePublished(),
                    '0.8',
                    [],
                    ['id', 'name', 'code', 'updated_at'],
                    'public.jobs-by-country'
                );

                return;
            }

            if (str_starts_with($key, 'job-state-')) {
                $this->addPaginatedSitemapItems(
                    $key,
                    'job-state',
                    State::query()->wherePublished(),
                    '0.8',
                    [],
                    ['id', 'name', 'updated_at', 'slug'],
                    'public.jobs-by-state'
                );

                return;
            }

            if (str_starts_with($key, 'job-companies-')) {
                $this->addPaginatedSitemapItems($key, 'job-companies', Company::query()->wherePublished());

                return;
            }

            if (str_starts_with($key, 'job-categories-')) {
                $this->addPaginatedSitemapItems($key, 'job-categories', Category::query()->wherePublished());

                return;
            }

            if (str_starts_with($key, 'job-tags-')) {
                $this->addPaginatedSitemapItems($key, 'job-tags', Tag::query()->wherePublished());

                return;
            }

            if (str_starts_with($key, 'job-country-title-')) {
                $this->addPaginatedSitemapItems(
                    $key,
                    'job-country-title',
                    $this->getJobQuery(),
                    '0.7',
                    [],
                    ['id', 'name', 'country_id', 'updated_at'],
                    'public.jobs-by-country-title'
                );

                return;
            }

            if (str_starts_with($key, 'job-title-')) {
                $this->addPaginatedSitemapItems(
                    $key,
                    'job-title',
                    $this->getJobQuery(),
                    '0.7',
                    [],
                    ['id', 'name', 'updated_at'],
                    'public.jobs-by-title'
                );

                return;
            }

            $this->addPaginatedSitemapItems($key, 'jobs', $this->getJobQuery());

            return;
        }

        $this->createPaginatedSitemapsForKey('job-city', City::query()->wherePublished());
        $this->createPaginatedSitemapsForKey('job-country', Country::query()->wherePublished());
        $this->createPaginatedSitemapsForKey('job-state', State::query()->wherePublished());
        $this->createPaginatedSitemapsForKey('job-companies', Company::query()->wherePublished());
        $this->createPaginatedSitemapsForKey('jobs', $this->getJobQuery());
        $this->createPaginatedSitemapsForKey('job-country-title', $this->getJobQuery());
        $this->createPaginatedSitemapsForKey('job-title', $this->getJobQuery());
        $this->createPaginatedSitemapsForKey('job-tags', Tag::query()->wherePublished());
        $this->createPaginatedSitemapsForKey('job-categories', Category::query()->wherePublished());
    }

    protected function addPaginatedSitemapItems(
        string $key,
        string $baseKey,
        Builder $query,
        string $priority = '0.8',
        array $with = ['slugable'],
        array $select = ['id', 'name', 'updated_at'],
        string $routeName = '',
    ): void {
        $paginationData = SiteMapManager::extractPaginationDataByPattern($key, $baseKey, 'monthly-archive');

        if ($paginationData) {
            $matches = $paginationData['matches'];
            $year = Arr::get($matches, 1);
            $month = Arr::get($matches, 2);

            if ($year && $month) {
                $items = $query
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->latest('updated_at')
                    ->select($select)
                    ->with($with)
                    ->skip($paginationData['offset'])
                    ->take($paginationData['limit'])
                    ->get();

                foreach ($items as $item) {
                    $routeParameter = $this->getRouteParameter($routeName, $item);

                    if ($routeName && ! $routeParameter) {
                        continue;
                    }

                    if (! $routeName && ! $item->slugable && ! $item->slug) {
                        continue;
                    }

                    SiteMapManager::add($routeName ? route($routeName, $routeParameter) : $item->url, $item->updated_at, $priority);
                }
            }
        }
    }

    protected function getRouteParameter(string $routeName, mixed $item): array|string|null
    {
        return match ($routeName) {
            'public.jobs-by-country' => strtolower((string) $item->code) ?: Str::slug($item->name),
            'public.jobs-by-country-title' => $this->getCountryTitleRouteParameter($item),
            'public.jobs-by-title' => Str::slug($item->name),
            default => $item->slug,
        };
    }

    protected function getCountryTitleRouteParameter(mixed $item): ?array
    {
        $country = $this->getCountryById((int) $item->country_id);

        if (! $country || ! $item->name) {
            return null;
        }

        return [
            'country' => strtolower((string) $country->code) ?: Str::slug($country->name),
            'slug' => Str::slug($item->name),
        ];
    }

    protected function getCountryById(int $countryId): ?Country
    {
        static $countries = null;

        if (! $countryId) {
            return null;
        }

        if ($countries === null) {
            $countries = Country::query()
                ->wherePublished()
                ->get(['id', 'name', 'code'])
                ->keyBy('id');
        }

        return $countries->get($countryId);
    }

    protected function createPaginatedSitemapsForKey(string $key, Builder $query): void
    {
        $items = $query
                ->selectRaw('YEAR(created_at) as created_year, MONTH(created_at) as created_month, MAX(created_at) as created_at, COUNT(*) as item_count')
                ->groupBy('created_year', 'created_month')
                ->orderByDesc('created_year')
                ->orderByDesc('created_month')
                ->get();

        if ($items->isEmpty()) {
            return;
        }

        foreach ($items as $item) {
            $formattedMonth = str_pad($item->created_month, 2, '0', STR_PAD_LEFT);
            $baseKey = sprintf($key . '-%s-%s', $item->created_year, $formattedMonth);

            SiteMapManager::createPaginatedSitemaps($baseKey, $item->item_count, $item->created_at);
        }
    }

    protected function getJobQuery(): Builder
    {
        $shouldNoIndexInactiveJobs = JobBoardHelper::shouldNoIndexInactiveJobs();

        $conditions = JobBoardHelper::getJobDisplayQueryConditions();
        $statusColumn = 'jb_jobs.status';

        $query = Job::query();

        foreach ($conditions as $column => $value) {
            if ($column === $statusColumn) {
                continue;
            }

            $query->where($column, $value);
        }

        if ($shouldNoIndexInactiveJobs) {
            $query->where($statusColumn, JobStatusEnum::PUBLISHED);
        } else {
            $query->whereIn($statusColumn, [JobStatusEnum::PUBLISHED, JobStatusEnum::CLOSED]);
        }

        if ($shouldNoIndexInactiveJobs) {
            $query
                ->notExpired()
                ->where('status', '!=', JobStatusEnum::CLOSED);
        } else {
            if (! JobBoardHelper::isExpiredJobAccessible()) {
                $query->notExpired();
            }

            if (! JobBoardHelper::isClosedJobAccessible()) {
                $query->notClosed();
            }
        }

        return $query;
    }
}
