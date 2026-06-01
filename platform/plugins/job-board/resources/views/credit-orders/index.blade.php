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
                    <div class="text-muted">Pending Approval</div>
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
            <x-core::card.title>Credit Orders</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body>
            <form method="GET" action="{{ route('credit-orders.index') }}" class="row g-2 mb-3">
                <div class="col-md-5">
                    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search by employer name or email">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All statuses</option>
                        @foreach(\Botble\JobBoard\Models\CreditOrder::statuses() as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a class="btn btn-outline-secondary" href="{{ route('credit-orders.index') }}">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-vcenter table-striped">
                    <thead>
                        <tr>
                            <th width="60">#</th>
                            <th>Employer</th>
                            <th>Package</th>
                            <th>Credits</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Reference</th>
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
                                    {{ $order->account?->name ?? 'N/A' }}
                                    <div class="text-muted small">{{ $order->account?->email ?? '' }}</div>
                                </td>
                                <td>{{ $order->package?->name ?? 'N/A' }}</td>
                                <td>{{ number_format($order->credits) }}</td>
                                <td>{{ $order->currency }} {{ number_format($order->amount, 2) }}</td>
                                <td>
                                    {{ $order->payment_method ? ucwords(str_replace('_', ' ', $order->payment_method)) : '—' }}
                                    @if($order->charge_id)
                                        <div class="text-muted small">{{ $order->charge_id }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($order->payment_reference)
                                        <span class="badge bg-blue-lt text-truncate" style="max-width:140px;" title="{{ $order->payment_reference }}">
                                            {{ $order->payment_reference }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badge = match($order->status) {
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            default    => 'warning',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }} text-white">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td>{{ $order->created_at?->toDateTimeString() }}</td>
                                <td class="text-end">
                                    @if($order->status === 'pending')
                                        <button type="button" class="btn btn-sm btn-success"
                                            data-bs-toggle="modal" data-bs-target="#approveModal"
                                            data-action="{{ route('credit-orders.approve', $order) }}"
                                            data-label="{{ $order->account?->name ?? 'this order' }} — {{ $order->package?->name ?? '' }} ({{ number_format($order->credits) }} credits)">
                                            Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal" data-bs-target="#rejectModal"
                                            data-action="{{ route('credit-orders.reject', $order) }}"
                                            data-label="{{ $order->account?->name ?? 'this order' }} — {{ $order->package?->name ?? '' }}">
                                            Reject
                                        </button>
                                    @else
                                        <span class="text-muted small">{{ $order->approved_at?->toDateString() ?? '—' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">No credit orders found.</td>
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
                    <h6 class="fw-semibold mb-1">Approve this credit order?</h6>
                    <p class="text-muted small mb-4" id="approveModalLabel">Credits will be added to the employer's account immediately.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <form id="approveForm" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success px-4">Approve</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-x text-danger fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Reject this credit order?</h6>
                    <p class="text-muted small mb-3" id="rejectModalLabel">The employer will not receive any credits.</p>
                    <form id="rejectForm" method="POST">
                        @csrf
                        <div class="mb-3 text-start">
                            <label class="form-label small">Reason / Notes (optional)</label>
                            <textarea class="form-control form-control-sm" name="notes" rows="3" placeholder="Explain the rejection reason…"></textarea>
                        </div>
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger px-4">Reject</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
        <script>
            document.getElementById('approveModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('approveForm').action = btn.dataset.action;
                document.getElementById('approveModalLabel').textContent = btn.dataset.label;
            });
            document.getElementById('rejectModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('rejectForm').action = btn.dataset.action;
                document.getElementById('rejectModalLabel').textContent = btn.dataset.label;
            });
        </script>
    @endpush
@endsection
