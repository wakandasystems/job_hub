@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
@include('plugins/job-board::job-alert-packages.form', ['package' => null, 'action' => route('career-alert-packages.store'), 'method' => 'POST'])
@endsection
