@php
    $audienceMeta = [
        0 => ['label' => 'For Individuals',  'cta' => 'Get Started Free'],
        1 => ['label' => 'For Individuals',  'cta' => 'Boost My Profile'],
        2 => ['label' => 'For Companies',    'cta' => 'Start Hiring Now'],
        3 => ['label' => 'For Companies',    'cta' => 'Scale Up Hiring'],
        4 => ['label' => 'For Companies',    'cta' => 'Go Enterprise'],
        5 => ['label' => 'For Recruiters',   'cta' => 'Join as a Pro'],
    ];
@endphp

<section class="section-box mt-90 mb-50">
    <div class="container">
        <h2 class="text-center mb-15">
            {!! BaseHelper::clean($shortcode->title) !!}
        </h2>
        <div class="font-lg color-text-paragraph-2 text-center">
            {!! BaseHelper::clean($shortcode->subtitle) !!}
        </div>
        <div class="max-width-price">
            <div class="block-pricing mt-70">
                <div class="row align-items-center">
                    @foreach ($packages as $package)
                        @php
                            $idx      = $loop->index;
                            $meta     = $audienceMeta[$idx] ?? $audienceMeta[2];
                            $popular  = $idx === 3;
                            $features = $package->formatted_features ?? [];
                            $color    = theme_option('primary_color', '#3C65F5');
                        @endphp
                        <div class="col-xl-4 col-lg-6 col-md-6">
                            <div class="box-pricing-item @if($popular) most-popular @endif"
                                 @if($popular) style="background: {{ $color }};" @endif>

                                {{-- Audience badge --}}
                                <div class="mb-10">
                                    <span class="pricing-audience-badge @if($popular) pricing-audience-badge--light @endif">
                                        {{ $meta['label'] }}
                                    </span>
                                    @if ($popular)
                                        <span class="pricing-hot-badge ms-2">⭐ Most Popular</span>
                                    @endif
                                </div>

                                <h3>{{ $package->name }}</h3>

                                @if ($package->description)
                                    <p class="text-desc-package mt-5 mb-0">{{ $package->description }}</p>
                                @endif

                                <div class="box-info-price">
                                    <span class="text-price @if(!$popular) color-brand-2 @endif">
                                        @if ($package->price == 0) Free @else {{ $package->price_text }} @endif
                                    </span>
                                </div>

                                <div class="border-bottom mb-30"></div>

                                <ul class="list-package-feature">
                                    @foreach ($features as $feature)
                                        <li>
                                            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle opacity="0.1" cx="14" cy="14" r="14" fill="{{ $popular ? '#fff' : $color }}"/>
                                                <path d="M19 10.5L11.5 18L8.5 15" stroke="{{ $popular ? '#fff' : $color }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>

                                <div>
                                    <a class="btn btn-border @if($popular) btn-white @endif"
                                       href="{{ auth('account')->check() ? route('public.account.packages') : route('public.account.login') }}">
                                        {{ $meta['cta'] }}
                                    </a>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
