@php
    $audienceMeta = [
        0 => ['label' => 'For Individuals', 'cta' => 'Get Started Free'],
        1 => ['label' => 'For Individuals', 'cta' => 'Boost My Profile'],
        2 => ['label' => 'For Companies',   'cta' => 'Start Hiring Now'],
        3 => ['label' => 'For Companies',   'cta' => 'Scale Up Hiring'],
        4 => ['label' => 'For Companies',   'cta' => 'Go Enterprise'],
        5 => ['label' => 'For Recruiters',  'cta' => 'Join as a Pro'],
    ];
    $maxVisible = 6;
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
                <div class="row align-items-stretch">
                    @foreach ($packages as $package)
                        @php
                            $idx      = $loop->index;
                            $meta     = $audienceMeta[$idx] ?? $audienceMeta[2];
                            $popular  = $idx === 3;
                            $features = $package->formatted_features ?? [];
                            $visible  = array_slice($features, 0, $maxVisible);
                            $hidden   = array_slice($features, $maxVisible);
                            $color    = theme_option('primary_color', '#3C65F5');
                            $modalId  = 'pricing-modal-' . $package->id;
                        @endphp

                        <div class="col-xl-4 col-lg-6 col-md-6 d-flex">
                            <div class="box-pricing-item pricing-card-equal @if($popular) most-popular @endif w-100"
                                 @if($popular) style="background:{{ $color }};" @endif>

                                {{-- Audience badge --}}
                                <div class="pricing-badge-row mb-10">
                                    <span class="pricing-audience-badge @if($popular) pricing-audience-badge--light @endif">
                                        {{ $meta['label'] }}
                                    </span>
                                    @if ($popular)
                                        <span class="pricing-hot-badge">⭐ Most Popular</span>
                                    @endif
                                </div>

                                <h3>{{ $package->name }}</h3>

                                @if ($package->description)
                                    <p class="text-desc-package pricing-desc mt-5 mb-0">{{ $package->description }}</p>
                                @endif

                                <div class="box-info-price">
                                    <span class="text-price @if(!$popular) color-brand-2 @endif">
                                        @if ($package->price == 0) Free @else {{ $package->price_text }} @endif
                                    </span>
                                </div>

                                <div class="border-bottom mb-30"></div>

                                {{-- Feature list (capped at $maxVisible) --}}
                                <ul class="list-package-feature pricing-features-list">
                                    @foreach ($visible as $feature)
                                        <li>
                                            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle opacity="0.1" cx="14" cy="14" r="14" fill="{{ $popular ? '#fff' : $color }}"/>
                                                <path d="M19 10.5L11.5 18L8.5 15" stroke="{{ $popular ? '#fff' : $color }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- See all link (only when features overflow) --}}
                                @if (count($hidden) > 0)
                                    <div class="mb-25">
                                        <button type="button"
                                                class="pricing-see-all @if($popular) pricing-see-all--light @endif"
                                                data-bs-toggle="modal"
                                                data-bs-target="#{{ $modalId }}">
                                            <span class="pricing-see-all__label">
                                                <svg width="14" height="14" viewBox="0 0 28 28" fill="none">
                                                    <circle opacity="0.2" cx="14" cy="14" r="14" fill="currentColor"/>
                                                    <path d="M9 14h10M14 9l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                View {{ count($hidden) }} more feature{{ count($hidden) > 1 ? 's' : '' }}
                                            </span>
                                            <span class="pricing-see-all__arrow">›</span>
                                        </button>
                                    </div>
                                @endif

                                {{-- CTA pinned to bottom --}}
                                <div class="mt-auto pt-10">
                                    <a class="btn btn-border @if($popular) btn-white @endif"
                                       href="{{ auth('account')->check() ? route('public.account.packages') : route('public.account.login') }}">
                                        {{ $meta['cta'] }}
                                    </a>
                                </div>

                            </div>
                        </div>

                        {{-- Modal (rendered once per package, outside card) --}}
                        @if (count($hidden) > 0)
                            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content pricing-modal">
                                        <div class="modal-header pricing-modal__header"
                                             @if($popular) style="background:{{ $color }};" @endif>
                                            <div>
                                                <span class="pricing-audience-badge @if($popular) pricing-audience-badge--light @else @endif" style="@if(!$popular) background:rgba(60,101,245,.08);color:{{ $color }}; @endif">
                                                    {{ $meta['label'] }}
                                                </span>
                                                <h5 class="modal-title pricing-modal__title @if($popular) text-white @endif mt-5" id="{{ $modalId }}-label">
                                                    {{ $package->name }} — All Features
                                                </h5>
                                            </div>
                                            <button type="button" class="btn-close @if($popular) btn-close-white @endif" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body pricing-modal__body">
                                            <ul class="pricing-modal__list">
                                                @foreach ($features as $feature)
                                                    <li>
                                                        <svg width="22" height="22" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <circle opacity="0.1" cx="14" cy="14" r="14" fill="{{ $color }}"/>
                                                            <path d="M19 10.5L11.5 18L8.5 15" stroke="{{ $color }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        {{ $feature }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <div class="modal-footer pricing-modal__footer">
                                            <a class="btn btn-default" style="background:{{ $color }};color:#fff;border-color:{{ $color }};"
                                               href="{{ auth('account')->check() ? route('public.account.packages') : route('public.account.login') }}">
                                                {{ $meta['cta'] }}
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
