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
                            <th width="90"></th>
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
                                    <a href="{{ route('career-service-orders.edit', $order) }}" class="btn btn-sm btn-primary">Manage</a>
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
