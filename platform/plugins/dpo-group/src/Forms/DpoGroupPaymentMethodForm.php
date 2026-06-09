<?php

namespace Botble\DpoGroup\Forms;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Payment\Concerns\Forms\HasAvailableCountriesField;
use Botble\Payment\Forms\PaymentMethodForm;

class DpoGroupPaymentMethodForm extends PaymentMethodForm
{
    use HasAvailableCountriesField;

    public function setup(): void
    {
        parent::setup();

        $this
            ->paymentId(DPO_GROUP_PAYMENT_METHOD_NAME)
            ->paymentName('DPO Group')
            ->paymentDescription(trans('plugins/dpo-group::dpo-group.payment_description'))
            ->paymentLogo(url('vendor/core/plugins/dpo-group/images/dpo-group.svg'))
            ->paymentFeeField(DPO_GROUP_PAYMENT_METHOD_NAME)
            ->paymentUrl('https://www.directpay.online')
            ->paymentInstructions(view('plugins/dpo-group::instructions')->render())
            ->add(
                sprintf('payment_%s_company_token', DPO_GROUP_PAYMENT_METHOD_NAME),
                'password',
                TextFieldOption::make()
                    ->label(trans('plugins/dpo-group::dpo-group.company_token'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '****' : get_payment_setting('company_token', DPO_GROUP_PAYMENT_METHOD_NAME))
            )
            ->add(
                sprintf('payment_%s_service_type', DPO_GROUP_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/dpo-group::dpo-group.service_type'))
                    ->helperText(trans('plugins/dpo-group::dpo-group.service_type_help'))
                    ->value(get_payment_setting('service_type', DPO_GROUP_PAYMENT_METHOD_NAME, '5525'))
            )
            ->add(
                sprintf('payment_%s_mode', DPO_GROUP_PAYMENT_METHOD_NAME),
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('plugins/dpo-group::dpo-group.mode'))
                    ->choices([
                        'live' => trans('plugins/dpo-group::dpo-group.live'),
                        'sandbox' => trans('plugins/dpo-group::dpo-group.sandbox'),
                    ])
                    ->selected(get_payment_setting('mode', DPO_GROUP_PAYMENT_METHOD_NAME, 'live'))
            )
            ->addAvailableCountriesField(DPO_GROUP_PAYMENT_METHOD_NAME);
    }
}
