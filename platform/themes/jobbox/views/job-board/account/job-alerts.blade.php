@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
<div>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h3 class="mt-0 mb-0 color-brand-1">{{ __('Job Alerts') }}</h3>
        <span class="badge bg-primary">{{ $alerts->count() }} {{ __('active') }}</span>
    </div>
    <p class="color-text-paragraph-2 font-sm mb-30">
        {{ __('Get notified when matching jobs are posted') }}
    </p>

    {{-- Quota status banner --}}
    @php
        $period    = $period ?? \Botble\JobBoard\Models\JobAlertQuota::currentPeriod();
        $freeLimit = (int) setting('job_alert_free_monthly_limit', 3);
        $freeSent  = (int) \Botble\JobBoard\Models\JobAlertQuota::query()
            ->where('account_id', $account->id)->where('period', $period)->whereNull('package_id')
            ->value('alerts_sent');
        $paidRows  = \Botble\JobBoard\Models\JobAlertQuota::query()
            ->activePaid()
            ->where('account_id', $account->id)->where('period', $period)
            ->with('package')->get();
        $hasPaid   = $paidRows->isNotEmpty();
        $exhausted = $freeSent >= $freeLimit && !$hasPaid;
    @endphp

    <div class="card border-0 shadow-sm mb-20">
        <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap gap-4">
                <div>
                    <span class="color-text-paragraph-2 font-xs d-block">{{ __('Total sent this month') }}</span>
                    <span class="fw-semibold text-primary">{{ $sentThisMonth ?? ($freeSent + $paidRows->sum('alerts_sent')) }}</span>
                </div>
                <div>
                    <span class="color-text-paragraph-2 font-xs d-block">{{ __('Free alerts this month') }}</span>
                    <span class="fw-semibold {{ $freeSent >= $freeLimit ? 'text-danger' : 'text-success' }}">
                        {{ $freeSent }} / {{ $freeLimit }}
                    </span>
                </div>
                @foreach($paidRows as $q)
                    <div>
                        <span class="color-text-paragraph-2 font-xs d-block">{{ $q->package->name ?? 'Paid quota' }}</span>
                        <span class="fw-semibold text-primary">
                            {{ $q->alerts_allowed === -1 ? '∞ unlimited' : ($q->alerts_sent . ' / ' . $q->alerts_allowed) }}
                        </span>
                    </div>
                @endforeach
            </div>
            @if($exhausted || $freeSent >= $freeLimit)
                <a href="{{ route('public.account.job-alert.packages.index') }}" class="btn btn-apply btn-sm px-3">
                    <i class="fi-rr-star me-1"></i>{{ __('Get more alerts') }}
                </a>
            @endif
        </div>
    </div>
    @if($exhausted)
        <div class="alert alert-warning font-sm mb-20">
            <i class="fi-rr-exclamation me-2"></i>
            {{ __('You\'ve used all your free alerts for this month. Upgrade to keep receiving job notifications.') }}
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-20" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-20">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Create Alert Form --}}
    <div class="card border-0 shadow-sm mb-30">
        <div class="card-body p-4">
            <h5 class="fw-semibold mb-20">{{ __('Create New Alert') }}</h5>
            <form method="POST" action="{{ route('public.account.job-alerts.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label font-sm fw-semibold">{{ __('Keyword') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                        <input type="text"
                               name="keyword"
                               class="form-control"
                               placeholder="{{ __('e.g. developer, accountant') }}"
                               value="{{ old('keyword') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-sm fw-semibold">{{ __('Categories') }} <span class="text-muted fw-normal">({{ __('optional, pick multiple') }})</span></label>
                        <select name="category_ids[]" class="form-select" multiple style="min-height:80px;">
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}"
                                    {{ in_array($id, (array) old('category_ids', [])) ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('Hold Ctrl / Cmd to select multiple.') }}</div>
                    </div>

                    @if($countries->isNotEmpty())
                        <div class="col-md-4">
                            <label class="form-label font-sm fw-semibold">{{ __('Country') }}</label>
                            <select name="country_id" id="alert-country" class="form-select">
                                <option value="">{{ __('Any Country') }}</option>
                                @foreach($countries as $id => $name)
                                    <option value="{{ $id }}" {{ old('country_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-sm fw-semibold">{{ __('State / Province') }}</label>
                            <select name="state_id" id="alert-state" class="form-select">
                                <option value="">{{ __('Any State') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-sm fw-semibold">{{ __('City') }}</label>
                            <select name="city_id" id="alert-city" class="form-select">
                                <option value="">{{ __('Any City') }}</option>
                            </select>
                        </div>
                    @endif

                    <div class="col-12">
                        <label class="form-label font-sm fw-semibold">{{ __('Notify me via') }}</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notify_email" value="1" id="notify_email_new"
                                    {{ old('notify_email', '1') ? 'checked' : '' }}>
                                <label class="form-check-label font-sm" for="notify_email_new">
                                    <i class="fi-rr-envelope me-1"></i>{{ __('Email') }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notify_whatsapp" value="1" id="notify_whatsapp_new"
                                    {{ old('notify_whatsapp') ? 'checked' : '' }}>
                                <label class="form-check-label font-sm" for="notify_whatsapp_new">
                                    <i class="fi-rr-comment me-1"></i>{{ __('WhatsApp') }}
                                    @if(! $account->whatsapp_number)
                                        <a href="{{ route('public.account.settings') }}#whatsapp_number" class="ms-1 font-xs text-warning">
                                            ({{ __('add number in settings') }})
                                        </a>
                                    @endif
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notify_telegram" value="1" id="notify_telegram_new"
                                    {{ old('notify_telegram') ? 'checked' : '' }}>
                                <label class="form-check-label font-sm" for="notify_telegram_new">
                                    <i class="fi-rr-paper-plane me-1"></i>{{ __('Telegram') }}
                                    @if(! $account->telegram_chat_id)
                                        <a href="{{ route('public.account.settings') }}#telegram_chat_id" class="ms-1 font-xs text-warning">
                                            ({{ __('add chat ID in settings') }})
                                        </a>
                                    @endif
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-apply px-4">
                            <i class="fi-rr-bell me-1"></i>{{ __('Create Alert') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Existing Alerts --}}
    @if($alerts->isNotEmpty())
        <h5 class="fw-semibold mb-20">{{ __('Your Alerts') }}</h5>
        <div class="table-responsive">
            <table class="table table-borderless align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="font-sm fw-semibold">{{ __('Keyword') }}</th>
                        <th class="font-sm fw-semibold">{{ __('Category') }}</th>
                        <th class="font-sm fw-semibold">{{ __('Location') }}</th>
                        <th class="font-sm fw-semibold">{{ __('Channels') }}</th>
                        <th class="font-sm fw-semibold">{{ __('Last 2 days') }}</th>
                        <th class="font-sm fw-semibold">{{ __('Sent') }}</th>
                        <th class="font-sm fw-semibold">{{ __('Active') }}</th>
                        <th class="font-sm fw-semibold"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alerts as $alert)
                        <tr>
                            <td class="font-sm">
                                {{ $alert->keyword ?: '—' }}
                            </td>
                            <td class="font-sm">
                                @php
                                    $alertCatIds = array_filter((array) ($alert->category_ids ?: ($alert->category_id ? [$alert->category_id] : [])));
                                    $catNames = $categories->only($alertCatIds)->values();
                                @endphp
                                {{ $catNames->isNotEmpty() ? $catNames->implode(', ') : __('All') }}
                            </td>
                            <td class="font-sm text-muted">
                                @php
                                    $locationParts = array_filter([
                                        $alert->city?->name,
                                        $alert->state?->name,
                                        $alert->country?->name,
                                    ]);
                                @endphp
                                {{ $locationParts ? implode(', ', $locationParts) : __('Anywhere') }}
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($alert->notify_email)
                                        <span class="badge bg-secondary font-xs"><i class="fi-rr-envelope me-1"></i>{{ __('Email') }}</span>
                                    @endif
                                    @if($alert->notify_whatsapp)
                                        <span class="badge bg-success font-xs"><i class="fi-rr-comment me-1"></i>{{ __('WhatsApp') }}</span>
                                    @endif
                                    @if($alert->notify_telegram)
                                        <span class="badge bg-info font-xs"><i class="fi-rr-paper-plane me-1"></i>{{ __('Telegram') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="font-sm">
                                @php($stats = $alertStats[$alert->id] ?? ['matches_last_two_days' => 0, 'latest_match' => null])
                                <div class="fw-semibold">{{ $stats['matches_last_two_days'] }}</div>
                                @if($stats['latest_match'])
                                    <a href="{{ $stats['latest_match']->url }}" target="_blank" class="font-xs text-muted">
                                        {{ \Illuminate\Support\Str::limit($stats['latest_match']->name, 36) }}
                                    </a>
                                @else
                                    <span class="font-xs text-muted">{{ __('No recent matches') }}</span>
                                @endif
                            </td>
                            <td class="font-sm">
                                {{ $sentThisMonth ?? ($freeSent + $paidRows->sum('alerts_sent')) }}
                            </td>
                            <td>
                                <form method="POST" action="{{ route('public.account.job-alerts.update', $alert->id) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="is_active" value="{{ $alert->is_active ? '0' : '1' }}">
                                    <button type="submit" class="btn btn-sm {{ $alert->is_active ? 'btn-outline-success' : 'btn-outline-secondary' }} py-1 px-2 font-xs">
                                        {{ $alert->is_active ? __('Active') : __('Paused') }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-end">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger py-1 px-2 font-xs btn-delete-alert"
                                            data-delete-url="{{ route('public.account.job-alerts.destroy', $alert->id) }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteAlertModal">
                                        <i class="fi-rr-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-40 color-text-paragraph-2">
            <i class="fi-rr-bell fs-1 d-block mb-10 opacity-25"></i>
            <p class="font-sm">{{ __('No job alerts yet. Create one above to get notified about matching jobs.') }}</p>
        </div>
    @endif
</div>

{{-- Delete confirmation modal --}}
<div class="modal fade" id="deleteAlertModal" tabindex="-1" aria-labelledby="deleteAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4 px-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                        <i class="fi-rr-trash text-danger fs-4"></i>
                    </span>
                </div>
                <h6 class="fw-semibold mb-1">{{ __('Delete this alert?') }}</h6>
                <p class="text-muted font-sm mb-4">{{ __('You will stop receiving notifications for this alert.') }}</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <form id="deleteAlertForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger px-4">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-delete-alert').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('deleteAlertForm').action = btn.getAttribute('data-delete-url');
        });
    });
});
</script>

@if($countries->isNotEmpty())
<script>
document.addEventListener('DOMContentLoaded', function () {
    var countrySelect = document.getElementById('alert-country');
    var stateSelect   = document.getElementById('alert-state');
    var citySelect    = document.getElementById('alert-city');

    if (!countrySelect) return;

    countrySelect.addEventListener('change', function () {
        var countryId = this.value;
        stateSelect.innerHTML = '<option value="">{{ __('Any State') }}</option>';
        citySelect.innerHTML  = '<option value="">{{ __('Any City') }}</option>';
        if (!countryId) return;

        fetch('{{ url('ajax/states-by-country') }}?country_id=' + countryId)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var items = Array.isArray(data) ? data : (data.data || []);
                items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    stateSelect.appendChild(opt);
                });
            });
    });

    stateSelect.addEventListener('change', function () {
        var stateId = this.value;
        citySelect.innerHTML = '<option value="">{{ __('Any City') }}</option>';
        if (!stateId) return;

        fetch('{{ url('ajax/cities-by-state') }}?state_id=' + stateId)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var items = Array.isArray(data) ? data : (data.data || []);
                items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    citySelect.appendChild(opt);
                });
            });
    });
});
</script>
@endif
@endsection
