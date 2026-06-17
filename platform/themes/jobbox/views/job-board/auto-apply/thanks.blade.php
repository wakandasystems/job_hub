<section class="section-box mt-80 mb-80">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-9 text-center">

                @if($order->status === 'approved')
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                         style="width:80px;height:80px;background:#ecfdf3;border:2px solid #abefc6;">
                        <i class="fi-rr-check text-success" style="font-size:2.5rem;"></i>
                    </div>
                    <h2 class="mb-3">Auto Apply Activated!</h2>
                    <p class="font-md color-text-paragraph-2 mb-4">
                        Your Auto Apply is now active. We'll automatically send AI-crafted applications
                        to matching jobs on your behalf.
                    </p>
                @else
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                         style="width:80px;height:80px;background:#eff6ff;border:2px solid #93c5fd;">
                        <i class="fi-rr-time-check text-primary" style="font-size:2.5rem;"></i>
                    </div>
                    <h2 class="mb-3">Payment Received!</h2>
                    <p class="font-md color-text-paragraph-2 mb-4">
                        Your payment has been received. Auto Apply will be activated shortly.
                    </p>
                @endif

                <div class="card border-0 shadow-sm text-start mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">Order Summary</h6>
                        <div class="row g-2 font-sm">
                            <div class="col-5 text-muted">Order #</div>
                            <div class="col-7 fw-medium">#{{ $order->id }}</div>
                            <div class="col-5 text-muted">Plan</div>
                            <div class="col-7 fw-medium">{{ $planData['label'] ?? $order->plan }}</div>
                            <div class="col-5 text-muted">Duration</div>
                            <div class="col-7 fw-medium">{{ $order->duration_days }} days</div>
                            <div class="col-5 text-muted">Applications/month</div>
                            <div class="col-7 fw-medium">
                                {{ $order->applications_allowed == -1 ? 'Unlimited' : $order->applications_allowed }}
                            </div>
                            <div class="col-5 text-muted">Amount</div>
                            <div class="col-7 fw-medium">{{ $order->currency }} {{ number_format($order->amount, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="alert border text-start" style="background:#eff6ff;border-color:#93c5fd !important;">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fi-rr-info text-primary mt-1 flex-shrink-0" style="font-size:1.4rem;"></i>
                        <div>
                            <strong>Next Steps</strong>
                            <p class="mb-0 font-sm text-muted mt-1">
                                Make sure your <a href="{{ route('public.account.auto-apply.index') }}">Auto Apply preferences</a>
                                are configured with your preferred keywords, categories, and match threshold.
                                The system will start applying to matching jobs automatically.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('public.account.auto-apply.index') }}" class="btn btn-primary me-2">Configure Preferences</a>
                    <a href="{{ route('public.index') }}" class="btn btn-outline-secondary">Browse Jobs</a>
                </div>

            </div>
        </div>
    </div>
</section>
