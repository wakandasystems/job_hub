<?php

namespace Botble\JobBoard\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\JobBoard\Models\Category;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\BulkChanges\CreatedAtBulkChange;
use Botble\Table\BulkChanges\NameBulkChange;
use Botble\Table\BulkChanges\NumberBulkChange;
use Botble\Table\BulkChanges\SelectBulkChange;
use Botble\Table\BulkChanges\StatusBulkChange;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\StatusColumn;
use Botble\Table\Columns\YesNoColumn;
use Botble\Table\HeaderActions\CreateHeaderAction;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class CategoryTable extends TableAbstract
{
    protected static $cachedCategories = null;

    public function setup(): void
    {
        $this
            ->model(Category::class)
            ->addActions([
                EditAction::make()->route('job-categories.edit'),
                DeleteAction::make()->route('job-categories.destroy'),
            ])
            ->addHeaderAction(CreateHeaderAction::make()->route('job-categories.create'));
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        static::$cachedCategories = $this->getModel()
            ->query()
            ->select(['id', 'name', 'parent_id', 'order'])
            ->get()
            ->keyBy('id');

        $sortedIds = $this->buildHierarchicalOrder(static::$cachedCategories);

        $query = $this
            ->getModel()
            ->query()
            ->select([
                'id',
                'name',
                'parent_id',
                'description',
                'order',
                'status',
                'is_featured',
                'is_default',
                'created_at',
            ])
            ->with(['parent:id,name,parent_id']);

        if (! empty($sortedIds)) {
            $query->whereIn('id', $sortedIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $sortedIds) . ')');
        } else {
            $query->whereRaw('1 = 0');
        }

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            FormattedColumn::make('name')
                ->title(trans('core/base::tables.name'))
                ->alignStart()
                ->renderUsing(function (FormattedColumn $column) {
                    /**
                     * @var Category $category
                     */
                    $category = $column->getItem();
                    $depth = $this->getCategoryDepth($category);
                    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
                    $icon = $depth > 0 ? BaseHelper::renderIcon('ti ti-corner-down-right', 'me-1') : '';

                    return $indent . $icon . '<a href="' . route('job-categories.edit', $category->id) . '">' . $category->name . '</a>';
                }),
            FormattedColumn::make('parent_id')
                ->title(trans('plugins/job-board::job-category.parent'))
                ->alignStart()
                ->renderUsing(function (FormattedColumn $column) {
                    /**
                     * @var Category $category
                     */
                    $category = $column->getItem();

                    return $this->buildParentTree($category);
                }),
            Column::make('order')
                ->title(trans('plugins/job-board::job-category.order'))
                ->alignStart(),
            YesNoColumn::make('is_featured')
                ->title(trans('core/base::tables.is_featured'))
                ->alignStart(),
            YesNoColumn::make('is_default')
                ->title(trans('plugins/job-board::job-category.is_default'))
                ->alignStart(),
            CreatedAtColumn::make(),
            StatusColumn::make(),
        ];
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('job-categories.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            NameBulkChange::make(),
            NumberBulkChange::make()
                ->name('order')
                ->title(trans('plugins/job-board::job-category.order')),
            'parent_id' => [
                'title' => trans('plugins/job-board::job-category.parent'),
                'type' => 'select-ajax',
                'validate' => 'nullable',
                'callback' => function (int|string|null $value = null): array {
                    $categorySelected = [];
                    if ($value && $category = Category::query()->find($value)) {
                        $categorySelected = [$category->getKey() => $category->name];
                    }

                    return [
                        'url' => route('job-categories.search'),
                        'selected' => $categorySelected,
                        'minimum-input' => 1,
                    ];
                },
            ],
            StatusBulkChange::make(),
            SelectBulkChange::make()
                ->name('is_featured')
                ->title(trans('core/base::tables.is_featured'))
                ->choices([
                    0 => trans('core/base::base.no'),
                    1 => trans('core/base::base.yes'),
                ]),
            SelectBulkChange::make()
                ->name('is_default')
                ->title(trans('plugins/job-board::job-category.is_default'))
                ->choices([
                    0 => trans('core/base::base.no'),
                    1 => trans('core/base::base.yes'),
                ]),
            CreatedAtBulkChange::make(),
        ];
    }

    public function saveBulkChangeItem(Model|Category $item, string $inputKey, ?string $inputValue): Model|bool
    {
        if ($inputKey === 'parent_id') {
            /**
             * @var Category $item
             */
            $item->parent_id = $inputValue ?: null;
            $item->save();

            return $item;
        }

        if ($inputKey === 'is_default' && $inputValue == 1) {
            Category::query()->where('id', '!=', $item->getKey())->update(['is_default' => 0]);
        }

        return parent::saveBulkChangeItem($item, $inputKey, $inputValue);
    }

    public function renderTable($data = [], $mergeData = []): View|Factory|Response
    {
        if ($this->isEmpty()) {
            return view('plugins/job-board::categories.intro');
        }

        return parent::renderTable($data, $mergeData);
    }

    protected function buildHierarchicalOrder($categories)
    {
        $sorted = [];
        $byParent = [];

        foreach ($categories as $category) {
            $parentId = $category->parent_id ?: 0;
            if (! isset($byParent[$parentId])) {
                $byParent[$parentId] = [];
            }
            $byParent[$parentId][] = $category;
        }

        foreach ($byParent as $parentId => $children) {
            usort($byParent[$parentId], function ($a, $b) {
                return $a->order <=> $b->order;
            });
        }

        $addChildren = function ($parentId) use (&$addChildren, &$byParent, &$sorted): void {
            if (! isset($byParent[$parentId])) {
                return;
            }
            foreach ($byParent[$parentId] as $category) {
                $sorted[] = $category->id;
                $addChildren($category->id);
            }
        };

        $addChildren(0);
        if (isset($byParent[null])) {
            $addChildren(null);
        }

        return $sorted;
    }

    protected function getCategoryDepth(Category $category): int
    {
        $allCategories = static::$cachedCategories;

        if (! $allCategories) {
            return 0;
        }

        $depth = 0;
        $currentId = $category->parent_id;

        while ($currentId && isset($allCategories[$currentId])) {
            $depth++;
            $parent = $allCategories[$currentId];
            $currentId = $parent->parent_id;
        }

        return $depth;
    }

    protected function buildParentTree(Category $category): string
    {
        if (! $category->parent_id) {
            return '—';
        }

        $allCategories = static::$cachedCategories;

        if (! $allCategories) {
            return '—';
        }

        $parents = [];
        $currentId = $category->parent_id;

        while ($currentId && isset($allCategories[$currentId])) {
            $parent = $allCategories[$currentId];
            array_unshift($parents, $parent->name);
            $currentId = $parent->parent_id;
        }

        return implode(' → ', $parents);
    }
}
