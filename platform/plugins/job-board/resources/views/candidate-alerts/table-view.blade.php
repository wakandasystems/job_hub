@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
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

{{-- The DataTable --}}
@include('core/table::base-table')

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

@endsection

@push('footer')
<script>
$(function () {

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

    // ── Preview & Send modal ──────────────────────────────────────────────────
    let previewAllJobs = [], previewPage = 1;
    const PREVIEW_PER_PAGE = 25;

    $(document).on('click', '.btn-preview-jobs', function () {
        const $btn = $(this), url = $btn.data('url'), sendUrl = $btn.data('send-url'), name = $btn.data('name');
        $('#previewModalTitle').text('Matching Jobs — ' + name);
        $('#previewContent').html('<div class="p-3 text-center text-muted"><i class="ti ti-loader-2 fa-spin fs-3 d-block mb-2"></i> Searching matching jobs…</div>');
        $('#btnSendNow').data('send-url', sendUrl);
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
        let html = '<table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th style="width:36px">#</th><th>Job Title</th><th>Company</th><th>Address</th><th>Country</th><th>Posted</th><th>Closes</th><th class="text-center">Sent?</th></tr></thead><tbody>';
        if (!slice.length) { html += '<tr><td colspan="8" class="text-center text-muted py-4">No jobs match your filters.</td></tr>'; }
        else { slice.forEach((job, idx) => {
            const rowNum = start + idx + 1, sentBadge = job.already_sent ? '<span class="badge bg-success text-white">✓ Sent</span>' : '<span class="badge bg-secondary text-white">New</span>';
            let deadlineBadge = '<span class="text-muted small">—</span>';
            if (job.deadline_days !== null && job.deadline_days !== undefined) {
                const d = job.deadline_days;
                deadlineBadge = d < 0 ? '<span class="badge bg-danger text-white">Expired</span>' : d === 0 ? '<span class="badge bg-danger text-white">Today</span>' : d <= 3 ? `<span class="badge bg-warning text-dark" title="${escHtml(job.deadline)}">${d}d left</span>` : d <= 14 ? `<span class="badge bg-info text-white" title="${escHtml(job.deadline)}">${d}d left</span>` : `<span class="text-muted small text-nowrap" title="${escHtml(job.deadline)}">${d}d</span>`;
            }
            html += `<tr data-job-id="${job.id}"><td class="text-muted small text-center">${rowNum}</td><td class="fw-semibold" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${escHtml(job.name)}">${escHtml(job.name)}</td><td class="text-muted small"><div class="d-flex align-items-center gap-2">${job.company_logo ? `<img src="${escHtml(job.company_logo)}" alt="" style="width:28px;height:28px;object-fit:contain;border-radius:4px;border:1px solid #eee;background:#fff;padding:2px">` : ''}<span>${escHtml(job.company)}</span></div></td><td class="text-muted small">${escHtml(job.location || '')}</td><td class="text-muted small">${escHtml(job.country || '')}</td><td class="text-muted small text-nowrap">${escHtml(job.created)}</td><td class="text-nowrap">${deadlineBadge}</td><td>${sentBadge}</td></tr>`;
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

    // Send Now
    $('#btnSendNow').on('click', async function () {
        const $btn = $(this), sendUrl = $btn.data('send-url'), forceResend = $('#forceResendCheck').is(':checked'), BATCH = 3, BATCH_GAP = 400;
        const jobsToSend = forceResend ? [...previewAllJobs] : previewAllJobs.filter(j => !j.already_sent);
        if (!jobsToSend.length) { Botble.showError('No new jobs to send.'); return; }
        const total = jobsToSend.length; let done = 0, sent = 0, failed = 0;
        $btn.prop('disabled', true).html('<i class="fab fa-whatsapp me-1"></i> Sending…');
        $('#btnExportCsv, #btnExportPdf, #forceResendCheck, #pv-country, #pv-company, #pv-period, #pv-clear-filters').prop('disabled', true);
        $('#pv-send-progress').html(`<div class="px-3 pt-3 pb-2"><div class="d-flex align-items-center justify-content-between mb-1"><span class="small fw-semibold" id="pv-prog-label">Sending 0 of ${total}…</span><span class="small text-muted" id="pv-prog-counts">0 sent · 0 failed</span></div><div class="progress mb-1" style="height:8px"><div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="pv-prog-bar" role="progressbar" style="width:0%;transition:width .4s ease"></div></div><div class="text-muted small text-truncate" id="pv-prog-current" style="min-height:1.3em"></div></div>`).show();
        const sendOne = async (job) => {
            $('#pv-prog-current').html(`<i class="fas fa-paper-plane me-1 text-success"></i>${escHtml(job.name)}`);
            try {
                await $httpClient.make().post(sendUrl, { force_resend: forceResend ? 1 : 0, job_ids: [job.id] }, { timeout: 30000 });
                sent++; job.already_sent = true;
                const $row = $(`tr[data-job-id="${job.id}"]`);
                if ($row.length) { $row.css({ transition: 'opacity .3s ease, transform .3s ease', opacity: 0, transform: 'translateX(40px)' }); setTimeout(() => $row.remove(), 320); }
            } catch (_) { failed++; }
            done++; const pct = Math.round((done/total)*100);
            $('#pv-prog-bar').css('width', pct + '%');
            $('#pv-prog-label').text(`Sending ${Math.min(done+BATCH,total)} of ${total}…`);
            $('#pv-prog-counts').text(`${sent} sent · ${failed} failed`);
        };
        for (let i = 0; i < jobsToSend.length; i += BATCH) {
            await Promise.all(jobsToSend.slice(i, i+BATCH).map(sendOne));
            if (i+BATCH < jobsToSend.length) await new Promise(r => setTimeout(r, BATCH_GAP));
        }
        $('#pv-prog-label').text(`Done — ${sent} sent${failed ? `, ${failed} failed` : ''}.`);
        $('#pv-prog-current').html(''); $('#pv-prog-bar').removeClass('progress-bar-animated progress-bar-striped').css('width','100%');
        failed ? Botble.showError(`${sent} sent, ${failed} failed.`) : Botble.showSuccess(`${sent} job(s) sent successfully.`);
        $btn.prop('disabled', false).html('<i class="fab fa-whatsapp me-1"></i> Send All Matching');
        $('#btnExportCsv, #btnExportPdf, #forceResendCheck, #pv-country, #pv-company, #pv-period, #pv-clear-filters').prop('disabled', false);
        setTimeout(() => location.reload(), 1500);
    });

    // Send welcome
    $(document).on('click', '.btn-send-welcome', function () {
        const $btn = $(this), url = $btn.data('url'), name = $btn.data('name');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $httpClient.make().post(url)
            .then(({ data: resp }) => Botble.showSuccess(resp.message || 'Welcome message sent to ' + name + '.'))
            .catch(({ response }) => Botble.showError(response?.data?.error || 'Failed to send.'))
            .finally(() => $btn.prop('disabled', false).html('<svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9"></path></svg>'));
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
        $('[data-target="' + $box.attr('id') + '"].btn-deselect-all-check').filter('.btn-outline-danger').each(function() { count > 0 ? $(this).show() : $(this).hide(); });
    }
    $(document).on('input', '.filter-search', function () {
        const needle = $(this).val().toLowerCase(), $box = $('#' + $(this).data('target'));
        $box.find('.checkable-item').each(function () { $(this).toggle($(this).text().toLowerCase().includes(needle)); });
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
    $(document).on('click', '.btn-apply-analysis', function () {
        const prefix = $(this).data('prefix'), analysis = $(this).data('analysis');
        if (analysis) applyAnalysisToForm(prefix, analysis, false);
    });
    function applyAnalysisToForm(prefix, data, showPanel) {
        if (data.keyword) { const $kw = $('[data-prefix="' + prefix + '"]').closest('.modal-content, form').find('input[name="filters[keyword]"]'); ($kw.length ? $kw : $('input[name="filters[keyword]"]').first()).val(data.keyword); }
        if (data.job_type_ids && data.job_type_ids.length) data.job_type_ids.forEach(id => $('#' + prefix + '-type-' + id).prop('checked', true));
        if (data.category_ids && data.category_ids.length) data.category_ids.forEach(id => $('#' + prefix + '-cat-' + id).prop('checked', true));
        if (data.job_experience_id) $('[data-prefix="' + prefix + '"]').closest('.modal-content, form').find('select[name="filters[job_experience_id]"]').val(data.job_experience_id);
        if (showPanel) {
            const $panel = $('#' + prefix + '-analysis-result'), confidence = data.confidence || 0, cb = confidence >= 80 ? 'bg-success-subtle text-success' : confidence >= 60 ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary';
            let html = `<div class="d-flex align-items-center gap-2 mb-2"><i class="ti ti-file-text text-primary"></i><strong class="small">AI Analysis Result</strong><span class="badge ${cb} ms-auto">${confidence}% confidence</span></div>`;
            if (data.summary) html += `<p class="text-muted small mb-2">${escHtml(data.summary)}</p>`;
            html += '<div class="d-flex gap-1 flex-wrap">';
            if (data.keyword) html += `<span class="badge bg-light border text-dark small"><i class="ti ti-search me-1"></i>${escHtml(data.keyword)}</span>`;
            (data.job_type_names||[]).forEach(n => { html += `<span class="badge bg-primary text-white small">${escHtml(n)}</span>`; });
            (data.category_names||[]).forEach(n => { html += `<span class="badge bg-secondary text-white small">${escHtml(n)}</span>`; });
            html += '</div><div class="text-success small mt-2"><i class="ti ti-check me-1"></i>Filters applied. Review and adjust as needed.</div>';
            $panel.html(html).removeClass('d-none');
        }
        Botble.showSuccess('AI analysis complete. Filters applied — review and adjust as needed.');
    }
});
</script>
@endpush
