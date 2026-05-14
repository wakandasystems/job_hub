<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\DataSynchronize\Http\Controllers\ImportController;
use Botble\DataSynchronize\Importer\Importer;
use Botble\JobBoard\Importers\JobImporter;

class ImportJobController extends ImportController
{
    protected function getImporter(): Importer
    {
        return JobImporter::make();
    }
}
