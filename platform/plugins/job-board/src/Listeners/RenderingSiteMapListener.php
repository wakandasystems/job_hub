<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\Tag;
use Botble\Location\Models\City;
use Botble\Location\Models\State;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Botble\Theme\Facades\SiteMapManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

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

            $this->addPaginatedSitemapItems($key, 'jobs', $this->getJobQuery());

            return;
        }

        $this->createPaginatedSitemapsForKey('job-city', City::query()->wherePublished());
        $this->createPaginatedSitemapsForKey('job-state', State::query()->wherePublished());
        $this->createPaginatedSitemapsForKey('job-companies', Company::query()->wherePublished());
        $this->createPaginatedSitemapsForKey('jobs', $this->getJobQuery());
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
                    if (! $item->slugable && ! $item->slug) {
                        continue;
                    }

                    SiteMapManager::add($routeName ? route($routeName, $item->slug) : $item->url, $item->updated_at, $priority);
                }
            }
        }
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
