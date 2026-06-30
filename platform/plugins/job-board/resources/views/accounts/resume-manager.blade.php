@php
    $resumeUrl = $account->resume ? \Botble\Media\Facades\RvMedia::url($account->resume) : '';
    $syncUrl = $account->getKey() ? route('accounts.sync-cv-profile', $account) : '';
@endphp

<div class="small text-muted mb-2">
    Preview the linked CV in a modal, unlink it from this profile, or re-run CV analysis to backfill the candidate fields.
</div>

<div class="d-flex flex-wrap gap-2 resume-admin-tools" data-input-name="resume">
    <button type="button" class="btn btn-outline-secondary btn-sm js-account-resume-preview" @disabled(! $resumeUrl)>
        <i class="ti ti-file-text me-1"></i> Preview CV
    </button>
    <button type="button" class="btn btn-outline-danger btn-sm js-account-resume-remove" @disabled(! $resumeUrl)>
        <i class="ti ti-trash me-1"></i> Unlink CV
    </button>
    @if ($syncUrl)
        <button type="button" class="btn btn-outline-primary btn-sm js-account-sync-cv" data-url="{{ $syncUrl }}" @disabled(! $resumeUrl)>
            <i class="ti ti-sparkles me-1"></i> Fill Profile From CV
        </button>
    @endif
</div>
