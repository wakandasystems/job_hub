<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\DataSynchronize\Http\Controllers\ImportController;
use Botble\DataSynchronize\Importer\Importer;
use Botble\JobBoard\Importers\AccountImporter;

class ImportAccountController extends ImportController
{
    protected function getImporter(): Importer
    {
        return AccountImporter::make();
    }
}
