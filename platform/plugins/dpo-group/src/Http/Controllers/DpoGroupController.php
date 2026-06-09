<?php

namespace Botble\DpoGroup\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\DpoGroup\Services\DpoGroupService;
use Botble\Payment\Enums\PaymentStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class DpoGroupController extends BaseController
{
    public function callback(Request $request, BaseHttpResponse $response)
    {
        $transToken = $request->query('TransactionToken');
        $companyRef = $request->query('CompanyRef');

        $returnUrl = session()->pull('dpo_group_return_url');
        $callbackUrl = session()->pull('dpo_group_callback_url');
        $orderId = session()->pull('dpo_group_order_id');
        $customerId = session()->pull('dpo_group_customer_id');
        $customerType = session()->pull('dpo_group_customer_type');

        if (! $transToken) {
            return $response
                ->setError()
                ->setNextUrl($returnUrl ?: url('/'))
                ->setMessage('Missing transaction token from DPO Group callback');
        }

        try {
            $dpo = new DpoGroupService();
            $result = $dpo->verifyToken($transToken);
            $resultCode = Arr::get($result, 'Result', '999');

            do_action('payment_after_api_response', DPO_GROUP_PAYMENT_METHOD_NAME, ['token' => $transToken], $result);

            if (! $dpo->isPaidResult($resultCode)) {
                return $response
                    ->setError()
                    ->setNextUrl($returnUrl ?: url('/'))
                    ->setMessage('Payment was not completed. Status: ' . Arr::get($result, 'ResultExplanation', 'Unknown'));
            }

            $amount = (float) Arr::get($result, 'TransactionAmount', 0);
            $currency = Arr::get($result, 'TransactionCurrency', '');

            do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                'amount' => $amount,
                'currency' => $currency,
                'charge_id' => $transToken,
                'payment_channel' => DPO_GROUP_PAYMENT_METHOD_NAME,
                'status' => PaymentStatusEnum::COMPLETED,
                'customer_id' => $customerId,
                'customer_type' => $customerType,
                'payment_type' => 'direct',
                'order_id' => (array) $orderId,
            ], $request);

            $params = ['charge_id' => $transToken];

            return redirect()->to($callbackUrl . '?' . http_build_query($params));
        } catch (\Throwable $exception) {
            BaseHelper::logError($exception);

            return $response
                ->setError()
                ->setNextUrl($returnUrl ?: url('/'))
                ->setMessage($exception->getMessage());
        }
    }

    public function cancel(Request $request, BaseHttpResponse $response)
    {
        $returnUrl = session()->pull('dpo_group_return_url', url('/'));

        return $response
            ->setError()
            ->setNextUrl($returnUrl)
            ->setMessage('Payment was cancelled.');
    }
}
