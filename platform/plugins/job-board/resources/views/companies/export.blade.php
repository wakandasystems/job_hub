@extends('packages/data-synchronize::export')

@section('export_extra_filters_after')
    @php
        use Botble\Base\Enums\BaseStatusEnum;

        $statuses = BaseStatusEnum::labels();
    @endphp

    <div class="row mb-3">
        <div class="col-md-3">
            <x-core::form.text-input
                name="limit"
                type="number"
                :label="trans('plugins/job-board::export.companies.limit')"
                :placeholder="trans('plugins/job-board::export.companies.limit_placeholder')"
                min="1"
            />
        </div>
        <div class="col-md-3">
            <x-core::form.select
                name="status"
                :label="trans('core/base::forms.status')"
                :options="['' => trans('plugins/job-board::export.companies.all_status')] + $statuses"
            />
        </div>
    </div>
@stop
