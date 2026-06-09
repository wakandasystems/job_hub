<section class="section-box mt-80 mb-80">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-9 text-center">

                @if($order->payment_status === 'paid')
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                         style="width:80px;height:80px;background:#ecfdf3;border:2px solid #abefc6;">
                        <i class="fi-rr-check text-success" style="font-size:2.5rem;"></i>
                    </div>
                    <h2 class="mb-3">Payment Received!</h2>
                    <p class="font-md color-text-paragraph-2 mb-4">
                        Thank you, <strong>{{ $order->candidate_name }}</strong>! Your payment of
                        <strong>{{ $order->currency }} {{ number_format($order->amount, 2) }}</strong> has been received.
                    </p>
                    <div class="alert border" style="background:#ecfdf3;border-color:#abefc6 !important;">
                        <div class="d-flex align-items-start gap-3 text-start">
                            <i class="fi-rr-time-check text-success mt-1 flex-shrink-0" style="font-size:1.4rem;"></i>
                            <div>
                                <strong>Activation pending</strong>
                                <p class="mb-0 font-sm text-muted mt-1">
                                    Your <strong>{{ $planData['label'] ?? $order->plan }}</strong> VIP alert will be activated
                                    within 24 hours after our team verifies your order.
                                    You'll receive a WhatsApp message at <strong>{{ $order->candidate_phone }}</strong> and
                                    an email at <strong>{{ $order->candidate_email }}</strong> once it's live.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                         style="width:80px;height:80px;background:#fffaeb;border:2px solid #fec84b;">
                        <i class="fi-rr-clock text-warning" style="font-size:2.5rem;"></i>
                    </div>
                    <h2 class="mb-3">Order Submitted</h2>
                    <p class="font-md color-text-paragraph-2 mb-4">
                        Your order has been submitted. Once your payment is confirmed and reviewed, your VIP alerts
                        will be activated within 24 hours.
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
                            <div class="col-5 text-muted">Amount Paid</div>
                            <div class="col-7 fw-medium">{{ $order->currency }} {{ number_format($order->amount, 2) }}</div>
                            <div class="col-5 text-muted">WhatsApp</div>
                            <div class="col-7 fw-medium">{{ $order->candidate_phone }}</div>
                            <div class="col-5 text-muted">Email</div>
                            <div class="col-7 fw-medium">{{ $order->candidate_email }}</div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('public.index') }}" class="btn btn-outline-secondary me-2">Browse Jobs</a>
                <a href="{{ route('public.vip-alerts.plans') }}" class="btn btn-success">View Plans</a>

            </div>
        </div>
    </div>
</section>
