@php
    Theme::asset()->container('footer')->usePath()->add('candidates-filter', 'js/candidates-filter.js');
    Theme::asset()->container('footer')->usePath()->add('select2-js', 'plugins/select2.min.js', ['jquery']);

    use Botble\JobBoard\Models\Account;
    use Botble\JobBoard\Models\CvReveal;
    use Botble\JobBoard\Models\JobSkill;

    $isEmployer     = auth('account')->check() && auth('account')->user()->isEmployer();
    $authEmployer   = $isEmployer ? auth('account')->user() : null;
    $revealedIds    = $authEmployer
        ? CvReveal::query()->where('employer_id', $authEmployer->id)->pluck('candidate_id')->all()
        : [];
    $revealService  = app(\Botble\JobBoard\Supports\CvRevealService::class);
    $revealCheck    = $authEmployer ? $revealService->canReveal($authEmployer) : ['can' => false, 'reason' => 'no_access', 'cost' => (int) setting('cv_reveal_credit_cost', 1)];
    $hasSubscriptionAccess = $authEmployer ? $revealService->hasSubscriptionAccess($authEmployer) : false;
    $canReveal      = (bool) ($revealCheck['can'] ?? false);
    $canRevealFree  = $canReveal && (int) ($revealCheck['cost'] ?? 0) === 0;
    $revealCost     = (int) setting('cv_reveal_credit_cost', 1);

    $allSkills      = JobSkill::orderBy('name')->pluck('name', 'id');
    $selectedSkills = array_filter((array) request('skill', []));
    $selectedCountry = is_plugin_active('location') ? wakanda_selected_country() : null;
    $selectedCountryId = $selectedCountry ? (int) $selectedCountry->id : null;
    $selectedStateId = (int) request('state_id');
    $selectedState = null;
    $allStates = collect();

    $allCities = collect();
    if (is_plugin_active('location')) {
        if ($selectedCountryId) {
            $allStates = \Botble\Location\Models\State::query()
                ->where('country_id', $selectedCountryId)
                ->wherePublished()
                ->orderBy('order')
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        if ($selectedStateId) {
            $selectedState = \Botble\Location\Models\State::query()
                ->where('id', $selectedStateId)
                ->when($selectedCountryId, fn ($query) => $query->where('country_id', $selectedCountryId))
                ->first();
        }

        if ($selectedState) {
            $allCities = \Botble\Location\Models\City::query()
                ->where('state_id', $selectedState->id)
                ->wherePublished()
                ->orderBy('order')
                ->orderBy('name')
                ->pluck('name', 'id');
        }
    }
    $selectedCities = $selectedState ? array_filter((array) request('city_id', [])) : [];
@endphp

<link rel="stylesheet" href="{{ asset('vendor/core/core/base/libraries/select2/css/select2.min.css') }}">
<style>
    /* Select2 — jobbox theme match */
    .talent-select2 .select2-selection--multiple {
        border: 1px solid #e0e6f7 !important;
        border-radius: 8px !important;
        min-height: 44px !important;
        max-width: 100% !important;
        padding: 4px 8px !important;
        background: #fff !important;
        cursor: pointer;
        overflow: hidden;
    }
    .talent-select2 .select2-selection--single {
        border: 1px solid #e0e6f7 !important;
        border-radius: 8px !important;
        height: 44px !important;
        background: #fff !important;
    }
    .talent-select2 .select2-selection--single .select2-selection__rendered {
        color: #4f5e64;
        font-size: 13px;
        line-height: 42px !important;
        padding-left: 12px !important;
        padding-right: 28px !important;
    }
    .talent-select2 .select2-selection--single .select2-selection__arrow {
        height: 42px !important;
        right: 8px !important;
    }
    .talent-select2.select2-container--focus .select2-selection--multiple {
        border-color: #3c65f5 !important;
        box-shadow: 0 0 0 3px rgba(60,101,245,.1) !important;
    }
    .talent-select2.select2-container--focus .select2-selection--single {
        border-color: #3c65f5 !important;
        box-shadow: 0 0 0 3px rgba(60,101,245,.1) !important;
    }
    .talent-select2.select2-container { max-width: 100% !important; }
    .talent-select2 .select2-selection--multiple .select2-selection__rendered {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
        max-width: 100%;
        padding: 0 !important;
    }
    .talent-select2 .select2-selection__choice {
        background: #e8f0fe; border: none !important; color: #3c65f5;
        border-radius: 20px; padding: 2px 10px; font-size: 12px; line-height: 20px; margin: 1px 0;
        max-width: 100%;
        white-space: normal;
        overflow-wrap: anywhere;
    }
    .talent-select2 .select2-selection__choice__remove { color: #3c65f5; margin-right: 4px; opacity: .7; }
    .talent-select2 .select2-selection__choice__remove:hover { opacity: 1; }
    .talent-select2 .select2-search--inline .select2-search__field { font-size: 13px; margin: 3px 0; }
    .talent-hub-dropdown .select2-results__options { max-height: 200px; overflow-y: auto; }
    .talent-hub-dropdown .select2-results__option { font-size: 13px; padding: 7px 12px; }
    .talent-hub-dropdown .select2-results__option--highlighted { background: #3c65f5 !important; }
    .talent-hub-dropdown .select2-search--dropdown { padding: 8px; }
    .talent-hub-dropdown .select2-search--dropdown .select2-search__field {
        border: 1px solid #e0e6f7; border-radius: 6px; padding: 6px 10px; font-size: 13px; width: 100%;
    }
    .talent-hub-multi-select {
        min-height: 44px;
    }
    .talent-filter-card {
        overflow: visible;
    }
    .talent-location-disabled .select2-selection {
        background: #f8f9fc !important;
        cursor: not-allowed;
    }
    .talent-skills-list {
        position: relative;
    }
    .talent-skills-toggle {
        align-items: center;
        background: #fff;
        border: 1px solid #e0e6f7;
        border-radius: 8px;
        color: #4f5e64;
        display: flex;
        font-size: 13px;
        justify-content: space-between;
        min-height: 44px;
        padding: 8px 12px;
        text-align: left;
        width: 100%;
    }
    .talent-skills-toggle:after {
        content: "⌄";
        color: #8a94a6;
        font-size: 16px;
        line-height: 1;
        margin-left: 10px;
    }
    .talent-skills-list.is-open .talent-skills-toggle {
        border-color: #3c65f5;
        box-shadow: 0 0 0 3px rgba(60,101,245,.1);
    }
    .talent-skills-panel {
        background: #fff;
        border: 1px solid #e0e6f7;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, .12);
        display: none;
        left: 0;
        overflow: hidden;
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        z-index: 20;
    }
    .talent-skills-list.is-open .talent-skills-panel {
        display: block;
    }
    .talent-skills-search {
        border: 0;
        border-bottom: 1px solid #e0e6f7;
        border-radius: 0;
        font-size: 13px;
        min-height: 42px;
    }
    .talent-skills-options {
        display: block;
        max-height: 184px;
        overflow-y: auto;
        padding: 8px 10px;
    }
    .talent-skill-option {
        align-items: center;
        display: flex !important;
        gap: 8px;
        margin: 0;
        min-height: 30px;
        padding: 3px 0;
        width: 100%;
    }
    .talent-skill-option span {
        color: #4f5e64;
        font-size: 13px;
        line-height: 18px;
        overflow-wrap: anywhere;
    }
    .talent-skill-summary {
        color: #3c65f5;
        font-size: 12px;
        font-weight: 600;
        min-height: 18px;
        padding: 0 10px 8px;
    }
    .talent-location-select {
        border: 1px solid #e0e6f7;
        border-radius: 8px;
        color: #4f5e64;
        font-size: 13px;
        min-height: 44px;
    }
    .btn-sign-in-reveal {
        background: transparent;
        border: 1px solid #3c65f5;
        color: #3c65f5;
        border-radius: 6px;
        padding: 3px 10px;
        white-space: nowrap;
        transition: background .18s, color .18s;
    }
    .btn-sign-in-reveal:hover,
    .btn-sign-in-reveal:focus {
        background: #3c65f5;
        color: #fff;
        border-color: #3c65f5;
    }
    .btn-reveal-contact {
        background: transparent;
        border: 1px solid var(--primary-color, #530f93);
        color: var(--primary-color, #530f93);
        font-weight: 500;
        transition: background .18s, color .18s, border-color .18s;
    }
    .btn-reveal-contact:hover,
    .btn-reveal-contact:focus {
        background: var(--primary-color, #530f93) !important;
        border-color: var(--primary-color, #530f93) !important;
        color: #fff !important;
    }
    .btn-reveal-contact:hover i,
    .btn-reveal-contact:focus i {
        color: #fff !important;
    }
    @media (max-width: 767px) {
        .talent-filter-card {
            padding: 20px !important;
        }
    }
</style>

<div class="container candidates-list">
    @if (is_plugin_active('ads'))
        {!! apply_filters('ads_render', null, 'candidate_list_before', ['class' => 'my-2 text-center']) !!}
    @endif

    <section class="section-box-2">
        <div class="container">
            <div class="banner-hero banner-company">
                <div class="block-banner text-center">
                    <h3 class="wow animate__animated animate__fadeInUp">{!! BaseHelper::clean($shortcode->title) !!}</h3>
                    <div class="font-sm color-text-paragraph-2 mt-10 wow animate__animated animate__fadeInUp" data-wow-delay=".1s">{!! BaseHelper::clean($shortcode->description) !!}</div>
                    <div class="box-list-character">
                        <ul>
                            @foreach(range('a', 'z') as $char)
                                <li>
                                    <a href="javascript:void(0)" class="keyword @if(request()->query('keyword') == $char) active @endif" data-keyword="{{ $char }}">{{ $char }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Filter card --}}
    <section class="section-box-2 mt-30 mb-10">
        <div class="container">
            <div class="box-shadow-bdrd-15 p-30 talent-filter-card" style="padding-left:16px;padding-right:16px;">
                {{-- Row 1: keyword + skills + state + city --}}
                <div class="row g-3 mb-20">
                    <div class="col-xl-3 col-lg-6 col-md-12">
                        <label class="font-sm color-text-mutted mb-5 fw-semibold"><i class="fi-rr-search me-1"></i>{{ __('Name or keyword') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fi-rr-user text-muted"></i></span>
                            <input class="form-control border-start-0" id="filter-keyword" name="keyword"
                                value="{{ request('keyword') }}" placeholder="{{ __('e.g. John, Accountant…') }}">
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <label class="font-sm color-text-mutted mb-5 fw-semibold"><i class="fi-rr-tags me-1"></i>{{ __('Skills') }}</label>
                        <div class="talent-skills-list">
                            <button type="button" class="talent-skills-toggle" id="skill-toggle">
                                <span id="skill-toggle-label">{{ __('Any skill') }}</span>
                            </button>
                            <div class="talent-skills-panel" id="skill-panel">
                                <input type="search" class="form-control talent-skills-search" id="skill-search" placeholder="{{ __('Search skills') }}">
                                <div class="talent-skills-options" id="skill-options">
                                    @foreach($allSkills as $id => $name)
                                        <label class="talent-skill-option" data-skill-name="{{ strtolower($name) }}">
                                            <input type="checkbox" class="form-check-input skill-checkbox" value="{{ $id }}" @checked(!empty($selectedSkills) && in_array($id, $selectedSkills))>
                                            <span>{{ $name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="talent-skill-summary" id="skill-summary"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <label class="font-sm color-text-mutted mb-5 fw-semibold"><i class="fi-rr-map me-1"></i>{{ __('Region / State') }}</label>
                        @if($selectedCountryId)
                            <select id="state-select" name="state_id" class="form-select talent-location-select" data-country-id="{{ $selectedCountryId }}">
                                <option value="">{{ __('Any region/state') }}</option>
                                @foreach($allStates as $id => $name)
                                    <option value="{{ $id }}" @selected($selectedState && (int) $selectedState->id === (int) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        @else
                            <select id="state-select" class="form-select talent-location-select" disabled>
                                <option>{{ __('Select a country first') }}</option>
                            </select>
                        @endif
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <label class="font-sm color-text-mutted mb-5 fw-semibold"><i class="fi-rr-marker me-1"></i>{{ __('City') }}</label>
                        @if($selectedCountryId)
                            <select id="city-select" name="city_id" class="form-select talent-location-select" data-country-id="{{ $selectedCountryId }}" data-url="{{ route('public.ajax.talent-cities') }}" @disabled(! $selectedState)>
                                <option value="">{{ __('Any city') }}</option>
                                @foreach($allCities as $id => $name)
                                    <option value="{{ $id }}" @selected(!empty($selectedCities) && in_array($id, $selectedCities))>{{ $name }}</option>
                                @endforeach
                            </select>
                        @else
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fi-rr-marker text-muted"></i></span>
                                <input class="form-control border-start-0" id="filter-location" name="location" value="{{ request('location') }}" placeholder="{{ __('Select a country first') }}" disabled>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Row 2: experience + availability + open to work + search btn --}}
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="font-sm color-text-mutted mb-5 fw-semibold"><i class="fi-rr-briefcase me-1"></i>{{ __('Experience') }}</label>
                        <select class="form-select" id="filter-experience">
                            @foreach(Account::experienceYearsOptions() as $val => $label)
                                <option value="{{ $val }}" @selected(request()->has('experience_years') && request('experience_years') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="font-sm color-text-mutted mb-5 fw-semibold"><i class="fi-rr-calendar me-1"></i>{{ __('Availability') }}</label>
                        <select class="form-select" id="filter-availability">
                            @foreach(Account::availabilityOptions() as $val => $label)
                                <option value="{{ $val }}" @selected(request('availability') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 d-flex align-items-center pt-20">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="filter-open-to-work" @checked(request('open_to_work'))>
                            <label class="form-check-label" for="filter-open-to-work">
                                <span class="badge bg-success me-1" style="font-size:9px">●</span>
                                <span class="font-sm fw-semibold">{{ __('Open to Work only') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 ms-auto">
                        <button class="btn btn-default w-100" id="talent-search-btn" type="button">
                            <i class="fi-rr-search me-2"></i>{{ __('Search') }}
                        </button>
                    </div>
                    <div class="col-lg-1 col-md-2">
                        <a href="{{ url()->current() }}" class="btn btn-outline-secondary w-100" title="{{ __('Reset filters') }}">
                            <i class="fi-rr-refresh"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Hidden AJAX form (drives candidate-filter.js) --}}
    <form action="{{ route('public.ajax.candidates') }}" class="candidate-filter-form" style="display:none">
        <input type="hidden" id="ajax-keyword"          name="keyword"          value="{{ BaseHelper::stringify(request()->query('keyword')) }}">
        @foreach($selectedSkills as $skillId)
            <input class="ajax-skill" type="hidden" name="skill[]" value="{{ $skillId }}">
        @endforeach
        <input type="hidden" id="ajax-country"         name="country_id"        value="{{ $selectedCountryId ?: '' }}">
        <input type="hidden" id="ajax-state"           name="state_id"          value="{{ $selectedState ? $selectedState->id : '' }}">
        @foreach($selectedCities as $cityId)
            <input class="ajax-city" type="hidden" name="city_id[]" value="{{ $cityId }}">
        @endforeach
        <input type="hidden" id="ajax-location"         name="location"         value="{{ BaseHelper::stringify(request()->query('location')) }}">
        <input type="hidden" id="ajax-experience"       name="experience_years" value="{{ BaseHelper::stringify(request()->query('experience_years')) }}">
        <input type="hidden" id="ajax-availability"     name="availability"     value="{{ BaseHelper::stringify(request()->query('availability')) }}">
        <input type="hidden" id="ajax-open-to-work"     name="open_to_work"     value="{{ BaseHelper::stringify(request()->query('open_to_work')) }}">
        <input type="hidden" name="per_page"            value="{{ BaseHelper::stringify(request()->query('per_page', 12)) }}">
        <input type="hidden" name="sort_by"             value="{{ BaseHelper::stringify(request()->query('sort_by', 'newest')) }}">
        <input type="hidden" name="page"                value="{{ BaseHelper::stringify(request()->query('page', 1)) }}">
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var $ = jQuery;

        function updateSkillSummary() {
            var count = $('.skill-checkbox:checked').length;
            var text = count ? count + ' ' + @json(__('skill(s) selected')) : @json(__('Any skill'));
            $('#skill-summary').text(text);
            $('#skill-toggle-label').text(text);
        }

        $('#skill-toggle').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $('.talent-skills-list').toggleClass('is-open');
            if ($('.talent-skills-list').hasClass('is-open')) {
                $('#skill-search').trigger('focus');
            }
        });

        $('#skill-panel').on('click', function (e) {
            e.stopPropagation();
        });

        $(document).on('click', function () {
            $('.talent-skills-list').removeClass('is-open');
        });

        $('#skill-search').on('input', function () {
            var term = $(this).val().toLowerCase();

            $('.talent-skill-option').each(function () {
                $(this).toggle($(this).data('skill-name').indexOf(term) !== -1);
            });
        });

        $('.skill-checkbox').on('change', function () {
            updateSkillSummary();
            syncAndSearch();
        });

        updateSkillSummary();

        function setCityEnabled(enabled) {
            var $city = $('#city-select');

            if (! $city.length) {
                return;
            }

            $city.prop('disabled', ! enabled);
        }

        function loadCitiesForState(stateId, selectedCityIds) {
            var $city = $('#city-select');

            if (! $city.length) {
                return;
            }

            $city.empty();
            $city.append(new Option(@json(__('Any city')), '', false, false));

            if (! stateId) {
                setCityEnabled(false);
                return;
            }

            setCityEnabled(true);

            $.ajax({
                url: $city.data('url'),
                method: 'GET',
                headers: { Accept: 'application/json' },
                data: {
                    country_id: $city.data('country-id'),
                    state_id: stateId,
                    per_page: 200
                },
                success: function (res) {
                    var selected = selectedCityIds || '';
                    (res.results || []).forEach(function (city) {
                        var option = new Option(city.text, city.id, false, String(selected) === String(city.id));
                        $city.append(option);
                    });
                }
            });
        }

        // --- Sync visible filters → hidden AJAX form, then fire AJAX ---
        function syncAndSearch() {
            // keyword
            $('#ajax-keyword').val($('#filter-keyword').val());

            // skills — remove old hidden inputs, add fresh ones
            $('.ajax-skill').remove();
            var $ajaxForm = $('form.candidate-filter-form');
            $('.skill-checkbox:checked').each(function() {
                var v = $(this).val();
                $ajaxForm.append($('<input>', { class: 'ajax-skill', type: 'hidden', name: 'skill[]', value: v }));
            });

            // country/state
            $('#ajax-country').val($('#state-select').data('country-id') || '');
            $('#ajax-state').val($('#state-select').val() || '');

            // cities
            $('.ajax-city').remove();
            var cityId = $('#city-select').val();
            if (cityId) {
                $ajaxForm.append($('<input>', { class: 'ajax-city', type: 'hidden', name: 'city_id[]', value: cityId }));
            }

            // text location fallback
            $('#ajax-location').val($('#filter-location').val() || '');

            // experience & availability
            $('#ajax-experience').val($('#filter-experience').val());
            $('#ajax-availability').val($('#filter-availability').val());

            // open to work
            $('#ajax-open-to-work').val($('#filter-open-to-work').is(':checked') ? '1' : '');

            // reset to page 1 then fire
            $('form.candidate-filter-form input[name="page"]').val(1);

            // trigger the existing candidates-filter.js AJAX call
            var serialized = $ajaxForm.serialize();
            var ajaxUrl    = $ajaxForm.attr('action');
            var base       = location.origin + location.pathname;
            $('.loading-ring').show();
            $.ajax({
                url: ajaxUrl, method: 'GET', data: serialized,
                beforeSend: function() { window.history.pushState(serialized, null, base + '?' + serialized); },
                success: function(res) {
                    $('.candidate-list').html(res.data.list);
                    $('.text-showing').text(res.data.total_text);
                },
                complete: function() { $('.loading-ring').hide(); }
            });
        }

        // Search button
        $('#talent-search-btn').on('click', syncAndSearch);

        // Keyword: search on Enter
        $('#filter-keyword').on('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); syncAndSearch(); } });

        // Dropdowns: search on change
        $('#filter-experience, #filter-availability').on('change', syncAndSearch);
        $('#city-select').on('change', syncAndSearch);
        $('#state-select').on('change', function () {
            loadCitiesForState($(this).val(), []);
            syncAndSearch();
        });

        setCityEnabled(!! $('#state-select').val());

        // Open to work toggle
        $('#filter-open-to-work').on('change', syncAndSearch);
    });
    </script>

    <section class="mt-30">
        <div class="container position-relative">
            <div class="content-page">
                {!! Theme::partial('loading') !!}
                <div class="box-filters-job">
                    <div class="row">
                        <div class="col-xl-6 col-lg-5">
                            <span class="text-small text-showing">
                                {{ __('Showing :from-:to of :total candidate(s)', [
                                    'from' => $candidates->firstItem() ?: 0,
                                    'to'   => $candidates->lastItem() ?: 0,
                                    'total'=> $candidates->total(),
                                ]) }}
                            </span>
                        </div>
                        <div class="col-xl-6 col-lg-7 text-lg-end mt-sm-15">
                            <div class="display-flex2">
                                <div class="box-border mr-10">
                                    <span class="text-sort_by">{{ __('Show') }}:</span>
                                    <div class="dropdown dropdown-sort">
                                        <button class="btn dropdown-toggle" id="dropdownSort" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-display="static">
                                            <span>{{ $candidates->perPage() }}</span>
                                            <i class="fi-rr-angle-small-down"></i>
                                        </button>
                                        <ul class="dropdown-menu js-dropdown-clickable dropdown-menu-light" aria-labelledby="dropdownSort">
                                            @foreach(JobBoardHelper::getPerPageParams() as $perPage)
                                                <li><a class="dropdown-item per-page" data-per-page="{{ $perPage }}">{{ $perPage }}</a></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                <div class="box-border">
                                    @include(Theme::getThemeNamespace('views.job-board.partials.sort-by-dropdown'))
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Paywall notice for guests / non-employers --}}
                @php $revealPriceLabel = setting('cv_reveal_price_label', ''); @endphp
                @if(! $isEmployer)
                    <div class="alert alert-info d-flex align-items-center gap-3 mb-4" style="background:#eef2ff;border-color:#c7d4fc;">
                        <i class="fi-rr-lock fs-4 flex-shrink-0 text-primary"></i>
                        <div class="flex-grow-1">
                            <strong>{{ __('Contact details are hidden.') }}</strong>
                            {{ __('Sign in as an employer to access full profiles, phone numbers, emails and CVs.') }}
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <a href="{{ route('public.account.login') }}" class="btn btn-sm btn-default">{{ __('Sign In') }}</a>
                            <a href="{{ route('public.account.register') }}" class="btn btn-sm btn-outline-primary">{{ __('Register') }}</a>
                        </div>
                    </div>
                @elseif(! $hasSubscriptionAccess)
                    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
                        <i class="fi-rr-unlock fs-4 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <strong>{{ __('Candidate contacts are locked.') }}</strong>
                            {{ __('Upgrade your subscription to view all profiles, or reveal one profile for :cost credit(s).', ['cost' => $revealCost]) }}
                            @if($authEmployer)
                                <span class="d-block font-xs mt-1">
                                    {{ __('You currently have :credits credit(s).', ['credits' => number_format((int) $authEmployer->credits)]) }}
                                </span>
                            @endif
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <a href="{{ route('public.account.subscription.index') }}" class="btn btn-sm btn-warning">{{ __('Upgrade Subscription') }}</a>
                            <a href="{{ route('public.account.credits') }}" class="btn btn-sm btn-outline-primary">{{ __('Buy Credits') }}</a>
                        </div>
                    </div>
                @endif

                <div class="row candidate-list">
                    @include(Theme::getThemeNamespace('views.job-board.partials.candidate-list'), [
                        'isEmployer'   => $isEmployer,
                        'authEmployer' => $authEmployer,
                        'revealedIds'  => $revealedIds,
                        'hasSubscriptionAccess' => $hasSubscriptionAccess,
                        'canReveal'    => $canReveal,
                        'canRevealFree'=> $canRevealFree,
                        'revealCost'   => $revealCost,
                    ])
                </div>
            </div>
        </div>
    </section>

    @if (is_plugin_active('ads'))
        {!! apply_filters('ads_render', null, 'candidate_list_after', ['class' => 'my-2 text-center']) !!}
    @endif
</div>
