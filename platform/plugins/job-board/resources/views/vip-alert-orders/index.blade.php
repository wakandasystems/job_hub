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
            <x-core::card.title>VIP Alert Orders</x-core::card.title>
            <div class="card-actions">
                <a href="{{ route('job-board.settings.vip-alert-plans') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-settings-dollar me-1"></i> Manage Plans
                </a>
                <a href="{{ route('public.vip-alerts.plans') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-external-link me-1"></i> Public Plans Page
                </a>
            </div>
        </x-core::card.header>
        <x-core::card.body>
            <form method="GET" action="{{ route('vip-alert-orders.index') }}" class="row g-2 mb-3">
                <div class="col-md-5">
                    <input class="form-control" name="q" value="{{ request('q') }}"
                           placeholder="Search by name, email or phone">
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
                    <a class="btn btn-outline-secondary" href="{{ route('vip-alert-orders.index') }}">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-vcenter table-striped">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>Customer</th>
                            <th>WhatsApp</th>
                            <th>Plan</th>
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
                                    <div class="fw-medium">{{ $order->candidate_name }}</div>
                                    <div class="text-muted small">{{ $order->candidate_email }}</div>
                                </td>
                                <td>
                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $order->candidate_phone) }}" target="_blank" rel="noopener" class="text-success">
                                        {{ $order->candidate_phone }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-primary text-white">{{ $order->planLabel() }}</span>
                                    <div class="text-muted small">{{ $order->duration_days }} days</div>
                                </td>
                                <td>{{ $order->currency }} {{ number_format($order->amount, 2) }}</td>
                                <td>
                                    @php $payBadge = match($order->payment_status) { 'paid' => 'success', 'failed' => 'danger', default => 'warning' }; @endphp
                                    <span class="badge bg-{{ $payBadge }} text-white">{{ ucfirst($order->payment_status) }}</span>
                                    @if($order->payment_method)
                                        <div class="text-muted small">{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</div>
                                    @endif
                                    @if($order->charge_id)
                                        <div class="text-muted small font-monospace" style="font-size:.7rem;">{{ Str::limit($order->charge_id, 20) }}</div>
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
                                            data-action="{{ route('vip-alert-orders.approve', $order) }}"
                                            data-label="{{ $order->candidate_name }} — {{ $order->planLabel() }}">
                                            Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal" data-bs-target="#rejectModal"
                                            data-action="{{ route('vip-alert-orders.reject', $order) }}"
                                            data-label="{{ $order->candidate_name }}">
                                            Reject
                                        </button>
                                    @elseif($order->admin_status === 'approved' && $order->candidate_alert_id)
                                        <a href="{{ route('job-board.candidate-alerts.index') }}"
                                           class="btn btn-sm btn-outline-success">
                                            <i class="ti ti-eye me-1"></i>View Alert
                                        </a>
                                    @else
                                        <span class="text-muted small">{{ $order->approved_at?->toDateString() ?? '—' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No VIP alert orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $orders->links() }}
        </x-core::card.body>
    </x-core::card>

    {{-- Approve modal --}}
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-check text-success fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Activate this VIP Alert?</h6>
                    <p class="text-muted small mb-1" id="approveModalLabel">This will create the WhatsApp alert and send a welcome message.</p>
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
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-x text-danger fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Reject this order?</h6>
                    <p class="text-muted small mb-2" id="rejectModalLabel">The subscriber will not be activated.</p>
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <form id="rejectForm" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger px-4">Reject</button>
                        </form>
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
                document.getElementById('approveModalLabel').textContent = btn.dataset.label + ' — will be activated and sent a welcome WhatsApp message.';
            });
            document.getElementById('rejectModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('rejectForm').action = btn.dataset.action;
                document.getElementById('rejectModalLabel').textContent = btn.dataset.label + ' — will not be activated.';
            });
        </script>
    @endpush
@endsection
