<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\JobCrawlerRun;
use Botble\JobBoard\Tables\JobCrawlerRunTable;

class JobCrawlerRunController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/job-board::job-board.name'))
            ->add('Agent Runs', route('job-board.crawler-runs.index'));
    }

    public function index(JobCrawlerRunTable $table)
    {
        $this->pageTitle('Agent Runs');

        return $table->renderTable();
    }

    public function show(JobCrawlerRun $run)
    {
        $this->pageTitle('Agent Run #' . $run->id);

        return view('plugins/job-board::crawlers.run', compact('run'));
    }
}
