<div class="mb-3">
    <label class="form-label required">{{ trans('plugins/job-board::account.registration.account_type') }}</label>
    <div class="account-type-selection">
        <div class="row">
            <div class="col-md-6">
                <div class="account-type-option">
                    <input type="radio" id="jobseeker" name="account_type" value="job-seeker" class="form-check-input" required>
                    <label for="jobseeker" class="account-type-label">
                        <div class="account-type-card">
                            <div class="account-type-check">
                                <x-core::icon name="ti ti-check" />
                            </div>
                            <div class="account-type-icon">
                                <x-core::icon name="ti ti-user" />
                            </div>
                            <div class="account-type-content">
                                <h6>{{ trans('plugins/job-board::account.registration.job_seeker') }}</h6>
                                <p>{{ trans('plugins/job-board::account.registration.job_seeker_description') }}</p>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="account-type-option">
                    <input type="radio" id="employer" name="account_type" value="employer" class="form-check-input" required>
                    <label for="employer" class="account-type-label">
                        <div class="account-type-card">
                            <div class="account-type-check">
                                <x-core::icon name="ti ti-check" />
                            </div>
                            <div class="account-type-icon">
                                <x-core::icon name="ti ti-building" />
                            </div>
                            <div class="account-type-content">
                                <h6>{{ trans('plugins/job-board::account.registration.employer') }}</h6>
                                <p>{{ trans('plugins/job-board::account.registration.employer_description') }}</p>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
