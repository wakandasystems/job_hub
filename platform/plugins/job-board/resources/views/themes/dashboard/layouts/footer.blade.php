<footer>
    @php
        $currencies = get_all_currencies();
    @endphp

    @if ($currencies->count() > 1)
        <p class="d-inline-block mb-0">{{ trans('plugins/job-board::dashboard.currencies_label') }}:
            @foreach ($currencies as $currency)
                @php($currencyMeta = wakanda_currency_meta($currency->title))
                <a
                    href="{{ route('public.change-currency', $currency->title) }}"
                    title="{{ $currencyMeta['label'] }}"
                    aria-label="{{ $currencyMeta['label'] }}"
                    @if (get_application_currency_id() == $currency->id) class="active" @endif
                ><span class="me-1" aria-hidden="true">{!! $currencyMeta['flag'] !!}</span><span>{{ $currency->title }}</span></a>
                @if (!$loop->last)
                    -
                @endif
            @endforeach
        </p>
    @endif
</footer>

<script src="{{ asset('vendor/core/plugins/job-board/js/app.js') }}"></script>
