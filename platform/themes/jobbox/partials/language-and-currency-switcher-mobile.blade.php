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
                @php
                    $currencyActive = get_application_currency()->title;
                    $activeCurrencyMeta = wakanda_currency_meta($currencyActive);
                @endphp
                <li class="has-children"><span class="menu-expand"><i class="fi-rr-angle-small-down"></i></span>
                    <a title="{{ $activeCurrencyMeta['label'] }}" aria-label="{{ $activeCurrencyMeta['label'] }}">
                        <span aria-hidden="true">{!! $activeCurrencyMeta['flag'] !!}</span>
                        <span>{{ $currencyActive }}</span>
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
                                @php($currencyMeta = wakanda_currency_meta($currency->title))
                                <li data-currency-code-mobile="{{ strtolower($currency->title . ' ' . $currencyMeta['country'] . ' ' . $currencyMeta['name']) }}">
                                    <a href="{{ route('public.change-currency', $currency->title) }}" title="{{ $currencyMeta['label'] }}" aria-label="{{ $currencyMeta['label'] }}">
                                        <span aria-hidden="true">{!! $currencyMeta['flag'] !!}</span>
                                        <span>{{ $currency->title }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                        <li class="mobile-currency-empty" hidden style="padding: 8px 15px; color: #777;">{{ __('No currencies found') }}</li>
                    </ul>
                </li>
                <script>
                    (function () {
                        const perPage = 3;

                        function initMobileCurrency(wrap) {
                            const search = wrap.querySelector('.mobile-currency-search');
                            const items = Array.from(wrap.querySelectorAll('[data-currency-code-mobile]'));
                            const empty = wrap.querySelector('.mobile-currency-empty');

                            let prevEl = wrap.querySelector('.mobile-currency-prev');
                            let nextEl = wrap.querySelector('.mobile-currency-next');
                            let pageEl = wrap.querySelector('.mobile-currency-page');

                            if (!prevEl) {
                                const nav = document.createElement('li');
                                nav.className = 'mobile-currency-nav';
                                nav.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:6px 15px;';
                                nav.innerHTML = '<button type="button" class="mobile-currency-prev" style="background:none;border:none;font-size:18px;cursor:pointer;" disabled>‹</button>'
                                              + '<span class="mobile-currency-page" style="font-size:13px;"></span>'
                                              + '<button type="button" class="mobile-currency-next" style="background:none;border:none;font-size:18px;cursor:pointer;">›</button>';
                                wrap.appendChild(nav);
                                prevEl = nav.querySelector('.mobile-currency-prev');
                                nextEl = nav.querySelector('.mobile-currency-next');
                                pageEl = nav.querySelector('.mobile-currency-page');
                            }

                            let page = 1;

                            function filtered() {
                                const term = (search.value || '').trim().toLowerCase();
                                return items.filter(i => !term || i.dataset.currencyCodeMobile.includes(term));
                            }

                            function render() {
                                const vis = filtered();
                                const total = Math.max(1, Math.ceil(vis.length / perPage));
                                page = Math.min(page, total);
                                const start = (page - 1) * perPage;
                                items.forEach(i => { i.hidden = true; });
                                vis.slice(start, start + perPage).forEach(i => { i.hidden = false; });
                                empty.hidden = vis.length > 0;
                                pageEl.textContent = vis.length ? page + ' / ' + total : '0 / 0';
                                prevEl.disabled = page <= 1;
                                nextEl.disabled = page >= total;
                            }

                            search.addEventListener('input', () => { page = 1; render(); });
                            prevEl.addEventListener('click', () => { page--; render(); });
                            nextEl.addEventListener('click', () => { page++; render(); });
                            render();
                        }

                        document.addEventListener('click', function (e) {
                            if (e.target.closest('.mobile-currency-search-wrap')) e.stopPropagation();
                        });

                        document.addEventListener('DOMContentLoaded', function () {
                            document.querySelectorAll('.sub-menu').forEach(function (sub) {
                                if (sub.querySelector('.mobile-currency-search')) {
                                    initMobileCurrency(sub);
                                }
                            });
                        });
                    })();
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
