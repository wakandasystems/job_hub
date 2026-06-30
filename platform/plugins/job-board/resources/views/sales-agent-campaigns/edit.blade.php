@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <ul class="nav nav-tabs mb-3" id="sales-agent-campaign-edit-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'details' ? 'active' : '' }}" id="campaign-details-tab" data-bs-toggle="tab" data-bs-target="#campaign-details-pane" type="button" role="tab" aria-controls="campaign-details-pane" aria-selected="{{ $activeTab === 'details' ? 'true' : 'false' }}">
                Campaign
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'history' ? 'active' : '' }}" id="campaign-history-tab" data-bs-toggle="tab" data-bs-target="#campaign-history-pane" type="button" role="tab" aria-controls="campaign-history-pane" aria-selected="{{ $activeTab === 'history' ? 'true' : 'false' }}">
                History
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $activeTab === 'details' ? 'show active' : '' }}" id="campaign-details-pane" role="tabpanel" aria-labelledby="campaign-details-tab">
            <x-core::form :url="route('sales-agent-campaigns.update', $campaign->getKey())" method="put" enctype="multipart/form-data">
                @csrf

                @include('plugins/job-board::sales-agent-campaigns.partials.form')
            </x-core::form>
        </div>

        <div class="tab-pane fade {{ $activeTab === 'history' ? 'show active' : '' }}" id="campaign-history-pane" role="tabpanel" aria-labelledby="campaign-history-tab">
            <x-core::card>
                <x-core::card.body>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Saved</th>
                                    <th>By</th>
                                    <th>Note</th>
                                    <th>Snapshot</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($versions as $version)
                                    @php($snapshot = $version->snapshot ?: [])
                                    @php($creatorName = trim((string) ($version->creator?->name ?: $version->creator?->username ?: 'System')))
                                    <tr>
                                        <td>#{{ $version->getKey() }}</td>
                                        <td>{{ $version->created_at?->format('Y-m-d H:i') }}</td>
                                        <td>{{ $creatorName !== '' ? $creatorName : 'System' }}</td>
                                        <td>
                                            <div>{{ $version->label ?: 'Saved version' }}</div>
                                            @if ($version->restoredFrom)
                                                <div class="small text-muted">Restored from #{{ $version->restoredFrom->getKey() }}</div>
                                            @endif
                                        </td>
                                        <td class="small text-muted">
                                            <div>{{ $snapshot['name'] ?? $campaign->name }}</div>
                                            <div>{{ \Botble\JobBoard\Models\SalesAgentCampaign::productTypeOptions()[$snapshot['product_type'] ?? ''] ?? ($snapshot['product_type'] ?? '—') }}</div>
                                            <div>{{ ! empty($snapshot['promo_price']) ? $snapshot['promo_price'] : 'No price' }}</div>
                                        </td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#restoreCampaignVersionModal"
                                                data-action="{{ route('sales-agent-campaigns.versions.restore', [$campaign->getKey(), $version->getKey()]) }}"
                                                data-label="version #{{ $version->getKey() }} saved on {{ $version->created_at?->format('Y-m-d H:i') }}"
                                            >
                                                Restore
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No saved history yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    <div class="modal fade" id="restoreCampaignVersionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-history-toggle text-success fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Restore this version?</h6>
                    <p class="text-muted small mb-4" id="restoreCampaignVersionLabel">This will replace the current campaign settings.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <form id="restoreCampaignVersionForm" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success px-4">Restore</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('footer')
    <script>
        document.getElementById('restoreCampaignVersionModal').addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            document.getElementById('restoreCampaignVersionForm').action = btn.dataset.action;
            document.getElementById('restoreCampaignVersionLabel').textContent = 'Restore ' + btn.dataset.label + '? A new history entry will be saved first.';
        });
    </script>
@endpush
