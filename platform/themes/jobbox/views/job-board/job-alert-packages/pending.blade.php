<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-12 text-center">

                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 mx-auto mb-3" style="width:72px;height:72px;">
                        <i class="fi-rr-clock fs-1 text-warning"></i>
                    </div>
                    <h3 class="fw-bold">Payment Pending Approval</h3>
                    <p class="text-muted">
                        Your order for <strong>{{ $package->name }}</strong> has been received.<br>
                        Our team will verify your payment and activate your quota shortly.
                    </p>
                </div>

                <div class="card border-0 shadow-sm mb-4 text-start">
                    <div class="card-body p-4">
                        <div class="row g-2">
                            <div class="col-5 text-muted">Order #</div>
                            <div class="col-7 fw-semibold">#{{ $order->id }}</div>
                            <div class="col-5 text-muted">Package</div>
                            <div class="col-7">{{ $package->name }}</div>
                            <div class="col-5 text-muted">Amount</div>
                            <div class="col-7">{{ $order->currency }} {{ number_format($order->amount, 2) }}</div>
                            @if($order->charge_id)
                                <div class="col-5 text-muted">Reference</div>
                                <div class="col-7 font-monospace small">{{ $order->charge_id }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <a href="{{ route('public.account.job-alert.packages.index') }}" class="btn btn-outline-primary">
                    Back to Job Alert Packages
                </a>
            </div>
        </div>
    </div>
</section>
