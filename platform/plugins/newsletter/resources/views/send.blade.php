@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">

        {{-- ── Alerts (for non-AJAX fallback) ── --}}
        @if(session('success_msg'))
            <div class="alert alert-success">{{ session('success_msg') }}</div>
        @endif

        @if(session('error_msg'))
            <div class="alert alert-danger d-flex align-items-center justify-content-between gap-3">
                <span>{{ session('error_msg') }}</span>
                @if(!empty($lastFailedSend))
                    <button class="btn btn-sm btn-danger text-nowrap ms-auto"
                            onclick="doResendFailed({{ $lastFailedSend->id }}, {{ (int)$lastFailedSend->failed_count }}, this)">
                        Resend failed ({{ number_format($lastFailedSend->failed_count) }})
                    </button>
                @endif
            </div>
        @elseif(!empty($lastFailedSend) && !session('success_msg'))
            <div class="alert alert-warning d-flex align-items-center justify-content-between gap-3">
                <span>
                    <i class="ti ti-alert-triangle me-1"></i>
                    Your last send "<strong>{{ $lastFailedSend->subject }}</strong>"
                    had <strong>{{ number_format($lastFailedSend->failed_count) }}</strong> failed recipient(s).
                </span>
                <button class="btn btn-sm btn-warning text-nowrap ms-auto"
                        onclick="doResendFailed({{ $lastFailedSend->id }}, {{ (int)$lastFailedSend->failed_count }}, this)">
                    Resend failed ({{ number_format($lastFailedSend->failed_count) }})
                </button>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════
             SEND FORM
        ══════════════════════════════════════════════ --}}
        <x-core::card id="send-form-card">
            <x-core::card.header>
                <x-core::card.title>Send Newsletter</x-core::card.title>
            </x-core::card.header>

            <x-core::card.body>
                <div class="alert alert-info mb-4" id="subscriber-info">
                    Sending to <strong>{{ number_format($subscriberCount) }}</strong> subscribed recipient(s).
                    Add a test email below to send a preview to one address first.
                </div>

                <form id="nl-send-form" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="subject">Subject <span class="text-danger">*</span></label>
                        <input class="form-control" id="subject" name="subject" maxlength="180" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="message">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="message" name="message" rows="12" required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label">Banner Image</label>
                            <input class="form-control mb-2" id="image_file" name="image_file" type="file" accept="image/*">
                            <input class="form-control" id="image_url" name="image_url" placeholder="Or paste an image URL: https://…">
                            <div class="form-text">Uploaded file takes priority over URL. Max 5 MB.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pdf">Attach PDF</label>
                            <input class="form-control" id="pdf" name="pdf" type="file" accept="application/pdf">
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3 mb-3">
                        {{-- Dedup window --}}
                        <div class="col-md-6">
                            <label class="form-label" for="dedup_minutes">
                                Skip recipients who already received a newsletter within…
                            </label>
                            <select class="form-select" id="dedup_minutes" name="dedup_minutes">
                                <option value="0">No deduplication (send to everyone)</option>
                                <option value="30">30 minutes</option>
                                <option value="60" selected>1 hour</option>
                                <option value="360">6 hours</option>
                                <option value="1440">24 hours</option>
                            </select>
                        </div>

                        {{-- Test email --}}
                        <div class="col-md-6">
                            <label class="form-label" for="test_to">Test email (preview only)</label>
                            <input class="form-control" id="test_to" name="test_to" type="email" placeholder="name@example.com">
                            <div class="form-text">Leave blank to send to all subscribers.</div>
                        </div>
                    </div>

                    {{-- Schedule --}}
                    <div class="mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="enable_schedule" onchange="toggleSchedule(this)">
                            <label class="form-check-label fw-semibold" for="enable_schedule">Schedule for later</label>
                        </div>
                        <div id="schedule-picker" style="display:none">
                            <input class="form-control" type="datetime-local" id="scheduled_at" name="scheduled_at"
                                   min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                            <div class="form-text">The newsletter will be queued and sent automatically at this time.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a class="btn btn-outline-secondary" href="{{ route('newsletter.index') }}">Back to subscribers</a>
                        <button class="btn btn-primary" type="submit" id="send-btn">
                            <span id="send-btn-label">🚀 Send Newsletter</span>
                        </button>
                    </div>
                </form>
            </x-core::card.body>
        </x-core::card>

        {{-- ══════════════════════════════════════════════
             DUPLICATE PANEL (shown when newsletter was already sent)
        ══════════════════════════════════════════════ --}}
        <div id="duplicate-panel" style="display:none" class="mt-4">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Already Sent</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="alert alert-warning mb-4">
                        <i class="ti ti-alert-triangle me-1"></i>
                        A newsletter with the same subject and message was already sent on
                        <strong id="dup-sent-at"></strong> —
                        <strong id="dup-sent-count"></strong> delivered out of <strong id="dup-recipient-count"></strong> recipients.
                    </div>
                    <div id="dup-new-subscribers-info" style="display:none" class="alert alert-info mb-4">
                        <i class="ti ti-users me-1"></i>
                        <strong id="dup-new-count"></strong> new subscriber(s) have joined since then and haven't received this newsletter yet.
                    </div>
                    <div id="dup-no-new-subscribers" style="display:none" class="text-muted small mb-4">
                        No new subscribers have joined since the last send.
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a id="dup-report-link" href="#" class="btn btn-outline-secondary">
                            <i class="ti ti-list me-1"></i>View Delivery Report
                        </a>
                        <button id="dup-resend-new-btn" style="display:none" class="btn btn-primary"
                                onclick="doResendNewOnly()">
                            <i class="ti ti-send me-1"></i>
                            Send to <span id="dup-btn-count"></span> new subscriber(s)
                        </button>
                        <a href="{{ route('newsletter.send') }}" class="btn btn-outline-secondary ms-auto">
                            <i class="ti ti-arrow-left me-1"></i>Edit newsletter
                        </a>
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>

        {{-- ══════════════════════════════════════════════
             PROGRESS PANEL (hidden until send starts)
        ══════════════════════════════════════════════ --}}
        <div id="progress-panel" style="display:none" class="mt-4">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title id="progress-title">Sending newsletter…</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>

                    {{-- Scheduled banner --}}
                    <div id="scheduled-banner" style="display:none" class="alert alert-success">
                        <i class="ti ti-calendar-check me-1"></i>
                        <strong>Scheduled!</strong> Your newsletter will be sent at
                        <strong id="scheduled-time"></strong>.
                        <a href="{{ route('newsletter.index') }}" class="alert-link ms-2">View all sends →</a>
                    </div>

                    {{-- Live progress --}}
                    <div id="live-progress">
                        <div class="d-flex justify-content-between mb-1 small text-muted">
                            <span id="progress-label">Queuing jobs…</span>
                            <span id="progress-pct">0%</span>
                        </div>
                        <div class="progress mb-3" style="height:10px">
                            <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 role="progressbar" style="width:0%"></div>
                        </div>
                        <div class="row text-center g-2 mb-3">
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="fs-4 fw-bold text-success" id="stat-sent">0</div>
                                    <div class="text-muted small">Sent</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="fs-4 fw-bold text-danger" id="stat-failed">0</div>
                                    <div class="text-muted small">Failed</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="fs-4 fw-bold text-secondary" id="stat-remaining">0</div>
                                    <div class="text-muted small">Remaining</div>
                                </div>
                            </div>
                        </div>
                        <div id="finish-actions" style="display:none" class="d-flex gap-2">
                            <a href="{{ route('newsletter.send') }}" class="btn btn-outline-primary btn-sm">Send another</a>
                            <a href="{{ route('newsletter.index') }}" class="btn btn-outline-secondary btn-sm">View all sends</a>
                        </div>
                    </div>

                </x-core::card.body>
            </x-core::card>
        </div>

    </div>
