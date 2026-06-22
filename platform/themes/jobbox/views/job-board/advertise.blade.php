@php
    $groupMeta = [
        'Jobs' => ['icon' => 'fi-rr-briefcase', 'color' => '#530F93'],
        'Companies' => ['icon' => 'fi-rr-building', 'color' => '#05264E'],
        'Candidates' => ['icon' => 'fi-rr-user', 'color' => '#0F9D58'],
        'Blog' => ['icon' => 'fi-rr-document', 'color' => '#E67E22'],
        'Social Media' => ['icon' => 'fi-rr-share', 'color' => '#1877F2'],
        'General' => ['icon' => 'fi-rr-megaphone', 'color' => '#6B7280'],
    ];
@endphp

<section class="section-box ad-advertise mt-50 mb-20">
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-lg-7 text-center">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:76px;height:76px;background:linear-gradient(135deg,#530F93,#05264E);box-shadow:0 12px 24px -8px rgba(83,15,147,.45);">
                    <i class="fi-rr-megaphone" style="font-size:32px;color:#fff;"></i>
                </div>
                <h1 class="section-title mb-3">{{ __('Advertise on Wakanda Jobs') }}</h1>
                <p class="font-md color-text-paragraph-2">
                    {{ __('Promote your brand by placing your own banner ad in front of job seekers and employers across the site. Pick a placement, choose your reach, and go live in minutes.') }}
                </p>
            </div>
        </div>

        {{-- Value props --}}
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10">
                <div class="row g-3 text-center">
                    @foreach([
                        ['icon' => 'fi-rr-eye', 'text' => __('Seen by thousands of visitors every month')],
                        ['icon' => 'fi-rr-target', 'text' => __('Target visitors by country reach')],
                        ['icon' => 'fi-rr-time-fast', 'text' => __('Live within 24 hours of approval')],
                        ['icon' => 'fi-rr-refresh', 'text' => __('Pause, renew, or swap your creative anytime')],
                    ] as $benefit)
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="ad-benefit-box p-3 rounded-3 border h-100 d-flex flex-column align-items-center gap-2">
                                <i class="{{ $benefit['icon'] }} fs-4" style="color:#530F93;"></i>
                                <span class="font-xs">{{ $benefit['text'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if($placements->isEmpty())
            <div class="text-center py-40 color-text-paragraph-2">
                <p class="font-sm">{{ __('No ad placements are available yet. Check back soon.') }}</p>
            </div>
        @else
            <ul class="nav-tabs text-center mb-4" id="adGroupTabs" role="tablist">
                @foreach($groups as $groupName => $groupPlacements)
                    <li class="nav-item d-inline-block" role="presentation">
                        <a href="#ad-group-{{ Str::slug($groupName) }}"
                            id="ad-group-{{ Str::slug($groupName) }}-btn"
                            class="@if($loop->first) active @endif"
                            data-bs-toggle="tab"
                            role="tab"
                            aria-controls="ad-group-{{ Str::slug($groupName) }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            <i class="{{ $groupMeta[$groupName]['icon'] ?? 'fi-rr-megaphone' }} me-1"></i>
                            {{ __($groupName) }}
                            <span class="badge bg-light text-dark ms-1">{{ $groupPlacements->count() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content mb-40" id="adGroupTabContent">
                @foreach($groups as $groupName => $groupPlacements)
                    @php $accent = $groupMeta[$groupName]['color'] ?? '#530F93'; @endphp
                    <div class="tab-pane fade @if($loop->first) show active @endif"
                        id="ad-group-{{ Str::slug($groupName) }}"
                        role="tabpanel"
                        aria-labelledby="ad-group-{{ Str::slug($groupName) }}-btn">
                        <div class="row g-4">
                            @foreach($groupPlacements as $placement)
                                @php
                                    $options = $placementOptions[$placement->id] ?? [];
                                    $cheapest = collect($options)->sortBy('price')->first();
                                @endphp
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="ad-placement-card box-shadow-bdrd-15 h-100 d-flex flex-column">
                                        <div class="d-flex align-items-start gap-3 mb-3">
                                            <div class="ad-placement-icon flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle"
                                                 style="width:44px;height:44px;background:{{ $accent }}1A;">
                                                <i class="{{ $groupMeta[$groupName]['icon'] ?? 'fi-rr-megaphone' }}" style="color:{{ $accent }};font-size:18px;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold fs-6">{{ $placement->name }}</div>
                                                @if($placement->description)
                                                    <p class="color-text-paragraph-2 mb-0 font-sm">{{ $placement->description }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <span class="ad-pill">
                                                <i class="fi-rr-clock me-1"></i>{{ $placement->displayDuration() }}
                                            </span>
                                            @if(count($options) > 1)
                                                <span class="ad-pill">
                                                    <i class="fi-rr-world me-1"></i>{{ __(':count reach options', ['count' => count($options)]) }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between mt-auto pt-3 border-top">
                                            <span class="fw-bold fs-5" style="color:{{ $accent }};">
                                                {{ count($options) > 1 ? __('From :price', ['price' => $cheapest['display']]) : $cheapest['display'] }}
                                            </span>
                                            @if($isLoggedIn)
                                                <button type="button" class="btn btn-default ad-request-btn"
                                                    data-bs-toggle="modal" data-bs-target="#requestAdModal"
                                                    data-action="{{ route('public.account.ads.store', ['placement' => $placement->id]) }}"
                                                    data-name="{{ $placement->name }}"
                                                    data-options="{{ json_encode($options) }}">
                                                    {{ __('Request') }}
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-default ad-request-btn"
                                                    data-bs-toggle="modal" data-bs-target="#guestSignInModal">
                                                    {{ __('Request') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<style>
    .ad-benefit-box {
        width: 100%;
        min-width: 0;
        background-color: #fff;
        border-color: #e0e6f6 !important;
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .ad-benefit-box:hover {
        box-shadow: 0 10px 20px -8px rgba(10, 42, 105, .12);
        transform: translateY(-2px);
    }
    .nav-tabs {
        border: 0;
    }
    .nav-tabs li a {
        cursor: pointer;
    }
    .ad-placement-card {
        width: 100%;
        min-width: 0;
        padding: 24px;
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .ad-placement-card:hover {
        box-shadow: 0 14px 28px -10px rgba(10, 42, 105, .16);
        transform: translateY(-3px);
    }
    .ad-pill {
        display: inline-flex;
        align-items: center;
        background-color: #f5f6fb;
        color: #5b6577;
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .ad-request-btn {
        border-radius: 999px;
        padding: 8px 22px;
    }
    @media (max-width: 576px) {
        .ad-advertise .section-title {
            font-size: 32px !important;
            line-height: 1.3 !important;
            overflow-wrap: break-word;
        }
        .ad-advertise #adGroupTabs {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            justify-content: flex-start;
            padding-bottom: 4px;
        }
        .ad-advertise #adGroupTabs .nav-item {
            flex: 0 0 auto;
        }
        .ad-advertise #adGroupTabs li a {
            white-space: nowrap;
        }
    }
</style>

@if($isLoggedIn)
    {{-- Request ad modal --}}
    <div class="modal modal-blur fade" id="requestAdModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Request Ad Placement') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="requestAdForm" method="POST" action="" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3" id="requestAdPlacementName"></p>

                        <div class="mb-3" id="requestAdReachWrapper">
                            <label class="form-label required">{{ __('Choose your reach') }}</label>
                            <div class="form-text mb-2">
                                {{ __('Your ad will only be shown to visitors browsing from the countries included in the reach you choose.') }}
                            </div>
                            <div id="requestAdReachOptions"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">{{ __('Banner Image') }}</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                            <div class="form-text">{{ __('Recommended: PNG or JPG, max 5MB.') }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Click-through URL') }}</label>
                            <input type="url" name="url" class="form-control" placeholder="https://example.com">
                        </div>
                        <div class="mb-0">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input" name="open_in_new_tab" value="1" checked>
                                <span class="form-check-label">{{ __('Open link in a new tab') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-default">
                            {{ __('Continue to Payment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .reach-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            border: 1px solid #dde1e6;
            border-radius: 10px;
            padding: .75rem 1rem;
            margin-bottom: .6rem;
            cursor: pointer;
            background-color: #fff;
            transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .reach-option:hover {
            border-color: var(--bs-primary, #530F93);
            background-color: rgba(83, 15, 147, 0.04);
        }
        .reach-option.is-selected {
            border-color: var(--bs-primary, #530F93);
            background-color: rgba(83, 15, 147, 0.08);
            box-shadow: 0 0 0 1px var(--bs-primary, #530F93) inset;
        }
        .reach-option input[type="radio"] {
            margin-top: .2rem;
            flex-shrink: 0;
        }
        .reach-option .reach-option-text {
            min-width: 0;
        }
        .reach-option .reach-option-name {
            font-weight: 600;
            line-height: 1.3;
        }
        .reach-option .reach-option-countries {
            font-size: .8rem;
            color: var(--tp-text-body, #6b7280);
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .reach-option .reach-option-price {
            white-space: nowrap;
            flex-shrink: 0;
        }
    </style>
    <script>
        document.querySelectorAll('[data-bs-target="#requestAdModal"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('requestAdForm').action = this.dataset.action;
                document.getElementById('requestAdPlacementName').textContent = '{{ __('Placement') }}: ' + this.dataset.name;

                var options = JSON.parse(this.dataset.options || '[]');
                var wrapper = document.getElementById('requestAdReachWrapper');
                var container = document.getElementById('requestAdReachOptions');
                container.innerHTML = '';

                if (options.length <= 1) {
                    wrapper.classList.add('d-none');
                    if (options.length === 1) {
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'tier_id';
                        hidden.value = options[0].tier_id ?? '';
                        container.appendChild(hidden);
                    }
                    return;
                }

                wrapper.classList.remove('d-none');

                options.forEach(function (option, index) {
                    var row = document.createElement('label');
                    row.className = 'reach-option';
                    if (index === 0) {
                        row.classList.add('is-selected');
                    }

                    var input = document.createElement('input');
                    input.type = 'radio';
                    input.className = 'form-check-input me-2';
                    input.name = 'tier_id';
                    input.value = option.tier_id ?? '';
                    if (index === 0) {
                        input.checked = true;
                    }
                    input.addEventListener('change', function () {
                        container.querySelectorAll('.reach-option').forEach(function (el) {
                            el.classList.remove('is-selected');
                        });
                        row.classList.add('is-selected');
                    });

                    var text = document.createElement('span');
                    text.className = 'reach-option-text';

                    var name = document.createElement('div');
                    name.className = 'reach-option-name';
                    name.textContent = option.name || option.label;
                    text.appendChild(name);

                    if (option.countries) {
                        var countries = document.createElement('div');
                        countries.className = 'reach-option-countries';
                        countries.textContent = option.countries;
                        text.appendChild(countries);
                    }

                    var price = document.createElement('span');
                    price.className = 'fw-semibold reach-option-price';
                    price.style.color = '#530F93';
                    price.textContent = option.display;

                    row.appendChild(input);
                    row.appendChild(text);
                    row.appendChild(price);
                    container.appendChild(row);
                });
            });
        });
    </script>
@else
    {{-- Sign in required modal --}}
    <div class="modal fade" id="guestSignInModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:16px;overflow:hidden;">
                <div class="text-center pt-4 pb-3" style="background:linear-gradient(135deg,#530F93,#05264E);">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:56px;height:56px;background:rgba(255,255,255,.15);">
                        <i class="fi-rr-lock" style="font-size:22px;color:#fff;"></i>
                    </div>
                    <h5 class="modal-title text-white mb-0">{{ __('Sign in to request this ad') }}</h5>
                </div>
                <div class="modal-body text-center px-4 pt-4">
                    <p class="color-text-paragraph-2 mb-0">
                        {{ __('You need an account to request an ad placement, upload your banner, and manage your bookings. Sign in or create a free account to continue — it only takes a minute.') }}
                    </p>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 d-flex gap-2">
                    <a href="{{ route('public.account.register') }}" class="btn btn-outline-primary flex-grow-1">{{ __('Sign Up') }}</a>
                    <a href="{{ route('public.account.login') }}" class="btn btn-default flex-grow-1">{{ __('Sign In') }}</a>
                </div>
            </div>
        </div>
    </div>
@endif
