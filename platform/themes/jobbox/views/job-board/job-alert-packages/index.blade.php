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
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="mb-3">
                                <div class="fw-semibold fs-6">{{ $pkg->name }}</div>
                                @if($pkg->description)
                                    <p class="color-text-paragraph-2 mb-0 font-sm">{{ $pkg->description }}</p>
                                @endif
                            </div>
                            <div class="mb-3 flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fi-rr-bell text-primary fs-4"></i>
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
        {{ __('Secure payment · Quota added immediately · Resets monthly') }}
    </p>
</div>

<style>
    .career-service-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,.10);
        border-color: var(--primary-color) !important;
    }
</style>
@endsection
