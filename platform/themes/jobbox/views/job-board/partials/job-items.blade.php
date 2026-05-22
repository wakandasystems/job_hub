@php
    $layout = BaseHelper::stringify(request()->query('layout'));
    if (! in_array($layout, ['list', 'grid', 'map'])) {
        $layout = 'grid';
    }
    $isMapActive = (theme_option('show_map_on_jobs_page', 'yes') === 'yes' && $layout === 'map') && $jobs->isNotEmpty();
    $template = $isMapActive ? 'map' : $layout;
@endphp

<style>
    .job-content-section .showing-of-results > .jobs-item.job-grid {
        display: flex;
        margin-bottom: 24px;
    }

    .job-content-section .jobs-item.job-grid .card-grid-2 {
        display: flex;
        flex-direction: column;
        height: 289px;
        min-height: 331px;
        overflow: hidden;
        width: 100%;
    }

    .job-content-section .jobs-item.job-grid .card-grid-2-image-left {
        align-items: flex-start;
        display: flex;
        height: 72px;
        min-height: 72px;
        overflow: hidden;
        padding: 14px 16px 0;
    }

    .job-content-section .jobs-item.job-grid .card-grid-2-image-left .flash {
        left: 12px;
        top: 12px;
    }

    .job-content-section .jobs-item.job-grid .card-grid-2-image-left .image-box {
        align-items: center;
        background: #f8fafc;
        border-radius: 8px;
        display: flex;
        flex: 0 0 42px;
        height: 42px;
        justify-content: center;
        margin-right: 10px;
        overflow: hidden;
        padding: 0;
        width: 42px;
    }

    .job-content-section .jobs-item.job-grid .card-grid-2-image-left .image-box img {
        display: block;
        height: 100%;
        max-height: 42px;
        max-width: 42px;
        object-fit: contain;
        width: 100%;
    }

    .job-content-section .jobs-item.job-grid .card-grid-2-image-left .right-info {
        flex: 1 1 auto;
        line-height: 16px;
        min-width: 0;
        overflow: hidden;
    }

    .job-content-section .jobs-item.job-grid .card-grid-2-image-left .name-job {
        display: -webkit-box;
        font-size: 11px;
        height: 32px;
        line-height: 16px;
        margin-bottom: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        width: auto;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .job-content-section .jobs-item.job-grid .company-verified-badge svg {
        height: 13px;
        width: 13px;
    }

    .job-content-section .jobs-item.job-grid .card-grid-2-image-left .location-small {
        display: block;
        font-size: 10px;
        height: 14px;
        line-height: 14px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .job-content-section .jobs-item.job-grid .card-block-info {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        overflow: hidden;
        padding: 12px 16px 16px;
    }

    .job-content-section .jobs-item.job-grid .card-block-info h6 {
        height: 40px;
        line-height: 20px;
        margin-bottom: 0;
        overflow: hidden;
        white-space: normal;
    }

    .job-content-section .jobs-item.job-grid .card-block-info h6 a {
        display: -webkit-box;
        font-size: 14px;
        line-height: 20px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .job-content-section .jobs-item.job-grid .card-block-info .mt-5 {
        height: 30px;
        line-height: 14px;
        overflow: hidden;
        white-space: nowrap;
    }

    .job-content-section .jobs-item.job-grid .card-briefcase,
    .job-content-section .jobs-item.job-grid .card-time,
    .job-content-section .jobs-item.job-grid .salary-information,
    .job-content-section .jobs-item.job-grid .salary-information span {
        font-size: 10px;
        line-height: 14px;
    }

    .job-content-section .jobs-item.job-grid .job-description {
        display: block;
        font-size: 9px !important;
        height: 54px;
        line-height: 18px !important;
        margin-top: 10px !important;
        min-height: 54px;
        overflow: hidden;
    }

    .job-content-section .jobs-item.job-grid .card-block-info > .mt-30:not(.card-2-bottom) {
        height: 0;
        margin-top: 0 !important;
        overflow: hidden;
    }

    .job-content-section .jobs-item.job-grid .card-2-bottom {
        margin-top: auto !important;
        min-height: 68px;
        overflow: hidden;
    }

    .job-content-section .jobs-item.job-grid .salary-information {
        height: 18px;
        overflow: hidden;
        white-space: nowrap;
    }

    .job-content-section .jobs-item.job-grid .card-2-bottom .col-12.mt-3 {
        margin-top: 12px !important;
    }

    .job-content-section .jobs-item.job-grid .job-card-actions {
        align-items: center;
        display: flex;
        gap: 6px;
        justify-content: space-between;
    }

    .job-content-section .jobs-item.job-grid .job-card-actions > .job-card-action {
        display: flex;
        flex: 0 0 auto;
        margin-left: auto;
        min-width: 0;
    }

    .job-content-section .jobs-item.job-grid .btn-view-details,
    .job-content-section .jobs-item.job-grid .btn-apply,
    .job-content-section .jobs-item.job-grid .btn-apply-now {
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

    .job-content-section .jobs-item.job-grid .btn-view-details {
        background-color: transparent;
        border: 0;
        box-shadow: none;
        color: var(--primary-color);
        flex: 0 0 28px;
        outline: 0;
        padding: 0;
        width: 28px;
    }

    .job-content-section .jobs-item.job-grid .btn-view-details:hover {
        background-color: transparent;
        border: 0;
        box-shadow: none;
        color: var(--secondary-color);
    }

    .job-content-section .jobs-item.job-grid .job-card-action .btn-apply-now,
    .job-content-section .jobs-item.job-grid .job-card-action .btn-apply {
        background-color: var(--primary-color);
        border: 0;
        border-radius: 999px;
        box-shadow: 0 8px 14px -8px rgba(81, 146, 255, 0.8);
        color: #ffffff;
        font-weight: 700;
        min-width: 72px;
        width: auto;
    }

    .job-content-section .jobs-item.job-grid .job-card-action .btn-apply-now:hover,
    .job-content-section .jobs-item.job-grid .job-card-action .btn-apply:hover {
        background-color: var(--secondary-color);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .job-content-section .jobs-item.job-grid .btn-view-details i {
        color: var(--primary-color);
        font-size: 13px;
        line-height: 1;
    }

    .job-content-section .jobs-item.job-grid .btn-view-details:hover i {
        color: var(--secondary-color);
    }

    .job-content-section .job-items:not(.job-grid) .job-card-actions {
        align-items: center;
        display: flex;
        gap: 6px;
        justify-content: flex-end;
    }

    .job-content-section .job-items:not(.job-grid) .btn-view-details {
        align-items: center;
        background-color: transparent;
        border: 0;
        box-shadow: none;
        color: var(--primary-color);
        display: inline-flex;
        flex: 0 0 28px;
        height: 26px;
        justify-content: center;
        line-height: 1;
        min-height: 26px;
        outline: 0;
        padding: 0;
        width: 28px;
    }

    .job-content-section .job-items:not(.job-grid) .btn-view-details:hover {
        background-color: transparent;
        border: 0;
        box-shadow: none;
        color: var(--secondary-color);
    }

    .job-content-section .job-items:not(.job-grid) .btn-view-details i {
        color: var(--primary-color);
        font-size: 13px;
        line-height: 1;
    }

    .job-content-section .job-items:not(.job-grid) .btn-view-details:hover i {
        color: var(--secondary-color);
    }

    .job-content-section .job-items:not(.job-grid) .job-card-action {
        display: flex;
        flex: 0 0 auto;
    }

    .job-content-section .job-items:not(.job-grid) .job-card-action .btn-apply-now,
    .job-content-section .job-items:not(.job-grid) .job-card-action .btn-apply {
        background-color: var(--primary-color);
        border: 0;
        border-radius: 999px;
        box-shadow: 0 8px 14px -8px rgba(81, 146, 255, 0.8);
        color: #ffffff;
        font-weight: 700;
    }

    .job-content-section .job-items:not(.job-grid) .job-card-action .btn-apply-now:hover,
    .job-content-section .job-items:not(.job-grid) .job-card-action .btn-apply:hover {
        background-color: var(--secondary-color);
        color: #ffffff;
        transform: translateY(-1px);
    }
</style>

<div class="content-page job-content-section">
    <div class="box-filters-job">
        <div class="row">
            <div class="col-xl-6 col-lg-5 jobs-listing-container">
                <span class="text-small text-showing showing-of-results">
                    @if ($jobs->total() > 0)
                        {{ __('Showing :from-:to of :total job(s)', [
                            'from' => $jobs->firstItem(),
                            'to' => $jobs->lastItem(),
                            'total' => $jobs->total(),
                        ]) }}
                    @endif
                </span>
            </div>
            <div class="col-xl-6 col-lg-7 text-lg-end mt-sm-15">
                <div class="display-flex2">
                    <div class="box-border btn-advanced-filter">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-filter"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-8.5l-4.48 -4.928a2 2 0 0 1 -.52 -1.345v-2.227z" /></svg>
                    </div>
                    <div class="box-border mr-10">
                        <span class="text-sort_by">{{ __('Show') }}:</span>
                        <div class="dropdown dropdown-sort">
                            <button class="btn dropdown-toggle" id="dropdownSort" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-display="static">
                                <span>{{ $jobs->perPage() }}</span>
                                <i class="fi-rr-angle-small-down"></i>
                            </button>
                            <ul class="dropdown-menu js-dropdown-clickable dropdown-menu-light" aria-labelledby="dropdownSort">
                                <li>
                                    @foreach($perPages ?? JobBoardHelper::getPerPageParams() as $value)
                                        <a class="dropdown-item per-page-item" href="#" data-per-page="{{ $value }}">{{ $value }}</a>
                                    @endforeach
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="box-border">
                        @include(Theme::getThemeNamespace('views.job-board.partials.sort-by-dropdown'))
                    </div>
                    <div class="box-view-type">
                        <a class="view-type layout-job" href="#" data-layout="list">
                            <img src="{{ Theme::asset()->url('imgs/template/icons/icon-list.svg') }}" alt="{{ __('List layout') }}">
                        </a>
                        <a class="view-type layout-job" href="#" data-layout="grid">
                            <img src="{{ Theme::asset()->url('imgs/template/icons/icon-grid.svg') }}" alt="{{ __('Grid layout') }}">
                        </a>
                        @if (theme_option('show_map_on_jobs_page', 'yes') === 'yes' && $jobs->isNotEmpty())
                            <a @class(['view-type layout-job map', 'active' => $layout === 'map']) href="#" data-layout="map">
                                <img src="{{ Theme::asset()->url('imgs/template/map/map' . ($layout === 'map' ? '-active' : null) . '.png') }}" alt="{{ __('Map layout') }}">
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row showing-of-results">
        {!! Theme::partial('loading') !!}

        @forelse($jobs as $job)
            @include(Theme::getThemeNamespace('views.job-board.partials.job-item-' . $template), ['job' => $job])
        @empty
            @include(Theme::getThemeNamespace('views.job-board.partials.job-item-empty'))
        @endforelse

        @if($isMapActive)
            <div class="col-12">
                <div class="col-lg-12 jobs-list-sidebar job-map-section d-lg-block">
                    <div class="right-map h-100">
                        <div class="position-sticky sticky-top">
                            <div class="w-100 bg-light" style="height: 100vh; width:100%">
                                <div class="jobs-list-map h-100" data-center="{{ json_encode(JobBoardHelper::getMapCenterLatLng()) }}"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@if(! $isMapActive)
    {!! $jobs->withQueryString()->links(Theme::getThemeNamespace('partials.pagination')) !!}
@endif