</div>

{{-- Resend failed confirmation modal --}}
<div class="modal fade" id="resendFailedModal" tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resend Failed Recipients</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    Resend to the <strong><span id="resendFailedCount">0</span></strong>
                    recipient(s) who didn't receive this email?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="resendFailedOkBtn">Yes, Resend</button>
            </div>
        </div>
    </div>
</div>

<script>
let pollInterval = null;

function toggleSchedule(chk) {
    document.getElementById('schedule-picker').style.display = chk.checked ? 'block' : 'none';
    document.getElementById('send-btn-label').textContent = chk.checked ? '📅 Schedule Newsletter' : '🚀 Send Newsletter';
}

document.getElementById('nl-send-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn = document.getElementById('send-btn');
    btn.disabled = true;
    document.getElementById('send-btn-label').textContent = '⏳ Submitting…';

    const fd = new FormData(this);

    let resp, data;
    try {
        resp = await fetch('{{ route("newsletter.send.post") }}', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd,
        });
        data = await resp.json();
    } catch (err) {
        btn.disabled = false;
        document.getElementById('send-btn-label').textContent = '🚀 Send Newsletter';
        Botble.showError('Network error — please try again.');
        return;
    }

    if (resp.status === 409 && data.duplicate) {
        btn.disabled = false;
        document.getElementById('send-btn-label').textContent = '🚀 Send Newsletter';
        showDuplicatePanel(data);
        return;
    }

    if (!resp.ok || data.error) {
        btn.disabled = false;
        document.getElementById('send-btn-label').textContent = '🚀 Send Newsletter';
        Botble.showError(data.error || data.message || 'An error occurred.');
        return;
    }

    // Hide form, show progress panel
    document.getElementById('send-form-card').style.display = 'none';
    document.getElementById('progress-panel').style.display = 'block';

    if (data.scheduled) {
        document.getElementById('scheduled-banner').style.display = 'block';
        document.getElementById('live-progress').style.display = 'none';
        const d = new Date(data.scheduled_at);
        document.getElementById('scheduled-time').textContent = d.toLocaleString();
        document.getElementById('progress-title').textContent = 'Newsletter Scheduled';
        return;
    }

    // Start polling
    document.getElementById('stat-remaining').textContent = data.total;
    startPolling(data.send_id, data.total);
});

