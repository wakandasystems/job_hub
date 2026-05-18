@php
    $countries = wakanda_localization_countries();
    $selectedCountry = wakanda_selected_country();
    $redirectQuery = request()->query();
    unset($redirectQuery['country_id'], $redirectQuery['c']);
    $redirectUrl = url()->current() . ($redirectQuery ? '?' . http_build_query($redirectQuery) : '');
    $africanCountryCodes = [
        'DZ', 'AO', 'BJ', 'BW', 'BF', 'BI', 'CV', 'CM', 'CF', 'TD', 'KM', 'CG', 'CD', 'CI', 'DJ', 'EG',
        'GQ', 'ER', 'SZ', 'ET', 'GA', 'GM', 'GH', 'GN', 'GW', 'KE', 'LS', 'LR', 'LY', 'MG', 'MW', 'ML',
        'MR', 'MU', 'MA', 'MZ', 'NA', 'NE', 'NG', 'RW', 'ST', 'SN', 'SC', 'SL', 'SO', 'ZA', 'SS', 'SD',
        'TZ', 'TG', 'TN', 'UG', 'ZM', 'ZW',
    ];
    $countries = $countries
        ->reject(fn ($country) => $country->id === $selectedCountry?->id)
        ->sortBy(fn ($country) => [
            in_array(strtoupper((string) $country->code), $africanCountryCodes, true) ? 0 : 1,
            strtolower((string) $country->name),
        ])
        ->values();
@endphp

@if ($countries->isNotEmpty() && $selectedCountry)
    <div class="country-switch dropdown">
        <button
            class="country-switch-toggle"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="{{ __('Select country') }}"
        >
            <span class="country-switch-flag">{!! wakanda_country_flag($selectedCountry->code) !!}</span>
            <span class="country-switch-name">{{ $selectedCountry->name }}</span>
            <i class="fi-rr-angle-small-down"></i>
        </button>
        <ul class="dropdown-menu country-switch-menu">
            <li class="country-switch-search-wrap">
                <input
                    class="country-switch-search"
                    type="search"
                    placeholder="{{ __('Search country') }}"
                    aria-label="{{ __('Search country') }}"
                    autocomplete="off"
                    data-country-switch-search
                >
            </li>
            <li class="country-switch-list-wrap">
                <ul class="country-switch-list">
            @foreach ($countries as $country)
                @php
                    $countryUrl = route('public.localization.country', [
                        'c' => wakanda_encode_country_id($country->id),
                        'redirect' => $redirectUrl,
                    ]);
                @endphp
                <li class="country-switch-option">
                    <a
                        class="dropdown-item country-switch-item"
                        href="{{ $countryUrl }}"
                        data-country-name="{{ strtolower($country->name) }}"
                    >
                        <span class="country-switch-flag">{!! wakanda_country_flag($country->code) !!}</span>
                        <span>{{ $country->name }}</span>
                    </a>
                </li>
            @endforeach
                </ul>
            </li>
            <li class="country-switch-empty" hidden>{{ __('No countries found') }}</li>
        </ul>
    </div>

    <script>
        if (! window.wakandaCountrySwitchBound) {
            window.wakandaCountrySwitchBound = true;

            document.addEventListener('input', function (event) {
                if (! event.target.matches('[data-country-switch-search]')) {
                    return;
                }

                const menu = event.target.closest('.country-switch-menu');
                const query = event.target.value.trim().toLowerCase();
                const options = menu.querySelectorAll('.country-switch-option');
                const empty = menu.querySelector('.country-switch-empty');
                let visible = 0;

                options.forEach(function (option) {
                    const link = option.querySelector('.country-switch-item');
                    const match = ! query || link.dataset.countryName.includes(query);

                    option.hidden = ! match;
                    visible += match ? 1 : 0;
                });

                empty.hidden = visible > 0;
            });

            document.addEventListener('click', function (event) {
                if (event.target.closest('.country-switch-search-wrap')) {
                    event.stopPropagation();
                }
            });

            document.addEventListener('shown.bs.dropdown', function (event) {
                const toggle = event.target.closest('.country-switch');
                if (toggle) {
                    const input = toggle.querySelector('[data-country-switch-search]');
                    if (input) {
                        input.focus();
                    }
                }
            });
        }
    </script>
@endif
