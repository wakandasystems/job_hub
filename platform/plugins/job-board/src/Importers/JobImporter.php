<?php

namespace Botble\JobBoard\Importers;

use Botble\DataSynchronize\Importer\ImportColumn;
use Botble\DataSynchronize\Importer\Importer;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\CareerLevel;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Currency;
use Botble\JobBoard\Models\DegreeLevel;
use Botble\JobBoard\Models\FunctionalArea;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobExperience;
use Botble\JobBoard\Models\JobShift;
use Botble\JobBoard\Models\JobSkill;
use Botble\JobBoard\Models\JobType;
use Botble\JobBoard\Models\Tag;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\Slug\Facades\SlugHelper;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JobImporter extends Importer
{
    protected bool $updateExisting = false;

    public function __construct()
    {
        $this->updateExisting = request()->boolean('update_existing_jobs');
    }

    public function label(): string
    {
        return trans('plugins/job-board::import.name');
    }

    public function columns(): array
    {
        $columns = [
            ImportColumn::make('id')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('unique_id')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('name')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('slug')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('url')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('description')
                ->rules(['nullable', 'string']),
            ImportColumn::make('content')
                ->rules(['nullable', 'string']),
            ImportColumn::make('apply_url')
                ->rules(['nullable', 'url', 'max:255']),
            ImportColumn::make('company')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('address')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('country')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('state')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('city')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('is_freelance')
                ->boolean(),
            ImportColumn::make('career_level')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('salary_from')
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('salary_to')
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('salary_range')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('currency')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('degree_level')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('job_shift')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('job_experience')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('functional_area')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('hide_salary')
                ->boolean(),
            ImportColumn::make('number_of_positions')
                ->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('expire_date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('hide_company')
                ->boolean(),
            ImportColumn::make('latitude')
                ->rules(['nullable', 'numeric', 'min:-90', 'max:90']),
            ImportColumn::make('longitude')
                ->rules(['nullable', 'numeric', 'min:-180', 'max:180']),
            ImportColumn::make('is_featured')
                ->boolean(),
            ImportColumn::make('auto_renew')
                ->boolean(),
            ImportColumn::make('never_expired')
                ->boolean(),
            ImportColumn::make('employer_colleagues')
                ->rules(['nullable', 'string']),
            ImportColumn::make('start_date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('application_closing_date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('status')
                ->rules(['nullable', 'string', 'in:published,draft,pending,closed']),
            ImportColumn::make('moderation_status')
                ->rules(['nullable', 'string', 'in:approved,pending,rejected']),
            ImportColumn::make('skills')
                ->rules(['nullable', 'string']),
            ImportColumn::make('categories')
                ->rules(['nullable', 'string']),
            ImportColumn::make('types')
                ->rules(['nullable', 'string']),
            ImportColumn::make('tags')
                ->rules(['nullable', 'string']),
        ];

        if (JobBoardHelper::isZipCodeEnabled()) {
            $columns[] = ImportColumn::make('zip_code')
                ->rules(['nullable', 'string', 'max:20']);
        }

        return $columns;
    }

    public function handle(array $data): int
    {
        $count = 0;

        foreach ($data as $row) {
            DB::transaction(function () use ($row, &$count): void {
                $slug = Arr::pull($row, 'slug');
                $url = Arr::pull($row, 'url');

                $countryId = $this->parseIdFromString(Arr::get($row, 'country'), Country::class);
                $stateId = $this->parseIdFromString(Arr::get($row, 'state'), State::class);
                $cityId = $this->parseIdFromString(Arr::get($row, 'city'), City::class);
                $companyId = $this->parseIdFromString(Arr::get($row, 'company'), Company::class);
                $currencyId = $this->parseIdFromString(Arr::get($row, 'currency'), Currency::class, 'title');
                $careerLevelId = $this->parseIdFromString(Arr::get($row, 'career_level'), CareerLevel::class);
                $degreeLevelId = $this->parseIdFromString(Arr::get($row, 'degree_level'), DegreeLevel::class);
                $jobShiftId = $this->parseIdFromString(Arr::get($row, 'job_shift'), JobShift::class);
                $jobExperienceId = $this->parseIdFromString(Arr::get($row, 'job_experience'), JobExperience::class);
                $functionalAreaId = $this->parseIdFromString(Arr::get($row, 'functional_area'), FunctionalArea::class);

                $skillIds = $this->parseIdsFromString(Arr::get($row, 'skills', ''), JobSkill::class);
                $categoryIds = $this->parseIdsFromString(Arr::get($row, 'categories', ''), Category::class);
                $typeIds = $this->parseIdsFromString(Arr::get($row, 'types', ''), JobType::class);
                $tagIds = $this->parseIdsFromString(Arr::get($row, 'tags', ''), Tag::class);

                $employerColleagues = $this->parseEmployerColleagues(Arr::get($row, 'employer_colleagues'));

                $expireDate = $this->parseDate(Arr::get($row, 'expire_date'));
                $startDate = $this->parseDate(Arr::get($row, 'start_date'));
                $applicationClosingDate = $this->parseDate(Arr::get($row, 'application_closing_date'));

                $uniqueId = Arr::get($row, 'unique_id');
                $id = Arr::get($row, 'id');
                $job = null;

                if ($this->updateExisting) {
                    if ($id) {
                        $job = Job::query()->find($id);
                    }

                    if (! $job && $uniqueId) {
                        $job = Job::query()->where('unique_id', $uniqueId)->first();
                    }
                }

                if (! $job) {
                    $job = new Job();
                }

                $jobData = [
                    'name' => Arr::get($row, 'name'),
                    'description' => Arr::get($row, 'description'),
                    'content' => Arr::get($row, 'content'),
                    'apply_url' => Arr::get($row, 'apply_url'),
                    'address' => Arr::get($row, 'address'),
                    'country_id' => $countryId,
                    'state_id' => $stateId,
                    'city_id' => $cityId,
                    'company_id' => $companyId,
                    'is_freelance' => (bool) Arr::get($row, 'is_freelance', false),
                    'career_level_id' => $careerLevelId,
                    'salary_from' => Arr::get($row, 'salary_from'),
                    'salary_to' => Arr::get($row, 'salary_to'),
                    'salary_range' => Arr::get($row, 'salary_range'),
                    'currency_id' => $currencyId,
                    'degree_level_id' => $degreeLevelId,
                    'job_shift_id' => $jobShiftId,
                    'job_experience_id' => $jobExperienceId,
                    'functional_area_id' => $functionalAreaId,
                    'hide_salary' => (bool) Arr::get($row, 'hide_salary', false),
                    'number_of_positions' => Arr::get($row, 'number_of_positions', 1),
                    'expire_date' => $expireDate,
                    'hide_company' => (bool) Arr::get($row, 'hide_company', false),
                    'latitude' => Arr::get($row, 'latitude'),
                    'longitude' => Arr::get($row, 'longitude'),
                    'auto_renew' => (bool) Arr::get($row, 'auto_renew', false),
                    'never_expired' => (bool) Arr::get($row, 'never_expired', ! $expireDate),
                    'is_featured' => (bool) Arr::get($row, 'is_featured', false),
                    'employer_colleagues' => $employerColleagues,
                    'start_date' => $startDate,
                    'application_closing_date' => $applicationClosingDate,
                    'status' => Arr::get($row, 'status', 'published'),
                    'moderation_status' => Arr::get($row, 'moderation_status', 'approved'),
                ];

                if (empty($uniqueId)) {
                    $jobData['unique_id'] = null;
                } else {
                    $jobData['unique_id'] = $uniqueId;
                }

                if (JobBoardHelper::isZipCodeEnabled()) {
                    $jobData['zip_code'] = Arr::get($row, 'zip_code');
                }

                $job->forceFill($jobData);
                $job->author()->associate(auth()->user());
                $job->save();

                $job->skills()->sync($skillIds);
                $job->categories()->sync($categoryIds);
                $job->jobTypes()->sync($typeIds);
                $job->tags()->sync($tagIds);

                if (! $slug && $url) {
                    $slug = basename(parse_url($url, PHP_URL_PATH));
                }

                SlugHelper::createSlug($job, $slug);

                $count++;
            });
        }

        return $count;
    }

    protected function parseEmployerColleagues($value): array
    {
        if (empty($value)) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            try {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (Exception) {
            }

            return array_map('trim', explode(',', $value));
        }

        return [];
    }

    protected function parseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (Exception) {
            return null;
        }
    }

    public function getValidateUrl(): string
    {
        return route('tools.data-synchronize.import.jobs.validate');
    }

    public function getImportUrl(): string
    {
        return route('tools.data-synchronize.import.jobs.store');
    }

    protected function parseIdFromString(?string $item, string $modelClass, string $column = 'name'): ?int
    {
        if (empty($item)) {
            return null;
        }

        $model = $modelClass::query()->firstOrCreate([$column => trim($item)]);

        if (SlugHelper::isSupportedModel($modelClass) && $model->wasRecentlyCreated) {
            SlugHelper::createSlug($model);
        }

        return $model->getKey();
    }

    protected function parseIdsFromString(string $items, string $modelClass): array
    {
        if (empty($items)) {
            return [];
        }

        $items = explode(',', $items);
        $ids = [];

        foreach ($items as $item) {
            $item = trim($item);
            if (empty($item)) {
                continue;
            }

            $id = $this->parseIdFromString($item, $modelClass);
            if ($id) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
