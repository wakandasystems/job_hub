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
                            <th>Limit</th>
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
                                    <div class="fw-medium">
                                        @if($order->account)
                                            <a href="{{ route('accounts.edit', $order->account_id) }}">{{ $order->account->name }}</a>
                                        @else
                                            Deleted
                                        @endif
                                    </div>
                                    <div class="text-muted small">{{ $order->account?->email }}</div>
                                    @if(! $hasCv)
                                        <span class="badge bg-danger text-white mt-1">Inactive · Missing CV</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary text-white">{{ $order->planLabel() }} · {{ $order->duration_days }} days</span>
                                </td>
                                <td>
                                        {{ $order->applicationsLabel() }}
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
                <form id="editOrderForm" method="POST" enctype="multipart/form-data">
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
                                <button class="nav-link active" id="edit-tab-cv" data-bs-toggle="tab" data-bs-target="#edit-pane-cv" type="button" role="tab" aria-controls="edit-pane-cv" aria-selected="true">
                                    <i class="ti ti-sparkles me-1"></i> CV &amp; AI
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-tab-candidate" data-bs-toggle="tab" data-bs-target="#edit-pane-candidate" type="button" role="tab" aria-controls="edit-pane-candidate" aria-selected="false">Candidate</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-tab-filters" data-bs-toggle="tab" data-bs-target="#edit-pane-filters" type="button" role="tab" aria-controls="edit-pane-filters" aria-selected="false">Filters</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-tab-quota" data-bs-toggle="tab" data-bs-target="#edit-pane-quota" type="button" role="tab" aria-controls="edit-pane-quota" aria-selected="false">Activation &amp; Quota</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="editOrderModalTabContent">
                            <div class="tab-pane fade show active" id="edit-pane-cv" role="tabpanel" aria-labelledby="edit-tab-cv">
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label">Upload CV <span class="text-muted small">(optional — replaces the candidate's CV on file)</span></label>
                                        <input type="file" class="form-control" id="editCvFile" name="cv_file" accept=".pdf,.doc,.docx,.txt" data-analyze-url="{{ route('auto-apply-orders.analyze-cv') }}">
                                        <div class="form-text">Use this to update the candidate's CV and regenerate keyword/filter suggestions.</div>
                                    </div>
                                    <div class="col-md-5 d-flex align-items-end gap-2 flex-wrap">
                                        <button type="button" class="btn btn-primary" id="editAnalyzeCvBtn" disabled>
                                            <i class="ti ti-sparkles me-1"></i> Analyse Uploaded CV
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="editAnalyzeAccountCvBtn" disabled data-url="{{ route('auto-apply-orders.analyze-account-cv') }}">
                                            <i class="ti ti-file-spark me-1"></i> Analyse Account CV
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="editPreviewCvBtn" disabled>
                                            <i class="ti ti-file-text me-1"></i> Preview CV
                                        </button>
                                    </div>
                                    <div class="col-12">
                                        <div id="editCvPrompt" class="alert alert-info py-2 px-3 mb-0 small">Loading candidate CV status…</div>
                                        <div id="editAnalysisResult" class="border rounded p-3 mt-3 d-none"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="edit-pane-candidate" role="tabpanel" aria-labelledby="edit-tab-candidate">
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
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label mb-0">Countries <span class="text-muted small">(leave empty to match any country)</span></label>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-link btn-sm p-0" id="editCountrySelectAllBtn">Select all</button>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="editCountryClearAllBtn">Clear all</button>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control" id="editCountryInput" placeholder="Search countries…" autocomplete="off">
                                        <div class="border rounded mt-2 d-none" id="editCountryResultsBox">
                                            <div id="editCountryResultsList" class="list-group list-group-flush"></div>
                                        </div>
                                        <div id="editCountryChips" class="d-flex flex-wrap gap-2 mt-2"></div>
                                        <div id="editCountryHidden"></div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label mb-0">Categories <span class="text-muted small">(leave empty to match any category)</span></label>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="editCategoryClearBtn">Clear</button>
                                        </div>
                                        <input type="text" class="form-control" id="editCategoryInput" placeholder="Search categories…" autocomplete="off">
                                        <div class="border rounded mt-2 d-none" id="editCategoryResultsBox">
                                            <div id="editCategoryResultsList" class="list-group list-group-flush"></div>
                                        </div>
                                        <div id="editCategoryChips" class="d-flex flex-wrap gap-2 mt-2"></div>
                                        <div id="editCategoryHidden"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label mb-0">Experience Level</label>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="editExperienceClearBtn">Clear</button>
                                        </div>
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
                            <x-core::icon name="ti ti-check" class="text-success" style="width:28px;height:28px;" />
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
                            <x-core::icon name="ti ti-x" class="text-danger" style="width:28px;height:28px;" />
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
                            <x-core::icon name="ti ti-ban" class="text-warning" style="width:28px;height:28px;" />
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
                            <x-core::icon name="ti ti-trash" class="text-danger" style="width:28px;height:28px;" />
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
                <form method="POST" action="{{ route('auto-apply-orders.setup-for-candidate') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Setup Auto Apply for Candidate</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs mb-3" id="setupModalTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="setup-tab-cv" data-bs-toggle="tab" data-bs-target="#setup-pane-cv" type="button" role="tab" aria-controls="setup-pane-cv" aria-selected="true">
                                    <i class="ti ti-sparkles me-1"></i> CV & AI
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="setup-tab-candidate" data-bs-toggle="tab" data-bs-target="#setup-pane-candidate" type="button" role="tab" aria-controls="setup-pane-candidate" aria-selected="false">Candidate</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="setup-tab-filters" data-bs-toggle="tab" data-bs-target="#setup-pane-filters" type="button" role="tab" aria-controls="setup-pane-filters" aria-selected="false">Filters</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="setup-tab-quota" data-bs-toggle="tab" data-bs-target="#setup-pane-quota" type="button" role="tab" aria-controls="setup-pane-quota" aria-selected="false">Activation &amp; Quota</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="setupModalTabContent">
                            <div class="tab-pane fade show active" id="setup-pane-cv" role="tabpanel" aria-labelledby="setup-tab-cv">
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label">Upload CV for AI filter setup <span class="text-muted small">(optional)</span></label>
                                        <input type="file" class="form-control" id="setupCvFile" name="cv_file" accept=".pdf,.doc,.docx,.txt" data-analyze-url="{{ route('auto-apply-orders.analyze-cv') }}">
                                        <div class="form-text">Saved as the candidate's CV on file, and used to generate keywords, country/category suggestions, location, and experience filters.</div>
                                    </div>
                                    <div class="col-md-5 d-flex align-items-end gap-2 flex-wrap">
                                        <button type="button" class="btn btn-primary" id="setupAnalyzeCvBtn" disabled>
                                            <i class="ti ti-sparkles me-1"></i> Analyse Uploaded CV
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="setupAnalyzeAccountCvBtn" disabled data-url="{{ route('auto-apply-orders.analyze-account-cv') }}">
                                            <i class="ti ti-file-spark me-1"></i> Analyse Account CV
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="setupPreviewCvBtn" disabled>
                                            <i class="ti ti-file-text me-1"></i> Preview CV
                                        </button>
                                    </div>
                                    <div class="col-12">
                                        <div id="setupCvPrompt" class="alert alert-info py-2 px-3 mb-0 small">
                                            Select a candidate with a CV on file or upload a CV here, then analyse it to prefill the filter tab.
                                        </div>
                                        <div id="setupAnalysisResult" class="border rounded p-3 mt-3 d-none"></div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                                            <label class="form-label mb-0">Matching jobs preview</label>
                                            <button type="button" class="btn btn-outline-success btn-sm" id="setupPreviewJobsBtn" data-url="{{ route('auto-apply-orders.preview-setup-jobs') }}">
                                                <i class="ti ti-eye me-1"></i> Preview Jobs From Filters
                                            </button>
                                        </div>
                                        <div id="setupPreviewJobsResult" class="border rounded p-3 text-muted small">
                                            Analyse a CV or adjust filters, then preview matching jobs before saving.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="setup-pane-candidate" role="tabpanel" aria-labelledby="setup-tab-candidate">
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
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label mb-0">Categories <span class="text-muted small">(leave empty to match any category)</span></label>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="setupCategoryClearBtn">Clear</button>
                                        </div>
                                        <input type="text" class="form-control" id="setupCategoryInput" placeholder="Search categories…" autocomplete="off">
                                        <div class="border rounded mt-2 d-none" id="setupCategoryResultsBox">
                                            <div id="setupCategoryResultsList" class="list-group list-group-flush"></div>
                                        </div>
                                        <div id="setupCategoryChips" class="d-flex flex-wrap gap-2 mt-2"></div>
                                        <div id="setupCategoryHidden"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label mb-0">Experience Level</label>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="setupExperienceClearBtn">Clear</button>
                                        </div>
                                        <select name="job_experience_id" class="form-select" id="setupJobExperienceId">
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
                        <button type="button" class="btn btn-success text-nowrap" id="activeJobsSendAllBtn" title="Send all unsent jobs currently shown on this page">
                            <i class="ti ti-send me-1"></i> Send All
                        </button>
                    </div>
                    <div id="activeJobsSendAllProgress" class="d-none mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span id="activeJobsSendAllStatusText">Sending…</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div id="activeJobsSendAllProgressBar" class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%"></div>
                        </div>
                    </div>
                    <div id="activeJobsLoading" class="text-center py-3 d-none">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter table-striped">
                            <thead>
                                <tr>
                                    <th width="40">#</th>
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
                                    <td colspan="8" class="text-center text-muted py-4">Select a candidate row to load jobs.</td>
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
                            <x-core::icon name="ti ti-send" class="text-success" style="width:28px;height:28px;" />
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

    {{-- Send all auto applies confirm modal --}}
    <div class="modal fade" id="sendAllAutoApplyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:52px;height:52px;">
                            <x-core::icon name="ti ti-send" class="text-success" style="width:28px;height:28px;" />
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Send all auto applies?</h6>
                    <p class="text-muted small mb-4" id="sendAllAutoApplyLabel">This will generate and send applications for every unsent job on this page.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success px-4" id="sendAllAutoApplyConfirmBtn">Send All</button>
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
            var setupSelectedAccount = null;
            var editSelectedAccount = null;

            function setupEscapeHtml(value) {
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
                editSelectedAccount = {
                    id: order.account_id || null,
                    has_cv: !!order.has_cv,
                    resume_url: order.resume_url || '',
                    resume_name: order.resume_name || ''
                };
                if (window.onEditAccountReady) window.onEditAccountReady();
            });
            document.getElementById('editOrderModal').addEventListener('hidden.bs.modal', function () {
                document.getElementById('editCvFile').value = '';
                document.getElementById('editAnalysisResult').classList.add('d-none');
                document.getElementById('editAnalysisResult').innerHTML = '';
                if (window.onEditAccountReady) window.onEditAccountReady();
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
                        btn.innerHTML = '<div class="d-flex align-items-start justify-content-between gap-2">'
                            + '<div><div class="fw-medium">' + setupEscapeHtml(item.name) + '</div>'
                            + '<div class="text-muted small">' + setupEscapeHtml(item.email) + '</div></div>'
                            + '<span class="badge ' + (item.has_cv ? 'bg-success' : 'bg-warning text-dark') + '">' + (item.has_cv ? 'Has CV' : 'No CV') + '</span>'
                            + '</div>';
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
                    setupSelectedAccount = item;
                    if (window.onSetupCandidateSelected) window.onSetupCandidateSelected(item);
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
                    setupSelectedAccount = null;
                    if (window.onSetupCandidateCleared) window.onSetupCandidateCleared();
                });

                document.getElementById('setupModal').addEventListener('hidden.bs.modal', function () {
                    hiddenId.value = '';
                    selectedBox.classList.add('d-none');
                    searchWrap.classList.remove('d-none');
                    searchInput.value = '';
                    resultsBox.classList.add('d-none');
                    resultsList.innerHTML = '';
                    setupSelectedAccount = null;
                    if (window.onSetupCandidateCleared) window.onSetupCandidateCleared();
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

            document.getElementById('setupCategoryClearBtn').addEventListener('click', function () {
                setupCategoryPicker.clear();
            });

            document.getElementById('setupExperienceClearBtn').addEventListener('click', function () {
                document.getElementById('setupJobExperienceId').value = '';
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

            (function () {
                var cvFileInput = document.getElementById('setupCvFile');
                var analyzeCvBtn = document.getElementById('setupAnalyzeCvBtn');
                var analyzeAccountCvBtn = document.getElementById('setupAnalyzeAccountCvBtn');
                var previewCvBtn = document.getElementById('setupPreviewCvBtn');
                var prompt = document.getElementById('setupCvPrompt');
                var analysisPanel = document.getElementById('setupAnalysisResult');
                var previewJobsBtn = document.getElementById('setupPreviewJobsBtn');
                var previewJobsResult = document.getElementById('setupPreviewJobsResult');
                var uploadedCvObjectUrl = '';

                function selectedValues(containerId) {
                    return Array.prototype.slice.call(document.querySelectorAll('#' + containerId + ' input[type="hidden"]'))
                        .map(function (input) { return input.value; })
                        .filter(Boolean);
                }

                function selectedObjects(ids, names) {
                    ids = Array.isArray(ids) ? ids : [];
                    names = Array.isArray(names) ? names : [];

                    return ids.map(function (id, index) {
                        return { id: id, name: names[index] || ('#' + id) };
                    });
                }

                function currentCvPreviewUrl() {
                    if (uploadedCvObjectUrl) return uploadedCvObjectUrl;
                    return setupSelectedAccount && setupSelectedAccount.resume_url ? setupSelectedAccount.resume_url : '';
                }

                function updateCvActions() {
                    var hasUpload = cvFileInput.files && cvFileInput.files.length > 0;
                    var hasAccountCv = !!(setupSelectedAccount && setupSelectedAccount.has_cv);
                    analyzeCvBtn.disabled = !hasUpload;
                    analyzeAccountCvBtn.disabled = !hasAccountCv;
                    previewCvBtn.disabled = !currentCvPreviewUrl();

                    if (hasAccountCv) {
                        prompt.className = 'alert alert-success py-2 px-3 mb-0 small';
                        prompt.innerHTML = '<strong>Account CV found:</strong> ' + setupEscapeHtml(setupSelectedAccount.resume_name || 'CV on file') + '<div class="text-muted mt-1">Use it to auto-generate Auto Apply keywords and filters.</div>';
                    } else if (setupSelectedAccount) {
                        prompt.className = 'alert alert-warning py-2 px-3 mb-0 small';
                        prompt.innerHTML = '<strong>No CV on this account.</strong> Upload a CV here to generate accurate filters.';
                    } else {
                        prompt.className = 'alert alert-info py-2 px-3 mb-0 small';
                        prompt.textContent = 'Select a candidate with a CV on file or upload a CV here, then analyse it to prefill the filter tab.';
                    }
                }

                function renderAnalysis(data) {
                    var keywords = Array.isArray(data.keywords) && data.keywords.length ? data.keywords : (data.keyword ? [data.keyword] : []);
                    var confidence = Number(data.confidence || 0);
                    var confidenceClass = confidence >= 80 ? 'bg-success-subtle text-success' : (confidence >= 60 ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary');
                    var html = '<div class="d-flex align-items-center gap-2 mb-2">'
                        + '<i class="ti ti-file-text text-primary"></i>'
                        + '<strong class="small">AI Analysis Result</strong>'
                        + '<span class="badge ' + confidenceClass + ' ms-auto">' + confidence + '% confidence</span>'
                        + '</div>';

                    if (data.candidate_type) html += '<div class="small fw-semibold mb-2">' + setupEscapeHtml(data.candidate_type) + '</div>';
                    if (data.summary) html += '<p class="text-muted small mb-2">' + setupEscapeHtml(data.summary) + '</p>';

                    html += '<div class="d-flex flex-wrap gap-1">';
                    keywords.forEach(function (keyword) {
                        html += '<span class="badge bg-dark text-white"><i class="ti ti-search me-1"></i>' + setupEscapeHtml(keyword) + '</span>';
                    });
                    (data.category_names || []).forEach(function (name) {
                        html += '<span class="badge bg-secondary text-white">' + setupEscapeHtml(name) + '</span>';
                    });
                    (data.country_names || []).forEach(function (name) {
                        html += '<span class="badge bg-info text-white">' + setupEscapeHtml(name) + '</span>';
                    });
                    if (data.location_keyword) {
                        html += '<span class="badge bg-light border text-dark"><i class="ti ti-map-pin me-1"></i>' + setupEscapeHtml(data.location_keyword) + '</span>';
                    }
                    html += '</div>';

                    if (data.usage && (data.usage.total_tokens || data.usage.estimated_cost_usd)) {
                        var cost = data.usage.estimated_cost_usd ? Number(data.usage.estimated_cost_usd).toFixed(6) : null;
                        html += '<div class="text-muted small mt-2">Usage: ' + setupEscapeHtml(String(data.usage.total_tokens || 0)) + ' tokens' + (cost ? ' · $' + setupEscapeHtml(cost) : '') + '</div>';
                    }

                    html += '<div class="text-success small mt-2"><i class="ti ti-check me-1"></i>Filters applied. Review and adjust before saving.</div>';
                    analysisPanel.innerHTML = html;
                    analysisPanel.classList.remove('d-none');
                }

                function applySetupAnalysis(data) {
                    var keywords = Array.isArray(data.keywords) && data.keywords.length ? data.keywords : (data.keyword ? [data.keyword] : []);
                    document.getElementById('keywordsInput').value = keywords.join(', ');

                    if (data.location_keyword) {
                        document.querySelector('#setup-pane-filters input[name="location_keyword"]').value = data.location_keyword;
                    }

                    if (data.job_experience_id) {
                        document.querySelector('#setup-pane-filters select[name="job_experience_id"]').value = data.job_experience_id;
                    }

                    if (setupCountryPicker) {
                        setupCountryPicker.setSelected(selectedObjects(data.country_ids || [], data.country_names || []));
                    }

                    if (setupCategoryPicker) {
                        setupCategoryPicker.setSelected(selectedObjects(data.category_ids || [], data.category_names || []));
                    }

                    renderAnalysis(data);
                    Botble.showSuccess('AI analysis complete. Auto Apply filters were filled in.');
                }

                function handleAnalysisResponse(resp) {
                    if (resp.error) {
                        Botble.showError(resp.error);
                        return;
                    }

                    applySetupAnalysis(resp.data || {});
                    bootstrap.Tab.getOrCreateInstance(document.getElementById('setup-tab-filters')).show();
                }

                cvFileInput.addEventListener('change', function () {
                    if (uploadedCvObjectUrl) {
                        URL.revokeObjectURL(uploadedCvObjectUrl);
                        uploadedCvObjectUrl = '';
                    }

                    if (this.files && this.files.length) {
                        uploadedCvObjectUrl = URL.createObjectURL(this.files[0]);
                    }

                    updateCvActions();
                });

                analyzeCvBtn.addEventListener('click', function () {
                    if (!cvFileInput.files || !cvFileInput.files.length) {
                        Botble.showError('Please select a CV file first.');
                        return;
                    }

                    analyzeCvBtn.disabled = true;
                    analyzeCvBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Analysing...';

                    var formData = new FormData();
                    formData.append('cv_file', cvFileInput.files[0]);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                    fetch(cvFileInput.dataset.analyzeUrl, { method: 'POST', body: formData, headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(handleAnalysisResponse)
                        .catch(function () { Botble.showError('CV analysis failed.'); })
                        .finally(function () {
                            analyzeCvBtn.innerHTML = '<i class="ti ti-sparkles me-1"></i> Analyse Uploaded CV';
                            updateCvActions();
                        });
                });

                analyzeAccountCvBtn.addEventListener('click', function () {
                    if (!setupSelectedAccount || !setupSelectedAccount.id) {
                        Botble.showError('Select a candidate first.');
                        return;
                    }

                    analyzeAccountCvBtn.disabled = true;
                    analyzeAccountCvBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Analysing...';

                    fetch(analyzeAccountCvBtn.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ account_id: setupSelectedAccount.id })
                    })
                        .then(function (r) { return r.json(); })
                        .then(handleAnalysisResponse)
                        .catch(function () { Botble.showError('Account CV analysis failed.'); })
                        .finally(function () {
                            analyzeAccountCvBtn.innerHTML = '<i class="ti ti-file-spark me-1"></i> Analyse Account CV';
                            updateCvActions();
                        });
                });

                previewCvBtn.addEventListener('click', function () {
                    var url = currentCvPreviewUrl();
                    if (!url) return;
                    document.getElementById('cvPreviewFrame').src = url;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('cvPreviewModal')).show();
                });

                previewJobsBtn.addEventListener('click', function () {
                    var keywords = document.getElementById('keywordsInput').value.split(',').map(function (item) {
                        return item.trim();
                    }).filter(Boolean);

                    previewJobsBtn.disabled = true;
                    previewJobsBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Previewing...';
                    previewJobsResult.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span> Searching matching jobs...</div>';

                    fetch(previewJobsBtn.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            keywords: keywords,
                            country_ids: selectedValues('setupCountryHidden'),
                            category_ids: selectedValues('setupCategoryHidden'),
                            blacklisted_company_ids: selectedValues('setupBlacklistHidden'),
                            location_keyword: document.querySelector('#setup-pane-filters input[name="location_keyword"]').value,
                            job_experience_id: document.querySelector('#setup-pane-filters select[name="job_experience_id"]').value
                        })
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (resp) {
                            if (resp.error) {
                                Botble.showError(resp.message || 'Failed to preview jobs.');
                                return;
                            }

                            var jobs = (resp.data && resp.data.items) || [];
                            if (!jobs.length) {
                                previewJobsResult.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-search d-block fs-2 mb-2"></i>No matching jobs found.</div>';
                                return;
                            }

                            var html = '<div class="d-flex justify-content-between align-items-center mb-2">'
                                + '<strong>' + jobs.length + ' matching job(s)</strong>'
                                + '<span class="text-muted small">Showing up to 100 latest jobs with application email</span>'
                                + '</div>'
                                + '<div class="table-responsive" style="max-height:360px;overflow:auto;">'
                                + '<table class="table table-sm table-vcenter mb-0">'
                                + '<thead><tr><th>Job</th><th>Company</th><th>Country</th><th>Posted</th></tr></thead><tbody>';

                            jobs.forEach(function (job) {
                                html += '<tr>'
                                    + '<td><div class="fw-medium">' + (job.url ? '<a href="' + setupEscapeHtml(job.url) + '" target="_blank" rel="noopener">' + setupEscapeHtml(job.name) + '</a>' : setupEscapeHtml(job.name)) + '</div><div class="text-muted small">#' + setupEscapeHtml(job.id) + '</div></td>'
                                    + '<td>' + setupEscapeHtml(job.company || '—') + '</td>'
                                    + '<td>' + setupEscapeHtml([job.country_flag || '', job.country || ''].join(' ').trim() || '—') + '</td>'
                                    + '<td class="text-muted small">' + setupEscapeHtml(job.created_at || '—') + '</td>'
                                    + '</tr>';
                            });

                            html += '</tbody></table></div>';
                            previewJobsResult.innerHTML = html;
                        })
                        .catch(function () {
                            previewJobsResult.innerHTML = '<div class="text-danger text-center py-4">Failed to preview matching jobs.</div>';
                        })
                        .finally(function () {
                            previewJobsBtn.disabled = false;
                            previewJobsBtn.innerHTML = '<i class="ti ti-eye me-1"></i> Preview Jobs From Filters';
                        });
                });

                window.onSetupCandidateSelected = updateCvActions;
                window.onSetupCandidateCleared = updateCvActions;

                document.getElementById('setupModal').addEventListener('hidden.bs.modal', function () {
                    if (uploadedCvObjectUrl) {
                        URL.revokeObjectURL(uploadedCvObjectUrl);
                        uploadedCvObjectUrl = '';
                    }
                    cvFileInput.value = '';
                    analysisPanel.classList.add('d-none');
                    analysisPanel.innerHTML = '';
                    previewJobsResult.innerHTML = 'Analyse a CV or adjust filters, then preview matching jobs before saving.';
                    updateCvActions();
                });

                updateCvActions();
            })();

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

            document.getElementById('editCountrySelectAllBtn').addEventListener('click', function () {
                fetch('{{ route('auto-apply-orders.search-countries') }}?all=1', {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function (r) { return r.json(); })
                    .then(function (resp) {
                        editCountryPicker.setSelected((resp.data || resp).items || []);
                    })
                    .catch(function () {
                        Botble.showError('Failed to load countries.');
                    });
            });

            document.getElementById('editCountryClearAllBtn').addEventListener('click', function () {
                editCountryPicker.clear();
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

            document.getElementById('editCategoryClearBtn').addEventListener('click', function () {
                editCategoryPicker.clear();
            });

            document.getElementById('editExperienceClearBtn').addEventListener('click', function () {
                document.getElementById('editJobExperienceId').value = '';
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

            // Edit modal: CV & AI tab (upload replaces the candidate's account CV on save)
            (function () {
                var cvFileInput = document.getElementById('editCvFile');
                var analyzeCvBtn = document.getElementById('editAnalyzeCvBtn');
                var analyzeAccountCvBtn = document.getElementById('editAnalyzeAccountCvBtn');
                var previewCvBtn = document.getElementById('editPreviewCvBtn');
                var prompt = document.getElementById('editCvPrompt');
                var analysisPanel = document.getElementById('editAnalysisResult');
                var uploadedCvObjectUrl = '';

                function currentCvPreviewUrl() {
                    if (uploadedCvObjectUrl) return uploadedCvObjectUrl;
                    return editSelectedAccount && editSelectedAccount.resume_url ? editSelectedAccount.resume_url : '';
                }

                function updateCvActions() {
                    var hasUpload = cvFileInput.files && cvFileInput.files.length > 0;
                    var hasAccountCv = !!(editSelectedAccount && editSelectedAccount.has_cv);
                    analyzeCvBtn.disabled = !hasUpload;
                    analyzeAccountCvBtn.disabled = !hasAccountCv;
                    previewCvBtn.disabled = !currentCvPreviewUrl();

                    if (hasAccountCv) {
                        prompt.className = 'alert alert-success py-2 px-3 mb-0 small';
                        prompt.innerHTML = '<strong>CV on file:</strong> ' + setupEscapeHtml(editSelectedAccount.resume_name || 'CV on file') + '<div class="text-muted mt-1">Upload a new CV here to replace it and refresh filters.</div>';
                    } else if (editSelectedAccount) {
                        prompt.className = 'alert alert-warning py-2 px-3 mb-0 small';
                        prompt.innerHTML = '<strong>No CV on this account.</strong> Upload a CV here — it will be saved to the candidate’s account.';
                    } else {
                        prompt.className = 'alert alert-info py-2 px-3 mb-0 small';
                        prompt.textContent = 'Loading candidate CV status…';
                    }
                }

                function selectedObjects(ids, names) {
                    ids = Array.isArray(ids) ? ids : [];
                    names = Array.isArray(names) ? names : [];

                    return ids.map(function (id, index) {
                        return { id: id, name: names[index] || ('#' + id) };
                    });
                }

                function renderAnalysis(data) {
                    var keywords = Array.isArray(data.keywords) && data.keywords.length ? data.keywords : (data.keyword ? [data.keyword] : []);
                    var confidence = Number(data.confidence || 0);
                    var confidenceClass = confidence >= 80 ? 'bg-success-subtle text-success' : (confidence >= 60 ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary');
                    var html = '<div class="d-flex align-items-center gap-2 mb-2">'
                        + '<i class="ti ti-file-text text-primary"></i>'
                        + '<strong class="small">AI Analysis Result</strong>'
                        + '<span class="badge ' + confidenceClass + ' ms-auto">' + confidence + '% confidence</span>'
                        + '</div>';

                    if (data.candidate_type) html += '<div class="small fw-semibold mb-2">' + setupEscapeHtml(data.candidate_type) + '</div>';
                    if (data.summary) html += '<p class="text-muted small mb-2">' + setupEscapeHtml(data.summary) + '</p>';

                    html += '<div class="d-flex flex-wrap gap-1">';
                    keywords.forEach(function (keyword) {
                        html += '<span class="badge bg-dark text-white"><i class="ti ti-search me-1"></i>' + setupEscapeHtml(keyword) + '</span>';
                    });
                    (data.category_names || []).forEach(function (name) {
                        html += '<span class="badge bg-secondary text-white">' + setupEscapeHtml(name) + '</span>';
                    });
                    (data.country_names || []).forEach(function (name) {
                        html += '<span class="badge bg-info text-white">' + setupEscapeHtml(name) + '</span>';
                    });
                    if (data.location_keyword) {
                        html += '<span class="badge bg-light border text-dark"><i class="ti ti-map-pin me-1"></i>' + setupEscapeHtml(data.location_keyword) + '</span>';
                    }
                    html += '</div>';

                    if (data.usage && (data.usage.total_tokens || data.usage.estimated_cost_usd)) {
                        var cost = data.usage.estimated_cost_usd ? Number(data.usage.estimated_cost_usd).toFixed(6) : null;
                        html += '<div class="text-muted small mt-2">Usage: ' + setupEscapeHtml(String(data.usage.total_tokens || 0)) + ' tokens' + (cost ? ' · $' + setupEscapeHtml(cost) : '') + '</div>';
                    }

                    html += '<div class="text-success small mt-2"><i class="ti ti-check me-1"></i>Filters applied. Review and adjust before saving.</div>';
                    analysisPanel.innerHTML = html;
                    analysisPanel.classList.remove('d-none');
                }

                function applyEditAnalysis(data) {
                    var keywords = Array.isArray(data.keywords) && data.keywords.length ? data.keywords : (data.keyword ? [data.keyword] : []);
                    document.getElementById('editKeywordsInput').value = keywords.join(', ');

                    if (data.location_keyword) {
                        document.getElementById('editLocationKeyword').value = data.location_keyword;
                    }

                    if (data.job_experience_id) {
                        document.getElementById('editJobExperienceId').value = data.job_experience_id;
                    }

                    if (editCountryPicker) {
                        editCountryPicker.setSelected(selectedObjects(data.country_ids || [], data.country_names || []));
                    }

                    if (editCategoryPicker) {
                        editCategoryPicker.setSelected(selectedObjects(data.category_ids || [], data.category_names || []));
                    }

                    renderAnalysis(data);
                    Botble.showSuccess('AI analysis complete. Auto Apply filters were filled in.');
                }

                function handleAnalysisResponse(resp) {
                    if (resp.error) {
                        Botble.showError(resp.error);
                        return;
                    }

                    applyEditAnalysis(resp.data || {});
                    bootstrap.Tab.getOrCreateInstance(document.getElementById('edit-tab-filters')).show();
                }

                cvFileInput.addEventListener('change', function () {
                    if (uploadedCvObjectUrl) {
                        URL.revokeObjectURL(uploadedCvObjectUrl);
                        uploadedCvObjectUrl = '';
                    }

                    if (this.files && this.files.length) {
                        uploadedCvObjectUrl = URL.createObjectURL(this.files[0]);
                    }

                    updateCvActions();
                });

                analyzeCvBtn.addEventListener('click', function () {
                    if (!cvFileInput.files || !cvFileInput.files.length) {
                        Botble.showError('Please select a CV file first.');
                        return;
                    }

                    analyzeCvBtn.disabled = true;
                    analyzeCvBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Analysing...';

                    var formData = new FormData();
                    formData.append('cv_file', cvFileInput.files[0]);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                    fetch(cvFileInput.dataset.analyzeUrl, { method: 'POST', body: formData, headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(handleAnalysisResponse)
                        .catch(function () { Botble.showError('CV analysis failed.'); })
                        .finally(function () {
                            analyzeCvBtn.innerHTML = '<i class="ti ti-sparkles me-1"></i> Analyse Uploaded CV';
                            updateCvActions();
                        });
                });

                analyzeAccountCvBtn.addEventListener('click', function () {
                    if (!editSelectedAccount || !editSelectedAccount.id) {
                        Botble.showError('This order has no candidate attached.');
                        return;
                    }

                    analyzeAccountCvBtn.disabled = true;
                    analyzeAccountCvBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Analysing...';

                    fetch(analyzeAccountCvBtn.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ account_id: editSelectedAccount.id })
                    })
                        .then(function (r) { return r.json(); })
                        .then(handleAnalysisResponse)
                        .catch(function () { Botble.showError('Account CV analysis failed.'); })
                        .finally(function () {
                            analyzeAccountCvBtn.innerHTML = '<i class="ti ti-file-spark me-1"></i> Analyse Account CV';
                            updateCvActions();
                        });
                });

                previewCvBtn.addEventListener('click', function () {
                    var url = currentCvPreviewUrl();
                    if (!url) return;
                    document.getElementById('cvPreviewFrame').src = url;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('cvPreviewModal')).show();
                });

                window.onEditAccountReady = updateCvActions;
            })();

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

                function renderScoreBadge(score, reasons) {
                    if (typeof score !== 'number') {
                        return '<span class="text-muted small">—</span>';
                    }
                    reasons = reasons || [];
                    var reasonsText = reasons.length ? reasons.join('\n') : 'AI match score based on the candidate\'s CV vs this job description.';
                    var badgeClass = score >= 70 ? 'bg-success' : (score >= 40 ? 'bg-warning' : 'bg-secondary');
                    return '<span class="badge ' + badgeClass + '" title="' + escapeHtml(reasonsText) + '" style="cursor:help;color:#fff">' + score + '%</span>';
                }

                function setScoreCell(jobId, html) {
                    var row = activeJobsList.querySelector('tr[data-job-id="' + jobId + '"]');
                    if (!row) return;
                    var cell = row.querySelector('.js-score-td');
                    if (cell) cell.innerHTML = html;
                }

                function scoreJobAjax(jobId) {
                    return fetch('{{ route("auto-apply-orders.preview") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ account_id: activeJobsAccountId, job_id: jobId, ai_model: 'gpt-4o-mini' })
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (resp) {
                            if (resp.error) return { score: null, reasons: [] };
                            var d = resp.data || {};
                            return {
                                score: (typeof d.score === 'number') ? d.score : null,
                                reasons: d.reasons || []
                            };
                        })
                        .catch(function () {
                            return { score: null, reasons: [] };
                        });
                }

                // AI-scores jobs that don't have a real score yet (i.e. never auto-applied to),
                // a couple at a time so the table fills in progressively without firing every
                // call at once. Results are cached server-side (AutoApplyPreview), so reopening
                // the modal for the same candidate/job won't re-trigger a paid OpenAI call.
                function scoreUnscoredJobs(jobsToScore) {
                    if (!jobsToScore.length) return;

                    var queue = jobsToScore.slice();

                    function next() {
                        if (!queue.length) return;
                        var job = queue.shift();

                        scoreJobAjax(job.id).then(function (result) {
                            setScoreCell(job.id, result.score === null
                                ? '<span class="text-muted small" title="AI scoring failed">—</span>'
                                : renderScoreBadge(result.score, result.reasons));
                            next();
                        });
                    }

                    next();
                    next();
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
                                activeJobsList.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No jobs found.</td></tr>';
                                renderActiveJobsPagination(meta);
                                return;
                            }

                            var rowOffset = meta ? (meta.current_page - 1) * meta.per_page : 0;

                            activeJobsList.innerHTML = jobs.map(function (job, idx) {
                                var rowNumber = rowOffset + idx + 1;
                                var companyCell = '<div class="text-muted small">—</div>';
                                if (job.company) {
                                    var companyNameHtml = job.company_url
                                        ? '<a href="' + escapeHtml(job.company_url) + '" target="_blank" rel="noopener" class="small text-reset">' + escapeHtml(job.company) + '</a>'
                                        : '<span class="small">' + escapeHtml(job.company) + '</span>';
                                    companyCell = '<div class="d-flex align-items-center gap-2">'
                                        + (job.company_logo ? '<img src="' + escapeHtml(job.company_logo) + '" alt="" style="width:28px;height:28px;object-fit:contain;border-radius:4px;border:1px solid #eee;background:#fff;padding:2px">' : '')
                                        + companyNameHtml
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

                                var scoreCell = job.needs_ai_score
                                    ? '<span class="text-muted small d-inline-flex align-items-center gap-1">'
                                        + '<span class="spinner-border spinner-border-sm" style="width:.85rem;height:.85rem;"></span> Scoring…</span>'
                                    : renderScoreBadge(job.score, job.match_reasons);

                                var logStatusBadges = {
                                    sent: 'bg-success',
                                    failed: 'bg-danger',
                                    bounced: 'bg-danger',
                                    skipped_low_score: 'bg-secondary'
                                };
                                var logStatusLabels = {
                                    sent: 'Sent',
                                    failed: 'Failed',
                                    bounced: 'Bounced',
                                    skipped_low_score: 'Skipped (low score)'
                                };

                                var actionsCell;
                                if (job.log_status) {
                                    var badgeClass = logStatusBadges[job.log_status] || 'bg-secondary';
                                    var badgeLabel = logStatusLabels[job.log_status] || job.log_status;
                                    var badgeTitle = job.log_sent_at ? 'On ' + job.log_sent_at : '';
                                    if (job.log_error) {
                                        badgeTitle += (badgeTitle ? ' — ' : '') + job.log_error;
                                    }
                                    actionsCell = '<div class="d-inline-flex gap-1 align-items-center">'
                                        + '<button type="button" class="btn btn-sm btn-outline-info js-preview-active-job" title="Preview application" data-job-id="' + job.id + '" data-job-name="' + escapeHtml(job.name) + '">Preview</button>'
                                        + '<span class="badge ' + badgeClass + '" title="' + escapeHtml(badgeTitle) + '" style="cursor:help;color:#fff">' + escapeHtml(badgeLabel) + '</span>'
                                        + '</div>';
                                } else {
                                    actionsCell = '<div class="d-inline-flex gap-1">'
                                        + '<button type="button" class="btn btn-sm btn-outline-info js-preview-active-job" title="Preview application" data-job-id="' + job.id + '" data-job-name="' + escapeHtml(job.name) + '">Preview</button>'
                                        + '<button type="button" class="btn btn-sm btn-success js-send-active-job" title="Send application" data-bs-toggle="modal" data-bs-target="#sendAutoApplyModal" data-job-id="' + job.id + '" data-job-name="' + escapeHtml(job.name) + '">Send</button>'
                                        + '</div>';
                                }

                                return '<tr data-job-id="' + job.id + '">'
                                    + '<td class="text-muted">' + rowNumber + '</td>'
                                    + '<td><div class="fw-medium">' + (job.url ? '<a href="' + escapeHtml(job.url) + '" target="_blank" rel="noopener" class="text-reset">' + escapeHtml(job.name) + '</a>' : escapeHtml(job.name)) + '</div><div class="text-muted small">#' + job.id + '</div></td>'
                                    + '<td>' + companyCell + '</td>'
                                    + '<td>' + countryCell + '</td>'
                                    + '<td class="text-muted small">' + escapeHtml(job.apply_email) + '</td>'
                                    + '<td>' + datesCell + '</td>'
                                    + '<td class="js-score-td">' + scoreCell + '</td>'
                                    + '<td class="text-end">' + actionsCell + '</td>'
                                    + '</tr>';
                            }).join('');
                            renderActiveJobsPagination(meta);
                            scoreUnscoredJobs(jobs.filter(function (j) { return j.needs_ai_score; }));
                        })
                        .catch(function () {
                            activeJobsList.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Failed to load active jobs.</td></tr>';
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

                // Send all unsent jobs on the current page, one at a time, with a live progress bar.
                var sendAllBtn = document.getElementById('activeJobsSendAllBtn');
                var sendAllProgressWrap = document.getElementById('activeJobsSendAllProgress');
                var sendAllProgressBar = document.getElementById('activeJobsSendAllProgressBar');
                var sendAllStatusText = document.getElementById('activeJobsSendAllStatusText');
                var sendAllConfirmBtn = document.getElementById('sendAllAutoApplyConfirmBtn');
                var sendAllInProgress = false;

                function setRowSending(jobId) {
                    var row = activeJobsList.querySelector('tr[data-job-id="' + jobId + '"]');
                    if (!row) return;
                    var cell = row.querySelector('td:last-child');
                    if (cell) {
                        cell.innerHTML = '<div class="d-inline-flex align-items-center gap-2 text-muted small">'
                            + '<span class="spinner-border spinner-border-sm text-success"></span> Sending…</div>';
                    }
                    row.style.transition = 'background-color .3s ease';
                    row.style.backgroundColor = '#fff8e6';
                }

                function setRowResult(jobId, ok, message) {
                    var row = activeJobsList.querySelector('tr[data-job-id="' + jobId + '"]');
                    if (!row) return;
                    var cell = row.querySelector('td:last-child');
                    if (cell) {
                        var badgeClass = ok ? 'bg-info' : 'bg-danger';
                        var badgeLabel = ok ? 'Queued' : 'Failed';
                        cell.innerHTML = '<div class="d-inline-flex gap-1 align-items-center">'
                            + '<span class="badge ' + badgeClass + '" title="' + escapeHtml(message || '') + '" style="cursor:help;color:#fff">' + badgeLabel + '</span>'
                            + '</div>';
                    }
                    row.style.backgroundColor = ok ? '#e9f9ee' : '#fdeceb';
                    setTimeout(function () {
                        row.style.backgroundColor = '';
                    }, 1000);
                }

                function sendJobAjax(jobId) {
                    var formData = new FormData();
                    formData.append('account_id', activeJobsAccountId);
                    formData.append('job_id', jobId);

                    return fetch('{{ route('auto-apply-orders.send-job') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                        .then(function (r) {
                            return r.json().catch(function () { return {}; }).then(function (data) {
                                return { ok: r.ok, data: data };
                            });
                        })
                        .then(function (result) {
                            var data = result.data || {};
                            return { success: result.ok && !data.error, message: data.message || '' };
                        })
                        .catch(function () {
                            return { success: false, message: 'Network error' };
                        });
                }

                function runSendAll() {
                    if (sendAllInProgress) return;

                    var buttons = Array.prototype.slice.call(activeJobsList.querySelectorAll('.js-send-active-job'));
                    var jobs = buttons.map(function (btn) {
                        return { id: btn.dataset.jobId, name: btn.dataset.jobName || ('#' + btn.dataset.jobId) };
                    });

                    if (!jobs.length) return;

                    sendAllInProgress = true;
                    sendAllBtn.disabled = true;
                    sendAllBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending…';
                    sendAllProgressWrap.classList.remove('d-none');
                    sendAllProgressBar.style.width = '0%';

                    var total = jobs.length;
                    var done = 0;
                    var okCount = 0;
                    var failCount = 0;

                    function next() {
                        if (done >= total) {
                            sendAllInProgress = false;
                            sendAllBtn.disabled = false;
                            sendAllBtn.innerHTML = '<i class="ti ti-send me-1"></i> Send All';
                            sendAllStatusText.textContent = 'Done — ' + okCount + ' queued, ' + failCount + ' failed.';

                            if (okCount > 0) {
                                Botble.showSuccess(okCount + ' application(s) queued for sending.');
                            }
                            if (failCount > 0) {
                                Botble.showError(failCount + ' application(s) failed to queue.');
                            }

                            setTimeout(function () {
                                sendAllProgressWrap.classList.add('d-none');
                            }, 2500);
                            return;
                        }

                        var job = jobs[done];
                        setRowSending(job.id);
                        sendAllStatusText.textContent = 'Sending ' + (done + 1) + ' / ' + total + ' — ' + job.name;

                        sendJobAjax(job.id).then(function (result) {
                            done++;
                            if (result.success) {
                                okCount++;
                            } else {
                                failCount++;
                            }
                            setRowResult(job.id, result.success, result.message);

                            var pct = Math.round((done / total) * 100);
                            sendAllProgressBar.style.width = pct + '%';

                            setTimeout(next, 600);
                        });
                    }

                    next();
                }

                sendAllBtn.addEventListener('click', function () {
                    var pendingCount = activeJobsList.querySelectorAll('.js-send-active-job').length;
                    if (!pendingCount) {
                        Botble.showError('No unsent jobs on this page.');
                        return;
                    }
                    document.getElementById('sendAllAutoApplyLabel').textContent =
                        'Send applications for ' + activeJobsCandidateLabel + ' to ' + pendingCount + ' job(s) on this page?';
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('sendAllAutoApplyModal')).show();
                });

                sendAllConfirmBtn.addEventListener('click', function () {
                    var modalEl = document.getElementById('sendAllAutoApplyModal');
                    var modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();
                    runSendAll();
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
