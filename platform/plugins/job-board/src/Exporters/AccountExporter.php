<?php

namespace Botble\JobBoard\Exporters;

use Botble\DataSynchronize\Exporter\ExportColumn;
use Botble\DataSynchronize\Exporter\ExportCounter;
use Botble\DataSynchronize\Exporter\Exporter;
use Botble\JobBoard\Models\Account;
use Botble\Media\Facades\RvMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AccountExporter extends Exporter
{
    protected ?int $limit = null;

    public function setLimit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLabel(): string
    {
        return trans('plugins/job-board::account.name');
    }

    public function columns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('first_name'),
            ExportColumn::make('last_name'),
            ExportColumn::make('email'),
            ExportColumn::make('description'),
            ExportColumn::make('gender'),
            ExportColumn::make('dob')
                ->label('Date of Birth'),
            ExportColumn::make('phone'),
            ExportColumn::make('address'),
            ExportColumn::make('bio'),
            ExportColumn::make('type'),
            ExportColumn::make('resume'),
            ExportColumn::make('cover_letter'),
            ExportColumn::make('is_public_profile')
                ->boolean(),
            ExportColumn::make('is_featured')
                ->boolean(),
            ExportColumn::make('available_for_hiring')
                ->boolean(),
            ExportColumn::make('country'),
            ExportColumn::make('state'),
            ExportColumn::make('city'),
            ExportColumn::make('avatar'),
            ExportColumn::make('views'),
            ExportColumn::make('slug'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    protected function applyFilters(Builder $query): void
    {
        if ($this->limit) {
            $query->latest()->limit($this->limit);
        } else {
            $query->oldest();
        }
    }

    public function counters(): array
    {
        $query = Account::query();

        $this->applyFilters($query);

        return [
            ExportCounter::make()
                ->label(trans('plugins/job-board::account.export.total'))
                ->value($query->count()),
        ];
    }

    public function hasDataToExport(): bool
    {
        return Account::query()->exists();
    }

    public function collection(): Collection
    {
        $query = Account::query()
            ->with(['country', 'state', 'city', 'slugable', 'avatar']);

        $this->applyFilters($query);

        return $query->get()
            ->transform(fn (Account $account) => [
                ...$account->toArray(),
                'slug' => $account->slugable?->key,
                'avatar' => RvMedia::getImageUrl($account->avatar_id),
                'resume' => $account->resume_url,
                'country' => $account->country?->name,
                'state' => $account->state?->name,
                'city' => $account->city?->name,
                'type' => $account->type?->getValue(),
            ]);
    }
}
