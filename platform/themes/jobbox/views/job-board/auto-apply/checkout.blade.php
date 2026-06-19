<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 text-center mb-4">
                <h2 class="mb-2">Auto Apply — {{ $planData['label'] }}</h2>
                <p class="font-md color-text-paragraph-2">
                    {{ $planData['applications_per_month'] === 0 ? 'Unlimited' : $planData['applications_per_month'] }} applications/month
                    &middot; {{ $planData['displayCurrency'] }} {{ number_format($planData['displayPrice'], 2) }}
                </p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                @if(!$account)
                    <div class="alert alert-warning">
                        <i class="fi-rr-info me-1"></i>
                        You need to <a href="{{ route('public.account.login') }}" class="fw-bold">sign in</a> to purchase Auto Apply.
                    </div>
                @else
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Confirm your details</h5>
                            <form method="POST" action="{{ route('public.auto-apply.prepare-checkout', $plan) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" disabled
                                           value="{{ $account->first_name }} {{ $account->last_name }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="text" class="form-control" disabled value="{{ $account->email }}">
                                </div>

                                @if(trim((string) $account->resume) === '')
                                    <div class="alert alert-danger">
                                        <i class="fi-rr-exclamation me-1"></i>
                                        <strong>CV Required:</strong> Please upload your CV in your
                                        <a href="{{ route('public.account.settings') }}">profile settings</a> first.
                                    </div>
                                @else
                                    <div class="alert alert-success">
                                        <i class="fi-rr-check me-1"></i> CV uploaded and ready to be sent with applications.
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 btn-apply-big">
                                        Proceed to Payment — {{ $planData['displayCurrency'] }} {{ number_format($planData['displayPrice'], 2) }}
                                    </button>
                                @endif
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
