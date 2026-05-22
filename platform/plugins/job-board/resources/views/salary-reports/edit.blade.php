@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>{{ $report ? 'Edit: ' . $report->title : 'New Salary Report' }}</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body>
            <form method="POST"
                action="{{ $report ? route('salary-reports.update', $report) : route('salary-reports.store') }}">
                @csrf
                @if($report) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                            name="title" value="{{ old('title', $report?->title) }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Year <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('year') is-invalid @enderror"
                            name="year" value="{{ old('year', $report?->year ?? date('Y')) }}"
                            min="2020" max="2050" required>
                        @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Published</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_published" value="1"
                                {{ old('is_published', $report?->is_published) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Sector / Market</label>
                        <input type="text" class="form-control" name="sector"
                            value="{{ old('sector', $report?->sector) }}"
                            placeholder="e.g. Technology, Banking, All Sectors">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Price <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('price') is-invalid @enderror"
                            name="price" value="{{ old('price', $report?->price ?? 0) }}"
                            min="0" step="0.01" required>
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Currency Code</label>
                        <input type="text" class="form-control" name="currency_code"
                            value="{{ old('currency_code', $report?->currency_code ?? 'USD') }}"
                            maxlength="10" placeholder="USD">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"
                            placeholder="Describe what this report covers…">{{ old('description', $report?->description) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        {{ $report ? 'Save Changes' : 'Create Report' }}
                    </button>
                    <a href="{{ route('salary-reports.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </x-core::card.body>
    </x-core::card>

    @if($report)
        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <x-core::card>
                    <x-core::card.header>
                        <x-core::card.title>PDF Generation</x-core::card.title>
                    </x-core::card.header>
                    <x-core::card.body>
                        @if($report->file_path)
                            <div class="alert alert-success mb-3">
                                PDF generated. <a href="{{ route('salary-reports.download-pdf', $report) }}">Download it</a>.
                            </div>
                        @else
                            <p class="text-muted">No PDF generated yet.</p>
                        @endif
                        <form method="POST" action="{{ route('salary-reports.generate-pdf', $report) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="ti ti-refresh me-1"></i>{{ $report->file_path ? 'Regenerate PDF' : 'Generate PDF' }}
                            </button>
                        </form>
                    </x-core::card.body>
                </x-core::card>
            </div>

            <div class="col-md-6">
                <x-core::card>
                    <x-core::card.header>
                        <x-core::card.title>Purchase History ({{ $report->purchases_count ?? $report->purchases()->count() }})</x-core::card.title>
                    </x-core::card.header>
                    <x-core::card.body class="p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Buyer</th>
                                        <th>Company</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">Downloaded</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($report->purchases()->latest()->take(10)->get() as $purchase)
                                        <tr>
                                            <td>
                                                {{ $purchase->buyer_name }}
                                                <div class="text-muted small">{{ $purchase->buyer_email }}</div>
                                            </td>
                                            <td>{{ $purchase->buyer_company ?: '—' }}</td>
                                            <td class="text-end">{{ $purchase->currency_code }} {{ number_format($purchase->amount_paid, 2) }}</td>
                                            <td class="text-end">{{ $purchase->downloaded_at?->toDateString() ?: 'Not yet' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">No purchases yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-core::card.body>
                </x-core::card>
            </div>
        </div>
    @endif
@endsection
