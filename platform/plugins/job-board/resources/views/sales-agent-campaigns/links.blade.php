@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bolder">Campaign Links - {{ $campaign->name }}</h4>
            <div class="text-muted small">{{ $campaign->resolvedProductLabel() }}@if($campaign->promo_price) · {{ $campaign->promo_price }}@endif</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('sales-agent-campaigns.edit', $campaign->getKey()) }}" class="btn btn-outline-dark btn-sm">Edit Campaign</a>
            <a href="{{ route('sales-agent-campaigns.index') }}" class="btn btn-outline-secondary btn-sm">Back to Campaigns</a>
        </div>
    </div>

    <x-core::card class="mb-3">
        <x-core::card.body>
            <form method="GET" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="q" class="form-control" placeholder="Search agent name, phone, or code" value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" value="1" id="active_only" name="active_only" {{ request()->boolean('active_only', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="active_only">Active agents only</label>
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="{{ route('sales-agent-campaigns.links.export', [$campaign->getKey(), 'q' => request('q'), 'active_only' => request('active_only', '1')]) }}" class="btn btn-outline-dark w-100">Export CSV</a>
                </div>
            </form>
        </x-core::card.body>
    </x-core::card>

    <x-core::card class="mb-3">
        <x-core::card.header>
            <x-core::card.title>Bulk Actions</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body>
            <form method="POST" action="{{ route('sales-agent-campaigns.links.send-bulk', $campaign->getKey()) }}">
                @csrf
                <input type="hidden" name="q" value="{{ request('q') }}">
                <input type="hidden" name="active_only" value="{{ request()->boolean('active_only', true) ? '1' : '0' }}">
                <button type="button" class="btn btn-success" data-confirm-submit data-confirm-title="Send campaign link to all filtered agents?" data-confirm-text="This will queue WhatsApp messages with the campaign link for every agent in the current filtered result set.">
                    <x-core::icon name="ti ti-brand-whatsapp" class="me-1" /> Send Link To All Filtered Agents
                </button>
            </form>
        </x-core::card.body>
    </x-core::card>

    <x-core::card>
        <x-core::card.body class="p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Code</th>
                            <th>Phone</th>
                            <th>Clicks</th>
                            <th>Share Link</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agents as $agent)
                            @php($shareUrl = $campaign->shareUrlForAgent($agent))
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $agent->name }}</div>
                                    <div class="text-muted small">{{ ucfirst($agent->status) }}</div>
                                </td>
                                <td><code>{{ $agent->code }}</code></td>
                                <td>{{ $agent->phone }}</td>
                                <td>{{ number_format($agent->campaign_clicks_count ?? 0) }}</td>
                                <td style="min-width:320px;">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" readonly value="{{ $shareUrl }}">
                                        <button type="button" class="btn btn-outline-primary js-copy-link" data-link="{{ $shareUrl }}">
                                            <x-core::icon name="ti ti-copy" />
                                        </button>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('sales-agent-campaigns.links.send', [$campaign->getKey(), $agent->getKey()]) }}" class="d-inline">
                                        @csrf
                                        <button type="button" class="btn btn-sm btn-success" data-confirm-submit data-confirm-title="Send campaign link to {{ $agent->name }}?" data-confirm-text="This will queue a WhatsApp message with the exact share link and CTA for this campaign.">
                                            <x-core::icon name="ti ti-brand-whatsapp" class="me-1" /> Send
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No agents found for this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-core::card.body>
    </x-core::card>

    <div class="mt-3">
        {{ $agents->links() }}
    </div>

    <div class="modal fade" id="campaignLinksConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:52px;height:52px;">
                            <x-core::icon name="ti ti-brand-whatsapp" class="text-success fs-3" />
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1" id="campaignLinksConfirmTitle">Send link?</h6>
                    <p class="text-muted small mb-4" id="campaignLinksConfirmText">Please confirm this action.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success px-4" id="campaignLinksConfirmBtn">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer')
    <script>
        (function () {
            var pendingForm = null;
            var modalEl = document.getElementById('campaignLinksConfirmModal');
            var modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
            var confirmBtn = document.getElementById('campaignLinksConfirmBtn');

            document.querySelectorAll('.js-copy-link').forEach(function (button) {
                button.addEventListener('click', function () {
                    var link = button.getAttribute('data-link') || '';

                    navigator.clipboard.writeText(link).then(function () {
                        Botble.showSuccess('Share link copied.');
                    }).catch(function () {
                        Botble.showError('Could not copy the link automatically.');
                    });
                });
            });

            document.addEventListener('click', function (event) {
                var button = event.target.closest('[data-confirm-submit]');

                if (!button || !modal) {
                    return;
                }

                pendingForm = button.closest('form');
                document.getElementById('campaignLinksConfirmTitle').textContent = button.dataset.confirmTitle || 'Confirm action?';
                document.getElementById('campaignLinksConfirmText').textContent = button.dataset.confirmText || 'Please confirm this action.';
                modal.show();
            });

            confirmBtn?.addEventListener('click', function () {
                if (!pendingForm) {
                    return;
                }

                pendingForm.submit();
            });
        })();
    </script>
@endpush
