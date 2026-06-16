<?php

namespace Botble\JobBoard\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Enums\ModerationStatusEnum;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Botble\Location\Models\Country;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\Action;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\JobBoard\BulkActions\SendToWhapiChannelBulkAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\BulkChanges\CreatedAtBulkChange;
use Botble\Table\BulkChanges\NameBulkChange;
use Botble\Table\BulkChanges\SelectBulkChange;
use Botble\Table\BulkChanges\StatusBulkChange;
use Botble\Table\BulkChanges\TextBulkChange;
use Botble\Table\Columns\EnumColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Botble\Table\Columns\StatusColumn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;

class JobTable extends TableAbstract
{
    protected bool $bStateSave = false;

    protected ?string $defaultSortColumnName = 'created_at';

    public function setup(): void
    {
        $this
            ->model(Job::class)
            ->addActions([
                Action::make('analytics')
                    ->route('jobs.analytics')
                    ->label(trans('plugins/job-board::job.analytics.title'))
                    ->icon('ti ti-chart-line')
                    ->color('info'),
                EditAction::make()->route('jobs.edit'),
                DeleteAction::make()->route('jobs.destroy'),
            ]);

        static $registeredCountryFilter = false;

        if (! $registeredCountryFilter) {
            add_filter(BASE_FILTER_TABLE_BEFORE_RENDER, function (?string $html, TableAbstract $table): ?string {
                if (! $table instanceof self) {
                    return $html;
                }

                return ($html ?: '') . $table->renderCountryFilter();
            }, 20, 2);

            $registeredCountryFilter = true;
        }
    }

