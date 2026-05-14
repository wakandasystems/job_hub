<div class="swatches-container">
    <div class="header">
        <div class="swatch-item">
            {{ trans('plugins/job-board::settings.currency.code') }}
        </div>
        <div class="swatch-item">
            {{ trans('plugins/job-board::settings.currency.symbol') }}
        </div>
        <div class="swatch-item swatch-exchange-rate">
            {{ trans('plugins/job-board::settings.currency.exchange_rate') }}
        </div>
        <div class="swatch-is-default">
            {{ trans('plugins/job-board::settings.currency.is_default') }}
        </div>
        <div class="swatch-advanced">
            {{ trans('plugins/job-board::settings.currency.advanced') }}
        </div>
        <div class="remove-item">{{ trans('plugins/job-board::settings.currency.remove') }}</div>
    </div>

    <ul class="swatches-list"></ul>

    <div class="d-flex justify-content-between w-100 align-items-center">
        <x-core::form.helper-text>
            {{ trans('plugins/job-board::settings.currency.instruction') }}
        </x-core::form.helper-text>

        <a class="js-add-new-attribute" href="javascript:void(0)">
            {{ trans('plugins/job-board::settings.currency.new_currency') }}
        </a>
    </div>
</div>
