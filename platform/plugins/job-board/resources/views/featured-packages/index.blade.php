@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<x-core::card>
    <x-core::card.header>
        <x-core::card.title>Featured Job Packages</x-core::card.title>
        <div class="ms-auto">
            <a href="{{ route('featured-packages.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> New Package
            </a>
        </div>
    </x-core::card.header>
    <x-core::card.body>
        <p class="text-muted mb-3">
            Configure the credit cost employers use to feature or sponsor their job posts.
            Active packages appear in the employer dashboard under <strong>Feature a Job</strong>.
        </p>
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Duration</th>
                    <th>Badge</th>
                    <th>Credit Cost</th>
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
                        <td>{{ $pkg->displayDuration() }}</td>
                        <td><span class="badge bg-warning text-dark">{{ $pkg->badge_label }}</span></td>
                        <td>{{ number_format((int) ceil($pkg->price)) }} credits</td>
                        <td>
                            <span class="badge text-white {{ $pkg->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $pkg->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $pkg->sort_order }}</td>
                        <td class="text-end">
                            <a href="{{ route('featured-packages.edit', $pkg) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#deletePackageModal"
                                data-action="{{ route('featured-packages.destroy', $pkg) }}"
                                data-label="{{ $pkg->name }}">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No packages yet. Create one to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-core::card.body>
</x-core::card>

<div class="modal fade" id="deletePackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4 px-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                        <i class="ti ti-trash text-danger fs-3"></i>
                    </span>
                </div>
                <h6 class="fw-semibold mb-1">Delete this package?</h6>
                <p class="text-muted small mb-4" id="deletePackageModalLabel">This cannot be undone.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <form id="deletePackageForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger px-4">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('footer')
    <script>
        document.getElementById('deletePackageModal').addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            document.getElementById('deletePackageForm').action = btn.dataset.action;
            document.getElementById('deletePackageModalLabel').textContent = btn.dataset.label;
        });
    </script>
@endpush
@endsection
