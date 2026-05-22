@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Total</div>
                    <div class="h2 mb-0">{{ number_format($stats['total']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-4 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Active</div>
                    <div class="h2 mb-0 text-success">{{ number_format($stats['active']) }}</div>
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
    </div>

    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>Employer Subscriptions</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body>
            <form method="GET" action="{{ route('employer-subscriptions.index') }}" class="row g-2 mb-3">
                <div class="col-md-5">
                    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search by name or email">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All statuses</option>
                        @foreach(\Botble\JobBoard\Models\EmployerSubscription::statuses() as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a class="btn btn-outline-secondary" href="{{ route('employer-subscriptions.index') }}">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-vcenter table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employer</th>
                            <th>Plan</th>
                            <th>Cycle</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Ends</th>
                            <th>Posts Used</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $sub)
                            @php
                                $badge = match($sub->status) {
                                    'active'    => 'success',
                                    'expired'   => 'secondary',
                                    'cancelled' => 'danger',
                                    default     => 'warning',
                                };
                            @endphp
                            <tr>
                                <td>#{{ $sub->id }}</td>
                                <td>
                                    {{ $sub->account?->name ?? 'N/A' }}
                                    <div class="text-muted small">{{ $sub->account?->email }}</div>
                                </td>
                                <td>{{ $sub->package?->name ?? 'N/A' }}</td>
                                <td>{{ ucfirst($sub->billing_cycle) }}</td>
                                <td>{{ $sub->currency }} {{ number_format($sub->amount, 2) }}</td>
                                <td><span class="badge bg-{{ $badge }} text-white">{{ ucfirst($sub->status) }}</span>
                                    @if($sub->cancel_at_period_end)
                                        <span class="badge bg-warning text-dark ms-1">Cancels at period end</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $sub->ends_at?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    @php $limit = (int)($sub->package?->posts_per_cycle ?? 0); @endphp
                                    {{ $sub->posts_used_this_cycle }} / {{ $limit === 0 ? '∞' : $limit }}
                                </td>
                                <td class="text-end">
                                    @if($sub->status === 'pending')
                                        <button type="button" class="btn btn-sm btn-success"
                                            data-bs-toggle="modal" data-bs-target="#activateModal"
                                            data-action="{{ route('employer-subscriptions.activate', $sub) }}"
                                            data-label="{{ $sub->account?->name }} — {{ $sub->package?->name }}">
                                            Activate
                                        </button>
                                    @endif
                                    @if(in_array($sub->status, ['pending','active']))
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal" data-bs-target="#cancelModal"
                                            data-action="{{ route('employer-subscriptions.cancel', $sub) }}"
                                            data-label="{{ $sub->account?->name }}">
                                            Cancel
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No subscriptions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        </x-core::card.body>
    </x-core::card>

    {{-- Activate modal --}}
    <div class="modal fade" id="activateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-check text-success fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Activate this subscription?</h6>
                    <p class="text-muted small mb-4" id="activateModalLabel">Employer will gain immediate access to plan features.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <form id="activateForm" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success px-4">Activate</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cancel modal --}}
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-x text-danger fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Cancel subscription?</h6>
                    <p class="text-muted small mb-3" id="cancelModalLabel">Employer will lose access immediately.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Back</button>
                        <form id="cancelForm" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger px-4">Cancel Sub</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
    <script>
        document.getElementById('activateModal').addEventListener('show.bs.modal', function(e) {
            document.getElementById('activateForm').action = e.relatedTarget.dataset.action;
            document.getElementById('activateModalLabel').textContent = e.relatedTarget.dataset.label;
        });
        document.getElementById('cancelModal').addEventListener('show.bs.modal', function(e) {
            document.getElementById('cancelForm').action = e.relatedTarget.dataset.action;
            document.getElementById('cancelModalLabel').textContent = 'Cancel subscription for: ' + e.relatedTarget.dataset.label + '?';
        });
    </script>
    @endpush
@endsection
