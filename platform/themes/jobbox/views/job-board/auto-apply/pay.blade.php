<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-10 col-12">

                {{-- Order summary --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle"
                                 style="width:52px;height:52px;background:linear-gradient(135deg,#3c65f5,#1e3a8a);">
                                <i class="fi-rr-paper-plane" style="font-size:22px;color:#fff;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-0 fw-bold">Auto Apply</h5>
                                <p class="mb-0 text-muted font-sm">{{ $planData['label'] ?? $order->plan }} plan</p>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fs-3 fw-bold text-primary">{{ $currency }} {{ number_format($amount, 2) }}</div>
                                <div class="font-xs text-muted">{{ $order->duration_days }} days access</div>
                            </div>
                        </div>
                    </div>
                </div>

                @include('plugins/payment::partials.header')

                <div class="checkout-wrapper">
                    <x-core::form :url="route('payments.checkout')" class="payment-checkout-form" method="post">
                        <input name="name"               type="hidden" value="{{ $name }}">
                        <input name="amount"             type="hidden" value="{{ $amount }}">
                        <input name="currency"           type="hidden" value="{{ $currency }}">
                        <input name="return_url"         type="hidden" value="{{ $returnUrl }}">
                        <input name="callback_url"       type="hidden" value="{{ $callbackUrl }}">
                        <input name="auto_apply_order_id" type="hidden" value="{{ $order->id }}">
                        <input name="customer_id"        type="hidden" value="{{ auth('account')->id() }}">
                        <input name="customer_type"      type="hidden" value="{{ \Botble\JobBoard\Models\Account::class }}">
                        <input name="customer_name"      type="hidden" value="{{ $order->account?->name }}">
                        <input name="customer_email"     type="hidden" value="{{ $order->account?->email }}">

                        @include('plugins/payment::partials.payment-methods')
                        {!! apply_filters(PAYMENT_FILTER_AFTER_PAYMENT_METHOD, null) !!}

                        <div class="mb-3">
                            <label for="sales_agent_code" class="form-label small fw-semibold">Have a referral code? (optional)</label>
                            <input type="text" name="sales_agent_code" id="sales_agent_code" class="form-control" placeholder="e.g. MELISSA10" style="text-transform:uppercase">
                        </div>

                        <x-core::button
                            class="payment-checkout-btn w-100 btn-apply-big"
                            color="primary"
                            data-processing-text="{{ trans('plugins/payment::payment.processing_please_wait') }}"
                            data-error-header="{{ trans('plugins/payment::payment.error') }}"
                            icon="ti ti-credit-card"
                        >
                            Pay {{ $currency }} {{ number_format($amount, 2) }}
                        </x-core::button>

                        <p class="text-center font-xs text-muted mt-3">
                            <i class="fi-rr-shield-check text-primary me-1"></i>
                            Auto Apply will be activated after payment confirmation.
                        </p>
                    </x-core::form>
                </div>

                @include('plugins/payment::partials.footer')
            </div>
        </div>
    </div>
</section>