function startPolling(sendId, total) {
    const statusUrl = '{{ rtrim(route("newsletter.send"), "/") }}'.replace('/send', '/send-status/') + sendId;

    pollInterval = setInterval(async () => {
        let data;
        try {
            const r = await fetch(statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            data = await r.json();
        } catch { return; }

        updateProgress(data, total);

        if (data.finished || ['completed', 'failed', 'cancelled'].includes(data.status)) {
            clearInterval(pollInterval);
            finishProgress(data, sendId);
        }
    }, 2000);
}

function updateProgress(data, total) {
    const pct = data.progress_pct ?? 0;
    const bar = document.getElementById('progress-bar');
    bar.style.width = pct + '%';
    bar.setAttribute('aria-valuenow', pct);
    document.getElementById('progress-pct').textContent = pct + '%';

    const sent      = data.sent      ?? 0;
    const failed    = data.failed    ?? 0;
    const processed = data.processed ?? 0;
    const remaining = Math.max(0, total - processed);

    document.getElementById('stat-sent').textContent      = sent;
    document.getElementById('stat-failed').textContent    = failed;
    document.getElementById('stat-remaining').textContent = remaining;
    document.getElementById('progress-label').textContent = `${processed} of ${total} processed…`;
}

function finishProgress(data, sendId) {
    const bar = document.getElementById('progress-bar');
    bar.style.width = '100%';
    bar.classList.remove('progress-bar-animated', 'progress-bar-striped');

    const hasFailed = (data.failed ?? 0) > 0;
    bar.classList.add(hasFailed ? 'bg-warning' : 'bg-success');

    const sent   = data.sent   ?? 0;
    const failed = data.failed ?? 0;

    document.getElementById('progress-pct').textContent   = '100%';
    document.getElementById('stat-remaining').textContent = 0;
    document.getElementById('stat-sent').textContent      = sent;
    document.getElementById('stat-failed').textContent    = failed;
    document.getElementById('progress-title').textContent = hasFailed ? 'Sent (with failures)' : 'Sent Successfully ✅';

    const actions = document.getElementById('finish-actions');
    actions.style.display = 'flex';

    if (hasFailed) {
        const btn = document.createElement('button');
        btn.className = 'btn btn-warning btn-sm ms-auto';
        btn.textContent = `Resend failed (${failed})`;
        btn.onclick = () => doResendFailed(sendId, failed, btn);
        actions.appendChild(btn);
    }

    document.getElementById('progress-label').textContent = hasFailed
        ? `Done — ${sent} delivered, ${failed} failed.`
        : `Done — ${sent} delivered successfully! 🎉`;
}

let _dupSendId = null;

function showDuplicatePanel(data) {
    _dupSendId = data.send_id;

    const sentAt = new Date(data.sent_at).toLocaleString(undefined, {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
    document.getElementById('dup-sent-at').textContent        = sentAt;
    document.getElementById('dup-sent-count').textContent     = data.sent_count.toLocaleString();
    document.getElementById('dup-recipient-count').textContent = data.recipient_count.toLocaleString();
    document.getElementById('dup-report-link').href           = '{{ url("/admin/newsletters") }}/' + data.send_id + '/recipients';

    if (data.new_subscriber_count > 0) {
        document.getElementById('dup-new-count').textContent   = data.new_subscriber_count.toLocaleString();
        document.getElementById('dup-btn-count').textContent   = data.new_subscriber_count.toLocaleString();
        document.getElementById('dup-new-subscribers-info').style.display = 'block';
        document.getElementById('dup-resend-new-btn').style.display       = 'inline-flex';
        document.getElementById('dup-no-new-subscribers').style.display   = 'none';
    } else {
        document.getElementById('dup-new-subscribers-info').style.display = 'none';
        document.getElementById('dup-resend-new-btn').style.display       = 'none';
        document.getElementById('dup-no-new-subscribers').style.display   = 'block';
    }

    document.getElementById('send-form-card').style.display = 'none';
    document.getElementById('duplicate-panel').style.display = 'block';
}

async function doResendNewOnly() {
    if (! _dupSendId) return;

    const btn = document.getElementById('dup-resend-new-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending…';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        ?? document.querySelector('input[name="_token"]')?.value ?? '';

    try {
        const r = await fetch('{{ url("/admin/newsletters") }}/' + _dupSendId + '/resend?new_only=1', {
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
            throw new Error('Server error — check browser console for details.');
        }
        if (!r.ok || data.error) throw new Error(data.error || data.message || 'Request failed');

        // Hide duplicate panel, show progress for the new send
        document.getElementById('duplicate-panel').style.display = 'none';
        document.getElementById('progress-panel').style.display  = 'block';
        document.getElementById('stat-remaining').textContent    = data.total;
        startPolling(data.send_id, data.total);
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-send me-1"></i> Send to <span id="dup-btn-count">' + document.getElementById('dup-btn-count').textContent + '</span> new subscriber(s)';
        Botble.showError(err.message);
    }
}

async function doResendFailed(sendId, failedCount, btn) {
    const confirmed = await new Promise(resolve => {
        const modalEl = document.getElementById('resendFailedModal');
        document.getElementById('resendFailedCount').textContent = failedCount;
        const modal = new bootstrap.Modal(modalEl);
        const okBtn = document.getElementById('resendFailedOkBtn');
        const handler = () => { resolve(true); modal.hide(); };
        okBtn.addEventListener('click', handler, { once: true });
        modalEl.addEventListener('hidden.bs.modal', () => resolve(false), { once: true });
        modal.show();
    });
    if (!confirmed) return;

    btn.disabled = true;
    btn.textContent = '⏳ Queuing…';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        ?? document.querySelector('input[name="_token"]')?.value ?? '';
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
        btn.textContent = `✅ Queued for ${data.total} recipient(s)`;
        btn.classList.replace('btn-warning', 'btn-success');
    } catch (err) {
        btn.disabled = false;
        btn.textContent = `Resend failed (${failedCount})`;
        Botble.showError(err.message);
    }
}
</script>
@endsection
