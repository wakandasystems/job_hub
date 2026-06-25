@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @push('header')
        <style>
            .marketing-image-progress {
                position: relative;
                width: 100%;
                height: 6px;
                border-radius: 999px;
                background: #e9ecef;
                overflow: hidden;
            }

            .marketing-image-progress::after {
                content: '';
                position: absolute;
                inset: 0;
                width: 40%;
                background: linear-gradient(90deg, rgba(60, 101, 245, 0.1) 0%, rgba(60, 101, 245, 0.95) 50%, rgba(60, 101, 245, 0.1) 100%);
                animation: marketingImageProgress 1.2s linear infinite;
            }

            @keyframes marketingImageProgress {
                0% { transform: translateX(-120%); }
                100% { transform: translateX(320%); }
            }
        </style>
    @endpush

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bolder">{{ $salesAgent->name }}</h4>
            <div class="text-muted small">
                <x-core::icon name="ti ti-phone" class="me-1" />{{ $salesAgent->phone }}
                &middot; Code: <code>{{ $salesAgent->code }}</code>
                <span class="badge bg-{{ $salesAgent->status === 'active' ? 'success' : 'secondary' }} text-white ms-2">{{ ucfirst($salesAgent->status) }}</span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('sales-agents.edit', $salesAgent->getKey()) }}" class="btn btn-outline-dark btn-sm">
                <x-core::icon name="ti ti-edit" class="me-1" /> Edit
            </a>
            <a href="{{ route('sales-agents.index') }}" class="btn btn-outline-dark btn-sm">
                <x-core::icon name="ti ti-arrow-left" class="me-1" /> Back to Sales Agents
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <x-core::card>
                <x-core::card.body class="text-center">
                    <div class="text-muted small">Referrals</div>
                    <div class="fs-3 fw-bold">{{ $salesAgent->referralCount() }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3">
            <x-core::card>
                <x-core::card.body class="text-center">
                    <div class="text-muted small">Total Revenue</div>
                    <div class="fs-3 fw-bold">{{ number_format($salesAgent->totalRevenue(), 2) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3">
            <x-core::card>
                <x-core::card.body class="text-center">
                    <div class="text-muted small">Commission Owed</div>
                    <div class="fs-3 fw-bold text-warning">{{ number_format($salesAgent->totalCommissionOwed(), 2) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3">
            <x-core::card>
                <x-core::card.body class="text-center">
                    <div class="text-muted small">Commission Paid</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($salesAgent->totalCommissionPaid(), 2) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    <x-core::card class="mb-3">
        <x-core::card.header>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <x-core::card.title>Marketing Images</x-core::card.title>
                <form method="POST" action="{{ route('sales-agents.marketing-images.generate', $salesAgent->getKey()) }}" class="d-flex flex-wrap gap-2 align-items-center" id="marketingImageGenerateForm" data-ajax-submit="generate-marketing-image">
                    @csrf
                    <select name="campaign_id" class="form-select form-select-sm" style="width:220px;" required>
                        <option value="">Select campaign</option>
                        @foreach ($campaigns as $campaign)
                            <option value="{{ $campaign->getKey() }}">{{ $campaign->name }}</option>
                        @endforeach
                    </select>
                    <select name="subject_mode" class="form-select form-select-sm" style="width:180px;">
                        @foreach (\Botble\JobBoard\Models\SalesAgentMarketingImage::subjectModes() as $value => $label)
                            <option value="{{ $value }}" @selected($salesAgent->preferredMarketingSubjectMode() === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-primary btn-sm" id="marketingImageGenerateButton">
                        <x-core::icon name="ti ti-photo-ai" class="me-1" /> Generate
                    </button>
                </form>
            </div>
        </x-core::card.header>
        <x-core::card.body>
            @if ($campaigns->isEmpty())
                <div class="alert alert-warning mb-0">Create an active marketing campaign before generating images.</div>
            @else
                <form method="POST" action="{{ route('sales-agents.marketing-images.bulk-destroy', $salesAgent->getKey()) }}" id="marketingImagesBulkDeleteForm">
                    @csrf
                    @method('DELETE')
                    <div class="mb-2 d-none" id="marketingImagesBulkBar">
                        <button type="button" class="btn btn-sm btn-outline-danger" id="marketingImagesBulkDeleteButton" data-confirm-submit data-confirm-title="Delete selected images?" data-confirm-text="This permanently deletes the selected records and image files. This cannot be undone.">
                            <x-core::icon name="ti ti-trash" class="me-1" /> Delete Selected (<span id="marketingImagesBulkCount">0</span>)
                        </button>
                    </div>
                </form>
                <div class="row g-3" id="marketingImagesGrid">
                    @forelse ($marketingImages as $image)
                        <div class="col-md-4 col-xl-3" data-marketing-image-card data-image-id="{{ $image->getKey() }}" data-status-url="{{ route('sales-agents.marketing-images.status', [$salesAgent->getKey(), $image->getKey()]) }}">
                            <div class="border rounded p-2 h-100">
                                <div class="form-check mb-1">
                                    <input type="checkbox" class="form-check-input" data-image-checkbox value="{{ $image->getKey() }}" id="imageCheckbox{{ $image->getKey() }}">
                                    <label class="form-check-label small text-muted" for="imageCheckbox{{ $image->getKey() }}">Select</label>
                                </div>
                                <div class="ratio ratio-1x1 bg-light rounded overflow-hidden mb-2" data-image-preview>
                                    @if ($image->status === 'completed' && $image->imageUrl())
                                        <img
                                            src="{{ $image->imageUrl() }}"
                                            alt="{{ $image->campaign?->name }}"
                                            style="width:100%;height:100%;object-fit:cover;cursor:pointer;"
                                            data-preview-image
                                            data-preview-title="{{ $image->campaign?->name ?: 'Campaign image' }}"
                                            data-preview-meta="{{ \Botble\JobBoard\Models\SalesAgentMarketingImage::subjectModes()[$image->subject_mode] ?? $image->subject_mode }}@if ($image->sent_at) · Sent {{ $image->sent_at->diffForHumans() }}@endif"
                                            data-preview-download="{{ route('sales-agents.marketing-images.download', [$salesAgent->getKey(), $image->getKey()]) }}"
                                        >
                                    @else
                                        <div class="d-flex flex-column align-items-center justify-content-center text-muted small text-center px-2">
                                            <div data-image-status-label>{{ ucfirst($image->status) }}</div>
                                            @if ($image->status === 'generating')
                                                <div class="marketing-image-progress mt-2"></div>
                                            @endif
                                            @if ($image->error_message)
                                                <div class="mt-2" data-image-error>{{ \Illuminate\Support\Str::limit($image->error_message, 80) }}</div>
                                            @else
                                                <div class="mt-2 d-none" data-image-error></div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="fw-semibold small" data-image-campaign-name>{{ $image->campaign?->name ?: 'Campaign deleted' }}</div>
                                <div class="text-muted small mb-2" data-image-meta>
                                    {{ \Botble\JobBoard\Models\SalesAgentMarketingImage::subjectModes()[$image->subject_mode] ?? $image->subject_mode }}
                                    @if ($image->sent_at)
                                        · Sent {{ $image->sent_at->diffForHumans() }}
                                    @endif
                                </div>
                                @if ($image->generationMeta())
                                    <div class="text-muted small mb-2" data-image-generation-meta>{{ $image->generationMeta() }}</div>
                                @endif
                                <div class="d-flex flex-wrap gap-1" data-image-actions>
                                    @if ($image->status === 'completed' && $image->image_path)
                                        <a href="{{ route('sales-agents.marketing-images.download', [$salesAgent->getKey(), $image->getKey()]) }}" class="btn btn-sm btn-outline-dark">
                                            <x-core::icon name="ti ti-download" />
                                        </a>
                                        <form method="POST" action="{{ route('sales-agents.marketing-images.send', [$salesAgent->getKey(), $image->getKey()]) }}">
                                            @csrf
                                            <button type="button" class="btn btn-sm btn-success" data-confirm-submit data-confirm-title="Send poster to agent?" data-confirm-text="This will send this image to {{ $salesAgent->name }} on WhatsApp.">
                                                <x-core::icon name="ti ti-brand-whatsapp" class="me-1" /> Send
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('sales-agents.marketing-images.destroy', [$salesAgent->getKey(), $image->getKey()]) }}" data-delete-marketing-image>
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
                        <div class="col-12 text-center text-muted py-4">No marketing images generated yet.</div>
                    @endforelse
                </div>

                <div class="mt-3">
                    {{ $marketingImages->links() }}
                </div>
            @endif
        </x-core::card.body>
    </x-core::card>

    <x-core::card class="mb-3">
        <x-core::card.header>
            <x-core::card.title>Manual Attribution</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body>
            <form method="POST" action="{{ route('sales-agents.assign-order', $salesAgent->getKey()) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Order Type</label>
                    <select name="order_type" class="form-select" required>
                        <option value="job_alert_order">Job Alert Order</option>
                        <option value="vip_alert_order">VIP Alert Order</option>
                        <option value="auto_apply_order">Auto Apply Order</option>
                        <option value="career_service_order">Career Service Order</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Order ID</label>
                    <input type="number" name="order_id" class="form-control" min="1" required>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-primary" data-confirm-submit data-confirm-title="Assign this order?" data-confirm-text="This will link the order to {{ $salesAgent->name }}, record the referral, and create commission if the order is already recognized as revenue.">
                        <x-core::icon name="ti ti-link" class="me-1" /> Assign to {{ $salesAgent->code }}
                    </button>
                </div>
                <div class="col-12">
                    <div class="form-hint">Use this when a customer paid but forgot to enter the agent code. It stamps the order, records the referral, and creates the commission row if missing.</div>
                </div>
            </form>
        </x-core::card.body>
    </x-core::card>

    <div class="modal fade" id="confirmAgentActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10" style="width:52px;height:52px;">
                            <x-core::icon name="ti ti-check" class="text-primary fs-3" />
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1" id="confirmAgentActionTitle">Approve action?</h6>
                    <p class="text-muted small mb-4" id="confirmAgentActionText">Please confirm this action.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary px-4" id="btnConfirmAgentAction">Approve</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="marketingImagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="marketingImagePreviewTitle">Campaign Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center bg-light">
                    <img id="marketingImagePreviewSrc" src="" alt="" class="img-fluid rounded shadow-sm" style="max-height:80vh;">
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="text-muted small" id="marketingImagePreviewMeta"></div>
                    <a href="#" class="btn btn-outline-dark btn-sm d-none" id="marketingImagePreviewDownload">
                        <x-core::icon name="ti ti-download" class="me-1" /> Download
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="marketingImagePreflightModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="marketingImagePreflightTitle">Review OpenAI Request</h5>
                        <div class="text-muted small" id="marketingImagePreflightSubtitle"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-none" id="marketingImagePreflightError"></div>
                    <div class="mb-3">
                        <div class="fw-semibold mb-2">Attached Images</div>
                        <div class="row g-3" id="marketingImagePreflightReferences"></div>
                    </div>
                    <div>
                        <div class="fw-semibold mb-2">Final Prompt Sent To OpenAI</div>
                        <textarea class="form-control" id="marketingImagePreflightPrompt" rows="12" readonly></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="text-muted small">Nothing is sent to OpenAI until you approve below.</div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="marketingImageApproveGenerate">Approve &amp; Generate</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Referred People</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Phone</th>
                                    <th>Source</th>
                                    <th>First Referred</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($referrals as $referral)
                                    <tr>
                                        <td>{{ $referral->phone }}</td>
                                        <td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', $referral->source) }}</span></td>
                                        <td>{{ $referral->first_used_at?->format('Y-m-d') ?: $referral->created_at->format('Y-m-d') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No referrals yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $referrals->links() }}
                </x-core::card.body>
            </x-core::card>
        </div>

        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Commissions</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Amount</th>
                                    <th>Commission</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($commissions as $commission)
                                    <tr>
                                        <td>{{ str_replace('_', ' ', $commission->order_type) }} #{{ $commission->order_id }}</td>
                                        <td>{{ $commission->currency }} {{ number_format($commission->amount, 2) }}</td>
                                        <td>{{ $commission->currency }} {{ number_format($commission->commission_amount, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $commission->status === 'paid' ? 'success' : 'warning' }} text-white">{{ ucfirst($commission->status) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No commissions yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $commissions->links() }}
                    <a href="{{ route('sales-agent-commissions.index', ['sales_agent_id' => $salesAgent->getKey()]) }}" class="btn btn-sm btn-outline-dark mt-2">
                        Manage payouts for this agent
                    </a>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    @push('footer')
        <script>
            (function () {
                var pendingForm = null;
                var modalElement = document.getElementById('confirmAgentActionModal');
                var modal = modalElement ? new bootstrap.Modal(modalElement) : null;
                var confirmButton = document.getElementById('btnConfirmAgentAction');
                var imageGrid = document.getElementById('marketingImagesGrid');
                var previewModalElement = document.getElementById('marketingImagePreviewModal');
                var previewModal = previewModalElement ? new bootstrap.Modal(previewModalElement) : null;
                var previewTitle = document.getElementById('marketingImagePreviewTitle');
                var previewImage = document.getElementById('marketingImagePreviewSrc');
                var previewMeta = document.getElementById('marketingImagePreviewMeta');
                var previewDownload = document.getElementById('marketingImagePreviewDownload');
                var preflightModalElement = document.getElementById('marketingImagePreflightModal');
                var preflightModal = preflightModalElement ? new bootstrap.Modal(preflightModalElement) : null;
                var preflightSubtitle = document.getElementById('marketingImagePreflightSubtitle');
                var preflightError = document.getElementById('marketingImagePreflightError');
                var preflightReferences = document.getElementById('marketingImagePreflightReferences');
                var preflightPrompt = document.getElementById('marketingImagePreflightPrompt');
                var approveGenerateButton = document.getElementById('marketingImageApproveGenerate');
                var generateButton = document.getElementById('marketingImageGenerateButton');
                var generateForm = document.getElementById('marketingImageGenerateForm');
                var pollTimers = {};
                var salesAgentName = @js($salesAgent->name);
                var csrfToken = @js(csrf_token());
                var previewAction = @js(route('sales-agents.marketing-images.preview', $salesAgent->getKey()));
                var approvedGenerateForm = null;
                var bulkBar = document.getElementById('marketingImagesBulkBar');
                var bulkCount = document.getElementById('marketingImagesBulkCount');
                var bulkForm = document.getElementById('marketingImagesBulkDeleteForm');

                function updateBulkBar() {
                    if (!imageGrid || !bulkBar || !bulkCount || !bulkForm) {
                        return;
                    }

                    var checked = Array.prototype.slice.call(imageGrid.querySelectorAll('[data-image-checkbox]:checked'));

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

                function escapeHtml(value) {
                    return String(value || '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function openPreview(image) {
                    if (!previewModal || !image) {
                        return;
                    }

                    previewTitle.textContent = image.dataset.previewTitle || 'Campaign Image';
                    previewImage.src = image.getAttribute('src') || '';
                    previewImage.alt = image.getAttribute('alt') || previewTitle.textContent;
                    previewMeta.textContent = image.dataset.previewMeta || '';

                    if (image.dataset.previewDownload) {
                        previewDownload.href = image.dataset.previewDownload;
                        previewDownload.classList.remove('d-none');
                    } else {
                        previewDownload.href = '#';
                        previewDownload.classList.add('d-none');
                    }

                    previewModal.show();
                }

                function actionButtonsHtml(payload) {
                    var html = '';

                    var downloadSvg = '<svg class="icon svg-icon-ti-ti-download" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>';
                    var whatsappSvg = '<svg class="icon me-1 svg-icon-ti-ti-brand-whatsapp" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg>';
                    var trashSvg = '<svg class="icon svg-icon-ti-ti-trash" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';

                    if (payload.download_url && payload.send_url) {
                        html += '' +
                            '<a href="' + payload.download_url + '" class="btn btn-sm btn-outline-dark">' +
                                downloadSvg +
                            '</a>' +
                            '<form method="POST" action="' + payload.send_url + '">' +
                                '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                                '<button type="button" class="btn btn-sm btn-success" data-confirm-submit data-confirm-title="Send poster to agent?" data-confirm-text="This will send this image to ' + escapeHtml(salesAgentName) + ' on WhatsApp.">' +
                                    whatsappSvg + ' Send' +
                                '</button>' +
                            '</form>';
                    }

                    if (payload.delete_url) {
                        html += '' +
                            '<form method="POST" action="' + payload.delete_url + '" data-delete-marketing-image>' +
                                '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                                '<input type="hidden" name="_method" value="DELETE">' +
                                '<button type="button" class="btn btn-sm btn-outline-danger" data-confirm-submit data-confirm-title="Delete this image?" data-confirm-text="This permanently deletes the record and the image file. This cannot be undone.">' +
                                    trashSvg +
                                '</button>' +
                            '</form>';
                    }

                    return html;
                }

                function referencesHtml(references) {
                    return (references || []).map(function (reference) {
                        var badgeClass = reference.available ? 'bg-success' : 'bg-danger';
                        var badgeLabel = reference.available ? 'Ready' : 'Missing';
                        var imageHtml = reference.url
                            ? '<img src="' + escapeHtml(reference.url) + '" alt="' + escapeHtml(reference.label) + '" class="img-fluid rounded border" style="width:100%;height:180px;object-fit:cover;">'
                            : '<div class="border rounded bg-light d-flex align-items-center justify-content-center text-muted small" style="height:180px;">No image available</div>';

                        return '' +
                            '<div class="col-md-4">' +
                                '<div class="border rounded p-2 h-100">' +
                                    imageHtml +
                                    '<div class="mt-2 d-flex justify-content-between align-items-center gap-2">' +
                                        '<div class="small fw-semibold">' + escapeHtml(reference.label) + '</div>' +
                                        '<span class="badge ' + badgeClass + ' text-white">' + badgeLabel + '</span>' +
                                    '</div>' +
                                    '<div class="text-muted small mt-1">' + escapeHtml(reference.filename || '') + '</div>' +
                                '</div>' +
                            '</div>';
                    }).join('');
                }

                function openPreflight(payload, errorMessage) {
                    if (!preflightModal) {
                        return;
                    }

                    preflightSubtitle.textContent = (payload.campaign_name || 'Campaign') + ' · ' + (payload.subject_label || '');
                    preflightReferences.innerHTML = referencesHtml(payload.references || []);
                    preflightPrompt.value = payload.prompt || '';
                    preflightError.textContent = errorMessage || '';
                    preflightError.classList.toggle('d-none', !errorMessage);
                    approveGenerateButton.disabled = !!errorMessage;
                    preflightModal.show();
                }

                function updateCard(card, payload) {
                    if (!card || !payload) {
                        return;
                    }

                    card.dataset.imageId = payload.image_id;
                    card.dataset.statusUrl = payload.status_url;

                    var preview = card.querySelector('[data-image-preview]');
                    var campaignName = card.querySelector('[data-image-campaign-name]');
                    var meta = card.querySelector('[data-image-meta]');
                    var generationMeta = card.querySelector('[data-image-generation-meta]');
                    var actions = card.querySelector('[data-image-actions]');

                    if (campaignName) {
                        campaignName.textContent = payload.campaign_name || 'Campaign deleted';
                    }

                    if (meta) {
                        meta.textContent = payload.subject_label + (payload.sent_at_human ? ' · Sent ' + payload.sent_at_human : '');
                    }

                    if (payload.generation_meta) {
                        if (!generationMeta) {
                            generationMeta = document.createElement('div');
                            generationMeta.className = 'text-muted small mb-2';
                            generationMeta.setAttribute('data-image-generation-meta', '');
                            meta?.insertAdjacentElement('afterend', generationMeta);
                        }
                        generationMeta.textContent = payload.generation_meta;
                    } else if (generationMeta) {
                        generationMeta.remove();
                    }

                    if (preview) {
                        if (payload.status === 'completed' && payload.image_url) {
                            preview.innerHTML = '<img src="' + payload.image_url + '" alt="' + escapeHtml(payload.campaign_name || 'Campaign image') + '" style="width:100%;height:100%;object-fit:cover;cursor:pointer;" data-preview-image data-preview-title="' + escapeHtml(payload.campaign_name || 'Campaign image') + '" data-preview-meta="' + escapeHtml(payload.subject_label + (payload.sent_at_human ? ' · Sent ' + payload.sent_at_human : '')) + '" data-preview-download="' + escapeHtml(payload.download_url || '') + '">';
                        } else {
                            preview.innerHTML = '' +
                                '<div class="d-flex flex-column align-items-center justify-content-center text-muted small text-center px-2">' +
                                    '<div data-image-status-label>' + escapeHtml((payload.status || 'generating').charAt(0).toUpperCase() + (payload.status || 'generating').slice(1)) + '</div>' +
                                    (payload.status === 'generating' ? '<div class="marketing-image-progress mt-2"></div>' : '') +
                                    '<div class="' + (payload.error_message ? 'mt-2' : 'mt-2 d-none') + '" data-image-error>' + escapeHtml(payload.error_message || '') + '</div>' +
                                '</div>';
                        }
                    }

                    if (actions) {
                        actions.innerHTML = actionButtonsHtml(payload);
                    }
                }

                function stopPolling(imageId) {
                    if (pollTimers[imageId]) {
                        clearInterval(pollTimers[imageId]);
                        delete pollTimers[imageId];
                    }
                }

                function pollCard(card) {
                    if (!card) {
                        return;
                    }

                    var imageId = card.dataset.imageId;
                    var statusUrl = card.dataset.statusUrl;

                    if (!imageId || !statusUrl || pollTimers[imageId]) {
                        return;
                    }

                    pollTimers[imageId] = setInterval(function () {
                        fetch(statusUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(function (response) {
                                return response.json();
                            })
                            .then(function (payload) {
                                var data = payload.data || null;

                                if (!data) {
                                    return;
                                }

                                updateCard(card, data);

                                if (data.status !== 'generating') {
                                    stopPolling(imageId);
                                }
                            })
                            .catch(function () {});
                    }, 4000);
                }

                function createGeneratingCard(payload) {
                    if (!imageGrid || !payload) {
                        return;
                    }

                    var emptyState = imageGrid.querySelector('.col-12.text-center.text-muted.py-4');
                    if (emptyState) {
                        emptyState.remove();
                    }

                    var wrapper = document.createElement('div');
                    wrapper.className = 'col-md-4 col-xl-3';
                    wrapper.setAttribute('data-marketing-image-card', '');
                    wrapper.setAttribute('data-image-id', payload.image_id);
                    wrapper.setAttribute('data-status-url', payload.status_url);
                    wrapper.innerHTML = '' +
                        '<div class="border rounded p-2 h-100">' +
                            '<div class="form-check mb-1">' +
                                '<input type="checkbox" class="form-check-input" data-image-checkbox value="' + payload.image_id + '" id="imageCheckbox' + payload.image_id + '">' +
                                '<label class="form-check-label small text-muted" for="imageCheckbox' + payload.image_id + '">Select</label>' +
                            '</div>' +
                            '<div class="ratio ratio-1x1 bg-light rounded overflow-hidden mb-2" data-image-preview>' +
                                '<div class="d-flex flex-column align-items-center justify-content-center text-muted small text-center px-2">' +
                                    '<div data-image-status-label>Generating</div>' +
                                    '<div class="marketing-image-progress mt-2"></div>' +
                                    '<div class="mt-2 d-none" data-image-error></div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="fw-semibold small" data-image-campaign-name></div>' +
                            '<div class="text-muted small mb-2" data-image-meta></div>' +
                            '<div class="d-flex flex-wrap gap-1" data-image-actions></div>' +
                        '</div>';

                    imageGrid.prepend(wrapper);
                    updateCard(wrapper, payload);
                    pollCard(wrapper);
                }

                function queueGenerate(form) {
                    var formData = new FormData(form);
                    var campaignField = form.querySelector('[name="campaign_id"]');
                    var subjectField = form.querySelector('[name="subject_mode"]');
                    var campaignId = campaignField ? String(campaignField.value || '').trim() : '';
                    var subjectMode = subjectField ? String(subjectField.value || '').trim() : '';

                    if (!campaignId) {
                        confirmButton.disabled = false;
                        pendingForm = null;
                        modal.hide();
                        Botble.showError('Please select a campaign first.');
                        return;
                    }

                    if (!subjectMode) {
                        confirmButton.disabled = false;
                        pendingForm = null;
                        modal.hide();
                        Botble.showError('Please select an image subject first.');
                        return;
                    }

                    formData.set('campaign_id', campaignId);
                    formData.set('subject_mode', subjectMode);

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    })
                        .then(function (response) {
                            return response.json().then(function (payload) {
                                return { ok: response.ok, payload: payload };
                            });
                        })
                        .then(function (result) {
                            confirmButton.disabled = false;
                            pendingForm = null;
                            modal.hide();

                            if (!result.ok) {
                                Botble.showError(result.payload.message || 'Could not queue marketing image generation.');
                                return;
                            }

                            if (result.payload.data) {
                                createGeneratingCard(result.payload.data);
                            }

                            Botble.showSuccess(result.payload.message || 'Marketing image generation queued.');
                        })
                        .catch(function () {
                            confirmButton.disabled = false;
                            pendingForm = null;
                            modal.hide();
                            Botble.showError('Could not queue marketing image generation.');
                        });
                }

                function previewGenerate(form) {
                    var formData = new FormData(form);
                    var campaignField = form.querySelector('[name="campaign_id"]');
                    var subjectField = form.querySelector('[name="subject_mode"]');
                    var campaignId = campaignField ? String(campaignField.value || '').trim() : '';
                    var subjectMode = subjectField ? String(subjectField.value || '').trim() : '';

                    if (!campaignId) {
                        confirmButton.disabled = false;
                        pendingForm = null;
                        modal.hide();
                        Botble.showError('Please select a campaign first.');
                        return;
                    }

                    if (!subjectMode) {
                        confirmButton.disabled = false;
                        pendingForm = null;
                        modal.hide();
                        Botble.showError('Please select an image subject first.');
                        return;
                    }

                    formData.set('campaign_id', campaignId);
                    formData.set('subject_mode', subjectMode);

                    fetch(previewAction, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    })
                        .then(function (response) {
                            return response.json().then(function (payload) {
                                return { ok: response.ok, payload: payload };
                            });
                        })
                        .then(function (result) {
                            confirmButton.disabled = false;
                            pendingForm = null;
                            modal.hide();

                            if (!result.payload.data) {
                                Botble.showError(result.payload.message || 'Could not prepare OpenAI preview.');
                                return;
                            }

                            approvedGenerateForm = form;
                            openPreflight(result.payload.data, result.ok ? '' : (result.payload.message || 'Could not prepare OpenAI preview.'));
                        })
                        .catch(function () {
                            confirmButton.disabled = false;
                            pendingForm = null;
                            modal.hide();
                            Botble.showError('Could not prepare OpenAI preview.');
                        });
                }

                document.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-confirm-submit]');
                    var previewTrigger = event.target.closest('[data-preview-image]');

                    if (previewTrigger) {
                        openPreview(previewTrigger);
                        return;
                    }

                    if (!button || !modal) {
                        return;
                    }

                    pendingForm = button.closest('form');
                    document.getElementById('confirmAgentActionTitle').textContent = button.dataset.confirmTitle || 'Approve action?';
                    document.getElementById('confirmAgentActionText').textContent = button.dataset.confirmText || 'Please confirm this action.';
                    modal.show();
                });

                confirmButton?.addEventListener('click', function () {
                    if (!pendingForm) {
                        return;
                    }

                    this.disabled = true;

                    if (pendingForm.dataset.ajaxSubmit === 'generate-marketing-image') {
                        previewGenerate(pendingForm);
                        return;
                    }

                    pendingForm.submit();
                });

                approveGenerateButton?.addEventListener('click', function () {
                    if (!approvedGenerateForm) {
                        return;
                    }

                    this.disabled = true;
                    preflightModal.hide();
                    queueGenerate(approvedGenerateForm);
                    approvedGenerateForm = null;

                    setTimeout(function () {
                        approveGenerateButton.disabled = false;
                    }, 500);
                });

                generateButton?.addEventListener('click', function () {
                    if (!generateForm) {
                        return;
                    }

                    this.disabled = true;
                    queueGenerate(generateForm);

                    setTimeout(function () {
                        generateButton.disabled = false;
                    }, 500);
                });

                document.querySelectorAll('[data-marketing-image-card]').forEach(function (card) {
                    var statusText = card.querySelector('[data-image-status-label]');

                    if (statusText && String(statusText.textContent || '').trim().toLowerCase() === 'generating') {
                        pollCard(card);
                    }
                });

                imageGrid?.addEventListener('change', function (event) {
                    if (event.target.matches('[data-image-checkbox]')) {
                        updateBulkBar();
                    }
                });
            })();
        </script>
    @endpush
@stop
