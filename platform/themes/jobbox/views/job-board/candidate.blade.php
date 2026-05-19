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
@endphp

<section class="section-box-2">
    @if (is_plugin_active('ads'))
        {!! apply_filters('ads_render', null, 'candidate_before', ['class' => 'my-2 text-center']) !!}
    @endif

    <div class="container">
        <div class="banner-hero banner-image-single">
            @if ($coverImage)
                <div class="wrap-cover-image">
                    <img src="{{ $coverImage }}" alt="{{ $candidate->name }}">
                </div>
            @endif
        </div>
        <div class="box-company-profile">
            <div class="image-candidate">
                <img src="{{ $candidate->avatar_thumb_url }}" alt="{{ $candidate->name }}" >
            </div>
            <div class="row mt-30">
                <div class="col-lg-8 col-md-12">
                    <h5 class="f-18">{{ $candidate->name }}
                        <span class="card-location font-regular ml-20">{{ $candidate->address }}</span>
                    </h5>
                    <p class="mt-0 font-md color-text-paragraph-2 mb-15">{!! BaseHelper::clean($candidate->description) !!}</p>
                </div>

                @php
                    $resumeAvailable = ! $candidate->hide_cv && $candidate->resume;
                @endphp

                @if ((! JobBoardHelper::isCandidateInformationHiddenForGuests() || auth('account')->check()) && ! JobBoardHelper::isOnlyEmployerCanViewCandidateInformation())
                    @if($resumeAvailable)
                        <div class="col-lg-4 col-md-12 text-lg-end">
                            <a class="btn btn-download-icon btn-apply btn-apply-big" href="{{ $candidate->resumeDownloadUrl }}">{{ __('Download CV') }}</a>
                        </div>
                    @endif
                @elseif($resumeAvailable)
                    @if(! auth('account')->check())
                        <div class="col-lg-4 col-md-12 text-lg-end">
                            <a class="alert-label-for-guest text-muted justify-content-end" href="{{ route('public.account.login') }}">
                                {{ JobBoardHelper::isOnlyEmployerCanViewCandidateInformation() ? __('Please log in as employer account to download CV') : __('Please log in to download CV') }}
                            </a>
                        </div>
                    @elseif (! auth('account')->user()->isEmployer() && JobBoardHelper::isOnlyEmployerCanViewCandidateInformation())
                        <div class="col-lg-4 col-md-12 text-lg-end">
                            <span class="alert-label-for-guest text-muted justify-content-end">
                                {{ __('Please log in as employer account to download CV') }}
                            </span>
                        </div>
                    @endif
                @endif

                @if(
                    JobBoardHelper::isOnlyEmployerCanViewCandidateInformation()
                    && auth('account')->check()
                    && auth('account')->user()->type->getValue() === 'employer'
                )
                    @if(! $candidate->hide_cv && $candidate->resume)
                        <div class="col-lg-4 col-md-12 text-lg-end">
                            <a class="btn btn-download-icon btn-apply btn-apply-big" href="{{ $candidate->resumeDownloadUrl }}">{{ __('Download CV') }}</a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
        <div class="border-bottom pt-10 pb-10"></div>
    </div>
</section>

