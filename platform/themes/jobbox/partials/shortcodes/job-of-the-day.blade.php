<style>
    .job-of-the-day-list > [class*="col-"] {
        display: flex;
        margin-bottom: 24px;
    }

    .job-of-the-day-list .card-grid-2.items {
        display: flex;
        flex-direction: column;
        height: 292px;
        min-height: 292px;
        overflow: hidden;
        width: 100%;
    }

    .job-of-the-day-list .card-grid-2.items .card-grid-2-image-left {
        align-items: flex-start;
        display: flex;
        height: 72px;
        min-height: 72px;
        overflow: hidden;
        padding: 14px 16px 0;
    }

    .job-of-the-day-list .card-grid-2.items .card-grid-2-image-left .image-box {
        align-items: center;
        background: #f8fafc;
        border-radius: 8px;
        display: flex;
        flex: 0 0 42px;
        height: 42px;
        justify-content: center;
        margin-right: 10px;
        overflow: hidden;
        width: 42px;
    }

    .job-of-the-day-list .card-grid-2.items .card-grid-2-image-left .image-box img {
        display: block;
        height: 100%;
        max-height: 42px;
        max-width: 42px;
        object-fit: contain;
        width: 100%;
    }

    .job-of-the-day-list .card-grid-2.items .card-grid-2-image-left .right-info {
        flex: 1 1 auto;
        line-height: 16px;
        min-width: 0;
        overflow: hidden;
    }

    .job-of-the-day-list .card-grid-2.items .card-grid-2-image-left .name-job {
        display: -webkit-box;
        font-size: 11px;
        height: 32px;
        line-height: 16px;
        margin-bottom: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .job-of-the-day-list .card-grid-2.items .card-grid-2-image-left .location-small {
        display: block;
        font-size: 10px;
        height: 14px;
        line-height: 14px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .job-of-the-day-list .card-grid-2.items .card-block-info {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        overflow: hidden;
        padding: 12px 16px 16px;
    }

    .job-of-the-day-list .card-grid-2.items .card-block-info .h6 {
        height: 40px;
        line-height: 20px;
        margin-bottom: 0;
        overflow: hidden;
        white-space: normal;
    }

    .job-of-the-day-list .card-grid-2.items .card-block-info .h6 a {
        display: -webkit-box;
        font-size: 14px;
        line-height: 20px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .job-of-the-day-list .card-grid-2.items .card-block-info .mt-5 {
        height: 30px;
        line-height: 14px;
        overflow: hidden;
        white-space: nowrap;
    }

    .job-of-the-day-list .card-grid-2.items .card-briefcase,
    .job-of-the-day-list .card-grid-2.items .card-time,
    .job-of-the-day-list .card-grid-2.items .card-location,
    .job-of-the-day-list .card-grid-2.items .salary-information,
    .job-of-the-day-list .card-grid-2.items .salary-information span {
        font-size: 10px;
        line-height: 14px;
    }

    .job-of-the-day-list .card-grid-2.items .job-description {
        font-size: 9px !important;
        height: 54px;
        line-height: 18px !important;
        margin-top: 10px !important;
        min-height: 54px;
        overflow: hidden;
    }

    .job-of-the-day-list .card-grid-2.items .card-block-info > .mt-15:not(.card-2-bottom) {
        height: 0;
        margin-top: 0 !important;
        overflow: hidden;
    }

    .job-of-the-day-list .card-grid-2.items .card-2-bottom {
        margin-top: auto !important;
        min-height: 68px;
        overflow: hidden;
    }

    .job-of-the-day-list .card-grid-2.items .salary-information {
        height: 18px;
        overflow: hidden;
        white-space: nowrap;
    }

    .job-of-the-day-list .card-grid-2.items .card-2-bottom .col-12.mt-3 {
        margin-top: 7px !important;
    }

    .job-of-the-day-list .card-grid-2.items .job-card-actions {
        align-items: center;
        display: flex;
        gap: 6px;
        justify-content: space-between;
    }

    .job-of-the-day-list .card-grid-2.items .btn-view-details,
    .job-of-the-day-list .card-grid-2.items .btn-apply,
    .job-of-the-day-list .card-grid-2.items .btn-apply-now {
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

    .job-of-the-day-list .card-grid-2.items .btn-view-details {
        background-color: transparent;
        border: 0;
        box-shadow: none;
        color: var(--primary-color);
        flex: 0 0 28px;
        outline: 0;
        padding: 0;
        width: 28px;
    }

    .job-of-the-day-list .card-grid-2.items .btn-view-details:hover {
        background-color: transparent;
        border: 0;
        box-shadow: none;
        color: var(--secondary-color);
    }

    .job-of-the-day-list .card-grid-2.items .job-card-action {
        display: flex;
        flex: 0 0 auto;
        margin-left: auto;
        min-width: 0;
    }

    .job-of-the-day-list .card-grid-2.items .job-card-action .btn-apply-now,
    .job-of-the-day-list .card-grid-2.items .job-card-action .btn-apply {
        background-color: var(--primary-color);
        border: 0;
        border-radius: 999px;
        box-shadow: 0 8px 14px -8px rgba(81, 146, 255, 0.8);
        color: #ffffff;
        font-weight: 700;
        min-width: 72px;
        width: auto;
    }

    .job-of-the-day-list .card-grid-2.items .job-card-action .btn-apply-now:hover,
    .job-of-the-day-list .card-grid-2.items .job-card-action .btn-apply:hover {
        background-color: var(--secondary-color);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .job-of-the-day-list .card-grid-2.items .btn-view-details i {
        color: var(--primary-color);
        font-size: 13px;
        line-height: 1;
    }

    .job-of-the-day-list .card-grid-2.items .btn-view-details:hover i {
        color: var(--secondary-color);
    }
</style>

@switch($shortcode->style)
    @case('style-2')
        <section class="section-box mt-30 job-of-the-day">
            <div class="container">
                <div class="text-start">
                    <h2 class="section-title mb-10 wow animate__animated animate__fadeInUp">
                        {!! BaseHelper::clean($shortcode->title) !!}
                    </h2>
                    <p class="font-lg color-text-paragraph-2 wow animate__animated animate__fadeInUp">
                        {!! BaseHelper::clean($shortcode->subtitle) !!}
                    </p>
                    @if (count($categories))
                        <div class="list-tabs mt-40">
                        <div class="nav nav-tabs" role="tablist">
                            @foreach($categories->loadMissing('metadata') as $category)
                                <div role="tab">
                                    <a
                                        @class(['active' => $loop->first, 'category-item'])
                                        id="nav-tab-job-{{ $category->id }}"
                                        href="#tab-job-{{ $category->id }}"
                                        data-url="{{ route('public.ajax.jobs-by-category', $category->getKey()) }}?limit={{ (int)$shortcode->limit ?: 8 }}"
                                        data-style="{{ $shortcode->style }}"
                                    >
                                        @if($iconImage = $category->getMetaData('icon_image', true))
                                            <img src="{{ RvMedia::getImageUrl($iconImage) }}" alt="{{ $category->name }}">
                                        @elseif($icon = $category->getMetaData('icon', true))
                                            <i class="{{ $icon }}"></i>
                                        @endif
                                        {{ $category->name }} ({{ $category->jobs_count }})
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @if (count($categories))
                    <div class="mt-50">
                        <div class="tab-content" id="myTabContent-1">
                            <div
                                @class(['tab-pane fade show active'])
                                id="tab-job-{{ $firstCategoryId = $categories->first()->id }}"
                                aria-labelledby="tab-job-{{ $firstCategoryId }}"
                            >
                            <div class="row job-of-the-day-list">
                                @include(Theme::getThemeNamespace('views.job-board.partials.job-of-the-day-items'), [
                                    'jobs' => $jobs,
                                    'style' => $shortcode->style
                                ])
                            </div>
                        </div>
                    </div>
                @endif
                </div>
            </div>
        </section>
        @break
    @case('style-3')
        <section class="section-box mt-70 job-of-the-day">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-title mb-10 wow animate__animated animate__fadeInUp">
                        {!! BaseHelper::clean($shortcode->title) !!}
                    </h2>
                    <p class="font-lg color-text-paragraph-2 wow animate__animated animate__fadeInUp">
                        {!! BaseHelper::clean($shortcode->subtitle) !!}
                    </p>
                    @if (count($categories))
                        <div class="list-tabs mt-40">
                            <div class="nav nav-tabs" role="tablist">
                                @foreach($categories as $category)
                                    <div role="tab">
                                        <a
                                            @class(['active' => $loop->first, 'category-item'])
                                            id="nav-tab-job-{{ $category->id }}"
                                            href="#tab-job-{{ $category->id }}"
                                            data-style="{{ $shortcode->style }}"
                                            data-url="{{ route('public.ajax.jobs-by-category', $category->getKey()) }}?limit={{ (int)$shortcode->limit ?: 8 }}"
                                        >
                                            <img src="{{ RvMedia::getImageUrl($category->getMetadata('icon_image', true)) }}" alt="{{ $category->name }}">
                                            {{ $category->name }} ({{ $category->jobs_count }})
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                @if (count($categories))
                    <div class="mt-50">
                        <div class="tab-content" id="myTabContent-1">
                            <div
                                @class(['tab-pane fade show active'])
                                id="tab-job-{{ $firstCategoryId = $categories->first()->id }}"
                                data-style="{{ $shortcode->style }}"
                                aria-labelledby="tab-job-{{ $firstCategoryId }}"
                            >
                            <div class="row job-of-the-day-list">
                                @include(Theme::getThemeNamespace('views.job-board.partials.job-of-the-day-items'), [
                                    'jobs' => $jobs,
                                    'style' => $shortcode->style
                                ])
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            </div>
        </section>
        @break
    @default
        <section class="section-box mt-50 job-of-the-day">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-title mb-10 wow animate__animated animate__fadeInUp">
                        {!! BaseHelper::clean($shortcode->title) !!}
                    </h2>
                    <p class="font-lg color-text-paragraph-2 wow animate__animated animate__fadeInUp">
                        {!! BaseHelper::clean($shortcode->subtitle) !!}
                    </p>
                    @if (count($categories))
                        <div class="list-tabs mt-40">
                            <div class="nav nav-tabs" role="tablist">
                                @foreach($categories->loadMissing('metadata') as $category)
                                    <div role="tab">
                                        <a
                                            @class(['active' => $loop->first, 'category-item'])
                                            id="nav-tab-job-{{ $category->id }}"
                                            href="#tab-job-{{ $category->id }}"
                                            data-url="{{ route('public.ajax.jobs-by-category', $category->getKey()) }}?limit={{ (int)$shortcode->limit ?: 8 }}"
                                            data-style="{{ $shortcode->style }}"
                                        >
                                            @if($iconImage = $category->getMetaData('icon_image', true))
                                                <img src="{{ RvMedia::getImageUrl($iconImage) }}" alt="{{ $category->name }}">
                                            @elseif($icon = $category->getMetaData('icon', true))
                                                <i class="{{ $icon }}"></i>
                                            @endif
                                            {{ $category->name }} ({{ $category->jobs_count }})
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                @if (count($categories))
                    <div class="mt-70">
                        <div class="tab-content" id="myTabContent-1">
                            <div
                                @class(['tab-pane fade show active'])
                                id="tab-job-{{ $firstCategoryId = $categories->first()->id }}"
                                aria-labelledby="tab-job-{{ $firstCategoryId }}"
                            >
                                <div class="row job-of-the-day-list">
                                    @include(Theme::getThemeNamespace('views.job-board.partials.job-of-the-day-items'), [
                                        'jobs' => $jobs,
                                        'style' => $shortcode->style,
                                    ])
                                </div>
                            </div>
                        </div>
                        <div class="list-tags-banner text-center wow animate__animated animate__fadeInUp" data-wow-delay=".3s" style="font-size:18px">
                            <strong>{{ __('Looking for more?') }}</strong>
                            <a href="{{ JobBoardHelper::getJobsPageURL() }}">{{ __('View All Jobs') }}</a>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @break
@endswitch
