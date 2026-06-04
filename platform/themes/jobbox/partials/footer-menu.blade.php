<ul {!! BaseHelper::clean($options) !!}>
    @php
        $menuPaths = collect($menu_nodes)
            ->map(fn ($row) => trim(parse_url($row->url, PHP_URL_PATH) ?: '', '/'))
            ->all();
    @endphp

    @foreach ($menu_nodes as $key => $row)
        @php
            $menuPath = trim(parse_url($row->url, PHP_URL_PATH) ?: '', '/');
            $title = match ($menuPath) {
                'about-us' => __('About Us'),
                'pricing-plan' => __('Pricing Plan'),
                default => $row->title,
            };
        @endphp

        <li><a href="{{ $row->url }}">{{ $title }}</a></li>
    @endforeach

    @if (in_array('salary-checker', $menuPaths, true) && ! in_array('career-services/cv-score', $menuPaths, true))
        <li><a href="{{ route('public.career-service.cv-score') }}">{{ __('AI CV Score') }}</a></li>
    @endif
    @if (in_array('salary-checker', $menuPaths, true) && ! in_array('career-services', $menuPaths, true))
        <li><a href="{{ route('public.career-service.listing') }}">{{ __('Career Services') }}</a></li>
    @endif
</ul>
