<?php

namespace Botble\Pesapal\Forms;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Payment\Concerns\Forms\HasAvailableCountriesField;
use Botble\Payment\Forms\PaymentMethodForm;

class PesapalPaymentMethodForm extends PaymentMethodForm
{
    use HasAvailableCountriesField;

    public function setup(): void
    {
        parent::setup();

        $this
            ->paymentId(PESAPAL_PAYMENT_METHOD_NAME)
            ->paymentName('Pesapal')
            ->paymentDescription(trans('plugins/pesapal::pesapal.payment_description'))
            ->paymentLogo(url('vendor/core/plugins/pesapal/images/pesapal.svg'))
            ->paymentFeeField(PESAPAL_PAYMENT_METHOD_NAME)
            ->paymentUrl('https://www.pesapal.com')
            ->paymentInstructions(view('plugins/pesapal::instructions')->render())
            ->add(
                sprintf('payment_%s_consumer_key', PESAPAL_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/pesapal::pesapal.consumer_key'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '****' : get_payment_setting('consumer_key', PESAPAL_PAYMENT_METHOD_NAME))
            )
            ->add(
                sprintf('payment_%s_consumer_secret', PESAPAL_PAYMENT_METHOD_NAME),
                'password',
                TextFieldOption::make()
                    ->label(trans('plugins/pesapal::pesapal.consumer_secret'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '****' : get_payment_setting('consumer_secret', PESAPAL_PAYMENT_METHOD_NAME))
            )
            ->add(
                sprintf('payment_%s_mode', PESAPAL_PAYMENT_METHOD_NAME),
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('plugins/pesapal::pesapal.mode'))
                    ->choices([
                        'live' => trans('plugins/pesapal::pesapal.live'),
                        'sandbox' => trans('plugins/pesapal::pesapal.sandbox'),
                    ])
                    ->selected(get_payment_setting('mode', PESAPAL_PAYMENT_METHOD_NAME, 'live'))
            )
            ->addAvailableCountriesField(PESAPAL_PAYMENT_METHOD_NAME);
    }
}
