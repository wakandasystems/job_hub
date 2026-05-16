@php
    $footerMenuPaths = $menu_nodes
        ->map(fn ($row) => trim(parse_url($row->url, PHP_URL_PATH) ?: '', '/'))
        ->all();
@endphp

@foreach ($menu_nodes->loadMissing('metadata') as $key => $row)
    <a @class(['font-xs color-text-paragraph', 'xmr-30 ml-30' => (! $loop->first || ! $loop->last)]) href="{{ $row->url }}">{{ $row->title }}</a>
@endforeach

@if (! in_array('about-us', $footerMenuPaths, true))
    <a class="font-xs color-text-paragraph xmr-30 ml-30" href="{{ url('about-us') }}">{{ __('About Us') }}</a>
@endif

@if (! in_array('pricing-plan', $footerMenuPaths, true))
    <a class="font-xs color-text-paragraph xmr-30 ml-30" href="{{ url('pricing-plan') }}">{{ __('Pricing Plan') }}</a>
@endif
