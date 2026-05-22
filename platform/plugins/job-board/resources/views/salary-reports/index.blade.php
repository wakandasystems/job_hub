@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Total Reports</div>
                    <div class="h2 mb-0">{{ number_format($stats['total']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Published</div>
                    <div class="h2 mb-0">{{ number_format($stats['published']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Total Sold</div>
                    <div class="h2 mb-0">{{ number_format($stats['sold']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Total Revenue</div>
                    <div class="h2 mb-0">${{ number_format($stats['revenue'], 2) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>Salary Reports</x-core::card.title>
            <div class="card-options">
                <a href="{{ route('salary-reports.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i>New Report
                </a>
            </div>
        </x-core::card.header>
        <x-core::card.body>
            <div class="table-responsive">
                <table class="table table-vcenter table-striped">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>Title</th>
                            <th>Year</th>
                            <th>Sector</th>
                            <th>Price</th>
                            <th>Sales</th>
                            <th>Status</th>
                            <th>PDF</th>
                            <th width="120"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>#{{ $report->id }}</td>
                                <td>
                                    <strong>{{ $report->title }}</strong>
                                    <div class="text-muted small">{{ $report->slug }}</div>
                                </td>
                                <td>{{ $report->year }}</td>
                                <td>{{ $report->sector ?: '—' }}</td>
                                <td>{{ $report->currency_code }} {{ number_format($report->price, 2) }}</td>
                                <td>{{ $report->purchases_count }}</td>
                                <td>
                                    <span class="badge bg-{{ $report->is_published ? 'success' : 'secondary' }} text-white">
                                        {{ $report->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                                <td>
                                    @if($report->file_path)
                                        <a href="{{ route('salary-reports.download-pdf', $report) }}" class="text-success" title="Download PDF">
                                            <i class="ti ti-file-type-pdf"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <form method="POST" action="{{ route('salary-reports.generate-pdf', $report) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary btn-icon"
                                                data-bs-toggle="tooltip" data-bs-title="Generate PDF">
                                                <i class="ti ti-refresh"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('salary-reports.toggle-published', $report) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-{{ $report->is_published ? 'warning' : 'success' }} btn-icon"
                                                data-bs-toggle="tooltip" data-bs-title="{{ $report->is_published ? 'Unpublish' : 'Publish' }}">
                                                <i class="ti ti-{{ $report->is_published ? 'eye-off' : 'eye' }}"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('salary-reports.edit', $report) }}"
                                            class="btn btn-sm btn-primary btn-icon"
                                            data-bs-toggle="tooltip" data-bs-title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-danger btn-icon"
                                            data-bs-toggle="modal"
                                            data-bs-target="#delete-report-modal"
                                            data-action="{{ route('salary-reports.destroy', $report) }}"
                                            data-label="{{ $report->title }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No salary reports yet. <a href="{{ route('salary-reports.create') }}">Create one</a>.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $reports->links() }}
        </x-core::card.body>
    </x-core::card>
@endsection

<div class="modal fade" id="delete-report-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger">Delete Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Are you sure you want to delete:</p>
                <p class="fw-semibold" id="delete-report-label"></p>
                <p class="text-muted small mb-0">This will also remove the generated PDF and cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delete-report-form" method="POST" action="">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Yes, delete it</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('footer')
<script>
    document.getElementById('delete-report-modal').addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        document.getElementById('delete-report-form').action = btn.dataset.action;
        document.getElementById('delete-report-label').textContent = btn.dataset.label;
    });
</script>
@endpush
