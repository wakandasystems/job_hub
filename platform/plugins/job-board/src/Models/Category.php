<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Contracts\HasTreeCategory as HasTreeCategoryContract;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Botble\Base\Traits\HasTreeCategory;
use Botble\JobBoard\Models\Concerns\HasActiveJobsRelation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Category extends BaseModel implements HasTreeCategoryContract
{
    use HasActiveJobsRelation;
    use HasTreeCategory;

    protected $table = 'jb_categories';

    protected $fillable = [
        'name',
        'description',
        'order',
        'is_default',
        'is_featured',
        'status',
        'parent_id',
    ];

    protected $casts = [
        'status' => BaseStatusEnum::class,
        'name' => SafeContent::class,
        'description' => SafeContent::class,
    ];

    protected static function booted(): void
    {
        static::deleting(function (Category $category): void {
            foreach ($category->children as $child) {
                $child->parent_id = $category->parent_id;
                $child->save();
            }

            $category->jobs()->detach();
        });
    }

    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(
            Job::class,
            'jb_jobs_categories',
            'category_id',
            'job_id'
        );
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id')->withDefault();
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function activeChildren(): HasMany
    {
        return $this
            ->children()
            ->wherePublished()
            ->with(['slugable', 'activeChildren']);
    }

    /**
     * Get all descendant category IDs including the current category
     */
    public function getAllCategoryIds(): array
    {
        $categoryIds = [$this->id];

        if ($this->activeChildren->isNotEmpty()) {
            $categoryIds = array_merge($categoryIds, static::getChildrenIds($this->activeChildren));
        }

        return $categoryIds;
    }

    /**
     * Recursively get all child category IDs
     */
    public static function getChildrenIds(EloquentCollection $children, array $categoryIds = []): array
    {
        if ($children->isEmpty()) {
            return $categoryIds;
        }

        foreach ($children as $item) {
            $categoryIds[] = $item->id;
            if ($item->activeChildren->isNotEmpty()) {
                $categoryIds = static::getChildrenIds($item->activeChildren, $categoryIds);
            }
        }

        return $categoryIds;
    }

    /**
     * Get jobs count including jobs from child categories
     */
    public function getJobsCountWithChildrenAttribute(): int
    {
        $categoryIds = $this->getAllCategoryIds();

        return Job::query()
            ->whereHas('categories', function ($query) use ($categoryIds): void {
                $query->whereIn('jb_categories.id', $categoryIds);
            })
            ->active()
            ->count();
    }

    public static function addJobsCountWithChildren($categories)
    {
        if (! $categories->first()?->relationLoaded('activeChildren')) {
            $categories->load('activeChildren.activeChildren.activeChildren');
        }

        $categoryMapping = [];
        foreach ($categories as $category) {
            $categoryMapping[$category->id] = $category->getAllCategoryIds();
        }

        $allCategoryIds = array_unique(array_merge(...array_values($categoryMapping)));

        if (empty($allCategoryIds)) {
            return $categories;
        }

        $jobCounts = Job::query()
            ->active()
            ->join('jb_jobs_categories', 'jb_jobs.id', '=', 'jb_jobs_categories.job_id')
            ->whereIn('jb_jobs_categories.category_id', $allCategoryIds)
            ->select('jb_jobs_categories.category_id', DB::raw('COUNT(DISTINCT jb_jobs.id) as count'))
            ->groupBy('jb_jobs_categories.category_id')
            ->pluck('count', 'category_id')
            ->toArray();

        foreach ($categories as $category) {
            $totalCount = 0;
            foreach ($categoryMapping[$category->id] as $categoryId) {
                $totalCount += $jobCounts[$categoryId] ?? 0;
            }

            $category->setAttribute('jobs_count', $totalCount);
            $category->setAttribute('active_jobs_count', $totalCount);
        }

        return $categories;
    }
}
