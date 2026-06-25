@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::form :url="route('sales-agents.store')" method="post" enctype="multipart/form-data">
        @csrf

        @include('plugins/job-board::sales-agents.partials.form')
    </x-core::form>
@stop
