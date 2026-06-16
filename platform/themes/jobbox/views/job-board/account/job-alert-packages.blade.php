@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
<div>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h3 class="mt-0 mb-0 color-brand-1">{{ __('VIP Job Alerts') }}</h3>
        <a href="{{ route('public.account.job-alerts.index') }}" class="font-sm color-text-paragraph-2">
            ← {{ __('Back to Job Alerts') }}
        </a>
    </div>
    <p class="color-text-paragraph-2 font-sm mb-30">
        {{ __('Get personalised job matches sent straight to your WhatsApp — no searching, we send the jobs to you.') }}
    </p>

    <div class="row g-3 mb-40">
        @foreach($plans as $planKey => $plan)
            @php $isPopular = ($plan['badge'] ?? null) === 'Most Popular'; @endphp
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border career-service-card position-relative {{ $isPopular ? 'border-success border-2' : '' }}" style="border-radius:12px;transition:box-shadow .2s,border-color .2s;">
                    @if(!empty($plan['badge']))
                        <span class="badge position-absolute top-0 start-50 translate-middle px-3 py-2"
                              style="background:{{ $isPopular ? '#25d366' : '#0d6efd' }};color:#fff;font-size:.75rem;">
                            {{ $plan['badge'] }}
                        </span>
                    @endif
                    <div class="card-body p-4 pt-5 d-flex flex-column">
                        <div class="text-center mb-3">
                            <div class="fw-bold color-brand-1 fs-4">{{ $plan['currency'] }} {{ number_format($plan['price'], 2) }}</div>
                            <div class="text-muted font-sm">{{ $plan['label'] }}</div>
                        </div>
                        <ul class="list-unstyled mb-4 flex-grow-1">
                            <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>{{ $plan['label'] }} {{ __('of VIP alerts') }}</li>
                            <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>{{ __('WhatsApp delivery') }}</li>
                            <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>{{ __('Custom job filters') }}</li>
                            <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>{{ __('All countries supported') }}</li>
                        </ul>
                        <a href="{{ route('public.vip-alerts.checkout', $planKey) }}" class="btn btn-apply px-4 {{ $isPopular ? '' : 'btn-outline-success' }}">
                            {{ __('Get Started') }} — {{ $plan['currency'] }} {{ number_format($plan['price'], 2) }}
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-center color-text-paragraph-2 mb-0" style="font-size:12px;">
        <i class="fi-rr-shield-check text-success me-1"></i>
        {{ __('Secure payment · Activation within 24 hours') }}
    </p>
</div>

<style>
    .career-service-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,.10);
        border-color: var(--primary-color) !important;
    }
</style>
@endsection
