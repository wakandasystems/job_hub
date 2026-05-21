@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Order details form --}}
            <form method="POST" action="{{ route('career-service-orders.update', $order) }}">
                @csrf

                <x-core::card class="mb-3">
                    <x-core::card.header>
                        <x-core::card.title>Order #{{ $order->id }} - {{ $order->service_name }}</x-core::card.title>
                    </x-core::card.header>
                    <x-core::card.body>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Assigned Coach Name</label>
                                <input class="form-control" name="assigned_coach_name" value="{{ old('assigned_coach_name', $order->assigned_coach_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Assigned Coach Email</label>
                                <input class="form-control" type="email" name="assigned_coach_email" value="{{ old('assigned_coach_email', $order->assigned_coach_email) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Delivery Status</label>
                                <select class="form-select" name="delivery_status">
                                    @foreach($deliveryStatuses as $value => $label)
                                        <option value="{{ $value }}" @selected(old('delivery_status', $order->delivery_status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Status</label>
                                <select class="form-select" name="status">
                                    @foreach(['pending' => 'Pending', 'paid' => 'Paid', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $order->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Delivered At</label>
                                <input class="form-control" type="datetime-local" name="delivered_at" value="{{ old('delivered_at', $order->delivered_at?->format('Y-m-d\\TH:i')) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="6">{{ old('notes', $order->notes) }}</textarea>
                            </div>
                        </div>
                    </x-core::card.body>
                    <x-core::card.footer>
                        <button class="btn btn-primary" type="submit">Save Changes</button>
                        <a class="btn btn-outline-secondary" href="{{ route('career-service-orders.index') }}">Back</a>
                    </x-core::card.footer>
                </x-core::card>
            </form>

            {{-- Candidate's CV --}}
            <x-core::card class="mb-3">
                <x-core::card.header>
                    <x-core::card.title>Candidate's CV</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    @if($order->candidate_cv_path)
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="ti ti-file-text fs-2 text-primary"></i>
                            <div>
                                <div class="fw-semibold">CV uploaded by candidate</div>
                                <div class="text-muted small">{{ strtoupper(pathinfo($order->candidate_cv_path, PATHINFO_EXTENSION)) }} file</div>
                            </div>
                        </div>
                        <a href="{{ route('career-service-orders.download-candidate-cv', $order) }}" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-download me-1"></i>Download Candidate CV
                        </a>
                    @else
                        <p class="text-muted mb-0">The candidate has not uploaded their CV yet. They can upload it from their order confirmation page.</p>
                    @endif
                </x-core::card.body>
            </x-core::card>

            {{-- Upload reviewed CV --}}
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Upload Reviewed CV</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    @if($order->reviewed_cv_path)
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                            <i class="ti ti-circle-check fs-5"></i>
                            <span>Reviewed CV has been uploaded and delivered to the candidate.</span>
                        </div>
                        <a href="{{ route('career-service-orders.download-reviewed-cv', $order) }}" class="btn btn-outline-success btn-sm me-2">
                            <i class="ti ti-download me-1"></i>Download Reviewed CV
                        </a>
                        <span class="text-muted small">Upload a new file below to replace it.</span>
                    @else
                        <p class="text-muted mb-3">
                            Upload the completed/reviewed CV. This will be made available for the candidate to download,
                            and the order will automatically be marked as <strong>Delivered</strong>.
                        </p>
                    @endif

                    <form method="POST" action="{{ route('career-service-orders.upload-reviewed-cv', $order) }}" enctype="multipart/form-data" class="mt-3">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reviewed CV file <span class="text-danger">*</span></label>
                            <input type="file" name="reviewed_cv" class="form-control" accept=".docx,.doc,.pdf" required>
                            <div class="form-text">DOCX or PDF. Max 20 MB.</div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-upload me-1"></i>
                            {{ $order->reviewed_cv_path ? 'Replace Reviewed CV' : 'Upload &amp; Mark as Delivered' }}
                        </button>
                    </form>
                </x-core::card.body>
            </x-core::card>
        </div>

        <div class="col-lg-4">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Customer</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <dl class="mb-0">
                        <dt>Name</dt>
                        <dd>{{ $order->customer_name ?: 'N/A' }}</dd>
                        <dt>Email</dt>
                        <dd>{{ $order->customer_email ?: 'N/A' }}</dd>
                        <dt>Phone</dt>
                        <dd>{{ $order->customer_phone ?: 'N/A' }}</dd>
                        <dt>Amount</dt>
                        <dd>{{ $order->currency }} {{ number_format($order->amount, 2) }}</dd>
                        <dt>Payment</dt>
                        <dd>{{ $order->payment_method ?: 'N/A' }} {{ $order->charge_id ? '(' . $order->charge_id . ')' : '' }}</dd>
                        <dt>AI CV Score</dt>
                        <dd>{{ $order->ai_cv_score !== null ? $order->ai_cv_score . '/100' : 'Not scored' }}</dd>
                        <dt>Delivery Status</dt>
                        <dd>
                            @php
                                $statusColors = [
                                    'unassigned' => 'secondary',
                                    'assigned' => 'info',
                                    'in_progress' => 'primary',
                                    'delivered' => 'success',
                                    'revision_requested' => 'warning',
                                    'cancelled' => 'danger',
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$order->delivery_status] ?? 'secondary' }}">
                                {{ $deliveryStatuses[$order->delivery_status] ?? $order->delivery_status }}
                            </span>
                        </dd>
                        @if($order->delivered_at)
                            <dt>Delivered At</dt>
                            <dd>{{ $order->delivered_at->format('d M Y H:i') }}</dd>
                        @endif
                    </dl>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>
@endsection
