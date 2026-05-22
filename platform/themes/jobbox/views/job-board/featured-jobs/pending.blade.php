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
                    <h3 class="fw-bold color-brand-1 mb-2">{{ __('Activation Pending') }}</h3>
                    <p class="color-text-paragraph-2 font-sm mb-4">
                        {{ __('Your featured job request is being reviewed. Your job will be featured as soon as it is approved.') }}
                    </p>

                    <div class="card bg-light border-0 p-3 mb-4 text-start">
                        <div class="row g-2">
                            <div class="col-6 font-sm text-muted">{{ __('Order ref') }}</div>
                            <div class="col-6 font-sm fw-semibold">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</div>
                            <div class="col-6 font-sm text-muted">{{ __('Package') }}</div>
                            <div class="col-6 font-sm fw-semibold">{{ $package?->name }}</div>
                            <div class="col-6 font-sm text-muted">{{ __('Job') }}</div>
                            <div class="col-6 font-sm fw-semibold">{{ \Illuminate\Support\Str::limit($job?->name ?? '—', 35) }}</div>
                            <div class="col-6 font-sm text-muted">{{ __('Credits Used') }}</div>
                            <div class="col-6 font-sm fw-semibold">{{ number_format((int) $order->amount) }} {{ __('credits') }}</div>
                            <div class="col-6 font-sm text-muted">{{ __('Payment') }}</div>
                            <div class="col-6 font-sm">
                                {{ $order->payment_method ? ucwords(str_replace('_', ' ', $order->payment_method)) : '—' }}
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('public.account.featured-jobs.index') }}" class="btn btn-apply px-5">
                        {{ __('Back to Feature a Job') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
