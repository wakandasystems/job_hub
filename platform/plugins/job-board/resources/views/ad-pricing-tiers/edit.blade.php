@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @include('plugins/job-board::ad-pricing-tiers.form', [
        'action' => route('ad-pricing-tiers.update', $tier),
        'method' => 'POST',
        'tier' => $tier,
        'countries' => $countries,
    ])
@endsection
