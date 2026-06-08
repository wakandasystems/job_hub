@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')

{{-- ── Subscriber Stats ── --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm text-center py-3">
            <div class="card-body py-2">
                <div class="fs-1 fw-bold text-primary">{{ number_format($stats['today']) }}</div>
                <div class="text-muted small mt-1">Joined Today</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm text-center py-3">
            <div class="card-body py-2">
                <div class="fs-1 fw-bold text-success">{{ number_format($stats['this_week']) }}</div>
                <div class="text-muted small mt-1">Joined This Week</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm text-center py-3">
            <div class="card-body py-2">
                <div class="fs-1 fw-bold text-warning">{{ number_format($stats['this_month']) }}</div>
                <div class="text-muted small mt-1">Joined in {{ now()->format('F') }}</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm text-center py-3">
            <div class="card-body py-2">
                <div class="fs-1 fw-bold text-secondary">{{ number_format($stats['all_time']) }}</div>
                <div class="text-muted small mt-1">All-Time Subscribers</div>
            </div>
        </div>
    </div>

</div>

{{-- ── Tabs ── --}}
<ul class="nav nav-tabs mb-0" id="nlTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-subscribers-btn" data-bs-toggle="tab" data-bs-target="#tab-subscribers" type="button" role="tab">
            <i class="ti ti-users me-1"></i> Subscribers
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-sends-btn" data-bs-toggle="tab" data-bs-target="#tab-sends" type="button" role="tab">
            <i class="ti ti-send me-1"></i> Newsletters Sent
            @if($recentSends->total() > 0)
                <span class="badge bg-primary ms-1 text-white">{{ $recentSends->total() }}</span>
            @endif
        </button>
    </li>
</ul>

<div class="tab-content">

    {{-- ── Subscribers tab ── --}}
    <div class="tab-pane fade show active" id="tab-subscribers" role="tabpanel">
        @include('core/table::base-table')
    </div>

    {{-- ── Newsletters Sent tab ── --}}
    <div class="tab-pane fade" id="tab-sends" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-header d-flex align-items-center justify-content-between gap-2">
                <span class="fw-semibold">Send History</span>
                <a href="{{ route('newsletter.send') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-send me-1"></i>Send Newsletter
                </a>
            </div>

            @if($recentSends->isNotEmpty())
            <div class="card-table">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th class="text-center" style="width:110px">Status</th>
                                <th class="text-center" style="width:100px">Sent</th>
                                <th class="text-center" style="width:80px">Failed</th>
                                <th style="width:90px">Type</th>
                                <th style="width:160px">Date</th>
                                <th style="width:140px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentSends as $send)
                            @php
                                $statusCfg = match($send->status ?? 'completed') {
                                    'running'   => ['bg-primary',   'spinner', 'Sending…'],
                                    'scheduled' => ['bg-info',      'clock',   'Scheduled'],
                                    'completed' => ['bg-success',   'check',   'Completed'],
                                    'failed'    => ['bg-danger',    'x',       'Failed'],
                                    'cancelled' => ['bg-secondary', 'ban',     'Cancelled'],
                                    default     => ['bg-success',   'check',   'Completed'],
                                };
                                $isLive    = empty($send->test_to);
                                $canResend = $isLive && in_array($send->status ?? 'completed', ['completed', 'failed', 'cancelled']);
                                $canCancel = in_array($send->status ?? 'completed', ['scheduled', 'running']);
                            @endphp
                            <tr id="send-row-{{ $send->id }}"
                                @if($send->test_to) data-test-send="1" @endif
                                @if(($send->status ?? '') === 'running')
                                    data-started-at="{{ \Carbon\Carbon::parse($send->sent_at)->timestamp }}"
                                    data-total="{{ $send->recipient_count }}"
                                @endif>
                                <td class="fw-medium">{{ $send->subject }}</td>
                                <td class="text-center">
                                    @if(($send->status ?? 'completed') === 'running')
                                        <span class="badge bg-primary text-white" id="status-badge-{{ $send->id }}">
                                            <span class="spinner-border spinner-border-sm me-1" style="width:.6rem;height:.6rem"></span>
                                            Sending…
                                        </span>
                                    @elseif(($send->status ?? 'completed') === 'scheduled')
                                        <span class="badge bg-info text-white" title="{{ $send->scheduled_at ? \Carbon\Carbon::parse($send->scheduled_at)->format('d M Y, H:i') : '' }}">
                                            <i class="ti ti-clock me-1"></i>Scheduled
                                        </span>
                                    @else
                                        <span class="badge {{ $statusCfg[0] }} text-white">{{ $statusCfg[2] }}</span>
                                    @endif
                                </td>
                                <td class="text-center" style="min-width:120px">
                                    @if(($send->status ?? 'completed') === 'running')
                                        <div id="progress-cell-{{ $send->id }}">
                                            <div class="progress mb-1" style="height:4px;">
                                                <div id="prog-bar-{{ $send->id }}" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:0%"></div>
                                            </div>
                                            <div class="small fw-semibold text-success">
                                                <span id="prog-sent-{{ $send->id }}">0</span>
                                                <span class="text-muted fw-normal">/ {{ number_format($send->recipient_count) }}</span>
                                            </div>
                                            <div class="text-muted" id="prog-eta-{{ $send->id }}" style="font-size:10px;">Calculating…</div>
                                        </div>
                                    @elseif(($send->status ?? 'completed') === 'scheduled')
                                        <span class="text-muted small">—</span>
                                    @else
                                        <span class="fw-semibold text-success">{{ number_format($send->sent_count ?? $send->recipient_count) }}</span>
                                        <span class="text-muted small d-block">of {{ number_format($send->recipient_count) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(($send->failed_count ?? 0) > 0)
                                        <span class="badge bg-danger rounded-pill">{{ number_format($send->failed_count) }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($send->test_to)
                                        <span class="badge bg-warning text-white" title="{{ $send->test_to }}">Test</span>
                                    @else
                                        <span class="badge bg-success text-white">Live</span>
                                    @endif
                                    @if($send->scheduled_at && ($send->status ?? '') === 'scheduled')
                                        <div class="text-muted" style="font-size:10px;margin-top:2px">
                                            {{ \Carbon\Carbon::parse($send->scheduled_at)->format('d M, H:i') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ \Carbon\Carbon::parse($send->sent_at)->format('d M Y, H:i') }}</td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        @if(in_array($send->status ?? 'completed', ['completed', 'failed']) && empty($send->test_to))
                                            <a href="{{ route('newsletter.send.recipients', $send->id) }}"
                                               class="btn btn-outline-secondary btn-sm"
                                               title="View delivery report">
                                                <i class="ti ti-list me-1"></i>Report
                                            </a>
                                        @endif
                                        @if($canResend)
                                            <button class="btn btn-outline-warning btn-sm"
                                                    onclick="promptResend({{ $send->id }}, this)"
                                                    title="Resend to recipients who didn't receive this email">
                                                <i class="ti ti-refresh me-1"></i>Resend
                                            </button>
                                        @endif
                                        @if($canCancel)
                                            <button class="btn btn-outline-danger btn-sm"
                                                    onclick="promptCancel({{ $send->id }}, this)"
                                                    title="Cancel this send">
                                                <i class="ti ti-x me-1"></i>Cancel
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($recentSends->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center gap-2">
                <div class="text-muted small">
                    Showing {{ $recentSends->firstItem() }}–{{ $recentSends->lastItem() }} of {{ $recentSends->total() }}
                </div>
                {{ $recentSends->appends(['sends_page' => $recentSends->currentPage()])->links('pagination::bootstrap-5') }}
            </div>
            @endif
            @else
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-send fs-1 opacity-25 d-block mb-2"></i>
                No newsletters have been sent yet.
            </div>
            @endif
        </div>
    </div>

</div>

{{-- ── Confirmation modals ── --}}
<div class="modal fade" id="nlConfirmModal" tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nlConfirmTitle">Confirm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="nlConfirmMsg" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="nlConfirmOkBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

@push('footer')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
    ?? document.querySelector('input[name="_token"]')?.value
    ?? '';

let _nlConfirmCallback = null;
const nlModal = new bootstrap.Modal(document.getElementById('nlConfirmModal'));

document.getElementById('nlConfirmOkBtn').addEventListener('click', function () {
    nlModal.hide();
    if (_nlConfirmCallback) { _nlConfirmCallback(); _nlConfirmCallback = null; }
});

function showNlConfirm(title, msg, btnClass, callback) {
    document.getElementById('nlConfirmTitle').textContent = title;
    document.getElementById('nlConfirmMsg').textContent   = msg;
    const okBtn = document.getElementById('nlConfirmOkBtn');
    okBtn.className = 'btn ' + btnClass;
    _nlConfirmCallback = callback;
    nlModal.show();
}

function promptResend(sendId, btn) {
    showNlConfirm(
        'Resend Newsletter',
        'Resend this newsletter to all subscribers who did NOT receive it the first time?',
        'btn-warning',
        () => executeResend(sendId, btn)
    );
}

function promptCancel(sendId, btn) {
    showNlConfirm(
        'Cancel Send',
        'Cancel this newsletter send? Jobs already dispatched will still run — only pending jobs will stop.',
        'btn-danger',
        () => executeCancel(sendId, btn)
    );
}

async function executeResend(sendId, btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    const url = '{{ url("/admin/newsletters") }}/' + sendId + '/resend';
    try {
        const r = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const text = await r.text();
        let data;
        try { data = JSON.parse(text); } catch {
            console.error('Non-JSON resend response:', text.substring(0, 500));
            throw new Error('Server error — check browser console for details.');
        }

        if (!r.ok || data.error) throw new Error(data.error || data.message || 'Request failed');

        Botble.showSuccess('Resend queued for ' + data.total + ' recipient(s).');
        location.reload();
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-refresh me-1"></i>Resend';
        Botble.showError(err.message);
    }
}

async function executeCancel(sendId, btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    const url = '{{ url("/admin/newsletters") }}/' + sendId + '/cancel';
    try {
        const r = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const text = await r.text();
        let data;
        try { data = JSON.parse(text); } catch {
            console.error('Non-JSON cancel response:', text.substring(0, 500));
            throw new Error('Server error — check browser console for details.');
        }

        if (!r.ok || data.error) throw new Error(data.error || data.message || 'Request failed');

        location.reload();
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-x me-1"></i>Cancel';
        Botble.showError(err.message);
    }
}

// Auto-activate sends tab when paginating
if (new URLSearchParams(location.search).has('sends_page')) {
    const btn = document.getElementById('tab-sends-btn');
    if (btn) btn.click();
}

// Inline polling for running sends — no full-page reload
(function pollRunningSends() {
    const statusBase = '{{ rtrim(url("/admin/newsletters"), "/") }}/send-status/';

    function fmtEta(seconds) {
        if (!isFinite(seconds) || seconds <= 0) return '';
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        if (h > 0)      return '~' + h + 'h ' + m + 'm left';
        if (m > 0)      return '~' + m + 'm ' + s + 's left';
        return '~' + s + 's left';
    }

    document.querySelectorAll('tr[id^="send-row-"]').forEach(function (row) {
        const statusCell = row.querySelector('td:nth-child(2)');
        if (!statusCell || !statusCell.querySelector('.spinner-border')) return;

        const sendId    = row.id.replace('send-row-', '');
        const startedAt = parseInt(row.dataset.startedAt || 0, 10); // unix seconds
        const total     = parseInt(row.dataset.total     || 0, 10);

        const barEl  = document.getElementById('prog-bar-'  + sendId);
        const sentEl = document.getElementById('prog-sent-' + sendId);
        const etaEl  = document.getElementById('prog-eta-'  + sendId);

        const interval = setInterval(async function () {
            try {
                const r = await fetch(statusBase + sendId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const d = await r.json();
                if (!r.ok) return;

                const processed = d.processed ?? 0;
                const sent      = d.sent      ?? 0;
                const failed    = d.failed    ?? 0;
                const tot       = d.total     || total || 1;
                const pct       = Math.round((processed / tot) * 100);

                // Update progress bar + sent count
                if (barEl)  { barEl.style.width = pct + '%'; }
                if (sentEl) { sentEl.textContent = sent; }

                // ETA
                if (etaEl && startedAt > 0 && processed > 0) {
                    const elapsed = (Date.now() / 1000) - startedAt;
                    const rate    = processed / elapsed;           // jobs/sec
                    const etaSec  = (tot - processed) / rate;
                    etaEl.textContent = fmtEta(etaSec);
                }

                // Update Failed cell
                const failedCell = row.querySelector('td:nth-child(4)');
                if (failedCell) {
                    failedCell.innerHTML = failed > 0
                        ? '<span class="badge bg-danger rounded-pill text-white">' + failed + '</span>'
                        : '<span class="text-muted small">—</span>';
                }

                if (d.finished || ['completed', 'failed', 'cancelled'].includes(d.status)) {
                    clearInterval(interval);

                    // Swap status badge
                    const badgeMap = { completed: ['bg-success', 'Completed'], failed: ['bg-danger', 'Failed'], cancelled: ['bg-secondary', 'Cancelled'] };
                    const [cls, lbl] = badgeMap[d.status] || ['bg-success', 'Completed'];
                    statusCell.innerHTML = '<span class="badge ' + cls + ' text-white">' + lbl + '</span>';

                    // Replace progress cell with final count
                    const progCell = document.getElementById('progress-cell-' + sendId);
                    if (progCell) {
                        progCell.innerHTML =
                            '<span class="fw-semibold text-success">' + sent + '</span>' +
                            '<span class="text-muted small d-block">of ' + tot + '</span>';
                    }

                    // Add Resend button for live sends
                    const actionCell = row.querySelector('td:last-child .d-flex');
                    if (actionCell && !row.dataset.testSend && ['completed', 'failed'].includes(d.status)) {
                        const btn = document.createElement('button');
                        btn.className = 'btn btn-outline-warning btn-sm';
                        btn.title = "Resend to recipients who didn't receive this email";
                        btn.innerHTML = '<i class="ti ti-refresh me-1"></i>Resend';
                        btn.addEventListener('click', function () { promptResend(parseInt(sendId), btn); });
                        actionCell.appendChild(btn);
                    }
                }
            } catch (e) { /* network blip — keep polling */ }
        }, 3000);
    });
})();
</script>
@endpush

@endsection
