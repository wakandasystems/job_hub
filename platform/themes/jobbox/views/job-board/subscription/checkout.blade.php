<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-10 col-12">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10" style="width:52px;height:52px;">
                                <i class="fi-rr-crown fs-4 text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1 fw-bold">{{ $package->name }}</h5>
                                <p class="text-muted mb-2 font-sm">{{ $package->description }}</p>
                                <span class="badge bg-soft-primary text-primary">
                                    {{ ucfirst($billingCycle) }} subscription
                                    @if($billingCycle === 'annual') · 2 months free @endif
                                </span>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fs-3 fw-bold text-primary">{{ $currency }} {{ number_format($price, 0) }}</div>
                                <div class="text-muted small">{{ $billingCycle === 'annual' ? '/year' : '/month' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                @php $formAction = route('payments.checkout'); @endphp
                @include('plugins/payment::partials.header')

                <div class="checkout-wrapper">
                    <x-core::form :url="$formAction" class="payment-checkout-form" method="post">
                        <input name="name"                    type="hidden" value="{{ $name }}">
                        <input name="amount"                  type="hidden" value="{{ $price }}">
                        <input name="currency"                type="hidden" value="{{ $currency }}">
                        <input name="return_url"              type="hidden" value="{{ $returnUrl }}">
                        <input name="callback_url"            type="hidden" value="{{ $callbackUrl }}">
                        <input name="subscription_order_id"   type="hidden" value="{{ $sub->id }}">

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h6 class="fw-semibold mb-3">{{ __('Your Details') }}</h6>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Full Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" class="form-control" value="{{ $account->name }}" required>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">{{ __('Email Address') }} <span class="text-danger">*</span></label>
                                    <input type="email" name="customer_email" class="form-control" value="{{ $account->email }}" required>
                                </div>
                            </div>
                        </div>

                        @include('plugins/payment::partials.payment-methods')
                        {!! apply_filters(PAYMENT_FILTER_AFTER_PAYMENT_METHOD, null) !!}

                        <x-core::button class="payment-checkout-btn w-100 btn-apply-big" color="primary"
                            data-processing-text="{{ trans('plugins/payment::payment.processing_please_wait') }}"
                            data-error-header="{{ trans('plugins/payment::payment.error') }}"
                            icon="ti ti-credit-card">
                            {{ __('Pay :currency :amount & Activate', ['currency' => $currency, 'amount' => number_format($price, 0)]) }}
                        </x-core::button>
                    </x-core::form>
                </div>

                @include('plugins/payment::partials.footer')
            </div>
        </div>
    </div>
</section>
