@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @include('plugins/job-board::featured-packages.form', [
        'action' => route('featured-packages.update', $package),
        'method' => 'POST',
        'package' => $package,
    ])
@endsection
