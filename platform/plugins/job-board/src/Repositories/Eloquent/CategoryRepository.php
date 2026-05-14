<?php

namespace Botble\JobBoard\Repositories\Eloquent;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\JobBoard\Repositories\Interfaces\CategoryInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;

class CategoryRepository extends RepositoriesAbstract implements CategoryInterface
{
    public function getFeaturedCategories(int $limit = 8, array $with = [])
    {
        $data = $this->model
            ->where(['status' => BaseStatusEnum::PUBLISHED, 'is_featured' => true])->latest('order')->latest()
            ->withCount(['activeJobs'])
            ->limit($limit)
            ->with(array_merge($with, ['slugable', 'metadata', 'activeChildren.activeChildren.activeChildren']));

        $categories = $this->applyBeforeExecuteQuery($data)->get();

        // Update jobs count to include child categories
        foreach ($categories as $category) {
            $category->active_jobs_count = $category->jobs_count_with_children;
        }

        return $categories;
    }

    public function getCategories(array $with = [])
    {
        $data = $this->model
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->oldest('order')->latest()
            ->withCount(['activeJobs'])
            ->with(array_merge($with, ['slugable', 'activeChildren.activeChildren.activeChildren']));

        $categories = $this->applyBeforeExecuteQuery($data)->get();

        // Update jobs count to include child categories
        foreach ($categories as $category) {
            $category->active_jobs_count = $category->jobs_count_with_children;
        }

        return $categories;
    }
}
