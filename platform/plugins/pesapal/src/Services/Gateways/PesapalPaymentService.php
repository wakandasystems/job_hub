<?php

namespace Botble\Pesapal\Services\Gateways;

use Botble\Pesapal\Services\Abstracts\PesapalPaymentAbstract;
use Illuminate\Http\Request;

class PesapalPaymentService extends PesapalPaymentAbstract
{
    public function makePayment(Request $request)
    {
    }

    public function afterMakePayment(Request $request)
    {
    }

    public function supportedCurrencyCodes(): array
    {
        return [
            'ZMW', // Zambian Kwacha
            'KES', // Kenyan Shilling
            'UGX', // Ugandan Shilling
            'TZS', // Tanzanian Shilling
            'MWK', // Malawian Kwacha
            'RWF', // Rwandan Franc
            'ZAR', // South African Rand
            'USD',
            'GBP',
            'EUR',
        ];
    }
}
