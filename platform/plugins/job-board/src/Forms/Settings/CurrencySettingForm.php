<?php

namespace Botble\JobBoard\Forms\Settings;

use Botble\Base\Facades\Assets;
use Botble\JobBoard\Http\Requests\Settings\CurrencySettingRequest;
use Botble\JobBoard\Models\Currency;
use Botble\Setting\Forms\SettingForm;

class CurrencySettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        Assets::addScripts(['jquery-ui'])
            ->addScriptsDirectly('vendor/core/plugins/job-board/js/currencies.js')
            ->addStylesDirectly('vendor/core/plugins/job-board/css/currencies.css');

        $currencies = Currency::query()
            ->oldest('order')
            ->get();

        $this
            ->setSectionTitle(trans('plugins/job-board::settings.currency.title'))
            ->setSectionDescription(trans('plugins/job-board::settings.currency.description'))
            ->setFormOptions([
                'class' => 'currency-setting-form',
            ])
            ->contentOnly()
            ->setValidatorClass(CurrencySettingRequest::class)
            ->add('job_board_enable_auto_detect_visitor_currency', 'onOffCheckbox', [
                'label' => trans('plugins/job-board::settings.currency.enable_auto_detect_visitor_currency'),
                'value' => setting('job_board_enable_auto_detect_visitor_currency', false),
                'help_block' => [
                    'text' => trans('plugins/job-board::settings.currency.auto_detect_visitor_currency_description'),
                ],
            ])
            ->add('job_board_add_space_between_price_and_currency', 'onOffCheckbox', [
                'label' => trans('plugins/job-board::settings.currency.add_space_between_price_and_currency'),
                'value' => setting('job_board_add_space_between_price_and_currency', false),
                'help_block' => [
                    'text' => trans('plugins/job-board::settings.currency.add_space_between_price_and_currency_helper'),
                ],
            ])
            ->add('job_board_thousands_separator', 'customSelect', [
                'label' => trans('plugins/job-board::settings.currency.thousands_separator'),
                'selected' => setting('job_board_thousands_separator', ','),
                'choices' => [
                    ',' => trans('plugins/job-board::settings.currency.separator_comma'),
                    '.' => trans('plugins/job-board::settings.currency.separator_period'),
                    'space' => trans('plugins/job-board::settings.currency.separator_space'),
                ],
                'help_block' => [
                    'text' => trans('plugins/job-board::settings.currency.thousands_separator_helper'),
                ],
            ])
            ->add('job_board_decimal_separator', 'customSelect', [
                'label' => trans('plugins/job-board::settings.currency.decimal_separator'),
                'selected' => setting('job_board_decimal_separator', '.'),
                'choices' => [
                    ',' => trans('plugins/job-board::settings.currency.separator_comma'),
                    '.' => trans('plugins/job-board::settings.currency.separator_period'),
                    'space' => trans('plugins/job-board::settings.currency.separator_space'),
                ],
                'help_block' => [
                    'text' => trans('plugins/job-board::settings.currency.decimal_separator_helper'),
                ],
            ])
            ->add('data_currencies', 'html', [
                'html' => view(
                    'plugins/job-board::settings.partials.data-currency-fields',
                    compact('currencies')
                ),
            ])
            ->add('currency_table', 'html', [
                'html' => view('plugins/job-board::settings.partials.currency-table'),
            ]);
    }
}
