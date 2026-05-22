<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-12 text-center">
                <div class="card border-0 shadow-sm p-5">
                    <div class="mb-4">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:72px;height:72px;">
                            <i class="fi-rr-check text-success fs-1"></i>
                        </span>
                    </div>
                    <h3 class="fw-bold color-brand-1 mb-2">{{ __('Subscription Activated!') }}</h3>
                    <p class="color-text-paragraph-2 font-sm mb-4">
                        {{ __('Your :plan plan is now active.', ['plan' => $subscription->package?->name]) }}
                        @if($subscription->ends_at)
                            {{ __('It renews on :date.', ['date' => $subscription->ends_at->format('d M Y')]) }}
                        @endif
                    </p>

                    <div class="card bg-light border-0 p-3 mb-4 text-start">
                        <div class="row g-2">
                            <div class="col-6 font-sm text-muted">Plan</div>
                            <div class="col-6 font-sm fw-semibold">{{ $subscription->package?->name }}</div>
                            <div class="col-6 font-sm text-muted">Billing</div>
                            <div class="col-6 font-sm fw-semibold">{{ ucfirst($subscription->billing_cycle) }}</div>
                            <div class="col-6 font-sm text-muted">Expires</div>
                            <div class="col-6 font-sm fw-semibold">{{ $subscription->ends_at?->format('d M Y') ?? 'N/A' }}</div>
                            @php $limit = (int)($subscription->package?->posts_per_cycle ?? 0); @endphp
                            <div class="col-6 font-sm text-muted">Post quota</div>
                            <div class="col-6 font-sm fw-semibold">{{ $limit === 0 ? 'Unlimited' : $limit . ' / cycle' }}</div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-center">
                        <a href="{{ route('public.account.dashboard') }}" class="btn btn-apply px-4">Go to Dashboard</a>
                        @if($subscription->package?->can_search_candidates)
                            <a href="{{ route('public.account.candidates.search') }}" class="btn btn-outline-secondary px-4">Search Candidates</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
