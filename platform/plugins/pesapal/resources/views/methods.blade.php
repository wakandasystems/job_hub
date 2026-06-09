@if (get_payment_setting('status', PESAPAL_PAYMENT_METHOD_NAME) == 1)
    <x-plugins-payment::payment-method
        :name="PESAPAL_PAYMENT_METHOD_NAME"
        paymentName="Pesapal"
        :supportedCurrencies="(new Botble\Pesapal\Services\Gateways\PesapalPaymentService)->supportedCurrencyCodes()"
    >
        <x-slot name="currencyNotSupportedMessage">
            <p class="mt-1 mb-0">
                {{ trans('plugins/pesapal::pesapal.currency_not_supported_note') }}
                {{ Html::link('https://www.pesapal.com', 'Pesapal', ['target' => '_blank', 'rel' => 'nofollow']) }}.
            </p>
        </x-slot>
    </x-plugins-payment::payment-method>
@endif
