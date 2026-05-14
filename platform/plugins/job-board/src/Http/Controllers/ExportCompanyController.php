<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\DataSynchronize\Exporter\Exporter;
use Botble\DataSynchronize\Http\Controllers\ExportController;
use Botble\DataSynchronize\Http\Requests\ExportRequest;
use Botble\JobBoard\Exporters\CompanyExporter;

class ExportCompanyController extends ExportController
{
    protected function getExporter(): Exporter
    {
        $exporter = CompanyExporter::make();

        if (request()->has('limit')) {
            $exporter->setLimit((int) request()->input('limit'));
        }

        if (request()->has('status') && request()->input('status') !== '') {
            $exporter->setStatus(request()->input('status'));
        }

        if (request()->has('is_featured') && request()->input('is_featured') !== '') {
            $exporter->setIsFeatured((bool) request()->input('is_featured'));
        }

        if (request()->has('is_verified') && request()->input('is_verified') !== '') {
            $exporter->setIsVerified((bool) request()->input('is_verified'));
        }

        return $exporter;
    }

    public function store(ExportRequest $request)
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:' . implode(',', BaseStatusEnum::values())],
            'is_featured' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
        ]);

        return parent::store($request);
    }
}
