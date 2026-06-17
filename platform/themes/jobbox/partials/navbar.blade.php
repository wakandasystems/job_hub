@if (theme_option('preloader_enabled', 'yes') == 'yes')
    <div id="preloader-active">
        <div class="preloader d-flex align-items-center justify-content-center">
            <div class="preloader-inner position-relative">
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    </div>
@endif

@if (is_plugin_active('job-board') && empty($hideApplyModal))
    @include(Theme::getThemeNamespace('partials.apply-modal'))
@endif
<header class="header @if (theme_option('enabled_sticky_header', 'yes') == 'yes') sticky-bar @endif">
    <div class="container">
        <div class="main-header">
            <div class="header-left">
                <div class="header-logo">
                    <a class="d-flex" href="{{ route('public.index') }}">
                        <img alt="{{ theme_option('site_title') }}" src="{{ setting('theme-jobbox-logo') ? RvMedia::getImageUrl(setting('theme-jobbox-logo')) : url(config('core.base.general.logo')) }}">
                    </a>
                </div>
            </div>
            <div class="header-nav">
                <nav class="nav-main-menu">
                    {!!
                        Menu::renderMenuLocation('main-menu', [
                            'options' => ['class' => 'main-menu'],
                            'view'    => 'main-menu',
                        ])
                    !!}
                </nav>
                <div class="burger-icon burger-icon-white">
                    <span class="burger-icon-top"></span>
                    <span class="burger-icon-mid"></span>
                    <span class="burger-icon-bottom"></span>
                </div>
            </div>
            <div class="header-right">
                @if (is_plugin_active('job-board'))
                    @auth('account')
                        <ul class="header-menu list-inline d-flex align-items-center mb-0 user-header-dropdown">
                            {!! apply_filters('theme-header-right-nav', null) !!}
                            <li class="list-inline-item">
                                <button type="button" id="header-search-toggle" class="header-search-toggle" aria-label="{{ __('Search') }}">
                                    <i class="fi-rr-search"></i>
                                </button>
                            </li>
                            <li class="list-inline-item">
                                {!! Theme::partial('country-switcher') !!}
                            </li>
                            @if (auth('account')->check() && $account = auth('account')->user())
                                <li class="list-inline-item dropdown">
                                    <a href="#" class="d-inline-flex header-item" id="userdropdown" data-bs-toggle="dropdown"
                                       aria-expanded="false">
                                        <img src="{{ $account->avatar_thumb_url }}" alt="{{ $account->name }}" width="35" height="35" class="rounded-circle me-1 mt-1 mr-2">
                                        <span class="text-left fw-medium icon-down" title="{{ __('Hi, :name', ['name' => $name = Str::limit($account->name, 15)]) }}">{{ __('Hi, :name', ['name' => $name]) }} </span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu" aria-labelledby="userdropdown">
                                        @include(Theme::getThemeNamespace('partials.account-menu-items'), [
                                            'account' => $account,
                                            'linkClass' => 'dropdown-item',
                                            'logoutFormId' => 'desktop-logout-form',
                                        ])
                                    </ul>
                                    <form id="desktop-logout-form" action="{{ route('public.account.logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </li>
                            @endif
                        </ul>
                    @else
                        <div class="block-signin">
                            <button type="button" id="header-search-toggle" class="header-search-toggle" aria-label="{{ __('Search') }}">
                                <i class="fi-rr-search"></i>
                            </button>
                            {!! Theme::partial('country-switcher') !!}
                            <a class="btn btn-default btn-shadow ml-30 hover-up" href="{{ route('public.account.login') }}"><x-core::icon name="ti ti-user-shield" class="me-1" />{{ __('Sign In') }}</a>
                        </div>
                    @endauth
                @endif
            </div>
        </div>
    </div>
</header>
<div class="mobile-header-active mobile-header-wrapper-style perfect-scrollbar">
    <div class="offcanvas-header justify-content-end">
        <button type="button" class="btn-close burger-close burger-icon" aria-label="Close"></button>
    </div>
    <div class="mobile-header-wrapper-inner">
        <div class="mobile-header-content-area">
            <div>
                <div class="mobile-search mobile-header-border mb-30 form-find position-relative">
                    <form method="GET" action="{{ JobBoardHelper::getJobsPageURL() }}" data-quick-search-url="{{ route('public.ajax.quick-search-jobs') }}">
                        <input class="input-keysearch" name="keyword" type="text" placeholder="{{ __('Search jobs...') }}" autocomplete="off">
                        <i class="fi-rr-search"></i>
                    </form>
                </div>
                <div class="mobile-menu-wrap mobile-header-border">
                    <nav>
                        <div class="mobile-country-switcher">
                            {!! Theme::partial('country-switcher') !!}
                        </div>
                        {!!
                            Menu::renderMenuLocation('main-menu', [
                                'options' => ['class' => 'mobile-menu font-heading'],
                                'view'    => 'main-menu',
                            ])
                        !!}
                        @if (is_plugin_active('language'))
                            {!! Theme::partial('language-and-currency-switcher-mobile') !!}
                        @endif
                    </nav>
                </div>
                @if (is_plugin_active('job-board'))
                    @auth('account')
                        @php($mobileAccount = auth('account')->user())
                        <div class="mobile-account">
                            <h6 class="mb-10">{{ __('Your Account') }}</h6>
                            <ul class="mobile-menu font-heading">
                                @include(Theme::getThemeNamespace('partials.account-menu-items'), [
                                    'account' => $mobileAccount,
                                    'logoutFormId' => 'mobile-logout-form',
                                ])
                            </ul>
                        </div>
                        <form id="mobile-logout-form" action="{{ route('public.account.logout') }}" method="post" style="display: none;">
                            @csrf
                        </form>
                    @else
                        <div class="mobile-account">
                            <ul class="mobile-menu font-heading">
                                <li><a href="{{ route('public.account.login') }}"><x-core::icon name="ti ti-user-plus" class="me-1" />{{ __('Sign In') }}</a></li>
                                <li><a href="{{ route('public.account.register') }}"><x-core::icon name="ti ti-user-shield" class="me-1" />{{ __('Sign Up') }}</a></li>
                            </ul>
                        </div>
                    @endauth
                @endif
                <div class="site-copyright">{!! BaseHelper::clean(theme_option('copyright')) !!}</div>
            </div>
        </div>
    </div>
</div>

{{-- Search overlay --}}
<div id="header-search-overlay" class="header-search-overlay" role="dialog" aria-modal="true" aria-label="{{ __('Search') }}">
    <div class="header-search-overlay__backdrop"></div>
    <div class="header-search-overlay__box">
        <div class="form-find position-relative">
            <form method="GET" action="{{ JobBoardHelper::getJobsPageURL() }}" data-quick-search-url="{{ route('public.ajax.quick-search-jobs') }}">
                <div class="header-search-overlay__inner">
                    <i class="fi-rr-search header-search-overlay__icon"></i>
                    <input
                        class="input-keysearch header-search-overlay__input"
                        name="keyword"
                        type="text"
                        placeholder="{{ __('Search jobs, companies, locations...') }}"
                        autocomplete="off"
                    >
                    <button type="button" class="header-search-overlay__close" aria-label="{{ __('Close') }}">
                        <i class="fi-rr-cross-small"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
