@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bolder">Lead Request #{{ $lead->getKey() }}</h4>
            <div class="text-muted small">
                Submitted {{ $lead->created_at?->format('Y-m-d H:i') }}
                <span class="badge bg-{{ $lead->statusBadgeClass() }} text-white ms-2">{{ $lead->statusLabel() }}</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('sales-agent-leads.index') }}" class="btn btn-outline-dark btn-sm">Back to Lead Requests</a>
            @if ($lead->onboardingAdminUrl())
                <a href="{{ $lead->onboardingAdminUrl() }}" class="btn btn-outline-primary btn-sm">Open Product Queue</a>
            @endif
        </div>
    </div>

    @if (session('success_msg'))
        <div class="alert alert-success">{{ session('success_msg') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <x-core::card class="mb-3">
                <x-core::card.header>
                    <x-core::card.title>Lead Details</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Candidate</div>
                            <div class="fw-semibold">{{ $lead->candidate_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Phone</div>
                            <div class="fw-semibold">{{ $lead->candidate_phone }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Email</div>
                            <div class="fw-semibold">{{ $lead->candidate_email ?: 'Not provided' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Linked account</div>
                            <div class="fw-semibold">
                                @if ($lead->account)
                                    {{ trim((string) $lead->account->name) ?: $lead->account->email }} (#{{ $lead->account->getKey() }})
                                @else
                                    Not linked yet
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Campaign</div>
                            <div class="fw-semibold">{{ $lead->campaign?->name ?: 'Campaign removed' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Product</div>
                            <div class="fw-semibold">{{ $lead->resolvedProductLabel() }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Agent</div>
                            <div class="fw-semibold">{{ $lead->salesAgent?->name ?: 'Agent removed' }} (<code>{{ $lead->sales_agent_code }}</code>)</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Promo</div>
                            <div class="fw-semibold">
                                {{ $lead->promo_price ?: 'N/A' }}
                                @if ($lead->promo_original_price)
                                    <span class="text-decoration-line-through text-muted ms-1">{{ $lead->promo_original_price }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Customer notes</div>
                            <div class="fw-semibold">{{ $lead->customer_notes ?: 'None' }}</div>
                        </div>
                    </div>
                </x-core::card.body>
            </x-core::card>

            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Status Timeline</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="small text-muted mb-2">Admin notified: {{ $lead->notified_admin_at?->format('Y-m-d H:i') ?: 'No' }}</div>
                    <div class="small text-muted mb-2">Contacted: {{ $lead->contacted_at?->format('Y-m-d H:i') ?: 'No' }}</div>
                    <div class="small text-muted mb-2">Paid: {{ $lead->paid_at?->format('Y-m-d H:i') ?: 'No' }}</div>
                    <div class="small text-muted mb-2">Onboarded: {{ $lead->onboarded_at?->format('Y-m-d H:i') ?: 'No' }}</div>
                    <div class="small text-muted">Rejected: {{ $lead->rejected_at?->format('Y-m-d H:i') ?: 'No' }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>

        <div class="col-lg-5">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Update Lead</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <form method="POST" action="{{ route('sales-agent-leads.update', $lead->getKey()) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Admin Notes</label>
                            <textarea name="admin_notes" class="form-control" rows="8" placeholder="Add payment notes, onboarding notes, or follow-up remarks...">{{ old('admin_notes', $lead->admin_notes) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Lead Update</button>
                    </form>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>
@endsection
