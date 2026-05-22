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
            Configure the packages employers can purchase to feature or sponsor their job posts.
            Active packages appear in the employer dashboard under <strong>Feature a Job</strong>.
        </p>
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Duration</th>
                    <th>Badge</th>
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
                        <td>{{ $pkg->displayDuration() }}</td>
                        <td><span class="badge bg-warning text-dark">{{ $pkg->badge_label }}</span></td>
                        <td>{{ $pkg->currency }} {{ number_format($pkg->price, 2) }}</td>
                        <td>
                            <span class="badge text-white {{ $pkg->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $pkg->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $pkg->sort_order }}</td>
                        <td class="text-end">
                            <a href="{{ route('featured-packages.edit', $pkg) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('featured-packages.destroy', $pkg) }}" class="d-inline"
                                  onsubmit="return confirm('Delete this package?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No packages yet. Create one to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-core::card.body>
</x-core::card>
@endsection
