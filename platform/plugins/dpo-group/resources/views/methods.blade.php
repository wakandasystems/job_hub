@if (get_payment_setting('status', DPO_GROUP_PAYMENT_METHOD_NAME) == 1)
    <x-plugins-payment::payment-method
        :name="DPO_GROUP_PAYMENT_METHOD_NAME"
        paymentName="DPO Group"
        :supportedCurrencies="(new Botble\DpoGroup\Services\Gateways\DpoGroupPaymentService)->supportedCurrencyCodes()"
    >
        <x-slot name="currencyNotSupportedMessage">
            <p class="mt-1 mb-0">
                {{ trans('plugins/dpo-group::dpo-group.currency_not_supported_note') }}
                {{ Html::link('https://www.directpay.online', 'DPO Group', ['target' => '_blank', 'rel' => 'nofollow']) }}.
            </p>
        </x-slot>
    </x-plugins-payment::payment-method>
@endif
