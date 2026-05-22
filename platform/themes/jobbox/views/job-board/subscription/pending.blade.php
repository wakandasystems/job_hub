<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-12 text-center">
                <div class="card border-0 shadow-sm p-5">
                    <div class="mb-4">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-15" style="width:72px;height:72px;">
                            <i class="fi-rr-clock text-warning fs-1"></i>
                        </span>
                    </div>
                    <h3 class="fw-bold color-brand-1 mb-2">{{ __('Payment Pending') }}</h3>
                    <p class="color-text-paragraph-2 font-sm mb-4">
                        {{ __('Your payment is being verified. Your subscription will activate as soon as we confirm receipt.') }}
                    </p>
                    <div class="card bg-light border-0 p-3 mb-4 text-start">
                        <div class="row g-2">
                            <div class="col-6 font-sm text-muted">Ref</div>
                            <div class="col-6 font-sm fw-semibold">#{{ str_pad($subscription->id, 6, '0', STR_PAD_LEFT) }}</div>
                            <div class="col-6 font-sm text-muted">Plan</div>
                            <div class="col-6 font-sm fw-semibold">{{ $subscription->package?->name }}</div>
                            <div class="col-6 font-sm text-muted">Amount</div>
                            <div class="col-6 font-sm fw-semibold">{{ $subscription->currency }} {{ number_format($subscription->amount, 2) }}</div>
                        </div>
                    </div>
                    <a href="{{ route('public.account.subscription.index') }}" class="btn btn-apply px-5">Back to Subscriptions</a>
                </div>
            </div>
        </div>
    </div>
</section>
