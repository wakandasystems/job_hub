@php
    $isLoggedIn = auth('account')->check();

    $account = null;
    if ($isLoggedIn) {
       $account = auth('account')->user();
    }

    $applyModalCountry   = wakanda_selected_country();
    $applyModalWaUrl     = wakanda_whatsapp_channel_url($applyModalCountry?->id);
    $applyModalWaLabel   = $applyModalCountry ? 'Follow Wakanda Jobs ' . $applyModalCountry->name . ' on WhatsApp' : null;
    $applyModalTgUrl     = wakanda_telegram_channel_url($applyModalCountry?->id);
    $applyModalTgLabel   = $applyModalCountry ? 'Join Wakanda Jobs ' . $applyModalCountry->name . ' on Telegram' : null;
    $applyModalVipPlans        = \Botble\JobBoard\Models\VipAlertOrder::plans();
    $applyModalVipStartingPlan = collect($applyModalVipPlans)->sortBy('price')->first();
    $applyModalAppCurrency     = get_application_currency();
    $applyModalVipCurrency     = $applyModalAppCurrency->title ?? ($applyModalVipStartingPlan['currency'] ?? 'USD');
    if ($applyModalVipStartingPlan) {
        $applyModalPlanCurrency = \Botble\JobBoard\Models\Currency::query()->where('title', $applyModalVipStartingPlan['currency'])->first();
        $applyModalPriceInDefault = ($applyModalPlanCurrency && !$applyModalPlanCurrency->is_default && $applyModalPlanCurrency->exchange_rate > 0)
            ? $applyModalVipStartingPlan['price'] / $applyModalPlanCurrency->exchange_rate
            : $applyModalVipStartingPlan['price'];
        $applyModalConvertedPrice = ($applyModalAppCurrency && !$applyModalAppCurrency->is_default && $applyModalAppCurrency->exchange_rate > 0)
            ? $applyModalPriceInDefault * $applyModalAppCurrency->exchange_rate
            : $applyModalPriceInDefault;
        $applyModalVipText = $applyModalVipCurrency . ' ' . number_format($applyModalConvertedPrice, 2);
    } else {
        $applyModalVipText = null;
    }
@endphp

@php
    $boostUrl = $isLoggedIn ? route('public.account.applications.boost', ['id' => '__ID__']) : null;
@endphp

