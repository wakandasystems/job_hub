@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bolder">Sales Agent Lead Requests</h4>
            <div class="text-muted small">Public campaign link submissions waiting for follow-up and onboarding.</div>
        </div>
    </div>

    <x-core::card class="mb-3">
        <x-core::card.body>
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" placeholder="Search name, phone, email, or agent code" value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="product_type" class="form-select">
                        <option value="">All products</option>
                        @foreach (\Botble\JobBoard\Models\SalesAgentCampaign::productTypeOptions() as $value => $label)
                            <option value="{{ $value }}" @selected(request('product_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </x-core::card.body>
    </x-core::card>

    <x-core::card>
        <x-core::card.body class="p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Lead</th>
                            <th>Campaign</th>
                            <th>Agent</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr>
                                <td>#{{ $lead->getKey() }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $lead->candidate_name }}</div>
                                    <div class="text-muted small">{{ $lead->candidate_phone }}</div>
                                    @if ($lead->candidate_email)
                                        <div class="text-muted small">{{ $lead->candidate_email }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $lead->campaign?->name ?: 'Campaign removed' }}</div>
                                    <div class="text-muted small">{{ $lead->resolvedProductLabel() }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $lead->salesAgent?->name ?: 'Agent removed' }}</div>
                                    <div class="text-muted small"><code>{{ $lead->sales_agent_code }}</code></div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $lead->statusBadgeClass() }} text-white">{{ $lead->statusLabel() }}</span>
                                </td>
                                <td>{{ $lead->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('sales-agent-leads.show', $lead->getKey()) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No lead requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-core::card.body>
    </x-core::card>

    <div class="mt-3">
        {{ $leads->links() }}
    </div>
@endsection
