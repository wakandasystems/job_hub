<?php

namespace Botble\DpoGroup\Providers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\DpoGroup\Forms\DpoGroupPaymentMethodForm;
use Botble\DpoGroup\Services\DpoGroupService;
use Botble\DpoGroup\Services\Gateways\DpoGroupPaymentService;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Facades\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Throwable;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, [$this, 'registerDpoGroupMethod'], 16, 2);
        $this->app->booted(function (): void {
            add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, [$this, 'checkoutWithDpoGroup'], 16, 2);
        });

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, [$this, 'addPaymentSettings'], 97);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class == PaymentMethodEnum::class) {
                $values['DPO_GROUP'] = DPO_GROUP_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 21, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == DPO_GROUP_PAYMENT_METHOD_NAME) {
                $value = 'DPO Group';
            }

            return $value;
        }, 21, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == DPO_GROUP_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )->toHtml();
            }

            return $value;
        }, 21, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == DPO_GROUP_PAYMENT_METHOD_NAME) {
                $data = DpoGroupPaymentService::class;
            }

            return $data;
        }, 20, 2);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
            if ($payment->payment_channel == DPO_GROUP_PAYMENT_METHOD_NAME) {
                $data .= view('plugins/dpo-group::detail', ['payment' => $payment])->render();
            }

            return $data;
        }, 20, 2);
    }

    public function addPaymentSettings(?string $settings): string
    {
        return $settings . DpoGroupPaymentMethodForm::create()->renderForm();
    }

    public function registerDpoGroupMethod(?string $html, array $data): string
    {
        PaymentMethods::method(DPO_GROUP_PAYMENT_METHOD_NAME, [
            'html' => view('plugins/dpo-group::methods', $data)->render(),
        ]);

        return $html;
    }

    public function checkoutWithDpoGroup(array $data, Request $request): array
    {
        if ($data['type'] !== DPO_GROUP_PAYMENT_METHOD_NAME) {
            return $data;
        }

        $supportedCurrencies = (new DpoGroupPaymentService())->supportedCurrencyCodes();
        $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

        if (! in_array($paymentData['currency'], $supportedCurrencies)) {
            $data['error'] = true;
            $data['message'] = trans('plugins/payment::payment.currency_not_supported', [
                'name' => 'DPO Group',
                'currency' => $paymentData['currency'],
                'currencies' => implode(', ', $supportedCurrencies),
            ]);

            return $data;
        }

        try {
            $orderIds = (array) $paymentData['order_id'];
            $orderId = Arr::first($orderIds);
            $companyRef = 'WKJ-' . $orderId . '-' . time();

            session([
                'dpo_group_return_url' => $request->input('return_url'),
                'dpo_group_callback_url' => $request->input('callback_url'),
                'dpo_group_order_id' => $orderId,
                'dpo_group_customer_id' => $paymentData['customer_id'],
                'dpo_group_customer_type' => $paymentData['customer_type'],
            ]);

            $dpo = new DpoGroupService();
            $tokenData = [
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'company_ref' => $companyRef,
                'redirect_url' => route('dpo-group.payment.callback'),
                'back_url' => route('dpo-group.payment.cancel'),
                'description' => 'Wakanda Jobs Payment #' . $orderId,
            ];

            do_action('payment_before_making_api_request', DPO_GROUP_PAYMENT_METHOD_NAME, $tokenData);

            $result = $dpo->createToken($tokenData);

            do_action('payment_after_api_response', DPO_GROUP_PAYMENT_METHOD_NAME, $tokenData, $result);

            $resultCode = Arr::get($result, 'Result', '999');

            if (! $dpo->isSuccessResult($resultCode)) {
                $data['error'] = true;
                $data['message'] = Arr::get($result, 'ResultExplanation', trans('plugins/dpo-group::dpo-group.payment_failed'));

                return $data;
            }

            $transToken = Arr::get($result, 'TransToken');
            $paymentUrl = $dpo->getPaymentUrl($transToken);

            header('Location: ' . $paymentUrl);
            exit;
        } catch (Throwable $exception) {
            $data['error'] = true;
            $data['message'] = $exception->getMessage();

            BaseHelper::logError($exception);
        }

        return $data;
    }
}