@if (!$isLoggedIn || ($account && !$account->isEmployer()))
    <div class="modal fade" id="ModalApplyJobForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content apply-job-form">
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body pl-30 pr-30 pt-50">
                    {!! \Botble\JobBoard\Forms\Fronts\InternalJobApplicationForm::create()->renderForm() !!}
                    <div class="text-center py-3 border-top mt-3">
                        @if($applyModalVipText)
                            <a href="{{ route('public.vip-alerts.plans') }}" class="badge px-3 py-2 mb-2 d-inline-block text-decoration-none" style="background:linear-gradient(135deg,#25d366,#128c4a);color:#fff;font-size:.78rem;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="#fff" style="vertical-align:middle;margin-right:4px;" aria-hidden="true"><path d="M12.04 2a9.84 9.84 0 0 0-8.43 14.92L2.05 22l5.2-1.52A9.96 9.96 0 1 0 12.04 2Zm4.34 13.02c-.24-.12-1.4-.69-1.62-.77-.22-.08-.38-.12-.54.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.91-1.18a7.17 7.17 0 0 1-1.32-1.64c-.14-.24-.01-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.47-.4-.4-.54-.41h-.46a.88.88 0 0 0-.63.3c-.22.24-.83.81-.83 1.98s.85 2.3.97 2.46c.12.16 1.67 2.55 4.05 3.58.57.24 1.01.39 1.35.5.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.03.14-1.12-.06-.1-.22-.16-.46-.28Z"/></svg>
                                VIP Alerts — from {{ $applyModalVipText }}
                            </a>
                        @endif
                        @if($applyModalWaUrl)
                            <a href="{{ $applyModalWaUrl }}" target="_blank" rel="noopener" class="d-inline-flex align-items-center gap-2 text-decoration-none fw-semibold" style="color:#25D366;">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" width="20" height="20" alt="WhatsApp"> {{ $applyModalWaLabel }}
                            </a>
                        @endif
                        @if($applyModalTgUrl)
                        <div class="mt-2">
                            <a href="{{ $applyModalTgUrl }}" target="_blank" rel="noopener" class="d-inline-flex align-items-center gap-2 text-decoration-none fw-semibold" style="color:#229ED9;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="#229ED9" xmlns="http://www.w3.org/2000/svg"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L7.065 13.85l-2.947-.924c-.64-.204-.657-.64.136-.954l11.57-4.46c.532-.194.998.13.82.95l-.75-.241z"/></svg>
                                {{ $applyModalTgLabel }}
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div><!-- END APPLY MODAL -->

    <!-- START APPLY MODAL -->
    <div class="modal fade" id="ModalApplyExternalJobForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content apply-job-form">
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body pl-30 pr-30 pt-50">
                    {!! \Botble\JobBoard\Forms\Fronts\ExternalJobApplicationForm::create()->renderForm() !!}
                    <div class="text-center py-3 border-top mt-3">
                        @if($applyModalVipText)
                            <a href="{{ route('public.vip-alerts.plans') }}" class="badge px-3 py-2 mb-2 d-inline-block text-decoration-none" style="background:linear-gradient(135deg,#25d366,#128c4a);color:#fff;font-size:.78rem;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="#fff" style="vertical-align:middle;margin-right:4px;" aria-hidden="true"><path d="M12.04 2a9.84 9.84 0 0 0-8.43 14.92L2.05 22l5.2-1.52A9.96 9.96 0 1 0 12.04 2Zm4.34 13.02c-.24-.12-1.4-.69-1.62-.77-.22-.08-.38-.12-.54.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.91-1.18a7.17 7.17 0 0 1-1.32-1.64c-.14-.24-.01-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.47-.4-.4-.54-.41h-.46a.88.88 0 0 0-.63.3c-.22.24-.83.81-.83 1.98s.85 2.3.97 2.46c.12.16 1.67 2.55 4.05 3.58.57.24 1.01.39 1.35.5.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.03.14-1.12-.06-.1-.22-.16-.46-.28Z"/></svg>
                                VIP Alerts — from {{ $applyModalVipText }}
                            </a>
                        @endif
                        @if($applyModalWaUrl)
                            <a href="{{ $applyModalWaUrl }}" target="_blank" rel="noopener" class="d-inline-flex align-items-center gap-2 text-decoration-none fw-semibold" style="color:#25D366;">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" width="20" height="20" alt="WhatsApp"> {{ $applyModalWaLabel }}
                            </a>
                        @endif
                        @if($applyModalTgUrl)
                        <div class="mt-2">
                            <a href="{{ $applyModalTgUrl }}" target="_blank" rel="noopener" class="d-inline-flex align-items-center gap-2 text-decoration-none fw-semibold" style="color:#229ED9;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="#229ED9" xmlns="http://www.w3.org/2000/svg"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L7.065 13.85l-2.947-.924c-.64-.204-.657-.64.136-.954l11.57-4.46c.532-.194.998.13.82.95l-.75-.241z"/></svg>
                                {{ $applyModalTgLabel }}
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if ($isLoggedIn && $account && !$account->isEmployer())
<div class="modal fade" id="ModalBoostApplication" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-4 text-center">
                <i class="fi fi-rr-rocket-launch fs-1 text-primary mb-2 d-block"></i>
                <h5 class="mb-1">{{ __('Boost Your Application') }}</h5>
                <p class="text-muted fs-14 mb-3">{{ __("Spend credits to appear at the top of the employer's list.") }}</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Credits to bid') }}</label>
                    <input type="number" id="boost-credits-input" class="form-control text-center" min="1" value="1">
                    <small class="text-muted" id="boost-credits-available"></small>
                </div>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Skip') }}</button>
                    <button type="button" id="boost-submit-btn" class="btn btn-primary btn-sm">{{ __('Boost') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    'use strict';
    var boostBaseUrl = '{{ route('public.account.applications.boost', ['id' => '__ID__']) }}';
    var $boostModal  = $('#ModalBoostApplication');
    var currentApplicationId = null;

    window.onApplyBoostAvailable = function (applicationId, credits) {
        currentApplicationId = applicationId;
        $('#boost-credits-input').val(1).attr('max', credits);
        $('#boost-credits-available').text('You have ' + credits + ' credit(s) available.');
        $boostModal.modal('show');
    };

    $boostModal.on('hidden.bs.modal', function () {
        if (currentApplicationId) { window.location.reload(); }
    });

    $('#boost-submit-btn').on('click', function () {
        var credits = parseInt($('#boost-credits-input').val(), 10);
        if (!credits || credits < 1 || !currentApplicationId) { return; }
        var url = boostBaseUrl.replace('__ID__', currentApplicationId);
        var $btn = $(this);
        $btn.prop('disabled', true).addClass('button-loading');
        $.ajax({
            type: 'POST', url: url,
            data: { credits: credits, _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.error) {
                    window.showAlert('alert-danger', window.alertTranslations.errors, res.message);
                } else {
                    window.showAlert('alert-success', window.alertTranslations.success, res.message);
                    currentApplicationId = null;
                    $boostModal.modal('hide');
                    setTimeout(function () { window.location.reload(); }, 1200);
                }
            },
            error: function () { window.showAlert('alert-danger', window.alertTranslations.errors, 'Something went wrong.'); },
            complete: function () { $btn.prop('disabled', false).removeClass('button-loading'); },
        });
    });
})();
</script>
@endif
