@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

        <x-core::card>
            <x-core::card.body class="text-center py-5 px-4">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                      style="width:64px;height:64px;background:rgba(111,66,193,.12);">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="#6f42c1" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01L12 2z"/>
                    </svg>
                </span>

                <h4 class="fw-bold mb-1">{{ __('Get Wakanda Verified') }}</h4>
                <p class="text-muted mb-4">{{ __('Stand out with a purple badge. Our team will review your skills and experience, then interview you before granting verification.') }}</p>

                <div class="card border mb-4 text-start">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">{{ __('Verification fee') }}</span>
                            <span class="fw-semibold">{{ number_format($cost) }} {{ __('credits') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">{{ __('Your current balance') }}</span>
                            @if ($account->credits >= $cost)
                                <span class="fw-semibold text-success">{{ number_format($account->credits) }} {{ __('credits') }}</span>
                            @else
                                <span class="fw-semibold text-danger">{{ number_format($account->credits) }} {{ __('credits') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($account->credits >= $cost)
                    <form method="POST" action="{{ route('public.account.wakanda-verification.store') }}">
                        @csrf
                        <button type="submit" class="btn w-100 text-white mb-2" style="background:#6f42c1;">
                            {{ __('Confirm — Pay :cost credits', ['cost' => number_format($cost)]) }}
                        </button>
                    </form>
                    <p class="text-muted small mt-2">{{ __('Credits are non-refundable. Rejection does not return credits.') }}</p>
                @else
                    <div class="alert alert-warning text-start mb-3">
                        {{ __('You need :cost credits but only have :balance. Please buy more credits first.', ['cost' => $cost, 'balance' => $account->credits]) }}
                    </div>
                    <a href="{{ route('public.account.credits') }}" class="btn btn-warning w-100 mb-2">
                        {{ __('Buy Credits') }}
                    </a>
                @endif

                <a href="{{ route('public.account.settings') }}" class="btn btn-outline-secondary w-100">
                    {{ __('Cancel') }}
                </a>
            </x-core::card.body>
        </x-core::card>

    </div>
</div>
@endsection
