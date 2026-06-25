@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <x-core::card>
                <x-core::card.body class="text-center">
                    <div class="text-muted small">Total Unpaid</div>
                    <div class="fs-3 fw-bold text-warning">{{ number_format($totalUnpaid, 2) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-6">
            <x-core::card>
                <x-core::card.body class="text-center">
                    <div class="text-muted small">Total Paid</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($totalPaid, 2) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>Commission Ledger</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body>
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="sales_agent_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All agents</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}" @selected(request('sales_agent_id') == $agent->id)>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option>
                        <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    </select>
                </div>
            </form>

            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <input type="text" class="form-control form-control-sm" id="bulkPayoutNotes" placeholder="Optional payout note/reference" style="max-width:320px;">
                <button type="button" class="btn btn-sm btn-success" id="btnBulkMarkPaid" data-url="{{ route('sales-agent-commissions.bulk-mark-paid') }}">
                    <x-core::icon name="ti ti-check" class="me-1" /> Mark Selected Paid
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th style="width:36px;"><input type="checkbox" class="form-check-input" id="checkAllCommissions"></th>
                            <th>Agent</th>
                            <th>Order</th>
                            <th>Sale Amount</th>
                            <th>Rate</th>
                            <th>Commission</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($commissions as $commission)
                            <tr>
                                <td>
                                    @if ($commission->status === 'unpaid')
                                        <input type="checkbox" class="form-check-input js-commission-check" value="{{ $commission->getKey() }}">
                                    @endif
                                </td>
                                <td>
                                    @if ($commission->salesAgent)
                                        <a href="{{ route('sales-agents.show', $commission->salesAgent->getKey()) }}">{{ $commission->salesAgent->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ str_replace('_', ' ', $commission->order_type) }} #{{ $commission->order_id }}</td>
                                <td>{{ $commission->currency }} {{ number_format($commission->amount, 2) }}</td>
                                <td>{{ rtrim(rtrim(number_format($commission->commission_rate, 2), '0'), '.') }}%</td>
                                <td>{{ $commission->currency }} {{ number_format($commission->commission_amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $commission->status === 'paid' ? 'success' : 'warning' }} text-white">{{ ucfirst($commission->status) }}</span>
                                </td>
                                <td class="text-end">
                                    @if ($commission->status === 'unpaid')
                                        <button type="button" class="btn btn-sm btn-success js-commission-action" data-url="{{ route('sales-agent-commissions.mark-paid', $commission->getKey()) }}" data-title="Mark commission paid?" data-text="This will mark this commission as paid.">Mark Paid</button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary js-commission-action" data-url="{{ route('sales-agent-commissions.mark-unpaid', $commission->getKey()) }}" data-title="Mark commission unpaid?" data-text="This will reopen this commission as unpaid.">Mark Unpaid</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No commissions recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $commissions->links() }}
        </x-core::card.body>
    </x-core::card>

    <div class="modal fade" id="confirmCommissionActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:52px;height:52px;">
                            <x-core::icon name="ti ti-check" class="text-success fs-3" />
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1" id="confirmCommissionActionTitle">Approve action?</h6>
                    <p class="text-muted small mb-4" id="confirmCommissionActionText">Please confirm this commission action.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success px-4" id="btnConfirmCommissionAction">Approve</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
        <script>
            var pendingCommissionAction = null;
            var commissionModalElement = document.getElementById('confirmCommissionActionModal');
            var commissionModal = commissionModalElement ? new bootstrap.Modal(commissionModalElement) : null;

            document.addEventListener('click', function (event) {
                var button = event.target.closest('.js-commission-action');

                if (!button) {
                    return;
                }

                pendingCommissionAction = {
                    type: 'single',
                    url: button.dataset.url,
                };

                document.getElementById('confirmCommissionActionTitle').textContent = button.dataset.title || 'Approve action?';
                document.getElementById('confirmCommissionActionText').textContent = button.dataset.text || 'Please confirm this commission action.';
                commissionModal?.show();
            });

            document.getElementById('checkAllCommissions')?.addEventListener('change', function (event) {
                document.querySelectorAll('.js-commission-check').forEach(function (checkbox) {
                    checkbox.checked = event.target.checked;
                });
            });

            document.getElementById('btnBulkMarkPaid')?.addEventListener('click', function () {
                var ids = Array.from(document.querySelectorAll('.js-commission-check:checked')).map(function (checkbox) {
                    return checkbox.value;
                });

                if (!ids.length) {
                    Botble.showError('Select at least one unpaid commission.');
                    return;
                }

                pendingCommissionAction = {
                    type: 'bulk',
                    url: this.dataset.url,
                    ids: ids,
                    notes: document.getElementById('bulkPayoutNotes').value,
                };

                document.getElementById('confirmCommissionActionTitle').textContent = 'Mark selected commissions paid?';
                document.getElementById('confirmCommissionActionText').textContent = 'This will mark ' + ids.length + ' selected commission row(s) as paid.';
                commissionModal?.show();
            });

            document.getElementById('btnConfirmCommissionAction')?.addEventListener('click', function () {
                if (!pendingCommissionAction) {
                    return;
                }

                this.disabled = true;

                var options = {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                };

                if (pendingCommissionAction.type === 'bulk') {
                    options.headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify({
                        ids: pendingCommissionAction.ids,
                        notes: pendingCommissionAction.notes,
                    });
                }

                fetch(pendingCommissionAction.url, options)
                    .then(function () {
                        window.location.reload();
                    })
                    .finally(function () {
                        document.getElementById('btnConfirmCommissionAction').disabled = false;
                    });
            });
        </script>
    @endpush
@stop