<section class="section-box mt-50">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 col-md-12 col-sm-12 col-12">
                <div class="content-single">
                    <div class="tab-content">
                        <div class="tab-pane fade active show mb-5" id="tab-short-bio" role="tabpanel" aria-labelledby="tab-short-bio">
                            <h4>{{ __('About Me') }}</h4>
                            {!! BaseHelper::clean($candidate->bio) !!}
                        </div>

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
                                                ({{  $education->started_at->format('Y') }} -
                                                {{ $education->ended_at ? $education->ended_at->format('Y'): __('Now') }})
                                            </p>
                                            <p class="text-muted">{!! BaseHelper::clean($education->description) !!}</p>
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
                                @foreach( $experiences as $experience)
                                    <div class="candidate-education-content mt-4 d-flex">
                                        <div class="circle flex-shrink-0 bg-soft-primary"> {{ $experience->position ? strtoupper(substr($experience->position, 0, 1)) : '' }} </div>
                                        <div class="ms-4">
                                            @if ($experience->position)
                                                <h6 class="fs-16 mb-1">{{ $experience->position }}</h6>
                                            @endif
                                            <p class="mb-2 text-muted">{{ $experience->company }} -
                                                ({{  $experience->started_at->format('Y') }} -
                                                {{ $experience->ended_at ? $experience->ended_at->format('Y'): __('Now')}})
                                            </p>
                                            <p class="text-muted">{!! BaseHelper::clean($experience->description) !!}</p>
                                        </div>
                                        @if ($countExperience >= 1 && ! $loop->last)
                                            <span class="line"></span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Career Services Marketplace --}}
                    @php $careerServices = \Botble\JobBoard\Models\CareerServiceOrder::services(); @endphp
                    <div class="mt-5 pt-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h4 class="fs-17 fw-bold mb-0">Career Services</h4>
                            <span class="badge bg-warning text-dark font-xs">Boost your career</span>
                        </div>
                        <p class="text-muted font-sm mb-4">Professional CV writing, LinkedIn optimisation &amp; interview coaching — delivered by vetted career coaches within 24–72 hrs.</p>

                        <div class="row g-3">
                            @foreach($careerServices as $key => $svc)
                                <div class="col-md-6">
                                    <div class="card h-100 border career-service-card" style="border-radius:12px;transition:box-shadow .2s;">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10" style="width:38px;height:38px;flex-shrink:0;">
                                                    <i class="{{ $svc['icon'] ?? 'fi-rr-briefcase' }} text-primary"></i>
                                                </span>
                                                <div>
                                                    <div class="fw-semibold small lh-sm">{{ $svc['name'] }}</div>
                                                    @if(!empty($svc['badge']))
                                                        <span class="badge bg-warning text-dark" style="font-size:10px;">{{ $svc['badge'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <p class="text-muted mb-2" style="font-size:12px;line-height:1.4;">{{ $svc['description'] }}</p>
                                            <div class="d-flex align-items-center justify-content-between mt-auto">
                                                <div>
                                                    <span class="fw-bold text-primary">${{ $svc['price'] }}</span>
                                                    <span class="text-muted ms-1" style="font-size:11px;">· {{ $svc['delivery'] }}</span>
                                                </div>
                                                <a href="{{ route('public.career-service.checkout', ['service' => $key, 'candidate' => $candidate->slug]) }}"
                                                   class="btn btn-sm btn-apply px-3">Book</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <p class="text-muted text-center mt-3" style="font-size:11px;">
                            <i class="fi-rr-shield-check text-success me-1"></i>Secure payment · Money-back guarantee if not delivered · Powered by Wakanda Jobs
                        </p>
                    </div>

                    <style>
                        .career-service-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.10); border-color: var(--primary-color) !important; }
                    </style>

                    @if(JobBoardHelper::isEnabledReview())
                        <div class="mt-4 pt-3 position-relative review-listing" @style(['display: none' => $candidate->reviews_count < 1])>
                            <h6 class="fs-17 fw-semibold mb-3">{{ __(":candidate's Reviews", ['candidate' => $candidate->name]) }}</h6>
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
                            'canReview' => $canReview,
                        ])
                    @endif
                </div>
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 col-12 pl-40 pl-lg-15 mt-lg-30">
                <div class="sidebar-border">
                    <div class="d-flex justify-content-between">
                        <h5 class="f-18">{{ __('Overview') }}</h5>

                        @if(JobBoardHelper::isEnabledReview())
                            <div>
                                {!! Theme::partial('rating-star', ['star' => round($candidate->reviews_avg_star)]) !!}
                                <span class="font-xs color-text-mutted ml-10">
                                <span>(</span>
                                <span>{{ $candidate->reviews_count }}</span>
                                <span>)</span>
                            </span>
                            </div>
                        @endif
                    </div>
                    <div class="sidebar-list-job">
                        <ul>
                            <li>
                                <div class="sidebar-icon-item">
                                    <i class="fi-rr-time-fast"></i>
                                </div>
                                <div class="sidebar-text-info">
                                    <span class="text-description">{{ __('View') }}</span>
                                    <strong class="small-heading">{{ number_format($candidate->views) }}</strong>
                                </div>
                            </li>

                            @if($candidate->languages->isNotEmpty())
                                <li>
                                    <div class="sidebar-icon-item">
                                        <i class="fi-rr-marker"></i>
                                    </div>
                                    <div class="sidebar-text-info">
                                        <span class="text-description">{{ __('Languages') }}</span>
                                        <div class="d-flex flex-wrap gap-1">
                                            <strong class="small-heading fw-semibold">{{ $candidate->language_text }}</strong>
                                        </div>
                                    </div>
                                </li>
                            @endif

                            @php
                                $candidate->loadMissing(['favoriteSkills', 'favoriteTags']);
                                $skills = $candidate->favoriteSkills;
                                $tags = $candidate->favoriteTags;
                            @endphp

                            @if($skills->isNotEmpty())
                                <li>
                                    <div class="sidebar-icon-item">
                                        <i class="fi-rr-star"></i>
                                    </div>
                                    <div class="sidebar-text-info">
                                        <span class="text-description">{{ __('Skills') }}</span>
                                        <div class="d-flex flex-wrap gap-1">
                                            <strong class="small-heading fw-semibold">{{ implode(', ', $skills->pluck('name')->all()) }}</strong>
                                        </div>
                                    </div>
                                </li>
                            @endif

                            @if($tags->isNotEmpty())
                                <li>
                                    <div class="sidebar-icon-item">
                                        <i class="fi-rr-bookmark"></i>
                                    </div>
                                    <div class="sidebar-text-info">
                                        <span class="text-description">{{ __('Tags') }}</span>
                                        <div class="d-flex flex-wrap gap-1">
                                            <strong class="small-heading fw-semibold">{{ implode(', ', $tags->pluck('name')->all()) }}</strong>
                                        </div>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </div>

                    @if ((! JobBoardHelper::isCandidateInformationHiddenForGuests() || auth('account')->check()) && ! JobBoardHelper::isOnlyEmployerCanViewCandidateInformation())
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
                                    <li>{{ __('LinkedIn:') }} <a title="{{ $linkedinUrl }}" href="{{ $linkedinUrl }}" target="_blank">{{ $candidate->name }}</a></li>
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

                    @else
                        @if(! auth('account')->check())
                            <a href="{{ route('public.account.login') }}" class="alert-label-for-guest text-muted mt-10">
                                {{ JobBoardHelper::isOnlyEmployerCanViewCandidateInformation() ? __('Please log in as employer account to view candidate information') :__('Please log in to see more information') }}
                            </a>
                        @elseif (! auth('account')->user()->isEmployer() && JobBoardHelper::isOnlyEmployerCanViewCandidateInformation())
                            <span class="alert-label-for-guest text-muted mt-10">
                                {{ __('Please log in as employer account to view candidate information') }}
                            </span>
                        @endif
                    @endif

                    @if(
                        JobBoardHelper::isOnlyEmployerCanViewCandidateInformation()
                        && auth('account')->check()
                        && auth('account')->user()->type->getValue() === 'employer'
                    )
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
                                    <li>{{ __('LinkedIn:') }} <a title="{{ $linkedinUrl }}" href="{{ $linkedinUrl }}" target="_blank">{{ $candidate->name }}</a></li>
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

