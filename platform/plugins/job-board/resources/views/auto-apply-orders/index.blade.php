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
            <div class="card-actions">
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
                            <th width="180"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>#{{ $order->id }}</td>
                                <td>
                                    <div class="fw-medium">{{ $order->account?->name ?? 'Deleted' }}</div>
                                    <div class="text-muted small">{{ $order->account?->email }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-primary text-white">{{ $order->planLabel() }}</span>
                                    <div class="text-muted small">{{ $order->duration_days }} days</div>
                                </td>
                                <td>{{ $order->applications_allowed == 0 ? 'Unlimited' : $order->applications_allowed }}</td>
                                <td>{{ $order->currency }} {{ number_format($order->amount, 2) }}</td>
                                <td>
                                    @php $payBadge = match($order->status) { 'approved' => 'success', 'rejected' => 'danger', default => 'warning' }; @endphp
                                    <span class="badge bg-{{ $payBadge }} text-white">{{ ucfirst($order->status) }}</span>
                                    @if($order->payment_method)
                                        <div class="text-muted small">{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badge = match($order->admin_status) {
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            default    => 'warning',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }} text-white">{{ ucfirst($order->admin_status) }}</span>
                                </td>
                                <td>{{ $order->created_at?->toDateString() }}</td>
                                <td class="text-end">
                                    @if($order->admin_status === 'pending')
                                        <button type="button" class="btn btn-sm btn-success"
                                            data-bs-toggle="modal" data-bs-target="#approveModal"
                                            data-action="{{ route('auto-apply-orders.approve', $order) }}"
                                            data-label="{{ $order->account?->name ?? '' }} — {{ $order->planLabel() }}">
                                            Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal" data-bs-target="#rejectModal"
                                            data-action="{{ route('auto-apply-orders.reject', $order) }}"
                                            data-label="{{ $order->account?->name ?? '' }}">
                                            Reject
                                        </button>
                                    @else
                                        <span class="text-muted small">{{ $order->approved_at?->toDateString() ?? '—' }}</span>
                                    @endif
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
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Candidate (Account ID)</label>
                                <input type="number" name="account_id" class="form-control" required placeholder="Account ID">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Match Score Threshold</label>
                                <div class="input-group">
                                    <input type="number" name="match_score_threshold" class="form-control" value="60" min="0" max="100">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keywords (comma-separated)</label>
                                <input type="text" class="form-control" id="keywordsInput" placeholder="e.g. developer, engineer, marketing">
                                <input type="hidden" name="keywords[]" id="keywordsHidden">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Location Keyword</label>
                                <input type="text" name="location_keyword" class="form-control" placeholder="e.g. Johannesburg">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                    <label class="form-check-label">Activate immediately</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <hr>
                                <h6>Grant Free Quota</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="grant_free_quota" value="0">
                                    <input class="form-check-input" type="checkbox" name="grant_free_quota" value="1" id="grantQuotaCheck">
                                    <label class="form-check-label">Grant free applications</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <input type="number" name="free_applications" class="form-control" value="10" min="1" max="1000" placeholder="Number of applications">
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
                        <div class="mb-2"><strong>Match Score:</strong> <span id="previewScore" class="badge bg-info"></span></div>
                        <div class="mb-2"><strong>Reasons:</strong> <span id="previewReasons"></span></div>
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

    @push('footer')
        <script>
            document.getElementById('approveModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('approveForm').action = btn.dataset.action;
                document.getElementById('approveModalLabel').textContent = btn.dataset.label + ' — will be activated.';
            });
            document.getElementById('rejectModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('rejectForm').action = btn.dataset.action;
            });

            // Keywords comma-to-array
            document.querySelector('#setupModal form').addEventListener('submit', function() {
                var input = document.getElementById('keywordsInput');
                var hidden = document.getElementById('keywordsHidden');
                hidden.name = '';
                var keywords = input.value.split(',').map(function(k) { return k.trim(); }).filter(Boolean);
                keywords.forEach(function(kw) {
                    var h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = 'keywords[]';
                    h.value = kw;
                    input.parentNode.appendChild(h);
                });
            });

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
