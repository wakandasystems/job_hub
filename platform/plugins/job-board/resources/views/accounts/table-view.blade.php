@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="row g-4 mb-3">

    {{-- Stats --}}
    <div class="col-12">
        <x-core::stat-widget class="mb-0">
            <x-core::stat-widget.item label="Total Users" :value="$stats['total']" icon="ti ti-users" color="primary" />
            <x-core::stat-widget.item label="Registered This Month" :value="$stats['this_month']" icon="ti ti-user-plus" color="info" />
            <x-core::stat-widget.item label="Verified" :value="$stats['verified']" icon="ti ti-user-check" color="success" />
            <x-core::stat-widget.item label="Unverified" :value="$stats['unverified']" icon="ti ti-user-exclamation" color="{{ $stats['unverified'] > 0 ? 'warning' : 'secondary' }}" />
        </x-core::stat-widget>
    </div>

</div>

{{-- The DataTable --}}
@include('core/table::base-table')
@endsection
