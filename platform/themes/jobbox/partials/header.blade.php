<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=1" name="viewport"/>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="google-adsense-account" content="ca-pub-1694446344606687">
        {!! Theme::partial('theme-meta') !!}
        {!! Theme::header() !!}
        @php
            $ogTitle       = SeoHelper::openGraph()->getProperty('title') ?: SeoHelper::getTitle();
            $ogDescription = SeoHelper::openGraph()->getProperty('description') ?: SeoHelper::getDescription();
            $ogImage       = SeoHelper::openGraph()->getProperty('image') ?: RvMedia::getImageUrl('chatgpt-image-may-14-2026-03-00-04-pm.png');
            $ogUrl         = SeoHelper::openGraph()->getProperty('url') ?: url()->current();
        @endphp
        {{-- Twitter Card tags (improves click-through from Twitter/X & iMessage previews) --}}
        <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $ogTitle }}">
        @if($ogDescription)
        <meta name="twitter:description" content="{{ Str::limit(strip_tags($ogDescription), 200) }}">
        @endif
        @if($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
        @endif
        @php
            $orgSchema = json_encode([
                '@context' => 'https://schema.org',
                '@type'    => 'Organization',
                'name'     => 'Wakanda Jobs',
                'url'      => url('/'),
                'logo'     => RvMedia::getImageUrl(theme_option('logo', '')),
                'description' => 'Wakanda Jobs is Africa\'s leading job board — connecting employers and job seekers across 50+ African countries including Nigeria, South Africa, Kenya, Ghana, Tanzania, Uganda, Zambia, Zimbabwe and more.',
                'sameAs'   => array_filter([
                    theme_option('facebook'),
                    theme_option('twitter'),
                    theme_option('linkedin'),
                    theme_option('instagram'),
                ]),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        @endphp
        <script type="application/ld+json">{!! $orgSchema !!}</script>
        @php
            $searchActionSchema = json_encode([
                '@context' => 'https://schema.org',
                '@type'    => 'WebSite',
                'name'     => 'Wakanda Jobs',
                'url'      => url('/'),
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => [
                        '@type'       => 'EntryPoint',
                        'urlTemplate' => url('/jobs') . '?keyword={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        @endphp
        <script type="application/ld+json">{!! $searchActionSchema !!}</script>
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-QPTHYTGC91"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-QPTHYTGC91');
        </script>
    </head>
    <body {!! Theme::bodyAttributes() !!}>
        {!! apply_filters(THEME_FRONT_BODY, null) !!}

        <div id="alert-container" class="toast-notification"></div>

        @if (empty($withoutNavbar))
            {!! Theme::partial('navbar') !!}
        @endempty

        <main class="main">
