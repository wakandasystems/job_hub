<?php

namespace Botble\JobBoard\Importers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Botble\DataSynchronize\Contracts\Importer\WithMapping;
use Botble\DataSynchronize\Importer\ImportColumn;
use Botble\DataSynchronize\Importer\Importer;
use Botble\JobBoard\Models\Company;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\Media\Facades\RvMedia;
use Botble\Slug\Facades\SlugHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyImporter extends Importer implements WithMapping
{
    protected bool $updateExisting = false;

    public function __construct()
    {
        $this->updateExisting = request()->boolean('update_existing_companies');
    }

    public function chunkSize(): int
    {
        return 50;
    }

    public function getLabel(): string
    {
        return trans('plugins/job-board::company.name');
    }

    public function columns(): array
    {
        return [
            ImportColumn::make('name')
                ->rules(['required', 'string', 'max:250'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Name', 'max' => 250])),
            ImportColumn::make('slug')
                ->rules(['nullable', 'string', 'max:250'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Slug', 'max' => 250])),
            ImportColumn::make('description')
                ->rules(['nullable', 'string', 'max:400'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Description', 'max' => 400])),
            ImportColumn::make('content')
                ->rules(['nullable', 'string'], trans('plugins/job-board::import.company.rules.nullable_string', ['attribute' => 'Content'])),
            ImportColumn::make('email')
                ->rules(['nullable', 'email', 'max:250'], trans('plugins/job-board::import.company.rules.nullable_email_max', ['attribute' => 'Email', 'max' => 250])),
            ImportColumn::make('website')
                ->rules(['nullable', 'string', 'max:250'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Website', 'max' => 250])),
            ImportColumn::make('logo')
                ->rules(['nullable', 'string'], trans('plugins/job-board::import.company.rules.nullable_string', ['attribute' => 'Logo'])),
            ImportColumn::make('cover_image')
                ->rules(['nullable', 'string'], trans('plugins/job-board::import.company.rules.nullable_string', ['attribute' => 'Cover image'])),
            ImportColumn::make('address')
                ->rules(['nullable', 'string', 'max:250'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Address', 'max' => 250])),
            ImportColumn::make('country')
                ->rules(['nullable', 'string'], trans('plugins/job-board::import.company.rules.nullable_string', ['attribute' => 'Country'])),
            ImportColumn::make('state')
                ->rules(['nullable', 'string'], trans('plugins/job-board::import.company.rules.nullable_string', ['attribute' => 'State'])),
            ImportColumn::make('city')
                ->rules(['nullable', 'string'], trans('plugins/job-board::import.company.rules.nullable_string', ['attribute' => 'City'])),
            ImportColumn::make('postal_code')
                ->rules(['nullable', 'string', 'max:50'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Postal code', 'max' => 50])),
            ImportColumn::make('latitude')
                ->rules(['nullable', 'numeric'], trans('plugins/job-board::import.company.rules.nullable_numeric', ['attribute' => 'Latitude'])),
            ImportColumn::make('longitude')
                ->rules(['nullable', 'numeric'], trans('plugins/job-board::import.company.rules.nullable_numeric', ['attribute' => 'Longitude'])),
            ImportColumn::make('phone')
                ->rules(['nullable', 'string', 'max:50'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Phone', 'max' => 50])),
            ImportColumn::make('tax_id')
                ->rules(['nullable', 'string', 'max:50'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Tax ID', 'max' => 50])),
            ImportColumn::make('year_founded')
                ->rules(['nullable', 'integer'], trans('plugins/job-board::import.company.rules.nullable_integer', ['attribute' => 'Year founded'])),
            ImportColumn::make('ceo')
                ->rules(['nullable', 'string', 'max:250'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'CEO', 'max' => 250])),
            ImportColumn::make('number_of_offices')
                ->rules(['nullable', 'integer'], trans('plugins/job-board::import.company.rules.nullable_integer', ['attribute' => 'Number of offices'])),
            ImportColumn::make('number_of_employees')
                ->rules(['nullable', 'integer'], trans('plugins/job-board::import.company.rules.nullable_integer', ['attribute' => 'Number of employees'])),
            ImportColumn::make('annual_revenue')
                ->rules(['nullable', 'string', 'max:250'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Annual revenue', 'max' => 250])),
            ImportColumn::make('facebook')
                ->rules(['nullable', 'string', 'max:250'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Facebook', 'max' => 250])),
            ImportColumn::make('linkedin')
                ->rules(['nullable', 'string', 'max:250'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'LinkedIn', 'max' => 250])),
            ImportColumn::make('twitter')
                ->rules(['nullable', 'string', 'max:250'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Twitter', 'max' => 250])),
            ImportColumn::make('instagram')
                ->rules(['nullable', 'string', 'max:250'], trans('plugins/job-board::import.company.rules.nullable_string_max', ['attribute' => 'Instagram', 'max' => 250])),
            ImportColumn::make('is_featured')
                ->boolean()
                ->rules(['boolean'], trans('plugins/job-board::import.company.rules.in', ['attribute' => 'Is featured', 'values' => 'Yes, No'])),
            ImportColumn::make('status')
                ->rules([Rule::in(BaseStatusEnum::values())], trans('plugins/job-board::import.company.rules.in', ['attribute' => 'Status', 'values' => implode(', ', BaseStatusEnum::values())])),
        ];
    }

    public function examples(): array
    {
        $companies = Company::query()
            ->take(5)
            ->with(['country', 'state', 'city', 'slugable'])
            ->get()
            ->map(function (Company $company) { // @phpstan-ignore-line
                return [
                    ...$company->toArray(),
                    'slug' => $company->slugable?->key,
                    'description' => Str::limit($company->description, 50),
                    'content' => Str::limit($company->content),
                    'is_featured' => $company->is_featured ? 'Yes' : 'No',
                    'logo' => RvMedia::getImageUrl($company->logo),
                    'cover_image' => RvMedia::getImageUrl($company->cover_image),
                    'country' => $company->country?->name,
                    'state' => $company->state?->name,
                    'city' => $company->city?->name,
                ];
            });

        if ($companies->isNotEmpty()) {
            return $companies->all();
        }

        return [
            [
                'name' => 'Acme Corporation',
                'slug' => 'acme-corporation',
                'description' => 'Leading technology solutions provider',
                'content' => 'Acme Corporation is a global leader in technology solutions, providing innovative products and services to businesses worldwide.',
                'email' => 'info@acme.com',
                'website' => 'https://acme.com',
                'logo' => 'https://via.placeholder.com/150',
                'cover_image' => 'https://via.placeholder.com/600x400',
                'address' => '123 Tech Street',
                'country' => 'United States',
                'state' => 'California',
                'city' => 'San Francisco',
                'postal_code' => '94102',
                'latitude' => '37.7749',
                'longitude' => '-122.4194',
                'phone' => '+1 555-0100',
                'tax_id' => 'TAX123456',
                'year_founded' => '2010',
                'ceo' => 'John Doe',
                'number_of_offices' => '5',
                'number_of_employees' => '500',
                'annual_revenue' => '$50M',
                'facebook' => 'https://facebook.com/acme',
                'linkedin' => 'https://linkedin.com/company/acme',
                'twitter' => 'https://twitter.com/acme',
                'instagram' => 'https://instagram.com/acme',
                'is_featured' => 'Yes',
                'status' => BaseStatusEnum::PUBLISHED,
            ],
            [
                'name' => 'TechStart Inc',
                'slug' => 'techstart-inc',
                'description' => 'Innovative startup focused on AI solutions',
                'content' => 'TechStart Inc is revolutionizing the AI industry with cutting-edge machine learning solutions.',
                'email' => 'contact@techstart.com',
                'website' => 'https://techstart.com',
                'logo' => 'https://via.placeholder.com/150',
                'cover_image' => 'https://via.placeholder.com/600x400',
                'address' => '456 Innovation Blvd',
                'country' => 'United States',
                'state' => 'New York',
                'city' => 'New York',
                'postal_code' => '10001',
                'latitude' => '40.7128',
                'longitude' => '-74.0060',
                'phone' => '+1 555-0200',
                'tax_id' => 'TAX789012',
                'year_founded' => '2018',
                'ceo' => 'Jane Smith',
                'number_of_offices' => '2',
                'number_of_employees' => '100',
                'annual_revenue' => '$10M',
                'facebook' => 'https://facebook.com/techstart',
                'linkedin' => 'https://linkedin.com/company/techstart',
                'twitter' => 'https://twitter.com/techstart',
                'instagram' => 'https://instagram.com/techstart',
                'is_featured' => 'No',
                'status' => BaseStatusEnum::PUBLISHED,
            ],
        ];
    }

    public function getValidateUrl(): string
    {
        return route('tools.data-synchronize.import.companies.validate');
    }

    public function getImportUrl(): string
    {
        return route('tools.data-synchronize.import.companies.store');
    }

    public function getDownloadExampleUrl(): ?string
    {
        return route('tools.data-synchronize.import.companies.download-example');
    }

    public function getExportUrl(): ?string
    {
        return Auth::user()->hasPermission('companies.export')
            ? route('tools.data-synchronize.export.companies.store')
            : null;
    }

    public function map(mixed $row): array
    {
        $countryId = null;
        $stateId = null;
        $cityId = null;

        if ($country = Arr::get($row, 'country')) {
            $countryId = $this->parseIdFromString($country, Country::class);
        }

        if ($state = Arr::get($row, 'state')) {
            $stateId = $this->parseIdFromString($state, State::class);
        }

        if ($city = Arr::get($row, 'city')) {
            $cityId = $this->parseIdFromString($city, City::class);
        }

        return [
            ...$row,
            'country_id' => $countryId,
            'state_id' => $stateId,
            'city_id' => $cityId,
        ];
    }

    public function handle(array $data): int
    {
        $count = 0;

        foreach ($data as $row) {
            $slug = Arr::pull($row, 'slug');
            $url = Arr::pull($row, 'url');
            $name = Arr::pull($row, 'name');

            Arr::forget($row, ['country', 'state', 'city']);

            $company = null;

            if ($this->updateExisting && $slug) {
                $company = Company::query()
                    ->whereHas('slugable', function ($query) use ($slug): void {
                        $query->where('key', $slug);
                    })
                    ->first();
            }

            if (! $company && $this->updateExisting) {
                $company = Company::query()->where('name', $name)->first();
            }

            if (! $company) {
                $company = new Company();
                $company->name = $name;
            }

            $logo = ! empty($row['logo']) ? $this->resolveMediaImage($row['logo'], 'companies') : null;
            $coverImage = ! empty($row['cover_image']) ? $this->resolveMediaImage($row['cover_image'], 'companies') : null;

            $company->forceFill([
                ...$row,
                'name' => $name,
                'logo' => $logo,
                'cover_image' => $coverImage,
                'year_founded' => $row['year_founded'] !== '' ? $row['year_founded'] : null,
                'number_of_offices' => $row['number_of_offices'] !== '' ? $row['number_of_offices'] : null,
                'number_of_employees' => $row['number_of_employees'] !== '' ? $row['number_of_employees'] : null,
            ]);

            $company->save();

            if (! $slug && $url) {
                $slug = basename(parse_url($url, PHP_URL_PATH));
            }

            SlugHelper::createSlug($company, $slug);

            $count++;
        }

        return $count;
    }

    protected function parseIdFromString(string $item, string $modelClass): ?int
    {
        /**
         * @var BaseModel $modelClass
         * @var BaseModel $model
         */
        $model = $modelClass::query()->firstOrCreate(['name' => trim($item)]);

        if (SlugHelper::isSupportedModel($modelClass) && $model->wasRecentlyCreated) {
            SlugHelper::createSlug($model);
        }

        return $model->getKey();
    }
}
