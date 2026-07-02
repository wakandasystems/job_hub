@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    <div class="row row-cards">
        <div class="col-lg-8">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ __('About') }}</x-core::card.title>
                </x-core::card.header>

                <x-core::card.body>
                    @if ($account->description)
                        <p class="text-muted mb-0">{!! BaseHelper::clean($account->description) !!}</p>
                    @else
                        <x-core::empty-state
                            :title="__('No overview yet')"
                            :subtitle="__('Add a profile description from My Profile.')"
                        />
                    @endif
                </x-core::card.body>
            </x-core::card>
        </div>

        <div class="col-lg-4">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ __('Profile') }}</x-core::card.title>
                </x-core::card.header>

                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <div class="text-secondary">{{ __('Name') }}</div>
                        <div class="fw-medium">{{ $account->name }}</div>
                    </div>
                    <div class="list-group-item">
                        <div class="text-secondary">{{ __('Email') }}</div>
                        <div class="fw-medium">{{ $account->email }}</div>
                    </div>
                </div>
            </x-core::card>
        </div>
    </div>

    @if($countEducation = $educations->count())
        <x-core::card class="mt-3">
            <x-core::card.header>
                <x-core::card.title>{{ __('Education') }}</x-core::card.title>
            </x-core::card.header>

            <div class="list-group list-group-flush">
                @foreach($educations as $education)
                    <div class="list-group-item">
                        @if ($education->specialized)
                            <div class="fw-bold">{{ $education->specialized }}</div>
                        @endif
                        <div class="text-secondary">
                            {{ $education->school }} -
                            {{ $education->started_at?->format('Y') ?: __('N/A') }} -
                            {{ $education->ended_at?->format('Y') ?: __('Now') }}
                        </div>
                        @if ($education->description)
                            <p class="text-muted mb-0 mt-2">{!! BaseHelper::clean($education->description) !!}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-core::card>
    @endif

    @if($countExperience = $experiences->count())
        <x-core::card class="mt-3">
            <x-core::card.header>
                <x-core::card.title>{{ __('Experience') }}</x-core::card.title>
            </x-core::card.header>

            <div class="list-group list-group-flush">
                @foreach($experiences as $experience)
                    <div class="list-group-item">
                        @if ($experience->position)
                            <div class="fw-bold">{{ $experience->position }}</div>
                        @endif
                        <div class="text-secondary">
                            {{ $experience->company }} -
                            {{ $experience->started_at?->format('Y') ?: __('N/A') }} -
                            {{ $experience->ended_at?->format('Y') ?: __('Now') }}
                        </div>
                        @if ($experience->description)
                            <p class="text-muted mb-0 mt-2">{!! BaseHelper::clean($experience->description) !!}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-core::card>
    @endif
@endsection
