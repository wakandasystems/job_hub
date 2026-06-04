@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
<div>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h3 class="mt-0 mb-0 color-brand-1">{{ __('Career Services') }}</h3>
        <span class="badge bg-warning text-dark">Boost your career</span>
    </div>
    <p class="color-text-paragraph-2 font-sm mb-30">
        Professional CV writing, LinkedIn optimisation &amp; interview coaching —
        delivered by vetted career coaches within 24–72 hrs.
        <strong>Trusted by job seekers across Zambia.</strong>
    </p>

    {{-- Free AI CV Score banner --}}
    <div class="card border-0 shadow-sm mb-30" style="background:linear-gradient(135deg,#f0f7ff 0%,#e8f4fd 100%);">
        <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h5 class="mb-1 fw-semibold">{{ __('Free AI CV Score') }}</h5>
                <p class="mb-0 color-text-paragraph-2 font-sm">{{ __('Score your CV first, then book a human review only if it needs work.') }}</p>
            </div>
            @if($account->resume)
                <button type="button" class="btn btn-apply px-4"
                    data-bs-toggle="modal" data-bs-target="#cvScoreModal">
                    {{ __('Score My CV') }}
                </button>
            @else
                <a href="{{ route('public.career-service.cv-score') }}" class="btn btn-apply px-4">
                    {{ __('Score My CV') }}
                </a>
            @endif
        </div>
    </div>

    {{-- CV source modal --}}
    @if($account->resume)
        <div class="modal fade" id="cvScoreModal" tabindex="-1" aria-labelledby="cvScoreModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-semibold" id="cvScoreModalTitle">{{ __('Which CV should we score?') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-2 pb-4 px-4">
                        <p class="text-muted font-sm mb-4">{{ __('We found a CV on your profile. Score it instantly, or upload a different one.') }}</p>
                        <div class="d-flex flex-column gap-3">
                            <form method="POST" action="{{ route('public.career-service.cv-score.profile') }}">
                                @csrf
                                <button type="submit" class="btn btn-apply w-100 text-start d-flex align-items-center gap-3 px-4 py-3">
                                    <i class="fi-rr-document fs-4 flex-shrink-0"></i>
                                    <div>
                                        <div class="fw-semibold">{{ __('Score my profile CV') }}</div>
                                        <div class="font-xs opacity-75">{{ $account->resumeName }}</div>
                                    </div>
                                </button>
                            </form>
                            <a href="{{ route('public.career-service.cv-score') }}"
                               class="btn btn-outline-primary w-100 text-start d-flex align-items-center gap-3 px-4 py-3"
                               data-bs-dismiss="modal">
                                <i class="fi-rr-upload fs-4 flex-shrink-0"></i>
                                <div>
                                    <div class="fw-semibold">{{ __('Upload or paste a different CV') }}</div>
                                    <div class="font-xs opacity-75">{{ __('Choose a file or paste CV text') }}</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Service cards --}}
    <div class="row g-4 mb-40">
        @foreach($services as $key => $svc)
            @php
                $pricing  = $servicePrices[$key] ?? null;
                $cardId   = 'svc-' . $key;
                $isFeatured = $key === 'bundle';
            @endphp
            <div class="col-md-6">
                <div class="card h-100 border career-service-card {{ $isFeatured ? 'border-warning' : '' }}"
                     style="border-radius:14px;">
                    @if($isFeatured)
                        <div class="card-header border-0 pt-3 pb-0 px-4" style="background:transparent;">
                            <span class="fw-semibold text-warning" style="font-size:12px;letter-spacing:.5px;">&#9733; MOST POPULAR — SAVE K250</span>
                        </div>
                    @endif
                    <div class="card-body p-4">
                        {{-- Header row --}}
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0
                                         {{ $isFeatured ? 'bg-warning' : 'bg-primary' }}"
                                  style="width:48px;height:48px;">
                                <i class="{{ $svc['icon'] ?? 'fi-rr-briefcase' }} text-white fs-5"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="fw-bold fs-6 mb-1">{{ $svc['name'] }}</div>
                                @if(!empty($svc['badge']))
                                    <span class="badge {{ $svc['badge'] === 'Premium' ? 'bg-dark' : 'bg-warning text-dark' }}" style="font-size:10px;">
                                        {{ $svc['badge'] }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-bold color-brand-1 fs-5 lh-1">
                                    {{ $pricing['display'] ?? ('K' . number_format($svc['price'])) }}
                                </div>
                                <div class="color-text-paragraph-2 font-xs mt-1">{{ $svc['delivery'] }}</div>
                            </div>
                        </div>

                        {{-- Description --}}
                        <p class="color-text-paragraph-2 mb-3" style="font-size:13px;line-height:1.6;">
                            {{ $svc['description'] }}
                        </p>

                        {{-- Includes pills (bundles) — plain ✓ instead of icon font --}}
                        @if(!empty($svc['includes']))
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                @foreach($svc['includes'] as $item)
                                    <span class="badge bg-light text-dark border" style="font-size:11px;font-weight:500;">&#10003; {{ $item }}</span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Benefits quick-view — plain ✓ instead of icon font --}}
                        @if(!empty($svc['benefits']))
                            <ul class="list-unstyled mb-3" style="font-size:12px;">
                                @foreach(array_slice($svc['benefits'], 0, 2) as $benefit)
                                    <li class="d-flex align-items-start gap-2 mb-1">
                                        <span class="text-success fw-bold flex-shrink-0" style="margin-top:1px;">&#10003;</span>
                                        <span class="color-text-paragraph-2">{{ $benefit }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- Expand toggle --}}
                        <button type="button"
                                class="cs-toggle btn btn-link p-0 font-xs color-brand-1 d-inline-flex align-items-center gap-1 mb-3 text-decoration-none border-0 shadow-none"
                                data-cs-target="#{{ $cardId }}-detail"
                                aria-expanded="false">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <span class="cs-label-closed">How it works &amp; what you get</span>
                            <span class="cs-label-open" style="display:none;">Hide details</span>
                            <svg class="cs-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                                 style="flex-shrink:0;transition:transform .25s;">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>

                        {{-- Collapsible detail panel --}}
                        <div id="{{ $cardId }}-detail" style="display:none;">
                            <div class="border rounded-3 p-3 mb-3 bg-light">
                                @if(!empty($svc['what_it_is']))
                                    <p class="mb-3 fw-semibold" style="font-size:13px;">{{ $svc['what_it_is'] }}</p>
                                @endif
                                <div class="row g-3">
                                    @if(!empty($svc['steps']))
                                        <div class="col-12 col-sm-6">
                                            <div class="fw-semibold mb-2 text-uppercase" style="font-size:11px;letter-spacing:.3px;color:#555;">
                                                How it works
                                            </div>
                                            <ol class="ps-3 mb-0" style="font-size:12px;color:#555;line-height:1.7;">
                                                @foreach($svc['steps'] as $step)
                                                    <li>{{ $step }}</li>
                                                @endforeach
                                            </ol>
                                        </div>
                                    @endif
                                    <div class="col-12 col-sm-6">
                                        @if(!empty($svc['deliverables']))
                                            <div class="fw-semibold mb-2 text-uppercase" style="font-size:11px;letter-spacing:.3px;color:#555;">
                                                What you receive
                                            </div>
                                            <ul class="list-unstyled mb-3" style="font-size:12px;color:#555;line-height:1.7;">
                                                @foreach($svc['deliverables'] as $deliverable)
                                                    <li>&#10003; {{ $deliverable }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if(!empty($svc['benefits']) && count($svc['benefits']) > 2)
                                            <div class="fw-semibold mb-2 text-uppercase" style="font-size:11px;letter-spacing:.3px;color:#555;">
                                                Why it matters
                                            </div>
                                            <ul class="list-unstyled mb-0" style="font-size:12px;color:#555;line-height:1.7;">
                                                @foreach(array_slice($svc['benefits'], 2) as $benefit)
                                                    <li>&#9733; {{ $benefit }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if(!empty($svc['time']))
                                            <div class="mt-2 text-muted" style="font-size:11px;">
                                                Coach time: {{ $svc['time'] }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CTA --}}
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('public.career-service.checkout', ['service' => $key, 'candidate' => $account->slug]) }}"
                               class="btn {{ $isFeatured ? 'btn-warning text-dark' : 'btn-apply' }} px-4 fw-semibold">
                                Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Trust bar --}}
    <div class="text-center mb-40">
        <p class="color-text-paragraph-2 mb-2" style="font-size:12px;">
            &#128274; Secure payment &nbsp;&middot;&nbsp; Money-back guarantee if not delivered &nbsp;&middot;&nbsp; Powered by Wakanda Jobs
        </p>
        <p class="color-text-paragraph-2" style="font-size:12px;">
            &#9733; All coaches are vetted professionals with HR and recruitment experience in Zambia
        </p>
    </div>

    {{-- My Orders --}}
    @if($myOrders->isNotEmpty())
        <div class="mt-10">
            <h5 class="fw-semibold mb-20">My Orders</h5>
            <div class="table-responsive">
                <table class="table table-borderless align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="font-sm fw-semibold">Ref</th>
                            <th class="font-sm fw-semibold">Service</th>
                            <th class="font-sm fw-semibold">Amount</th>
                            <th class="font-sm fw-semibold">Payment</th>
                            <th class="font-sm fw-semibold">Delivery</th>
                            <th class="font-sm fw-semibold">Date</th>
                            <th class="font-sm fw-semibold"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($myOrders as $order)
                            <tr>
                                <td class="font-xs text-muted">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td class="font-sm">{{ $order->service_name }}</td>
                                <td class="font-sm fw-semibold">{{ $order->currency }} {{ number_format($order->amount, 2) }}</td>
                                <td>
                                    @php
                                        $badge = match($order->status) {
                                            'paid'      => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            'refunded'  => 'bg-warning',
                                            default     => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td>
                                    @php
                                        $deliveryBadge = match($order->delivery_status) {
                                            'delivered'          => 'bg-success',
                                            'in_progress'        => 'bg-primary',
                                            'assigned'           => 'bg-info',
                                            'revision_requested' => 'bg-warning',
                                            'cancelled'          => 'bg-danger',
                                            default              => 'bg-secondary',
                                        };
                                        $deliveryLabels = [
                                            'unassigned'         => 'Pending',
                                            'assigned'           => 'Assigned',
                                            'in_progress'        => 'In Progress',
                                            'delivered'          => 'Delivered',
                                            'revision_requested' => 'Revision',
                                            'cancelled'          => 'Cancelled',
                                        ];
                                    @endphp
                                    <span class="badge {{ $deliveryBadge }}">{{ $deliveryLabels[$order->delivery_status] ?? ucfirst($order->delivery_status) }}</span>
                                </td>
                                <td class="font-xs text-muted">{{ $order->created_at->format('d M Y') }}</td>
                                <td>
                                    @if($order->reviewed_cv_path && $order->delivery_status === 'delivered')
                                        <a href="{{ route('public.career-service.download-reviewed-cv', ['order' => $order->id]) }}"
                                           class="btn btn-outline-success btn-sm py-1 px-2 font-xs">
                                            Download Reviewed CV
                                        </a>
                                    @elseif($order->status === 'paid' && !$order->candidate_cv_path && !in_array($order->service_type, ['interview_coaching', 'career_consultation']))
                                        <a href="{{ route('public.career-service.thanks', ['order' => $order->id]) }}"
                                           class="btn btn-outline-warning btn-sm py-1 px-2 font-xs">
                                            Upload CV
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<style>
.career-service-card { transition: box-shadow .2s, border-color .2s; }
.career-service-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,.10);
    border-color: var(--primary-color) !important;
}
.career-service-card.border-warning:hover {
    border-color: #ffc107 !important;
    box-shadow: 0 6px 24px rgba(255,193,7,.20);
}
/* Toggle button base reset */
.cs-toggle { color: var(--primary-color); }
.cs-toggle:hover, .cs-toggle:focus { color: var(--primary-color); box-shadow: none !important; }
</style>

<script>
(function () {
    function initToggles() {
        document.querySelectorAll('.cs-toggle').forEach(function (btn) {
            var panel = document.querySelector(btn.getAttribute('data-cs-target'));
            if (!panel) return;
            btn.addEventListener('click', function () {
                var open = panel.classList.contains('cs-open');
                var labelClosed = btn.querySelector('.cs-label-closed');
                var labelOpen   = btn.querySelector('.cs-label-open');
                var chevron     = btn.querySelector('.cs-chevron');
                if (open) {
                    panel.classList.remove('cs-open');
                    panel.style.display = 'none';
                    if (labelClosed) labelClosed.style.display = 'inline';
                    if (labelOpen)   labelOpen.style.display   = 'none';
                    if (chevron)     chevron.style.transform   = 'rotate(0deg)';
                    btn.setAttribute('aria-expanded', 'false');
                } else {
                    panel.classList.add('cs-open');
                    panel.style.display = 'block';
                    if (labelClosed) labelClosed.style.display = 'none';
                    if (labelOpen)   labelOpen.style.display   = 'inline';
                    if (chevron)     chevron.style.transform   = 'rotate(180deg)';
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToggles);
    } else {
        initToggles();
    }
}());
</script>

@endsection
