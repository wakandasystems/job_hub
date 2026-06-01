@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
<div>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h3 class="mt-0 mb-0 color-brand-1">{{ __('Job Alert Packages') }}</h3>
        <a href="{{ route('public.account.job-alerts.index') }}" class="font-sm color-text-paragraph-2">
            ← {{ __('Back to Job Alerts') }}
        </a>
    </div>
    <p class="color-text-paragraph-2 font-sm mb-30">
        {{ __('Get more job notifications each month by upgrading your alert quota.') }}
    </p>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-20" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Current quota summary --}}
    <div class="card border-0 shadow-sm mb-30">
        <div class="card-body p-4">
            <h6 class="fw-semibold mb-3">{{ __('This Month') }} ({{ $period }})</h6>
            <div class="row g-3">
                <div class="col-auto">
                    <div class="text-muted font-sm">{{ __('Free alerts used') }}</div>
                    <div class="fw-bold fs-5">{{ $sentFree }} / {{ $freeLimit }}</div>
                </div>
                @foreach($paidQuota as $q)
                    <div class="col-auto">
                        <div class="text-muted font-sm">{{ $q->package->name ?? 'Paid' }}</div>
                        <div class="fw-bold fs-5 text-success">
                            {{ $q->alerts_allowed === -1 ? $q->alerts_sent . ' / ∞' : $q->alerts_sent . ' / ' . $q->alerts_allowed }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Package cards --}}
    @if($packages->isEmpty())
        <div class="text-center py-40 color-text-paragraph-2">
            <p class="font-sm">{{ __('No packages are available yet. Check back soon.') }}</p>
        </div>
    @else
        <div class="row g-3 mb-40">
            @foreach($packages as $pkg)
                @php
                    $pricing = $packagePrices[$pkg->id] ?? null;
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border career-service-card" style="border-radius:12px;transition:box-shadow .2s,border-color .2s;">
                        <div class="card-body p-4 d-flex flex-column position-relative">
                            <span class="position-absolute top-0 end-0 mt-3 me-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10" style="width:40px;height:40px;">
                                <i class="fi-rr-bell text-primary fs-5"></i>
                            </span>
                            <div class="mb-3">
                                <div class="fw-semibold fs-6">{{ $pkg->name }}</div>
                                @if($pkg->description)
                                    <p class="color-text-paragraph-2 mb-0 font-sm">{{ $pkg->description }}</p>
                                @endif
                            </div>
                            <div class="mb-3 flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold color-brand-1 fs-4">
                                        {{ $pkg->displayAlerts() }}
                                    </span>
                                    <span class="color-text-paragraph-2 font-sm">
                                        {{ $pkg->isUnlimited() ? __('alerts / month') : __('alerts / month') }}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-auto">
                                <div>
                                    <span class="fw-bold color-brand-1 fs-5">
                                        {{ $pricing['display'] ?? ($pkg->currency . ' ' . number_format($pkg->price, 2)) }}
                                    </span>
                                    @if($pricing && $pricing['origin_currency_code'] !== $pricing['currency_code'])
                                        <div class="font-xs color-text-paragraph-2">
                                            {{ __('Converted for :country', ['country' => $pricing['target_country'] ?? $pricing['currency_code']]) }}
                                        </div>
                                    @endif
                                    @if($pricing)
                                        <div class="font-xs color-text-paragraph-2">
                                            {{ __('Original: :country :price', ['country' => $pricing['origin_country'] ?? $pricing['origin_currency_code'], 'price' => $pricing['origin_display']]) }}
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('public.account.job-alert.packages.checkout', ['package' => $pkg->id]) }}"
                                   class="btn btn-apply px-4">
                                    {{ __('Get this plan') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <p class="text-center color-text-paragraph-2 mb-0" style="font-size:12px;">
        <i class="fi-rr-shield-check text-success me-1"></i>
        {{ __('Secure payment · Quota added on approval · Resets monthly') }}
    </p>

    @if($myOrders->isNotEmpty())
        <div class="mt-40">
            <h5 class="fw-semibold mb-20">{{ __('My Orders') }}</h5>
            <div class="table-responsive">
                <table class="table table-borderless align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="font-sm fw-semibold">Ref</th>
                            <th class="font-sm fw-semibold">Package</th>
                            <th class="font-sm fw-semibold">Amount</th>
                            <th class="font-sm fw-semibold">Payment</th>
                            <th class="font-sm fw-semibold">Status</th>
                            <th class="font-sm fw-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($myOrders as $o)
                            @php
                                $statusBadge = match($o->status) {
                                    'approved'  => 'success',
                                    'rejected'  => 'danger',
                                    'cancelled' => 'secondary',
                                    default     => 'warning',
                                };
                            @endphp
                            <tr>
                                <td class="font-xs text-muted">#{{ str_pad($o->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td class="font-sm">{{ $o->package?->name ?? '—' }}</td>
                                <td class="font-sm fw-semibold">{{ $o->currency }} {{ number_format($o->amount, 2) }}</td>
                                <td class="font-xs text-muted">
                                    {{ $o->payment_method ? ucwords(str_replace('_', ' ', $o->payment_method)) : '—' }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $statusBadge }}">{{ ucfirst($o->status) }}</span>
                                </td>
                                <td class="font-xs text-muted">{{ $o->created_at?->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<style>
    .career-service-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,.10);
        border-color: var(--primary-color) !important;
    }
</style>
@endsection
