<?php

namespace Botble\JobBoard\Tables\Fronts;

use Botble\Base\Facades\Html;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\Review;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\StatusColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;

class ReviewTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Review::class)
            ->addActions([
                DeleteAction::make()->route('public.account.reviews.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('star', function (Review $item) {
                return view('plugins/job-board::reviews.partials.rating', ['star' => $item->star])->render();
            })
            ->editColumn('reviewable_id', function (Review $item) {
                if (! $item->reviewable_id || ! $item->reviewable?->id) {
                    return '&mdash;';
                }

                return Html::link(
                    '#',
                    $item->reviewable->name
                )->toHtml();
            });

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        /**
         * @var Account $account
         */
        $account = auth('account')->user();

        $query = $this
            ->getModel()
            ->query()
            ->select([
                'id',
                'star',
                'review',
                'reviewable_id',
                'reviewable_type',
                'created_by_id',
                'created_by_type',
                'status',
                'created_at',
            ])
            ->where('created_by_id', $account->getKey())
            ->with(['reviewable']);

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            Column::make('reviewable_id')
                ->title(trans('plugins/job-board::review.account_or_company'))
                ->alignLeft(),
            Column::make('star')
                ->title(trans('plugins/job-board::review.star')),
            Column::make('review')
                ->title(trans('plugins/job-board::review.review'))
                ->alignLeft(),
            StatusColumn::make(),
            CreatedAtColumn::make(),
        ];
    }
}
