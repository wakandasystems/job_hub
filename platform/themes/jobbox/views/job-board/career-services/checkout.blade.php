<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-10 col-12">

                {{-- Service summary card --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10" style="width:52px;height:52px;">
                                <i class="{{ $service['icon'] ?? 'fi-rr-briefcase' }} fs-4 text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1 fw-bold">{{ $service['name'] }}</h5>
                                <p class="text-muted mb-2 font-sm">{{ $service['description'] }}</p>
                                <span class="badge bg-soft-success text-success me-2"><i class="fi-rr-clock me-1"></i>Delivered in {{ $service['delivery'] }}</span>
                                @if (!empty($service['badge']))
                                    <span class="badge bg-warning text-dark">{{ $service['badge'] }}</span>
                                @endif
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fs-3 fw-bold text-primary">{{ $pricing['display'] ?? ($currency . ' ' . number_format($amount, 2)) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Customer details form + payment --}}
                @php
                    $formAction = route('payments.checkout');
                    $callbackUrl = route('public.career-service.callback', ['order' => $order->id]);
                    $name = $service['name'];
                @endphp

                @include('plugins/payment::partials.header')

                <div class="checkout-wrapper">
                    <x-core::form
                        :url="$formAction"
                        class="payment-checkout-form"
                        method="post"
                    >
                        <input name="name"         type="hidden" value="{{ $name }}">
                        <input name="amount"       type="hidden" value="{{ $amount }}">
                        <input name="currency"     type="hidden" value="{{ $currency }}">
                        <input name="return_url"   type="hidden" value="{{ $returnUrl }}">
                        <input name="callback_url" type="hidden" value="{{ $callbackUrl }}">
                        <input name="career_service_order_id" type="hidden" value="{{ $order->id }}">

                        {{-- Customer info fields --}}
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h6 class="fw-semibold mb-3">Your Details</h6>
                                <div class="mb-3">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" class="form-control"
                                        value="{{ old('customer_name', auth('account')->user()?->name) }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="customer_email" class="form-control"
                                        value="{{ old('customer_email', auth('account')->user()?->email) }}" required>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" name="customer_phone" class="form-control" required
                                        value="{{ old('customer_phone', auth('account')->user()?->phone) }}"
                                        placeholder="+260 97x xxx xxx">
                                </div>
                            </div>
                        </div>

                        @include('plugins/payment::partials.payment-methods')

                        {!! apply_filters(PAYMENT_FILTER_AFTER_PAYMENT_METHOD, null) !!}

                        <x-core::button
                            class="payment-checkout-btn w-100 btn-apply-big"
                            color="primary"
                            data-processing-text="{{ trans('plugins/payment::payment.processing_please_wait') }}"
                            data-error-header="{{ trans('plugins/payment::payment.error') }}"
                            icon="ti ti-credit-card"
                        >
                            Pay {{ $pricing['display'] ?? ($currency . ' ' . number_format($amount, 2)) }} &amp; Book Service
                        </x-core::button>
                    </x-core::form>
                </div>

                @include('plugins/payment::partials.footer')

            </div>
        </div>
    </div>
</section>

@push('footer')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.querySelector('.payment-checkout-btn');
    if (!btn) return;

    btn.addEventListener('click', function (e) {
        const form = btn.closest('form');
        if (!form) return;

        const required = form.querySelectorAll('[required]');
        let firstInvalid = null;

        required.forEach(function (field) {
            field.classList.remove('is-invalid');
            const next = field.nextElementSibling;
            if (next && next.classList.contains('invalid-feedback')) next.remove();
        });

        required.forEach(function (field) {
            const empty = field.value.trim() === '';
            const badEmail = field.type === 'email' && field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value);

            if (empty || badEmail) {
                field.classList.add('is-invalid');
                const msg = document.createElement('div');
                msg.className = 'invalid-feedback d-block';
                msg.textContent = empty
                    ? (field.name === 'customer_phone' ? 'Phone number is required.' : 'This field is required.')
                    : 'Please enter a valid email address.';
                field.insertAdjacentElement('afterend', msg);
                if (!firstInvalid) firstInvalid = field;
            }
        });

        if (firstInvalid) {
            e.stopImmediatePropagation();
            firstInvalid.focus();
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, true); // capture phase — fires before payment.js document-level handler
});
</script>
@endpush
