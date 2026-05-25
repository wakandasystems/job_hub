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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class JobCrawlerTable extends TableAbstract
{
    protected bool $bStateSave = false;

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

        static $registeredStatsFilter = false;

        if (! $registeredStatsFilter) {
            add_filter(BASE_FILTER_TABLE_BEFORE_RENDER, function (?string $html, TableAbstract $table): ?string {
                if (! $table instanceof self) {
                    return $html;
                }

                return ($html ?: '') . $table->renderStatsPeriodFilter();
            }, 20, 2);

            $registeredStatsFilter = true;
        }
    }

    protected function countryForCrawler(JobCrawler $crawler): string
    {
        // Static map for parsers that always target one country
        $staticMap = [
            'gozambiajobs' => ['flag' => '🇿🇲', 'name' => 'Zambia'],
            'jobsearchzm'  => ['flag' => '🇿🇲', 'name' => 'Zambia'],
            'careers24'    => ['flag' => '🇿🇦', 'name' => 'South Africa'],
            'myjobmu'      => ['flag' => '🇲🇺', 'name' => 'Mauritius'],
            'jobstanzania' => ['flag' => '🇹🇿', 'name' => 'Tanzania'],
            'jobinrwanda'  => ['flag' => '🇷🇼', 'name' => 'Rwanda'],
            'keejob'       => ['flag' => '🇹🇳', 'name' => 'Tunisia'],
        ];

        // Go Africa Jobs reuses the Go Zambia parser but stores its country in field_mappings.
        if ($crawler->parser_type === 'gozambiajobs' && $country = $this->countryFromMappings($crawler)) {
            return $country;
        }

        if (isset($staticMap[$crawler->parser_type])) {
            $info = $staticMap[$crawler->parser_type];

            return $info['flag'] . ' ' . $info['name'];
        }

        // For parsers whose country is stored in field_mappings.country_id
        if (in_array($crawler->parser_type, ['ringier', 'africawork', 'pending']) && $country = $this->countryFromMappings($crawler)) {
            return $country;
        }

        return '&mdash;';
    }

    protected function countryFromMappings(JobCrawler $crawler): ?string
    {
        $mappings = $crawler->field_mappings;
        $countryId = is_array($mappings) ? ($mappings['country_id'] ?? null) : null;

        if (! $countryId) {
            return null;
        }

        $country = \DB::table('countries')->where('id', $countryId)->first(['name', 'code']);

        return $country ? $this->codeToFlag((string) $country->code) . ' ' . $country->name : null;
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
        $periodLabel = e($this->statsRange()['label']);

        $data = $this->table
            ->eloquent($this->query())
            ->addColumn('country', fn (JobCrawler $crawler) => $this->countryForCrawler($crawler))
            ->addColumn('jobs', function (JobCrawler $crawler) use ($periodLabel) {
                $total = number_format((int) $crawler->total_jobs);
                $new   = (int) $crawler->period_created;
                $badge = $new > 0
                    ? '<span class="badge bg-success-lt ms-2">' . number_format($new) . ' new</span>'
                    : '<span class="text-muted ms-2 small">0 new</span>';
                $tip = 'Total jobs: ' . $total . ' · New in ' . $periodLabel . ': ' . number_format($new);

                return '<span title="' . e($tip) . '" style="cursor:default;">' . $total . $badge . '</span>';
            })
            ->editColumn('last_status', function (JobCrawler $crawler) use ($periodLabel) {
                if (! $crawler->last_status) {
                    return '&mdash;';
                }

                $color  = $crawler->last_status === 'failed' ? 'danger' : 'success';
                $badge  = BaseHelper::renderBadge($crawler->last_status, $color);

                $runAt      = $crawler->last_run_at
                    ? e($crawler->last_run_at->format('D, M j \a\t g:ia') . ' (' . $crawler->last_run_at->diffForHumans() . ')')
                    : 'Never';
                $periodCreated = number_format((int) $crawler->period_created);
                $periodUpdated = number_format((int) $crawler->period_updated);
                $periodFound = number_format((int) $crawler->period_found);
                $periodSkipped = number_format((int) $crawler->period_skipped);
                $lrCreated  = number_format((int) ($crawler->last_run_created ?? 0));
                $lrUpdated  = number_format((int) ($crawler->last_run_updated ?? 0));
                $lrFound    = number_format((int) ($crawler->last_run_found ?? 0));
                $activeJobs = number_format((int) ($crawler->active_jobs ?? 0));
                $totalJobs  = number_format((int) $crawler->total_jobs);

                return <<<HTML
                    <span class="crstatus">{$badge}<span class="crstatus-tip">
                        <span class="crname-tip-row"><span class="crname-tip-lbl">Last run</span><span>{$runAt}</span></span>
                        <span class="crname-tip-row"><span class="crname-tip-lbl">{$periodLabel}</span><span class="text-success fw-semibold">{$periodCreated} new</span></span>
                        <span class="crname-tip-row"><span class="crname-tip-lbl">Period totals</span><span>{$periodFound} found &middot; {$periodUpdated} updated &middot; {$periodSkipped} skipped</span></span>
                        <span class="crname-tip-row"><span class="crname-tip-lbl">Last run</span><span>+{$lrCreated} new &middot; {$lrUpdated} updated &middot; {$lrFound} found</span></span>
                        <span class="crname-tip-row"><span class="crname-tip-lbl">Published</span><span>{$activeJobs} active / {$totalJobs} total</span></span>
                    </span></span>
                HTML;
            })
            ->editColumn('last_run_at', fn (JobCrawler $crawler) => $crawler->last_run_at?->diffForHumans() ?: '&mdash;')
            ->addColumn('runs_today', fn (JobCrawler $crawler) => (int) $crawler->period_runs > 0
                ? '<span class="badge bg-blue-lt">' . number_format((int) $crawler->period_runs) . 'x</span>'
                : '<span class="text-muted">—</span>');

        return $this->toJson($data);
    }

    protected function resolveAllCrawlerCountries(): array
    {
        $staticNameMap = [
            'gozambiajobs' => 'Zambia',
            'jobsearchzm'  => 'Zambia',
            'careers24'    => 'South Africa',
            'myjobmu'      => 'Mauritius',
            'jobstanzania' => 'Tanzania',
            'jobinrwanda'  => 'Rwanda',
            'keejob'       => 'Tunisia',
        ];

        $crawlers = JobCrawler::query()->select(['id', 'parser_type', 'field_mappings'])->get();

        $countryIds = [];
        foreach ($crawlers as $crawler) {
            $mappings = $crawler->field_mappings;
            if (is_array($mappings) && ! empty($mappings['country_id'])) {
                $countryIds[] = (int) $mappings['country_id'];
            }
        }

        $countriesById = [];
        if ($countryIds) {
            \DB::table('countries')
                ->whereIn('id', array_unique($countryIds))
                ->get(['id', 'name'])
                ->each(function ($row) use (&$countriesById) {
                    $countriesById[(int) $row->id] = $row->name;
                });
        }

        $result = [];
        foreach ($crawlers as $crawler) {
            $mappings = $crawler->field_mappings;
            $countryId = is_array($mappings) ? (int) ($mappings['country_id'] ?? 0) : 0;

            if ($countryId > 0 && isset($countriesById[$countryId])) {
                $result[$crawler->id] = $countriesById[$countryId];
            } elseif (isset($staticNameMap[$crawler->parser_type]) && ! $countryId) {
                $result[$crawler->id] = $staticNameMap[$crawler->parser_type];
            }
        }

        return $result;
    }

    protected function getCountryFilterOptions(): array
    {
        $countries = array_unique(array_values($this->resolveAllCrawlerCountries()));
        sort($countries);

        return $countries;
    }

    protected function crawlerIdsForCountry(string $countryName): array
    {
        return array_keys(array_filter(
            $this->resolveAllCrawlerCountries(),
            fn (string $name) => strcasecmp($name, $countryName) === 0
        ));
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $range = $this->statsRange();
        $from = $range['from']->toDateTimeString();
        $to = $range['to']->toDateTimeString();

        $countryFilter = (string) $this->request()->input('country_filter', 'Zambia');

        $baseQuery = $this->getModel()->query()
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
            ]);

        if ($countryFilter !== '') {
            $ids = $this->crawlerIdsForCountry($countryFilter);
            $baseQuery->whereIn('id', $ids ?: [0]);
        }

        return $this->applyScopes(
            $baseQuery
                ->selectRaw(
                    '(SELECT COUNT(*) FROM jb_jobs WHERE jb_jobs.crawler_id = jb_job_crawlers.id) AS total_jobs'
                )
                ->selectRaw(
                    '(SELECT COALESCE(SUM(jobs_created), 0) FROM jb_job_crawler_runs'
                    . ' WHERE jb_job_crawler_runs.crawler_id = jb_job_crawlers.id'
                    . " AND started_at BETWEEN '$from' AND '$to') AS period_created"
                )
                ->selectRaw(
                    '(SELECT COALESCE(SUM(jobs_found), 0) FROM jb_job_crawler_runs'
                    . ' WHERE jb_job_crawler_runs.crawler_id = jb_job_crawlers.id'
                    . " AND started_at BETWEEN '$from' AND '$to') AS period_found"
                )
                ->selectRaw(
                    '(SELECT COALESCE(SUM(jobs_updated), 0) FROM jb_job_crawler_runs'
                    . ' WHERE jb_job_crawler_runs.crawler_id = jb_job_crawlers.id'
                    . " AND started_at BETWEEN '$from' AND '$to') AS period_updated"
                )
                ->selectRaw(
                    '(SELECT COALESCE(SUM(jobs_skipped), 0) FROM jb_job_crawler_runs'
                    . ' WHERE jb_job_crawler_runs.crawler_id = jb_job_crawlers.id'
                    . " AND started_at BETWEEN '$from' AND '$to') AS period_skipped"
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
                    . " AND started_at BETWEEN '$from' AND '$to') AS period_runs"
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
            Column::make('runs_today')->title('Runs')->width(100)->orderable(false)->className('text-center'),
        ];
    }

    protected function statsRange(): array
    {
        $period = (string) $this->request()->input('stats_period', 'today');
        $now = Carbon::now();

        [$from, $to, $label] = match ($period) {
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'Yesterday'],
            '7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), 'Last 7 days'],
            '30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay(), 'Last 30 days'],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay(), 'This month'],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth(), 'Last month'],
            'custom' => $this->customStatsRange($now),
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'Today'],
        };

        return compact('period', 'from', 'to', 'label');
    }

    protected function customStatsRange(Carbon $now): array
    {
        $fromInput = (string) $this->request()->input('stats_from');
        $toInput = (string) $this->request()->input('stats_to');

        try {
            $from = $fromInput ? Carbon::parse($fromInput)->startOfDay() : $now->copy()->startOfDay();
        } catch (\Throwable) {
            $from = $now->copy()->startOfDay();
        }

        try {
            $to = $toInput ? Carbon::parse($toInput)->endOfDay() : $now->copy()->endOfDay();
        } catch (\Throwable) {
            $to = $now->copy()->endOfDay();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to, $from->toDateString() . ' to ' . $to->toDateString()];
    }

    public function renderStatsPeriodFilter(): string
    {
        $range = $this->statsRange();
        $period = $range['period'];
        $from = e((string) $this->request()->input('stats_from', $range['from']->toDateString()));
        $to = e((string) $this->request()->input('stats_to', $range['to']->toDateString()));
        $countryFilter = (string) $this->request()->input('country_filter', 'Zambia');
        $action = e($this->request()->url());
        $query = Arr::except($this->request()->query(), ['stats_period', 'stats_from', 'stats_to', 'country_filter']);
        $hidden = '';

        foreach ($query as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $hidden .= '<input type="hidden" name="' . e($key) . '" value="' . e((string) $value) . '">';
        }

        $option = fn (string $value, string $label) => '<option value="' . e($value) . '"' . ($period === $value ? ' selected' : '') . '>' . e($label) . '</option>';
        $options = implode('', [
            $option('today', 'Today'),
            $option('yesterday', 'Yesterday'),
            $option('7_days', 'Last 7 days'),
            $option('30_days', 'Last 30 days'),
            $option('this_month', 'This month'),
            $option('last_month', 'Last month'),
            $option('custom', 'Custom range'),
        ]);

        $countryOptions = '<option value=""' . ($countryFilter === '' ? ' selected' : '') . '>All countries</option>';
        foreach ($this->getCountryFilterOptions() as $name) {
            $sel = strcasecmp($name, $countryFilter) === 0 ? ' selected' : '';
            $countryOptions .= '<option value="' . e($name) . '"' . $sel . '>' . e($name) . '</option>';
        }

        return <<<HTML
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{$action}" id="crawler-filter-form" class="row g-2 align-items-end">
                        {$hidden}
                        <div class="col-12 col-md-2">
                            <label class="form-label mb-1">Country</label>
                            <select name="country_filter" class="form-select">
                                {$countryOptions}
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label mb-1">Stats period</label>
                            <select name="stats_period" class="form-select">
                                {$options}
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">From</label>
                            <input type="date" name="stats_from" value="{$from}" class="form-control">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">To</label>
                            <input type="date" name="stats_to" value="{$to}" class="form-control">
                        </div>
                        <div class="col-12 col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Apply</button>
                            <a href="{$action}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                        <div class="col-12 col-md text-muted small">
                            Showing run stats for <strong>{$range['label']}</strong>. Total jobs remains all-time; the green badge, runs, and tooltip period totals use this range.
                        </div>
                    </form>
                </div>
            </div>
            <script>
            document.getElementById('crawler-filter-form').addEventListener('submit', function() {
                Object.keys(localStorage).filter(k => k.startsWith('DataTables_')).forEach(k => localStorage.removeItem(k));
            });
            </script>
        HTML;
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
