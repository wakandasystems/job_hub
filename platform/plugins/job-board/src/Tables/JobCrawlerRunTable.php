<?php

namespace Botble\JobBoard\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\JobBoard\Models\JobCrawlerRun;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\ViewAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\IdColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;

class JobCrawlerRunTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(JobCrawlerRun::class)
            ->addActions([
                ViewAction::make()->route('job-board.crawler-runs.show')->permission('job-board.crawler-runs.index'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('crawler_id', fn (JobCrawlerRun $run) => $run->crawler->name ?: '&mdash;')
            ->editColumn('status', fn (JobCrawlerRun $run) => BaseHelper::renderBadge(
                $run->status,
                match ($run->status) {
                    'success' => 'success',
                    'failed' => 'danger',
                    default => 'warning',
                }
            ))
            ->editColumn('started_at', fn (JobCrawlerRun $run) => $run->started_at?->toDateTimeString() ?: '&mdash;')
            ->editColumn('finished_at', fn (JobCrawlerRun $run) => $run->finished_at?->toDateTimeString() ?: '&mdash;')
            ->editColumn('error_message', fn (JobCrawlerRun $run) => $run->error_message
                ? e($run->error_message)
                : '&mdash;');

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        return $this->applyScopes($this->getModel()->query()
            ->with('crawler')
            ->select([
                'id',
                'crawler_id',
                'status',
                'started_at',
                'finished_at',
                'jobs_found',
                'jobs_created',
                'jobs_updated',
                'jobs_skipped',
                'error_message',
            ]));
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            Column::make('crawler_id')->title('Agent')->className('text-start'),
            Column::make('status')->title('Status')->width(110),
            Column::make('started_at')->title('Started')->width(160),
            Column::make('finished_at')->title('Finished')->width(160),
            Column::make('jobs_found')->title('Found')->width(80),
            Column::make('jobs_created')->title('Created')->width(80),
            Column::make('jobs_updated')->title('Updated')->width(80),
            Column::make('jobs_skipped')->title('Skipped')->width(80),
            Column::make('error_message')->title('Error')->className('text-start'),
        ];
    }
}
