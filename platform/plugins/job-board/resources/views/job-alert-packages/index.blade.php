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
                            <form method="POST" action="{{ route('career-alert-packages.destroy', $pkg) }}" class="d-inline"
                                  onsubmit="return confirm('Delete this package?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
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
@endsection
