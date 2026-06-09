<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-10 col-12">

                {{-- Order summary --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle"
                                 style="width:52px;height:52px;background:linear-gradient(135deg,#25d366,#128c4a);">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path fill="#fff" d="M12.04 2a9.84 9.84 0 0 0-8.43 14.92L2.05 22l5.2-1.52A9.96 9.96 0 1 0 12.04 2Zm4.34 13.02c-.24-.12-1.4-.69-1.62-.77-.22-.08-.38-.12-.54.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.91-1.18a7.17 7.17 0 0 1-1.32-1.64c-.14-.24-.01-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.47-.4-.4-.54-.41h-.46a.88.88 0 0 0-.63.3c-.22.24-.83.81-.83 1.98s.85 2.3.97 2.46c.12.16 1.67 2.55 4.05 3.58.57.24 1.01.39 1.35.5.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.03.14-1.12-.06-.1-.22-.16-.46-.28Z"/>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-0 fw-bold">VIP WhatsApp Job Alerts</h5>
                                <p class="mb-0 text-muted font-sm">{{ $planData['label'] ?? $order->plan }} plan &middot; {{ $order->candidate_name }}</p>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fs-3 fw-bold text-success">{{ $currency }} {{ number_format($amount, 2) }}</div>
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
                        <input name="vip_alert_order_token" type="hidden" value="{{ $order->public_token }}">
                        <input name="customer_name"      type="hidden" value="{{ $order->candidate_name }}">
                        <input name="customer_email"     type="hidden" value="{{ $order->candidate_email }}">

                        @include('plugins/payment::partials.payment-methods')
                        {!! apply_filters(PAYMENT_FILTER_AFTER_PAYMENT_METHOD, null) !!}

                        <x-core::button
                            class="payment-checkout-btn w-100 btn-apply-big"
                            color="success"
                            data-processing-text="{{ trans('plugins/payment::payment.processing_please_wait') }}"
                            data-error-header="{{ trans('plugins/payment::payment.error') }}"
                            icon="ti ti-credit-card"
                        >
                            Pay {{ $currency }} {{ number_format($amount, 2) }} &amp; Submit Order
                        </x-core::button>

                        <p class="text-center font-xs text-muted mt-3">
                            <i class="fi-rr-shield-check text-success me-1"></i>
                            Your subscription will be activated within 24 hours after admin review.
                        </p>
                    </x-core::form>
                </div>

                @include('plugins/payment::partials.footer')
            </div>
        </div>
    </div>
</section>
