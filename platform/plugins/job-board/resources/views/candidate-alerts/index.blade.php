@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
@php
    $activeCandidateAlertTab = request('tab') === 'quick-add' ? 'quick-add' : 'alerts';
@endphp
<div class="row g-4">

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
                        Candidates receive a <strong>welcome message</strong> immediately on activation and
                        <strong>renewal reminders</strong> before expiry. Uses the active <em>Whapi automation</em> token.
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modal-discount-newsletter">
                            <i class="ti ti-discount me-1"></i> Discount Campaign
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-add-alert">
                            <i class="ti ti-plus me-1"></i> Add Alert
                        </button>
                    </div>
                </div>
            </x-core::card.body>
        </x-core::card>
    </div>

    {{-- Alerts table and settings --}}
    <div class="col-12">
        <ul class="nav nav-tabs mb-3" id="candidate-alert-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeCandidateAlertTab === 'alerts' ? 'active' : '' }}" id="candidate-alerts-tab" data-bs-toggle="tab" data-bs-target="#candidate-alerts-pane" type="button" role="tab" aria-controls="candidate-alerts-pane" aria-selected="{{ $activeCandidateAlertTab === 'alerts' ? 'true' : 'false' }}">
                    <i class="ti ti-list-details me-1"></i> Alerts
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeCandidateAlertTab === 'quick-add' ? 'active' : '' }}" id="candidate-alert-quick-add-tab" data-bs-toggle="tab" data-bs-target="#candidate-alert-quick-add-pane" type="button" role="tab" aria-controls="candidate-alert-quick-add-pane" aria-selected="{{ $activeCandidateAlertTab === 'quick-add' ? 'true' : 'false' }}">
                    <i class="ti ti-sparkles me-1"></i> Quick Add Options
                </button>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade {{ $activeCandidateAlertTab === 'alerts' ? 'show active' : '' }}" id="candidate-alerts-pane" role="tabpanel" aria-labelledby="candidate-alerts-tab">
        <div class="card has-actions">
            <div class="card-header">
                <div class="w-100 justify-content-between d-flex flex-wrap align-items-center gap-1">
                    <div class="d-flex flex-wrap flex-md-nowrap align-items-center gap-1">
                        <h5 class="mb-0 d-flex align-items-center gap-2 me-3">
                            <i class="ti ti-device-mobile-message text-primary"></i>
                            Candidate VIP Job Alert Subscriptions
                        </h5>
                        <form method="GET" action="{{ route('job-board.candidate-alerts.index') }}" id="alerts-search-form">
                            <div class="table-search-input">
                                <label>
                                    <input type="search" name="q" class="form-control input-sm"
                                        placeholder="Search…"
                                        style="min-width:160px"
                                        value="{{ request('q') }}">
                                    <button type="submit" title="Search" class="search-icon">
                                        <svg class="icon svg-icon-ti-ti-search" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path><path d="M21 21l-6 -6"></path></svg>
                                    </button>
                                    @if(request('q'))
                                    <a href="{{ route('job-board.candidate-alerts.index', ['per_page' => request('per_page', 20)]) }}"
                                        title="Clear" class="search-reset-icon" style="display:inline;">
                                        <svg class="icon svg-icon-ti-ti-x" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6l-12 12"></path><path d="M6 6l12 12"></path></svg>
                                    </a>
                                    @endif
                                    <input type="hidden" name="per_page" value="{{ request('per_page', 20) }}">
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-1 table-action-buttons">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-alert">
                            <svg class="icon svg-icon-ti-ti-plus" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg>
                            Add Alert
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-table">
                <div class="table-responsive table-has-actions">
                @if($alerts->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="ti ti-bell-off d-block mb-2 fs-1"></i>
                        <p class="mb-1 fw-semibold">No alerts configured yet</p>
                        <p class="small mb-3">Create your first VIP job alert for a candidate</p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-add-alert">
                            <i class="ti ti-plus me-1"></i> Add First Alert
                        </button>
                    </div>
                @else
                    <table class="table table-vcenter table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:42px"></th>
                                    <th>Candidate</th>
                                    <th>Alert / Filters</th>
                                    <th style="width:120px">Package</th>
                                    <th style="width:100px">Status</th>
                                    <th style="width:90px">Days Left</th>
                                    <th style="width:80px">Sent</th>
                                    <th style="width:230px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($alerts as $alert)
                                    @php
                                        $daysLeft   = $alert->daysRemaining();
                                        $isExpired  = $alert->status === 'expired';
                                        $isDisabled = ! $alert->is_active && ! $isExpired;
                                        $durInfo    = \Botble\JobBoard\Models\CandidateAlert::$durations[$alert->duration_days] ?? ['label' => $alert->duration_days . 'd', 'badge' => 'bg-secondary-subtle text-secondary'];
                                        $filters    = $alert->filters ?? [];
                                    @endphp
                                    <tr class="alert-row{{ $isExpired ? ' table-danger opacity-75' : '' }}" data-id="{{ $alert->id }}">
                                        <td>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input alert-toggle" type="checkbox" role="switch"
                                                    data-url="{{ route('job-board.candidate-alerts.toggle', $alert->id) }}"
                                                    {{ $alert->is_active ? 'checked' : '' }}
                                                    {{ $isExpired ? 'disabled' : '' }}
                                                    title="{{ $alert->is_active ? 'Active — click to disable' : 'Inactive — click to enable' }}">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold d-flex align-items-center gap-2">
                                                @if($alert->account?->avatar_thumb_url || $alert->account?->avatar_url)
                                                    <img src="{{ $alert->account?->avatar_thumb_url ?: $alert->account?->avatar_url }}" alt="" style="width:24px;height:24px;border-radius:999px;object-fit:cover;border:1px solid #e5e7eb">
                                                @endif
                                                <span>{{ $alert->candidate_name }}</span>
                                                @if($alert->account?->wakanda_verified)
                                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle" title="Wakanda Verified" style="width:16px;height:16px;background:#6f42c1;color:#fff;font-size:10px;line-height:1;">
                                                        <i class="ti ti-star-filled"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-muted small">
                                                <i class="ti ti-brand-whatsapp me-1" style="color:#25D366"></i>{{ $alert->candidate_phone }}
                                            </div>
                                            @if($alert->candidate_email)
                                                <div class="text-muted small"><i class="ti ti-mail me-1"></i>{{ $alert->candidate_email }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-truncate" style="max-width:200px" title="{{ $alert->label }}">{{ $alert->label }}</div>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @php
                                                    $kws     = array_filter(array_map('trim', (array)($filters['keywords'] ?? (($filters['keyword'] ?? null) ? [$filters['keyword']] : []))));
                                                    $ctrIds  = array_filter(array_map('intval', (array)($filters['country_ids'] ?? [])));
                                                @endphp
                                                @if(!empty($kws))
                                                    <span class="badge bg-light text-dark border small"><i class="fas fa-search me-1"></i>{{ Str::limit(implode(', ', $kws), 25) }}</span>
                                                @endif
                                                @if(!empty($ctrIds))
                                                    @php $ctrLabel = count($ctrIds) === 1 ? ($cities[reset($ctrIds)] ?? ($countries[reset($ctrIds)] ?? 'Country')) : count($ctrIds).' countries'; @endphp
                                                    <span class="badge bg-light text-dark border small"><i class="fas fa-globe me-1"></i>{{ $ctrLabel }}</span>
                                                @endif
                                                @if(!empty($filters['job_type_ids']))
                                                    @php $typeCount = count(array_filter((array)$filters['job_type_ids'])); @endphp
                                                    <span class="badge bg-primary-subtle text-primary border small"><i class="fas fa-briefcase me-1"></i>{{ $typeCount }} type(s)</span>
                                                @endif
                                                @if(!empty($filters['category_ids']))
                                                    @php $catCount = count(array_filter((array)$filters['category_ids'])); @endphp
                                                    <span class="badge bg-secondary-subtle text-secondary border small"><i class="fas fa-tags me-1"></i>{{ $catCount }} cat.</span>
                                                @endif
                                                @if(!empty($filters['job_experience_id']))
                                                    <span class="badge bg-light text-dark border small"><i class="fas fa-layer-group me-1"></i>{{ $experiences[$filters['job_experience_id']] ?? 'Exp' }}</span>
                                                @endif
                                                @if(empty($filters))
                                                    <span class="badge bg-light text-muted border small">All jobs</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $durInfo['badge'] }} fw-semibold">{{ $durInfo['label'] }}</span>
                                            <div class="text-muted small mt-1">K{{ number_format($alert->price, 0) }}</div>
                                        </td>
                                        <td>
                                            @if($isExpired)
                                                <span class="badge bg-danger-subtle text-danger alert-status-badge">Expired</span>
                                            @elseif(! $alert->is_active)
                                                <span class="badge bg-secondary-subtle text-secondary alert-status-badge">Disabled</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success alert-status-badge">Active</span>
                                            @endif
                                            @if($alert->expires_at)
                                                <div class="text-muted" style="font-size:.7rem" title="Expires {{ $alert->expires_at->format('d M Y') }}">
                                                    {{ $alert->expires_at->format('d M Y') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($isExpired)
                                                <span class="text-danger fw-bold">—</span>
                                            @elseif($daysLeft <= 3 && $daysLeft > 0)
                                                <span class="badge bg-warning text-dark fw-bold">{{ $daysLeft }}d</span>
                                            @elseif($daysLeft > 0)
                                                <span class="text-success fw-semibold">{{ $daysLeft }}d</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info-subtle text-info fw-semibold">{{ $alert->logs->count() }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <button type="button"
                                                    class="btn btn-outline-secondary"
                                                    title="Edit"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modal-edit-{{ $alert->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-view-logs"
                                                    title="View send logs"
                                                    data-url="{{ route('job-board.candidate-alerts.logs', $alert->id) }}"
                                                    data-name="{{ $alert->candidate_name }}">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-success btn-preview-jobs"
                                                    title="View matching jobs &amp; send"
                                                    data-url="{{ route('job-board.candidate-alerts.preview', $alert->id) }}"
                                                    data-send-url="{{ route('job-board.candidate-alerts.send-now', $alert->id) }}"
                                                    data-name="{{ $alert->candidate_name }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-warning btn-send-welcome"
                                                    title="Resend VIP welcome message via WhatsApp"
                                                    data-url="{{ route('job-board.candidate-alerts.send-welcome', $alert->id) }}"
                                                    data-name="{{ $alert->candidate_name }}">
                                                    <i class="fab fa-whatsapp"></i>
                                                </button>
                                                @if(! $alert->account_id && $alert->candidate_email)
                                                <button type="button"
                                                    class="btn btn-outline-dark btn-send-account-invite"
                                                    title="Invite candidate to create a Wakanda Jobs account"
                                                    data-url="{{ route('job-board.candidate-alerts.send-account-invite', $alert->id) }}"
                                                    data-name="{{ $alert->candidate_name }}">
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                                @endif
                                                @if($alert->hasStoredCv())
                                                <span class="btn btn-outline-info disabled" title="{{ $alert->hasLinkedAccountCv() ? 'CV is linked from the candidate account.' : 'CV on file — re-upload in Edit to re-analyse' }}">
                                                    <i class="fas fa-file-alt"></i>
                                                </span>
                                                @endif
                                                <button type="button"
                                                    class="btn btn-outline-danger btn-delete-alert"
                                                    title="Delete"
                                                    data-url="{{ route('job-board.candidate-alerts.destroy', $alert->id) }}"
                                                    data-name="{{ $alert->label }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <div class="dataTables_length">
                                <label>
                                    <span class="dt-length-style">
                                        <select class="form-select form-select-sm" onchange="window.location.href='{{ route('job-board.candidate-alerts.index') }}?q={{ request('q') }}&per_page='+this.value">
                                            @foreach([10, 20, 50, 100, 500] as $n)
                                                <option value="{{ $n }}" {{ request('per_page', 20) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                            @endforeach
                                        </select>
                                    </span>
                                </label>
                            </div>
                            <div class="m-0 text-muted">
                                <div class="dataTables_info" role="status" aria-live="polite">
                                    <span class="dt-length-records">
                                        <svg class="icon svg-icon-ti-ti-world" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path><path d="M3.6 9h16.8"></path><path d="M3.6 15h16.8"></path><path d="M11.5 3a17 17 0 0 0 0 18"></path><path d="M12.5 3a17 17 0 0 1 0 18"></path></svg>
                                        <span class="d-none d-sm-inline">Show from</span>
                                        {{ $alerts->total() > 0 ? $alerts->firstItem() : 0 }}
                                        to {{ $alerts->total() > 0 ? $alerts->lastItem() : 0 }} in
                                        <span class="badge bg-secondary text-secondary-fg">{{ $alerts->total() }}</span>
                                        <span class="hidden-xs">records</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if($alerts->hasPages())
                        <div class="d-flex justify-content-center">
                            <div class="dataTables_paginate paging_simple_numbers">
                                {{ $alerts->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                        @endif
                    </div>
                @endif
                </div>{{-- /table-responsive --}}
            </div>{{-- /card-table --}}
        </div>{{-- /card --}}
            </div>
            <div class="tab-pane fade {{ $activeCandidateAlertTab === 'quick-add' ? 'show active' : '' }}" id="candidate-alert-quick-add-pane" role="tabpanel" aria-labelledby="candidate-alert-quick-add-tab">
                <x-core::card>
                    <x-core::card.header>
                        <div>
                            <h5 class="mb-1 d-flex align-items-center gap-2">
                                <i class="ti ti-sparkles text-primary"></i>
                                Quick Add Keyword Options
                            </h5>
                            <p class="text-muted small mb-0">These groups appear in the Keywords Quick Add dropdown when adding or editing a VIP job alert.</p>
                        </div>
                    </x-core::card.header>
                    <x-core::card.body>
                        <form method="POST" action="{{ route('job-board.candidate-alerts.quick-add-presets.update') }}" id="quickAddPresetForm">
                            @csrf
                            @method('PUT')
                            <div id="quickAddPresetRows" class="d-flex flex-column gap-3">
                                @foreach($keywordPresets as $presetIndex => $preset)
                                    <div class="quick-add-preset-row border rounded p-3">
                                        <div class="row g-3 align-items-start">
                                            <div class="col-lg-4">
                                                <label class="form-label">Group Label</label>
                                                <input type="text" name="presets[{{ $presetIndex }}][label]" class="form-control" value="{{ $preset['label'] }}" maxlength="80" placeholder="e.g. Accounting & Finance">
                                            </div>
                                            <div class="col-lg-7">
                                                <label class="form-label">Keywords</label>
                                                <textarea name="presets[{{ $presetIndex }}][keywords]" class="form-control" rows="3" placeholder="One keyword per line or comma-separated">{{ implode("\n", $preset['keywords'] ?? []) }}</textarea>
                                                <div class="form-text">Saved keywords are de-duplicated. Empty groups are ignored.</div>
                                            </div>
                                            <div class="col-lg-1 d-flex justify-content-lg-end">
                                                <button type="button" class="btn btn-outline-danger btn-icon btn-remove-quick-add-preset mt-lg-4" title="Remove group" aria-label="Remove group">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                <button type="button" class="btn btn-outline-primary" id="addQuickAddPreset">
                                    <i class="ti ti-plus me-1"></i> Add Group
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i> Save Quick Add Options
                                </button>
                            </div>
                        </form>
                    </x-core::card.body>
                </x-core::card>
            </div>
        </div>
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
                        <i class="ti ti-plus me-1"></i> Create Alert & Send Welcome Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====================== EDIT MODALS ====================== --}}
@foreach($alerts as $alert)
<div class="modal fade" id="modal-edit-{{ $alert->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('job-board.candidate-alerts.update', $alert->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="ti ti-bell-check text-primary"></i> Edit: {{ $alert->label }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('plugins/job-board::candidate-alerts._form', ['alert' => $alert, 'prefix' => 'edit-' . $alert->id])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

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
                    <i class="fas fa-eye text-success"></i>
                    <span id="previewModalTitle">Matching Jobs</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="pv-send-progress" style="display:none;border-bottom:1px solid #dee2e6"></div>
                <div id="previewContent" class="p-3 text-center text-muted">
                    <i class="ti ti-loader-2 fa-spin fs-3 d-block mb-2"></i> Loading matching jobs…
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between flex-wrap gap-2">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="forceResendCheck">
                    <label class="form-check-label small" for="forceResendCheck">
                        Include already-sent jobs
                    </label>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnExportCsv" title="Download as CSV spreadsheet">
                        <i class="fas fa-file-csv me-1"></i> CSV
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="btnExportPdf" title="Print / Save as PDF">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                    <button type="button" class="btn btn-success" id="btnSendNow" data-send-url="">
                        <i class="fab fa-whatsapp me-1"></i> Send All New
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====================== DISCOUNT NEWSLETTER MODAL ====================== --}}
@php
$defaultDiscountSubject = 'Special Promotion — Get Job Alerts Directly to Your WhatsApp!';
$defaultDiscountBody = 'Hello, how are you?

We are currently running a special promotion that helps job seekers receive personalized job opportunities directly in their inbox.

Instead of checking multiple websites, social media pages, and job groups every day, our system does the work for you. Simply tell us the types of jobs you\'re interested in.

For example, if you\'re looking for opportunities in Nursing and Accounting, our system continuously scans the Zambian job market and immediately sends you relevant vacancies, application details, and direct application links as soon as they become available.

Benefits include:
• Instant job notifications as soon as vacancies are posted.
• Personalized alerts tailored to your preferred profession.
• No unnecessary messages about jobs that don\'t match your interests.
• Saves you time and ensures you never miss an opportunity.

Our promotional rates are:
• K20 per week
• K50 per month

Would this be something you\'d be interested in?

Reply to this email or WhatsApp us to sign up today!

— Wakanda Jobs Team
wakandajobs.com';
@endphp
<div class="modal fade" id="modal-discount-newsletter" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning bg-opacity-10">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-discount text-warning"></i>
                    Send Discount Campaign to Newsletter Subscribers
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 px-3 mb-3 small">
                    <i class="fas fa-info-circle me-1"></i>
                    This will email <strong>all subscribed newsletter contacts</strong> with your promotional offer.
                    Edit the subject and body below before sending.
                </div>
                <div id="discount-send-error" class="alert alert-danger d-none py-2 px-3 small mb-3"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Subject <span class="text-danger">*</span></label>
                    <input type="text" id="discount-subject" class="form-control"
                        value="{{ $defaultDiscountSubject }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Body <span class="text-danger">*</span></label>
                    <textarea id="discount-body" class="form-control" rows="18" style="font-family:monospace;font-size:.85rem">{{ $defaultDiscountBody }}</textarea>
                    <div class="form-text">Plain text. Newlines become line breaks in the email.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="btnSendDiscountNewsletter"
                    data-url="{{ route('job-board.candidate-alerts.send-discount-newsletter') }}">
                    <i class="ti ti-send me-1"></i> Send to All Subscribers
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ====================== DELETE MODAL ====================== --}}
<div class="modal fade" id="modal-delete-alert" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4 px-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                        <i class="ti ti-trash text-danger fs-3"></i>
                    </span>
                </div>
                <h6 class="fw-semibold mb-1">Delete this alert?</h6>
                <p class="text-muted small mb-4" id="deleteAlertLabel">This will also delete all send logs.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" id="confirmDeleteAlert">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('header')
<style>
@media (max-width: 767.98px) {
    .alert-action-btns .btn {
        padding: .45rem .6rem;
        font-size: 1rem;
        min-width: 38px;
        min-height: 38px;
    }
    .alert-action-btns {
        gap: .35rem !important;
    }
}
.quick-add-preset-row textarea {
    min-height: 96px;
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

    // ── Quick Add preset settings ────────────────────────────────────────────

    let quickAddPresetIndex = {{ count($keywordPresets) }};

    $('#addQuickAddPreset').on('click', function () {
        const index = quickAddPresetIndex++;
        const row = `
            <div class="quick-add-preset-row border rounded p-3">
                <div class="row g-3 align-items-start">
                    <div class="col-lg-4">
                        <label class="form-label">Group Label</label>
                        <input type="text" name="presets[${index}][label]" class="form-control" maxlength="80" placeholder="e.g. Accounting & Finance">
                    </div>
                    <div class="col-lg-7">
                        <label class="form-label">Keywords</label>
                        <textarea name="presets[${index}][keywords]" class="form-control" rows="3" placeholder="One keyword per line or comma-separated"></textarea>
                        <div class="form-text">Saved keywords are de-duplicated. Empty groups are ignored.</div>
                    </div>
                    <div class="col-lg-1 d-flex justify-content-lg-end">
                        <button type="button" class="btn btn-outline-danger btn-icon btn-remove-quick-add-preset mt-lg-4" title="Remove group" aria-label="Remove group">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>
            </div>`;

        $('#quickAddPresetRows').append(row).find('.quick-add-preset-row:last input').trigger('focus');
    });

    $(document).on('click', '.btn-remove-quick-add-preset', function () {
        $(this).closest('.quick-add-preset-row').remove();
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

    // Clear warning when user starts retyping
    $(document).on('input', '.phone-check-input', function () {
        $(this).closest('.col-md-6').find('.phone-check-warning').hide();
    });

    // ── Double-submit prevention ─────────────────────────────────────────────
    // Runs on every modal form submit. Disables the button immediately and shows
    // a spinner so the user knows it's saving. The button re-enables if the
    // server returns a validation error (new page load resets state).
    $(document).on('submit', '.modal form', function () {
        const $form = $(this);

        // Bail if already mid-flight (catches double-click within same page lifecycle)
        if ($form.data('submitting')) {
            return false;
        }

        $form.data('submitting', true);

        const $btn = $form.find('button[type="submit"]');
        $btn.prop('disabled', true)
            .data('original-html', $btn.html())
            .html('<i class="fas fa-spinner fa-spin me-1"></i> Saving…');

        // Safety re-enable after 8 s in case of network issue (browser won't navigate)
        setTimeout(() => {
            $form.data('submitting', false);
            $btn.prop('disabled', false).html($btn.data('original-html'));
        }, 8000);
    });

    // Toggle
    $(document).on('change', '.alert-toggle', function () {
        const $toggle = $(this);
        const $row    = $toggle.closest('.alert-row');
        const $badge  = $row.find('.alert-status-badge');
        const url     = $toggle.data('url');
        const active  = $toggle.is(':checked');

        $httpClient.make().post(url)
            .then(({ data: resp }) => {
                const isActive = resp.data?.is_active ?? active;
                const welcomeSent = resp.data?.welcome_sent ?? false;
                const welcomeError = resp.data?.welcome_error || null;
                $badge.text(isActive ? 'Active' : 'Disabled')
                    .removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary bg-danger-subtle text-danger')
                    .addClass(isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary');
                $toggle.prop('checked', isActive);
                if (isActive) {
                    if (welcomeSent) Botble.showSuccess('Alert enabled. Welcome message sent.');
                    else Botble.showError(welcomeError || 'Alert enabled, but welcome message failed.');
                } else {
                    Botble.showSuccess('Alert disabled.');
                }
            })
            .catch(() => {
                $toggle.prop('checked', !active);
                Botble.showError('Could not update alert.');
            });
    });

    // -- Logs modal --
    $(document).on('click', '.btn-view-logs', function () {
        const $btn = $(this);
        const url  = $btn.data('url');
        const name = $btn.data('name');

        $('#logsModalTitle').text('Send Logs — ' + name);
        $('#logsContent').html('<div class="p-3 text-center text-muted"><i class="ti ti-loader-2 fa-spin fs-3 d-block mb-2"></i> Loading...</div>');

        new bootstrap.Modal(document.getElementById('modal-logs')).show();

        $httpClient.make().get(url)
            .then(({ data: resp }) => {
                const logs = resp.data || [];
                if (!logs.length) {
                    $('#logsContent').html('<div class="p-4 text-center text-muted"><i class="ti ti-inbox d-block fs-2 mb-2"></i>No jobs sent yet.</div>');
                    return;
                }

                let html = '<table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th>#</th><th>Job</th><th>Company</th><th>Status</th><th>Sent At</th></tr></thead><tbody>';
                logs.forEach((log, i) => {
                    const statusClass = log.status === 'sent' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                    html += `<tr>
                        <td class="text-muted small">${i+1}</td>
                        <td><span class="fw-semibold">${escHtml(log.job_name)}</span>${log.error ? '<div class="text-danger small">' + escHtml(log.error) + '</div>' : ''}</td>
                        <td class="text-muted small">${escHtml(log.company)}</td>
                        <td><span class="badge ${statusClass}">${escHtml(log.status)}</span></td>
                        <td class="text-muted small">${escHtml(log.sent_at || '')}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
                $('#logsContent').html(html);
            })
            .catch(() => {
                $('#logsContent').html('<div class="p-4 text-center text-danger">Failed to load logs.</div>');
            });
    });

    // -- Preview & Send modal --
    // State hoisted to module scope so Export/PDF buttons can access getFilteredJobs()
    let previewAllJobs = [];
    let previewPage    = 1;
    const PREVIEW_PER_PAGE = 25;

    $(document).on('click', '.btn-preview-jobs', function () {
        const $btn    = $(this);
        const url     = $btn.data('url');
        const sendUrl = $btn.data('send-url');
        const name    = $btn.data('name');

        $('#previewModalTitle').text('Matching Jobs — ' + name);
        $('#previewContent').html('<div class="p-3 text-center text-muted"><i class="ti ti-loader-2 fa-spin fs-3 d-block mb-2"></i> Searching matching jobs…</div>');
        $('#btnSendNow').data('send-url', sendUrl);
        $('#forceResendCheck').prop('checked', false);

        new bootstrap.Modal(document.getElementById('modal-preview')).show();

        $httpClient.make().get(url)
            .then(({ data: resp }) => {
                previewAllJobs = resp.data || [];
                const total    = resp.total || 0;

                if (!previewAllJobs.length) {
                    $('#previewContent').html('<div class="p-4 text-center text-muted"><i class="fas fa-search d-block fs-2 mb-2"></i>No matching jobs found.</div>');
                    return;
                }

                // Build unique country list for dropdown
                const countries = [...new Set(previewAllJobs.map(j => j.country).filter(Boolean))].sort();
                const countryOpts = countries.map(c => `<option value="${escHtml(c)}">${escHtml(c)}</option>`).join('');

                const filtersBar = `
                    <div class="d-flex align-items-center gap-2 flex-wrap px-3 pt-3 pb-2 border-bottom bg-light">
                        <select id="pv-country" class="form-select form-select-sm" style="max-width:160px">
                            <option value="">All Countries</option>${countryOpts}
                        </select>
                        <input type="text" id="pv-company" class="form-control form-control-sm" placeholder="Filter by company…" style="max-width:200px">
                        <select id="pv-period" class="form-select form-select-sm" style="max-width:150px">
                            <option value="">Any Date</option>
                            <option value="today">Today</option>
                            <option value="7">Last 7 Days</option>
                            <option value="14">Last 14 Days</option>
                            <option value="30">Last 30 Days</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="pv-clear-filters">
                            <i class="fas fa-times me-1"></i>Clear
                        </button>
                        <span id="pv-count-label" class="text-muted small ms-auto"></span>
                    </div>
                    <div id="pv-table-wrap"></div>
                    <div id="pv-pagination" class="d-flex align-items-center justify-content-between px-3 py-2 border-top"></div>`;

                $('#previewContent').html(filtersBar);
                previewPage = 1;
                renderPreviewTable(total);

                // Filter events
                $('#pv-country, #pv-period').on('change', () => { previewPage = 1; renderPreviewTable(total); });
                $('#pv-company').on('input', () => { previewPage = 1; renderPreviewTable(total); });
                $('#pv-clear-filters').on('click', () => {
                    $('#pv-country').val('');
                    $('#pv-company').val('');
                    $('#pv-period').val('');
                    previewPage = 1;
                    renderPreviewTable(total);
                });
            })
            .catch(() => {
                $('#previewContent').html('<div class="p-4 text-center text-danger">Failed to load matching jobs.</div>');
            });

        // Page button clicks (delegated since pager re-renders)
        $(document).on('click', '.pv-page-btn:not([disabled])', function () {
            previewPage = parseInt($(this).data('p'));
            renderPreviewTable(previewAllJobs.length);
            $('#pv-table-wrap')[0]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // ── getFilteredJobs + renderPreviewTable at module scope ─────────────────
    // (must be outside the click handler so Export CSV/PDF can call them)

    function getFilteredJobs() {
        const country = $('#pv-country').val() || '';
        const company = ($('#pv-company').val() || '').toLowerCase().trim();
        const period  = $('#pv-period').val() || '';

        const today  = new Date(); today.setHours(0,0,0,0);
        const cutoff = period === 'today' ? today
            : period ? new Date(today - parseInt(period) * 86400000) : null;

        const filtered = previewAllJobs.filter(job => {
            if (country && job.country !== country) return false;
            if (company && !(job.company || '').toLowerCase().includes(company)) return false;
            if (cutoff && job.created_date && new Date(job.created_date) < cutoff) return false;
            return true;
        });

        return filtered.sort((a, b) => {
            const da = a.deadline_days !== null && a.deadline_days !== undefined ? a.deadline_days : Infinity;
            const db = b.deadline_days !== null && b.deadline_days !== undefined ? b.deadline_days : Infinity;
            return db - da;
        });
    }

    function renderPreviewTable(serverTotal) {
        const filtered = getFilteredJobs();
        const total    = filtered.length;
        const pages    = Math.max(1, Math.ceil(total / PREVIEW_PER_PAGE));
        previewPage    = Math.min(previewPage, pages);
        const start    = (previewPage - 1) * PREVIEW_PER_PAGE;
        const slice    = filtered.slice(start, start + PREVIEW_PER_PAGE);

        const isFiltered = total !== previewAllJobs.length;
        $('#pv-count-label').text(
            isFiltered ? `${total} of ${serverTotal} jobs match filters` : `${serverTotal} matching job(s) total`
        );

        let html = '<table class="table table-sm table-hover align-middle mb-0">'
            + '<thead class="table-light"><tr>'
            + '<th style="width:36px">#</th><th>Job Title</th><th>Company</th><th>Location</th><th>Country</th><th>Posted</th><th>Closes</th><th class="text-center" style="width:70px">Status</th><th class="text-center" style="width:80px">Send</th>'
            + '</tr></thead><tbody>';

        if (!slice.length) {
            html += '<tr><td colspan="9" class="text-center text-muted py-4">No jobs match your filters.</td></tr>';
        } else {
            slice.forEach((job, idx) => {
                const rowNum    = start + idx + 1;
                const sentBadge = job.already_sent
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle">✓ Sent</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary border">New</span>';

                let deadlineBadge = '<span class="text-muted small">—</span>';
                if (job.deadline_days !== null && job.deadline_days !== undefined) {
                    const d = job.deadline_days;
                    deadlineBadge = d < 0   ? '<span class="badge bg-danger text-white">Expired</span>'
                        : d === 0           ? '<span class="badge bg-danger text-white">Today</span>'
                        : d <= 3            ? `<span class="badge bg-warning text-dark" title="${escHtml(job.deadline)}">${d}d left</span>`
                        : d <= 14           ? `<span class="badge bg-info text-white" title="${escHtml(job.deadline)}">${d}d left</span>`
                        :                     `<span class="text-muted small text-nowrap" title="${escHtml(job.deadline)}">${d}d</span>`;
                }

                const sendBtn = job.already_sent
                    ? `<button type="button" class="btn btn-sm btn-outline-secondary pv-send-one-btn" data-job-id="${job.id}" title="Resend this job">
                           <i class="fab fa-whatsapp"></i>
                       </button>`
                    : `<button type="button" class="btn btn-sm btn-success pv-send-one-btn" data-job-id="${job.id}" title="Send this job now">
                           <i class="fab fa-whatsapp"></i>
                       </button>`;

                html += `<tr data-job-id="${job.id}">
                    <td class="text-muted small text-center">${rowNum}</td>
                    <td class="fw-semibold" style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${escHtml(job.name)}">${escHtml(job.name)}</td>
                    <td class="text-muted small"><div class="d-flex align-items-center gap-2">${job.company_logo ? `<img src="${escHtml(job.company_logo)}" alt="" style="width:26px;height:26px;object-fit:contain;border-radius:4px;border:1px solid #eee;background:#fff;padding:2px">` : ''}<span>${escHtml(job.company)}</span></div></td>
                    <td class="text-muted small">${escHtml(job.location || '')}</td>
                    <td class="text-muted small">${escHtml(job.country || '')}</td>
                    <td class="text-muted small text-nowrap">${escHtml(job.created)}</td>
                    <td class="text-nowrap">${deadlineBadge}</td>
                    <td class="text-center">${sentBadge}</td>
                    <td class="text-center">${sendBtn}</td>
                </tr>`;
            });
        }
        html += '</tbody></table>';
        $('#pv-table-wrap').html(html);

        if (total <= PREVIEW_PER_PAGE) {
            $('#pv-pagination').empty();
        } else {
            const showing = `Showing ${start + 1}–${Math.min(start + PREVIEW_PER_PAGE, total)} of ${total}`;
            let pager = `<span class="text-muted small">${showing}</span><div class="d-flex gap-1">`;
            pager += `<button class="btn btn-sm btn-outline-secondary pv-page-btn" data-p="1" ${previewPage===1?'disabled':''}>«</button>`;
            pager += `<button class="btn btn-sm btn-outline-secondary pv-page-btn" data-p="${previewPage-1}" ${previewPage===1?'disabled':''}>‹ Prev</button>`;
            for (let p = Math.max(1, previewPage-2); p <= Math.min(pages, previewPage+2); p++) {
                pager += `<button class="btn btn-sm ${p===previewPage?'btn-primary text-white':'btn-outline-secondary'} pv-page-btn" data-p="${p}">${p}</button>`;
            }
            pager += `<button class="btn btn-sm btn-outline-secondary pv-page-btn" data-p="${previewPage+1}" ${previewPage===pages?'disabled':''}>Next ›</button>`;
            pager += `<button class="btn btn-sm btn-outline-secondary pv-page-btn" data-p="${pages}" ${previewPage===pages?'disabled':''}>»</button></div>`;
            $('#pv-pagination').html(pager);
        }
    }

    // ── Per-row single job send ───────────────────────────────────────────────
    $(document).on('click', '.pv-send-one-btn', function () {
        const $btn   = $(this);
        const jobId  = parseInt($btn.data('job-id'));
        const sendUrl = $('#btnSendNow').data('send-url');
        const job    = previewAllJobs.find(j => j.id === jobId);

        if (!sendUrl || !job) return;

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $httpClient.make().post(sendUrl, { force_resend: 1, job_ids: [jobId] }, { timeout: 30000 })
            .then(() => {
                job.already_sent = true;
                // Update status badge in same row
                const $row = $(`tr[data-job-id="${jobId}"]`);
                $row.find('td:nth-child(8)').html('<span class="badge bg-success-subtle text-success border border-success-subtle">✓ Sent</span>');
                // Change button to outline (resend)
                $btn.prop('disabled', false)
                    .removeClass('btn-success').addClass('btn-outline-secondary')
                    .html('<i class="fab fa-whatsapp"></i>')
                    .attr('title', 'Resend this job');
                Botble.showSuccess(`Sent: ${escHtml(job.name)}`);
            })
            .catch(() => {
                $btn.prop('disabled', false).html('<i class="fab fa-whatsapp"></i>');
                Botble.showError('Failed to send. Check Whapi configuration.');
            });
    });

    // Send Now button — batched parallel sends (3 concurrent) with per-row animation
    $('#btnSendNow').on('click', async function () {
        const $btn        = $(this);
        const sendUrl     = $btn.data('send-url');
        const forceResend = $('#forceResendCheck').is(':checked');
        const BATCH       = 3;   // concurrent requests per round
        const BATCH_GAP   = 400; // ms pause between batches (Whapi rate-limit headroom)

        const jobsToSend = forceResend
            ? [...previewAllJobs]
            : previewAllJobs.filter(j => !j.already_sent);

        if (!jobsToSend.length) {
            Botble.showError('No new jobs to send.');
            return;
        }

        const total = jobsToSend.length;
        let done = 0, sent = 0, failed = 0;
        const failedJobs = [];

        $btn.prop('disabled', true).html('<i class="fab fa-whatsapp me-1"></i> Sending…');
        $('#btnExportCsv, #btnExportPdf, #forceResendCheck, #pv-country, #pv-company, #pv-period, #pv-clear-filters').prop('disabled', true);

        $('#pv-send-progress').html(`
            <div class="px-3 pt-3 pb-2">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="small fw-semibold" id="pv-prog-label">Sending 0 of ${total}…</span>
                    <span class="small text-muted" id="pv-prog-counts">0 sent · 0 failed</span>
                </div>
                <div class="progress mb-1" style="height:8px">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                         id="pv-prog-bar" role="progressbar" style="width:0%;transition:width .4s ease"></div>
                </div>
                <div class="text-muted small text-truncate" id="pv-prog-current" style="min-height:1.3em"></div>
            </div>
        `).show();

        $('#pv-send-failures').remove();

        const renderFailedJobs = () => {
            $('#pv-send-failures').remove();
            if (!failedJobs.length) return;

            const items = failedJobs.map((item, index) => `
                <tr>
                    <td class="text-muted small">${index + 1}</td>
                    <td class="fw-semibold">${escHtml(item.name)}</td>
                    <td class="text-danger small">${escHtml(item.error || 'Failed to send')}</td>
                </tr>
            `).join('');

            $('#pv-send-progress').after(`
                <div id="pv-send-failures" class="border-bottom">
                    <div class="px-3 pt-2 pb-3">
                        <div class="alert alert-danger py-2 px-3 mb-2 small">
                            <strong>${failedJobs.length} failed job(s)</strong> could not be sent. Review the reasons below.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th style="width:36px">#</th><th>Job</th><th>Reason</th></tr>
                                </thead>
                                <tbody>${items}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `);
        };

        // Helper: send one job, animate its row out on success
        const sendOne = async (job) => {
            $('#pv-prog-current').html(
                `<i class="fas fa-paper-plane me-1 text-success"></i>${escHtml(job.name)}`
            );
            try {
                const { data } = await $httpClient.make().post(sendUrl, {
                    force_resend: forceResend ? 1 : 0,
                    job_ids: [job.id],
                }, { timeout: 30000 });

                sent++;
                job.already_sent = true;

                const $row = $(`tr[data-job-id="${job.id}"]`);
                if ($row.length) {
                    $row.css({ transition: 'opacity .3s ease, transform .3s ease', opacity: 0, transform: 'translateX(40px)' });
                    setTimeout(() => $row.remove(), 320);
                }
                if (Array.isArray(data?.failed_jobs) && data.failed_jobs.length) {
                    data.failed_jobs.forEach(item => failedJobs.push({
                        id: item.job_id,
                        name: item.job_name || job.name,
                        error: item.error || 'Failed to send',
                    }));
                }
            } catch (error) {
                failed++;
                const response = error?.response?.data || {};
                const failedItem = Array.isArray(response.failed_jobs) && response.failed_jobs.length
                    ? response.failed_jobs[0]
                    : null;
                failedJobs.push({
                    id: failedItem?.job_id || job.id,
                    name: failedItem?.job_name || job.name,
                    error: failedItem?.error || response.error || 'Failed to send',
                });

                const $row = $(`tr[data-job-id="${job.id}"]`);
                if ($row.length) {
                    $row.find('td:nth-child(8)').html('<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Failed</span>');
                }
            }

            done++;
            const pct = Math.round((done / total) * 100);
            $('#pv-prog-bar').css('width', pct + '%');
            $('#pv-prog-label').text(`Sending ${Math.min(done + BATCH, total)} of ${total}…`);
            $('#pv-prog-counts').text(`${sent} sent · ${failed} failed`);
            renderFailedJobs();
        };

        // Process in batches of BATCH, with a short gap between rounds
        for (let i = 0; i < jobsToSend.length; i += BATCH) {
            const batch = jobsToSend.slice(i, i + BATCH);
            await Promise.all(batch.map(sendOne));
            if (i + BATCH < jobsToSend.length) {
                await new Promise(r => setTimeout(r, BATCH_GAP));
            }
        }

        // Finished
        $('#pv-prog-label').text(`Done — ${sent} sent${failed ? `, ${failed} failed` : ''}.`);
        $('#pv-prog-current').html('');
        $('#pv-prog-bar').removeClass('progress-bar-animated progress-bar-striped').css('width', '100%');
        renderFailedJobs();

        failed ? Botble.showError(`${sent} sent, ${failed} failed.`) : Botble.showSuccess(`${sent} job(s) sent successfully.`);

        $btn.prop('disabled', false).html('<i class="fab fa-whatsapp me-1"></i> Send All New');
        $('#btnExportCsv, #btnExportPdf, #forceResendCheck, #pv-country, #pv-company, #pv-period, #pv-clear-filters').prop('disabled', false);

        if (!failed) {
            setTimeout(() => location.reload(), 1500);
        }
    });

    // ── Resend welcome message ────────────────────────────────────────────────
    $(document).on('click', '.btn-send-welcome', function () {
        const $btn = $(this);
        const url  = $btn.data('url');
        const name = $btn.data('name');

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $httpClient.make().post(url)
            .then(({ data: resp }) => {
                Botble.showSuccess(resp.message || 'Welcome message sent to ' + name + '.');
            })
            .catch(({ response }) => {
                const err = response?.data?.error || 'Failed to send. Check Whapi configuration.';
                Botble.showError(err);
            })
            .finally(() => {
                $btn.prop('disabled', false).html('<i class="fab fa-whatsapp"></i>');
            });
    });

    // -- Delete --
    const $deleteModal = new bootstrap.Modal(document.getElementById('modal-delete-alert'));
    let pendingDeleteUrl = null;
    let pendingDeleteRow = null;

    $(document).on('click', '.btn-delete-alert', function () {
        pendingDeleteUrl = $(this).data('url');
        pendingDeleteRow = $(this).closest('.alert-row');
        $('#deleteAlertLabel').text($(this).data('name') + ' — This will delete all send logs too.');
        $deleteModal.show();
    });

    $('#confirmDeleteAlert').on('click', function () {
        if (! pendingDeleteUrl) return;

        $httpClient.make().delete(pendingDeleteUrl)
            .then(() => {
                pendingDeleteRow?.fadeOut(200, () => pendingDeleteRow.remove());
                Botble.showSuccess('Alert deleted.');
                $deleteModal.hide();
                pendingDeleteUrl = null;
            })
            .catch(() => Botble.showError('Could not delete alert.'));
    });

    // ── Export CSV ─────────────────────────────────────────────────────────────
    $(document).on('click', '#btnExportCsv', function () {
        const filtered = getFilteredJobs();
        if (!filtered.length) { Botble.showError('No jobs to export.'); return; }

        const headers = ['#', 'Job Title', 'Company', 'City', 'Province/State', 'Country', 'Posted', 'Closing Date', 'Days Left', 'Status'];
        const rows = filtered.map((job, idx) => [
            idx + 1,
            job.name,
            job.company,
            job.city  || '',
            job.state || '',
            job.country || '',
            job.created,
            job.deadline || '',
            job.deadline_days !== null && job.deadline_days !== undefined
                ? (job.deadline_days < 0 ? 'Expired' : job.deadline_days + 'd')
                : '',
            job.already_sent ? 'Sent' : 'New',
        ]);

        const csv  = [headers, ...rows]
            .map(r => r.map(c => '"' + String(c || '').replace(/"/g, '""') + '"').join(','))
            .join('\n');

        // BOM (﻿) ensures Excel opens UTF-8 correctly
        const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'vip-alert-jobs-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        setTimeout(() => { document.body.removeChild(a); URL.revokeObjectURL(url); }, 100);
        Botble.showSuccess('Exported ' + filtered.length + ' job(s) to CSV.');
    });

    // ── Export PDF (print-to-PDF) ──────────────────────────────────────────────
    $(document).on('click', '#btnExportPdf', function () {
        const filtered = getFilteredJobs();
        if (!filtered.length) { Botble.showError('No jobs to export.'); return; }

        const title   = $('#previewModalTitle').text() || 'VIP Alert — Matching Jobs';
        const date    = new Date().toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });

        const rows = filtered.map((job, idx) => {
            const rowNum = idx + 1;
            const d   = job.deadline_days;
            const dLabel = d === null || d === undefined ? '—'
                : d < 0   ? '<span style="color:#dc3545;font-weight:600">Expired</span>'
                : d === 0 ? '<span style="color:#dc3545;font-weight:600">Today</span>'
                : d <= 3  ? `<span style="color:#fd7e14;font-weight:600">${d}d left</span>`
                : d <= 14 ? `<span style="color:#0dcaf0;font-weight:600">${d}d left</span>`
                : `${d}d`;

            const logo = job.company_logo
                ? `<img src="${escHtml(job.company_logo)}" style="width:22px;height:22px;object-fit:contain;vertical-align:middle;margin-right:5px;border:1px solid #eee;border-radius:3px">`
                : '';

            const sentBadge = job.already_sent
                ? '<span style="background:#198754;color:#fff;padding:1px 6px;border-radius:3px;font-size:11px">✓ Sent</span>'
                : '<span style="background:#6c757d;color:#fff;padding:1px 6px;border-radius:3px;font-size:11px">New</span>';

            return `<tr>
                <td style="text-align:center;color:#999;font-size:10px">${rowNum}</td>
                <td style="max-width:220px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis" title="${escHtml(job.name)}">${escHtml(job.name)}</td>
                <td>${logo}${escHtml(job.company)}</td>
                <td>${escHtml(job.country || '')}</td>
                <td>${escHtml(job.created)}</td>
                <td>${dLabel}</td>
                <td>${sentBadge}</td>
            </tr>`;
        }).join('');

        const html = `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>${escHtml(title)}</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
  h2   { font-size: 15px; margin-bottom: 4px; }
  p    { color: #666; font-size: 10px; margin-bottom: 12px; }
  table { width: 100%; border-collapse: collapse; }
  th   { background: #f8f9fa; border: 1px solid #dee2e6; padding: 5px 7px; text-align: left; font-size: 10px; }
  td   { border: 1px solid #dee2e6; padding: 5px 7px; vertical-align: middle; font-size: 11px; }
  tr:nth-child(even) td { background: #f8f9fa; }
  @media print { body { margin: 10px; } }
</style>
</head>
<body>
<h2>${escHtml(title)}</h2>
<p>Generated: ${date} &nbsp;·&nbsp; ${filtered.length} job(s)</p>
<table>
<thead><tr><th>#</th><th>Job Title</th><th>Company</th><th>Country</th><th>Posted</th><th>Closes</th><th>Status</th></tr></thead>
<tbody>${rows}</tbody>
</table>
</body>
</html>`;

        const w = window.open('', '_blank', 'width=900,height=700');
        w.document.write(html);
        w.document.close();
        w.focus();
        setTimeout(() => { w.print(); }, 400);
    });

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Discount Newsletter Send ──────────────────────────────────────────────
    $('#btnSendDiscountNewsletter').on('click', function () {
        const $btn  = $(this);
        const url   = $btn.data('url');
        const subject = $('#discount-subject').val().trim();
        const body    = $('#discount-body').val().trim();

        $('#discount-send-error').addClass('d-none').text('');

        if (!subject || !body) {
            $('#discount-send-error').removeClass('d-none').text('Subject and body are required.');
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Sending…');

        $httpClient.make().post(url, { subject, body })
            .then(({ data: resp }) => {
                Botble.showSuccess(resp.message || 'Discount campaign queued successfully.');
                bootstrap.Modal.getInstance(document.getElementById('modal-discount-newsletter'))?.hide();
            })
            .catch(({ response }) => {
                const err = response?.data?.error || 'Failed to queue discount campaign.';
                $('#discount-send-error').removeClass('d-none').text(err);
                Botble.showError(err);
            })
            .finally(() => {
                $btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> Send to All Subscribers');
            });
    });

    // ── Cascading location: Country → State → City ────────────────────────────

    // Country changed → load states, show/hide buttons
    $(document).on('change', '.loc-country', function () {
        const tid        = $(this).data('tid');
        const countryId  = $(this).val();
        const statesUrl  = $(this).data('states-url');
        const $stateRow  = $('.loc-state-row-' + tid);
        const $stateEl   = $stateRow.find('.loc-state');
        const $cityRow   = $('.loc-city-search-row-' + tid);
        const $results   = $('.loc-city-results-' + tid);
        const $allCountryBtn = $(this).siblings('.loc-add-all-country');

        // Reset downstream
        $stateEl.html('<option value="">— Select State —</option>').val('');
        $cityRow.hide();
        $results.hide();
        $stateRow.find('.loc-add-all-state').hide();
        $allCountryBtn.hide();

        if (! countryId) { $stateRow.hide(); return; }

        $allCountryBtn.show();
        $stateRow.show();
        $stateEl.prop('disabled', true);

        fetch(statesUrl + '?country_id=' + countryId)
            .then(r => r.json())
            .then(states => {
                let opts = '<option value="">— Select State —</option>';
                states.forEach(s => { opts += `<option value="${s.id}">${escHtml(s.name)}</option>`; });
                $stateEl.html(opts).prop('disabled', false);
            })
            .catch(() => { $stateEl.prop('disabled', false); });
    });

    // State changed → show city search + "All in State" button
    $(document).on('change', '.loc-state', function () {
        const tid        = $(this).data('tid');
        const stateId    = $(this).val();
        const $cityRow   = $('.loc-city-search-row-' + tid);
        const $input     = $cityRow.find('.loc-city-input');
        const $results   = $('.loc-city-results-' + tid);
        const $allStateBtn = $(this).closest('.d-flex').find('.loc-add-all-state');

        $input.val('');
        $results.hide();

        if (stateId) {
            $cityRow.show();
            $allStateBtn.show();
            $input.focus();
        } else {
            $cityRow.hide();
            $allStateBtn.hide();
        }
    });

    // "Add all cities in State"
    $(document).on('click', '.loc-add-all-state', function () {
        const $btn      = $(this);
        const tid       = $btn.data('tid');
        const citiesUrl = $btn.data('cities-url');
        const $stateEl  = $btn.closest('.d-flex').find('.loc-state');
        const stateId   = $stateEl.val();
        const stateName = $stateEl.find('option:selected').text();

        if (! stateId) return;

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Loading…');

        fetch(citiesUrl + '?state_id=' + stateId + '&all=1')
            .then(r => r.json())
            .then(resp => {
                const cities = resp.data || [];
                const added  = bulkAddCities(tid, cities);
                Botble.showSuccess('Added ' + added + ' cities from ' + stateName + '.');
            })
            .catch(() => Botble.showError('Failed to load cities.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i> All in State'));
    });

    // "Add all cities in Country"
    $(document).on('click', '.loc-add-all-country', function () {
        const $btn       = $(this);
        const tid        = $btn.data('tid');
        const citiesUrl  = $btn.data('cities-url');
        const $countryEl = $btn.siblings('.loc-country');
        const countryId  = $countryEl.val();
        const countryName = $countryEl.find('option:selected').text();

        if (! countryId) return;

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Loading…');

        fetch(citiesUrl + '?country_id=' + countryId + '&all=1')
            .then(r => r.json())
            .then(resp => {
                const cities = resp.data || [];
                const added  = bulkAddCities(tid, cities);
                Botble.showSuccess('Added ' + added + ' cities from ' + countryName + '.' + (cities.length >= 500 ? ' (first 500)' : ''));
            })
            .catch(() => Botble.showError('Failed to load cities.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-globe me-1"></i> All in Country'));
    });

    // Shared helper: add an array of {id, name} cities as chips, skip duplicates
    function bulkAddCities(tid, cities) {
        const existing = getSelectedCityIds(tid);
        let added = 0;
        cities.forEach(city => {
            if (existing.includes(city.id)) return;
            const chip = `<span class="badge bg-primary text-white d-flex align-items-center gap-1 loc-chip" style="font-size:.8rem">
                ${escHtml(city.name)}
                <button type="button" class="btn-close btn-close-white btn-sm p-0 loc-chip-remove"
                    style="font-size:.6rem" data-city-id="${city.id}" data-tid="${tid}"></button>
                <input type="hidden" name="filters[city_ids][]" value="${city.id}" class="loc-hidden-${tid}">
            </span>`;
            $('.loc-chips-' + tid).append(chip);
            existing.push(city.id);
            added++;
        });
        updateCityCountBadge(tid);
        // Refresh visible results list to show "Added" badges
        const $list = $('.loc-city-list-' + tid);
        $list.find('.loc-city-add-btn').each(function () {
            if (existing.includes(parseInt($(this).data('city-id')))) {
                $(this).replaceWith('<span class="badge bg-success text-white small">Added</span>');
            }
        });
        return added;
    }

    // City search input → debounced AJAX fetch
    let locSearchTimers = {};
    $(document).on('input', '.loc-city-input', function () {
        const tid = $(this).data('tid');
        clearTimeout(locSearchTimers[tid]);
        locSearchTimers[tid] = setTimeout(() => fetchCities(tid, 1), 280);
    });

    function fetchCities(tid, page) {
        const $stateEl  = $('.loc-state-row-' + tid + ' .loc-state');
        const $input    = $('.loc-city-input[data-tid="' + tid + '"]');
        const stateId   = $stateEl.val();
        const search    = $input.val().trim();
        const citiesUrl = $stateEl.data('cities-url');
        const $results  = $('.loc-city-results-' + tid);
        const $list     = $('.loc-city-list-' + tid);
        const $pager    = $('.loc-city-pager-' + tid);

        if (! stateId) return;

        $list.html('<div class="p-2 text-muted small"><i class="fas fa-spinner fa-spin me-1"></i> Searching…</div>');
        $results.show();

        const url = citiesUrl + '?state_id=' + stateId + '&search=' + encodeURIComponent(search) + '&page=' + page;

        fetch(url)
            .then(r => r.json())
            .then(resp => {
                const cities   = resp.data || [];
                const total    = resp.total || 0;
                const hasMore  = resp.has_more || false;
                const curPage  = resp.page || 1;

                if (! cities.length) {
                    $list.html('<div class="p-2 text-muted small">No cities found.</div>');
                    $pager.hide();
                    return;
                }

                // Get already-selected IDs
                const selectedIds = getSelectedCityIds(tid);

                let html = '';
                cities.forEach(city => {
                    const already = selectedIds.includes(city.id);
                    html += `<div class="d-flex align-items-center justify-content-between px-2 py-1 border-bottom loc-city-item" style="cursor:${already ? 'default' : 'pointer'}">
                        <span class="small">${escHtml(city.name)}</span>
                        ${already
                            ? '<span class="badge bg-success text-white small">Added</span>'
                            : `<button type="button" class="btn btn-primary btn-sm py-0 loc-city-add-btn"
                                data-city-id="${city.id}" data-city-name="${escHtml(city.name)}" data-tid="${tid}">
                                + Add</button>`}
                    </div>`;
                });
                $list.html(html);

                // Pager
                if (total > 3) {
                    $('.loc-city-page-info-' + tid).text('Page ' + curPage + ' · ' + total + ' total');
                    $pager.find('.loc-city-prev').prop('disabled', curPage <= 1).data('page', curPage - 1);
                    $pager.find('.loc-city-next').prop('disabled', ! hasMore).data('page', curPage + 1);
                    $pager.css('display', 'flex');
                } else {
                    $pager.hide();
                }
            })
            .catch(() => {
                $list.html('<div class="p-2 text-danger small">Search failed.</div>');
            });
    }

    // Pagination prev/next
    $(document).on('click', '.loc-city-prev, .loc-city-next', function () {
        const tid  = $(this).data('tid');
        const page = $(this).data('page');
        if (page >= 1) fetchCities(tid, page);
    });

    // Add city chip
    $(document).on('click', '.loc-city-add-btn', function () {
        const tid      = $(this).data('tid');
        const cityId   = $(this).data('city-id');
        const cityName = $(this).data('city-name');

        if (getSelectedCityIds(tid).includes(cityId)) return;

        const chip = `<span class="badge bg-primary text-white d-flex align-items-center gap-1 loc-chip" style="font-size:.8rem">
            ${escHtml(cityName)}
            <button type="button" class="btn-close btn-close-white btn-sm p-0 loc-chip-remove"
                style="font-size:.6rem" data-city-id="${cityId}" data-tid="${tid}"></button>
            <input type="hidden" name="filters[city_ids][]" value="${cityId}" class="loc-hidden-${tid}">
        </span>`;
        $('.loc-chips-' + tid).append(chip);
        updateCityCountBadge(tid);

        // Change button to "Added"
        $(this).replaceWith('<span class="badge bg-success text-white small">Added</span>');
    });

    // Clear ALL selected cities
    $(document).on('click', '.loc-clear-all', function () {
        const tid = $(this).data('tid');
        $('.loc-chips-' + tid).empty();
        updateCityCountBadge(tid);
        $(this).hide();
        // Reset "Added" badges back to Add buttons in the results list
        $('.loc-city-list-' + tid + ' .badge.bg-success').each(function () {
            const $item  = $(this).closest('.loc-city-item');
            const $span  = $item.find('span.small');
            const name   = $span.text().trim();
            // We don't have the ID here so just mark it visually — a re-search will fix it
            $(this).replaceWith('<button type="button" class="btn btn-primary btn-sm py-0 loc-city-add-btn-placeholder">+ Add</button>');
        });
    });

    // Remove city chip
    $(document).on('click', '.loc-chip-remove', function () {
        const tid    = $(this).data('tid');
        const cityId = parseInt($(this).data('city-id'));
        $(this).closest('.loc-chip').remove();
        updateCityCountBadge(tid);
        // Refresh results to re-show Add button if city is visible
        const $list = $('.loc-city-list-' + tid);
        $list.find('.loc-city-item').each(function () {
            const $addBtn = $(this).find('.loc-city-add-btn');
            if (parseInt($addBtn.data('city-id')) === cityId) {
                $addBtn.closest('.d-flex').find('.badge.bg-success').replaceWith(
                    `<button type="button" class="btn btn-primary btn-sm py-0 loc-city-add-btn"
                        data-city-id="${cityId}" data-city-name="${$addBtn.data('city-name')}" data-tid="${tid}">
                        + Add</button>`
                );
            }
        });
    });

    function getSelectedCityIds(tid) {
        return $('.loc-hidden-' + tid).map(function () { return parseInt($(this).val()); }).get();
    }

    function updateCityCountBadge(tid) {
        const count = getSelectedCityIds(tid).length;
        $('.city-count-badge-' + tid).text(count + ' selected');
        const $clearBtn = $('.loc-clear-all[data-tid="' + tid + '"]');
        count > 0 ? $clearBtn.show() : $clearBtn.hide();
    }

    // ── Filter tab helpers ────────────────────────────────────────────────────

    // Select All checkboxes in a box
    $(document).on('click', '.btn-select-all-check', function () {
        const $box   = $('#' + $(this).data('target'));
        const badge  = $(this).data('count-badge');
        $box.find('input[type="checkbox"]:not([disabled])').prop('checked', true);
        updateCountBadge(badge, $box);
    });

    // Deselect All
    $(document).on('click', '.btn-deselect-all-check', function () {
        const $box  = $('#' + $(this).data('target'));
        const badge = $(this).data('count-badge');
        $box.find('input[type="checkbox"]').prop('checked', false);
        updateCountBadge(badge, $box);
    });

    // Live count badge update when any checkbox changes
    $(document).on('change', '.border.rounded input[type="checkbox"]', function () {
        const $box = $(this).closest('.border.rounded');
        const id   = $box.attr('id');
        if (!id) return;
        // Find the badge associated with this box
        $('[data-target="' + id + '"]').each(function () {
            const badge = $(this).data('count-badge');
            if (badge) updateCountBadge(badge, $box);
        });
    });

    function updateCountBadge(badgeClass, $box) {
        const count = $box.find('input[type="checkbox"]:checked').length;
        const total = $box.find('input[type="checkbox"]').length;
        $('.' + badgeClass).text(count + (total > 0 ? ' selected' : ''));
        // Enable/disable sibling Clear button (btn-deselect-all-check next to the collapse toggle)
        $('[data-target="' + $box.attr('id') + '"].btn-deselect-all-check').filter('.btn-outline-danger')
            .prop('disabled', count === 0);
    }

    // Search/filter within checkbox lists
    $(document).on('input', '.filter-search', function () {
        const needle = $(this).val().toLowerCase();
        const $box   = $('#' + $(this).data('target'));
        $box.find('.checkable-item').each(function () {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(needle));
        });
    });

    // Add keyword row
    $(document).on('click', '.btn-add-kw', function () {
        const listId = $(this).data('target');
        const badgeClass = $(this).data('count-badge');
        const $row = $('<div class="input-group input-group-sm mb-1 keyword-row">' +
            '<input type="text" name="filters[keywords][]" class="form-control" placeholder="e.g. Accountant">' +
            '<button type="button" class="btn btn-outline-danger btn-remove-kw" title="Remove"><i class="fas fa-times"></i></button>' +
            '</div>');
        $('#' + listId).append($row);
        $row.find('input').focus();
        updateKwBadge(badgeClass, listId);
    });

    // Remove keyword row (keep at least one)
    $(document).on('click', '.btn-remove-kw', function () {
        const $list = $(this).closest('[id^="keywords-list-"]');
        const listId = $list.attr('id');
        if ($list.find('.keyword-row').length > 1) {
            $(this).closest('.keyword-row').remove();
        } else {
            $(this).closest('.keyword-row').find('input').val('');
        }
        const $btn = $('[data-target="' + listId + '"].btn-add-kw');
        updateKwBadge($btn.data('count-badge'), listId);
    });

    // Update keyword badge on type
    $(document).on('input', '[name="filters[keywords][]"]', function () {
        const $list = $(this).closest('[id^="keywords-list-"]');
        const listId = $list.attr('id');
        const $btn = $('[data-target="' + listId + '"].btn-add-kw');
        updateKwBadge($btn.data('count-badge'), listId);
    });

    function updateKwBadge(badgeClass, listId) {
        if (!badgeClass) return;
        const count = $('#' + listId).find('input').filter(function () { return $(this).val().trim() !== ''; }).length;
        $('.' + badgeClass).text(count);
    }

    // Add company keyword row
    $(document).on('click', '.btn-add-company-kw', function () {
        const listId = $(this).data('target');
        const badgeClass = $(this).data('count-badge');
        const $row = $('<div class="input-group input-group-sm mb-1 company-kw-row">' +
            '<input type="text" name="filters[company_keywords][]" class="form-control" placeholder="e.g. Zambia National Commercial Bank">' +
            '<button type="button" class="btn btn-outline-danger btn-remove-company-kw" title="Remove"><i class="fas fa-times"></i></button>' +
            '</div>');
        $('#' + listId).append($row);
        $row.find('input').focus();
        updateCoKwBadge(badgeClass, listId);
    });

    $(document).on('click', '.btn-remove-company-kw', function () {
        const $list = $(this).closest('[id^="company-list-"]');
        const listId = $list.attr('id');
        if ($list.find('.company-kw-row').length > 1) {
            $(this).closest('.company-kw-row').remove();
        } else {
            $(this).closest('.company-kw-row').find('input').val('');
        }
        const $btn = $('[data-target="' + listId + '"].btn-add-company-kw');
        updateCoKwBadge($btn.data('count-badge'), listId);
    });

    $(document).on('input', '[name="filters[company_keywords][]"]', function () {
        const $list = $(this).closest('[id^="company-list-"]');
        const listId = $list.attr('id');
        const $btn = $('[data-target="' + listId + '"].btn-add-company-kw');
        updateCoKwBadge($btn.data('count-badge'), listId);
    });

    function updateCoKwBadge(badgeClass, listId) {
        if (!badgeClass) return;
        const count = $('#' + listId).find('input').filter(function () { return $(this).val().trim() !== ''; }).length;
        $('.' + badgeClass).text(count);
    }

    // Rotate chevron on collapse toggle
    $(document).on('click', '.collapse-toggle-btn', function () {
        const expanded = $(this).attr('aria-expanded') === 'true';
        $(this).find('.collapse-chevron').css('transform', expanded ? '' : 'rotate(180deg)');
    });

    // ── Candidate account search / link ─────────────────────────────────────

    const candidateAccountSearchState = {};

    function ensureCandidateAccountState(prefix) {
        if (!candidateAccountSearchState[prefix]) {
            candidateAccountSearchState[prefix] = { page: 1, term: '', hasMore: false, timer: null };
        }

        return candidateAccountSearchState[prefix];
    }

    function renderSelectedAccount(prefix, account) {
        const $selected = $('#candidate-account-selected-' + prefix);
        const phone = account.phone || account.whatsapp_number || '';
        const hasCv = !!account.has_cv;
        const avatar = account.avatar_url || '';
        const badge = account.wakanda_verified
            ? '<span class="d-inline-flex align-items-center justify-content-center rounded-circle ms-1" title="Wakanda Verified" style="width:16px;height:16px;background:#6f42c1;color:#fff;font-size:10px;line-height:1;"><i class="ti ti-star-filled"></i></span>'
            : '';

        $selected
            .removeClass('d-none')
            .attr('data-account-id', account.id || '')
            .attr('data-has-cv', hasCv ? '1' : '0')
            .attr('data-resume-name', account.resume_name || '')
            .attr('data-account-name', account.name || '')
            .attr('data-account-email', account.email || '')
            .attr('data-account-phone', phone)
            .html(
                '<div class="border rounded px-3 py-2 bg-white">'
                + '<div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">'
                + '<div><div class="fw-semibold d-flex align-items-center gap-2">' + (avatar ? '<img src="' + escHtml(avatar) + '" alt="" style="width:24px;height:24px;border-radius:999px;object-fit:cover;border:1px solid #e5e7eb">' : '') + '<span>' + escHtml(account.name || '') + '</span>' + badge + '</div>'
                + '<div class="text-muted small">'
                + (account.email ? '<span class="me-2"><i class="ti ti-mail me-1"></i>' + escHtml(account.email) + '</span>' : '')
                + (phone ? '<span><i class="ti ti-brand-whatsapp me-1"></i>' + escHtml(phone) + '</span>' : '')
                + '</div></div>'
                + '<button type="button" class="btn btn-outline-danger btn-sm btn-clear-linked-account" data-prefix="' + prefix + '"><i class="ti ti-x me-1"></i> Clear</button>'
                + '</div></div>'
            );

        $('input[name="linked_account_id"]').filter(function () {
            return $(this).closest('.modal-content, form').find('#candidate-account-selected-' + prefix).length > 0;
        }).val(account.id || '');

        renderLinkedAccountCvPrompt(prefix, account);
    }

    function renderLinkedAccountCvPrompt(prefix, account) {
        const $prompt = $('#candidate-account-cv-prompt-' + prefix);
        const hasCv = !!account.has_cv;
        const resumeName = account.resume_name || 'CV on file';

        if (hasCv) {
            $prompt.html(
                '<div class="alert alert-success py-2 px-3 mb-0 small">'
                + '<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">'
                + '<div><strong>Account CV found:</strong> ' + escHtml(resumeName)
                + '<div class="text-muted mt-1">Use the linked account CV to auto-generate keywords and matching filters.</div></div>'
                + '<button type="button" class="btn btn-success btn-sm btn-analyze-linked-account-cv" data-prefix="' + prefix + '"><i class="ti ti-sparkles me-1"></i> Analyse Account CV</button>'
                + '</div></div>'
            );
        } else {
            $prompt.html(
                '<div class="alert alert-warning py-2 px-3 mb-0 small">'
                + '<strong>No CV on this account.</strong> Upload a CV below to generate the best keywords and matching filters.'
                + '</div>'
            );
        }
    }

    function clearLinkedAccount(prefix) {
        $('#candidate-account-selected-' + prefix)
            .addClass('d-none')
            .attr('data-account-id', '')
            .attr('data-has-cv', '0')
            .empty();
        $('#candidate-account-cv-prompt-' + prefix).empty();
        $('input[name="linked_account_id"]').filter(function () {
            return $(this).closest('.modal-content, form').find('#candidate-account-selected-' + prefix).length > 0;
        }).val('');
    }

    function runCandidateAccountSearch(prefix, page) {
        const $input = $('#candidate-account-search-' + prefix);
        const url = $input.data('search-url');
        const term = $input.val().trim();
        const state = ensureCandidateAccountState(prefix);
        const $results = $('#candidate-account-results-' + prefix);

        state.term = term;
        state.page = page;

        if (term.length < 2) {
            $results.addClass('d-none').empty();
            return;
        }

        $results.removeClass('d-none').html('<div class="border rounded p-3 bg-white text-muted small"><i class="ti ti-loader-2 fa-spin me-1"></i> Searching accounts…</div>');

        fetch(url + '?q=' + encodeURIComponent(term) + '&page=' + page)
            .then(r => r.json())
            .then(resp => {
                const rows = resp.data || [];
                state.hasMore = !!resp.has_more;

                if (!rows.length) {
                    $results.html('<div class="border rounded p-3 bg-white text-muted small">No matching candidate accounts found.</div>');
                    return;
                }

                let html = '<div class="border rounded bg-white overflow-hidden"><div class="list-group list-group-flush">';
                rows.forEach(account => {
                    const phone = account.phone || account.whatsapp_number || '';
                    const avatar = account.avatar_url || '';
                    const badge = account.wakanda_verified
                        ? '<span class="d-inline-flex align-items-center justify-content-center rounded-circle ms-1" title="Wakanda Verified" style="width:16px;height:16px;background:#6f42c1;color:#fff;font-size:10px;line-height:1;"><i class="ti ti-star-filled"></i></span>'
                        : '';
                    html += '<button type="button" class="list-group-item list-group-item-action btn-select-candidate-account"'
                        + ' data-prefix="' + prefix + '"'
                        + ' data-account-id="' + escHtml(account.id) + '"'
                        + ' data-account-name="' + escHtml(account.name || '') + '"'
                        + ' data-account-email="' + escHtml(account.email || '') + '"'
                        + ' data-account-phone="' + escHtml(phone) + '"'
                        + ' data-has-cv="' + (account.has_cv ? '1' : '0') + '"'
                        + ' data-resume-name="' + escHtml(account.resume_name || '') + '"'
                        + ' data-avatar-url="' + escHtml(avatar) + '"'
                        + ' data-wakanda-verified="' + (account.wakanda_verified ? '1' : '0') + '">'
                        + '<div class="d-flex align-items-start justify-content-between gap-2">'
                        + '<div><div class="fw-semibold d-flex align-items-center gap-2">' + (avatar ? '<img src="' + escHtml(avatar) + '" alt="" style="width:24px;height:24px;border-radius:999px;object-fit:cover;border:1px solid #e5e7eb">' : '') + '<span>' + escHtml(account.name || '') + '</span>' + badge + '</div>'
                        + '<div class="text-muted small">'
                        + (account.email ? '<span class="me-2"><i class="ti ti-mail me-1"></i>' + escHtml(account.email) + '</span>' : '')
                        + (phone ? '<span><i class="ti ti-brand-whatsapp me-1"></i>' + escHtml(phone) + '</span>' : '')
                        + '</div></div>'
                        + '<span class="badge ' + (account.has_cv ? 'bg-success' : 'bg-warning text-dark') + '">'
                        + (account.has_cv ? 'Has CV' : 'No CV')
                        + '</span></div></button>';
                });
                html += '</div>';
                html += '<div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm btn-candidate-account-page" data-prefix="' + prefix + '" data-page="' + Math.max(1, page - 1) + '"' + (page <= 1 ? ' disabled' : '') + '>Prev</button>'
                    + '<span class="text-muted small">Page ' + page + '</span>'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm btn-candidate-account-page" data-prefix="' + prefix + '" data-page="' + (page + 1) + '"' + (state.hasMore ? '' : ' disabled') + '>Next</button>'
                    + '</div></div>';
                $results.html(html);
            })
            .catch(() => {
                $results.html('<div class="border rounded p-3 bg-white text-danger small">Account search failed. Try again.</div>');
            });
    }

    $(document).on('input', '.candidate-account-search-input', function () {
        const prefix = $(this).data('prefix');
        const state = ensureCandidateAccountState(prefix);
        clearTimeout(state.timer);
        state.timer = setTimeout(() => runCandidateAccountSearch(prefix, 1), 250);
    });

    $(document).on('click', '.btn-candidate-account-page', function () {
        if ($(this).is(':disabled')) return;
        runCandidateAccountSearch($(this).data('prefix'), parseInt($(this).data('page'), 10) || 1);
    });

    $(document).on('click', '.btn-select-candidate-account', function () {
        const prefix = $(this).data('prefix');
        const account = {
            id: $(this).data('account-id'),
            name: $(this).data('account-name'),
            email: $(this).data('account-email'),
            phone: $(this).data('account-phone'),
            has_cv: String($(this).data('has-cv')) === '1',
            resume_name: $(this).data('resume-name'),
            avatar_url: $(this).data('avatar-url'),
            wakanda_verified: String($(this).data('wakanda-verified')) === '1'
        };

        const $scope = $(this).closest('.modal-content, form');
        $scope.find('input[name="candidate_name"]').val(account.name || '');
        $scope.find('input[name="candidate_phone"]').val(account.phone || '');
        $scope.find('input[name="candidate_email"]').val(account.email || '');

        renderSelectedAccount(prefix, account);
        $('#candidate-account-results-' + prefix).addClass('d-none').empty();

        Botble.showSuccess(account.has_cv
            ? 'Candidate account linked. Analyse the account CV to generate keywords.'
            : 'Candidate account linked. This account has no CV yet, so upload one below for accurate keyword matching.');
    });

    $(document).on('click', '.btn-clear-linked-account', function () {
        clearLinkedAccount($(this).data('prefix'));
    });

    // ── CV Upload & AI Analysis ──────────────────────────────────────────────

    // Enable "Analyse" button once a file is chosen
    $(document).on('change', '.cv-upload-input', function () {
        const prefix = $(this).data('prefix');
        const hasFile = !!this.files && this.files.length > 0;
        $('[data-prefix="' + prefix + '"].btn-analyze-cv').prop('disabled', !hasFile);
    });

    // Run AI analysis
    $(document).on('click', '.btn-analyze-cv', function () {
        const $btn    = $(this);
        const prefix  = $btn.data('prefix');
        const $fileInput = $('#' + prefix + '-cv-file');
        const analyzeUrl = $fileInput.data('analyze-url');

        if (!$fileInput[0].files || !$fileInput[0].files.length) {
            Botble.showError('Please select a CV file first.');
            return;
        }

        $btn.prop('disabled', true).html('<i class="ti ti-loader-2 fa-spin me-1"></i> Analysing…');

        const formData = new FormData();
        formData.append('cv_file', $fileInput[0].files[0]);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        fetch(analyzeUrl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(resp => {
                if (resp.error) {
                    Botble.showError(resp.error);
                    return;
                }
                const data = resp.data;
                applyAnalysisToForm(prefix, data, true);
            })
            .catch(() => Botble.showError('CV analysis failed. Please try again.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="ti ti-sparkles me-1"></i> Analyse with AI'));
    });

    $(document).on('click', '.btn-analyze-linked-account-cv', function () {
        const $btn = $(this);
        const prefix = $btn.data('prefix');
        const $selected = $('#candidate-account-selected-' + prefix);
        const accountId = parseInt($selected.attr('data-account-id'), 10) || 0;
        const analyzeUrl = $('#candidate-account-search-' + prefix).data('analyze-account-cv-url');

        if (!accountId) {
            Botble.showError('Select an account first.');
            return;
        }

        $btn.prop('disabled', true).html('<i class="ti ti-loader-2 fa-spin me-1"></i> Analysing…');

        fetch(analyzeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ account_id: accountId })
        })
            .then(r => r.json())
            .then(resp => {
                if (resp.error) {
                    Botble.showError(resp.error);
                    return;
                }

                applyAnalysisToForm(prefix, resp.data, true);
                Botble.showSuccess('Linked account CV analysed and filters applied.');
            })
            .catch(() => Botble.showError('Linked account CV analysis failed.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="ti ti-sparkles me-1"></i> Analyse Account CV'));
    });

    // Apply analysis results button (already-analysed CV)
    $(document).on('click', '.btn-apply-analysis', function () {
        const prefix   = $(this).data('prefix');
        const analysis = $(this).data('analysis');
        if (analysis) applyAnalysisToForm(prefix, analysis, false);
    });

    function applyAnalysisToForm(prefix, data, showPanel) {
        const $scope = $('[data-prefix="' + prefix + '"]').closest('.modal-content, form');
        const keywords = Array.isArray(data.keywords) && data.keywords.length ? data.keywords : (data.keyword ? [data.keyword] : []);

        if (data.candidate_name) {
            $scope.find('input[name="candidate_name"]').val(data.candidate_name);
        }

        if (data.candidate_phone) {
            $scope.find('input[name="candidate_phone"]').val(data.candidate_phone);
        }

        if (data.candidate_email) {
            $scope.find('input[name="candidate_email"]').val(data.candidate_email);
        }

        const $keywordsList = $('#keywords-list-' + prefix);
        if ($keywordsList.length) {
            const $inputs = $keywordsList.find('input[name="filters[keywords][]"]');
            $inputs.val('');

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

        $('#jobtypes-box-' + prefix + ' input[type="checkbox"]').prop('checked', false);
        (data.job_type_ids || []).forEach(id => {
            $('#' + prefix + '-type-' + id).prop('checked', true);
        });
        $('.jt-count-badge-' + prefix).text((data.job_type_ids || []).length + ' selected');
        $('[data-target="jobtypes-box-' + prefix + '"].btn-deselect-all-check.btn-outline-danger').prop('disabled', !(data.job_type_ids || []).length);

        $('#categories-box-' + prefix + ' input[type="checkbox"]').prop('checked', false);
        (data.category_ids || []).forEach(id => {
            $('#' + prefix + '-cat-' + id).prop('checked', true);
        });
        $('.cat-count-badge-' + prefix).text((data.category_ids || []).length + ' selected');
        $('[data-target="categories-box-' + prefix + '"].btn-deselect-all-check.btn-outline-danger').prop('disabled', !(data.category_ids || []).length);

        $('#countries-box-' + prefix + ' input[type="checkbox"]').prop('checked', false);
        (data.country_ids || []).forEach(id => {
            $('#' + prefix + '-country-' + id + ', #add-country-' + id + ', #edit-country-' + id).prop('checked', true);
        });
        $('.country-count-badge-' + prefix).text((data.country_ids || []).length + ' selected');

        if (data.job_experience_id) {
            $scope.find('select[name="filters[job_experience_id]"]').val(data.job_experience_id);
        }

        if (data.location_keyword) {
            $scope.find('input[name="filters[location_keyword]"]').val(data.location_keyword);
        }

        $scope.find('input[name="cv_analysis_payload"]').val(JSON.stringify(data));

        // Show result panel
        if (showPanel) {
            const $panel = $('#' + prefix + '-analysis-result');
            const confidence = data.confidence || 0;
            const confidenceBadgeClass = confidence >= 80 ? 'bg-success-subtle text-success' : confidence >= 60 ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary';

            let html = '<div class="d-flex align-items-center gap-2 mb-2">';
            html += '<i class="ti ti-file-text text-primary"></i>';
            html += '<strong class="small">AI Analysis Result</strong>';
            html += '<span class="badge ' + confidenceBadgeClass + ' ms-auto">' + confidence + '% confidence</span>';
            html += '</div>';

            if (data.candidate_type) {
                html += '<div class="small text-dark fw-semibold mb-2">' + escHtml(data.candidate_type) + '</div>';
            }

            if (data.candidate_name || data.candidate_phone || data.candidate_email) {
                html += '<div class="text-muted small mb-2">';
                if (data.candidate_name) html += '<span class="me-2"><i class="ti ti-user me-1"></i>' + escHtml(data.candidate_name) + '</span>';
                if (data.candidate_phone) html += '<span class="me-2"><i class="ti ti-brand-whatsapp me-1"></i>' + escHtml(data.candidate_phone) + '</span>';
                if (data.candidate_email) html += '<span><i class="ti ti-mail me-1"></i>' + escHtml(data.candidate_email) + '</span>';
                html += '</div>';
            }

            if (data.summary) {
                html += '<p class="text-muted small mb-2">' + escHtml(data.summary) + '</p>';
            }

            html += '<div class="d-flex gap-1 flex-wrap mb-2">';
            keywords.forEach(n => { html += '<span class="badge bg-dark text-white small"><i class="ti ti-search me-1"></i>' + escHtml(n) + '</span>'; });
            (data.job_type_names || []).forEach(n => { html += '<span class="badge bg-blue-subtle text-blue border small">' + escHtml(n) + '</span>'; });
            (data.category_names || []).forEach(n => { html += '<span class="badge bg-purple-subtle text-purple border small">' + escHtml(n) + '</span>'; });
            if (data.experience_name) html += '<span class="badge bg-light border text-dark small"><i class="ti ti-award me-1"></i>' + escHtml(data.experience_name) + '</span>';
            (data.country_names || []).forEach(n => { html += '<span class="badge bg-info text-white small">' + escHtml(n) + '</span>'; });
            if (data.location_keyword) html += '<span class="badge bg-light border text-dark small"><i class="ti ti-map-pin me-1"></i>' + escHtml(data.location_keyword) + '</span>';
            html += '</div>';
            if (data.usage && (data.usage.total_tokens || data.usage.estimated_cost_usd)) {
                const cost = data.usage.estimated_cost_usd ? Number(data.usage.estimated_cost_usd).toFixed(6) : null;
                html += '<div class="text-muted small mb-2">Usage: ' + escHtml(String(data.usage.total_tokens || 0)) + ' tokens' + (cost ? ' · $' + escHtml(cost) : '') + '</div>';
            }
            html += '<div class="text-success small mt-2"><i class="ti ti-check me-1"></i>Filters have been applied to the form below. Review and adjust as needed.</div>';

            $panel.html(html).removeClass('d-none');
        }

        Botble.showSuccess('AI analysis complete. Filters applied to the form — review and adjust as needed.');
    }
});
</script>
@endpush
