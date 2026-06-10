@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<x-core::card class="mb-3">
    <x-core::card.header>
        <x-core::card.title>Ad Pricing</x-core::card.title>
        <div class="ms-auto">
            <a href="{{ route('ad-placements.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> New Placement
            </a>
        </div>
    </x-core::card.header>
    <x-core::card.body>
        <p class="text-muted mb-0">
            Configure the price employers pay to run their own banner ad in each section of the site.
            Active placements appear on the employer dashboard under <strong>Advertise</strong>.
        </p>
    </x-core::card.body>
</x-core::card>

@if($groups->isEmpty())
    <x-core::card>
        <x-core::card.body>
            <p class="text-center text-muted mb-0">No ad placements yet. Create one to get started.</p>
        </x-core::card.body>
    </x-core::card>
@else
    <x-core::tab>
        @foreach($groups as $groupName => $groupPlacements)
            <x-core::tab.item
                :is-active="$loop->first"
                id="ad-group-{{ Str::slug($groupName) }}"
                label="{{ $groupName }} ({{ $groupPlacements->count() }})"
            />
        @endforeach
    </x-core::tab>

    <x-core::tab.content>
        @foreach($groups as $groupName => $groupPlacements)
            <x-core::tab.pane
                id="ad-group-{{ Str::slug($groupName) }}"
                :is-active="$loop->first"
            >
                <div class="row row-cards mt-3">
                    @foreach($groupPlacements as $placement)
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <h5 class="mb-0">{{ $placement->name }}</h5>
                                        <span class="badge text-white {{ $placement->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $placement->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <code>{{ $placement->location }}</code>
                                    </div>
                                    @if($placement->description)
                                        <p class="text-muted small mb-3">{{ $placement->description }}</p>
                                    @endif
                                    <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="h4 mb-0">{{ $placement->currency }} {{ number_format($placement->price, 2) }}</div>
                                            <div class="text-muted small">{{ $placement->displayDuration() }} &middot; order {{ $placement->sort_order }}</div>
                                        </div>
                                        <div>
                                            <a href="{{ route('ad-placements.edit', $placement) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#deletePlacementModal"
                                                data-action="{{ route('ad-placements.destroy', $placement) }}"
                                                data-label="{{ $placement->name }}">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-core::tab.pane>
        @endforeach
    </x-core::tab.content>
@endif

<div class="modal fade" id="deletePlacementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4 px-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                        <i class="ti ti-trash text-danger fs-3"></i>
                    </span>
                </div>
                <h6 class="fw-semibold mb-1">Delete this placement?</h6>
                <p class="text-muted small mb-4" id="deletePlacementModalLabel">This cannot be undone.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <form id="deletePlacementForm" method="POST">
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
        document.getElementById('deletePlacementModal').addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            document.getElementById('deletePlacementForm').action = btn.dataset.action;
            document.getElementById('deletePlacementModalLabel').textContent = btn.dataset.label;
        });
    </script>
@endpush
@endsection
