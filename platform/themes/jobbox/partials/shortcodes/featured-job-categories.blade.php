@php($categories = $categories->where('active_jobs_count', '>', 0)->values())

@if(isset($recentJobs) && $recentJobs->isNotEmpty())
    <style>
        .latest-jobs-showcase .box-swiper {
            margin-top: 32px;
        }

        .latest-jobs-showcase .swiper-wrapper {
            padding-bottom: 54px !important;
        }

        .latest-jobs-showcase .swiper-container:not(.swiper-container-initialized):not(.swiper-initialized) .swiper-wrapper {
            display: flex;
            gap: 16px;
            overflow: hidden;
            flex-wrap: nowrap;
        }

        .latest-jobs-showcase .swiper-container:not(.swiper-container-initialized):not(.swiper-initialized) .swiper-slide {
            flex: 0 0 calc((100% - 80px) / 6);
            width: calc((100% - 80px) / 6) !important;
        }

        .latest-jobs-showcase .swiper-slide {
            height: auto;
        }

        .latest-jobs-showcase .swiper-button-next-latest-jobs,
        .latest-jobs-showcase .swiper-button-prev-latest-jobs {
            z-index: 5;
        }

        .latest-jobs-showcase .card-grid-2 {
            display: flex;
            flex-direction: column;
            height: 292px;
            min-height: 292px;
            overflow: hidden;
        }

        .latest-jobs-showcase .card-grid-2-image-left {
            align-items: flex-start;
            display: flex;
            height: 72px;
            padding: 14px 16px 0;
        }

        .latest-jobs-showcase .card-grid-2-image-left .image-box {
            align-items: center;
            background: #f8faff;
            border-radius: 8px;
            display: flex;
            flex: 0 0 42px;
            height: 42px;
            justify-content: center;
            min-width: 42px;
            overflow: hidden;
            width: 42px;
        }

        .latest-jobs-showcase .card-grid-2-image-left .image-box img {
            height: 100%;
            object-fit: contain;
            width: 100%;
        }

        .latest-jobs-showcase .card-grid-2-image-left .right-info {
            flex: 1 1 auto;
            line-height: 18px;
            min-width: 0;
        }

        .latest-jobs-showcase .card-grid-2-image-left .name-job {
            display: -webkit-box;
            font-size: 11px;
            height: 32px;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 16px;
            overflow: hidden;
        }

        .latest-jobs-showcase .card-grid-2-image-left .location-small {
            display: block;
            font-size: 9px;
            height: 28px;
            line-height: 14px;
            overflow: hidden;
            text-overflow: unset;
            white-space: normal;
        }

        .latest-jobs-showcase .card-grid-2 .card-grid-2-image-left .right-info .location-small {
            font-size: 9px;
        }

        .latest-jobs-showcase .card-block-info {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            padding: 12px 16px 16px;
        }

        .latest-jobs-showcase .card-block-info .h6,
        .latest-jobs-showcase .card-block-info .h6 a {
            display: -webkit-box;
            font-size: 12px;
            height: 40px;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 20px;
            overflow: hidden;
            text-overflow: unset;
            white-space: normal;
        }

        .latest-jobs-showcase .card-block-info .mt-5 {
            height: 30px;
            overflow: hidden;
            white-space: nowrap;
        }

        .latest-jobs-showcase .card-briefcase,
        .latest-jobs-showcase .card-time,
        .latest-jobs-showcase .salary-information,
        .latest-jobs-showcase .salary-information span {
            font-size: 10px;
            line-height: 14px;
        }

        .latest-jobs-showcase .job-description {
            font-size: 9px !important;
            line-height: 18px !important;
            margin-top: 10px !important;
            height: 54px;
            min-height: 54px;
            overflow: hidden;
        }

        .latest-jobs-showcase .card-2-bottom {
            margin-top: auto !important;
            min-height: 70px;
            overflow: hidden;
        }

        .latest-jobs-showcase .card-2-bottom .row {
            height: 100%;
        }

        .latest-jobs-showcase .salary-information {
            height: 18px;
            overflow: hidden;
            white-space: nowrap;
        }

        .latest-jobs-showcase .card-2-bottom .col-12.mt-3 {
            margin-top: -14px !important;
        }

        .latest-jobs-showcase .job-card-actions {
            align-items: center;
            display: flex;
            gap: 6px;
            justify-content: space-between;
        }

        .latest-jobs-showcase .btn-view-details,
        .latest-jobs-showcase .btn-apply,
        .latest-jobs-showcase .btn-apply-now {
            align-items: center;
            box-sizing: border-box;
            display: inline-flex;
            font-size: 9px;
            height: 26px;
            justify-content: center;
            line-height: 1;
            min-width: auto;
            overflow: hidden;
            padding: 0 6px;
            text-align: center;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .latest-jobs-showcase .btn-view-details {
            background-color: transparent;
            border: 0;
            box-shadow: none;
            color: var(--primary-color);
            flex: 0 0 28px;
            outline: 0;
            padding: 0;
            width: 28px;
        }

        .latest-jobs-showcase .btn-view-details:hover {
            background-color: transparent;
            border: 0;
            box-shadow: none;
            color: var(--secondary-color);
        }

        .latest-jobs-showcase .job-card-action {
            display: flex;
            flex: 0 0 auto;
            margin-left: auto;
            min-width: 0;
        }

        .latest-jobs-showcase .job-card-action .btn-apply-now,
        .latest-jobs-showcase .job-card-action .btn-apply {
            background-color: var(--primary-color);
            border: 0;
            border-radius: 999px;
            box-shadow: 0 8px 14px -8px rgba(81, 146, 255, 0.8);
            color: #ffffff;
            font-weight: 700;
            min-width: 72px;
            width: auto;
        }

        .latest-jobs-showcase .job-card-action .btn-apply-now:hover,
        .latest-jobs-showcase .job-card-action .btn-apply:hover {
            background-color: var(--secondary-color);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .latest-jobs-showcase .btn-view-details i {
            color: var(--primary-color);
            font-size: 13px;
            line-height: 1;
        }

        .latest-jobs-showcase .btn-view-details:hover i {
            color: var(--secondary-color);
        }

        @media (max-width: 991.98px) {
            .latest-jobs-showcase .swiper-container:not(.swiper-container-initialized):not(.swiper-initialized) .swiper-slide {
                flex-basis: calc((100% - 32px) / 3);
                width: calc((100% - 32px) / 3) !important;
            }
        }

        @media (max-width: 575.98px) {
            .latest-jobs-showcase .swiper-container:not(.swiper-container-initialized):not(.swiper-initialized) .swiper-slide {
                flex-basis: calc((100% - 16px) / 2);
                width: calc((100% - 16px) / 2) !important;
            }
        }
    </style>

    <section class="section-box mt-80 latest-jobs-showcase">
        <div class="section-box wow animate__animated animate__fadeIn">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-title mb-10 wow animate__animated animate__fadeInUp">
                        {{ __('Latest jobs') }}@if(isset($selectedCountry) && $selectedCountry) {{ __('in :country', ['country' => $selectedCountry->name]) }} @endif
                    </h2>
                    <p class="font-lg color-text-paragraph-2 wow animate__animated animate__fadeInUp">
                        {{ __('Browse the 20 most recent jobs available for your selected country.') }}
                    </p>
                </div>
                <div class="box-swiper mt-50">
                    <div class="swiper-container swiper-group-latest-jobs swiper">
                        <div class="swiper-wrapper pb-70 pt-5">
                            @foreach($recentJobs as $job)
                                <div class="swiper-slide hover-up">
                                    <div class="card-grid-2 grid-bd-16 hover-up item-grid @if($job->is_featured) featured-job-item @endif">
                                        <div class="card-grid-2-image-left job-item">
                                            @if($job->is_featured)
                                                <span class="flash"></span>
                                            @endif
                                            <div class="image-box">
                                                <img src="{{ $job->company_logo_thumb }}" alt="{{ $job->company_name ?: $job->name }}">
                                            </div>
                                            <div class="right-info">
                                                @if(! $job->hide_company && $job->company_name)
                                                    <a class="name-job" title="{{ $job->company_name }}" href="{{ $job->company_url ?: 'javascript:void(0);' }}">{{ $job->company_name }}</a>
                                                @endif
                                                @if($job->location)
                                                    <span class="location-small">{{ $job->location }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-block-info">
                                            <div class="h6 fw-bold text-truncate">
                                                <a href="{{ $job->url }}" title="{{ $job->name }}">{{ $job->name }}</a>
                                            </div>
                                            <div class="mt-5">
                                                @if($job->jobTypes->isNotEmpty())
                                                    <span class="card-briefcase">
                                                        @foreach($job->jobTypes as $jobType)
                                                            {{ $jobType->name }}@if(! $loop->last), @endif
                                                        @endforeach
                                                    </span>
                                                @endif
                                                <span class="card-time">{{ $job->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="font-sm color-text-paragraph job-description mt-15" title="{{ $job->description }}">
                                                {{ Str::limit($job->description, 90) }}
                                            </p>
                                            <div class="card-2-bottom mt-20">
                                                <div class="row">
                                                    <div class="col-12 salary-information">
                                                        {!! Theme::partial('salary', compact('job')) !!}
                                                    </div>
                                                    <div class="col-12 mt-3">
                                                        <div class="job-card-actions">
                                                            <a class="btn btn-view-details" href="{{ $job->url }}" aria-label="{{ __('View details for :job', ['job' => $job->name]) }}">
                                                                <i class="fi-rr-eye" aria-hidden="true"></i>
                                                            </a>
                                                            {!! Theme::partial('apply-button', ['job' => $job, 'wrapClass' => 'job-card-action']) !!}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="swiper-button-next swiper-button-next-latest-jobs"></div>
                    <div class="swiper-button-prev swiper-button-prev-latest-jobs"></div>
                </div>
            </div>
        </div>
    </section>
@endif
