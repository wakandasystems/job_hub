<?php

namespace Botble\Pesapal;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Facades\Setting;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Setting::delete([
            'payment_pesapal_name',
            'payment_pesapal_description',
            'payment_pesapal_consumer_key',
            'payment_pesapal_consumer_secret',
            'payment_pesapal_ipn_id',
            'payment_pesapal_status',
            'payment_pesapal_mode',
        ]);
    }
}
