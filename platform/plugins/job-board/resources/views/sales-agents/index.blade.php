@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card>
        <x-core::card.header>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <x-core::card.title>Sales Agents</x-core::card.title>
                <a href="{{ route('sales-agents.create') }}" class="btn btn-primary">
                    <x-core::icon name="ti ti-plus" class="me-1" /> Add Sales Agent
                </a>
            </div>
        </x-core::card.header>
        <x-core::card.body>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th style="width:64px;">Image</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Code</th>
                            <th>Status</th>
                            <th>Referrals</th>
                            <th>Revenue</th>
                            <th>Commission Owed</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agents as $agent)
                            <tr>
                                <td>
                                    <img
                                        src="{{ $agent->photoUrl() ?: RvMedia::getDefaultImage() }}"
                                        alt="{{ $agent->name }}"
                                        class="rounded border"
                                        style="width:44px;height:44px;object-fit:cover;"
                                    >
                                </td>
                                <td>
                                    <a href="{{ route('sales-agents.show', $agent->getKey()) }}" class="fw-semibold">{{ $agent->name }}</a>
                                    @if ($agent->email)
                                        <div class="text-muted small">{{ $agent->email }}</div>
                                    @endif
                                </td>
                                <td>{{ $agent->phone }}</td>
                                <td><code>{{ $agent->code }}</code></td>
                                <td>
                                    <span class="badge bg-{{ $agent->status === 'active' ? 'success' : 'secondary' }} text-white">{{ ucfirst($agent->status) }}</span>
                                </td>
                                <td>{{ $agent->referralCount() }}</td>
                                <td>{{ number_format($agent->totalRevenue(), 2) }}</td>
                                <td>{{ number_format($agent->totalCommissionOwed(), 2) }}</td>
                                <td class="text-end">
                                    <div class="btn-list justify-content-end flex-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-success js-send-welcome" data-bs-toggle="modal" data-bs-target="#modal-send-welcome" data-url="{{ route('sales-agents.send-welcome', $agent->getKey()) }}" data-name="{{ $agent->name }}" title="Send welcome WhatsApp">
                                            <x-core::icon name="ti ti-brand-whatsapp" />
                                        </button>
                                        <a href="{{ route('sales-agent-campaigns.index') }}" class="btn btn-sm btn-outline-primary" title="Send marketing campaigns">
                                            <x-core::icon name="ti ti-photo-ai" />
                                        </a>
                                        <a href="{{ route('sales-agent-commissions.index', ['sales_agent_id' => $agent->getKey()]) }}" class="btn btn-sm btn-outline-warning" title="Update commissions">
                                            <x-core::icon name="ti ti-cash" />
                                        </a>
                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $agent->phone) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success" title="Open WhatsApp chat">
                                            <x-core::icon name="ti ti-message-circle" />
                                        </a>
                                        <a href="{{ route('sales-agents.edit', $agent->getKey()) }}" class="btn btn-sm btn-outline-dark" title="Edit">
                                        <x-core::icon name="ti ti-edit" />
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger js-delete-sales-agent" data-bs-toggle="modal" data-bs-target="#modal-delete-sales-agent" data-url="{{ route('sales-agents.destroy', $agent->getKey()) }}" data-name="{{ $agent->name }}" title="Delete">
                                            <x-core::icon name="ti ti-trash" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No sales agents yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $agents->links() }}
        </x-core::card.body>
    </x-core::card>

    <div class="modal fade" id="modal-send-welcome" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:52px;height:52px;">
                            <x-core::icon name="ti ti-brand-whatsapp" class="text-success fs-3" />
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Send welcome message?</h6>
                    <p class="text-muted small mb-4" id="sendWelcomeLabel">This will send a WhatsApp welcome message.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="btnConfirmSendWelcome">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-delete-sales-agent" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <x-core::icon name="ti ti-trash" class="text-danger fs-3" />
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Delete this sales agent?</h6>
                    <p class="text-muted small mb-4" id="deleteSalesAgentLabel">This cannot be undone.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="btnConfirmDeleteSalesAgent">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
        <script>
            (function () {
                var deleteUrl = null;
                var welcomeUrl = null;

                document.addEventListener('click', function (event) {
                    var welcomeButton = event.target.closest('.js-send-welcome');
                    if (welcomeButton) {
                        welcomeUrl = welcomeButton.dataset.url;
                        document.getElementById('sendWelcomeLabel').textContent = 'This will send the WhatsApp welcome message to ' + (welcomeButton.dataset.name || 'this agent') + '.';

                        return;
                    }

                    var button = event.target.closest('.js-delete-sales-agent');

                    if (!button) {
                        return;
                    }

                    deleteUrl = button.dataset.url;
                    document.getElementById('deleteSalesAgentLabel').textContent = (button.dataset.name || 'This agent') + ' will be permanently removed.';
                });

                document.getElementById('btnConfirmDeleteSalesAgent').addEventListener('click', function () {
                    if (!deleteUrl) {
                        return;
                    }

                    fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json',
                        },
                    })
                        .then(function () {
                            window.location.reload();
                        });
                });

                document.getElementById('btnConfirmSendWelcome').addEventListener('click', function () {
                    if (!welcomeUrl) {
                        return;
                    }

                    this.disabled = true;

                    fetch(welcomeUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json',
                        },
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (payload) {
                            if (window.Botble && Botble.showSuccess) {
                                Botble.showSuccess(payload.message || 'Welcome message sent.');
                            }

                            bootstrap.Modal.getInstance(document.getElementById('modal-send-welcome'))?.hide();
                        })
                        .catch(function () {
                            if (window.Botble && Botble.showError) {
                                Botble.showError('Could not send welcome message.');
                            }
                        })
                        .finally(function () {
                            document.getElementById('btnConfirmSendWelcome').disabled = false;
                        });
                });
            })();
        </script>
    @endpush
@stop
