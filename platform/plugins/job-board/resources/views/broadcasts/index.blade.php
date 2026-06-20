@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @php
        $platformMeta = [
            'facebook' => ['label' => 'Facebook', 'icon' => 'ti ti-brand-facebook', 'badge' => 'bg-primary-subtle text-primary'],
            'linkedin' => ['label' => 'LinkedIn', 'icon' => 'ti ti-brand-linkedin', 'badge' => 'bg-info-subtle text-info'],
            'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'ti ti-brand-whatsapp', 'badge' => 'bg-success-subtle text-success'],
            'whapi'    => ['label' => 'WhatsApp Channel', 'icon' => 'ti ti-brand-whatsapp', 'badge' => 'bg-success-subtle text-success'],
            'publer'   => ['label' => 'Publer (FB/LinkedIn/TikTok pages)', 'icon' => 'ti ti-rocket', 'badge' => 'bg-purple-subtle text-purple'],
        ];
        $statusBadge = [
            'pending'   => 'bg-secondary-subtle text-secondary',
            'scheduled' => 'bg-warning-subtle text-warning',
            'sent'      => 'bg-success-subtle text-success',
            'failed'    => 'bg-danger-subtle text-danger',
            'cancelled' => 'bg-secondary-subtle text-secondary',
            'recurring' => 'bg-purple-subtle text-purple',
            'completed' => 'bg-info-subtle text-info',
        ];
        $recurrenceLabel = [
            'fixed_daily'    => 'This time every day',
            'daily_around'   => 'Around this time daily',
            'random_per_day' => 'Random times/day',
        ];
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>📢 Broadcast — Post to All Channels</x-core::card.title>
                </x-core::card.header>

                <x-core::card.body>
                    @if($channels->isEmpty())
                        <div class="alert alert-warning">
                            No active Facebook, LinkedIn, or WhatsApp channel automations are connected yet.
                            <a href="{{ route('job-board.automations.index') }}">Connect one first</a> — broadcasts only
                            reach channels that are active there.
                        </div>
                    @else
                        <div class="alert alert-info mb-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 bc-collapse-toggle"
                                 style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#bc-channel-badges-body"
                                 role="button" aria-expanded="false" aria-controls="bc-channel-badges-body">
                                <span>Posting to <strong>{{ $channels->count() }}</strong> connected channel(s)</span>
                                <i class="ti ti-chevron-down bc-collapse-icon"></i>
                            </div>
                            <div class="collapse" id="bc-channel-badges-body">
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                    @foreach($channels as $channel)
                                        @php $meta = $platformMeta[$channel->platform] ?? ['label' => ucfirst($channel->platform), 'icon' => 'ti ti-share', 'badge' => 'bg-light text-dark']; @endphp
                                        <span class="badge {{ $meta['badge'] }}"><i class="{{ $meta['icon'] }} me-1"></i>{{ $channel->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <form id="broadcast-form">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label d-block">Audience</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="audience" id="bc-audience-channels" value="channels" checked>
                                <label class="form-check-label" for="bc-audience-channels">
                                    Connected social channels ({{ $channels->count() }})
                                </label>
                            </div>

                            @if($channels->isNotEmpty())
                            <div class="border rounded mb-3" id="bc-channel-list-panel">
                                <div class="d-flex align-items-center justify-content-between px-3 py-2 bc-collapse-toggle"
                                     style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#bc-channel-list-body"
                                     role="button" aria-expanded="false" aria-controls="bc-channel-list-body">
                                    <span class="fw-semibold">
                                        <i class="ti ti-list me-1"></i>Connected Channels ({{ $channels->count() }})
                                    </span>
                                    <i class="ti ti-chevron-down bc-collapse-icon"></i>
                                </div>
                                <div class="collapse" id="bc-channel-list-body">
                                    <div class="table-responsive border-top">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width:42px">#</th>
                                                    <th>Platform</th>
                                                    <th>Channel / Page Name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($channels as $i => $channel)
                                                    @php $meta = $platformMeta[$channel->platform] ?? ['label' => ucfirst($channel->platform), 'icon' => 'ti ti-share', 'badge' => 'bg-light text-dark']; @endphp
                                                    <tr>
                                                        <td class="text-muted">{{ $i + 1 }}</td>
                                                        <td>
                                                            <span class="badge {{ $meta['badge'] }}">
                                                                <i class="{{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $channel->name }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="px-3 py-2 border-top d-flex justify-content-end">
                                        <a href="{{ route('job-board.automations.index') }}" class="small text-muted">
                                            <i class="ti ti-settings me-1"></i>Manage channels
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endif

                        </div>

                        <div class="border rounded mb-4 d-none" id="bc-employer-contacts-panel">
                            <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 border-bottom">
                                <div>
                                    <div class="fw-semibold">Employer WhatsApp Recipients</div>
                                    <div class="text-muted small" id="bc-contacts-summary">Loading contacts…</div>
                                </div>
                                <div class="spinner-border spinner-border-sm text-success" id="bc-contacts-loading" role="status"></div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:70px">#</th>
                                            <th>Employer / Company</th>
                                            <th>Origin</th>
                                            <th>WhatsApp Number</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bc-contacts-body"></tbody>
                                </table>
                            </div>
                            <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 border-top">
                                <span class="text-muted small" id="bc-contacts-page-label"></span>
                                <div class="btn-group btn-group-sm" id="bc-contacts-pagination"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="bc-message">Message <span class="text-danger">*</span></label>

                            <div id="bc-placeholders-bar" class="mb-2 d-none">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="text-muted small">Insert:</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary bc-placeholder-btn"
                                            data-placeholder="[Company Name]"
                                            title="Replaced with the employer or company name for each recipient">
                                        [Company Name]
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary bc-placeholder-btn"
                                            data-placeholder="[Country]"
                                            title="Replaced with the recipient's country">
                                        [Country]
                                    </button>
                                    <span class="text-muted small fst-italic">— personalised per recipient</span>
                                </div>
                            </div>

                            <textarea class="form-control" id="bc-message" name="message" rows="8" maxlength="3000" required
                                      placeholder="What do you want to tell your audience across Facebook, LinkedIn, and WhatsApp?"></textarea>
                            <div class="form-text"><span id="bc-message-counter">0</span>/3000</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Image (optional)</label>
                            <div id="bc-drop-zone" class="border border-2 border-dashed rounded p-4 text-center" style="cursor:pointer">
                                <div id="bc-drop-placeholder">
                                    <i class="ti ti-photo-up fs-1 text-muted d-block mb-1"></i>
                                    <span class="text-muted">Click or drag an image here (JPG, PNG, WebP — max 10 MB)</span>
                                </div>
                                <div id="bc-image-uploading" style="display:none">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                    <span class="text-muted">Uploading…</span>
                                </div>
                                <div id="bc-image-preview" style="display:none">
                                    <img id="bc-image-img" class="img-fluid rounded mb-2" style="max-height:280px">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="bc-image-replace">
                                            <i class="ti ti-replace me-1"></i> Replace
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="bc-image-remove">
                                            <i class="ti ti-trash me-1"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <input type="file" id="bc-image-input" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none">
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <button class="btn btn-primary" type="submit" id="bc-submit-btn" {{ $channels->isEmpty() ? 'disabled' : '' }}>
                                <i class="ti ti-send me-1"></i> Post to Channels
                            </button>
                        </div>
                    </form>
                </x-core::card.body>
            </x-core::card>

            <x-core::card class="mt-4">
                <x-core::card.header class="d-flex align-items-center justify-content-between bc-collapse-toggle"
                                      style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#bc-recent-broadcasts-body"
                                      role="button" aria-expanded="true" aria-controls="bc-recent-broadcasts-body">
                    <x-core::card.title class="mb-0">Recent Broadcasts</x-core::card.title>
                    <i class="ti ti-chevron-down bc-collapse-icon"></i>
                </x-core::card.header>
                <div class="collapse show" id="bc-recent-broadcasts-body">
                <x-core::card.body>
                    @if($broadcasts->isEmpty())
                        <p class="text-muted mb-0">No broadcasts sent yet — your history will show up here.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Scheduled / Sent</th>
                                        <th>Channels</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($broadcasts as $broadcast)
                                        <tr>
                                            <td style="max-width:260px">
                                                <span title="{{ $broadcast->message }}">{{ Str::limit($broadcast->message, 70) }}</span>
                                                @if($broadcast->image_path)
                                                    <i class="ti ti-photo text-muted ms-1" title="Includes an image"></i>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $statusBadge[$broadcast->status] ?? 'bg-light text-dark' }}">{{ ucfirst($broadcast->status) }}</span>
                                                @if($broadcast->recurrence_type)
                                                    <div class="small text-muted mt-1">
                                                        🔁 {{ $recurrenceLabel[$broadcast->recurrence_type] ?? $broadcast->recurrence_type }}
                                                        @if($broadcast->ai_spice)
                                                            <span class="badge bg-warning-subtle text-warning border ms-1" title="Reworded by AI on each send">🌟 AI Spice</span>
                                                        @endif
                                                        <div>
                                                            {{ $broadcast->occurrence_count }}{{ $broadcast->max_occurrences ? '/'.$broadcast->max_occurrences : '' }} sent
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-nowrap small text-muted">
                                                @if($broadcast->status === 'recurring' && $broadcast->next_run_at)
                                                    Next: {{ $broadcast->next_run_at->format('M j, g:i A') }}
                                                    @if($broadcast->sent_at)
                                                        <div>Last: {{ $broadcast->sent_at->format('M j, g:i A') }}</div>
                                                    @endif
                                                @elseif($broadcast->status === 'scheduled' && $broadcast->scheduled_at)
                                                    {{ $broadcast->scheduled_at->format('M j, Y g:i A') }}
                                                @elseif($broadcast->sent_at)
                                                    {{ $broadcast->sent_at->format('M j, Y g:i A') }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td style="max-width:220px">
                                                @if(is_array($broadcast->results) && $broadcast->results)
                                                    @php $visibleCount = 3; @endphp
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($broadcast->results as $index => $result)
                                                            <span class="badge {{ ($result['success'] ?? false) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} {{ $index >= $visibleCount ? 'collapse bc-extra-channel-'.$broadcast->id : '' }}"
                                                                  title="{{ $result['name'] ?? '' }}">
                                                                <i class="{{ $platformMeta[$result['platform'] ?? '']['icon'] ?? 'ti ti-share' }} me-1"></i>{{ $result['name'] ?? $result['platform'] ?? '—' }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                    @if(count($broadcast->results) > $visibleCount)
                                                        <button type="button" class="btn btn-link btn-sm p-0 mt-1 bc-toggle-channels"
                                                                data-bs-toggle="collapse" data-bs-target=".bc-extra-channel-{{ $broadcast->id }}"
                                                                aria-expanded="false">
                                                            +{{ count($broadcast->results) - $visibleCount }} more
                                                        </button>
                                                    @endif
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-nowrap">
                                                @if(in_array($broadcast->status, ['scheduled', 'recurring']))
                                                    <button class="btn btn-sm btn-outline-warning bc-cancel"
                                                            data-url="{{ route('job-board.automations.broadcast-cancel', $broadcast->id) }}"
                                                            data-label='Cancel the {{ $broadcast->status === 'recurring' ? 'recurring' : 'scheduled' }} broadcast "{{ Str::limit($broadcast->message, 60) }}"?'>
                                                        <i class="ti ti-player-pause me-1"></i> Cancel
                                                    </button>
                                                @endif
                                                @if(false && $broadcast->audience === 'employers' && in_array($broadcast->status, ['failed', 'sent']))
                                                    @php
                                                        $sentSoFar = count($broadcast->sent_recipients ?? []);
                                                        $totalRecipients = $broadcast->recipient_count ?? 0;
                                                        $remaining = max(0, $totalRecipients - $sentSoFar);
                                                        $retryLabel = $broadcast->status === 'failed'
                                                            ? ($sentSoFar ? "Retry ({$remaining} remaining, {$sentSoFar} already reached)" : 'Retry')
                                                            : "Resend to all ({$totalRecipients})";
                                                    @endphp
                                                    <button class="btn btn-sm btn-outline-primary bc-retry"
                                                            data-url="{{ route('job-board.automations.broadcast-retry', $broadcast->id) }}"
                                                            data-label="{{ $retryLabel }}">
                                                        <i class="ti ti-refresh me-1"></i> Retry
                                                    </button>
                                                @endif
                                                <button class="btn btn-sm btn-outline-danger bc-delete"
                                                        data-url="{{ route('job-board.automations.broadcast-destroy', $broadcast->id) }}"
                                                        data-label='Remove "{{ Str::limit($broadcast->message, 60) }}" from history? This does not unpublish posts already sent.'>
                                                    <i class="ti ti-trash me-1"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-core::card.body>
                </div>
            </x-core::card>

        </div>
    </div>

    {{-- Confirm send / schedule modal --}}
    <div class="modal fade" id="bcConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="ti ti-send text-primary"></i>
                        <span>Post to channels?</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2" id="bc-confirm-intro"></p>
                    <div class="border rounded p-2 mb-3 small text-muted" style="max-height:140px;overflow:auto" id="bc-confirm-preview"></div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="bc-enable-schedule">
                        <label class="form-check-label fw-semibold" for="bc-enable-schedule">Schedule for later</label>
                    </div>
                    <div id="bc-schedule-picker" style="display:none" class="mb-2">
                        <input class="form-control" type="datetime-local" id="bc-scheduled-at"
                               min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                        <div class="form-text">The post will be queued and published automatically at this time.</div>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="bc-enable-recurring">
                        <label class="form-check-label fw-semibold" for="bc-enable-recurring">🔁 Repeat this broadcast</label>
                    </div>
                    <div id="bc-recurring-panel" style="display:none" class="border rounded p-3 mb-2 bg-light">
                        <div class="mb-2">
                            <label class="form-label small mb-1">When</label>
                            <select class="form-select form-select-sm" id="bc-recurrence-type">
                                <option value="daily_around" selected>Around this time, every day</option>
                                <option value="fixed_daily">This exact time, every day</option>
                                <option value="random_per_day">Random times, a few times a day</option>
                            </select>
                        </div>

                        <div id="bc-recurrence-time-row" class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small mb-1">Time</label>
                                <input type="time" class="form-control form-control-sm" id="bc-recurrence-time" value="09:00">
                            </div>
                            <div class="col-6" id="bc-jitter-col">
                                <label class="form-label small mb-1">± minutes</label>
                                <input type="number" class="form-control form-control-sm" id="bc-recurrence-jitter" value="45" min="1" max="240">
                            </div>
                        </div>
                        <div class="form-text mb-2" id="bc-jitter-hint">
                            Each day the post fires a random amount within ±45 minutes of 09:00 — reads like a person, not a clock.
                        </div>

                        <div id="bc-recurrence-random-row" class="row g-2 mb-2" style="display:none">
                            <div class="col-4">
                                <label class="form-label small mb-1">Times/day</label>
                                <input type="number" class="form-control form-control-sm" id="bc-recurrence-times-per-day" value="2" min="1" max="6">
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">From (24h)</label>
                                <input type="number" class="form-control form-control-sm" id="bc-recurrence-window-start" value="8" min="0" max="23">
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">Until (24h)</label>
                                <input type="number" class="form-control form-control-sm" id="bc-recurrence-window-end" value="20" min="1" max="24">
                            </div>
                            <div class="col-12">
                                <div class="form-text">
                                    Spread across this window so nothing fires at 3am — keeps the cadence looking natural.
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1">Stop after</label>
                            <div class="input-group input-group-sm" style="max-width:220px">
                                <input type="number" class="form-control" id="bc-max-occurrences" min="1" max="10000" placeholder="∞">
                                <span class="input-group-text">posts</span>
                            </div>
                            <div class="form-text">Leave blank to repeat indefinitely until you cancel it.</div>
                        </div>

                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="bc-ai-spice">
                            <label class="form-check-label" for="bc-ai-spice">
                                🌟 AI Spice — reword the message a little differently on each send
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Not yet</button>
                    <button type="button" class="btn btn-primary" id="bc-confirm-send-btn">
                        <i class="ti ti-send me-1"></i> <span id="bc-confirm-send-label">Post Now</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Generic confirm modal (cancel / delete history rows) --}}
    <div class="modal fade" id="bcGenericConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-alert-triangle text-warning fs-3"></i>
                        </span>
                    </div>
                    <p class="text-muted small mb-4" id="bcGenericConfirmLabel"></p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning px-4" id="bcGenericConfirmBtn">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer')
    <style>
        .bc-collapse-icon { transition: transform .2s ease; }
        .bc-collapse-toggle[aria-expanded="true"] .bc-collapse-icon { transform: rotate(180deg); }
    </style>
    <script>
    $(function () {
        $('.bc-toggle-channels').each(function () {
            const $btn   = $(this);
            const target = $btn.data('bs-target');
            const moreText = $btn.text().trim();
            $(target)
                .on('shown.bs.collapse', function () { $btn.text('Show less'); })
                .on('hidden.bs.collapse', function () { $btn.text(moreText); });
        });
        const csrfToken     = $('meta[name="csrf-token"]').attr('content');
        const uploadUrl     = '{{ route('job-board.automations.broadcast-upload-image') }}';
        const sendUrl       = '{{ route('job-board.automations.broadcast-send') }}';
        const contactsUrl   = '{{ route('job-board.automations.broadcast-employer-contacts') }}';
        const channelCount  = {{ $channels->count() }};
        const employerCount = {{ $employerPhoneCount }};

        let uploadedImagePath = null;
        let uploadedImageUrl  = null;
        let uploading         = false;
        let contactsLoaded    = false;

        // Message counter
        $('#bc-message').on('input', function () {
            $('#bc-message-counter').text($(this).val().length);
        });
        $('input[name="audience"]').on('change', function () {
            const employersSelected = $(this).val() === 'employers';
            $('#bc-submit-btn').html(
                employersSelected
                    ? '<i class="ti ti-brand-whatsapp me-1"></i> Send to Employers'
                    : '<i class="ti ti-send me-1"></i> Post to Channels'
            );
            $('#bc-channel-list-panel').toggle(!employersSelected);
            $('#bc-employer-contacts-panel').toggle(employersSelected);
            $('#bc-placeholders-bar').toggleClass('d-none', !employersSelected);
            if (employersSelected && !contactsLoaded) loadEmployerContacts(1);
        });

        $(document).on('click', '.bc-placeholder-btn', function () {
            const textarea = document.getElementById('bc-message');
            const placeholder = $(this).data('placeholder');
            const start = textarea.selectionStart;
            const end   = textarea.selectionEnd;
            const value = textarea.value;
            textarea.value = value.slice(0, start) + placeholder + value.slice(end);
            textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
            textarea.focus();
            $('#bc-message-counter').text(textarea.value.length);
        });

        function loadEmployerContacts(page) {
            $('#bc-contacts-loading').show();
            $('#bc-contacts-summary').text('Loading contacts…');

            fetch(contactsUrl + '?page=' + page, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => {
                    if (!r.ok) throw new Error('Could not load employer contacts.');
                    return r.json();
                })
                .then(resp => {
                    contactsLoaded = true;
                    renderEmployerContacts(resp.data || [], resp.meta || {});
                })
                .catch(err => {
                    $('#bc-contacts-body')
                        .empty()
                        .append($('<tr>').append($('<td colspan="4" class="text-danger text-center py-3">').text(err.message)));
                    $('#bc-contacts-summary').text('Contacts could not be loaded.');
                })
                .finally(() => $('#bc-contacts-loading').hide());
        }

        function renderEmployerContacts(contacts, meta) {
            const $body = $('#bc-contacts-body').empty();
            contacts.forEach(function (contact, index) {
                const $name = contact.edit_url
                    ? $('<a class="fw-medium">').attr('href', contact.edit_url).text(contact.name || 'Employer')
                    : document.createTextNode(contact.name || 'Employer');

                $('<tr>')
                    .append($('<td class="text-muted">').text((meta.from || 1) + index))
                    .append($('<td>').append($name))
                    .append($('<td class="text-nowrap">').attr('title', contact.country_name || 'Unknown').text(
                        countryFlag(contact.country_code) + ' ' + (contact.country_name || 'Unknown')
                    ))
                    .append($('<td class="text-nowrap">').append(
                        $('<i class="ti ti-brand-whatsapp text-success me-1">'),
                        document.createTextNode('+' + contact.phone)
                    ))
                    .appendTo($body);
            });

            if (!contacts.length) {
                $body.html('<tr><td colspan="4" class="text-muted text-center py-3">No employer WhatsApp numbers found.</td></tr>');
            }

            $('#bc-contacts-summary').text(
                meta.total ? 'Showing ' + meta.from + '–' + meta.to + ' of ' + meta.total + ' numbers.' : 'No numbers found.'
            );
            $('#bc-contacts-page-label').text('Page ' + (meta.current_page || 1) + ' of ' + (meta.last_page || 1));

            const $pagination = $('#bc-contacts-pagination').empty();
            $('<button type="button" class="btn btn-outline-secondary">Previous</button>')
                .prop('disabled', meta.current_page <= 1)
                .on('click', () => loadEmployerContacts(meta.current_page - 1))
                .appendTo($pagination);
            $('<button type="button" class="btn btn-outline-secondary">Next</button>')
                .prop('disabled', meta.current_page >= meta.last_page)
                .on('click', () => loadEmployerContacts(meta.current_page + 1))
                .appendTo($pagination);
        }

        function countryFlag(code) {
            code = String(code || '').toUpperCase();
            if (!/^[A-Z]{2}$/.test(code)) return '🏳';
            return String.fromCodePoint(...[...code].map(char => 127397 + char.charCodeAt(0)));
        }

        // ── Image drop zone ──────────────────────────────────────────────
        const $dropZone   = $('#bc-drop-zone');
        const $fileInput  = $('#bc-image-input');

        $dropZone.on('click', function () {
            if (!uploading) $fileInput.trigger('click');
        });
        $dropZone.on('dragover', function (e) { e.preventDefault(); $(this).addClass('border-primary'); });
        $dropZone.on('dragleave', function (e) { e.preventDefault(); $(this).removeClass('border-primary'); });
        $dropZone.on('drop', function (e) {
            e.preventDefault();
            $(this).removeClass('border-primary');
            const file = e.originalEvent.dataTransfer?.files?.[0];
            if (file) uploadImage(file);
        });
        $fileInput.on('change', function () {
            if (this.files[0]) uploadImage(this.files[0]);
        });

        $('#bc-image-replace').on('click', function (e) {
            e.stopPropagation();
            $fileInput.trigger('click');
        });
        $('#bc-image-remove').on('click', function (e) {
            e.stopPropagation();
            uploadedImagePath = null;
            uploadedImageUrl  = null;
            $fileInput.val('');
            $('#bc-image-preview').hide();
            $('#bc-drop-placeholder').show();
        });

        function uploadImage(file) {
            if (file.size > 10 * 1024 * 1024) {
                Botble.showError('Image is too large — max 10 MB.');
                return;
            }

            uploading = true;
            uploadedImagePath = null;
            uploadedImageUrl  = null;
            $('#bc-drop-placeholder, #bc-image-preview').hide();
            $('#bc-image-uploading').show();

            const fd = new FormData();
            fd.append('_token', csrfToken);
            fd.append('image', file);

            fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(resp => {
                    uploading = false;
                    $('#bc-image-uploading').hide();

                    if (!resp.ok) {
                        Botble.showError(resp.message || 'Image upload failed.');
                        $('#bc-drop-placeholder').show();
                        return;
                    }

                    uploadedImagePath = resp.path;
                    uploadedImageUrl  = resp.url;
                    $('#bc-image-img').attr('src', resp.url);
                    $('#bc-image-preview').show();

                    Botble.showSuccess('Image uploaded.');

                    // "Once image is uploaded, ask to send."
                    if ($('#bc-message').val().trim().length > 0) {
                        openConfirmModal();
                    }
                })
                .catch(() => {
                    uploading = false;
                    $('#bc-image-uploading').hide();
                    $('#bc-drop-placeholder').show();
                    Botble.showError('Image upload failed — network error.');
                });
        }

        // ── Confirm / schedule modal ─────────────────────────────────────
        const confirmModal = new bootstrap.Modal(document.getElementById('bcConfirmModal'));

        function confirmSendLabel() {
            if ($('#bc-enable-recurring').is(':checked')) return 'Start Repeating';
            if ($('#bc-enable-schedule').is(':checked')) return 'Schedule Post';
            return 'Post Now';
        }

        $('#bc-enable-schedule').on('change', function () {
            $('#bc-schedule-picker').toggle(this.checked);
            if (this.checked) $('#bc-enable-recurring').prop('checked', false).trigger('change');
            $('#bc-confirm-send-label').text(confirmSendLabel());
        });

        $('#bc-enable-recurring').on('change', function () {
            $('#bc-recurring-panel').toggle(this.checked);
            if (this.checked) $('#bc-enable-schedule').prop('checked', false).trigger('change');
            $('#bc-confirm-send-label').text(confirmSendLabel());
        });

        $('#bc-recurrence-type').on('change', function () {
            const type = $(this).val();
            $('#bc-recurrence-time-row').toggle(type === 'fixed_daily' || type === 'daily_around');
            $('#bc-jitter-col').toggle(type === 'daily_around');
            $('#bc-jitter-hint').toggle(type === 'daily_around');
            $('#bc-recurrence-random-row').toggle(type === 'random_per_day');
        }).trigger('change');

        $('#bc-recurrence-time, #bc-recurrence-jitter').on('input', function () {
            const time = $('#bc-recurrence-time').val() || '09:00';
            const jitter = $('#bc-recurrence-jitter').val() || 45;
            $('#bc-jitter-hint').text('Each day the post fires a random amount within ±' + jitter + ' minutes of ' + time + ' — reads like a person, not a clock.');
        });

        function openConfirmModal() {
            const message = $('#bc-message').val().trim();
            if (!message) {
                Botble.showError('Write a message before posting.');
                return;
            }
            const audience = $('input[name="audience"]:checked').val();
            if (audience === 'channels' && !channelCount) {
                Botble.showError('No active channels are connected — add one in Automations first.');
                return;
            }

            $('#bc-confirm-intro').html(
                (audience === 'employers'
                    ? 'Ready to send directly to <strong>' + employerCount + '</strong> employer WhatsApp contact(s)'
                    : 'Ready to post to <strong>' + channelCount + '</strong> connected channel(s)') +
                (uploadedImagePath ? ' with your uploaded image' : ' as a text-only post') + '.'
            );
            $('#bc-confirm-preview').text(message);
            $('#bc-enable-schedule').prop('checked', false).trigger('change');
            $('#bc-enable-recurring').prop('checked', false).trigger('change');
            $('#bc-confirm-send-btn').prop('disabled', false).find('#bc-confirm-send-label').text('Post Now');
            confirmModal.show();
        }

        $('#broadcast-form').on('submit', function (e) {
            e.preventDefault();
            openConfirmModal();
        });

        $('#bc-confirm-send-btn').on('click', function () {
            const $btn        = $(this);
            const message     = $('#bc-message').val().trim();
            const scheduled   = $('#bc-enable-schedule').is(':checked');
            const scheduledAt = $('#bc-scheduled-at').val();
            const recurring   = $('#bc-enable-recurring').is(':checked');
            const recurrenceType = $('#bc-recurrence-type').val();

            if (scheduled && !scheduledAt) {
                Botble.showError('Pick a date and time to schedule this post.');
                return;
            }
            if (recurring && (recurrenceType === 'fixed_daily' || recurrenceType === 'daily_around') && !$('#bc-recurrence-time').val()) {
                Botble.showError('Pick a time of day for the recurring post.');
                return;
            }

            $btn.prop('disabled', true).find('#bc-confirm-send-label').text(recurring ? 'Setting up…' : (scheduled ? 'Scheduling…' : 'Posting…'));

            const fd = new FormData();
            fd.append('_token', csrfToken);
            fd.append('message', message);
            fd.append('audience', $('input[name="audience"]:checked').val());
            if (uploadedImagePath) fd.append('image_path', uploadedImagePath);
            if (scheduled && scheduledAt) fd.append('scheduled_at', scheduledAt.replace('T', ' '));

            if (recurring) {
                fd.append('recurrence_type', recurrenceType);
                if (recurrenceType === 'fixed_daily' || recurrenceType === 'daily_around') {
                    fd.append('recurrence_time', $('#bc-recurrence-time').val());
                }
                if (recurrenceType === 'daily_around') {
                    fd.append('recurrence_jitter_minutes', $('#bc-recurrence-jitter').val() || 45);
                }
                if (recurrenceType === 'random_per_day') {
                    fd.append('recurrence_times_per_day', $('#bc-recurrence-times-per-day').val() || 2);
                    fd.append('recurrence_window_start', $('#bc-recurrence-window-start').val() || 8);
                    fd.append('recurrence_window_end', $('#bc-recurrence-window-end').val() || 20);
                }
                const maxOccurrences = $('#bc-max-occurrences').val();
                if (maxOccurrences) fd.append('max_occurrences', maxOccurrences);
                fd.append('ai_spice', $('#bc-ai-spice').is(':checked') ? 1 : 0);
            }

            fetch(sendUrl, { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(resp => {
                    if (resp.error) {
                        Botble.showError(resp.message || 'Could not queue the broadcast.');
                        $btn.prop('disabled', false).find('#bc-confirm-send-label').text(confirmSendLabel());
                        return;
                    }

                    Botble.showSuccess(resp.message || 'Broadcast queued.');
                    confirmModal.hide();
                    setTimeout(() => location.reload(), 900);
                })
                .catch(() => {
                    Botble.showError('Request failed. Check server logs.');
                    $btn.prop('disabled', false).find('#bc-confirm-send-label').text(confirmSendLabel());
                });
        });

        // ── Generic confirm (cancel scheduled / delete history row) ──────
        const genericConfirmModal = new bootstrap.Modal(document.getElementById('bcGenericConfirmModal'));
        let pendingGenericAction = null;

        $(document).on('click', '.bc-cancel, .bc-delete, .bc-retry', function () {
            pendingGenericAction = {
                url:    $(this).data('url'),
                method: $(this).hasClass('bc-delete') ? 'DELETE' : 'POST',
            };
            $('#bcGenericConfirmLabel').text($(this).data('label'));
            $('#bcGenericConfirmBtn').text($(this).hasClass('bc-retry') ? 'Retry' : 'Confirm');
            genericConfirmModal.show();
        });

        $('#bcGenericConfirmBtn').on('click', function () {
            if (!pendingGenericAction) return;
            const $btn = $(this);
            $btn.prop('disabled', true);

            const fd = new FormData();
            fd.append('_token', csrfToken);
            if (pendingGenericAction.method === 'DELETE') fd.append('_method', 'DELETE');

            fetch(pendingGenericAction.url, { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(resp => {
                    if (resp.error) {
                        Botble.showError(resp.message || 'Action failed.');
                    } else {
                        Botble.showSuccess(resp.message || 'Done.');
                        setTimeout(() => location.reload(), 700);
                    }
                })
                .catch(() => Botble.showError('Request failed. Check server logs.'))
                .finally(() => {
                    $btn.prop('disabled', false);
                    pendingGenericAction = null;
                });
        });
    });
    </script>
@endpush
