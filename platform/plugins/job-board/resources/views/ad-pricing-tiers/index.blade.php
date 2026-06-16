@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<x-core::card class="mb-3">
    <x-core::card.header>
        <x-core::card.title>Ad Reach &amp; Pricing Tiers</x-core::card.title>
        <div class="ms-auto">
            <a href="{{ route('ad-pricing-tiers.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> New Reach
            </a>
        </div>
    </x-core::card.header>
    <x-core::card.body>
        <p class="text-muted mb-0">
            A <strong>reach</strong> is a group of countries an employer can target their ad to. On each ad
            placement's edit page, set a price for every reach &mdash; e.g. <em>"Footer for Zambia"</em> $40,
            <em>"Footer for Zambia + Southern Africa"</em> $80, <em>"Footer for All Africa"</em> $200. When the
            employer requests an ad, they pick a reach and pay that price; once approved, the ad is shown only to
            visitors browsing from a country in that reach. Placements with no price set for a reach simply don't
            offer that option to employers.
        </p>
    </x-core::card.body>
</x-core::card>

@if($tiers->isEmpty())
    <x-core::card>
        <x-core::card.body>
            <p class="text-center text-muted mb-0">No pricing tiers yet. Create one to get started.</p>
        </x-core::card.body>
    </x-core::card>
@else
    <x-core::card>
        <x-core::card.body class="p-0">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Reach</th>
                            <th>Countries</th>
                            <th>Sort Order</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tiers as $tier)
                            @php
                                $tierCountryIds = $tier->country_ids ?? [];
                                $tierCountryNames = $countries->whereIn('id', $tierCountryIds)->pluck('name');
                            @endphp
                            <tr>
                                <td>{{ $tier->name }}</td>
                                <td>
                                    @if($tierCountryNames->isEmpty())
                                        <span class="text-muted">No countries assigned</span>
                                    @elseif($tierCountryNames->count() > 6)
                                        <span class="badge bg-blue-lt">{{ $tierCountryNames->count() }} countries</span>
                                        <span class="text-muted small">{{ $tierCountryNames->take(6)->implode(', ') }}, &hellip;</span>
                                    @else
                                        <span class="text-muted small">{{ $tierCountryNames->implode(', ') }}</span>
                                    @endif
                                </td>
                                <td>{{ $tier->sort_order }}</td>
                                <td class="text-end">
                                    <a href="{{ route('ad-pricing-tiers.edit', $tier) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal" data-bs-target="#deleteTierModal"
                                        data-action="{{ route('ad-pricing-tiers.destroy', $tier) }}"
                                        data-label="{{ $tier->name }}">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-core::card.body>
    </x-core::card>
@endif

<div class="modal fade" id="deleteTierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4 px-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                        <i class="ti ti-trash text-danger fs-3"></i>
                    </span>
                </div>
                <h6 class="fw-semibold mb-1">Delete this pricing tier?</h6>
                <p class="text-muted small mb-4" id="deleteTierModalLabel">This cannot be undone.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteTierForm" method="POST">
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
        document.getElementById('deleteTierModal').addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            document.getElementById('deleteTierForm').action = btn.dataset.action;
            document.getElementById('deleteTierModalLabel').textContent = btn.dataset.label;
        });
    </script>
@endpush
@endsection
