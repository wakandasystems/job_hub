@php
    $isLoggedIn = auth('account')->check();

    $account = null;
    if ($isLoggedIn) {
       $account = auth('account')->user();
    }
@endphp

@if (!$isLoggedIn || ($account && !$account->isEmployer()))
    <div class="modal fade" id="ModalApplyJobForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content apply-job-form">
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body pl-30 pr-30 pt-50">
                    {!! \Botble\JobBoard\Forms\Fronts\InternalJobApplicationForm::create()->renderForm() !!}
                    <div class="text-center py-3 border-top mt-3">
                        <a href="https://whatsapp.com/channel/0029Vb7umsx2ZjClLN546U3f" target="_blank" rel="noopener" class="d-inline-flex align-items-center gap-2 text-decoration-none fw-semibold" style="color:#25D366;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" width="20" height="20" alt="WhatsApp"> Follow our WhatsApp Channel for job updates
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- END APPLY MODAL -->

    <!-- START APPLY MODAL -->
    <div class="modal fade" id="ModalApplyExternalJobForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content apply-job-form">
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body pl-30 pr-30 pt-50">
                    {!! \Botble\JobBoard\Forms\Fronts\ExternalJobApplicationForm::create()->renderForm() !!}
                    <div class="text-center py-3 border-top mt-3">
                        <a href="https://whatsapp.com/channel/0029Vb7umsx2ZjClLN546U3f" target="_blank" rel="noopener" class="d-inline-flex align-items-center gap-2 text-decoration-none fw-semibold" style="color:#25D366;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" width="20" height="20" alt="WhatsApp"> Follow our WhatsApp Channel for job updates
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
