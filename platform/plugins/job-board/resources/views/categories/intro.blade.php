@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card>
        <x-core::card.body class="text-center">
            <h3 class="mb-3">{{ trans('plugins/job-board::job-category.name') }}</h3>
            <p class="text-muted mb-4">{{ trans('plugins/job-board::job-category.intro.description', ['default' => 'Organize your jobs into categories for better content management.']) }}</p>

            <x-core::button
                tag="a"
                :href="route('job-categories.create')"
                color="primary"
                icon="ti ti-plus"
            >
                {{ trans('plugins/job-board::job-category.create') }}
            </x-core::button>
        </x-core::card.body>
    </x-core::card>
@endsection
