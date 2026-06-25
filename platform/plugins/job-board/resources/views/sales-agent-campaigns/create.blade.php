@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::form :url="route('sales-agent-campaigns.store')" method="post">
        @csrf

        @include('plugins/job-board::sales-agent-campaigns.partials.form')
    </x-core::form>
@stop