    protected function getCountriesWithJobs(): array
    {
        return \DB::table('jb_jobs')
            ->join('countries', 'jb_jobs.country_id', '=', 'countries.id')
            ->select('countries.id', 'countries.name', 'countries.code')
            ->distinct()
            ->orderBy('countries.name')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->id => $row->name])
            ->all();
    }

    public function renderCountryFilter(): string
    {
        $selectedId = (int) $this->request()->input('country_filter_id', 7);
        $action = e($this->request()->url());
        $query = Arr::except($this->request()->query(), ['country_filter_id']);
        $hidden = '';

        foreach ($query as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $hidden .= '<input type="hidden" name="' . e($key) . '" value="' . e((string) $value) . '">';
        }

        $options = '<option value=""' . ($selectedId === 0 ? ' selected' : '') . '>All countries</option>';
        foreach ($this->getCountriesWithJobs() as $id => $name) {
            $sel = ((int) $id === $selectedId) ? ' selected' : '';
            $options .= '<option value="' . (int) $id . '"' . $sel . '>' . e($name) . '</option>';
        }

        return <<<HTML
            <div class="card mb-3">
                <div class="card-body py-2">
                    <form method="GET" action="{$action}" class="row g-2 align-items-end">
                        {$hidden}
                        <div class="col-12 col-md-3">
                            <label class="form-label mb-1">Country</label>
                            <select name="country_filter_id" class="form-select" onchange="this.form.submit()">
                                {$options}
                            </select>
                        </div>
                        <div class="col-auto">
                            <a href="{$action}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        HTML;
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $countryFilterId = (int) $this->request()->input('country_filter_id', 7);

        $query = $this
            ->getModel()
            ->query()
            ->select([
                'id',
                'name',
                'created_at',
                'status',
                'moderation_status',
                'expire_date',
                'never_expired',
                'unique_id',
                'company_id',
                'country_id',
                'whatsapp_image',
                'tiktok_image',
                'linkedin_image',
                'facebook_image',
            ])
            ->with(['company:id,name,logo', 'country:id,name,code'])
            ->latest('created_at');

        if ($countryFilterId > 0) {
            $query->where('country_id', $countryFilterId);
        }

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            NameColumn::make()->route('jobs.edit'),
            FormattedColumn::make('unique_id')
                ->title(trans('plugins/job-board::job-board.form.unique_id'))
                ->withEmptyState(),
            FormattedColumn::make('company_id')
                ->title(trans('plugins/job-board::forms.company'))
                ->withEmptyState()
                ->getValueUsing(function (FormattedColumn $column) {
                    $item = $column->getItem();

                    if (! $item->company->exists) {
                        return null;
                    }

                    $logo = Html::image($item->company->logo_thumb, $item->company->name, [
                        'style' => 'width:28px;height:28px;object-fit:contain;border-radius:4px;margin-right:6px;vertical-align:middle;',
                    ])->toHtml();

                    return Html::link(
                        route('companies.edit', $item->company_id),
                        $logo . e($item->company->name),
                        [],
                        null,
                        false
                    )->toHtml();
                }),
            FormattedColumn::make('country_id')
                ->title('Country')
                ->withEmptyState()
                ->getValueUsing(function (FormattedColumn $column) {
                    $item = $column->getItem();

                    if (! $item->country || ! $item->country->getKey()) {
                        return null;
                    }

                    $flag = wakanda_country_flag($item->country->code);

                    return '<span style="font-size:1.2em;margin-right:4px;">' . $flag . '</span>' . e($item->country->name);
                }),
            FormattedColumn::make('expire_date')
                ->title(trans('plugins/job-board::messages.expire_date'))
                ->width(170)
                ->getValueUsing(function (FormattedColumn $column) {
                    $item = $column->getItem();

                    if ($item->never_expired) {
                        return BaseHelper::renderIcon('ti ti-infinity');
                    }

                    if (! $item->expire_date) {
                        return null;
                    }

                    $expireDate = BaseHelper::formatDate($item->expire_date);

                    if ($item->expire_date->isPast()) {
                        return Html::tag('span', $expireDate, ['class' => 'text-danger'])->toHtml();
                    }

                    if (Carbon::now()->diffInDays($item->expire_date) < 3) {
                        return Html::tag('span', $expireDate, ['class' => 'text-warning'])->toHtml();
                    }

                    return $expireDate;
                }),
            FormattedColumn::make('created_at')
                ->title('Age')
                ->width(120)
                ->getValueUsing(fn (FormattedColumn $column) => Carbon::parse($column->getItem()->created_at)->diffForHumans([
                    'parts' => 2,
                    'short' => true,
                ])),
            FormattedColumn::make('whatsapp_image')
                ->title('Social')
                ->width(100)
                ->alignCenter()
                ->getValueUsing(function (FormattedColumn $column) {
                    $item = $column->getItem();

                    $icon = fn (string $fa, string $color, string $label, bool $active) =>
                        '<span title="' . $label . '" style="font-size:1.1rem;margin:0 2px;color:' . ($active ? $color : '#ccc') . ';">'
                        . '<i class="' . $fa . '"></i>'
                        . ($active ? ' <i class="fas fa-check" style="font-size:.55rem;vertical-align:top;"></i>' : '')
                        . '</span>';

                    return
                        $icon('fab fa-whatsapp',  '#25D366', 'WhatsApp image',  !empty($item->whatsapp_image))
                        . $icon('fab fa-tiktok',   '#000',    'TikTok image',    !empty($item->tiktok_image))
                        . $icon('fab fa-linkedin', '#0A66C2', 'LinkedIn image',  !empty($item->linkedin_image))
                        . $icon('fab fa-facebook', '#1877F2', 'Facebook image',  !empty($item->facebook_image));
                }),
            StatusColumn::make(),
            EnumColumn::make('moderation_status')
                ->title(trans('plugins/job-board::job.moderation_status'))
                ->width(150),
        ];
    }

    public function buttons(): array
    {
        $buttons = $this->addCreateButton(route('jobs.create'), 'jobs.create');

        if ($this->hasPermission('jobs.import')) {
            $buttons['import'] = [
                'link' => route('tools.data-synchronize.import.jobs.index'),
                'text' =>
                    BaseHelper::renderIcon('ti ti-upload')
                    . trans('plugins/job-board::import.name'),
            ];
        }

        if ($this->hasPermission('jobs.export')) {
            $buttons['export'] = [
                'link' => route('tools.data-synchronize.export.jobs.index'),
                'text' =>
                    BaseHelper::renderIcon('ti ti-download')
                    . trans('plugins/job-board::export.jobs.name'),
            ];
        }

        return $buttons;
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('jobs.destroy'),
            SendToWhapiChannelBulkAction::make(),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            NameBulkChange::make(),
            StatusBulkChange::make()->choices(JobStatusEnum::labels()),
            CreatedAtBulkChange::make(),
            SelectBulkChange::make()
                ->name('moderation_status')
                ->title(trans('plugins/job-board::job.moderation_status'))
                ->choices(ModerationStatusEnum::labels())
                ->validate('required|in:' . implode(',', ModerationStatusEnum::values())),
            TextBulkChange::make()
                ->name('unique_id')
                ->title(trans('plugins/job-board::job-board.form.unique_id'))
                ->validate('nullable|string|max:120'),
            SelectBulkChange::make()
                ->name('type')
                ->title(trans('plugins/job-board::messages.type'))
                ->choices([
                    'expired' => trans('plugins/job-board::messages.expired_jobs'),
                    'without-company' => trans('plugins/job-board::messages.jobs_without_company'),
                ])
                ->validate('required|in:expired,without-company'),
            SelectBulkChange::make()
                ->name('company_id')
                ->title(trans('plugins/job-board::forms.company'))
                ->searchable()
                ->choices(fn () => Company::query()->pluck('name', 'id')->all()),
            SelectBulkChange::make()
                ->name('country_id')
                ->title('Country')
                ->searchable()
                ->choices(fn () => Country::query()->orderBy('name')->pluck('name', 'id')->all()),
        ];
    }

    public function getOperationsHeading(): array
    {
        return [
            'operations' => [
                'title' => trans('core/base::tables.operations'),
                'width' => '300px',
                'class' => 'text-center',
                'orderable' => false,
                'searchable' => false,
                'exportable' => false,
                'printable' => false,
            ],
        ];
    }

    public function applyFilterCondition(
        EloquentBuilder|QueryBuilder|EloquentRelation $query,
        string $key,
        string $operator,
        ?string $value
    ): EloquentRelation|EloquentBuilder|QueryBuilder {
        if ($key == 'country_id') {
            return $query->where('jb_jobs.country_id', $operator, $value);
        }

        if ($key == 'type') {
            switch ($value) {
                case 'expired':
                    // @phpstan-ignore-next-line
                    return $query->expired();
                case 'without-company':
                    return $query->whereNull('company_id');
            }
        }

        return parent::applyFilterCondition($query, $key, $operator, $value);
    }
}
