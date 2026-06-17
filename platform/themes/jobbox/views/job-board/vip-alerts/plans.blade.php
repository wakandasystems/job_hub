<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-7 text-center">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:72px;height:72px;background:linear-gradient(135deg,#25d366,#128c4a);">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path fill="#fff" d="M12.04 2a9.84 9.84 0 0 0-8.43 14.92L2.05 22l5.2-1.52A9.96 9.96 0 1 0 12.04 2Zm0 17.95a8 8 0 0 1-4.08-1.12l-.29-.17-3.08.9.82-3-.19-.3a7.91 7.91 0 1 1 6.82 3.69Zm4.34-5.93c-.24-.12-1.4-.69-1.62-.77-.22-.08-.38-.12-.54.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.91-1.18a7.17 7.17 0 0 1-1.32-1.64c-.14-.24-.01-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.47-.4-.4-.54-.41h-.46a.88.88 0 0 0-.63.3c-.22.24-.83.81-.83 1.98s.85 2.3.97 2.46c.12.16 1.67 2.55 4.05 3.58.57.24 1.01.39 1.35.5.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.03.14-1.12-.06-.1-.22-.16-.46-.28Z"/>
                    </svg>
                </div>
                <h1 class="section-title mb-3">VIP WhatsApp Job Alerts</h1>
                <p class="font-md color-text-paragraph-2">
                    Receive personalised job matches directly on WhatsApp. No searching — we send the jobs to you.
                </p>
            </div>
        </div>

        {{-- Benefit bullets --}}
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8">
                <div class="row g-3 text-center">
                    @foreach([
                        ['icon' => 'fi-rr-target', 'text' => 'Matched to your skills &amp; role'],
                        ['icon' => 'fi-rr-bell', 'text' => 'Instant alerts when new jobs post'],
                        ['icon' => 'fi-rr-world', 'text' => 'Works in any country, any currency'],
                        ['icon' => 'fi-rr-shield-check', 'text' => 'Activate only after admin review'],
                    ] as $benefit)
                        <div class="col-6 col-md-3">
                            <div class="p-3 rounded-3 border h-100 d-flex flex-column align-items-center gap-2">
                                <i class="{{ $benefit['icon'] }} fs-4 text-success"></i>
                                <span class="font-xs">{!! $benefit['text'] !!}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Pricing cards --}}
        <div class="row justify-content-center g-4">
            @foreach($plans as $planKey => $plan)
                @php $isPopular = $plan['badge'] === 'Most Popular'; @endphp
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow h-100 position-relative {{ $isPopular ? 'border-success border-2' : '' }}"
                         style="{{ $isPopular ? 'border:2px solid #25d366!important;' : '' }}">
                        @if($plan['badge'])
                            <span class="badge position-absolute top-0 start-50 translate-middle px-3 py-2"
                                  style="background:{{ $isPopular ? '#25d366' : '#0d6efd' }};color:#fff;font-size:.75rem;">
                                {{ $plan['badge'] }}
                            </span>
                        @endif
                        <div class="card-body p-4 pt-5 d-flex flex-column">
                            <div class="text-center mb-4">
                                <div class="display-5 fw-bold text-dark">{{ $plan['displayCurrency'] }} {{ number_format($plan['displayPrice'], 2) }}</div>
                                <div class="text-muted font-sm">{{ $plan['label'] }}</div>
                            </div>
                            <ul class="list-unstyled mb-4 flex-grow-1">
                                <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>{{ $plan['label'] }} of VIP alerts</li>
                                <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>WhatsApp delivery</li>
                                <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>Custom job filters</li>
                                <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>All countries supported</li>
                                @if($planKey === 'one_time')
                                    <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>Best value — save vs monthly</li>
                                @endif
                            </ul>
                            <a href="{{ route('public.vip-alerts.checkout', $planKey) }}"
                               class="btn w-100 btn-apply-big {{ $isPopular ? 'btn-success' : 'btn-outline-success' }}">
                                Get Started — {{ $plan['displayCurrency'] }} {{ number_format($plan['displayPrice'], 2) }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row justify-content-center mt-5">
            <div class="col-lg-6 text-center">
                <p class="font-sm color-text-paragraph-2">
                    <i class="fi-rr-lock text-success me-1"></i>
                    Secure payment via PayPal &amp; other gateways &middot; Cancel any time &middot; Activation within 24 hours
                </p>
            </div>
        </div>
    </div>
</section>
