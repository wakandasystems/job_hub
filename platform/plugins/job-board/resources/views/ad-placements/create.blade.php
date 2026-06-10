@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @include('plugins/job-board::ad-placements.form', [
        'action' => route('ad-placements.store'),
        'method' => 'POST',
        'placement' => null,
    ])
@endsection
