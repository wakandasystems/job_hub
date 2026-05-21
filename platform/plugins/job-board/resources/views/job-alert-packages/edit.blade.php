@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
@include('plugins/job-board::job-alert-packages.form', ['action' => route('career-alert-packages.update', $package), 'method' => 'POST'])
@endsection
