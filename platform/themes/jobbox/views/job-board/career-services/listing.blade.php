<section class="section-box mt-50 mb-50">
    <div class="container">

        {{-- Page header --}}
        <div class="text-center mb-50">
            <span class="badge bg-warning text-dark mb-3">Boost your career</span>
            <h2 class="fw-bold mb-3">Professional Career Services</h2>
            <p class="color-text-paragraph-2 mx-auto" style="max-width:600px;">
                CV writing, LinkedIn optimisation &amp; interview coaching — delivered by vetted career coaches within 24–72 hrs.
                Trusted by job seekers across Zambia.
            </p>
            <a href="{{ route('public.career-service.cv-score') }}" class="btn btn-outline-primary mt-2 px-4">
                <i class="fi-rr-star me-2"></i>Score Your CV Free First
            </a>
        </div>

        {{-- Service cards --}}
        <div class="row g-4 mb-50">
            @foreach($services as $key => $svc)
                @php
                    $pricing    = $servicePrices[$key] ?? null;
                    $cardId     = 'pub-svc-' . $key;
                    $isFeatured = $key === 'bundle';
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border cs-pub-card {{ $isFeatured ? 'border-warning' : '' }}"
                         style="border-radius:14px;transition:box-shadow .2s,border-color .2s;">
                        @if($isFeatured)
                            <div class="card-header border-0 pt-3 pb-0 px-4" style="background:transparent;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold text-warning" style="font-size:12px;letter-spacing:.5px;">
                                        ★ MOST POPULAR — SAVE K250
                                    </span>
                                </div>
                            </div>
                        @endif
                        <div class="card-body p-4 d-flex flex-column">

                            {{-- Header --}}
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0
                                             {{ $isFeatured ? 'bg-warning' : 'bg-primary' }}"
                                      style="width:48px;height:48px;">
                                    <i class="{{ $svc['icon'] ?? 'fi-rr-briefcase' }} text-white fs-5"
                                       style="line-height:1;vertical-align:middle;display:inline-flex;align-items:center;justify-content:center;width:1em;height:1em;"></i>
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
                            <p class="color-text-paragraph-2 mb-3 flex-grow-1" style="font-size:13px;line-height:1.6;">
                                {{ $svc['description'] }}
                            </p>

                            {{-- Includes pills (bundles) --}}
                            @if(!empty($svc['includes']))
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    @foreach($svc['includes'] as $item)
                                        <span class="badge bg-light text-dark border" style="font-size:11px;font-weight:500;">
                                            ✓ {{ $item }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Benefits (first 2) --}}
                            @if(!empty($svc['benefits']))
                                <ul class="list-unstyled mb-3" style="font-size:12px;">
                                    @foreach(array_slice($svc['benefits'], 0, 2) as $benefit)
                                        <li class="d-flex align-items-start gap-2 mb-1">
                                            <span class="text-success flex-shrink-0 mt-1" style="font-size:11px;">✓</span>
                                            <span class="color-text-paragraph-2">{{ $benefit }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            {{-- Expand toggle --}}
                            <a class="cs-pub-toggle d-flex align-items-center gap-1 mb-3 text-decoration-none color-brand-1"
                               style="font-size:12px;cursor:pointer;"
                               data-bs-toggle="collapse"
                               href="#{{ $cardId }}-detail"
                               role="button"
                               aria-expanded="false"
                               aria-controls="{{ $cardId }}-detail">
                                <span class="cs-pub-toggle-label">How it works &amp; what you get</span>
                                <svg class="cs-pub-chevron ms-1" width="12" height="12" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                                     style="transition:transform .2s;flex-shrink:0;">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </a>

                            {{-- Collapsible detail --}}
                            <div class="collapse" id="{{ $cardId }}-detail">
                                <div class="border rounded-3 p-3 mb-3 bg-light">
                                    @if(!empty($svc['what_it_is']))
                                        <p class="mb-3 fw-semibold" style="font-size:13px;">{{ $svc['what_it_is'] }}</p>
                                    @endif
                                    <div class="row g-2">
                                        @if(!empty($svc['steps']))
                                            <div class="col-12 col-sm-6">
                                                <div class="fw-semibold mb-2 text-uppercase" style="font-size:11px;color:#555;letter-spacing:.3px;">
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
                                                <div class="fw-semibold mb-2 text-uppercase" style="font-size:11px;color:#555;letter-spacing:.3px;">
                                                    What you receive
                                                </div>
                                                <ul class="list-unstyled mb-3" style="font-size:12px;color:#555;line-height:1.7;">
                                                    @foreach($svc['deliverables'] as $d)
                                                        <li>✓ {{ $d }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            @if(!empty($svc['time']))
                                                <div class="text-muted" style="font-size:11px;">Coach time: {{ $svc['time'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- CTA --}}
                            <a href="{{ route('public.career-service.book', ['service' => $key]) }}"
                               class="btn {{ $isFeatured ? 'btn-warning text-dark' : 'btn-apply' }} w-100 fw-semibold mt-auto">
                                Book Now
                            </a>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Trust bar --}}
        <div class="text-center mb-30">
            <p class="color-text-paragraph-2 mb-1" style="font-size:13px;">
                🔒 Secure payment &nbsp;·&nbsp; Money-back guarantee if not delivered &nbsp;·&nbsp; Powered by Wakanda Jobs
            </p>
            <p class="color-text-paragraph-2" style="font-size:13px;">
                ★ All coaches are vetted professionals with HR and recruitment experience in Zambia
            </p>
        </div>

        {{-- Auth nudge (shown only to guests) --}}
        @guest('account')
            <div class="card border-0 bg-light text-center p-4">
                <p class="mb-3 color-text-paragraph-2">
                    Create a free Wakanda Jobs account to book a service. It only takes 60 seconds.
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ route('public.account.register') }}" class="btn btn-apply px-5">Create Free Account</a>
                    <a href="{{ route('public.account.login') }}" class="btn btn-outline-primary px-5">Log In</a>
                </div>
            </div>
        @endguest

    </div>
</section>

<style>
.cs-pub-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,.10);
    border-color: var(--primary-color) !important;
}
.cs-pub-card.border-warning:hover {
    border-color: #ffc107 !important;
    box-shadow: 0 6px 24px rgba(255,193,7,.20);
}
.cs-pub-toggle[aria-expanded="true"] .cs-pub-chevron {
    transform: rotate(180deg);
}
.cs-pub-toggle[aria-expanded="true"] .cs-pub-toggle-label {
    display: none;
}
.cs-pub-toggle[aria-expanded="true"]::before {
    content: "Hide details";
    font-size: 12px;
}
</style>
