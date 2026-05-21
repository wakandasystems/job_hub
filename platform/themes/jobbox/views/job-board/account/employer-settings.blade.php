@extends(Theme::getThemeNamespace('views.job-board.account.partials.layout-settings'))

@section('content')
<div class="col-lg-12">
    <div class="card profile-content-page mt-4 mt-lg-0">

        <ul class="profile-content-nav nav nav-pills border-bottom mb-4" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ request()->get('tab') !== 'security' ? 'active' : '' }}"
                    id="tab-profile" data-bs-toggle="pill" data-bs-target="#pane-profile"
                    type="button" role="tab">
                    {{ __('Profile') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ request()->get('tab') === 'security' ? 'active' : '' }}"
                    id="tab-security" data-bs-toggle="pill" data-bs-target="#pane-security"
                    type="button" role="tab">
                    {{ __('Security') }}
                </button>
            </li>
        </ul>

        <div class="tab-content card-body p-4" id="pills-tabContent">

            {{-- Profile tab --}}
            <div class="tab-pane fade {{ request()->get('tab') !== 'security' ? 'show active' : '' }}"
                id="pane-profile" role="tabpanel">
                {!! Form::open(['route' => 'public.account.employer.settings.update', 'method' => 'POST', 'files' => true]) !!}
                    {!! $form->contentOnly()->renderForm(showStart: false, showEnd: false) !!}
                    <div class="box-button mt-15">
                        <button type="submit" class="btn btn-apply-big font-md font-bold">{{ __('Save All Changes') }}</button>
                    </div>
                {!! Form::close() !!}
            </div>

            {{-- Security tab --}}
            <div class="tab-pane fade {{ request()->get('tab') === 'security' ? 'show active' : '' }}"
                id="pane-security" role="tabpanel">
                <h5 class="fs-17 fw-semibold mb-3">{{ __('Change Password') }}</h5>

                {!! Form::open(['route' => 'public.account.post.security', 'method' => 'PUT']) !!}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label for="emp-current-password" class="form-label">{{ __('Current password') }}</label>
                            <input type="password"
                                @class(['form-control', 'is-invalid' => $errors->has('old_password')])
                                name="old_password" id="emp-current-password"
                                placeholder="{{ __('Enter current password') }}"
                                autocomplete="current-password">
                            @error('old_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="emp-new-password" class="form-label">{{ __('New password') }}</label>
                            <input type="password"
                                @class(['form-control', 'is-invalid' => $errors->has('password')])
                                name="password" id="emp-new-password"
                                placeholder="{{ __('Enter new password') }}"
                                autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="emp-confirm-password" class="form-label">{{ __('Password confirmation') }}</label>
                            <input type="password"
                                @class(['form-control', 'is-invalid' => $errors->has('password_confirmation')])
                                name="password_confirmation" id="emp-confirm-password"
                                placeholder="{{ __('Enter password confirmation') }}">
                            @error('password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-end">
                    <button type="submit" class="btn btn-apply-big font-md font-bold">{{ __('Update Password') }}</button>
                </div>
                {!! Form::close() !!}
            </div>

        </div>
    </div>
</div>

@if($errors->has('old_password') || $errors->has('password') || $errors->has('password_confirmation'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Tab(document.getElementById('tab-security')).show();
        });
    </script>
@endif
@endsection
