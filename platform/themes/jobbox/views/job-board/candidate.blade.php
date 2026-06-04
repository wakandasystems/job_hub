@php
    Theme::asset()->usePath()->add('css-bar-rating', 'plugins/jquery-bar-rating/themes/css-stars.css');
    Theme::asset()->container('footer')->usePath()->add('jquery-bar-rating-js', 'plugins/jquery-bar-rating/jquery.barrating.min.js');

    Theme::set('pageTitle', $candidate->name);

    $coverImage = null;

    if ($candidate->getMetaData('cover_image', true)) {
        $coverImage = RvMedia::getImageUrl($candidate->getMetaData('cover_image', true));
    } elseif (theme_option('background_cover_candidate_default')) {
        $coverImage = RvMedia::getImageUrl(theme_option('background_cover_candidate_default'));
    }

    // Paywall access logic
    $isEmployer    = $isEmployer    ?? ($account && $account->isEmployer());
    $hasRevealed   = $hasRevealed   ?? false;
    $canRevealFree = $canRevealFree ?? false;
    $revealCost    = $revealCost    ?? (int) setting('cv_reveal_credit_cost', 1);
    $revealUrl     = $revealUrl     ?? null;

    // Full access: own profile, or employer who has already revealed / has free access
    $isOwnProfile = $account && $account->getKey() === $candidate->getKey();
    $hasAccess = $isOwnProfile || ($isEmployer && ($hasRevealed || $canRevealFree));

    $revealPriceLabel = setting('cv_reveal_price_label', '');
    $candidateDisplayName = \Botble\JobBoard\Supports\ProfileContactGuard::obscure($candidate->name);
    $candidateDescription = \Botble\JobBoard\Supports\ProfileContactGuard::obscure($candidate->description);
    $shareUrl = $candidate->url ?: url()->current();
@endphp

<style>
.profile-paywall-wrap {
    position: relative;
    overflow: hidden;
    border-radius: 10px;
}
.profile-paywall-blur {
    filter: blur(5px);
    user-select: none;
    pointer-events: none;
    min-height: 120px;
    padding: 16px;
    background: #f8f9fc;
    border-radius: 8px;
}
.profile-paywall-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(2px);
    border-radius: 10px;
    z-index: 2;
}
.profile-paywall-box {
    background: #fff;
    border: 1.5px solid #e0e6f7;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(60,101,245,.10);
    padding: 28px 32px;
    max-width: 460px;
    width: 100%;
    text-align: center;
}
.profile-paywall-box .paywall-icon {
    font-size: 2.2rem;
    margin-bottom: 10px;
    color: #3c65f5;
}
.profile-paywall-box h5 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #05264e;
    margin-bottom: 8px;
}
.profile-paywall-box .paywall-perks {
    list-style: none;
    padding: 0;
    margin: 0 0 18px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 5px 16px;
    text-align: left;
}
.profile-paywall-box .paywall-perks li {
    font-size: 13px;
    color: #4f5e64;
    display: flex;
    align-items: center;
    gap: 6px;
}
.profile-paywall-box .paywall-perks li i {
    color: #3c65f5;
    font-size: 12px;
    flex-shrink: 0;
}
.profile-paywall-plans {
    display: flex;
    gap: 12px;
    margin-bottom: 18px;
}
.paywall-plan-card {
    flex: 1;
    border: 1.5px solid #e0e6f7;
    border-radius: 10px;
    padding: 14px 12px;
    text-align: center;
    font-size: 13px;
    color: #4f5e64;
    background: #f8f9fc;
}
.paywall-plan-card.featured {
    border-color: #3c65f5;
    background: #eef2ff;
}
.paywall-plan-card .plan-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #3c65f5;
    display: block;
    margin: 4px 0;
}
.paywall-plan-card .plan-label {
    font-size: 11px;
    color: #8a94a6;
    display: block;
}
.candidate-share-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: flex-end;
}
.candidate-share-actions .btn {
    white-space: nowrap;
}
@media (max-width: 480px) {
    .profile-paywall-box { padding: 20px 16px; }
    .profile-paywall-plans { flex-direction: column; }
    .profile-paywall-box .paywall-perks { grid-template-columns: 1fr; }
}
</style>

