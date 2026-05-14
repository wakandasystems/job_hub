<?php

namespace Botble\JobBoard\Exporters;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\DataSynchronize\Exporter\ExportColumn;
use Botble\DataSynchronize\Exporter\ExportCounter;
use Botble\DataSynchronize\Exporter\Exporter;
use Botble\JobBoard\Models\Company;
use Botble\Media\Facades\RvMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CompanyExporter extends Exporter
{
    protected ?int $limit = null;

    protected ?string $status = null;

    public function setLimit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getLabel(): string
    {
        return trans('plugins/job-board::company.name');
    }

    public function columns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name'),
            ExportColumn::make('description'),
            ExportColumn::make('content'),
            ExportColumn::make('email'),
            ExportColumn::make('website'),
            ExportColumn::make('logo'),
            ExportColumn::make('cover_image'),
            ExportColumn::make('address'),
            ExportColumn::make('country'),
            ExportColumn::make('state'),
            ExportColumn::make('city'),
            ExportColumn::make('postal_code'),
            ExportColumn::make('latitude'),
            ExportColumn::make('longitude'),
            ExportColumn::make('phone'),
            ExportColumn::make('tax_id')
                ->label('Tax ID'),
            ExportColumn::make('year_founded'),
            ExportColumn::make('ceo')
                ->label('CEO'),
            ExportColumn::make('number_of_offices'),
            ExportColumn::make('number_of_employees'),
            ExportColumn::make('annual_revenue'),
            ExportColumn::make('facebook'),
            ExportColumn::make('linkedin'),
            ExportColumn::make('twitter'),
            ExportColumn::make('instagram'),
            ExportColumn::make('is_featured')
                ->boolean(),
            ExportColumn::make('is_verified')
                ->boolean(),
            ExportColumn::make('slug'),
            ExportColumn::make('url')
                ->label('URL'),
            ExportColumn::make('status')
                ->dropdown(BaseStatusEnum::values()),
        ];
    }

    protected function applyFilters(Builder $query): void
    {
        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->limit) {
            $query->latest()->limit($this->limit);
        } else {
            $query->oldest();
        }
    }

    public function counters(): array
    {
        $query = Company::query();

        $this->applyFilters($query);

        return [
            ExportCounter::make()
                ->label(trans('plugins/job-board::export.companies.total'))
                ->value($query->count()),
        ];
    }

    public function hasDataToExport(): bool
    {
        return Company::query()->exists();
    }

    public function collection(): Collection
    {
        $query = Company::query()
            ->with(['country', 'state', 'city', 'slugable']);

        $this->applyFilters($query);

        return $query->get()
            ->transform(fn (Company $company) => [
                ...$company->toArray(),
                'slug' => $company->slugable?->key,
                'url' => $company->url,
                'logo' => RvMedia::getImageUrl($company->logo),
                'cover_image' => RvMedia::getImageUrl($company->cover_image),
                'country' => $company->country?->name,
                'state' => $company->state?->name,
                'city' => $company->city?->name,
            ]);
    }

    protected function getView(): string
    {
        return 'plugins/job-board::companies.export';
    }
}
