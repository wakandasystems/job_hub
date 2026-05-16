@php
    $categoryMeta = [
        0 => ['label' => 'Individual',  'color' => '#3C65F5', 'bg' => '#EEF2FF', 'cta' => 'Get Started Free',     'icon' => 'fi-rr-user'],
        1 => ['label' => 'Individual',  'color' => '#3C65F5', 'bg' => '#EEF2FF', 'cta' => 'Boost My Profile',     'icon' => 'fi-rr-rocket'],
        2 => ['label' => 'Company',     'color' => '#0BA02C', 'bg' => '#E8F5E9', 'cta' => 'Start Hiring Now',     'icon' => 'fi-rr-briefcase'],
        3 => ['label' => 'Company',     'color' => '#0BA02C', 'bg' => '#E8F5E9', 'cta' => 'Scale Up Hiring',      'icon' => 'fi-rr-chart-line-up'],
        4 => ['label' => 'Company',     'color' => '#0BA02C', 'bg' => '#E8F5E9', 'cta' => 'Go Enterprise',        'icon' => 'fi-rr-building'],
        5 => ['label' => 'Recruiter',   'color' => '#7B2FBE', 'bg' => '#F3E8FF', 'cta' => 'Join as a Pro',        'icon' => 'fi-rr-search-alt'],
    ];

    $taglines = [
        0 => 'For job seekers',
        1 => 'For ambitious candidates',
        2 => 'For small businesses',
        3 => 'Most Popular',
        4 => 'For large organisations',
        5 => 'For agencies & recruiters',
    ];
@endphp

<section class="section-box mt-90 mb-60">
    <div class="container">
        <div class="text-center mb-20">
            <span class="pricing-eyebrow">Flexible Plans for Every Ambition</span>
            <h2 class="mt-10 mb-15">
                {!! BaseHelper::clean($shortcode->title) !!}
            </h2>
            <p class="font-lg color-text-paragraph-2" style="max-width:560px;margin:0 auto;">
                {!! BaseHelper::clean($shortcode->subtitle) !!}
            </p>
        </div>

        <div class="row mt-60 g-4 align-items-stretch">
            @foreach ($packages as $package)
                @php
                    $idx  = $loop->index;
                    $meta = $categoryMeta[$idx] ?? $categoryMeta[2];
                    $tag  = $taglines[$idx] ?? '';
                    $isPopular = $idx === 3;
                    $features = $package->formatted_features ?? [];
                @endphp
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="pricing-card @if($isPopular) pricing-card--popular @endif h-100">

                        {{-- Top badge row --}}
                        <div class="pricing-card__header">
                            <span class="pricing-cat-badge" style="color:{{ $meta['color'] }};background:{{ $meta['bg'] }};">
                                <i class="{{ $meta['icon'] }}"></i>
                                {{ $meta['label'] }}
                            </span>
                            @if ($isPopular)
                                <span class="pricing-popular-badge">
                                    <i class="fi-rr-star"></i> Most Popular
                                </span>
                            @else
                                <span class="pricing-tag-label">{{ $tag }}</span>
                            @endif
                        </div>

                        {{-- Plan name & description --}}
                        <h3 class="pricing-card__name">{{ $package->name }}</h3>
                        @if ($package->description)
                            <p class="pricing-card__desc">{{ $package->description }}</p>
                        @endif

                        {{-- Price --}}
                        <div class="pricing-card__price-wrap">
                            <span class="pricing-card__price">
                                @if ($package->price == 0)
                                    Free
                                @else
                                    {{ $package->price_text }}
                                @endif
                            </span>
                            @if ($package->price > 0)
                                <span class="pricing-card__period">/&nbsp;one-time</span>
                            @endif
                        </div>

                        <div class="pricing-card__divider"></div>

                        {{-- Features --}}
                        @if (!empty($features))
                            <ul class="pricing-card__features">
                                @foreach ($features as $feature)
                                    <li>
                                        <span class="pricing-check" style="background:{{ $meta['bg'] }};color:{{ $meta['color'] }};">
                                            <svg width="12" height="12" viewBox="0 0 28 28" fill="none">
                                                <path d="M19 10.5L11.5 18L8.5 15" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- CTA --}}
                        <div class="pricing-card__cta mt-auto">
                            <a
                                class="btn pricing-card__btn @if($isPopular) pricing-card__btn--primary @else pricing-card__btn--outline @endif"
                                href="{{ auth('account')->check() ? route('public.account.packages') : route('public.account.login') }}"
                                style="@if($isPopular) background:{{ $meta['color'] }};border-color:{{ $meta['color'] }}; @else color:{{ $meta['color'] }};border-color:{{ $meta['color'] }}; @endif"
                            >
                                {{ $meta['cta'] }}
                            </a>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>

        {{-- Bottom reassurance strip --}}
        <div class="pricing-reassurance mt-50">
            <span><i class="fi-rr-shield-check"></i> Secure payments</span>
            <span><i class="fi-rr-headset"></i> Dedicated support</span>
            <span><i class="fi-rr-refresh"></i> Cancel anytime</span>
            <span><i class="fi-rr-african"></i> Built for Africa</span>
        </div>
    </div>
</section>
