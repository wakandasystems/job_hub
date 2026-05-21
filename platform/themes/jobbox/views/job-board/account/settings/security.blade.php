@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>{{ __('Change Password') }}</x-core::card.title>
        </x-core::card.header>

        <x-core::card.body>
            {!! Form::open(['route' => 'public.account.post.security', 'method' => 'PUT']) !!}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label for="current-password-input" class="form-label">{{ __('Current password') }}</label>
                            <input type="password" @class(['form-control', 'is-invalid' => $errors->has('old_password')])
                                placeholder="{{ __('Enter current password') }}" name="old_password" id="current-password-input" autocomplete="current-password" />
                            @error('old_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="new-password-input" class="form-label">{{ __('New password') }}</label>
                            <input type="password" @class(['form-control', 'is-invalid' => $errors->has('password')])
                                placeholder="{{ __('Enter new password') }}" name="password" id="new-password-input" autocomplete="new-password" />
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="confirm-password-input" class="form-label">{{ __('Password confirmation') }}</label>
                            <input type="password" @class(['form-control', 'is-invalid' => $errors->has('password_confirmation')])
                                placeholder="{{ __('Enter password confirmation') }}" name="password_confirmation" id="confirm-password-input" autocomplete="new-password" />
                            @error('password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <x-core::button type="submit" color="primary">
                        {{ __('Update Password') }}
                    </x-core::button>
                </div>
            {!! Form::close() !!}
        </x-core::card.body>
    </x-core::card>
@endsection
