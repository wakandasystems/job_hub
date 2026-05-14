<?php

namespace Botble\JobBoard\Exporters;

use Botble\DataSynchronize\Exporter\ExportColumn;
use Botble\DataSynchronize\Exporter\ExportCounter;
use Botble\DataSynchronize\Exporter\Exporter;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JobExporter extends Exporter
{
    protected ?int $limit = null;

    protected ?string $status = null;

    protected ?bool $isFeatured = null;

    protected ?string $startDate = null;

    protected ?string $endDate = null;

    protected int $chunkSize = 200;

    protected bool $useChunkedExport = true;

    protected bool $optimizeQueries = true;

    protected bool $streamingMode = false;

    protected bool $useMultiFile = false;

    protected int $recordsPerFile = 10000;

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

    public function setIsFeatured(?bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function setStartDate(?string $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function setEndDate(?string $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function label(): string
    {
        return trans('plugins/job-board::export.jobs.name');
    }

    public function columns(): array
    {
        $columns = [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('unique_id'),
            ExportColumn::make('name'),
            ExportColumn::make('slug'),
            ExportColumn::make('url'),
            ExportColumn::make('description'),
            ExportColumn::make('content'),
            ExportColumn::make('apply_url'),
            ExportColumn::make('company'),
            ExportColumn::make('address'),
            ExportColumn::make('country'),
            ExportColumn::make('state'),
            ExportColumn::make('city'),
            ExportColumn::make('is_freelance')->boolean(),
            ExportColumn::make('career_level'),
            ExportColumn::make('salary_from'),
            ExportColumn::make('salary_to'),
            ExportColumn::make('salary_range'),
            ExportColumn::make('currency'),
            ExportColumn::make('degree_level'),
            ExportColumn::make('job_shift'),
            ExportColumn::make('job_experience'),
            ExportColumn::make('functional_area'),
            ExportColumn::make('hide_salary')->boolean(),
            ExportColumn::make('number_of_positions'),
            ExportColumn::make('expire_date'),
            ExportColumn::make('author_id'),
            ExportColumn::make('author_type'),
            ExportColumn::make('views'),
            ExportColumn::make('number_of_applied'),
            ExportColumn::make('hide_company')->boolean(),
            ExportColumn::make('latitude'),
            ExportColumn::make('longitude'),
            ExportColumn::make('auto_renew')->boolean(),
            ExportColumn::make('external_apply_clicks'),
            ExportColumn::make('never_expired')->boolean(),
            ExportColumn::make('is_featured')->boolean(),
            ExportColumn::make('status'),
            ExportColumn::make('moderation_status'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
            ExportColumn::make('employer_colleagues'),
            ExportColumn::make('start_date'),
            ExportColumn::make('application_closing_date'),
            ExportColumn::make('skills'),
            ExportColumn::make('categories'),
            ExportColumn::make('types'),
            ExportColumn::make('tags'),
        ];

        if (JobBoardHelper::isZipCodeEnabled()) {
            $columns[] = ExportColumn::make('zip_code');
        }

        return $columns;
    }

    public function collection(): Collection
    {
        if ($this->useChunkedExport) {
            return $this->getChunkedCollection();
        }

        return $this->getAllJobs();
    }

    protected function getChunkedCollection(): Collection
    {
        $jobs = collect();
        $with = $this->getOptimizedRelationships();

        DB::disableQueryLog();
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $processedCount = 0;
        $lastId = 0;

        do {
            $batch = $this->getJobQuery()
                ->select($this->getSelectColumns())
                ->where('id', '>', $lastId)
                ->with($with)
                ->orderBy('id')
                ->limit($this->chunkSize)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            $jobs = $jobs->concat($this->jobResults($batch));
            $lastId = $batch->last()->getKey();
            $processedCount += $batch->count();

            if ($processedCount % 500 === 0) {
                $this->freeMemory();
            }
        } while ($batch->count() === $this->chunkSize);

        DB::enableQueryLog();

        return $jobs;
    }

    protected function getAllJobs(): Collection
    {
        $jobs = collect();
        $with = $this->getRelationships();

        $this->getJobQuery()
            ->select(['*'])
            ->with($with)
            ->chunk($this->chunkSize, function ($collection) use (&$jobs): void {
                $jobs = $jobs->concat($this->jobResults($collection));
            });

        return $jobs;
    }

    public function jobResults(Collection $jobs): array
    {
        $results = [];

        foreach ($jobs as $job) {
            $results[] = $this->formatJobRow($job);
        }

        return $results;
    }

    public function formatJobRow(Job $job): array
    {
        return [
            ...$job->toArray(),
            'slug' => $job->slugable?->key,
            'url' => $job->url,
            'company' => $job->company?->name,
            'country' => $job->country?->name,
            'state' => $job->state?->name,
            'city' => $job->city?->name,
            'career_level' => $job->careerLevel?->name,
            'currency' => $job->currency?->title,
            'degree_level' => $job->degreeLevel?->name,
            'job_shift' => $job->jobShift?->name,
            'job_experience' => $job->jobExperience?->name,
            'functional_area' => $job->functionalArea?->name,
            'status' => $job->status->getValue(),
            'moderation_status' => $job->moderation_status->getValue(),
            'employer_colleagues' => is_array($job->employer_colleagues) ? implode(',', $job->employer_colleagues) : $job->employer_colleagues,
            'skills' => $job->skills->pluck('name')->implode(','),
            'categories' => $job->categories->pluck('name')->implode(','),
            'types' => $job->jobTypes->pluck('name')->implode(','),
            'tags' => $job->tags->pluck('name')->implode(','),
        ];
    }

    protected function getJobQuery(): Builder
    {
        $query = Job::query();
        $this->applyFilters($query);

        return $query;
    }

    public function getRelationships(): array
    {
        return [
            'country',
            'state',
            'city',
            'company',
            'careerLevel',
            'degreeLevel',
            'jobShift',
            'jobExperience',
            'functionalArea',
            'currency',
            'categories',
            'skills',
            'jobTypes',
            'tags',
            'slugable',
        ];
    }

    protected function getOptimizedRelationships(): array
    {
        if (! $this->optimizeQueries) {
            return $this->getRelationships();
        }

        return [
            'country:id,name',
            'state:id,name',
            'city:id,name',
            'company:id,name',
            'careerLevel:id,name',
            'degreeLevel:id,name',
            'jobShift:id,name',
            'jobExperience:id,name',
            'functionalArea:id,name',
            'currency:id,title',
            'categories:id,name',
            'skills:id,name',
            'jobTypes:id,name',
            'tags:id,name',
            'slugable',
        ];
    }

    protected function getSelectColumns(): array
    {
        if (! $this->optimizeQueries) {
            return ['*'];
        }

        return [
            'id',
            'unique_id',
            'name',
            'description',
            'content',
            'apply_url',
            'company_id',
            'address',
            'country_id',
            'state_id',
            'city_id',
            'is_freelance',
            'career_level_id',
            'salary_from',
            'salary_to',
            'salary_range',
            'currency_id',
            'degree_level_id',
            'job_shift_id',
            'job_experience_id',
            'functional_area_id',
            'hide_salary',
            'number_of_positions',
            'expire_date',
            'author_id',
            'author_type',
            'views',
            'number_of_applied',
            'hide_company',
            'latitude',
            'longitude',
            'auto_renew',
            'external_apply_clicks',
            'never_expired',
            'is_featured',
            'status',
            'moderation_status',
            'created_at',
            'updated_at',
            'employer_colleagues',
            'start_date',
            'application_closing_date',
            'zip_code',
        ];
    }

    protected function freeMemory(): void
    {
        if (gc_enabled()) {
            gc_collect_cycles();
        }

        DB::disconnect();
        DB::reconnect();
    }

    protected function applyFilters($query): void
    {
        if ($this->limit) {
            $query->limit($this->limit);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->isFeatured !== null) {
            $query->where('is_featured', $this->isFeatured);
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }
    }

    public function setChunkSize(int $size): self
    {
        $this->chunkSize = $size;

        return $this;
    }

    public function useChunkedExport(bool $use = true): self
    {
        $this->useChunkedExport = $use;

        return $this;
    }

    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    public function setOptimizeQueries(bool $optimize): self
    {
        $this->optimizeQueries = $optimize;

        return $this;
    }

    public function enableStreamingMode(bool $enable = true): self
    {
        $this->streamingMode = $enable;

        if ($enable) {
            $this->optimizeChunkSize();
        }

        return $this;
    }

    public function isStreamingMode(): bool
    {
        return $this->streamingMode;
    }

    public function streamingGenerator(): \Generator
    {
        $with = $this->getOptimizedRelationships();
        $lastId = 0;

        DB::disableQueryLog();
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '256M');

        do {
            $batch = $this->getJobQuery()
                ->select($this->getSelectColumns())
                ->where('id', '>', $lastId)
                ->with($with)
                ->orderBy('id')
                ->limit($this->chunkSize)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            $results = $this->jobResults($batch);
            foreach ($results as $result) {
                yield $result;
            }

            $lastId = $batch->last()->getKey();
            $this->freeMemory();
        } while ($batch->count() === $this->chunkSize);

        DB::enableQueryLog();
    }

    protected function optimizeChunkSize(): void
    {
        $query = $this->getJobQuery();
        $totalCount = $query->count();

        if ($totalCount > 50000) {
            $this->chunkSize = 100;
        } elseif ($totalCount > 30000) {
            $this->chunkSize = 150;
        } elseif ($totalCount > 20000) {
            $this->chunkSize = 200;
        } elseif ($totalCount > 10000) {
            $this->chunkSize = 250;
        } elseif ($totalCount > 5000) {
            $this->chunkSize = 300;
        } else {
            $this->chunkSize = 400;
        }
    }

    public function enableMultiFile(bool $enable = true): self
    {
        $this->useMultiFile = $enable;

        return $this;
    }

    public function isMultiFileMode(): bool
    {
        return $this->useMultiFile;
    }

    public function setRecordsPerFile(int $records): self
    {
        $this->recordsPerFile = $records;

        return $this;
    }

    public function getRecordsPerFile(): int
    {
        return $this->recordsPerFile;
    }

    public function getTotalRecords(): int
    {
        return $this->getJobQuery()->count();
    }

    public function getNumberOfFiles(): int
    {
        $total = $this->getTotalRecords();

        return (int) ceil($total / $this->recordsPerFile);
    }

    public function streamingGeneratorForFile(int $fileNumber): \Generator
    {
        $with = $this->getOptimizedRelationships();
        $offset = ($fileNumber - 1) * $this->recordsPerFile;
        $limit = $this->recordsPerFile;
        $processedInFile = 0;

        DB::disableQueryLog();
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '256M');

        $lastId = 0;
        if ($offset > 0) {
            $lastRecord = $this->getJobQuery()
                ->select(['id'])
                ->oldest('id')
                ->skip($offset - 1)
                ->first();

            if ($lastRecord) {
                $lastId = $lastRecord->id;
            }
        }

        do {
            if ($processedInFile >= $limit) {
                break;
            }

            $remainingInFile = $limit - $processedInFile;
            $batchSize = min($this->chunkSize, $remainingInFile);

            $batch = $this->getJobQuery()
                ->select($this->getSelectColumns())
                ->where('id', '>', $lastId)
                ->with($with)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            $results = $this->jobResults($batch);
            foreach ($results as $result) {
                yield $result;
                $processedInFile++;

                if ($processedInFile >= $limit) {
                    break 2;
                }
            }

            $lastId = $batch->last()->getKey();
            $this->freeMemory();
        } while ($batch->count() === $batchSize);

        DB::enableQueryLog();
    }

    public function map($row): array
    {
        if (is_array($row)) {
            $mappedData = [];
            foreach ($this->getAcceptedColumns() as $column) {
                $columnName = $column->getName();
                $mappedData[] = $row[$columnName] ?? '';
            }

            return $mappedData;
        }

        return parent::map($row);
    }

    public function counters(): array
    {
        return [
            ExportCounter::make()
                ->label(trans('plugins/job-board::export.jobs.total'))
                ->value(Job::query()->count()),
        ];
    }

    protected function getView(): string
    {
        return 'plugins/job-board::jobs.export';
    }
}
