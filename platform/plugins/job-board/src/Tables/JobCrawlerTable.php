<?php

namespace Botble\JobBoard\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\JobBoard\Models\JobCrawler;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\Action;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
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
            ->displayActionsAsDropdown(false)
            ->addActions([
                Action::make('run')
                    ->label('Run')
                    ->icon('ti ti-player-play')
                    ->color('success')
                    ->url(fn (Action $action) => route('job-board.crawlers.run', $action->getItem()->getKey())
                        . '?pt=' . urlencode((string) $action->getItem()->parser_type))
                    ->permission('job-board.crawlers.run')
                    ->addAttribute('data-crawler-run', '1'),
                Action::make('clear-jobs')
                    ->label('Clear jobs')
                    ->icon('ti ti-trash')
                    ->color('warning')
                    ->url(fn (Action $action) => route('job-board.crawlers.clear-jobs', $action->getItem()->getKey()))
                    ->permission('job-board.crawlers.edit')
                    ->action('DELETE')
                    ->confirmation()
                    ->confirmationModalTitle('Clear collected jobs')
                    ->confirmationModalMessage('Are you sure you want to delete all jobs collected by this agent? This cannot be undone.')
                    ->confirmationModalButton('Yes, clear jobs'),
                EditAction::make()->route('job-board.crawlers.edit'),
                DeleteAction::make()->route('job-board.crawlers.destroy'),
            ]);
    }

    protected function countryForCrawler(JobCrawler $crawler): string
    {
        // Static map for parsers that always target one country
        $staticMap = [
            'gozambiajobs' => ['flag' => '🇿🇲', 'name' => 'Zambia'],
            'careers24'    => ['flag' => '🇿🇦', 'name' => 'South Africa'],
            'myjobmu'      => ['flag' => '🇲🇺', 'name' => 'Mauritius'],
            'jobstanzania' => ['flag' => '🇹🇿', 'name' => 'Tanzania'],
            'jobinrwanda'  => ['flag' => '🇷🇼', 'name' => 'Rwanda'],
            'keejob'       => ['flag' => '🇹🇳', 'name' => 'Tunisia'],
        ];

        if (isset($staticMap[$crawler->parser_type])) {
            $info = $staticMap[$crawler->parser_type];

            return $info['flag'] . ' ' . $info['name'];
        }

        // For parsers whose country is stored in field_mappings.country_id
        if (in_array($crawler->parser_type, ['ringier', 'africawork', 'pending'])) {
            $mappings  = $crawler->field_mappings;
            $countryId = is_array($mappings) ? ($mappings['country_id'] ?? null) : null;

            if ($countryId) {
                $country = \DB::table('countries')->where('id', $countryId)->first(['name', 'code']);

                if ($country) {
                    return $this->codeToFlag((string) $country->code) . ' ' . $country->name;
                }
            }
        }

        return '&mdash;';
    }

    protected function codeToFlag(string $code): string
    {
        $code = strtoupper(trim($code));
        if (strlen($code) !== 2) {
            return '';
        }

        $offset = 0x1F1E6 - ord('A');

        return mb_chr(ord($code[0]) + $offset, 'UTF-8')
             . mb_chr(ord($code[1]) + $offset, 'UTF-8');
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->addColumn('country', fn (JobCrawler $crawler) => $this->countryForCrawler($crawler))
            ->addColumn('jobs', function (JobCrawler $crawler) {
                $total = number_format((int) $crawler->total_jobs);
                $new   = (int) $crawler->new_today;
                $badge = $new > 0
                    ? '<span class="badge bg-success-lt ms-2">' . number_format($new) . ' new</span>'
                    : '<span class="text-muted ms-2 small">0 new</span>';
                $tip = 'Total jobs: ' . $total . ' · New today: ' . number_format($new);

                return '<span title="' . e($tip) . '" style="cursor:default;">' . $total . $badge . '</span>';
            })
            ->editColumn('last_status', function (JobCrawler $crawler) {
                if (! $crawler->last_status) {
                    return '&mdash;';
                }

                $color  = $crawler->last_status === 'failed' ? 'danger' : 'success';
                $badge  = BaseHelper::renderBadge($crawler->last_status, $color);

                $runAt      = $crawler->last_run_at
                    ? e($crawler->last_run_at->format('D, M j \a\t g:ia') . ' (' . $crawler->last_run_at->diffForHumans() . ')')
                    : 'Never';
                $newToday   = number_format((int) $crawler->new_today);
                $lrCreated  = number_format((int) ($crawler->last_run_created ?? 0));
                $lrUpdated  = number_format((int) ($crawler->last_run_updated ?? 0));
                $lrFound    = number_format((int) ($crawler->last_run_found ?? 0));
                $activeJobs = number_format((int) ($crawler->active_jobs ?? 0));
                $totalJobs  = number_format((int) $crawler->total_jobs);

                return <<<HTML
                    <span class="crstatus">{$badge}<span class="crstatus-tip">
                        <span class="crname-tip-row"><span class="crname-tip-lbl">Last run</span><span>{$runAt}</span></span>
                        <span class="crname-tip-row"><span class="crname-tip-lbl">Today</span><span class="text-success fw-semibold">{$newToday} new jobs</span></span>
                        <span class="crname-tip-row"><span class="crname-tip-lbl">Last run</span><span>+{$lrCreated} new &middot; {$lrUpdated} updated &middot; {$lrFound} found</span></span>
                        <span class="crname-tip-row"><span class="crname-tip-lbl">Published</span><span>{$activeJobs} active / {$totalJobs} total</span></span>
                    </span></span>
                HTML;
            })
            ->editColumn('last_run_at', fn (JobCrawler $crawler) => $crawler->last_run_at?->diffForHumans() ?: '&mdash;')
            ->addColumn('runs_today', fn (JobCrawler $crawler) => (int) $crawler->runs_today > 0
                ? '<span class="badge bg-blue-lt">' . number_format((int) $crawler->runs_today) . 'x</span>'
                : '<span class="text-muted">—</span>');

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        return $this->applyScopes(
            $this->getModel()->query()
                ->select([
                    'id',
                    'name',
                    'source_url',
                    'parser_type',
                    'field_mappings',
                    'is_active',
                    'last_status',
                    'last_run_at',
                    'created_at',
                ])
                ->selectRaw(
                    '(SELECT COUNT(*) FROM jb_jobs WHERE jb_jobs.crawler_id = jb_job_crawlers.id) AS total_jobs'
                )
                ->selectRaw(
                    '(SELECT COALESCE(SUM(jobs_created), 0) FROM jb_job_crawler_runs'
                    . ' WHERE jb_job_crawler_runs.crawler_id = jb_job_crawlers.id'
                    . ' AND DATE(started_at) = CURDATE()) AS new_today'
                )
                ->selectRaw(
                    "(SELECT jobs_created FROM jb_job_crawler_runs"
                    . " WHERE crawler_id = jb_job_crawlers.id AND status IN ('success','failed')"
                    . " ORDER BY id DESC LIMIT 1) AS last_run_created"
                )
                ->selectRaw(
                    "(SELECT jobs_updated FROM jb_job_crawler_runs"
                    . " WHERE crawler_id = jb_job_crawlers.id AND status IN ('success','failed')"
                    . " ORDER BY id DESC LIMIT 1) AS last_run_updated"
                )
                ->selectRaw(
                    "(SELECT jobs_found FROM jb_job_crawler_runs"
                    . " WHERE crawler_id = jb_job_crawlers.id AND status IN ('success','failed')"
                    . " ORDER BY id DESC LIMIT 1) AS last_run_found"
                )
                ->selectRaw(
                    "(SELECT COUNT(*) FROM jb_jobs"
                    . " WHERE jb_jobs.crawler_id = jb_job_crawlers.id AND jb_jobs.status = 'published') AS active_jobs"
                )
                ->selectRaw(
                    '(SELECT COUNT(*) FROM jb_job_crawler_runs'
                    . ' WHERE crawler_id = jb_job_crawlers.id'
                    . ' AND DATE(started_at) = CURDATE()) AS runs_today'
                )
                ->orderBy('id')
        );
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            NameColumn::make()->renderUsing(function (FormattedColumn $column) {
                $item   = $column->getItem();
                $name   = e($item->name);
                $parser = e($item->parser_type ?? '');
                $source = e($item->source_url ?? '');
                $href   = $source ?: '#';

                return <<<HTML
                    <span class="crname"><a href="{$href}" target="_blank" rel="noopener">{$name}</a><span class="crname-tip"><span class="crname-tip-row"><span class="crname-tip-lbl">Parser</span><code>{$parser}</code></span><span class="crname-tip-row"><span class="crname-tip-lbl">Source</span>{$source}</span></span></span>
                HTML;
            }),
            Column::make('country')->title('Country')->width(150)->orderable(false),
            Column::make('jobs')->title('Jobs')->width(180)->orderable(false)->className('text-end'),
            FormattedColumn::make('is_active')
                ->title('Active')
                ->width(90)
                ->orderable(false)
                ->getValueUsing(function (FormattedColumn $column) {
                    $item = $column->getItem();
                    $checked = $item->is_active ? 'checked' : '';
                    $url = route('job-board.crawlers.toggle-active', $item->getKey());

                    return <<<HTML
                        <label class="form-check form-switch mb-0" title="Toggle active">
                            <input type="checkbox" class="form-check-input crawler-toggle-active" {$checked}
                                data-url="{$url}"
                                style="cursor:pointer;">
                        </label>
                        HTML;
                }),
            Column::make('last_status')->title('Last status')->width(130),
            Column::make('last_run_at')->title('Last run')->width(160),
            Column::make('runs_today')->title('Runs today')->width(100)->orderable(false)->className('text-center'),
        ];
    }

    public function buttons(): array
    {
        return [];
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('job-board.crawlers.destroy'),
        ];
    }
}
