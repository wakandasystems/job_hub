<section class="pt-100 login-register">
    <div class="container">
        <div class="row login-register-cover">
            <div class="col-lg-4 col-md-6 col-sm-12 mx-auto">
                <div class="text-center">
                    <p class="font-sm text-brand-2">{{ __('Welcome Back!') }}</p>
                    <h2 class="mt-10 mb-5 text-brand-1">{{ __('Member Login') }}</h2>
                    <p class="font-sm text-muted mb-30">{{ __('Sign in to continue.') }}</p>
                </div>

                <br>
                @if (session()->has('status'))
                    <div role="alert" class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @elseif (session()->has('auth_error_message'))
                    <div role="alert" class="alert alert-danger">
                        {{ session('auth_error_message') }}
                    </div>
                @elseif (session()->has('auth_success_message'))
                    <div role="alert" class="alert alert-success">
                        {{ session('auth_success_message') }}
                    </div>
                @elseif (session()->has('auth_warning_message'))
                    <div role="alert" class="alert alert-warning">
                        {{ session('auth_warning_message') }}
                    </div>
                @endif

                {!!
                    $form
                        ->formClass('login-register text-start mt-20 auth-form')
                        ->modify('remember', 'html', ['html' => sprintf('<div class="col-6"><label class="cb-container">
                            <input type="checkbox" name="remember" value="1" checked>
                            <span class="text-small">%s</span>
                            <span class="checkmark"></span>
                        </label></div>', __('Remember me'))], true)
                        ->renderForm()
                !!}
            </div>
            <div class="img-1 d-none d-lg-block">
                <img class="shape-1" src="{{ RvMedia::getImageUrl(theme_option('auth_background_image_1')) }}" alt="{{ theme_option('site_name') }}">
            </div>
            <div class="img-2">
                <img src="{{ RvMedia::getImageUrl(theme_option('auth_background_image_2')) }}" alt="{{ theme_option('site_name') }}">
            </div>
        </div>
    </div>
</section>
