<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Facades\Assets;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Forms\JobCrawlerForm;
use Botble\JobBoard\Http\Requests\JobCrawlerRequest;
use Botble\JobBoard\Models\JobCrawler;
use Botble\JobBoard\Models\JobCrawlerRun;
use Botble\JobBoard\Tables\JobCrawlerTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use JsonException;

class JobCrawlerController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/job-board::job-board.name'))
            ->add('Agents', route('job-board.crawlers.index'));
    }

    public function index(JobCrawlerTable $table)
    {
        $this->pageTitle('Agents');

        Assets::addScriptsDirectly('vendor/core/plugins/job-board/js/crawler-progress.js');

        return $table->renderTable();
    }

    public function create()
    {
        $this->pageTitle('Create agent');

        return JobCrawlerForm::create()->renderForm();
    }

    public function store(JobCrawlerRequest $request)
    {
        $crawler = JobCrawler::query()->create($this->prepareInput($request));

        event(new CreatedContentEvent('job-crawler', $request, $crawler));

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('job-board.crawlers.index'))
            ->setNextUrl(route('job-board.crawlers.edit', $crawler->id))
            ->withCreatedSuccessMessage();
    }

    public function edit(JobCrawler $crawler, Request $request)
    {
        event(new BeforeEditContentEvent($request, $crawler));

        $this->pageTitle('Edit agent: ' . $crawler->name);

        return JobCrawlerForm::createFromModel($crawler)->renderForm();
    }

    public function update(JobCrawler $crawler, JobCrawlerRequest $request)
    {
        $crawler->fill($this->prepareInput($request))->save();

        event(new UpdatedContentEvent('job-crawler', $request, $crawler));

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('job-board.crawlers.index'))
            ->withUpdatedSuccessMessage();
    }

    public function run(JobCrawler $crawler)
    {
        $run = JobCrawlerRun::query()->create([
            'crawler_id' => $crawler->getKey(),
            'status' => 'running',
            'started_at' => Carbon::now(),
            'meta' => ['current_page' => 0, 'total_pages' => 20, 'jobs_found_so_far' => 0],
        ]);

        $php = PHP_BINARY;
        if (str_contains($php, 'fpm') || ! is_executable($php)) {
            $php = '/usr/bin/php';
        }
        $artisan = base_path('artisan');
        \exec(sprintf('%s %s job-board:crawl-run %d > /dev/null 2>&1 &', escapeshellcmd($php), escapeshellarg($artisan), $run->id));

        return $this->httpResponse()
            ->setData([
                'run_id' => $run->id,
                'status_url' => route('job-board.crawlers.run-status', ['crawler' => $crawler->id, 'run' => $run->id]),
                'run_url' => route('job-board.crawler-runs.show', $run->id),
            ])
            ->setMessage('Crawl started — monitoring progress…');
    }

    public function runStatus(JobCrawler $crawler, JobCrawlerRun $run)
    {
        $run->refresh();
        $meta = $run->meta ?? [];

        return $this->httpResponse()->setData([
            'status' => $run->status,
            'current_page' => $meta['current_page'] ?? 0,
            'total_pages' => $meta['total_pages'] ?? 20,
            'jobs_found_so_far' => $meta['jobs_found_so_far'] ?? 0,
            'jobs_created' => $run->jobs_created,
            'jobs_updated' => $run->jobs_updated,
            'jobs_skipped' => $run->jobs_skipped,
            'error_message' => $run->error_message,
            'run_url' => route('job-board.crawler-runs.show', $run->id),
        ]);
    }

    public function destroy(JobCrawler $crawler)
    {
        return DeleteResourceAction::make($crawler);
    }

    protected function prepareInput(JobCrawlerRequest $request): array
    {
        $input = $request->input();
        $input['is_active'] = $request->boolean('is_active');
        $input['field_mappings'] = null;

        $mappings = trim((string) $request->input('field_mappings'));
        if ($mappings !== '') {
            try {
                $input['field_mappings'] = json_decode($mappings, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                abort(422, 'Extra field mappings must be valid JSON.');
            }
        }

        return $input;
    }
}
