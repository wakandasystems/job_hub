<?php

namespace Botble\DpoGroup\Services\Gateways;

use Botble\DpoGroup\Services\Abstracts\DpoGroupPaymentAbstract;
use Illuminate\Http\Request;

class DpoGroupPaymentService extends DpoGroupPaymentAbstract
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
            'USD',
            'ZAR', // South African Rand
            'KES', // Kenyan Shilling
            'TZS', // Tanzanian Shilling
            'UGX', // Ugandan Shilling
            'GHS', // Ghanaian Cedi
            'MWK', // Malawian Kwacha
            'NGN', // Nigerian Naira
            'RWF', // Rwandan Franc
            'ETB', // Ethiopian Birr
            'BWP', // Botswana Pula
            'MZN', // Mozambican Metical
            'EUR',
            'GBP',
        ];
    }
}
