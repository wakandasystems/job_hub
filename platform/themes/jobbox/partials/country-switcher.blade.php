@php
    $countries = wakanda_localization_countries();
    $selectedCountry = wakanda_selected_country();
    $redirectUrl = url()->full();
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
            @foreach ($countries as $country)
                <li>
                    <a
                        @class(['dropdown-item country-switch-item', 'active' => $country->id === $selectedCountry->id])
                        href="{{ route('public.localization.country', ['country_id' => $country->id, 'redirect' => $redirectUrl]) }}"
                    >
                        <span class="country-switch-flag">{!! wakanda_country_flag($country->code) !!}</span>
                        <span>{{ $country->name }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
