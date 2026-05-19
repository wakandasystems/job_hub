<x-core::layouts.base :body-attributes="['data-bs-theme' => 'dark']">
    <main class="row g-0 flex-fill vh-100">
        <div class="col-12 col-lg-6 col-xl-4 border-top-wide border-primary d-flex flex-column justify-content-center">
            <div class="container container-tight my-5 px-lg-5">
                <div class="text-center mb-4">
                    @include('core/base::partials.logo', ['defaultLogoHeight' => 50])
                </div>

                @yield('content')

                <div class="text-center mt-4">
                    <a href="{{ url('/') }}" class="text-muted small d-inline-flex align-items-center gap-1" style="text-decoration:none;opacity:.7;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.7">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                        Back to website
                    </a>
                </div>
            </div>
        </div>
        <div class="position-relative col-12 col-lg-6 col-xl-8 d-none d-lg-block">
            <div
                class="bg-cover bg-white h-100 min-vh-100"
                style="background-image: url({{ $backgroundUrl }})"
            ></div>
            <div class="end-0 bottom-0 position-absolute">
                <div class="text-white me-5 mb-4">
                    <h1 class="mb-1">{{ setting('admin_title', config('core.base.general.base_name')) }}</h1>
                    <p>@include('core/base::partials.copyright')</p>
                </div>
            </div>
        </div>
    </main>
</x-core::layouts.base>
