@php
    $currencies = get_all_currencies();
    $supportedLocales = Language::getSupportedLocales();
    if (empty($options)) {
        $options = [
            'before' => '',
            'lang_flag' => true,
            'lang_name' => true,
            'class' => '',
            'after' => '',
        ];
    }

    $displayLanguageSwitcher = $supportedLocales && count($supportedLocales) > 1;
    $displayCurrencySwitcher = $currencies->count() > 1;
@endphp

@if ($displayLanguageSwitcher || $displayCurrencySwitcher)
    <div class="mobile-menu-switcher">
        <ul class="mobile-menu font-heading">
            @if ($displayCurrencySwitcher)
                @php $currencyActive = get_application_currency()->title; @endphp
                <li class="has-children"><span class="menu-expand"><i class="fi-rr-angle-small-down"></i></span>
                    <a>
                        {{ $currencyActive }}
                        <div class="arrow-down"></div>
                    </a>
                    <ul class="sub-menu" style="display: none;">
                        <li class="mobile-currency-search-wrap" style="padding: 8px 15px;">
                            <input
                                type="search"
                                class="form-control form-control-sm mobile-currency-search"
                                placeholder="{{ __('Search currency') }}"
                                aria-label="{{ __('Search currency') }}"
                                autocomplete="off"
                            >
                        </li>
                        @foreach ($currencies as $currency)
                            @if ($currency->title != $currencyActive)
                                <li data-currency-code-mobile="{{ strtolower($currency->title) }}">
                                    <a href="{{ route('public.change-currency', $currency->title) }}">
                                        {{ $currency->title }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                        <li class="mobile-currency-empty" hidden style="padding: 8px 15px; color: #777;">{{ __('No currencies found') }}</li>
                    </ul>
                </li>
                <script>
                    document.addEventListener('input', function (e) {
                        if (! e.target.matches('.mobile-currency-search')) return;
                        const term = e.target.value.trim().toLowerCase();
                        const list = e.target.closest('.sub-menu');
                        const items = list.querySelectorAll('[data-currency-code-mobile]');
                        const empty = list.querySelector('.mobile-currency-empty');
                        let visible = 0;
                        items.forEach(function (item) {
                            const match = !term || item.dataset.currencyCodeMobile.includes(term);
                            item.hidden = !match;
                            if (match) visible++;
                        });
                        empty.hidden = visible > 0;
                    });
                    document.addEventListener('click', function (e) {
                        if (e.target.closest('.mobile-currency-search-wrap')) e.stopPropagation();
                    });
                </script>
            @endif

            @if ($displayLanguageSwitcher)
                @php
                    $languageDisplay = setting('language_display', 'all');
                    $showRelated = setting('language_show_default_item_if_current_version_not_existed', true);
                @endphp
                <li class="has-children"><span class="menu-expand"><i class="fi-rr-angle-small-down"></i></span>
                    <a>
                        {!! language_flag(Language::getCurrentLocaleFlag(), Language::getCurrentLocaleName()) !!}
                        <span>&nbsp;{{ Language::getCurrentLocaleName() }}</span>
                        <div class="arrow-down"></div>
                    </a>
                    <ul class="sub-menu" style="display: none;">
                        @foreach ($supportedLocales as $localeCode => $properties)
                            @if ($localeCode != Language::getCurrentLocale())
                                <li>
                                    <a href="{{ $showRelated ? Language::getLocalizedURL($localeCode) : url($localeCode) }}">
                                        @if (Arr::get($options, 'lang_flag', true) && ($languageDisplay == 'all' || $languageDisplay == 'flag'))
                                            {!! language_flag($properties['lang_flag'], $properties['lang_name']) !!}
                                        @endif
                                        @if (Arr::get($options, 'lang_name', true) && ($languageDisplay == 'all' || $languageDisplay == 'name'))
                                            &nbsp;<span>&nbsp;{{ $properties['lang_name'] }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>
            @endif
        </ul>
    </div>
@endif
