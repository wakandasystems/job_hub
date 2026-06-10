@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @include('plugins/job-board::ad-pricing-tiers.form', [
        'action' => route('ad-pricing-tiers.store'),
        'method' => 'POST',
        'tier' => null,
        'countries' => $countries,
    ])
@endsection
