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
    $canRevealFree  = $authEmployer && app(\Botble\JobBoard\Supports\CvRevealService::class)->canReveal($authEmployer)['can'];
    $revealCost     = (int) setting('cv_reveal_credit_cost', 1);

    $allSkills      = JobSkill::orderBy('name')->pluck('name', 'id');
    $selectedSkills = array_filter((array) request('skill', []));

    $allCities = collect();
    if (is_plugin_active('location')) {
        $usedCityIds = \DB::table('jb_accounts')->whereNotNull('city_id')->distinct()->pluck('city_id');
        if ($usedCityIds->isNotEmpty()) {
            $allCities = \DB::table('cities')->whereIn('id', $usedCityIds)->orderBy('name')->pluck('name', 'id');
        }
    }
    $selectedCities = array_filter((array) request('city_id', []));
@endphp

<link rel="stylesheet" href="{{ asset('vendor/core/core/base/libraries/select2/css/select2.min.css') }}">
<style>
    /* Select2 — jobbox theme match */
    .talent-select2 .select2-selection--multiple {
        border: 1px solid #e0e6f7 !important;
        border-radius: 8px !important;
        min-height: 44px !important;
        padding: 4px 8px !important;
        background: #fff !important;
        cursor: pointer;
    }
    .talent-select2.select2-container--focus .select2-selection--multiple {
        border-color: #3c65f5 !important;
        box-shadow: 0 0 0 3px rgba(60,101,245,.1) !important;
    }
    .talent-select2 .select2-selection__rendered { display: flex; flex-wrap: wrap; gap: 3px; padding: 0 !important; }
    .talent-select2 .select2-selection__choice {
        background: #e8f0fe; border: none !important; color: #3c65f5;
        border-radius: 20px; padding: 2px 10px; font-size: 12px; line-height: 20px; margin: 1px 0;
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
    <section class="section-box mt-30 mb-10">
        <div class="box-shadow-bdrd-15 p-30">
                {{-- Row 1: keyword + skills + city --}}
                <div class="row g-3 mb-20">
                    <div class="col-lg-4 col-md-12">
                        <label class="font-sm color-text-mutted mb-5 fw-semibold"><i class="fi-rr-search me-1"></i>{{ __('Name or keyword') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fi-rr-user text-muted"></i></span>
                            <input class="form-control border-start-0" id="filter-keyword" name="keyword"
                                value="{{ request('keyword') }}" placeholder="{{ __('e.g. John, Accountant…') }}">
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-8">
                        <label class="font-sm color-text-mutted mb-5 fw-semibold"><i class="fi-rr-tags me-1"></i>{{ __('Skills') }}</label>
                        <select id="skill-select" name="skill[]" multiple class="w-100">
                            @foreach($allSkills as $id => $name)
                                <option value="{{ $id }}" @if(!empty($selectedSkills) && in_array($id, $selectedSkills)) selected @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label class="font-sm color-text-mutted mb-5 fw-semibold"><i class="fi-rr-marker me-1"></i>{{ __('City / Region') }}</label>
                        @if($allCities->isNotEmpty())
                            <select id="city-select" name="city_id[]" multiple class="w-100">
                                @foreach($allCities as $id => $name)
                                    <option value="{{ $id }}" @if(!empty($selectedCities) && in_array($id, $selectedCities)) selected @endif>{{ $name }}</option>
                                @endforeach
                            </select>
                        @else
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fi-rr-marker text-muted"></i></span>
                                <input class="form-control border-start-0" id="filter-location" name="location"
                                    value="{{ request('location') }}" placeholder="{{ __('Any city') }}">
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
    </section>

    {{-- Hidden AJAX form (drives candidate-filter.js) --}}
    <form action="{{ route('public.ajax.candidates') }}" class="candidate-filter-form" style="display:none">
        <input type="hidden" id="ajax-keyword"          name="keyword"          value="{{ BaseHelper::stringify(request()->query('keyword')) }}">
        @foreach($selectedSkills as $skillId)
            <input class="ajax-skill" type="hidden" name="skill[]" value="{{ $skillId }}">
        @endforeach
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

        // --- Select2 init ---
        var s2opts = { multiple: true, allowClear: true, closeOnSelect: false, width: '100%', containerCssClass: 'talent-select2', dropdownCssClass: 'talent-hub-dropdown' };
        $('#skill-select').select2(Object.assign({}, s2opts, { placeholder: '{{ __('Any skill') }}' }));
        $('#city-select').select2(Object.assign({}, s2opts, { placeholder: '{{ __('Any city') }}' }));

        // --- Sync visible filters → hidden AJAX form, then fire AJAX ---
        function syncAndSearch() {
            // keyword
            $('#ajax-keyword').val($('#filter-keyword').val());

            // skills — remove old hidden inputs, add fresh ones
            $('.ajax-skill').remove();
            var $ajaxForm = $('form.candidate-filter-form');
            $('#skill-select').val() && $('#skill-select').val().forEach(function(v) {
                $ajaxForm.append('<input class="ajax-skill" type="hidden" name="skill[]" value="' + v + '">');
            });

            // cities
            $('.ajax-city').remove();
            $('#city-select').length && $('#city-select').val() && $('#city-select').val().forEach(function(v) {
                $ajaxForm.append('<input class="ajax-city" type="hidden" name="city_id[]" value="' + v + '">');
            });

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
        $('#skill-select, #city-select').on('change', syncAndSearch);

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
                @if(! $isEmployer)
                    <div class="alert alert-info d-flex align-items-center gap-3 mb-4">
                        <i class="fi-rr-lock fs-4 flex-shrink-0"></i>
                        <div>
                            <strong>{{ __('Contact details are hidden.') }}</strong>
                            {{ __('Sign in as an employer to reveal phone, email and CVs.') }}
                            <a href="{{ route('public.account.login') }}" class="btn btn-sm btn-primary ms-2">{{ __('Sign In') }}</a>
                        </div>
                    </div>
                @elseif(! $canRevealFree)
                    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
                        <i class="fi-rr-unlock fs-4 flex-shrink-0"></i>
                        <div>
                            {{ __('Reveal a candidate contact for :cost credit(s) each — or get unlimited reveals with a subscription.', ['cost' => $revealCost]) }}
                            <a href="{{ route('public.account.subscription.index') }}" class="btn btn-sm btn-warning ms-2">{{ __('View Plans') }}</a>
                        </div>
                    </div>
                @endif

                <div class="row candidate-list">
                    @include(Theme::getThemeNamespace('views.job-board.partials.candidate-list'), [
                        'isEmployer'   => $isEmployer,
                        'authEmployer' => $authEmployer,
                        'revealedIds'  => $revealedIds,
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
