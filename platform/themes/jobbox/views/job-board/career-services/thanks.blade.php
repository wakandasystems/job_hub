@extends(Theme::getThemeNamespace('views.base'))

@section('content')
<section class="section-box mt-80 mb-80">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-6 col-md-8 col-12">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:80px;height:80px;">
                        <i class="fi-rr-check fs-1 text-success"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-2">Booking Confirmed!</h3>
                <p class="color-text-paragraph-2 mb-4">
                    Your <strong>{{ $order->service_name }}</strong> has been booked successfully.
                    A career coach will reach out to <strong>{{ $order->customer_email }}</strong> within 2–4 hours.
                </p>
                <div class="card border-0 bg-light text-start mb-4">
                    <div class="card-body px-4 py-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Service</span>
                            <strong>{{ $order->service_name }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Amount Paid</span>
                            <strong>{{ $order->currency }} {{ number_format($order->amount, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Reference</span>
                            <strong class="text-muted">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong>
                        </div>
                    </div>
                </div>
                <a href="{{ route('public.index') }}" class="btn btn-apply btn-apply-big me-2">Back to Home</a>
                <a href="{{ route('public.jobs') }}" class="btn btn-outline-primary btn-apply-big">Browse Jobs</a>
            </div>
        </div>
    </div>
</section>
@endsection
