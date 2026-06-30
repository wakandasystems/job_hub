@php
    Theme::layout('default');
@endphp

<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="box-border-single px-4 py-5 rounded-4 text-center">
                    <span class="btn btn-tag mb-3">Request Received</span>
                    <h1 class="mb-3">We’ve received your onboarding request.</h1>
                    <p class="text-muted mb-3">
                        {{ $lead->salesAgent?->name ?: 'Our team' }} shared the <strong>{{ $lead->campaign?->name ?: $lead->resolvedProductLabel() }}</strong> offer with you.
                        Our admin team will contact you on <strong>{{ $lead->candidate_phone }}</strong> to continue the onboarding.
                    </p>
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="small text-muted">Requested Product</div>
                        <div class="fw-bold">{{ $lead->resolvedProductLabel() }}</div>
                        @if ($lead->promo_price)
                            <div class="mt-1">{{ $lead->promo_price }}@if($lead->promo_original_price) <span class="text-decoration-line-through text-muted ms-1">{{ $lead->promo_original_price }}</span>@endif</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
