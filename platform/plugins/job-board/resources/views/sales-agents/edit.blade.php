@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::form :url="route('sales-agents.update', $agent->getKey())" method="put" enctype="multipart/form-data">
        @csrf

        @include('plugins/job-board::sales-agents.partials.form')
    </x-core::form>

    @include('plugins/job-board::sales-agents.partials.campaign-builder')
@stop
