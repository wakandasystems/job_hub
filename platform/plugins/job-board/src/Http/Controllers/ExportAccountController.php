<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\DataSynchronize\Exporter\Exporter;
use Botble\DataSynchronize\Http\Controllers\ExportController;
use Botble\DataSynchronize\Http\Requests\ExportRequest;
use Botble\JobBoard\Exporters\AccountExporter;

class ExportAccountController extends ExportController
{
    protected function getExporter(): Exporter
    {
        $exporter = AccountExporter::make();

        if (request()->has('limit')) {
            $exporter->setLimit((int) request()->input('limit'));
        }

        return $exporter;
    }

    public function store(ExportRequest $request)
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);

        return parent::store($request);
    }
}
