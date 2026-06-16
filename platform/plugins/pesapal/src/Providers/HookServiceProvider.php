<?php

namespace Botble\Pesapal\Providers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Facades\PaymentMethods;
use Botble\Pesapal\Forms\PesapalPaymentMethodForm;
use Botble\Pesapal\Services\Gateways\PesapalPaymentService;
use Botble\Pesapal\Services\PesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Throwable;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, [$this, 'registerPesapalMethod'], 16, 2);
        $this->app->booted(function (): void {
            add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, [$this, 'checkoutWithPesapal'], 16, 2);
        });

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, [$this, 'addPaymentSettings'], 97);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class == PaymentMethodEnum::class) {
                $values['PESAPAL'] = PESAPAL_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 21, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == PESAPAL_PAYMENT_METHOD_NAME) {
                $value = 'Pesapal';
            }

            return $value;
        }, 21, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == PESAPAL_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )->toHtml();
            }

            return $value;
        }, 21, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == PESAPAL_PAYMENT_METHOD_NAME) {
                $data = PesapalPaymentService::class;
            }

            return $data;
        }, 20, 2);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
            if ($payment->payment_channel == PESAPAL_PAYMENT_METHOD_NAME) {
                $data .= view('plugins/pesapal::detail', ['payment' => $payment])->render();
            }

            return $data;
        }, 20, 2);
    }

    public function addPaymentSettings(?string $settings): string
    {
        return $settings . PesapalPaymentMethodForm::create()->renderForm();
    }

    public function registerPesapalMethod(?string $html, array $data): string
    {
        PaymentMethods::method(PESAPAL_PAYMENT_METHOD_NAME, [
            'html' => view('plugins/pesapal::methods', $data)->render(),
        ]);

        return $html;
    }

    public function checkoutWithPesapal(array $data, Request $request): array
    {
        if ($data['type'] !== PESAPAL_PAYMENT_METHOD_NAME) {
            return $data;
        }

        $supportedCurrencies = (new PesapalPaymentService())->supportedCurrencyCodes();
        $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

        if (! in_array($paymentData['currency'], $supportedCurrencies)) {
            $data['error'] = true;
            $data['message'] = trans('plugins/payment::payment.currency_not_supported', [
                'name' => 'Pesapal',
                'currency' => $paymentData['currency'],
                'currencies' => implode(', ', $supportedCurrencies),
            ]);

            return $data;
        }

        try {
            $orderIds = (array) $paymentData['order_id'];
            $orderId = Arr::first($orderIds);
            $merchantReference = 'WKJ-' . $orderId . '-' . time();

            session([
                'pesapal_return_url' => $request->input('return_url'),
                'pesapal_callback_url' => $request->input('callback_url'),
                'pesapal_order_id' => $orderId,
                'pesapal_customer_id' => $paymentData['customer_id'],
                'pesapal_customer_type' => $paymentData['customer_type'],
            ]);

            $nameParts = explode(' ', $paymentData['name'] ?? '', 2);

            $orderData = [
                'merchant_reference' => $merchantReference,
                'currency' => $paymentData['currency'],
                'amount' => $paymentData['amount'],
                'description' => 'Wakanda Jobs Payment #' . $orderId,
                'callback_url' => route('pesapal.payment.callback'),
                'email' => $paymentData['email'] ?? '',
                'phone' => $paymentData['phone'] ?? '',
                'first_name' => $nameParts[0] ?? '',
                'last_name' => $nameParts[1] ?? '',
            ];

            $pesapal = new PesapalService();

            do_action('payment_before_making_api_request', PESAPAL_PAYMENT_METHOD_NAME, $orderData);

            $result = $pesapal->submitOrder($orderData);

            do_action('payment_after_api_response', PESAPAL_PAYMENT_METHOD_NAME, $orderData, $result);

            $redirectUrl = Arr::get($result, 'redirect_url');
            if (! $redirectUrl) {
                $data['error'] = true;
                $data['message'] = trans('plugins/pesapal::pesapal.payment_failed');

                return $data;
            }

            header('Location: ' . $redirectUrl);
            exit;
        } catch (Throwable $exception) {
            $data['error'] = true;
            $data['message'] = $exception->getMessage();

            BaseHelper::logError($exception);
        }

        return $data;
    }
}
