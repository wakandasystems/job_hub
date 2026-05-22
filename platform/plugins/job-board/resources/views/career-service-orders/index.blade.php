@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Total Orders</div>
                    <div class="h2 mb-0">{{ number_format($stats['total']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Paid</div>
                    <div class="h2 mb-0">{{ number_format($stats['paid']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Unassigned</div>
                    <div class="h2 mb-0">{{ number_format($stats['unassigned']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">In Progress</div>
                    <div class="h2 mb-0">{{ number_format($stats['in_progress']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>Career Service Orders</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body>
            <form method="GET" action="{{ route('career-service-orders.index') }}" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search customer, coach, service or charge ID">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All payment statuses</option>
                        @foreach(['pending' => 'Pending', 'paid' => 'Paid', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="delivery_status">
                        <option value="">All delivery statuses</option>
                        @foreach($deliveryStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('delivery_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a class="btn btn-outline-secondary" href="{{ route('career-service-orders.index') }}">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-vcenter table-striped">
                    <thead>
                        <tr>
                            <th width="70">ID</th>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Coach</th>
                            <th>Payment</th>
                            <th>Delivery</th>
                            <th>CV Score</th>
                            <th>Created</th>
                            <th width="80"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>#{{ $order->id }}</td>
                                <td>
                                    <strong>{{ $order->service_name }}</strong>
                                    <div class="text-muted">{{ $order->currency }} {{ number_format($order->amount, 2) }}</div>
                                </td>
                                <td>
                                    {{ $order->customer_name ?: 'N/A' }}
                                    <div class="text-muted">{{ $order->customer_email ?: 'N/A' }}</div>
                                </td>
                                <td>{{ $order->assigned_coach_name ?: 'Unassigned' }}</td>
                                <td>
                                    <span class="badge bg-{{ $order->status === 'paid' ? 'success' : 'warning' }} text-white">{{ ucfirst($order->status) }}</span>
                                    @if($order->charge_id)
                                        <div class="text-muted">{{ $order->charge_id }}</div>
                                    @endif
                                </td>
                                <td>{{ $deliveryStatuses[$order->delivery_status] ?? ucfirst($order->delivery_status) }}</td>
                                <td>{{ $order->ai_cv_score !== null ? $order->ai_cv_score . '/100' : 'N/A' }}</td>
                                <td>{{ $order->created_at?->toDateTimeString() }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('career-service-orders.edit', $order) }}"
                                            class="btn btn-sm btn-primary btn-icon"
                                            data-bs-toggle="tooltip"
                                            data-bs-title="Manage">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <span data-bs-toggle="tooltip" data-bs-title="Delete" class="d-inline-block">
                                            <button type="button"
                                                class="btn btn-sm btn-danger btn-icon"
                                                data-bs-toggle="modal"
                                                data-bs-target="#delete-order-modal"
                                                data-action="{{ route('career-service-orders.destroy', $order) }}"
                                                data-label="#{{ $order->id }} — {{ Str::limit($order->service_name, 30) }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No career service orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $orders->links() }}
        </x-core::card.body>
    </x-core::card>
@endsection

{{-- Delete confirmation modal --}}
<div class="modal fade" id="delete-order-modal" tabindex="-1" aria-labelledby="delete-order-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger" id="delete-order-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="me-1 mb-1">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 9v4"/>
                        <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/>
                        <path d="M12 16h.01"/>
                    </svg>
                    Delete Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Are you sure you want to permanently delete order:</p>
                <p class="fw-semibold" id="delete-order-label-text"></p>
                <p class="text-muted small mb-0">This will also remove any uploaded CV files and cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delete-order-form" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Yes, delete it</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('footer')
<script>
    document.getElementById('delete-order-modal').addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        document.getElementById('delete-order-form').action = btn.dataset.action;
        document.getElementById('delete-order-label-text').textContent = btn.dataset.label;
    });
</script>
@endpush