<section class="section-box-2">
    @if (is_plugin_active('ads'))
        {!! apply_filters('ads_render', null, 'candidate_before', ['class' => 'my-2 text-center']) !!}
    @endif

    <div class="container">
        <div class="banner-hero banner-image-single">
            @if ($coverImage)
                <div class="wrap-cover-image">
                    <img src="{{ $coverImage }}" alt="{{ $candidateDisplayName }}">
                </div>
            @endif
        </div>
        <div class="box-company-profile">
            <div class="image-candidate">
                <div class="position-relative d-inline-block">
                    <img src="{{ $candidate->avatar_thumb_url }}" alt="{{ $candidateDisplayName }}" >
                    {!! $candidate->wakandaBadgeHtml() !!}
                </div>
            </div>
            <div class="row mt-30">
                <div class="col-lg-8 col-md-12">
                    <h5 class="f-18">{{ $candidateDisplayName }}
                        <span class="card-location font-regular ml-20">{{ $candidate->address }}</span>
                    </h5>
                    <p class="mt-0 font-md color-text-paragraph-2 mb-15">{!! BaseHelper::clean($candidateDescription) !!}</p>
                </div>

                @php $resumeAvailable = $hasAccess && ! $candidate->hide_cv && $candidate->resume; @endphp
                <div class="col-lg-4 col-md-12 text-lg-end">
                    <div class="candidate-share-actions mt-10 mt-lg-0">
                        <button type="button"
                            class="btn btn-outline-primary btn-apply-big"
                            id="candidate-share-btn"
                            data-share-url="{{ $shareUrl }}"
                            data-share-title="{{ $candidateDisplayName }}">
                            <i class="fi-rr-share me-1"></i>{{ __('Share Profile') }}
                        </button>

                    @if($resumeAvailable)
                            <a class="btn btn-download-icon btn-apply btn-apply-big" href="{{ $candidate->resumeDownloadUrl }}">
                                {{ __('Download CV') }}
                            </a>
                    @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="border-bottom pt-10 pb-10"></div>
    </div>
</section>

