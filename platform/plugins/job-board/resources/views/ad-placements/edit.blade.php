@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @include('plugins/job-board::ad-placements.form', [
        'action' => route('ad-placements.update', $placement),
        'method' => 'POST',
        'placement' => $placement,
        'tiers' => $tiers,
    ])
@endsection
