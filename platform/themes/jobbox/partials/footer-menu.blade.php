<ul {!! BaseHelper::clean($options) !!}>
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
</ul>
