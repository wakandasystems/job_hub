<div class="modal fade account-type-modal" id="accountTypeModal" tabindex="-1" aria-labelledby="accountTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountTypeModalLabel">{{ trans('plugins/job-board::account.registration.confirm_account_type') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="modal-icon">
                    <x-core::icon name="ti ti-user-check" />
                </div>
                <h6 class="mb-3">{{ trans('plugins/job-board::account.registration.please_confirm_account_type') }}</h6>
                <p class="text-muted mb-0">{{ trans('plugins/job-board::account.registration.you_are_creating_account_as') }}</p>

                <div class="account-type-confirmation">
                    <div class="confirmation-card">
                        <span class="account-type-icon-confirm">
                            <x-core::icon name="ti ti-building" class="employer-icon d-none" />
                            <x-core::icon name="ti ti-user" class="jobseeker-icon d-none" />
                        </span>
                        <span class="account-type-text-confirm"></span>
                    </div>
                </div>

                <p class="text-muted small mb-0">{{ trans('plugins/job-board::account.registration.make_sure_correct') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('plugins/job-board::account.registration.go_back') }}</button>
                <button type="button" class="btn btn-primary" id="confirmRegistration">{{ trans('plugins/job-board::account.registration.confirm_and_register') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const form = document.querySelector("form[action*=\"register\"]");
    const submitButton = form?.querySelector("button[type=\"submit\"]");
    const modal = new bootstrap.Modal(document.getElementById("accountTypeModal"));

    if (form && submitButton) {
        form.addEventListener("submit", function(e) {
            e.preventDefault();

            const selectedAccountType = document.querySelector("input[name=\"account_type\"]:checked");

            if (!selectedAccountType) {
                alert("{{ trans('plugins/job-board::account.registration.please_select_account_type') }}");
                return;
            }

            document.querySelector(".account-type-text-confirm").textContent = selectedAccountType.value === "employer"
                ? "{{ trans('plugins/job-board::account.registration.employer') }}"
                : "{{ trans('plugins/job-board::account.registration.job_seeker') }}";

            const employerIcon = document.querySelector(".account-type-icon-confirm .employer-icon");
            const jobseekerIcon = document.querySelector(".account-type-icon-confirm .jobseeker-icon");

            if (selectedAccountType.value === "employer") {
                employerIcon.classList.remove("d-none");
                jobseekerIcon.classList.add("d-none");
            } else {
                employerIcon.classList.add("d-none");
                jobseekerIcon.classList.remove("d-none");
            }

            modal.show();
        });

        document.getElementById("confirmRegistration").addEventListener("click", function() {
            modal.hide();

            const selectedAccountType = document.querySelector("input[name=\"account_type\"]:checked");
            const isEmployerInput = document.createElement("input");
            isEmployerInput.type = "hidden";
            isEmployerInput.name = "is_employer";
            isEmployerInput.value = selectedAccountType.value === "employer" ? "1" : "0";
            form.appendChild(isEmployerInput);

            form.submit();
        });
    }
});
</script>
