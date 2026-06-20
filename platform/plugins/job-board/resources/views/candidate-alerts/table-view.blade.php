@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
@php
    $activeCandidateAlertTab = request('tab') === 'quick-add' ? 'quick-add' : 'alerts';
@endphp
<div class="row g-4 mb-3">

    {{-- Stats --}}
    <div class="col-12">
        <x-core::stat-widget class="mb-0">
            <x-core::stat-widget.item label="Total Alerts" :value="$stats['total']" icon="ti ti-bell" color="primary" />
            <x-core::stat-widget.item label="Active" :value="$stats['active']" icon="ti ti-bell-check" color="{{ $stats['active'] > 0 ? 'success' : 'secondary' }}" />
            <x-core::stat-widget.item label="Expired" :value="$stats['expired']" icon="ti ti-bell-x" color="{{ $stats['expired'] > 0 ? 'danger' : 'secondary' }}" />
            <x-core::stat-widget.item label="Jobs Sent Today" :value="$stats['sent_today']" icon="ti ti-send" color="info" />
        </x-core::stat-widget>
    </div>

    {{-- Info banner --}}
    <div class="col-12">
        <x-core::card>
            <x-core::card.body class="py-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <i class="ti ti-info-circle text-primary fs-4 flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <strong>How VIP Job Alerts work:</strong>
                        Create a personalised alert for a candidate, specify their phone number and job criteria.
                        The system runs <strong>daily</strong> and sends matching new jobs to their WhatsApp automatically.
                        Uses the active <em>Whapi automation</em> token.
                    </div>
                </div>
            </x-core::card.body>
        </x-core::card>
    </div>

</div>

<ul class="nav nav-tabs mb-3" id="candidate-alert-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeCandidateAlertTab === 'alerts' ? 'active' : '' }}" id="candidate-alerts-tab" data-bs-toggle="tab" data-bs-target="#candidate-alerts-pane" type="button" role="tab" aria-controls="candidate-alerts-pane" aria-selected="{{ $activeCandidateAlertTab === 'alerts' ? 'true' : 'false' }}">
            <x-core::icon name="ti ti-list-details" class="me-1" /> Alerts
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeCandidateAlertTab === 'quick-add' ? 'active' : '' }}" id="candidate-alert-quick-add-tab" data-bs-toggle="tab" data-bs-target="#candidate-alert-quick-add-pane" type="button" role="tab" aria-controls="candidate-alert-quick-add-pane" aria-selected="{{ $activeCandidateAlertTab === 'quick-add' ? 'true' : 'false' }}">
            <x-core::icon name="ti ti-sparkles" class="me-1" /> Quick Add Options
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade {{ $activeCandidateAlertTab === 'alerts' ? 'show active' : '' }}" id="candidate-alerts-pane" role="tabpanel" aria-labelledby="candidate-alerts-tab">
        @include('core/table::base-table')
    </div>

    <div class="tab-pane fade {{ $activeCandidateAlertTab === 'quick-add' ? 'show active' : '' }}" id="candidate-alert-quick-add-pane" role="tabpanel" aria-labelledby="candidate-alert-quick-add-tab">
        <x-core::card>
            <x-core::card.header>
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 w-100">
                    <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2">
                        <x-core::icon name="ti ti-sparkles" class="text-primary" />
                        Quick Add Keyword Options
                    </h5>
                    <p class="text-muted small mb-0">These groups appear in the Keywords Quick Add dropdown when adding or editing a VIP job alert.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="createQuickAddPreset">
                        <x-core::icon name="ti ti-plus" class="me-1" />
                        Create Quick Add
                    </button>
                </div>
            </x-core::card.header>
            <x-core::card.body>
                <form method="POST" action="{{ route('job-board.candidate-alerts.quick-add-presets.update') }}" id="quickAddPresetForm">
                    @csrf
                    @method('PUT')
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="text-muted small" id="quickAddPresetSummary"></div>
                        <div class="d-flex align-items-center gap-2">
                            <label for="quickAddPresetPageSize" class="small text-muted mb-0">Show</label>
                            <select id="quickAddPresetPageSize" class="form-select form-select-sm" style="width:auto">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                    </div>
                    <div id="quickAddPresetRows" class="d-flex flex-column gap-3">
                        @foreach($keywordPresets as $presetIndex => $preset)
                            <div class="quick-add-preset-row border rounded p-3" data-preset-index="{{ $presetIndex }}">
                                <div class="row g-3 align-items-start">
                                    <div class="col-lg-4">
                                        <label class="form-label">Group Label</label>
                                        <input type="text" name="presets[{{ $presetIndex }}][label]" class="form-control" value="{{ $preset['label'] }}" maxlength="80" placeholder="e.g. Accounting & Finance">
                                    </div>
                                    <div class="col-lg-7">
                                        <label class="form-label">Keywords</label>
                                        <div class="quick-add-keywords-box" id="quick-add-keywords-{{ $presetIndex }}">
                                            @foreach(($preset['keywords'] ?? ['']) as $keyword)
                                                <div class="input-group input-group-sm mb-1 quick-add-keyword-row">
                                                    <input type="text" name="presets[{{ $presetIndex }}][keywords][]" class="form-control" value="{{ $keyword }}" placeholder="e.g. Software Engineer">
                                                    <button type="button" class="btn btn-outline-danger btn-remove-quick-add-keyword" title="Remove keyword" aria-label="Remove keyword">
                                                        <x-core::icon name="ti ti-x" />
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                                            <button type="button" class="btn btn-outline-secondary btn-sm btn-add-quick-add-keyword" data-group-index="{{ $presetIndex }}">
                                                <x-core::icon name="ti ti-plus" class="me-1" />
                                                Add Keyword
                                            </button>
                                            <div class="form-text mb-0">Saved keywords are de-duplicated. Empty rows are ignored.</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-1 d-flex justify-content-lg-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm mt-lg-4 btn-remove-quick-add-preset" title="Remove group" aria-label="Remove group">
                                            <x-core::icon name="ti ti-trash" class="me-1" />
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="quickAddPresetPrev">
                                <x-core::icon name="ti ti-chevron-left" class="me-1" />
                                Prev
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="quickAddPresetNext">
                                Next
                                <x-core::icon name="ti ti-chevron-right" class="ms-1" />
                            </button>
                            <span class="small text-muted" id="quickAddPresetPageLabel"></span>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <x-core::icon name="ti ti-device-floppy" class="me-1" />
                            Save Quick Add Options
                        </button>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                        <button type="button" class="btn btn-outline-primary" id="addQuickAddPreset">
                            <x-core::icon name="ti ti-plus" class="me-1" />
                            Add Another Group
                        </button>
                    </div>
                </form>
            </x-core::card.body>
        </x-core::card>
    </div>
</div>

{{-- ====================== ADD MODAL ====================== --}}
<div class="modal fade" id="modal-add-alert" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('job-board.candidate-alerts.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="ti ti-bell-plus text-primary"></i> Add VIP Job Alert
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('plugins/job-board::candidate-alerts._form', ['alert' => null, 'prefix' => 'add'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> Create Alert &amp; Send Welcome Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====================== EDIT MODAL ====================== --}}
<div class="modal fade" id="modal-edit-alert" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-body p-4 text-center text-muted">
                <i class="ti ti-loader-2 fa-spin fs-3 d-block mb-2"></i> Loading edit form...
            </div>
        </div>
    </div>
</div>

{{-- ====================== LOGS MODAL ====================== --}}
<div class="modal fade" id="modal-logs" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-history text-info"></i>
                    <span id="logsModalTitle">Send Logs</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="logsContent" class="p-3 text-center text-muted">
                    <i class="ti ti-loader-2 fa-spin fs-3 d-block mb-2"></i> Loading...
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====================== PREVIEW / SEND MODAL ====================== --}}
<div class="modal fade" id="modal-preview" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:92vw;width:92vw">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-search text-success"></i>
                    <span id="previewModalTitle">Matching Jobs</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="pv-send-progress" style="display:none;border-bottom:1px solid #dee2e6"></div>
                <div id="previewContent" class="p-3 text-center text-muted">
                    <i class="ti ti-loader-2 fa-spin fs-3 d-block mb-2"></i> Loading...
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="forceResendCheck">
                    <label class="form-check-label small" for="forceResendCheck">
                        Force resend (include already-sent jobs)
                    </label>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnExportCsv" title="Download as CSV">
                        <i class="fas fa-file-csv me-1"></i> CSV
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="btnExportPdf" title="Print / Save as PDF">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                    <button type="button" class="btn btn-success" id="btnSendNow" data-send-url="">
                        <i class="fab fa-whatsapp me-1"></i> Send All Matching
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====================== CV BUILDER MODAL ====================== --}}
<div class="modal fade" id="modal-cv-builder" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-file-cv text-dark"></i>
                    <span id="cvBuilderTitle">Build Candidate CV</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="cvBuilderError" class="alert alert-danger d-none py-2 px-3 small"></div>
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-semibold mb-3">WhatsApp Interview</h6>
                            <input type="hidden" id="cvBuilderStartUrl">
                            <input type="hidden" id="cvBuilderSessionsUrl">
                            <div class="mb-3">
                                <label class="form-label">Candidate Name</label>
                                <input type="text" id="cvBuilderCandidateName" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">WhatsApp Number</label>
                                <input type="text" id="cvBuilderWhatsapp" class="form-control">
                            </div>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-dark" id="btnStartCvBuilder">
                                    <i class="ti ti-brand-whatsapp me-1"></i> Start &amp; Send Question 1
                                </button>
                                <button type="button" class="btn btn-outline-dark" id="btnSendNextCvQuestion" disabled>
                                    <i class="ti ti-send me-1"></i> Send Next Question
                                </button>
                            </div>
                            <div class="small text-muted mt-3" id="cvBuilderSessionStatus">No active CV session selected.</div>
                            <div class="mt-3" id="cvBuilderDownloads"></div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="border rounded p-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <h6 class="fw-semibold mb-0">Paste Completed WhatsApp Chat</h6>
                                <button type="button" class="btn btn-primary btn-sm" id="btnGenerateCvFromChat" disabled>
                                    <i class="ti ti-sparkles me-1"></i> Generate DOCX &amp; PDF
                                </button>
                            </div>
                            <textarea id="cvBuilderConversation" class="form-control" rows="12" placeholder="Paste the whole WhatsApp chat here after all questions are answered."></textarea>
                            <div class="form-text">The full pasted conversation is stored with this CV builder session.</div>
                        </div>
                        <div class="border rounded p-3 mt-3">
                            <h6 class="fw-semibold mb-2">OpenAI Meta Response</h6>
                            <pre id="cvBuilderAiMeta" class="bg-light rounded p-3 small mb-0" style="max-height:320px;overflow:auto">No OpenAI request yet.</pre>
                        </div>
                    </div>
                </div>
                <div class="border rounded p-3 mt-3">
                    <h6 class="fw-semibold mb-2">Recent CV Sessions</h6>
                    <div id="cvBuilderSessions" class="text-muted small">No sessions loaded.</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====================== ACTION CONFIRM MODAL ====================== --}}
