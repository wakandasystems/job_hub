<?php

namespace Botble\DpoGroup;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Facades\Setting;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Setting::delete([
            'payment_dpo-group_name',
            'payment_dpo-group_description',
            'payment_dpo-group_company_token',
            'payment_dpo-group_service_type',
            'payment_dpo-group_status',
            'payment_dpo-group_mode',
        ]);
    }
}
