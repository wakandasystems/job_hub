@php
    $currencies = get_all_currencies();
@endphp

@if($currencies->count() > 1)
    <div class="dropdown d-inline-block currency-switch" data-currency-switcher>
        <a
            class="btn-currency-footer dropdown-toggle"
            data-bs-toggle="dropdown"
            type="button"
            href="#"
            aria-haspopup="true"
            aria-expanded="false"
        >
            {{ get_application_currency()->title }}
        </a>
        <div class="dropdown-menu dropdown-menu-end">
            <div class="currency-switch-search">
                <input
                    class="currency-switch-search-input"
                    type="search"
                    placeholder="{{ __('Search currency') }}"
                    aria-label="{{ __('Search currency') }}"
                    data-currency-search
                >
            </div>
            <div class="currency-switch-list" data-currency-list>
                @foreach ($currencies as $currency)
                    @php($currencyMeta = wakanda_currency_meta($currency->title))
                    <a
                        class="dropdown-item notify-item language"
                        href="{{ route('public.change-currency', $currency->title) }}"
                        title="{{ $currencyMeta['label'] }}"
                        aria-label="{{ $currencyMeta['label'] }}"
                        data-currency-item
                        data-currency-code="{{ $currency->title }} {{ $currencyMeta['country'] }} {{ $currencyMeta['name'] }}"
                    ><span class="me-1" aria-hidden="true">{!! $currencyMeta['flag'] !!}</span><span>{{ $currency->title }}</span></a>
                @endforeach
            </div>
            <div class="currency-switch-empty" data-currency-empty hidden>{{ __('No currencies found') }}</div>
            <div class="currency-switch-pagination">
                <button type="button" data-currency-prev aria-label="{{ __('Previous') }}">&lsaquo;</button>
                <span data-currency-page></span>
                <button type="button" data-currency-next aria-label="{{ __('Next') }}">&rsaquo;</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-currency-switcher]').forEach(function (switcher) {
                if (switcher.dataset.currencyReady) {
                    return;
                }

                switcher.dataset.currencyReady = '1';

                const perPage = 3;
                const search = switcher.querySelector('[data-currency-search]');
                const items = Array.from(switcher.querySelectorAll('[data-currency-item]'));
                const empty = switcher.querySelector('[data-currency-empty]');
                const pageLabel = switcher.querySelector('[data-currency-page]');
                const prev = switcher.querySelector('[data-currency-prev]');
                const next = switcher.querySelector('[data-currency-next]');
                let page = 1;

                function filteredItems() {
                    const term = (search.value || '').trim().toLowerCase();

                    return items.filter(function (item) {
                        return !term || item.dataset.currencyCode.toLowerCase().includes(term);
                    });
                }

                function render() {
                    const visibleItems = filteredItems();
                    const totalPages = Math.max(1, Math.ceil(visibleItems.length / perPage));
                    page = Math.min(page, totalPages);

                    const start = (page - 1) * perPage;
                    const end = start + perPage;

                    items.forEach(function (item) {
                        item.hidden = true;
                    });

                    visibleItems.slice(start, end).forEach(function (item) {
                        item.hidden = false;
                    });

                    empty.hidden = visibleItems.length > 0;
                    pageLabel.textContent = visibleItems.length ? page + ' / ' + totalPages : '0 / 0';
                    prev.disabled = page <= 1;
                    next.disabled = page >= totalPages;
                }

                switcher.querySelector('.dropdown-menu').addEventListener('click', function (event) {
                    if (!event.target.closest('a')) {
                        event.stopPropagation();
                    }
                });

                search.addEventListener('input', function () {
                    page = 1;
                    render();
                });

                prev.addEventListener('click', function () {
                    page--;
                    render();
                });

                next.addEventListener('click', function () {
                    page++;
                    render();
                });

                render();
            });
        });
    </script>
@endif