<div class="modal fade" id="modal-alert-action-confirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4 px-4">
                <div class="mb-3">
                    <span id="alertActionIconWrap" class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10" style="width:52px;height:52px;">
                        <i id="alertActionIcon" class="ti ti-alert-triangle text-warning fs-3"></i>
                    </span>
                </div>
                <h6 class="fw-semibold mb-1" id="alertActionTitle">Confirm action?</h6>
                <p class="text-muted small mb-4" id="alertActionMessage">Please confirm before continuing.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning px-4" id="confirmAlertAction">Continue</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('header')
<style>
.quick-add-preset-row[hidden] {
    display: none !important;
}
.quick-add-keywords-box {
    max-height: 108px;
    overflow-y: auto;
    padding-right: 4px;
}
.pv-match-reasons {
    background: #f8fafc;
    border-top: 1px solid #e9ecef;
}
.pv-match-reasons .badge {
    white-space: normal;
}
.table-actions .btn.btn-icon,
.table-actions .btn.btn-icon i,
.table-actions .btn.btn-icon svg {
    color: #fff !important;
}
</style>
@endpush

@push('footer')
<script>
$(function () {

    const alertActionModalEl = document.getElementById('modal-alert-action-confirm');
    const alertActionModal = alertActionModalEl ? new bootstrap.Modal(alertActionModalEl) : null;
    let pendingAlertAction = null;

    function showAlertActionConfirm(options) {
        if (! alertActionModal) {
            options.onConfirm();
            return;
        }

        pendingAlertAction = options;
        $('#alertActionTitle').text(options.title || 'Confirm action?');
        $('#alertActionMessage').text(options.message || 'Please confirm before continuing.');
        $('#alertActionIconWrap').attr('class', 'd-inline-flex align-items-center justify-content-center rounded-circle ' + (options.iconWrapClass || 'bg-warning bg-opacity-10'));
        $('#alertActionIcon').attr('class', options.iconClass || 'ti ti-alert-triangle text-warning fs-3');
        $('#confirmAlertAction').attr('class', 'btn px-4 ' + (options.confirmClass || 'btn-warning')).text(options.confirmText || 'Continue');
        alertActionModal.show();
    }

    $('#confirmAlertAction').on('click', function () {
        if (! pendingAlertAction) {
            return;
        }

        const action = pendingAlertAction;
        pendingAlertAction = null;
        alertActionModal.hide();
        action.onConfirm();
    });

    function setActionButtonLoading($btn) {
        $btn.data('original-html', $btn.html());
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>');
    }

    function restoreActionButton($btn) {
        $btn.prop('disabled', false).html($btn.data('original-html') || '');
    }

    let quickAddPresetIndex = {{ count($keywordPresets) }};
    let quickAddPresetPage = 1;
    let quickAddPresetPageSize = 10;

    function renderQuickAddPresetPagination() {
        const rows = $('#quickAddPresetRows .quick-add-preset-row');
        const total = rows.length;
        const showAll = quickAddPresetPageSize === 'all';
        const totalPages = showAll || total === 0 ? 1 : Math.max(1, Math.ceil(total / quickAddPresetPageSize));

        if (quickAddPresetPage > totalPages) {
            quickAddPresetPage = totalPages;
        }

        rows.each(function (index) {
            if (showAll) {
                this.hidden = false;
                return;
            }

            const start = (quickAddPresetPage - 1) * quickAddPresetPageSize;
            const end = start + quickAddPresetPageSize;
            this.hidden = index < start || index >= end;
        });

        const startRecord = total === 0 ? 0 : (showAll ? 1 : ((quickAddPresetPage - 1) * quickAddPresetPageSize) + 1);
        const endRecord = total === 0 ? 0 : (showAll ? total : Math.min(total, quickAddPresetPage * quickAddPresetPageSize));

        $('#quickAddPresetSummary').text(total ? `Showing ${startRecord} to ${endRecord} of ${total} quick add groups` : 'No quick add groups configured');
        $('#quickAddPresetPageLabel').text(showAll ? `All ${total} records` : `Page ${quickAddPresetPage} of ${totalPages}`);
        $('#quickAddPresetPrev').prop('disabled', showAll || quickAddPresetPage <= 1 || total === 0);
        $('#quickAddPresetNext').prop('disabled', showAll || quickAddPresetPage >= totalPages || total === 0);
    }

    $('#addQuickAddPreset').on('click', function () {
        const index = quickAddPresetIndex++;
        const row = `
            <div class="quick-add-preset-row border rounded p-3" data-preset-index="${index}">
                <div class="row g-3 align-items-start">
                    <div class="col-lg-4">
                        <label class="form-label">Group Label</label>
                        <input type="text" name="presets[${index}][label]" class="form-control" maxlength="80" placeholder="e.g. Accounting & Finance">
                    </div>
                    <div class="col-lg-7">
                        <label class="form-label">Keywords</label>
                        <div class="quick-add-keywords-box" id="quick-add-keywords-${index}">
                            <div class="input-group input-group-sm mb-1 quick-add-keyword-row">
                                <input type="text" name="presets[${index}][keywords][]" class="form-control" placeholder="e.g. Software Engineer">
                                <button type="button" class="btn btn-outline-danger btn-remove-quick-add-keyword" title="Remove keyword" aria-label="Remove keyword">
                                    <svg class="icon svg-icon-ti-ti-x" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6l-12 12"></path><path d="M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-add-quick-add-keyword" data-group-index="${index}">
                                <svg class="icon svg-icon-ti-ti-plus me-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg>
                                Add Keyword
                            </button>
                            <div class="form-text mb-0">Saved keywords are de-duplicated. Empty rows are ignored.</div>
                        </div>
                    </div>
                    <div class="col-lg-1 d-flex justify-content-lg-end">
                        <button type="button" class="btn btn-outline-danger btn-sm mt-lg-4 btn-remove-quick-add-preset" title="Remove group" aria-label="Remove group">
                            Remove
                        </button>
                    </div>
                </div>
            </div>`;

        const $rows = $('#quickAddPresetRows');
        $rows.append(row);

        if (quickAddPresetPageSize !== 'all') {
            quickAddPresetPage = Math.max(1, Math.ceil($rows.find('.quick-add-preset-row').length / quickAddPresetPageSize));
        }

        renderQuickAddPresetPagination();
        $rows.find('.quick-add-preset-row:last input').trigger('focus');
    });

    $('#createQuickAddPreset').on('click', function () {
        $('#addQuickAddPreset').trigger('click');
    });

    $(document).on('click', '.btn-remove-quick-add-preset', function () {
        $(this).closest('.quick-add-preset-row').remove();
        renderQuickAddPresetPagination();
    });

    $(document).on('click', '.btn-add-quick-add-keyword', function () {
        const groupIndex = $(this).data('group-index');
        const $box = $('#quick-add-keywords-' + groupIndex);
        const row = `
            <div class="input-group input-group-sm mb-1 quick-add-keyword-row">
                <input type="text" name="presets[${groupIndex}][keywords][]" class="form-control" placeholder="e.g. Software Engineer">
                <button type="button" class="btn btn-outline-danger btn-remove-quick-add-keyword" title="Remove keyword" aria-label="Remove keyword">
                    <svg class="icon svg-icon-ti-ti-x" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6l-12 12"></path><path d="M6 6l12 12"></path></svg>
                </button>
            </div>`;

        $box.append(row);
        $box.find('.quick-add-keyword-row:last input').trigger('focus');
    });

    $(document).on('click', '.btn-remove-quick-add-keyword', function () {
        const $box = $(this).closest('.quick-add-keywords-box');
        const $rows = $box.find('.quick-add-keyword-row');

        if ($rows.length > 1) {
            $(this).closest('.quick-add-keyword-row').remove();
        } else {
            $rows.find('input').val('').trigger('focus');
        }
    });

    $('#quickAddPresetPageSize').on('change', function () {
        const value = $(this).val();
        quickAddPresetPageSize = value === 'all' ? 'all' : parseInt(value, 10);
        quickAddPresetPage = 1;
        renderQuickAddPresetPagination();
    });

    $('#quickAddPresetPrev').on('click', function () {
        if (quickAddPresetPage > 1) {
            quickAddPresetPage--;
            renderQuickAddPresetPagination();
        }
    });

    $('#quickAddPresetNext').on('click', function () {
        quickAddPresetPage++;
        renderQuickAddPresetPagination();
    });

    renderQuickAddPresetPagination();

    // Open Add Alert modal when the DataTable "Add Alert" button is clicked
    $(document).on('click', '[data-action="add_alert"]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        new bootstrap.Modal(document.getElementById('modal-add-alert')).show();
    });

    // ── Phone number duplicate check ─────────────────────────────────────────
    $(document).on('blur', '.phone-check-input', function () {
        const $input     = $(this);
        const phone      = $input.val().trim();
        const checkUrl   = $input.data('check-url');
        const excludeId  = $input.data('exclude-id') || 0;
        const $warning   = $input.closest('.col-md-6').find('.phone-check-warning');

        $warning.hide().html('');
        if (! phone) return;

        fetch(checkUrl + '?phone=' + encodeURIComponent(phone) + '&exclude_id=' + excludeId)
            .then(r => r.json())
            .then(resp => {
                if (! resp.exists) return;
                const alerts = resp.alerts || [];
                let html = '<div class="alert alert-warning py-2 px-3 mb-0 small">'
                    + '<i class="fas fa-exclamation-triangle me-1"></i>'
                    + '<strong>This number already has ' + alerts.length + ' alert(s):</strong><ul class="mb-0 mt-1 ps-3">';
                alerts.forEach(a => {
                    const statusBadge = a.active && a.status === 'active'
                        ? '<span class="badge bg-success text-white ms-1" style="font-size:.65rem">Active</span>'
                        : '<span class="badge bg-secondary text-white ms-1" style="font-size:.65rem">' + escHtml(a.status) + '</span>';
                    html += '<li>' + escHtml(a.label) + statusBadge + '</li>';
                });
                html += '</ul><div class="mt-1 text-muted">You can still add another alert for this number.</div></div>';
                $warning.html(html).show();
            });
    });

    $(document).on('input', '.phone-check-input', function () {
        $(this).closest('.col-md-6').find('.phone-check-warning').hide();
    });

    // ── Double-submit prevention ─────────────────────────────────────────────
    $(document).on('submit', '.modal form', function () {
        const $form = $(this);
        if ($form.data('submitting')) return false;
        $form.data('submitting', true);
        const $btn = $form.find('button[type="submit"]');
        $btn.prop('disabled', true).data('original-html', $btn.html()).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving…');
        setTimeout(() => {
            $form.data('submitting', false);
            $btn.prop('disabled', false).html($btn.data('original-html'));
        }, 8000);
    });

    // ── Toggle active/inactive ────────────────────────────────────────────────
    $(document).on('change', '.alert-toggle', function () {
        const $toggle = $(this);
        const $row    = $toggle.closest('tr');
        const $badge  = $row.find('.alert-status-badge');
        const url     = $toggle.data('url');
        const active  = $toggle.is(':checked');

        $httpClient.make().post(url)
            .then(({ data: resp }) => {
                const isActive = resp.data?.is_active ?? active;
                $badge.text(isActive ? 'Active' : 'Disabled')
                    .removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary bg-danger-subtle text-danger')
                    .addClass(isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary');
                $toggle.prop('checked', isActive);
                Botble.showSuccess(isActive ? 'Alert enabled.' : 'Alert disabled.');
            })
            .catch(() => {
                $toggle.prop('checked', !active);
                Botble.showError('Could not update alert.');
            });
    });

    // ── Logs modal ────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-view-logs', function () {
        const $btn = $(this), url = $btn.data('url'), name = $btn.data('name');
        $('#logsModalTitle').text('Send Logs — ' + name);
        $('#logsContent').html('<div class="p-3 text-center text-muted"><i class="ti ti-loader-2 fa-spin fs-3 d-block mb-2"></i> Loading...</div>');
        new bootstrap.Modal(document.getElementById('modal-logs')).show();
        $httpClient.make().get(url)
            .then(({ data: resp }) => {
                const logs = resp.data || [];
                if (!logs.length) { $('#logsContent').html('<div class="p-4 text-center text-muted"><i class="ti ti-inbox d-block fs-2 mb-2"></i>No jobs sent yet.</div>'); return; }
                let html = '<table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th>#</th><th>Job</th><th>Company</th><th>Status</th><th>Sent At</th></tr></thead><tbody>';
                logs.forEach((log, i) => {
                    const sc = log.status === 'sent' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                    html += `<tr><td class="text-muted small">${i+1}</td><td><span class="fw-semibold">${escHtml(log.job_name)}</span>${log.error ? '<div class="text-danger small">'+escHtml(log.error)+'</div>' : ''}</td><td class="text-muted small">${escHtml(log.company)}</td><td><span class="badge ${sc}">${escHtml(log.status)}</span></td><td class="text-muted small">${escHtml(log.sent_at || '')}</td></tr>`;
                });
                html += '</tbody></table>';
                $('#logsContent').html(html);
            })
            .catch(() => $('#logsContent').html('<div class="p-4 text-center text-danger">Failed to load logs.</div>'));
    });

    $(document).on('click', '.btn-edit-alert-modal', function () {
        const url = $(this).data('url');
        const $modal = $('#modal-edit-alert');
        const modal = new bootstrap.Modal($modal[0]);

        $modal.find('.modal-content').html('<div class="modal-body p-4 text-center text-muted"><i class="ti ti-loader-2 fa-spin fs-3 d-block mb-2"></i> Loading edit form...</div>');
        modal.show();

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                $modal.find('.modal-content').html(html);
            })
            .catch(() => {
                $modal.find('.modal-content').html('<div class="modal-body p-4 text-center text-danger">Failed to load the edit form.</div>');
            });
    });

    $(document).on('click', '.btn-reanalyze-alert-cv', function () {
        const $btn = $(this);
        const prefix = $btn.data('prefix');
        const url = $btn.data('url');

        $btn.prop('disabled', true).html('<i class="ti ti-loader-2 fa-spin me-1"></i> Re-analysing…');

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        })
            .then(r => r.json())
            .then(resp => {
                if (resp.error) {
                    Botble.showError(resp.error);
                    return;
                }

                applyAnalysisToForm(prefix, resp.data, true);
                Botble.showSuccess('Stored CV re-analysed and filters updated.');
            })
            .catch(() => Botble.showError('Stored CV re-analysis failed.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="ti ti-refresh me-1"></i> Re-analyse CV'));
    });

    // ── Preview & Send modal ──────────────────────────────────────────────────
    let previewAllJobs = [], previewPage = 1;
    const PREVIEW_PER_PAGE = 25;

    $(document).on('click', '.btn-preview-jobs', function () {
        const $btn = $(this), url = $btn.data('url'), sendUrl = $btn.data('send-url'), name = $btn.data('name');
        $('#previewModalTitle').text('Matching Jobs — ' + name);
        $('#previewContent').html('<div class="p-3 text-center text-muted"><i class="ti ti-loader-2 fa-spin fs-3 d-block mb-2"></i> Searching matching jobs…</div>');
        $('#btnSendNow').data('send-url', sendUrl).data('preview-url', url);
        $('#forceResendCheck').prop('checked', false);
        new bootstrap.Modal(document.getElementById('modal-preview')).show();
        $httpClient.make().get(url)
            .then(({ data: resp }) => {
                previewAllJobs = resp.data || [];
                const total = resp.total || 0;
                if (!previewAllJobs.length) { $('#previewContent').html('<div class="p-4 text-center text-muted"><i class="fas fa-search d-block fs-2 mb-2"></i>No matching jobs found.</div>'); return; }
                const countries = [...new Set(previewAllJobs.map(j => j.country).filter(Boolean))].sort();
                const countryOpts = countries.map(c => `<option value="${escHtml(c)}">${escHtml(c)}</option>`).join('');
                const filtersBar = `<div class="d-flex align-items-center gap-2 flex-wrap px-3 pt-3 pb-2 border-bottom bg-light"><select id="pv-country" class="form-select form-select-sm" style="max-width:160px"><option value="">All Countries</option>${countryOpts}</select><input type="text" id="pv-company" class="form-control form-control-sm" placeholder="Filter by company…" style="max-width:200px"><select id="pv-period" class="form-select form-select-sm" style="max-width:150px"><option value="">Any Date</option><option value="today">Today</option><option value="7">Last 7 Days</option><option value="14">Last 14 Days</option><option value="30">Last 30 Days</option></select><button type="button" class="btn btn-outline-secondary btn-sm" id="pv-clear-filters"><i class="fas fa-times me-1"></i>Clear</button><span id="pv-count-label" class="text-muted small ms-auto"></span></div><div id="pv-table-wrap"></div><div id="pv-pagination" class="d-flex align-items-center justify-content-between px-3 py-2 border-top"></div>`;
                $('#previewContent').html(filtersBar);
                previewPage = 1; renderPreviewTable(total);
                $('#pv-country, #pv-period').on('change', () => { previewPage = 1; renderPreviewTable(total); });
                $('#pv-company').on('input', () => { previewPage = 1; renderPreviewTable(total); });
                $('#pv-clear-filters').on('click', () => { $('#pv-country').val(''); $('#pv-company').val(''); $('#pv-period').val(''); previewPage = 1; renderPreviewTable(total); });
            })
            .catch(() => $('#previewContent').html('<div class="p-4 text-center text-danger">Failed to load matching jobs.</div>'));
        $(document).on('click', '.pv-page-btn:not([disabled])', function () {
            previewPage = parseInt($(this).data('p'));
            renderPreviewTable(previewAllJobs.length);
            $('#pv-table-wrap')[0]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    function getFilteredJobs() {
        const country = $('#pv-country').val() || '', company = ($('#pv-company').val() || '').toLowerCase().trim(), period = $('#pv-period').val() || '';
        const today = new Date(); today.setHours(0,0,0,0);
        const cutoff = period === 'today' ? today : period ? new Date(today - parseInt(period) * 86400000) : null;
        return previewAllJobs.filter(job => {
            if (country && job.country !== country) return false;
            if (company && !(job.company || '').toLowerCase().includes(company)) return false;
            if (cutoff && job.created_date && new Date(job.created_date) < cutoff) return false;
            return true;
        }).sort((a, b) => {
            const da = a.deadline_days !== null && a.deadline_days !== undefined ? a.deadline_days : Infinity;
            const db = b.deadline_days !== null && b.deadline_days !== undefined ? b.deadline_days : Infinity;
            return db - da;
        });
    }

    function renderPreviewTable(serverTotal) {
        const filtered = getFilteredJobs(), total = filtered.length, pages = Math.max(1, Math.ceil(total / PREVIEW_PER_PAGE));
        previewPage = Math.min(previewPage, pages);
        const start = (previewPage - 1) * PREVIEW_PER_PAGE, slice = filtered.slice(start, start + PREVIEW_PER_PAGE);
        $('#pv-count-label').text(total !== previewAllJobs.length ? `${total} of ${serverTotal} jobs match filters` : `${serverTotal} matching job(s) total`);
        let html = '<table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th style="width:36px">#</th><th>Job Title</th><th>Company Name</th><th>Address</th><th>Country</th><th>Posted / Closes</th><th>Matched By</th><th class="text-center">Sent?</th></tr></thead><tbody>';
        if (!slice.length) { html += '<tr><td colspan="8" class="text-center text-muted py-4">No jobs match your filters.</td></tr>'; }
        else { slice.forEach((job, idx) => {
            const rowNum = start + idx + 1, sentBadge = job.already_sent ? '<span class="badge bg-success text-white">✓ Sent</span>' : '<span class="badge bg-secondary text-white">New</span>';
            let deadlineBadge = '<span class="text-muted small">—</span>';
            if (job.deadline_days !== null && job.deadline_days !== undefined) {
                const d = job.deadline_days;
                deadlineBadge = d < 0 ? '<span class="badge bg-danger text-white">Expired</span>' : d === 0 ? '<span class="badge bg-danger text-white">Today</span>' : d <= 3 ? `<span class="badge bg-warning text-dark" title="${escHtml(job.deadline)}">${d}d left</span>` : d <= 14 ? `<span class="badge bg-info text-white" title="${escHtml(job.deadline)}">${d}d left</span>` : `<span class="text-muted small text-nowrap" title="${escHtml(job.deadline)}">${d}d</span>`;
            }
            const countryLabel = [job.country_flag || '', job.country || ''].filter(Boolean).join(' ');
            const dateBlock = `<div class="small text-nowrap"><div><span class="text-muted">Posted:</span> ${escHtml(job.created || '—')}</div><div><span class="text-muted">Closes:</span> ${job.deadline ? escHtml(job.deadline) : '—'} ${deadlineBadge}</div></div>`;
            const reasons = Array.isArray(job.match_reasons) ? job.match_reasons : [];
            const reasonBadges = reasons.length
                ? reasons.slice(0, 3).map((reason) => `<span class="badge bg-light text-dark border me-1 mb-1">${escHtml(reason.field)}${reason.keyword ? ': ' + escHtml(reason.keyword) : ''}</span>`).join('')
                : '<span class="text-muted small">General match</span>';
            const reasonDetails = reasons.length
                ? reasons.map((reason) => `<div class="small mb-2"><div class="fw-semibold">${escHtml(reason.field)}${reason.keyword ? ' · ' + escHtml(reason.keyword) : ''}</div><div class="text-muted">${escHtml(reason.snippet || '')}</div></div>`).join('')
                : '<div class="small text-muted">No detailed trigger information available.</div>';

            html += `<tr class="pv-job-row" data-job-id="${job.id}"><td class="text-muted small text-center">${rowNum}</td><td class="fw-semibold" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${escHtml(job.name)}">${escHtml(job.name)}</td><td class="text-muted small"><div class="d-flex align-items-center gap-2">${job.company_logo ? `<img src="${escHtml(job.company_logo)}" alt="" style="width:28px;height:28px;object-fit:contain;border-radius:4px;border:1px solid #eee;background:#fff;padding:2px">` : ''}<span>${escHtml(job.company)}</span></div></td><td class="text-muted small">${escHtml(job.location || '')}</td><td class="text-muted small">${escHtml(countryLabel)}</td><td>${dateBlock}</td><td>${reasonBadges}<div class="small text-primary mt-1">View trigger</div></td><td>${sentBadge}</td></tr>`;
            html += `<tr class="pv-match-reasons-row d-none"><td colspan="8" class="pv-match-reasons px-3 py-2">${reasonDetails}</td></tr>`;
        }); }
        html += '</tbody></table>';
        $('#pv-table-wrap').html(html);
        if (total <= PREVIEW_PER_PAGE) { $('#pv-pagination').empty(); }
        else {
            let pager = `<span class="text-muted small">Showing ${start+1}–${Math.min(start+PREVIEW_PER_PAGE,total)} of ${total}</span><div class="d-flex gap-1">`;
            pager += `<button class="btn btn-sm btn-outline-secondary pv-page-btn" data-p="1" ${previewPage===1?'disabled':''}>«</button>`;
            pager += `<button class="btn btn-sm btn-outline-secondary pv-page-btn" data-p="${previewPage-1}" ${previewPage===1?'disabled':''}>‹ Prev</button>`;
            for (let p = Math.max(1,previewPage-2); p <= Math.min(pages,previewPage+2); p++) pager += `<button class="btn btn-sm ${p===previewPage?'btn-primary text-white':'btn-outline-secondary'} pv-page-btn" data-p="${p}">${p}</button>`;
            pager += `<button class="btn btn-sm btn-outline-secondary pv-page-btn" data-p="${previewPage+1}" ${previewPage===pages?'disabled':''}>Next ›</button><button class="btn btn-sm btn-outline-secondary pv-page-btn" data-p="${pages}" ${previewPage===pages?'disabled':''}>»</button></div>`;
            $('#pv-pagination').html(pager);
        }
    }

    $(document).on('click', '.pv-job-row', function () {
        const $row = $(this);
        const $detail = $row.next('.pv-match-reasons-row');
        const wasOpen = !$detail.hasClass('d-none');

        $('#pv-table-wrap .pv-match-reasons-row').addClass('d-none');
        $('#pv-table-wrap .pv-job-row').removeClass('table-active');

        if (!wasOpen) {
            $row.addClass('table-active');
            $detail.removeClass('d-none');
        }
    });

    // Send Now
    $('#btnSendNow').on('click', async function () {
        const $btn = $(this), sendUrl = $btn.data('send-url'), previewUrl = $btn.data('preview-url'), forceResend = $('#forceResendCheck').is(':checked'), BATCH = 3, BATCH_GAP = 400;

        // Refresh "already sent" state first — the daily digest (or a real-time VIP
        // alert) may have sent some of these jobs in the background since this preview
        // was opened, and we don't want to report those as failures below.
        if (previewUrl) {
            $btn.prop('disabled', true).html('<i class="ti ti-loader-2 fa-spin me-1"></i> Checking…');
            try {
                const { data: resp } = await $httpClient.make().get(previewUrl);
                const freshById = new Map((resp.data || []).map(j => [j.id, j.already_sent]));
                previewAllJobs.forEach(j => { if (freshById.has(j.id)) j.already_sent = freshById.get(j.id); });
                renderPreviewTable(previewAllJobs.length);
            } catch (e) { /* non-fatal — fall back to the existing snapshot */ }
        }

        const jobsToSend = forceResend ? [...previewAllJobs] : previewAllJobs.filter(j => !j.already_sent);
        if (!jobsToSend.length) { Botble.showError('No new jobs to send.'); $btn.prop('disabled', false).html('<i class="fab fa-whatsapp me-1"></i> Send All Matching'); return; }
        const total = jobsToSend.length; let done = 0, sent = 0, skipped = 0, failed = 0; const failedJobs = [];
        $btn.prop('disabled', true).html('<i class="fab fa-whatsapp me-1"></i> Sending…');
        $('#btnExportCsv, #btnExportPdf, #forceResendCheck, #pv-country, #pv-company, #pv-period, #pv-clear-filters').prop('disabled', true);
        $('#pv-send-progress').html(`<div class="px-3 pt-3 pb-2"><div class="d-flex align-items-center justify-content-between mb-1"><span class="small fw-semibold" id="pv-prog-label">Sending 0 of ${total}…</span><span class="small text-muted" id="pv-prog-counts">0 sent · 0 failed</span></div><div class="progress mb-1" style="height:8px"><div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="pv-prog-bar" role="progressbar" style="width:0%;transition:width .4s ease"></div></div><div class="text-muted small text-truncate" id="pv-prog-current" style="min-height:1.3em"></div></div>`).show();
        $('#pv-send-failures').remove();
        const renderFailedJobs = () => {
            $('#pv-send-failures').remove();
            if (!failedJobs.length) return;
            const items = failedJobs.map((item, index) => `<tr><td class="text-muted small">${index + 1}</td><td class="fw-semibold">${escHtml(item.name)}</td><td class="text-danger small">${escHtml(item.error || 'Failed to send')}</td></tr>`).join('');
            $('#pv-send-progress').after(`<div id="pv-send-failures" class="border-bottom"><div class="px-3 pt-2 pb-3"><div class="alert alert-danger py-2 px-3 mb-2 small"><strong>${failedJobs.length} failed job(s)</strong> could not be sent. Review the reasons below.</div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th style="width:36px">#</th><th>Job</th><th>Reason</th></tr></thead><tbody>${items}</tbody></table></div></div></div>`);
        };
        const sendOne = async (job) => {
            $('#pv-prog-current').html(`<i class="fas fa-paper-plane me-1 text-success"></i>${escHtml(job.name)}`);
            try {
                const { data } = await $httpClient.make().post(sendUrl, { force_resend: forceResend ? 1 : 0, job_ids: [job.id] }, { timeout: 30000 });
                const wasSkipped = Array.isArray(data?.skipped_jobs) && data.skipped_jobs.length > 0;
                if (wasSkipped) { skipped++; } else { sent++; }
                job.already_sent = true;
                const $row = $(`tr[data-job-id="${job.id}"]`);
                if ($row.length) { $row.css({ transition: 'opacity .3s ease, transform .3s ease', opacity: 0, transform: 'translateX(40px)' }); setTimeout(() => $row.remove(), 320); }
                if (Array.isArray(data?.failed_jobs) && data.failed_jobs.length) {
                    data.failed_jobs.forEach(item => failedJobs.push({ id: item.job_id, name: item.job_name || job.name, error: item.error || 'Failed to send' }));
                }
            } catch (error) {
                failed++;
                const response = error?.response?.data || {};
                const failedItem = Array.isArray(response.failed_jobs) && response.failed_jobs.length ? response.failed_jobs[0] : null;
                failedJobs.push({ id: failedItem?.job_id || job.id, name: failedItem?.job_name || job.name, error: failedItem?.error || response.error || 'Failed to send' });
                const $row = $(`tr[data-job-id="${job.id}"]`);
                if ($row.length) $row.find('td:nth-child(8)').html('<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Failed</span>');
            }
            done++; const pct = Math.round((done/total)*100);
            $('#pv-prog-bar').css('width', pct + '%');
            $('#pv-prog-label').text(`Sending ${Math.min(done+BATCH,total)} of ${total}…`);
            $('#pv-prog-counts').text(`${sent} sent · ${skipped} already sent · ${failed} failed`);
            renderFailedJobs();
        };
        for (let i = 0; i < jobsToSend.length; i += BATCH) {
            await Promise.all(jobsToSend.slice(i, i+BATCH).map(sendOne));
            if (i+BATCH < jobsToSend.length) await new Promise(r => setTimeout(r, BATCH_GAP));
        }
        $('#pv-prog-label').text(`Done — ${sent} sent${skipped ? `, ${skipped} already sent` : ''}${failed ? `, ${failed} failed` : ''}.`);
        $('#pv-prog-current').html(''); $('#pv-prog-bar').removeClass('progress-bar-animated progress-bar-striped').css('width','100%');
        renderFailedJobs();
        failed ? Botble.showError(`${sent} sent, ${skipped} already sent, ${failed} failed.`) : Botble.showSuccess(`${sent} job(s) sent` + (skipped ? `, ${skipped} already sent (skipped)` : '') + '.');
        $btn.prop('disabled', false).html('<i class="fab fa-whatsapp me-1"></i> Send All Matching');
        $('#btnExportCsv, #btnExportPdf, #forceResendCheck, #pv-country, #pv-company, #pv-period, #pv-clear-filters').prop('disabled', false);
        if (!failed) setTimeout(() => location.reload(), 1500);
    });

    // ── CV Builder ───────────────────────────────────────────────────────────
    let cvBuilderSession = null;
    let cvBuilderSessionsCache = [];

    function setCvBuilderError(message) {
        const $box = $('#cvBuilderError');
        if (!message) {
            $box.addClass('d-none').text('');
            return;
        }
        $box.removeClass('d-none').text(message);
    }

    function renderCvBuilderSession(session) {
        cvBuilderSession = session || null;

        if (!session) {
            $('#cvBuilderSessionStatus').text('No active CV session selected.');
            $('#btnSendNextCvQuestion, #btnGenerateCvFromChat').prop('disabled', true);
            $('#cvBuilderDownloads').empty();
            return;
        }

        const sent = session.current_question_index || 0;
        const total = session.question_total || 0;
        const complete = sent >= total;
        const statusLabel = complete
            ? 'All questions sent. Paste the completed chat and generate the CV.'
            : `Question ${sent + 1} of ${total} is next.`;

        $('#cvBuilderSessionStatus').html(
            `<div><strong>Session #${session.id}</strong> · ${escHtml(session.status)}</div>`
            + `<div>${escHtml(statusLabel)}</div>`
            + (session.next_question ? `<div class="mt-2 text-dark"><strong>Next:</strong> ${escHtml(session.next_question)}</div>` : '')
        );

        $('#btnSendNextCvQuestion').prop('disabled', complete || !session.send_next_url);
        $('#btnGenerateCvFromChat').prop('disabled', !session.generate_url);

        let downloads = '';
        if (session.docx_url) {
            downloads += `<a class="btn btn-outline-primary btn-sm me-1 mb-1" href="${escHtml(session.docx_url)}"><i class="ti ti-file-type-docx me-1"></i> DOCX</a>`;
        }
        if (session.pdf_url) {
            downloads += `<a class="btn btn-outline-danger btn-sm me-1 mb-1" href="${escHtml(session.pdf_url)}"><i class="ti ti-file-type-pdf me-1"></i> PDF</a>`;
        }
        $('#cvBuilderDownloads').html(downloads);

        if (session.ai_logs && session.ai_logs.length) {
            $('#cvBuilderAiMeta').text(JSON.stringify(session.ai_logs[0], null, 2));
        }
    }

    function renderCvBuilderSessions(sessions) {
        cvBuilderSessionsCache = sessions;

        if (!sessions.length) {
            $('#cvBuilderSessions').html('<div class="text-muted small">No CV builder sessions yet.</div>');
            return;
        }

        const html = sessions.map((session, index) => {
            const downloads = [
                session.docx_url ? `<a href="${escHtml(session.docx_url)}" class="btn btn-outline-primary btn-sm">DOCX</a>` : '',
                session.pdf_url ? `<a href="${escHtml(session.pdf_url)}" class="btn btn-outline-danger btn-sm">PDF</a>` : '',
            ].filter(Boolean).join(' ');

            return `<div class="border rounded p-2 mb-2 cv-builder-session-row">
                <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                    <div>
                        <div class="fw-semibold">Session #${session.id} · ${escHtml(session.status)}</div>
                        <div class="text-muted small">${escHtml(session.candidate_name || '')} · ${escHtml(session.whatsapp_number || '')}</div>
                        <div class="text-muted small">${session.current_question_index || 0}/${session.question_total || 0} questions sent${session.completed_at ? ' · completed ' + escHtml(session.completed_at) : ''}</div>
                    </div>
                    <div class="d-flex gap-1 flex-wrap">${downloads}<button type="button" class="btn btn-outline-secondary btn-sm btn-select-cv-session" data-index="${index}">Select</button></div>
                </div>
            </div>`;
        }).join('');

        $('#cvBuilderSessions').html(html);
    }

    function loadCvBuilderSessions() {
        const url = $('#cvBuilderSessionsUrl').val();
        if (!url) return;

        $('#cvBuilderSessions').html('<div class="text-muted small"><i class="ti ti-loader-2 fa-spin me-1"></i> Loading sessions...</div>');

        $httpClient.make().get(url)
            .then(({ data: resp }) => {
                const sessions = resp.data || [];
                renderCvBuilderSessions(sessions);
                renderCvBuilderSession(sessions[0] || null);
            })
            .catch(() => {
                $('#cvBuilderSessions').html('<div class="text-danger small">Failed to load CV sessions.</div>');
            });
    }

    $(document).on('click', '.btn-cv-builder', function () {
        const $btn = $(this);

        setCvBuilderError(null);
        cvBuilderSession = null;
        $('#cvBuilderTitle').text('Build CV — ' + ($btn.data('name') || 'Candidate'));
        $('#cvBuilderCandidateName').val($btn.data('name') || '');
        $('#cvBuilderWhatsapp').val($btn.data('phone') || '');
        $('#cvBuilderStartUrl').val($btn.data('start-url') || '');
        $('#cvBuilderSessionsUrl').val($btn.data('sessions-url') || '');
        $('#cvBuilderConversation').val('');
        $('#cvBuilderAiMeta').text('No OpenAI request yet.');
        $('#cvBuilderDownloads').empty();
        renderCvBuilderSession(null);

        new bootstrap.Modal(document.getElementById('modal-cv-builder')).show();
        loadCvBuilderSessions();
    });

    $(document).on('click', '.btn-select-cv-session', function () {
        const index = parseInt($(this).data('index'), 10);
        if (Number.isNaN(index) || !cvBuilderSessionsCache[index]) {
            Botble.showError('Could not select CV session.');
            return;
        }

        renderCvBuilderSession(cvBuilderSessionsCache[index]);
    });

    $('#btnStartCvBuilder').on('click', function () {
        const $btn = $(this);
        const url = $('#cvBuilderStartUrl').val();

        setCvBuilderError(null);
        setActionButtonLoading($btn);

        $httpClient.make().post(url, {
            candidate_name: $('#cvBuilderCandidateName').val(),
            whatsapp_number: $('#cvBuilderWhatsapp').val()
        })
            .then(({ data: resp }) => {
                Botble.showSuccess(resp.message || 'CV builder started.');
                renderCvBuilderSession(resp.data);
                loadCvBuilderSessions();
            })
            .catch(({ response }) => {
                const message = response?.data?.error || 'Failed to start CV builder.';
                setCvBuilderError(message);
                Botble.showError(message);
            })
            .finally(() => restoreActionButton($btn));
    });

    $('#btnSendNextCvQuestion').on('click', function () {
        if (!cvBuilderSession?.send_next_url) return;

        const $btn = $(this);
        setCvBuilderError(null);
        setActionButtonLoading($btn);

        $httpClient.make().post(cvBuilderSession.send_next_url)
            .then(({ data: resp }) => {
                Botble.showSuccess(resp.message || 'Question sent.');
                renderCvBuilderSession(resp.data);
                loadCvBuilderSessions();
            })
            .catch(({ response }) => {
                const message = response?.data?.error || 'Failed to send next question.';
                setCvBuilderError(message);
                Botble.showError(message);
            })
            .finally(() => restoreActionButton($btn));
    });

    $('#btnGenerateCvFromChat').on('click', function () {
        if (!cvBuilderSession?.generate_url) return;

        const $btn = $(this);
        setCvBuilderError(null);
        setActionButtonLoading($btn);
        $('#cvBuilderAiMeta').text('Generating CV with OpenAI...');

        $httpClient.make().post(cvBuilderSession.generate_url, {
            conversation_text: $('#cvBuilderConversation').val()
        }, { timeout: 120000 })
            .then(({ data: resp }) => {
                Botble.showSuccess(resp.message || 'CV generated.');
                renderCvBuilderSession(resp.data);
                $('#cvBuilderAiMeta').text(JSON.stringify(resp.ai_meta || {}, null, 2));
                loadCvBuilderSessions();
            })
            .catch(({ response }) => {
                const message = response?.data?.error || 'Failed to generate CV.';
                setCvBuilderError(message);
                $('#cvBuilderAiMeta').text(response?.data?.ai_meta ? JSON.stringify(response.data.ai_meta, null, 2) : message);
                Botble.showError(message);
            })
            .finally(() => restoreActionButton($btn));
    });

    // Send welcome
    $(document).on('click', '.btn-send-welcome', function () {
        const $btn = $(this), url = $btn.data('url'), name = $btn.data('name');

        showAlertActionConfirm({
            title: 'Resend VIP welcome?',
            message: 'Send the VIP welcome message to ' + name + '?',
            confirmText: 'Send',
            confirmClass: 'btn-warning',
            iconWrapClass: 'bg-warning bg-opacity-10',
            iconClass: 'ti ti-refresh text-warning fs-3',
            onConfirm: function () {
                setActionButtonLoading($btn);
                $httpClient.make().post(url)
                    .then(({ data: resp }) => Botble.showSuccess(resp.message || 'Welcome message sent to ' + name + '.'))
                    .catch(({ response }) => Botble.showError(response?.data?.error || 'Failed to send.'))
                    .finally(() => restoreActionButton($btn));
            }
        });
    });

    // Send account invite
    $(document).on('click', '.btn-send-account-invite', function () {
        const $btn = $(this), url = $btn.data('url'), name = $btn.data('name');

        showAlertActionConfirm({
            title: 'Send account invite?',
            message: 'Invite ' + name + ' to create a Wakanda Jobs account?',
            confirmText: 'Send invite',
            confirmClass: 'btn-secondary',
            iconWrapClass: 'bg-secondary bg-opacity-10',
            iconClass: 'ti ti-mail text-secondary fs-3',
            onConfirm: function () {
                setActionButtonLoading($btn);
                $httpClient.make().post(url)
                    .then(({ data: resp }) => Botble.showSuccess(resp.message || 'Account invite sent to ' + name + '.'))
                    .catch(({ response }) => Botble.showError(response?.data?.error || 'Failed to send account invite.'))
                    .finally(() => restoreActionButton($btn));
            }
        });
    });

    // Delete alert
    $(document).on('click', '.btn-delete-alert-modal', function () {
        const $btn = $(this), url = $btn.data('url'), name = $btn.data('name');

        showAlertActionConfirm({
            title: 'Delete this alert?',
            message: 'Delete alert for ' + name + '? This will also delete all send logs.',
            confirmText: 'Delete',
            confirmClass: 'btn-danger',
            iconWrapClass: 'bg-danger bg-opacity-10',
            iconClass: 'ti ti-trash text-danger fs-3',
            onConfirm: function () {
                setActionButtonLoading($btn);
                $httpClient.make().delete(url)
                    .then(() => {
                        Botble.showSuccess('Alert deleted.');
                        const tableSelector = '#botble-job-board-tables-candidate-alert-table';
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable(tableSelector)) {
                            $(tableSelector).DataTable().ajax.reload(null, false);
                        } else {
                            $btn.closest('tr').fadeOut(200, function () {
                                $(this).remove();
                            });
                        }
                    })
                    .catch(({ response }) => {
                        Botble.showError(response?.data?.error || 'Could not delete alert.');
                        restoreActionButton($btn);
                    });
            }
        });
    });

    // ── Export CSV ────────────────────────────────────────────────────────────
    $(document).on('click', '#btnExportCsv', function () {
        const filtered = getFilteredJobs();
        if (!filtered.length) { Botble.showError('No jobs to export.'); return; }
        const headers = ['#','Job Title','Company','City','Province/State','Country','Posted','Closing Date','Days Left','Status'];
        const rows = filtered.map((job, idx) => [idx+1, job.name, job.company, job.city||'', job.state||'', job.country||'', job.created, job.deadline||'', job.deadline_days !== null && job.deadline_days !== undefined ? (job.deadline_days<0?'Expired':job.deadline_days+'d') : '', job.already_sent?'Sent':'New']);
        const csv = [headers,...rows].map(r => r.map(c => '"'+String(c||'').replace(/"/g,'""')+'"').join(',')).join('\n');
        const blob = new Blob(['﻿'+csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob), a = document.createElement('a');
        a.href = url; a.download = 'vip-alert-jobs-'+new Date().toISOString().slice(0,10)+'.csv';
        document.body.appendChild(a); a.click(); setTimeout(() => { document.body.removeChild(a); URL.revokeObjectURL(url); }, 100);
        Botble.showSuccess('Exported ' + filtered.length + ' job(s) to CSV.');
    });

    // ── Export PDF ────────────────────────────────────────────────────────────
    $(document).on('click', '#btnExportPdf', function () {
        const filtered = getFilteredJobs();
        if (!filtered.length) { Botble.showError('No jobs to export.'); return; }
        const title = $('#previewModalTitle').text() || 'VIP Alert — Matching Jobs', date = new Date().toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
        const rows = filtered.map((job, idx) => {
            const d = job.deadline_days, dLabel = d===null||d===undefined?'—':d<0?'<span style="color:#dc3545;font-weight:600">Expired</span>':d===0?'<span style="color:#dc3545;font-weight:600">Today</span>':d<=3?`<span style="color:#fd7e14;font-weight:600">${d}d left</span>`:d<=14?`<span style="color:#0dcaf0;font-weight:600">${d}d left</span>`:`${d}d`;
            const logo = job.company_logo ? `<img src="${escHtml(job.company_logo)}" style="width:22px;height:22px;object-fit:contain;vertical-align:middle;margin-right:5px;border:1px solid #eee;border-radius:3px">` : '';
            const sentBadge = job.already_sent ? '<span style="background:#198754;color:#fff;padding:1px 6px;border-radius:3px;font-size:11px">✓ Sent</span>' : '<span style="background:#6c757d;color:#fff;padding:1px 6px;border-radius:3px;font-size:11px">New</span>';
            return `<tr><td style="text-align:center;color:#999;font-size:10px">${idx+1}</td><td style="max-width:220px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${escHtml(job.name)}</td><td>${logo}${escHtml(job.company)}</td><td>${escHtml(job.country||'')}</td><td>${escHtml(job.created)}</td><td>${dLabel}</td><td>${sentBadge}</td></tr>`;
        }).join('');
        const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${escHtml(title)}</title><style>body{font-family:Arial,sans-serif;font-size:11px;color:#333;margin:20px}h2{font-size:15px;margin-bottom:4px}p{color:#666;font-size:10px;margin-bottom:12px}table{width:100%;border-collapse:collapse}th{background:#f8f9fa;border:1px solid #dee2e6;padding:5px 7px;text-align:left;font-size:10px}td{border:1px solid #dee2e6;padding:5px 7px;vertical-align:middle;font-size:11px}tr:nth-child(even) td{background:#f8f9fa}</style></head><body><h2>${escHtml(title)}</h2><p>Generated: ${date} · ${filtered.length} job(s)</p><table><thead><tr><th>#</th><th>Job Title</th><th>Company</th><th>Country</th><th>Posted</th><th>Closes</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table></body></html>`;
        const w = window.open('','_blank','width=900,height=700');
        w.document.write(html); w.document.close(); w.focus();
        setTimeout(() => w.print(), 400);
    });

    function escHtml(str) {
        return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Filter tab helpers ────────────────────────────────────────────────────
    $(document).on('click', '.btn-select-all-check', function () {
        const $box = $('#' + $(this).data('target')), badge = $(this).data('count-badge');
        $box.find('input[type="checkbox"]:not([disabled])').prop('checked', true);
        updateCountBadge(badge, $box);
    });
    $(document).on('click', '.btn-deselect-all-check', function () {
        const $box = $('#' + $(this).data('target')), badge = $(this).data('count-badge');
        $box.find('input[type="checkbox"]').prop('checked', false);
        updateCountBadge(badge, $box);
    });
    $(document).on('change', '.border.rounded input[type="checkbox"]', function () {
        const $box = $(this).closest('.border.rounded'), id = $box.attr('id');
        if (!id) return;
        $('[data-target="' + id + '"]').each(function () { const badge = $(this).data('count-badge'); if (badge) updateCountBadge(badge, $box); });
    });
    function updateCountBadge(badgeClass, $box) {
        const count = $box.find('input[type="checkbox"]:checked').length, total = $box.find('input[type="checkbox"]').length;
        $('.' + badgeClass).text(count + (total > 0 ? ' selected' : ''));
        $('[data-target="' + $box.attr('id') + '"].btn-deselect-all-check').filter('.btn-outline-danger').prop('disabled', count === 0);
    }
    $(document).on('input', '.filter-search', function () {
        const needle = $(this).val().toLowerCase(), $box = $('#' + $(this).data('target'));
        $box.find('.checkable-item').each(function () { $(this).toggle($(this).text().toLowerCase().includes(needle)); });
    });

    // Clear experience level
    $(document).on('click', '.btn-clear-experience', function () {
        $('#' + $(this).data('target')).val('');
    });

    // Clear all filters in the Filters tab
    $(document).on('click', '.btn-clear-all-filters', function () {
        const tid = $(this).data('tid');

        const $kwList = $('#keywords-list-' + tid);
        $kwList.find('.keyword-row').slice(1).remove();
        $kwList.find('input[name="filters[keywords][]"]').val('');
        $('.kw-count-badge-' + tid).text('0');

        const $coList = $('#company-list-' + tid);
        $coList.find('.company-kw-row').slice(1).remove();
        $coList.find('input[name="filters[company_keywords][]"]').val('');
        $('.co-count-badge-' + tid).text('0');

        $('#countries-box-' + tid + ' input[type="checkbox"]').prop('checked', false);
        $('.country-count-badge-' + tid).text('0 selected');

        $('#jobtypes-box-' + tid + ' input[type="checkbox"]').prop('checked', false);
        $('.jt-count-badge-' + tid).text('0 selected');

        $('#categories-box-' + tid + ' input[type="checkbox"]').prop('checked', false);
        $('.cat-count-badge-' + tid).text('0 selected');

        $('[data-target="jobtypes-box-' + tid + '"].btn-deselect-all-check.btn-outline-danger, [data-target="categories-box-' + tid + '"].btn-deselect-all-check.btn-outline-danger').prop('disabled', true);

        $('#tab-filters-' + tid + ' input[name="filters[location_keyword]"]').val('');
        $('#' + tid + '-job-experience-id').val('');

        Botble.showSuccess('All filters cleared.');
    });

    // ── Keyword rows ──────────────────────────────────────────────────────────
    $(document).on('click', '.btn-add-kw', function () {
        const listId = $(this).data('target'), badgeClass = $(this).data('count-badge');
        const $row = $('<div class="input-group input-group-sm mb-1 keyword-row"><input type="text" name="filters[keywords][]" class="form-control" placeholder="e.g. Accountant"><button type="button" class="btn btn-outline-danger btn-remove-kw" title="Remove"><i class="fas fa-times"></i></button></div>');
        $('#' + listId).append($row); $row.find('input').focus(); updateKwBadge(badgeClass, listId);
    });
    $(document).on('click', '.btn-remove-kw', function () {
        const $list = $(this).closest('[id^="keywords-list-"]'), listId = $list.attr('id');
        if ($list.find('.keyword-row').length > 1) $(this).closest('.keyword-row').remove();
        else $(this).closest('.keyword-row').find('input').val('');
        const $btn = $('[data-target="' + listId + '"].btn-add-kw');
        updateKwBadge($btn.data('count-badge'), listId);
    });
    $(document).on('input', '[name="filters[keywords][]"]', function () {
        const $list = $(this).closest('[id^="keywords-list-"]'), listId = $list.attr('id');
        updateKwBadge($('[data-target="' + listId + '"].btn-add-kw').data('count-badge'), listId);
    });
    function updateKwBadge(badgeClass, listId) {
        if (!badgeClass) return;
        $('.' + badgeClass).text($('#' + listId).find('input').filter(function () { return $(this).val().trim() !== ''; }).length);
    }

    // ── Company keyword rows ──────────────────────────────────────────────────
    $(document).on('click', '.btn-add-company-kw', function () {
        const listId = $(this).data('target'), badgeClass = $(this).data('count-badge');
        const $row = $('<div class="input-group input-group-sm mb-1 company-kw-row"><input type="text" name="filters[company_keywords][]" class="form-control" placeholder="e.g. Zambia National Commercial Bank"><button type="button" class="btn btn-outline-danger btn-remove-company-kw" title="Remove"><i class="fas fa-times"></i></button></div>');
        $('#' + listId).append($row); $row.find('input').focus(); updateCoKwBadge(badgeClass, listId);
    });
    $(document).on('click', '.btn-remove-company-kw', function () {
        const $list = $(this).closest('[id^="company-list-"]'), listId = $list.attr('id');
        if ($list.find('.company-kw-row').length > 1) $(this).closest('.company-kw-row').remove();
        else $(this).closest('.company-kw-row').find('input').val('');
        updateCoKwBadge($('[data-target="' + listId + '"].btn-add-company-kw').data('count-badge'), listId);
    });
    $(document).on('input', '[name="filters[company_keywords][]"]', function () {
        const $list = $(this).closest('[id^="company-list-"]'), listId = $list.attr('id');
        updateCoKwBadge($('[data-target="' + listId + '"].btn-add-company-kw').data('count-badge'), listId);
    });
    function updateCoKwBadge(badgeClass, listId) {
        if (!badgeClass) return;
        $('.' + badgeClass).text($('#' + listId).find('input').filter(function () { return $(this).val().trim() !== ''; }).length);
    }

    // Collapse chevron
    $(document).on('click', '.collapse-toggle-btn', function () {
        const expanded = $(this).attr('aria-expanded') === 'true';
        $(this).find('.collapse-chevron').css('transform', expanded ? '' : 'rotate(180deg)');
    });

    // Candidate account search / link
    const candidateAccountSearchState = {};
    function ensureCandidateAccountState(prefix) {
        if (!candidateAccountSearchState[prefix]) candidateAccountSearchState[prefix] = { page: 1, timer: null, hasMore: false };
        return candidateAccountSearchState[prefix];
    }
    function renderLinkedAccountCvPrompt(prefix, account) {
        const $prompt = $('#candidate-account-cv-prompt-' + prefix);
        if (account.has_cv) {
            $prompt.html('<div class="alert alert-success py-2 px-3 mb-0 small"><div class="d-flex align-items-center justify-content-between gap-2 flex-wrap"><div><strong>Account CV found:</strong> ' + escHtml(account.resume_name || 'CV on file') + '<div class="text-muted mt-1">Use the linked account CV to auto-generate keywords and matching filters.</div></div><button type="button" class="btn btn-success btn-sm btn-analyze-linked-account-cv" data-prefix="' + prefix + '"><i class="ti ti-sparkles me-1"></i> Analyse Account CV</button></div></div>');
        } else {
            $prompt.html('<div class="alert alert-warning py-2 px-3 mb-0 small"><strong>No CV on this account.</strong> Upload a CV below to generate the best keywords and matching filters.</div>');
        }
    }
    function renderSelectedAccount(prefix, account) {
        const phone = account.phone || account.whatsapp_number || '';
        const avatar = account.avatar_url || '';
        $('#candidate-account-selected-' + prefix).removeClass('d-none').attr('data-account-id', account.id || '').attr('data-has-cv', account.has_cv ? '1' : '0').html('<div class="border rounded px-3 py-2 bg-white"><div class="d-flex align-items-start justify-content-between gap-2 flex-wrap"><div class="d-flex align-items-center gap-2">' + (avatar ? '<img src="' + escHtml(avatar) + '" alt="" style="width:40px;height:40px;border-radius:999px;object-fit:cover;border:1px solid #e5e7eb">' : '') + '<div><div class="fw-semibold">' + escHtml(account.name || '') + '</div><div class="text-muted small">' + (account.email ? '<span class="me-2"><i class="ti ti-mail me-1"></i>' + escHtml(account.email) + '</span>' : '') + (phone ? '<span><i class="ti ti-brand-whatsapp me-1"></i>' + escHtml(phone) + '</span>' : '') + '</div></div></div><button type="button" class="btn btn-outline-danger btn-sm btn-clear-linked-account" data-prefix="' + prefix + '"><i class="ti ti-x me-1"></i> Clear</button></div></div>');
        $('input[name="linked_account_id"]').filter(function () { return $(this).closest('.modal-content, form').find('#candidate-account-selected-' + prefix).length > 0; }).val(account.id || '');
        renderLinkedAccountCvPrompt(prefix, account);
    }
    function clearLinkedAccount(prefix) {
        $('#candidate-account-selected-' + prefix).addClass('d-none').attr('data-account-id', '').empty();
        $('#candidate-account-cv-prompt-' + prefix).empty();
        $('input[name="linked_account_id"]').filter(function () { return $(this).closest('.modal-content, form').find('#candidate-account-selected-' + prefix).length > 0; }).val('');
    }
    function runCandidateAccountSearch(prefix, page) {
        const $input = $('#candidate-account-search-' + prefix), url = $input.data('search-url'), term = $input.val().trim(), state = ensureCandidateAccountState(prefix), $results = $('#candidate-account-results-' + prefix);
        state.page = page;
        if (term.length < 2) { $results.addClass('d-none').empty(); return; }
        $results.removeClass('d-none').html('<div class="border rounded p-3 bg-white text-muted small"><i class="ti ti-loader-2 fa-spin me-1"></i> Searching accounts…</div>');
        fetch(url + '?q=' + encodeURIComponent(term) + '&page=' + page)
            .then(r => r.json())
            .then(resp => {
                const rows = resp.data || []; state.hasMore = !!resp.has_more;
                if (!rows.length) { $results.html('<div class="border rounded p-3 bg-white text-muted small">No matching candidate accounts found.</div>'); return; }
                let html = '<div class="border rounded bg-white overflow-hidden"><div class="list-group list-group-flush">';
                rows.forEach(account => {
                    const phone = account.phone || account.whatsapp_number || '';
                    const avatar = account.avatar_url || '';
                    html += '<button type="button" class="list-group-item list-group-item-action btn-select-candidate-account" data-prefix="' + prefix + '" data-account-id="' + escHtml(account.id) + '" data-account-name="' + escHtml(account.name || '') + '" data-account-email="' + escHtml(account.email || '') + '" data-account-phone="' + escHtml(phone) + '" data-has-cv="' + (account.has_cv ? '1' : '0') + '" data-resume-name="' + escHtml(account.resume_name || '') + '" data-avatar-url="' + escHtml(avatar) + '"><div class="d-flex align-items-start justify-content-between gap-2"><div class="d-flex align-items-center gap-2">' + (avatar ? '<img src="' + escHtml(avatar) + '" alt="" style="width:24px;height:24px;border-radius:999px;object-fit:cover;border:1px solid #e5e7eb">' : '') + '<div><div class="fw-semibold">' + escHtml(account.name || '') + '</div><div class="text-muted small">' + (account.email ? '<span class="me-2"><i class="ti ti-mail me-1"></i>' + escHtml(account.email) + '</span>' : '') + (phone ? '<span><i class="ti ti-brand-whatsapp me-1"></i>' + escHtml(phone) + '</span>' : '') + '</div></div></div><span class="badge ' + (account.has_cv ? 'bg-success' : 'bg-warning text-dark') + '">' + (account.has_cv ? 'Has CV' : 'No CV') + '</span></div></button>';
                });
                html += '</div><div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light"><button type="button" class="btn btn-outline-secondary btn-sm btn-candidate-account-page" data-prefix="' + prefix + '" data-page="' + Math.max(1, page - 1) + '"' + (page <= 1 ? ' disabled' : '') + '>Prev</button><span class="text-muted small">Page ' + page + '</span><button type="button" class="btn btn-outline-secondary btn-sm btn-candidate-account-page" data-prefix="' + prefix + '" data-page="' + (page + 1) + '"' + (state.hasMore ? '' : ' disabled') + '>Next</button></div></div>';
                $results.html(html);
            })
            .catch(() => $results.html('<div class="border rounded p-3 bg-white text-danger small">Account search failed. Try again.</div>'));
    }
    $(document).on('input', '.candidate-account-search-input', function () {
        const prefix = $(this).data('prefix'), state = ensureCandidateAccountState(prefix);
        clearTimeout(state.timer);
        state.timer = setTimeout(() => runCandidateAccountSearch(prefix, 1), 250);
    });
    $(document).on('click', '.btn-candidate-account-page', function () {
        if ($(this).is(':disabled')) return;
        runCandidateAccountSearch($(this).data('prefix'), parseInt($(this).data('page'), 10) || 1);
    });
    $(document).on('click', '.btn-select-candidate-account', function () {
        const prefix = $(this).data('prefix');
        const account = { id: $(this).data('account-id'), name: $(this).data('account-name'), email: $(this).data('account-email'), phone: $(this).data('account-phone'), has_cv: String($(this).data('has-cv')) === '1', resume_name: $(this).data('resume-name'), avatar_url: $(this).data('avatar-url') };
        const $scope = $(this).closest('.modal-content, form');
        $scope.find('input[name="candidate_name"]').val(account.name || '');
        $scope.find('input[name="candidate_phone"]').val(account.phone || '');
        $scope.find('input[name="candidate_email"]').val(account.email || '');
        renderSelectedAccount(prefix, account);
        $('#candidate-account-results-' + prefix).addClass('d-none').empty();
        Botble.showSuccess(account.has_cv ? 'Candidate account linked. Analyse the account CV to generate keywords.' : 'Candidate account linked. This account has no CV yet, so upload one below for accurate keyword matching.');
    });
    $(document).on('click', '.btn-clear-linked-account', function () { clearLinkedAccount($(this).data('prefix')); });

    // ── CV Upload & AI Analysis ───────────────────────────────────────────────
    $(document).on('change', '.cv-upload-input', function () {
        $('[data-prefix="' + $(this).data('prefix') + '"].btn-analyze-cv').prop('disabled', !(this.files && this.files.length > 0));
    });
    $(document).on('click', '.btn-analyze-cv', function () {
        const $btn = $(this), prefix = $btn.data('prefix'), $fileInput = $('#' + prefix + '-cv-file'), analyzeUrl = $fileInput.data('analyze-url');
        if (!$fileInput[0].files || !$fileInput[0].files.length) { Botble.showError('Please select a CV file first.'); return; }
        $btn.prop('disabled', true).html('<i class="ti ti-loader-2 fa-spin me-1"></i> Analysing…');
        const formData = new FormData();
        formData.append('cv_file', $fileInput[0].files[0]);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        fetch(analyzeUrl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(resp => { if (resp.error) { Botble.showError(resp.error); return; } applyAnalysisToForm(prefix, resp.data, true); })
            .catch(() => Botble.showError('CV analysis failed.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Analyse with AI'));
    });
    $(document).on('click', '.btn-analyze-linked-account-cv', function () {
        const $btn = $(this), prefix = $btn.data('prefix'), accountId = parseInt($('#candidate-account-selected-' + prefix).attr('data-account-id'), 10) || 0, analyzeUrl = $('#candidate-account-search-' + prefix).data('analyze-account-cv-url');
        if (!accountId) { Botble.showError('Select an account first.'); return; }
        $btn.prop('disabled', true).html('<i class="ti ti-loader-2 fa-spin me-1"></i> Analysing…');
        fetch(analyzeUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }, body: JSON.stringify({ account_id: accountId }) })
            .then(r => r.json())
            .then(resp => { if (resp.error) { Botble.showError(resp.error); return; } applyAnalysisToForm(prefix, resp.data, true); Botble.showSuccess('Linked account CV analysed and filters applied.'); })
            .catch(() => Botble.showError('Linked account CV analysis failed.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="ti ti-sparkles me-1"></i> Analyse Account CV'));
    });
    $(document).on('click', '.btn-apply-analysis', function () {
        const prefix = $(this).data('prefix'), analysis = $(this).data('analysis');
        if (analysis) applyAnalysisToForm(prefix, analysis, false);
    });
    function applyAnalysisToForm(prefix, data, showPanel) {
        const $scope = $('[data-prefix="' + prefix + '"]').closest('.modal-content, form');
        const keywords = Array.isArray(data.keywords) && data.keywords.length ? data.keywords : (data.keyword ? [data.keyword] : []);

        if (data.candidate_name) $scope.find('input[name="candidate_name"]').val(data.candidate_name);
        if (data.candidate_phone) $scope.find('input[name="candidate_phone"]').val(data.candidate_phone);
        if (data.candidate_email) $scope.find('input[name="candidate_email"]').val(data.candidate_email);

        const $keywordsList = $('#keywords-list-' + prefix);
        if ($keywordsList.length) {
            $keywordsList.find('input[name="filters[keywords][]"]').val('');
            keywords.forEach(function (keyword, index) {
                let $input = $keywordsList.find('input[name="filters[keywords][]"]').eq(index);
                if (! $input.length) {
                    $keywordsList.append('<div class="input-group input-group-sm mb-1 keyword-row"><input type="text" name="filters[keywords][]" class="form-control" placeholder="e.g. Software Engineer"><button type="button" class="btn btn-outline-danger btn-remove-kw" title="Remove"><i class="fas fa-times"></i></button></div>');
                    $input = $keywordsList.find('input[name="filters[keywords][]"]').eq(index);
                }
                $input.val(keyword);
            });
            $('.kw-count-badge-' + prefix).text(keywords.length);
        }

        // Job types, categories, experience level, and city/location are intentionally
        // left alone — the admin picks those manually. The AI's suggestions for them are
        // still shown as read-only badges in the result panel below.
        $('#countries-box-' + prefix + ' input[type="checkbox"]').prop('checked', false);
        (data.country_ids || []).forEach(id => $('#' + prefix + '-country-' + id + ', #add-country-' + id + ', #edit-country-' + id).prop('checked', true));
        $('.country-count-badge-' + prefix).text((data.country_ids || []).length + ' selected');

        $scope.find('input[name="cv_analysis_payload"]').val(JSON.stringify(data));

        if (showPanel) {
            const $panel = $('#' + prefix + '-analysis-result'), confidence = data.confidence || 0, cb = confidence >= 80 ? 'bg-success-subtle text-success' : confidence >= 60 ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary';
            let html = `<div class="d-flex align-items-center gap-2 mb-2"><i class="ti ti-file-text text-primary"></i><strong class="small">AI Analysis Result</strong><span class="badge ${cb} ms-auto">${confidence}% confidence</span></div>`;
            if (data.candidate_type) html += `<div class="small text-dark fw-semibold mb-2">${escHtml(data.candidate_type)}</div>`;
            if (data.candidate_name || data.candidate_phone || data.candidate_email) {
                html += '<div class="text-muted small mb-2">';
                if (data.candidate_name) html += `<span class="me-2"><i class="ti ti-user me-1"></i>${escHtml(data.candidate_name)}</span>`;
                if (data.candidate_phone) html += `<span class="me-2"><i class="ti ti-brand-whatsapp me-1"></i>${escHtml(data.candidate_phone)}</span>`;
                if (data.candidate_email) html += `<span><i class="ti ti-mail me-1"></i>${escHtml(data.candidate_email)}</span>`;
                html += '</div>';
            }
            if (data.summary) html += `<p class="text-muted small mb-2">${escHtml(data.summary)}</p>`;
            html += '<div class="d-flex gap-1 flex-wrap mb-2">';
            keywords.forEach(n => { html += `<span class="badge bg-dark text-white small"><i class="ti ti-search me-1"></i>${escHtml(n)}</span>`; });
            (data.job_type_names||[]).forEach(n => { html += `<span class="badge bg-primary text-white small">${escHtml(n)}</span>`; });
            (data.category_names||[]).forEach(n => { html += `<span class="badge bg-secondary text-white small">${escHtml(n)}</span>`; });
            (data.country_names||[]).forEach(n => { html += `<span class="badge bg-info text-white small">${escHtml(n)}</span>`; });
            if (data.location_keyword) html += `<span class="badge bg-light border text-dark small"><i class="ti ti-map-pin me-1"></i>${escHtml(data.location_keyword)}</span>`;
            html += '</div><div class="text-success small mt-2"><i class="ti ti-check me-1"></i>Filters applied. Review and adjust as needed.</div>';
            if (data.usage && (data.usage.total_tokens || data.usage.estimated_cost_usd)) {
                const cost = data.usage.estimated_cost_usd ? Number(data.usage.estimated_cost_usd).toFixed(6) : null;
                html = html.replace('</div><div class="text-success small mt-2">', `</div><div class="text-muted small mb-2">Usage: ${escHtml(String(data.usage.total_tokens || 0))} tokens${cost ? ' · $' + escHtml(cost) : ''}</div><div class="text-success small mt-2">`);
            }
            $panel.html(html).removeClass('d-none');
        }
        Botble.showSuccess('AI analysis complete. Filters applied — review and adjust as needed.');
    }
});
</script>
@endpush
