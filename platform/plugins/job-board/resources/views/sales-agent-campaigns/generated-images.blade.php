@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card>
        <x-core::card.header>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <x-core::card.title>Generated Marketing Images</x-core::card.title>
                <a href="{{ route('sales-agent-campaigns.index') }}" class="btn btn-outline-dark">
                    <x-core::icon name="ti ti-arrow-left" class="me-1" /> Campaigns
                </a>
            </div>
        </x-core::card.header>
        <x-core::card.body>
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="sales_agent_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All agents</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}" @selected(request('sales_agent_id') == $agent->id)>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="campaign_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All campaigns</option>
                        @foreach ($campaigns as $campaign)
                            <option value="{{ $campaign->id }}" @selected(request('campaign_id') == $campaign->id)>{{ $campaign->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="generating" @selected(request('status') === 'generating')>Generating</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('sales-agent-campaigns.generated-images') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>

            <form method="POST" action="{{ route('sales-agent-campaigns.generated-images.bulk-destroy') }}" id="generatedImagesBulkDeleteForm">
                @csrf
                @method('DELETE')
                <div class="mb-2 d-none" id="generatedImagesBulkBar">
                    <button type="button" class="btn btn-sm btn-outline-danger" id="generatedImagesBulkDeleteButton" data-confirm-submit data-confirm-title="Delete selected images?" data-confirm-text="This permanently deletes the selected records and image files. This cannot be undone.">
                        <x-core::icon name="ti ti-trash" class="me-1" /> Delete Selected (<span id="generatedImagesBulkCount">0</span>)
                    </button>
                </div>
            </form>

            <div class="row g-3" id="generatedImagesGrid">
                @forelse ($images as $image)
                    <div class="col-md-4 col-xl-3">
                        <div class="border rounded p-2 h-100">
                            <div class="form-check mb-1">
                                <input type="checkbox" class="form-check-input" data-image-checkbox value="{{ $image->getKey() }}" id="generatedImageCheckbox{{ $image->getKey() }}">
                                <label class="form-check-label small text-muted" for="generatedImageCheckbox{{ $image->getKey() }}">Select</label>
                            </div>
                            <div class="ratio ratio-1x1 bg-light rounded overflow-hidden mb-2">
                                @if ($image->status === 'completed' && $image->imageUrl())
                                    <img src="{{ $image->imageUrl() }}" alt="{{ $image->campaign?->name }}" style="width:100%;height:100%;object-fit:cover;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center text-muted small text-center px-2">
                                        {{ ucfirst($image->status) }}
                                        @if ($image->error_message)
                                            <br>{{ \Illuminate\Support\Str::limit($image->error_message, 80) }}
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="fw-semibold small">{{ $image->campaign?->name ?: 'Campaign deleted' }}</div>
                            <div class="text-muted small">
                                {{ $image->salesAgent?->name ?: 'Agent deleted' }}
                                @if ($image->sent_at)
                                    · Sent {{ $image->sent_at->diffForHumans() }}
                                @endif
                            </div>
                            @if ($image->generationMeta())
                                <div class="text-muted small mb-1">{{ $image->generationMeta() }}</div>
                            @endif
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @if ($image->salesAgent)
                                    <a href="{{ route('sales-agents.show', $image->salesAgent->getKey()) }}" class="btn btn-sm btn-outline-dark">
                                        Open Agent
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('sales-agent-campaigns.generated-images.destroy', $image->getKey()) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-confirm-submit data-confirm-title="Delete this image?" data-confirm-text="This permanently deletes the record and the image file. This cannot be undone.">
                                        <x-core::icon name="ti ti-trash" />
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted py-4">No marketing images found.</div>
                @endforelse
            </div>

            <div class="mt-3">
                {{ $images->links() }}
            </div>
        </x-core::card.body>
    </x-core::card>

    <div class="modal fade" id="confirmGeneratedImageActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <x-core::icon name="ti ti-trash" class="text-danger fs-3" />
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1" id="confirmGeneratedImageActionTitle">Delete?</h6>
                    <p class="text-muted small mb-4" id="confirmGeneratedImageActionText">Please confirm this action.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger px-4" id="btnConfirmGeneratedImageAction">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
        <script>
            (function () {
                var pendingForm = null;
                var modalElement = document.getElementById('confirmGeneratedImageActionModal');
                var modal = modalElement ? new bootstrap.Modal(modalElement) : null;
                var confirmButton = document.getElementById('btnConfirmGeneratedImageAction');
                var grid = document.getElementById('generatedImagesGrid');
                var bulkBar = document.getElementById('generatedImagesBulkBar');
                var bulkCount = document.getElementById('generatedImagesBulkCount');
                var bulkForm = document.getElementById('generatedImagesBulkDeleteForm');

                function updateBulkBar() {
                    if (!grid || !bulkBar || !bulkCount || !bulkForm) {
                        return;
                    }

                    var checked = Array.prototype.slice.call(grid.querySelectorAll('[data-image-checkbox]:checked'));

                    bulkForm.querySelectorAll('input[name="ids[]"]').forEach(function (input) {
                        input.remove();
                    });

                    checked.forEach(function (checkbox) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = checkbox.value;
                        bulkForm.appendChild(input);
                    });

                    bulkCount.textContent = checked.length;
                    bulkBar.classList.toggle('d-none', checked.length === 0);
                }

                grid?.addEventListener('change', function (event) {
                    if (event.target.matches('[data-image-checkbox]')) {
                        updateBulkBar();
                    }
                });

                document.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-confirm-submit]');

                    if (!button || !modal) {
                        return;
                    }

                    pendingForm = button.closest('form');
                    document.getElementById('confirmGeneratedImageActionTitle').textContent = button.dataset.confirmTitle || 'Delete?';
                    document.getElementById('confirmGeneratedImageActionText').textContent = button.dataset.confirmText || 'Please confirm this action.';
                    modal.show();
                });

                confirmButton?.addEventListener('click', function () {
                    if (!pendingForm) {
                        return;
                    }

                    this.disabled = true;
                    pendingForm.submit();
                });
            })();
        </script>
    @endpush
@stop
