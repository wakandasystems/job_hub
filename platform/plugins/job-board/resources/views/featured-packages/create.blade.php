@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @include('plugins/job-board::featured-packages.form', [
        'action' => route('featured-packages.store'),
        'method' => 'POST',
        'package' => null,
    ])
@endsection
