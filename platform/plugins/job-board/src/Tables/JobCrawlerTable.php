<?php

namespace Botble\JobBoard\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\JobBoard\Models\JobCrawler;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\Action;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Botble\Table\Columns\YesNoColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;

class JobCrawlerTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(JobCrawler::class)
            ->addActions([
                Action::make('run')
                    ->label('Run')
                    ->icon('ti ti-player-play')
                    ->color('success')
                    ->url(fn (Action $action) => route('job-board.crawlers.run', $action->getItem()->getKey()))
                    ->permission('job-board.crawlers.run')
                    ->addAttribute('data-crawler-run', '1'),
                EditAction::make()->route('job-board.crawlers.edit'),
                DeleteAction::make()->route('job-board.crawlers.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('last_status', fn (JobCrawler $crawler) => $crawler->last_status
                ? BaseHelper::renderBadge($crawler->last_status, $crawler->last_status === 'failed' ? 'danger' : 'success')
                : '&mdash;')
            ->editColumn('last_run_at', fn (JobCrawler $crawler) => $crawler->last_run_at?->diffForHumans() ?: '&mdash;');

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        return $this->applyScopes($this->getModel()->query()->select([
            'id',
            'name',
            'source_url',
            'parser_type',
            'is_active',
            'last_status',
            'last_run_at',
            'created_at',
        ]));
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            NameColumn::make()->route('job-board.crawlers.edit'),
            Column::make('source_url')->title('Source URL')->className('text-start'),
            Column::make('parser_type')->title('Parser')->width(100),
            YesNoColumn::make('is_active')->title('Active')->width(100),
            Column::make('last_status')->title('Last status')->width(130),
            Column::make('last_run_at')->title('Last run')->width(160),
            CreatedAtColumn::make(),
        ];
    }

    public function buttons(): array
    {
        return $this->addCreateButton(route('job-board.crawlers.create'), 'job-board.crawlers.create');
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('job-board.crawlers.destroy'),
        ];
    }
}
