<section class="section-box mt-80 mb-80">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-6 col-md-8 col-12">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:80px;height:80px;">
                        <i class="fi-rr-check fs-1 text-success"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-2">{{ __('All set!') }}</h3>
                <p class="color-text-paragraph-2 mb-4">
                    Your <strong>{{ $package->name }}</strong> is now active.
                    You have
                    <strong>{{ $package->isUnlimited() ? 'unlimited' : $package->alerts_per_month }}</strong>
                    job alert{{ $package->isUnlimited() || $package->alerts_per_month > 1 ? 's' : '' }}
                    available this month.
                </p>
                <div class="card border-0 bg-light text-start mb-4">
                    <div class="card-body px-4 py-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Package</span>
                            <strong>{{ $package->name }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Alerts this month</span>
                            <strong>{{ $package->displayAlerts() }}</strong>
                        </div>
                        @if($quota)
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">Used</span>
                                <strong>{{ $quota->alerts_sent }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
                <a href="{{ route('public.account.job-alerts.index') }}" class="btn btn-apply btn-apply-big me-2">
                    Manage My Alerts
                </a>
                <a href="{{ route('public.index') }}" class="btn btn-outline-primary btn-apply-big">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</section>
