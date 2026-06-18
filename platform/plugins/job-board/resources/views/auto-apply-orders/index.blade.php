@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Total Orders</div>
                    <div class="h2 mb-0">{{ number_format($stats['total']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-4 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Pending Activation</div>
                    <div class="h2 mb-0 text-warning">{{ number_format($stats['pending']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-4 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Approved</div>
                    <div class="h2 mb-0 text-success">{{ number_format($stats['approved']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>Auto Apply Orders</x-core::card.title>
            <div class="card-options">
                <a href="{{ route('job-board.settings.auto-apply-plans') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-settings-dollar me-1"></i> Manage Plans
                </a>
                <a href="{{ route('auto-apply-logs.index') }}" class="btn btn-sm btn-outline-info">
                    <i class="ti ti-list-details me-1"></i> View Logs
                </a>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#setupModal">
                    <i class="ti ti-user-plus me-1"></i> Setup for Candidate
                </button>
            </div>
        </x-core::card.header>
        <x-core::card.body>
            <form method="GET" action="{{ route('auto-apply-orders.index') }}" class="row g-2 mb-3">
                <div class="col-md-5">
                    <input class="form-control" name="q" value="{{ request('q') }}"
                           placeholder="Search by name or email">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All statuses</option>
                        <option value="pending"  @selected(request('status') === 'pending')>Pending</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a class="btn btn-outline-secondary" href="{{ route('auto-apply-orders.index') }}">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-vcenter table-striped">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>Candidate</th>
                            <th>Plan</th>
                            <th>Apps/Mo</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th width="230" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @php
                                $hasCv = trim((string) $order->account?->resume) !== '';
                            @endphp
                            <tr @class(['table-danger' => ! $hasCv])>
                                <td>#{{ $order->id }}</td>
                                <td>
                                    <div class="fw-medium">{{ $order->account?->name ?? 'Deleted' }}</div>
                                    <div class="text-muted small">{{ $order->account?->email }}</div>
                                    @if(! $hasCv)
                                        <span class="badge bg-danger text-white mt-1">Inactive · Missing CV</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary text-white">{{ $order->planLabel() }} · {{ $order->duration_days }} days</span>
                                </td>
                                <td>
                                    @if($order->plan === 'admin_granted')
                                        {{ $order->applications_allowed > 0 ? $order->applications_allowed : 'Preference only' }}
                                    @else
                                        {{ $order->applications_allowed <= 0 ? 'Unlimited' : $order->applications_allowed }}
                                    @endif
                                </td>
                                <td>{{ $order->currency }} {{ number_format($order->amount, 2) }}</td>
                                <td>
                                    @php $payBadge = match($order->status) { 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary', default => 'warning' }; @endphp
                                    <span class="badge bg-{{ $payBadge }} text-white">
                                        {{ ucfirst($order->status) }}{{ $order->payment_method ? ' · ' . ucwords(str_replace('_', ' ', $order->payment_method)) : '' }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $badge = match($order->admin_status) {
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            'cancelled' => 'secondary',
                                            default    => 'warning',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }} text-white">{{ ucfirst($order->admin_status) }}</span>
                                </td>
                                <td>{{ $order->created_at?->toDateString() }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                                            title="Edit order"
                                            aria-label="Edit order"
                                            data-bs-toggle="modal" data-bs-target="#editOrderModal"
                                            data-action="{{ route('auto-apply-orders.update', $order) }}"
                                            data-order-id="{{ $order->id }}"
                                            data-label="{{ $order->account?->name ?? 'Deleted candidate' }} — {{ $order->planLabel() }}"
                                            data-plan="{{ $order->plan }}"
                                            data-duration-days="{{ $order->duration_days }}"
                                            data-applications-allowed="{{ $order->applications_allowed }}"
                                            data-amount="{{ $order->amount }}"
                                            data-currency="{{ $order->currency }}"
                                            data-payment-method="{{ $order->payment_method }}"
                                            data-status="{{ $order->status }}"
                                            data-admin-status="{{ $order->admin_status }}"
                                            data-notes="{{ $order->notes }}">
                                            <x-core::icon name="ti ti-edit" />
                                        </button>
                                        @if($order->admin_status === 'pending')
                                            <button type="button" class="btn btn-sm btn-icon btn-success"
                                                title="Approve order"
                                                aria-label="Approve order"
                                                data-bs-toggle="modal" data-bs-target="#approveModal"
                                                data-action="{{ route('auto-apply-orders.approve', $order) }}"
                                                data-label="{{ $order->account?->name ?? '' }} — {{ $order->planLabel() }}">
                                                <x-core::icon name="ti ti-check" />
                                            </button>
                                            <button type="button" class="btn btn-sm btn-icon btn-danger"
                                                title="Reject order"
                                                aria-label="Reject order"
                                                data-bs-toggle="modal" data-bs-target="#rejectModal"
                                                data-action="{{ route('auto-apply-orders.reject', $order) }}"
                                                data-label="{{ $order->account?->name ?? '' }}">
                                                <x-core::icon name="ti ti-x" />
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-info"
                                            title="{{ $hasCv ? 'Preview active jobs' : 'Candidate CV missing' }}"
                                            aria-label="Preview active jobs"
                                            data-bs-toggle="modal" data-bs-target="#activeJobsModal"
                                            data-url="{{ route('auto-apply-orders.active-jobs', $order) }}"
                                            data-account-id="{{ $order->account_id }}"
                                            data-label="{{ $order->account?->name ?? 'this candidate' }}"
                                            @disabled(! $hasCv)>
                                            <x-core::icon name="ti ti-briefcase" />
                                        </button>
                                        <a href="{{ route('auto-apply-logs.index', ['account_id' => $order->account_id]) }}"
                                            class="btn btn-sm btn-icon btn-outline-secondary"
                                            title="View auto apply logs"
                                            aria-label="View auto apply logs">
                                            <x-core::icon name="ti ti-list-details" />
                                        </a>
                                        @if($order->admin_status !== 'cancelled')
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-warning"
                                                title="Disable auto apply"
                                                aria-label="Disable auto apply"
                                                data-bs-toggle="modal" data-bs-target="#disableModal"
                                                data-action="{{ route('auto-apply-orders.disable', $order) }}"
                                                data-label="{{ $order->account?->name ?? 'this candidate' }} — {{ $order->planLabel() }}">
                                                <x-core::icon name="ti ti-ban" />
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger"
                                            title="Delete order"
                                            aria-label="Delete order"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-action="{{ route('auto-apply-orders.destroy', $order) }}"
                                            data-label="{{ $order->account?->name ?? 'this candidate' }} — {{ $order->planLabel() }}">
                                            <x-core::icon name="ti ti-trash" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No auto apply orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $orders->links() }}
        </x-core::card.body>
    </x-core::card>

    {{-- Edit order modal --}}
    <div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="editOrderForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Auto Apply Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3" id="editOrderModalLabel"></p>
                        <ul class="nav nav-tabs mb-3" id="editOrderModalTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="edit-tab-candidate" data-bs-toggle="tab" data-bs-target="#edit-pane-candidate" type="button" role="tab" aria-controls="edit-pane-candidate" aria-selected="true">Candidate</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-tab-filters" data-bs-toggle="tab" data-bs-target="#edit-pane-filters" type="button" role="tab" aria-controls="edit-pane-filters" aria-selected="false">Filters</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-tab-quota" data-bs-toggle="tab" data-bs-target="#edit-pane-quota" type="button" role="tab" aria-controls="edit-pane-quota" aria-selected="false">Activation &amp; Quota</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="editOrderModalTabContent">
                            <div class="tab-pane fade show active" id="edit-pane-candidate" role="tabpanel" aria-labelledby="edit-tab-candidate">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Candidate</label>
                                        <input type="hidden" name="account_id" id="editAccountId" required>
                                        <div id="editCandidateSelected" class="alert alert-success py-2 px-3 mb-2">
                                            <i class="ti ti-user-check me-1"></i>
                                            <span id="editCandidateSelectedLabel"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="edit-pane-filters" role="tabpanel" aria-labelledby="edit-tab-filters">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Match Score Threshold</label>
                                        <div class="input-group">
                                            <input type="number" name="match_score_threshold" id="editMatchScoreThreshold" class="form-control" min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Location Keyword</label>
                                        <input type="text" name="location_keyword" id="editLocationKeyword" class="form-control" placeholder="e.g. Johannesburg">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Keywords (comma-separated)</label>
                                        <input type="text" class="form-control" id="editKeywordsInput" placeholder="e.g. developer, engineer, marketing">
                                        <input type="hidden" name="keywords[]" id="editKeywordsHidden">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Countries <span class="text-muted small">(leave empty to match any country)</span></label>
                                        <input type="text" class="form-control" id="editCountryInput" placeholder="Search countries…" autocomplete="off">
                                        <div class="border rounded mt-2 d-none" id="editCountryResultsBox">
                                            <div id="editCountryResultsList" class="list-group list-group-flush"></div>
                                        </div>
                                        <div id="editCountryChips" class="d-flex flex-wrap gap-2 mt-2"></div>
                                        <div id="editCountryHidden"></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Categories <span class="text-muted small">(leave empty to match any category)</span></label>
                                        <input type="text" class="form-control" id="editCategoryInput" placeholder="Search categories…" autocomplete="off">
                                        <div class="border rounded mt-2 d-none" id="editCategoryResultsBox">
                                            <div id="editCategoryResultsList" class="list-group list-group-flush"></div>
                                        </div>
                                        <div id="editCategoryChips" class="d-flex flex-wrap gap-2 mt-2"></div>
                                        <div id="editCategoryHidden"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Experience Level</label>
                                        <select name="job_experience_id" id="editJobExperienceId" class="form-select">
                                            <option value="">— Any Experience —</option>
                                            @foreach($experiences as $experienceId => $experienceName)
                                                <option value="{{ $experienceId }}">{{ $experienceName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Blacklisted Companies <span class="text-muted small">(never auto-apply to these)</span></label>
                                        <input type="text" class="form-control" id="editBlacklistCompanyInput" placeholder="Search companies to exclude…" autocomplete="off">
                                        <div class="border rounded mt-2 d-none" id="editBlacklistResultsBox">
                                            <div id="editBlacklistResultsList" class="list-group list-group-flush"></div>
                                        </div>
                                        <div id="editBlacklistChips" class="d-flex flex-wrap gap-2 mt-2"></div>
                                        <div id="editBlacklistHidden"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="edit-pane-quota" role="tabpanel" aria-labelledby="edit-tab-quota">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Plan</label>
                                        <select name="plan" id="editOrderPlan" class="form-select" required>
                                            @foreach($plans as $planKey => $plan)
                                                <option value="{{ $planKey }}">{{ $plan['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Duration Days</label>
                                        <input type="number" id="editOrderDurationDays" class="form-control" disabled>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Apps/Mo</label>
                                        <input type="text" id="editOrderApplicationsAllowed" class="form-control" disabled>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Amount</label>
                                        <input type="number" id="editOrderAmount" class="form-control" disabled>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Currency</label>
                                        <input type="text" id="editOrderCurrency" class="form-control" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mt-1">
                                            <input type="hidden" name="is_active" value="0">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editIsActive">
                                            <label class="form-check-label">Activate immediately</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Method</label>
                                        <input type="text" name="payment_method" id="editOrderPaymentMethod" class="form-control" maxlength="100">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Status</label>
                                        <select name="status" id="editOrderStatus" class="form-select" required>
                                            @foreach(\Botble\JobBoard\Models\AutoApplyOrder::statuses() as $status => $label)
                                                <option value="{{ $status }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Admin Status</label>
                                        <select name="admin_status" id="editOrderAdminStatus" class="form-select" required>
                                            @foreach(\Botble\JobBoard\Models\AutoApplyOrder::statuses() as $status => $label)
                                                <option value="{{ $status }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" id="editOrderNotes" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Approve modal --}}
    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-check text-success fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Activate Auto Apply?</h6>
                    <p class="text-muted small mb-1" id="approveModalLabel">This will enable auto-apply and grant quota.</p>
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <form id="approveForm" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success px-4">Activate</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-x text-danger fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Reject this order?</h6>
                    <p class="text-muted small mb-2" id="rejectModalLabel">Auto Apply will not be activated.</p>
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <form id="rejectForm" method="POST">
                            @csrf
                            <textarea name="notes" class="form-control mb-2" placeholder="Optional reason..." rows="2"></textarea>
                            <button type="submit" class="btn btn-danger px-4">Reject</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Disable modal --}}
    <div class="modal fade" id="disableModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-ban text-warning fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Disable Auto Apply?</h6>
                    <p class="text-muted small mb-2" id="disableModalLabel">This candidate will stop receiving auto applications.</p>
                    <form id="disableForm" method="POST">
                        @csrf
                        <textarea name="notes" class="form-control mb-3" placeholder="Optional note..." rows="2"></textarea>
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning px-4">Disable</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-trash text-danger fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Delete this order?</h6>
                    <p class="text-muted small mb-4" id="deleteModalLabel">This cannot be undone.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <form id="deleteForm" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger px-4">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Setup for Candidate modal --}}
    <div class="modal fade" id="setupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('auto-apply-orders.setup-for-candidate') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Setup Auto Apply for Candidate</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs mb-3" id="setupModalTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="setup-tab-candidate" data-bs-toggle="tab" data-bs-target="#setup-pane-candidate" type="button" role="tab" aria-controls="setup-pane-candidate" aria-selected="true">Candidate</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="setup-tab-filters" data-bs-toggle="tab" data-bs-target="#setup-pane-filters" type="button" role="tab" aria-controls="setup-pane-filters" aria-selected="false">Filters</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="setup-tab-quota" data-bs-toggle="tab" data-bs-target="#setup-pane-quota" type="button" role="tab" aria-controls="setup-pane-quota" aria-selected="false">Activation &amp; Quota</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="setupModalTabContent">
                            <div class="tab-pane fade show active" id="setup-pane-candidate" role="tabpanel" aria-labelledby="setup-tab-candidate">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Candidate</label>
                                        <input type="hidden" name="account_id" id="setupAccountId" required>
                                        <div id="candidateSelected" class="alert alert-success py-2 px-3 mb-2 d-none">
                                            <i class="ti ti-user-check me-1"></i>
                                            <span id="candidateSelectedLabel"></span>
                                            <button type="button" class="btn btn-sm btn-link p-0 ms-2" id="candidateChangeBtn">Change</button>
                                        </div>
                                        <div id="candidateSearchWrap">
                                            <input type="text" class="form-control" id="candidateSearchInput"
                                                   placeholder="Search candidates by name or email…" autocomplete="off">
                                            <div class="border rounded mt-2 d-none" id="candidateResultsBox">
                                                <div id="candidateResultsList" class="list-group list-group-flush"></div>
                                                <div class="d-flex align-items-center justify-content-between px-2 py-2 border-top">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="candidatePrevBtn" disabled>
                                                        <i class="ti ti-chevron-left"></i> Prev
                                                    </button>
                                                    <span class="text-muted small" id="candidatePageLabel"></span>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="candidateNextBtn" disabled>
                                                        Next <i class="ti ti-chevron-right"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="setup-pane-filters" role="tabpanel" aria-labelledby="setup-tab-filters">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Match Score Threshold</label>
                                        <div class="input-group">
                                            <input type="number" name="match_score_threshold" class="form-control" value="60" min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Location Keyword</label>
                                        <input type="text" name="location_keyword" class="form-control" placeholder="e.g. Johannesburg">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Keywords (comma-separated)</label>
                                        <input type="text" class="form-control" id="keywordsInput" placeholder="e.g. developer, engineer, marketing">
                                        <input type="hidden" name="keywords[]" id="keywordsHidden">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Countries <span class="text-muted small">(leave empty to match any country)</span></label>
                                        <input type="text" class="form-control" id="setupCountryInput" placeholder="Search countries…" autocomplete="off">
                                        <div class="border rounded mt-2 d-none" id="setupCountryResultsBox">
                                            <div id="setupCountryResultsList" class="list-group list-group-flush"></div>
                                        </div>
                                        <div id="setupCountryChips" class="d-flex flex-wrap gap-2 mt-2"></div>
                                        <div id="setupCountryHidden"></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Categories <span class="text-muted small">(leave empty to match any category)</span></label>
                                        <input type="text" class="form-control" id="setupCategoryInput" placeholder="Search categories…" autocomplete="off">
                                        <div class="border rounded mt-2 d-none" id="setupCategoryResultsBox">
                                            <div id="setupCategoryResultsList" class="list-group list-group-flush"></div>
                                        </div>
                                        <div id="setupCategoryChips" class="d-flex flex-wrap gap-2 mt-2"></div>
                                        <div id="setupCategoryHidden"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Experience Level</label>
                                        <select name="job_experience_id" class="form-select">
                                            <option value="">— Any Experience —</option>
                                            @foreach($experiences as $experienceId => $experienceName)
                                                <option value="{{ $experienceId }}">{{ $experienceName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Blacklisted Companies <span class="text-muted small">(never auto-apply to these)</span></label>
                                        <input type="text" class="form-control" id="setupBlacklistCompanyInput"
                                               placeholder="Search companies to exclude…" autocomplete="off">
                                        <div class="border rounded mt-2 d-none" id="setupBlacklistResultsBox">
                                            <div id="setupBlacklistResultsList" class="list-group list-group-flush"></div>
                                        </div>
                                        <div id="setupBlacklistChips" class="d-flex flex-wrap gap-2 mt-2"></div>
                                        <div id="setupBlacklistHidden"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="setup-pane-quota" role="tabpanel" aria-labelledby="setup-tab-quota">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Plan</label>
                                        <select name="plan" id="setupPlan" class="form-select" required>
                                            @foreach($plans as $planKey => $plan)
                                                @if($plan['enabled'])
                                                    <option value="{{ $planKey }}">{{ $plan['label'] }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Duration Days</label>
                                        <input type="number" id="setupPlanDurationDays" class="form-control" disabled>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Apps/Mo</label>
                                        <input type="text" id="setupPlanApplicationsAllowed" class="form-control" disabled>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Amount</label>
                                        <input type="number" id="setupPlanAmount" class="form-control" disabled>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Currency</label>
                                        <input type="text" id="setupPlanCurrency" class="form-control" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mt-1">
                                            <input type="hidden" name="is_active" value="0">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                            <label class="form-check-label">Activate immediately</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save & Activate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Active jobs modal --}}
    <div class="modal fade" id="activeJobsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Active Jobs for Auto Apply</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-2 mb-3">
                        <input type="text" class="form-control" id="activeJobsSearch" placeholder="Search active jobs...">
                        <button type="button" class="btn btn-outline-primary" id="activeJobsSearchBtn">Search</button>
                    </div>
                    <div id="activeJobsLoading" class="text-center py-3 d-none">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter table-striped">
                            <thead>
                                <tr>
                                    <th>Job</th>
                                    <th>Company</th>
                                    <th>Country</th>
                                    <th>Apply Email</th>
                                    <th>Posted / Closing</th>
                                    <th>Score</th>
                                    <th width="180" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="activeJobsList">
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Select a candidate row to load jobs.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="activeJobsPagination" class="d-flex align-items-center justify-content-between gap-2 mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Active job AI preview result modal --}}
    <div class="modal fade" id="activeJobPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">AI Application Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="text-muted small text-uppercase fw-semibold mb-2">Job Ad</div>
                            <div class="border rounded p-3" style="max-height:520px;overflow-y:auto;">
                                <div class="fw-medium mb-1" id="activeJobPreviewJobName"></div>
                                <div class="d-flex align-items-center gap-2 mb-2" id="activeJobPreviewJobCompanyWrap">
                                    <img id="activeJobPreviewJobCompanyLogo" src="" alt="" style="width:24px;height:24px;object-fit:contain;border-radius:4px;border:1px solid #eee;background:#fff;padding:2px;display:none">
                                    <span class="small text-muted" id="activeJobPreviewJobCompany"></span>
                                </div>
                                <div class="small text-muted mb-1" id="activeJobPreviewJobLocation"></div>
                                <div class="small mb-1" id="activeJobPreviewJobTypes"></div>
                                <div class="small mb-1" id="activeJobPreviewJobCategories"></div>
                                <div class="small mb-1" id="activeJobPreviewJobSkills"></div>
                                <div class="small mb-1" id="activeJobPreviewJobSalary"></div>
                                <div class="small mb-1" id="activeJobPreviewJobEmail"></div>
                                <div class="small text-muted mb-2" id="activeJobPreviewJobDates"></div>
                                <hr class="my-2">
                                <div class="small job-ad-html-content" id="activeJobPreviewJobDescription"></div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="border rounded p-3" style="max-height:520px;overflow-y:auto;">
                                <div class="mb-2"><strong>Match Score:</strong> <span id="activeJobPreviewScore" class="badge bg-info" style="color:#fff"></span></div>
                                <div class="mb-2"><strong>Reasons:</strong> <span id="activeJobPreviewReasons"></span></div>
                                <div class="mb-2"><strong>AI Usage:</strong> <span id="activeJobPreviewUsage" class="text-muted small"></span></div>
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="activeJobPreviewCvBtn">
                                        <i class="ti ti-file-text me-1"></i> Preview CV
                                    </button>
                                </div>
                                <div class="mb-2"><strong>Subject:</strong> <span id="activeJobPreviewSubject"></span></div>
                                <div class="card bg-light p-3"><pre id="activeJobPreviewBody" style="white-space:pre-wrap;font-family:inherit;margin:0;"></pre></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="activeJobPreviewSendBtn" data-bs-toggle="modal" data-bs-target="#sendAutoApplyModal">
                        <i class="ti ti-send me-1"></i> Send
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- CV inline preview modal --}}
    <div class="modal fade" id="cvPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width:700px;">
            <div class="modal-content" style="height:85vh;">
                <div class="modal-header">
                    <h5 class="modal-title">Candidate CV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="cvPreviewFrame" src="" style="width:100%;height:100%;border:0;"></iframe>
                </div>
            </div>
        </div>
    </div>

    {{-- Send auto apply modal --}}
    <div class="modal fade" id="sendAutoApplyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-send text-success fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Send this auto apply?</h6>
                    <p class="text-muted small mb-4" id="sendAutoApplyLabel">This will generate and send the application email.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <form id="sendAutoApplyForm" method="POST" action="{{ route('auto-apply-orders.send-job') }}">
                            @csrf
                            <input type="hidden" name="account_id" id="sendAutoApplyAccountId">
                            <input type="hidden" name="job_id" id="sendAutoApplyJobId">
                            <button type="submit" class="btn btn-success px-4">Send</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Preview modal --}}
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">AI Email Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <input type="number" class="form-control" id="previewAccountId" placeholder="Account ID">
                        </div>
                        <div class="col-md-4">
                            <input type="number" class="form-control" id="previewJobId" placeholder="Job ID">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="previewModel">
                                <option value="gpt-4o-mini">GPT-4o Mini</option>
                                <option value="gpt-4o">GPT-4o</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary mb-3" id="generatePreviewBtn">
                        <i class="ti ti-sparkles me-1"></i> Generate Preview
                    </button>
                    <div id="previewResult" class="d-none">
                        <div class="mb-2"><strong>Match Score:</strong> <span id="previewScore" class="badge bg-info" style="color:#fff"></span></div>
                        <div class="mb-2"><strong>Reasons:</strong> <span id="previewReasons"></span></div>
                        <div class="mb-2"><strong>AI Usage:</strong> <span id="previewUsage" class="text-muted small"></span></div>
                        <div class="mb-2"><strong>Subject:</strong> <span id="previewSubject"></span></div>
                        <div class="card bg-light p-3"><pre id="previewBody" style="white-space:pre-wrap;font-family:inherit;margin:0;"></pre></div>
                    </div>
                    <div id="previewLoading" class="d-none text-center py-3">
                        <div class="spinner-border text-primary"></div>
                        <p class="text-muted mt-2">Generating email with AI...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .job-ad-html-content p { margin-bottom: 0.5rem; }
        .job-ad-html-content ul, .job-ad-html-content ol { margin-bottom: 0.5rem; padding-left: 1.25rem; }
        .job-ad-html-content h1, .job-ad-html-content h2, .job-ad-html-content h3,
        .job-ad-html-content h4, .job-ad-html-content h5, .job-ad-html-content h6 { font-size: 0.9rem; margin: 0.5rem 0 0.25rem; }
        .job-ad-html-content img { max-width: 100%; }
    </style>

    @push('footer')
        <script>
            var autoApplyPlans = @json($plans);
            var autoApplyEditOrders = @json($editOrdersData);
            var editCountryPicker;
            var editCategoryPicker;
            var editBlacklistPicker;

            function autoApplyPlanApplicationsText(plan) {
                if (!plan) return '';

                return Number(plan.applications_per_month || 0) === 0 ? 'Unlimited' : plan.applications_per_month;
            }

            function fillAutoApplyPlanFields(planKey, prefix) {
                var plan = autoApplyPlans[planKey] || null;

                document.getElementById(prefix + 'DurationDays').value = plan ? plan.duration_days : '';
                document.getElementById(prefix + 'ApplicationsAllowed').value = autoApplyPlanApplicationsText(plan);
                document.getElementById(prefix + 'Amount').value = plan ? plan.price : '';
                document.getElementById(prefix + 'Currency').value = plan ? plan.currency : '';
            }

            document.getElementById('editOrderPlan').addEventListener('change', function () {
                fillAutoApplyPlanFields(this.value, 'editOrder');
            });

            document.getElementById('setupPlan').addEventListener('change', function () {
                fillAutoApplyPlanFields(this.value, 'setupPlan');
            });

            fillAutoApplyPlanFields(document.getElementById('setupPlan').value, 'setupPlan');

            document.getElementById('editOrderModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                var order = autoApplyEditOrders[btn.dataset.orderId] || {};
                document.getElementById('editOrderForm').action = btn.dataset.action;
                document.getElementById('editOrderModalLabel').textContent = btn.dataset.label;
                document.getElementById('editAccountId').value = order.account_id || '';
                document.getElementById('editCandidateSelectedLabel').textContent = order.candidate_label || btn.dataset.label || '';
                document.getElementById('editOrderPlan').value = btn.dataset.plan || Object.keys(autoApplyPlans)[0];
                fillAutoApplyPlanFields(document.getElementById('editOrderPlan').value, 'editOrder');
                document.getElementById('editMatchScoreThreshold').value = order.match_score_threshold || {{ \Botble\JobBoard\Models\AutoApplyOrder::globalMatchThreshold() }};
                document.getElementById('editLocationKeyword').value = order.location_keyword || '';
                document.getElementById('editKeywordsInput').value = (order.keywords || []).join(', ');
                document.getElementById('editJobExperienceId').value = order.job_experience_id || '';
                document.getElementById('editIsActive').checked = !!order.is_active;
                editCountryPicker && editCountryPicker.setSelected(order.country_labels || order.country_ids || []);
                editCategoryPicker && editCategoryPicker.setSelected(order.category_labels || order.category_ids || []);
                editBlacklistPicker && editBlacklistPicker.setSelected(order.blacklisted_company_labels || order.blacklisted_company_ids || []);
                document.getElementById('editOrderPaymentMethod').value = btn.dataset.paymentMethod || '';
                document.getElementById('editOrderStatus').value = btn.dataset.status || 'pending';
                document.getElementById('editOrderAdminStatus').value = btn.dataset.adminStatus || 'pending';
                document.getElementById('editOrderNotes').value = btn.dataset.notes || '';
            });
            document.getElementById('approveModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('approveForm').action = btn.dataset.action;
                document.getElementById('approveModalLabel').textContent = btn.dataset.label + ' — will be activated.';
            });
            document.getElementById('rejectModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('rejectForm').action = btn.dataset.action;
                document.getElementById('rejectModalLabel').textContent = (btn.dataset.label || 'This order') + ' will not be activated.';
            });
            document.getElementById('disableModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('disableForm').action = btn.dataset.action;
                document.getElementById('disableModalLabel').textContent = (btn.dataset.label || 'This candidate') + ' will stop receiving auto applications.';
            });
            document.getElementById('deleteModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('deleteForm').action = btn.dataset.action;
                document.getElementById('deleteModalLabel').textContent = btn.dataset.label || 'This cannot be undone.';
            });

            function prepareKeywords(inputId, hiddenId) {
                var input = document.getElementById(inputId);
                var hidden = document.getElementById(hiddenId);
                hidden.name = '';
                input.parentNode.querySelectorAll('input[data-generated-keyword="1"]').forEach(function (item) {
                    item.remove();
                });

                input.value.split(',').map(function(k) { return k.trim(); }).filter(Boolean).forEach(function(kw) {
                    var h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = 'keywords[]';
                    h.value = kw;
                    h.dataset.generatedKeyword = '1';
                    input.parentNode.appendChild(h);
                });
            }

            // Keywords comma-to-array + candidate validation
            document.querySelector('#setupModal form').addEventListener('submit', function(e) {
                if (!document.getElementById('setupAccountId').value) {
                    e.preventDefault();
                    Botble.showError('Please search for and select a candidate first.');
                    return;
                }

                prepareKeywords('keywordsInput', 'keywordsHidden');
            });

            document.querySelector('#editOrderForm').addEventListener('submit', function(e) {
                if (!document.getElementById('editAccountId').value) {
                    e.preventDefault();
                    Botble.showError('This order has no candidate attached.');
                    return;
                }

                prepareKeywords('editKeywordsInput', 'editKeywordsHidden');
            });

            // Candidate search (paginated, 3 per page)
            (function () {
                var searchInput   = document.getElementById('candidateSearchInput');
                var resultsBox    = document.getElementById('candidateResultsBox');
                var resultsList   = document.getElementById('candidateResultsList');
                var prevBtn       = document.getElementById('candidatePrevBtn');
                var nextBtn       = document.getElementById('candidateNextBtn');
                var pageLabel     = document.getElementById('candidatePageLabel');
                var selectedBox   = document.getElementById('candidateSelected');
                var selectedLabel = document.getElementById('candidateSelectedLabel');
                var searchWrap    = document.getElementById('candidateSearchWrap');
                var hiddenId      = document.getElementById('setupAccountId');
                var changeBtn     = document.getElementById('candidateChangeBtn');
                var debounceTimer = null;
                var currentPage   = 1;
                var lastPage      = 1;

                function fetchCandidates(page) {
                    currentPage = page;
                    var url = '{{ route('auto-apply-orders.search-candidates') }}'
                        + '?q=' + encodeURIComponent(searchInput.value.trim())
                        + '&page=' + page;

                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (resp) {
                            var data = resp.data || resp;
                            renderResults(data.items || []);
                            lastPage = data.last_page || 1;
                            pageLabel.textContent = data.total
                                ? ('Page ' + data.current_page + ' of ' + lastPage + ' (' + data.total + ' found)')
                                : 'No candidates found';
                            prevBtn.disabled = currentPage <= 1;
                            nextBtn.disabled = currentPage >= lastPage;
                            resultsBox.classList.remove('d-none');
                        })
                        .catch(function () {
                            Botble.showError('Failed to search candidates.');
                        });
                }

                function renderResults(items) {
                    resultsList.innerHTML = '';
                    if (!items.length) {
                        resultsList.innerHTML = '<div class="list-group-item text-muted small">No candidates found.</div>';
                        return;
                    }
                    items.forEach(function (item) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action';
                        btn.innerHTML = '<div class="fw-medium">' + item.name + '</div>'
                            + '<div class="text-muted small">' + item.email + '</div>';
                        btn.addEventListener('click', function () {
                            selectCandidate(item);
                        });
                        resultsList.appendChild(btn);
                    });
                }

                function selectCandidate(item) {
                    hiddenId.value = item.id;
                    selectedLabel.textContent = item.name + ' (' + item.email + ')';
                    selectedBox.classList.remove('d-none');
                    searchWrap.classList.add('d-none');
                    resultsBox.classList.add('d-none');
                }

                searchInput && searchInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () { fetchCandidates(1); }, 300);
                });

                prevBtn && prevBtn.addEventListener('click', function () {
                    if (currentPage > 1) fetchCandidates(currentPage - 1);
                });

                nextBtn && nextBtn.addEventListener('click', function () {
                    if (currentPage < lastPage) fetchCandidates(currentPage + 1);
                });

                changeBtn && changeBtn.addEventListener('click', function () {
                    hiddenId.value = '';
                    selectedBox.classList.add('d-none');
                    searchWrap.classList.remove('d-none');
                    searchInput.value = '';
                    resultsBox.classList.add('d-none');
                });

                document.getElementById('setupModal').addEventListener('hidden.bs.modal', function () {
                    hiddenId.value = '';
                    selectedBox.classList.add('d-none');
                    searchWrap.classList.remove('d-none');
                    searchInput.value = '';
                    resultsBox.classList.add('d-none');
                    resultsList.innerHTML = '';
                });
            })();

            // Generic AJAX-searchable chip picker (used for countries, categories, blacklisted companies)
            function setupAutoApplyChipPicker(opts) {
                var input       = document.getElementById(opts.inputId);
                var resultsBox  = document.getElementById(opts.resultsBoxId);
                var resultsList = document.getElementById(opts.resultsListId);
                var chips       = document.getElementById(opts.chipsId);
                var hidden      = document.getElementById(opts.hiddenId);
                var debounceTimer = null;
                var selected    = {};

                if (!input) return null;

                function renderChips() {
                    chips.innerHTML = '';
                    hidden.innerHTML = '';
                    Object.keys(selected).forEach(function (id) {
                        var badge = document.createElement('span');
                        badge.className = 'badge bg-light text-dark border d-inline-flex align-items-center gap-1 px-2 py-1';
                        badge.style.fontSize = '.8rem';
                        badge.innerHTML = '<span>' + selected[id] + '</span>'
                            + '<button type="button" class="btn btn-link text-danger p-0 ms-1 lh-1" aria-label="Remove" title="Remove" style="font-size:1rem;text-decoration:none;">&times;</button>';
                        badge.querySelector('button').addEventListener('click', function () {
                            delete selected[id];
                            renderChips();
                        });
                        chips.appendChild(badge);

                        var h = document.createElement('input');
                        h.type = 'hidden';
                        h.name = opts.hiddenName;
                        h.value = id;
                        hidden.appendChild(h);
                    });
                }

                function renderResults(items) {
                    resultsList.innerHTML = '';
                    if (!items.length) {
                        resultsList.innerHTML = '<div class="list-group-item text-muted small">No results found.</div>';
                        return;
                    }
                    items.forEach(function (item) {
                        if (selected[item.id]) return;
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action';
                        btn.textContent = item.name;
                        btn.addEventListener('click', function () {
                            selected[item.id] = item.name;
                            renderChips();
                            resultsBox.classList.add('d-none');
                            input.value = '';
                        });
                        resultsList.appendChild(btn);
                    });
                }

                input.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    var term = input.value.trim();
                    if (!term) {
                        resultsBox.classList.add('d-none');
                        return;
                    }
                    debounceTimer = setTimeout(function () {
                        fetch(opts.url + '?q=' + encodeURIComponent(term), {
                            headers: { 'Accept': 'application/json' }
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (resp) {
                                renderResults(opts.parseResponse(resp));
                                resultsBox.classList.remove('d-none');
                            })
                            .catch(function () {
                                Botble.showError('Failed to search.');
                            });
                    }, 300);
                });

                document.getElementById('setupModal').addEventListener('hidden.bs.modal', function () {
                    selected = {};
                    renderChips();
                    input.value = '';
                    resultsBox.classList.add('d-none');
                });

                return {
                    setSelected: function (values) {
                        selected = {};
                        if (Array.isArray(values)) {
                            values.forEach(function (value) {
                                if (value === null || value === undefined || value === '') return;
                                if (typeof value === 'object') {
                                    selected[value.id] = value.name || value.label || ('#' + value.id);
                                    return;
                                }
                                selected[value] = '#' + value;
                            });
                        } else {
                            Object.keys(values || {}).forEach(function (id) {
                                selected[id] = values[id] || ('#' + id);
                            });
                        }
                        renderChips();
                    },
                    clear: function () {
                        selected = {};
                        renderChips();
                    }
                };
            }

            var setupCountryPicker = setupAutoApplyChipPicker({
                inputId: 'setupCountryInput',
                resultsBoxId: 'setupCountryResultsBox',
                resultsListId: 'setupCountryResultsList',
                chipsId: 'setupCountryChips',
                hiddenId: 'setupCountryHidden',
                hiddenName: 'country_ids[]',
                url: '{{ route('auto-apply-orders.search-countries') }}',
                parseResponse: function (resp) { return (resp.data || resp).items || []; },
            });

            var setupCategoryPicker = setupAutoApplyChipPicker({
                inputId: 'setupCategoryInput',
                resultsBoxId: 'setupCategoryResultsBox',
                resultsListId: 'setupCategoryResultsList',
                chipsId: 'setupCategoryChips',
                hiddenId: 'setupCategoryHidden',
                hiddenName: 'category_ids[]',
                url: '{{ route('auto-apply-orders.search-categories') }}',
                parseResponse: function (resp) { return (resp.data || resp).items || []; },
            });

            var setupBlacklistPicker = setupAutoApplyChipPicker({
                inputId: 'setupBlacklistCompanyInput',
                resultsBoxId: 'setupBlacklistResultsBox',
                resultsListId: 'setupBlacklistResultsList',
                chipsId: 'setupBlacklistChips',
                hiddenId: 'setupBlacklistHidden',
                hiddenName: 'blacklisted_company_ids[]',
                url: '{{ route('companies.list') }}',
                parseResponse: function (resp) { return resp.data && resp.data.data ? resp.data.data : (resp.data || []); },
            });

            editCountryPicker = setupAutoApplyChipPicker({
                inputId: 'editCountryInput',
                resultsBoxId: 'editCountryResultsBox',
                resultsListId: 'editCountryResultsList',
                chipsId: 'editCountryChips',
                hiddenId: 'editCountryHidden',
                hiddenName: 'country_ids[]',
                url: '{{ route('auto-apply-orders.search-countries') }}',
                parseResponse: function (resp) { return (resp.data || resp).items || []; },
            });

            editCategoryPicker = setupAutoApplyChipPicker({
                inputId: 'editCategoryInput',
                resultsBoxId: 'editCategoryResultsBox',
                resultsListId: 'editCategoryResultsList',
                chipsId: 'editCategoryChips',
                hiddenId: 'editCategoryHidden',
                hiddenName: 'category_ids[]',
                url: '{{ route('auto-apply-orders.search-categories') }}',
                parseResponse: function (resp) { return (resp.data || resp).items || []; },
            });

            editBlacklistPicker = setupAutoApplyChipPicker({
                inputId: 'editBlacklistCompanyInput',
                resultsBoxId: 'editBlacklistResultsBox',
                resultsListId: 'editBlacklistResultsList',
                chipsId: 'editBlacklistChips',
                hiddenId: 'editBlacklistHidden',
                hiddenName: 'blacklisted_company_ids[]',
                url: '{{ route('companies.list') }}',
                parseResponse: function (resp) { return resp.data && resp.data.data ? resp.data.data : (resp.data || []); },
            });

            // Active jobs preview/send
            (function () {
                var activeJobsUrl = '';
                var activeJobsAccountId = '';
                var activeJobsCandidateLabel = '';
                var activeJobsList = document.getElementById('activeJobsList');
                var activeJobsSearch = document.getElementById('activeJobsSearch');
                var activeJobsLoading = document.getElementById('activeJobsLoading');
                var activeJobsPagination = document.getElementById('activeJobsPagination');
                var activeJobsPage = 1;

                function escapeHtml(value) {
                    return String(value || '').replace(/[&<>"']/g, function (char) {
                        return {
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;'
                        }[char];
                    });
                }

                function renderActiveJobsPagination(meta) {
                    if (!meta || (meta.last_page || 1) <= 1) {
                        activeJobsPagination.innerHTML = '';
                        return;
                    }

                    activeJobsPagination.innerHTML = ''
                        + '<div class="text-muted small">Showing page ' + meta.current_page + ' of ' + meta.last_page + ' · ' + meta.total + ' jobs</div>'
                        + '<div class="d-inline-flex gap-2">'
                        + '<button type="button" class="btn btn-sm btn-outline-secondary" id="activeJobsPrevBtn"' + (meta.current_page <= 1 ? ' disabled' : '') + '>Prev</button>'
                        + '<button type="button" class="btn btn-sm btn-outline-primary" id="activeJobsNextBtn"' + (!meta.has_more_pages ? ' disabled' : '') + '>Next</button>'
                        + '</div>';

                    var prevBtn = document.getElementById('activeJobsPrevBtn');
                    var nextBtn = document.getElementById('activeJobsNextBtn');

                    if (prevBtn) {
                        prevBtn.addEventListener('click', function () {
                            if (activeJobsPage > 1) {
                                activeJobsPage--;
                                loadActiveJobs();
                            }
                        });
                    }

                    if (nextBtn) {
                        nextBtn.addEventListener('click', function () {
                            if (meta.has_more_pages) {
                                activeJobsPage++;
                                loadActiveJobs();
                            }
                        });
                    }
                }

                function loadActiveJobs() {
                    if (!activeJobsUrl) return;

                    activeJobsLoading.classList.remove('d-none');

                    var url = activeJobsUrl + '?q=' + encodeURIComponent(activeJobsSearch.value.trim()) + '&page=' + activeJobsPage;

                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (resp) {
                            var data = resp.data || resp;
                            var jobs = data.items || [];
                            var meta = data.pagination || null;
                            activeJobsAccountId = data.account_id || activeJobsAccountId;

                            if (!jobs.length) {
                                activeJobsList.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No active unprocessed jobs found.</td></tr>';
                                renderActiveJobsPagination(meta);
                                return;
                            }

                            activeJobsList.innerHTML = jobs.map(function (job) {
                                var companyCell = '<div class="text-muted small">—</div>';
                                if (job.company) {
                                    companyCell = '<div class="d-flex align-items-center gap-2">'
                                        + (job.company_logo ? '<img src="' + escapeHtml(job.company_logo) + '" alt="" style="width:28px;height:28px;object-fit:contain;border-radius:4px;border:1px solid #eee;background:#fff;padding:2px">' : '')
                                        + '<span class="small">' + escapeHtml(job.company) + '</span>'
                                        + '</div>';
                                }

                                var countryCell = '<span class="text-muted small">—</span>';
                                if (job.country || job.country_flag) {
                                    countryCell = '<span class="small">' + escapeHtml([job.country_flag || '', job.country || ''].join(' ').trim()) + '</span>';
                                }

                                var datesCell = '<div class="small text-nowrap">'
                                    + '<div><span class="text-muted">Posted:</span> ' + escapeHtml(job.created_at || '—') + '</div>'
                                    + '<div><span class="text-muted">Closing:</span> ' + escapeHtml(job.closing_date || '—') + '</div>'
                                    + '</div>';

                                var score = (typeof job.score === 'number') ? job.score : 100;
                                var scoreBadgeClass = score >= 70 ? 'bg-success' : (score >= 40 ? 'bg-warning' : 'bg-secondary');
                                var reasons = job.match_reasons || [];
                                var reasonsText = reasons.length
                                    ? reasons.map(function (r) { return 'Keyword "' + r.keyword + '" found in ' + r.field + ': ' + r.snippet; }).join('\n')
                                    : 'No keyword filters set — matches all jobs in the selected country.';
                                var scoreCell = '<span class="badge ' + scoreBadgeClass + '" title="' + escapeHtml(reasonsText) + '" style="cursor:help;color:#fff">' + score + '%</span>';

                                return '<tr>'
                                    + '<td><div class="fw-medium">' + escapeHtml(job.name) + '</div><div class="text-muted small">#' + job.id + '</div></td>'
                                    + '<td>' + companyCell + '</td>'
                                    + '<td>' + countryCell + '</td>'
                                    + '<td class="text-muted small">' + escapeHtml(job.apply_email) + '</td>'
                                    + '<td>' + datesCell + '</td>'
                                    + '<td>' + scoreCell + '</td>'
                                    + '<td class="text-end"><div class="d-inline-flex gap-1">'
                                    + '<button type="button" class="btn btn-sm btn-outline-info js-preview-active-job" title="Preview application" data-job-id="' + job.id + '" data-job-name="' + escapeHtml(job.name) + '">Preview</button>'
                                    + '<button type="button" class="btn btn-sm btn-success js-send-active-job" title="Send application" data-bs-toggle="modal" data-bs-target="#sendAutoApplyModal" data-job-id="' + job.id + '" data-job-name="' + escapeHtml(job.name) + '">Send</button>'
                                    + '</div></td>'
                                    + '</tr>';
                            }).join('');
                            renderActiveJobsPagination(meta);
                        })
                        .catch(function () {
                            activeJobsList.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Failed to load active jobs.</td></tr>';
                            activeJobsPagination.innerHTML = '';
                        })
                        .finally(function () {
                            activeJobsLoading.classList.add('d-none');
                        });
                }

                document.getElementById('activeJobsModal').addEventListener('show.bs.modal', function (e) {
                    var btn = e.relatedTarget;
                    activeJobsUrl = btn.dataset.url || '';
                    activeJobsAccountId = btn.dataset.accountId || '';
                    activeJobsCandidateLabel = btn.dataset.label || 'candidate';
                    activeJobsSearch.value = '';
                    activeJobsPage = 1;
                    document.querySelector('#activeJobsModal .modal-title').textContent = 'Active Jobs for ' + activeJobsCandidateLabel;
                    loadActiveJobs();
                });

                document.getElementById('activeJobsSearchBtn').addEventListener('click', function () {
                    activeJobsPage = 1;
                    loadActiveJobs();
                });
                activeJobsSearch.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        activeJobsPage = 1;
                        loadActiveJobs();
                    }
                });

                activeJobsList.addEventListener('click', function (e) {
                    var previewBtn = e.target.closest('.js-preview-active-job');
                    var sendBtn = e.target.closest('.js-send-active-job');

                    if (previewBtn) {
                        activeJobsLoading.classList.remove('d-none');
                        previewedJobId = previewBtn.dataset.jobId;
                        previewedJobName = previewBtn.dataset.jobName || 'this job';

                        fetch('{{ route("auto-apply-orders.preview") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ account_id: activeJobsAccountId, job_id: previewBtn.dataset.jobId, ai_model: 'gpt-4o-mini' })
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                if (data.error) {
                                    Botble.showError(data.message || 'Failed to generate preview.');
                                    return;
                                }
                                var d = data.data;
                                document.getElementById('activeJobPreviewScore').textContent = d.score + '%';
                                document.getElementById('activeJobPreviewReasons').textContent = (d.reasons || []).join('; ');
                                document.getElementById('activeJobPreviewUsage').textContent = d.total_tokens
                                    ? (d.total_tokens + ' tokens (' + d.prompt_tokens + ' in / ' + d.completion_tokens + ' out) · $' + Number(d.cost || 0).toFixed(5) + ' · ' + (d.ai_model || '') + (d.cached ? ' · cached (no new AI call)' : ''))
                                    : 'N/A';
                                document.getElementById('activeJobPreviewSubject').textContent = d.subject;
                                document.getElementById('activeJobPreviewBody').textContent = d.body;
                                renderActiveJobPreviewJob(d.job || {});
                                currentPreviewResumeUrl = d.resume_url || '';
                                document.getElementById('activeJobPreviewCvBtn').disabled = ! currentPreviewResumeUrl;
                                bootstrap.Modal.getOrCreateInstance(document.getElementById('activeJobPreviewModal')).show();
                            })
                            .catch(function () {
                                Botble.showError('Failed to generate preview.');
                            })
                            .finally(function () {
                                activeJobsLoading.classList.add('d-none');
                            });
                    }

                    if (sendBtn) {
                        document.getElementById('sendAutoApplyAccountId').value = activeJobsAccountId;
                        document.getElementById('sendAutoApplyJobId').value = sendBtn.dataset.jobId;
                        document.getElementById('sendAutoApplyLabel').textContent = 'Send application for ' + activeJobsCandidateLabel + ' to ' + (sendBtn.dataset.jobName || 'this job') + '?';
                    }
                });

                var previewedJobId = null;
                var previewedJobName = '';
                var currentPreviewResumeUrl = '';

                function renderActiveJobPreviewJob(job) {
                    document.getElementById('activeJobPreviewJobName').textContent = job.name || '—';

                    var companyLogo = document.getElementById('activeJobPreviewJobCompanyLogo');
                    if (job.company_logo) {
                        companyLogo.src = job.company_logo;
                        companyLogo.style.display = '';
                    } else {
                        companyLogo.style.display = 'none';
                    }
                    document.getElementById('activeJobPreviewJobCompany').textContent = job.company || '—';

                    var location = [job.country_flag || '', job.country || '', job.address || ''].filter(Boolean).join(' ');
                    document.getElementById('activeJobPreviewJobLocation').textContent = location || '—';

                    document.getElementById('activeJobPreviewJobTypes').innerHTML = (job.job_types && job.job_types.length)
                        ? '<strong>Type:</strong> ' + escapeHtml(job.job_types.join(', ')) : '';
                    document.getElementById('activeJobPreviewJobCategories').innerHTML = (job.categories && job.categories.length)
                        ? '<strong>Category:</strong> ' + escapeHtml(job.categories.join(', ')) : '';
                    document.getElementById('activeJobPreviewJobSkills').innerHTML = (job.skills && job.skills.length)
                        ? '<strong>Skills:</strong> ' + escapeHtml(job.skills.join(', ')) : '';
                    document.getElementById('activeJobPreviewJobSalary').innerHTML = job.salary_text
                        ? '<strong>Salary:</strong> ' + escapeHtml(job.salary_text) : '';
                    document.getElementById('activeJobPreviewJobEmail').innerHTML = job.apply_email
                        ? '<strong>Apply Email:</strong> ' + escapeHtml(job.apply_email) : '';

                    var dates = [];
                    if (job.created_at) dates.push('Posted: ' + job.created_at);
                    if (job.closing_date) dates.push('Closing: ' + job.closing_date);
                    document.getElementById('activeJobPreviewJobDates').textContent = dates.join(' · ');

                    document.getElementById('activeJobPreviewJobDescription').innerHTML = job.description || 'No description available.';
                }

                document.getElementById('activeJobPreviewSendBtn').addEventListener('click', function () {
                    document.getElementById('sendAutoApplyAccountId').value = activeJobsAccountId;
                    document.getElementById('sendAutoApplyJobId').value = previewedJobId;
                    document.getElementById('sendAutoApplyLabel').textContent = 'Send application for ' + activeJobsCandidateLabel + ' to ' + previewedJobName + '?';
                });

                document.getElementById('activeJobPreviewCvBtn').addEventListener('click', function () {
                    if (! currentPreviewResumeUrl) return;
                    document.getElementById('cvPreviewFrame').src = currentPreviewResumeUrl;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('cvPreviewModal')).show();
                });

                document.getElementById('cvPreviewModal').addEventListener('hidden.bs.modal', function () {
                    document.getElementById('cvPreviewFrame').src = '';
                });
            })();

            // Preview
            document.getElementById('generatePreviewBtn').addEventListener('click', function() {
                var accountId = document.getElementById('previewAccountId').value;
                var jobId = document.getElementById('previewJobId').value;
                var model = document.getElementById('previewModel').value;

                if (!accountId || !jobId) {
                    Botble.showError('Please enter Account ID and Job ID.');
                    return;
                }

                document.getElementById('previewResult').classList.add('d-none');
                document.getElementById('previewLoading').classList.remove('d-none');

                fetch('{{ route("auto-apply-orders.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ account_id: accountId, job_id: jobId, ai_model: model })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    document.getElementById('previewLoading').classList.add('d-none');
                    if (data.error) {
                        Botble.showError(data.message || 'Failed to generate preview.');
                        return;
                    }
                    var d = data.data;
                    document.getElementById('previewScore').textContent = d.score + '%';
                    document.getElementById('previewReasons').textContent = (d.reasons || []).join('; ');
                    document.getElementById('previewUsage').textContent = d.total_tokens
                        ? (d.total_tokens + ' tokens (' + d.prompt_tokens + ' in / ' + d.completion_tokens + ' out) · $' + Number(d.cost || 0).toFixed(5) + ' · ' + (d.ai_model || '') + (d.cached ? ' · cached (no new AI call)' : ''))
                        : 'N/A';
                    document.getElementById('previewSubject').textContent = d.subject;
                    document.getElementById('previewBody').textContent = d.body;
                    document.getElementById('previewResult').classList.remove('d-none');
                })
                .catch(function() {
                    document.getElementById('previewLoading').classList.add('d-none');
                    Botble.showError('Network error.');
                });
            });
        </script>
    @endpush
@endsection
