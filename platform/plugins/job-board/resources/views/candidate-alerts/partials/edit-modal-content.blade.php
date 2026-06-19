<form method="POST" action="{{ route('job-board.candidate-alerts.update', $alert->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="modal-header">
        <div class="d-flex align-items-center justify-content-between gap-3 w-100">
            <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
                <i class="ti ti-bell-check text-primary"></i> Edit: {{ $alert->label }}
            </h5>
            <div class="d-flex align-items-center gap-2">
                @if($alert->hasStoredCv())
                    <button type="button"
                        class="btn btn-outline-primary btn-sm btn-reanalyze-alert-cv"
                        data-prefix="{{ $prefix }}"
                        data-url="{{ route('job-board.candidate-alerts.analyze-existing-cv', $alert->id) }}">
                        <i class="ti ti-refresh me-1"></i> Re-analyse CV
                    </button>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
        </div>
    </div>
    <div class="modal-body">
        @include('plugins/job-board::candidate-alerts._form', ['alert' => $alert, 'prefix' => $prefix])
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i> Save Changes
        </button>
    </div>
</form>
