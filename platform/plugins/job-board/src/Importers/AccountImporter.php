<?php

namespace Botble\JobBoard\Importers;

use Botble\Base\Models\BaseModel;
use Botble\DataSynchronize\Contracts\Importer\WithMapping;
use Botble\DataSynchronize\Importer\ImportColumn;
use Botble\DataSynchronize\Importer\Importer;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Models\Account;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\Slug\Facades\SlugHelper;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountImporter extends Importer implements WithMapping
{
    protected bool $updateExisting = false;

    public function __construct()
    {
        $this->updateExisting = request()->boolean('update_existing_accounts');
    }

    public function chunkSize(): int
    {
        return 50;
    }

    public function getLabel(): string
    {
        return trans('plugins/job-board::account.name');
    }

    public function columns(): array
    {
        return [
            ImportColumn::make('first_name')
                ->rules(['required', 'string', 'max:120']),
            ImportColumn::make('last_name')
                ->rules(['required', 'string', 'max:120']),
            ImportColumn::make('email')
                ->rules(['required', 'email', 'max:60', 'unique:jb_accounts,email']),
            ImportColumn::make('password')
                ->rules(['nullable', 'string', 'min:6']),
            ImportColumn::make('description')
                ->rules(['nullable', 'string', 'max:400']),
            ImportColumn::make('gender')
                ->rules(['nullable', 'string', 'max:20']),
            ImportColumn::make('dob')
                ->label('Date of Birth')
                ->rules(['nullable', 'date']),
            ImportColumn::make('phone')
                ->rules(['nullable', 'string', 'max:25']),
            ImportColumn::make('address')
                ->rules(['nullable', 'string', 'max:250']),
            ImportColumn::make('bio')
                ->rules(['nullable', 'string']),
            ImportColumn::make('type')
                ->rules([Rule::in(AccountTypeEnum::values())]),
            ImportColumn::make('is_public_profile')
                ->boolean(),
            ImportColumn::make('is_featured')
                ->boolean(),
            ImportColumn::make('available_for_hiring')
                ->boolean(),
            ImportColumn::make('country')
                ->rules(['nullable', 'string']),
            ImportColumn::make('state')
                ->rules(['nullable', 'string']),
            ImportColumn::make('city')
                ->rules(['nullable', 'string']),
            ImportColumn::make('slug')
                ->rules(['nullable', 'string', 'max:250']),
        ];
    }

    public function examples(): array
    {
        $accounts = Account::query()
            ->take(5)
            ->with(['country', 'state', 'city', 'slugable'])
            ->get()
            ->map(function (Account $account) {
                return [
                    ...$account->toArray(),
                    'slug' => $account->slugable?->key,
                    'description' => Str::limit($account->description, 50),
                    'bio' => Str::limit($account->bio, 100),
                    'is_public_profile' => $account->is_public_profile ? 'Yes' : 'No',
                    'is_featured' => $account->is_featured ? 'Yes' : 'No',
                    'available_for_hiring' => $account->available_for_hiring ? 'Yes' : 'No',
                    'country' => $account->country?->name,
                    'state' => $account->state?->name,
                    'city' => $account->city?->name,
                    'type' => $account->type?->getValue(),
                ];
            });

        if ($accounts->isNotEmpty()) {
            return $accounts->all();
        }

        return [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'password' => 'password123',
                'description' => 'Experienced software developer',
                'gender' => 'Male',
                'dob' => '1990-01-15',
                'phone' => '+1234567890',
                'address' => '123 Main St',
                'bio' => 'Passionate about coding and technology',
                'type' => AccountTypeEnum::JOB_SEEKER,
                'is_public_profile' => 'Yes',
                'is_featured' => 'No',
                'available_for_hiring' => 'Yes',
                'country' => 'United States',
                'state' => 'California',
                'city' => 'San Francisco',
            ],
        ];
    }

    public function getValidateUrl(): string
    {
        return route('tools.data-synchronize.import.accounts.validate');
    }

    public function getImportUrl(): string
    {
        return route('tools.data-synchronize.import.accounts.store');
    }

    public function getDownloadExampleUrl(): ?string
    {
        return route('tools.data-synchronize.import.accounts.download-example');
    }

    public function getExportUrl(): ?string
    {
        return Auth::user()->hasPermission('accounts.export')
            ? route('tools.data-synchronize.export.accounts.store')
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
            $email = Arr::get($row, 'email');
            $password = Arr::pull($row, 'password');

            Arr::forget($row, ['country', 'state', 'city']);

            $account = null;

            if ($this->updateExisting) {
                $account = Account::query()->where('email', $email)->first();
            }

            if (! $account) {
                $account = new Account();
                $account->email = $email;
                $wasRecentlyCreated = true;
            } else {
                $wasRecentlyCreated = false;
            }

            if (! empty($password)) {
                $row['password'] = Hash::make($password);
            } elseif (! $account->exists) {
                $row['password'] = Hash::make(Str::random(32));
            } else {
                unset($row['password']);
            }

            if ($wasRecentlyCreated && empty($row['confirmed_at'])) {
                $row['confirmed_at'] = Carbon::now();
            }

            if (isset($row['dob']) && $row['dob'] === '') {
                $row['dob'] = null;
            }

            $account->forceFill($row);
            $account->save();

            if (! $slug && $url) {
                $slug = basename(parse_url($url, PHP_URL_PATH));
            }

            SlugHelper::createSlug($account, $slug);

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