<section class="section-box mt-50">
    <div class="container">
        <div class="row">
            {{-- Main content column --}}
            <div class="col-lg-8 col-md-12 col-sm-12 col-12">
                @if($hasAccess)
                    {{-- Full profile content for paying employers --}}
                    <div class="content-single">
                        <div class="tab-content">
                            @if($candidate->bio)
                                <div class="tab-pane fade active show mb-5" id="tab-short-bio" role="tabpanel">
                                    <h4>{{ __('About Me') }}</h4>
                                    {!! BaseHelper::clean(\Botble\JobBoard\Supports\ProfileContactGuard::obscure($candidate->bio)) !!}
                                </div>
                            @endif

                            @if($countEducation = $educations->count())
                                <div class="candidate-education-details mt-4 pt-3">
                                    <h4 class="fs-17 fw-bold mb-0">{{ __('Education') }}</h4>
                                    @foreach($educations as $education)
                                        <div class="candidate-education-content mt-4 d-flex">
                                            <div class="circle flex-shrink-0 bg-soft-primary">{{ $education->specialized ? strtoupper(substr($education->specialized, 0, 1)) : 'E' }}</div>
                                            <div class="ms-4">
                                                @if ($education->specialized)
                                                    <h6 class="fs-16 mb-1">{{ $education->specialized }}</h6>
                                                @endif
                                                <p class="mb-2 text-muted">{{ $education->school }} -
                                                    ({{ $education->started_at->format('Y') }} -
                                                    {{ $education->ended_at ? $education->ended_at->format('Y') : __('Now') }})
                                                </p>
                                                <p class="text-muted">{!! BaseHelper::clean(\Botble\JobBoard\Supports\ProfileContactGuard::obscure($education->description)) !!}</p>
                                            </div>
                                            @if ($countEducation >= 1 && ! $loop->last)
                                                <span class="line"></span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if($countExperience = $experiences->count())
                                <div class="candidate-education-details mt-4 pt-3">
                                    <h4 class="fs-17 fw-bold mb-0">{{ __('Experience') }}</h4>
                                    @foreach($experiences as $experience)
                                        <div class="candidate-education-content mt-4 d-flex">
                                            <div class="circle flex-shrink-0 bg-soft-primary">{{ $experience->position ? strtoupper(substr($experience->position, 0, 1)) : '' }}</div>
                                            <div class="ms-4">
                                                @if ($experience->position)
                                                    <h6 class="fs-16 mb-1">{{ $experience->position }}</h6>
                                                @endif
                                                <p class="mb-2 text-muted">{{ $experience->company }} -
                                                    ({{ $experience->started_at->format('Y') }} -
                                                    {{ $experience->ended_at ? $experience->ended_at->format('Y') : __('Now') }})
                                                </p>
                                                <p class="text-muted">{!! BaseHelper::clean(\Botble\JobBoard\Supports\ProfileContactGuard::obscure($experience->description)) !!}</p>
                                            </div>
                                            @if ($countExperience >= 1 && ! $loop->last)
                                                <span class="line"></span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if(JobBoardHelper::isEnabledReview())
                            <div class="mt-4 pt-3 position-relative review-listing" @style(['display: none' => $candidate->reviews_count < 1])>
                                <h6 class="fs-17 fw-semibold mb-3">{{ __(":candidate's Reviews", ['candidate' => $candidateDisplayName]) }}</h6>
                                <div class="spinner-overflow"></div>
                                <div class="half-circle-spinner" style="display: none;position: absolute;top: 70%;left: 50%;">
                                    <div class="circle circle-1"></div>
                                    <div class="circle circle-2"></div>
                                </div>
                                <div class="review-list">
                                    @include(Theme::getThemeNamespace('views.job-board.partials.review-load'), ['reviews' => $candidate->reviews])
                                </div>
                            </div>

                            @include(Theme::getThemeNamespace('views.job-board.partials.review-form'), [
                                'reviewable' => $candidate,
                                'canReview'  => $canReview,
                            ])
                        @endif
                    </div>
                @else
                    {{-- Paywall overlay --}}
                    <div class="profile-paywall-wrap" style="min-height:340px;">
                        <div class="profile-paywall-blur">
                            <h4>{{ __('About Me') }}</h4>
                            <p>{{ __('This candidate has provided a detailed professional bio, full work history, and educational background.') }}</p>
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.</p>
                            <h5 class="mt-4">{{ __('Experience') }}</h5>
                            <p>Senior ████████ at ████████████ · 2021–Present</p>
                            <p>███████ at ████████ · 2018–2021</p>
                            <h5 class="mt-4">{{ __('Education') }}</h5>
                            <p>████████████ · ████████ University · 2014–2018</p>
                        </div>
                        <div class="profile-paywall-overlay">
                            <div class="profile-paywall-box">
                                <div class="paywall-icon"><i class="fi-rr-lock"></i></div>
                                <h5>{{ __('Unlock Full Profile') }}</h5>
                                <p class="font-sm color-text-paragraph-2 mb-15">{{ __('Get access to this candidate\'s complete profile:') }}</p>
                                <ul class="paywall-perks">
                                    <li><i class="fi-rr-check"></i>{{ __('Full bio') }}</li>
                                    <li><i class="fi-rr-check"></i>{{ __('Work history') }}</li>
                                    <li><i class="fi-rr-check"></i>{{ __('Education details') }}</li>
                                    <li><i class="fi-rr-check"></i>{{ __('Contact info & CV') }}</li>
                                </ul>

                                @if(! auth('account')->check())
                                    {{-- Guest --}}
                                    <div class="profile-paywall-plans">
                                        <div class="paywall-plan-card featured">
                                            <span class="plan-label">{{ __('Per profile') }}</span>
                                            <span class="plan-price">1 {{ __('credit') }}</span>
                                            <span class="plan-label">{{ __('one-time access') }}</span>
                                        </div>
                                        <div class="paywall-plan-card">
                                            <span class="plan-label">{{ __('Monthly plan') }}</span>
                                            <span class="plan-price">{{ __('Unlimited') }}</span>
                                            <span class="plan-label">{{ __('access all profiles') }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('public.account.login') }}" class="btn btn-default">
                                            <i class="fi-rr-sign-in me-1"></i>{{ __('Sign In') }}
                                        </a>
                                        <a href="{{ route('public.account.register') }}" class="btn btn-outline-primary">
                                            {{ __('Create Account') }}
                                        </a>
                                    </div>
                                @elseif(! $isEmployer)
                                    {{-- Logged in but not employer --}}
                                    <p class="font-sm color-text-paragraph-2">
                                        {{ __('Employer accounts can access full candidate profiles.') }}
                                        <a href="{{ route('public.account.register') }}">{{ __('Register as employer') }}</a>
                                    </p>
                                @else
                                    {{-- Employer without access --}}
                                    <div class="profile-paywall-plans">
                                        <div class="paywall-plan-card featured">
                                            <span class="plan-label">{{ __('Per profile') }}</span>
                                            <span class="plan-price">1 {{ __('credit') }}</span>
                                            <span class="plan-label">{{ __('one-time access') }}</span>
                                        </div>
                                        <div class="paywall-plan-card">
                                            <span class="plan-label">{{ __('Monthly plan') }}</span>
                                            <span class="plan-price">{{ __('Unlimited') }}</span>
                                            <span class="plan-label">{{ __('access all profiles') }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                                        @if($revealUrl)
                                            <button type="button" id="btn-reveal-profile"
                                                class="btn btn-default"
                                                data-reveal-url="{{ $revealUrl }}"
                                                data-candidate-id="{{ $candidate->id }}">
                                                <i class="fi-rr-unlock me-1"></i>
                                                @if($revealPriceLabel)
                                                    {{ __('Unlock — :price', ['price' => $revealPriceLabel]) }}
                                                @else
                                                    {{ __('Unlock — :cost credit(s)', ['cost' => $revealCost]) }}
                                                @endif
                                            </button>
                                        @endif
                                        <a href="{{ route('public.account.subscription.index') }}" class="btn btn-outline-primary">
                                            <i class="fi-rr-star me-1"></i>{{ __('View Plans') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4 col-md-12 col-sm-12 col-12 pl-40 pl-lg-15 mt-lg-30">
                <div class="sidebar-border">
                    <div class="d-flex justify-content-between">
                        <h5 class="f-18">{{ __('Overview') }}</h5>

                        @if(JobBoardHelper::isEnabledReview())
                            <div>
                                {!! Theme::partial('rating-star', ['star' => round($candidate->reviews_avg_star ?? 0)]) !!}
                                <span class="font-xs color-text-mutted ml-10">
                                    <span>(</span>
                                    <span>{{ $candidate->reviews_count ?? 0 }}</span>
                                    <span>)</span>
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="sidebar-list-job">
                        <ul>
                            <li>
                                <div class="sidebar-icon-item"><i class="fi-rr-time-fast"></i></div>
                                <div class="sidebar-text-info">
                                    <span class="text-description">{{ __('View') }}</span>
                                    <strong class="small-heading">{{ number_format($candidate->views) }}</strong>
                                </div>
                            </li>

                            @if($candidate->languages->isNotEmpty())
                                <li>
                                    <div class="sidebar-icon-item"><i class="fi-rr-marker"></i></div>
                                    <div class="sidebar-text-info">
                                        <span class="text-description">{{ __('Languages') }}</span>
                                        <strong class="small-heading fw-semibold">{{ $candidate->language_text }}</strong>
                                    </div>
                                </li>
                            @endif

                            @php
                                $candidate->loadMissing(['favoriteSkills', 'favoriteTags']);
                                $skills = $candidate->favoriteSkills;
                                $tags   = $candidate->favoriteTags;
                            @endphp

                            @if($skills->isNotEmpty())
                                <li>
                                    <div class="sidebar-icon-item"><i class="fi-rr-star"></i></div>
                                    <div class="sidebar-text-info">
                                        <span class="text-description">{{ __('Skills') }}</span>
                                        <strong class="small-heading fw-semibold">{{ implode(', ', $skills->pluck('name')->all()) }}</strong>
                                    </div>
                                </li>
                            @endif

                            @if($tags->isNotEmpty())
                                <li>
                                    <div class="sidebar-icon-item"><i class="fi-rr-bookmark"></i></div>
                                    <div class="sidebar-text-info">
                                        <span class="text-description">{{ __('Tags') }}</span>
                                        <strong class="small-heading fw-semibold">{{ implode(', ', $tags->pluck('name')->all()) }}</strong>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </div>

                    {{-- Contact info — gated behind paywall --}}
                    @if($hasAccess)
                        <div class="sidebar-list-job">
                            <ul class="ul-disc">
                                @if ($uniqueId = $candidate->unique_id)
                                    <li>{{ __('ID: :id', ['id' => $uniqueId]) }}</li>
                                @endif
                                @if ($address = $candidate->address)
                                    <li>{!! BaseHelper::clean($address) !!}</li>
                                @endif
                                @if ($phone = $candidate->phone)
                                    <li>{{ __('Phone:') }} <a href="tel:{{ $phone }}">{{ $phone }}</a></li>
                                @endif
                                @if ($email = $candidate->email)
                                    <li>{{ __('Email:') }} <a href="mailto:{{ $email }}">{{ $email }}</a></li>
                                @endif
                                @if ($linkedinUrl = $candidate->getMetaData('linkedin', true))
                                    <li>{{ __('LinkedIn:') }} <a title="{{ $linkedinUrl }}" href="{{ $linkedinUrl }}" target="_blank">{{ $candidateDisplayName }}</a></li>
                                @endif
                            </ul>

                            @if ($phone = $candidate->phone)
                                <div class="mt-30">
                                    <a class="btn btn-send-message" href="tel:{{ $phone }}">
                                        <span>{{ __('Contact Me') }}</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @elseif(! auth('account')->check())
                        <div class="mt-15 text-center">
                            <p class="font-sm color-text-mutted mb-10">
                                <i class="fi-rr-lock me-1 text-primary"></i>
                                {{ __('Sign in to view contact details') }}
                            </p>
                            <a href="{{ route('public.account.login') }}" class="btn btn-sm btn-default w-100">
                                {{ __('Sign In') }}
                            </a>
                        </div>
                    @elseif($isEmployer)
                        {{-- Employer without reveal: show reveal button --}}
                        <div class="mt-15" id="sidebar-reveal-wrap">
                            <div class="d-flex gap-2 flex-column align-items-center" style="filter:blur(4px);user-select:none;pointer-events:none;margin-bottom:8px;">
                                <span class="font-sm">+260 9X XXX XXXX</span>
                                <span class="font-sm">candidate@email.com</span>
                            </div>
                            @if($revealUrl)
                                <button type="button" id="btn-reveal-sidebar"
                                    class="btn btn-sm btn-outline-primary w-100 mt-1"
                                    data-reveal-url="{{ $revealUrl }}"
                                    data-candidate-id="{{ $candidate->id }}">
                                    <i class="fi-rr-unlock me-1"></i>
                                    @if($revealPriceLabel)
                                        {{ __('Unlock — :price', ['price' => $revealPriceLabel]) }}
                                    @else
                                        {{ __('Unlock — :cost credit(s)', ['cost' => $revealCost]) }}
                                    @endif
                                </button>
                                <a href="{{ route('public.account.subscription.index') }}" class="btn btn-xs btn-link w-100 mt-1 font-xs">
                                    {{ __('Or subscribe for unlimited access') }}
                                </a>
                            @else
                                <a href="{{ route('public.account.subscription.index') }}" class="btn btn-sm btn-outline-primary w-100">
                                    {{ __('View Plans') }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                <div>
                    @if (is_plugin_active('ads'))
                        {!! apply_filters('ads_render', null, 'candidate_sidebar_before', ['class' => 'my-2 text-center']) !!}
                    @endif

                    {!! dynamic_sidebar('candidate_sidebar') !!}

                    @if (is_plugin_active('ads'))
                        {!! apply_filters('ads_render', null, 'candidate_sidebar_after', ['class' => 'my-2 text-center']) !!}
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (is_plugin_active('ads'))
        {!! apply_filters('ads_render', null, 'candidate_after', ['class' => 'my-2 text-center']) !!}
    @endif
</section>

@push('footer')
<script>
(function () {
    'use strict';

    var shareButton = document.getElementById('candidate-share-btn');
    if (shareButton) {
        shareButton.addEventListener('click', function () {
            var url = shareButton.dataset.shareUrl || window.location.href;
            var title = shareButton.dataset.shareTitle || document.title;

            if (navigator.share) {
                navigator.share({ title: title, url: url }).catch(function () {});
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    shareButton.innerHTML = '<i class="fi-rr-check me-1"></i>{{ __('Link Copied') }}';
                    setTimeout(function () {
                        shareButton.innerHTML = '<i class="fi-rr-share me-1"></i>{{ __('Share Profile') }}';
                    }, 1800);
                });
                return;
            }

            // Clipboard API unavailable — show modal with copyable link
            document.getElementById('copyLinkInput').value = url;
            new bootstrap.Modal(document.getElementById('copyLinkModal')).show();
        });
    }

    function handleReveal(btn, onSuccess) {
        var url = btn.dataset.revealUrl;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                'Accept': 'application/json',
            }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                onSuccess(data);
            } else {
                btn.disabled = false;
                btn.innerHTML = btn.dataset.origLabel || 'Unlock';
                var msg = (data.message || 'Could not unlock profile.').replace(/<[^>]+>/g, '');
                document.getElementById('themeErrorMsg').textContent = msg;
                new bootstrap.Modal(document.getElementById('themeErrorModal')).show();
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.origLabel || 'Unlock';
        });
    }

    // Main paywall "Unlock" button
    var btnMain = document.getElementById('btn-reveal-profile');
    if (btnMain) {
        btnMain.dataset.origLabel = btnMain.innerHTML;
        btnMain.addEventListener('click', function () {
            handleReveal(btnMain, function () {
                // Reload so full content appears
                window.location.reload();
            });
        });
    }

    // Sidebar reveal button
    var btnSidebar = document.getElementById('btn-reveal-sidebar');
    if (btnSidebar) {
        btnSidebar.dataset.origLabel = btnSidebar.innerHTML;
        btnSidebar.addEventListener('click', function () {
            handleReveal(btnSidebar, function (data) {
                window.location.reload();
            });
        });
    }
}());
</script>

{{-- Copy-link fallback modal --}}
<div class="modal fade" id="copyLinkModal" tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Copy profile link') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group">
                    <input type="text" class="form-control" id="copyLinkInput" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('copyLinkInput').value).then(()=>{this.textContent='Copied!';setTimeout(()=>{this.textContent='Copy'},1500)})">Copy</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Generic theme error modal --}}
<div class="modal fade" id="themeErrorModal" tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="mb-3 text-danger fs-1"><i class="fi-rr-exclamation"></i></div>
                <p id="themeErrorMsg" class="mb-0"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endpush
