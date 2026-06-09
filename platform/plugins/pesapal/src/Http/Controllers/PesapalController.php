<?php

namespace Botble\Pesapal\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Pesapal\Services\PesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PesapalController extends BaseController
{
    public function callback(Request $request, BaseHttpResponse $response)
    {
        $orderTrackingId = $request->query('OrderTrackingId');
        $merchantReference = $request->query('OrderMerchantReference');

        $returnUrl = session()->pull('pesapal_return_url', url('/'));
        $callbackUrl = session()->pull('pesapal_callback_url');
        $orderId = session()->pull('pesapal_order_id');
        $customerId = session()->pull('pesapal_customer_id');
        $customerType = session()->pull('pesapal_customer_type');

        if (! $orderTrackingId) {
            return $response
                ->setError()
                ->setNextUrl($returnUrl)
                ->setMessage('Missing order tracking ID from Pesapal callback');
        }

        try {
            $pesapal = new PesapalService();
            $status = $pesapal->getTransactionStatus($orderTrackingId);

            do_action('payment_after_api_response', PESAPAL_PAYMENT_METHOD_NAME, ['orderTrackingId' => $orderTrackingId], $status);

            if (! $pesapal->isPaymentCompleted($status)) {
                $statusDesc = Arr::get($status, 'payment_status_description', 'Payment not completed');

                return $response
                    ->setError()
                    ->setNextUrl($returnUrl)
                    ->setMessage($statusDesc);
            }

            do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                'amount' => Arr::get($status, 'amount'),
                'currency' => Arr::get($status, 'currency'),
                'charge_id' => $orderTrackingId,
                'payment_channel' => PESAPAL_PAYMENT_METHOD_NAME,
                'status' => PaymentStatusEnum::COMPLETED,
                'customer_id' => $customerId,
                'customer_type' => $customerType,
                'payment_type' => 'direct',
                'order_id' => (array) $orderId,
            ], $request);

            $params = ['charge_id' => $orderTrackingId];

            return redirect()->to($callbackUrl . '?' . http_build_query($params));
        } catch (\Throwable $exception) {
            BaseHelper::logError($exception);

            return $response
                ->setError()
                ->setNextUrl($returnUrl)
                ->setMessage($exception->getMessage());
        }
    }

    public function ipn(Request $request)
    {
        // Pesapal sends IPN notification; we just acknowledge it.
        // Actual status is checked on the callback redirect.
        return response()->json(['status' => 200]);
    }
}
