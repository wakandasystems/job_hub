@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<x-core::card>
    <x-core::card.header>
        <x-core::card.title>Job Alert Packages</x-core::card.title>
        <div class="ms-auto">
            <a href="{{ route('career-alert-packages.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> New Package
            </a>
        </div>
    </x-core::card.header>
    <x-core::card.body>
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Alerts / Month</th>
                    <th>Price</th>
                    <th>Active</th>
                    <th>Order</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($packages as $pkg)
                    <tr>
                        <td>
                            <strong>{{ $pkg->name }}</strong>
                            @if($pkg->description)
                                <div class="text-muted small">{{ $pkg->description }}</div>
                            @endif
                        </td>
                        <td>{{ $pkg->isUnlimited() ? 'Unlimited' : $pkg->alerts_per_month }}</td>
                        <td>{{ $pkg->currency }} {{ number_format($pkg->price, 2) }}</td>
                        <td>
                            <span class="badge text-white {{ $pkg->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $pkg->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $pkg->sort_order }}</td>
                        <td class="text-end">
                            <a href="{{ route('career-alert-packages.edit', $pkg) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('career-alert-packages.destroy', $pkg) }}" class="d-inline pkg-delete-form">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger btn-pkg-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No packages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-core::card.body>
</x-core::card>
{{-- Delete confirmation modal --}}
<div class="modal fade" id="pkgDeleteModal" tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                        <i class="ti ti-trash text-danger fs-3"></i>
                    </span>
                </div>
                <h6 class="fw-semibold mb-1">Delete this package?</h6>
                <p class="text-muted small mb-0">This cannot be undone.</p>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="pkgDeleteConfirmBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

@push('footer')
<script>
let _pkgDeleteForm = null;
const pkgDeleteModal = new bootstrap.Modal(document.getElementById('pkgDeleteModal'));

document.querySelectorAll('.btn-pkg-delete').forEach(btn => {
    btn.addEventListener('click', function () {
        _pkgDeleteForm = this.closest('.pkg-delete-form');
        pkgDeleteModal.show();
    });
});

document.getElementById('pkgDeleteConfirmBtn').addEventListener('click', function () {
    pkgDeleteModal.hide();
    if (_pkgDeleteForm) _pkgDeleteForm.submit();
});
</script>
@endpush

@endsection
