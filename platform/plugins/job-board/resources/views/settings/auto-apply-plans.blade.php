@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<form method="POST" action="{{ route('job-board.settings.auto-apply-plans.update') }}">
    @csrf
    @method('PUT')

    <x-core::card>
        <x-core::card.header>
            <div class="d-flex align-items-center justify-content-between w-100">
                <x-core::card.title>Auto Apply Plans</x-core::card.title>
                <a href="{{ route('public.auto-apply.plans') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-external-link me-1"></i> View Public Page
                </a>
            </div>
        </x-core::card.header>
        <x-core::card.body>
            <div class="alert alert-info">
                Configure Auto Apply subscription plans. When candidates subscribe, the system automatically sends AI-crafted application emails to matching jobs on their behalf. Changes apply to new orders only.
            </div>

            {{-- Global AI Settings --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Default AI Model</label>
                    <select name="ai_model" class="form-select">
                        <option value="gpt-4o-mini" {{ $aiModel === 'gpt-4o-mini' ? 'selected' : '' }}>
                            GPT-4o Mini (~$0.003/email — faster, cheaper)
                        </option>
                        <option value="gpt-4o" {{ $aiModel === 'gpt-4o' ? 'selected' : '' }}>
                            GPT-4o (~$0.03/email — higher quality)
                        </option>
                    </select>
                    <div class="form-text">Used for drafting application emails. Can preview both models before choosing.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Default Match Threshold</label>
                    <div class="input-group">
                        <input type="number" name="match_threshold" class="form-control"
                               min="0" max="100" value="{{ $matchThreshold }}">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Jobs scoring below this threshold won't get auto-applied to. Candidates can override.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">OpenAI API Key</label>
                    <input type="password" class="form-control" disabled
                           value="{{ setting('openai_api_key') ? '********' : '' }}"
                           placeholder="{{ setting('openai_api_key') ? 'Configured' : 'Not set — add OPENAI_API_KEY to .env or openai_api_key in settings' }}">
                    <div class="form-text">Set via <code>.env</code> (<code>OPENAI_API_KEY</code>) or site settings (<code>openai_api_key</code>).</div>
                </div>
            </div>

            <hr>

            {{-- Plan Tiers --}}
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Enabled</th>
                            <th>Plan</th>
                            <th style="width:120px">Duration (days)</th>
                            <th style="width:130px">Usage Limit</th>
                            <th style="width:120px">Amount</th>
                            <th style="width:100px">Currency</th>
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
                                    <input type="number" class="form-control" min="0" required
                                        name="plans[{{ $key }}][applications_per_month]"
                                        value="{{ old("plans.{$key}.applications_per_month", $plan['applications_per_month']) }}">
                                    <div class="form-text">For plans under 30 days: total for the plan. For 30+ day plans: per 30-day cycle. 0 = unlimited.</div>
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
