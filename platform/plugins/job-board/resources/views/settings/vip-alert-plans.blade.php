@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<form method="POST" action="{{ route('job-board.settings.vip-alert-plans.update') }}">
    @csrf
    @method('PUT')

    <x-core::card>
        <x-core::card.header>
            <div class="d-flex align-items-center justify-content-between w-100">
                <x-core::card.title>VIP Alert Plans</x-core::card.title>
                <a href="{{ route('public.vip-alerts.plans') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-external-link me-1"></i> View Public Page
                </a>
            </div>
        </x-core::card.header>
        <x-core::card.body>
            <div class="alert alert-info">
                Changes apply to new orders only. Existing orders keep the amount and duration purchased.
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Enabled</th>
                            <th>Plan</th>
                            <th style="width:130px">Duration (days)</th>
                            <th style="width:130px">Amount</th>
                            <th style="width:110px">Currency</th>
                            <th>Badge</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $key => $plan)
                            <tr>
                                <td>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="plans[{{ $key }}][enabled]" value="0">
                                        <input class="form-check-input" type="checkbox"
                                            name="plans[{{ $key }}][enabled]" value="1"
                                            {{ old("plans.{$key}.enabled", $plan['enabled']) ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control" required
                                        name="plans[{{ $key }}][label]"
                                        value="{{ old("plans.{$key}.label", $plan['label']) }}">
                                    <div class="form-text"><code>{{ $key }}</code></div>
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="1" max="3650" required
                                        name="plans[{{ $key }}][duration_days]"
                                        value="{{ old("plans.{$key}.duration_days", $plan['duration_days']) }}">
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="0" step="0.01" required
                                        name="plans[{{ $key }}][price]"
                                        value="{{ old("plans.{$key}.price", $plan['price']) }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control text-uppercase" maxlength="3" required
                                        name="plans[{{ $key }}][currency]"
                                        value="{{ old("plans.{$key}.currency", $plan['currency']) }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control"
                                        name="plans[{{ $key }}][badge]"
                                        placeholder="e.g. Most Popular"
                                        value="{{ old("plans.{$key}.badge", $plan['badge']) }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-core::card.body>
        <x-core::card.footer>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i> Save Plans
            </button>
        </x-core::card.footer>
    </x-core::card>
</form>
@endsection
