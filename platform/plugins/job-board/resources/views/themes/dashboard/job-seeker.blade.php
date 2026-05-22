@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    <x-core::stat-widget class="mb-3 row-cols-1 row-cols-sm-2 row-cols-md-4">
        <x-core::stat-widget.item
            :label="__('Applications')"
            :value="$totalApplications"
            icon="ti ti-send"
            color="primary"
        />

        <x-core::stat-widget.item
            :label="__('Saved Jobs')"
            :value="$savedJobs"
            icon="ti ti-bookmark"
            color="success"
        />

        <x-core::stat-widget.item
            :label="__('Job Alerts')"
            :value="$activeAlerts"
            icon="ti ti-bell"
            color="warning"
        />

        <x-core::stat-widget.item
            :label="__('CV Score')"
            :value="$profileScore ? $profileScore . '/100' : __('Not scored')"
            icon="ti ti-sparkles"
            color="info"
        />
    </x-core::stat-widget>

    <div class="row row-cards mb-3">
        <div class="col-lg-8">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ __('Recent Applications') }}</x-core::card.title>
                    <x-core::card.actions>
                        <x-core::button tag="a" size="sm" :href="route('public.account.jobs.applied-jobs')">
                            {{ __('View all') }}
                        </x-core::button>
                    </x-core::card.actions>
                </x-core::card.header>

                <div class="list-group list-group-flush">
                    @forelse ($recentApplications as $application)
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="{{ $application->job->url }}" class="text-reset fw-medium d-block">
                                        {{ $application->job->name }}
                                    </a>
                                    <div class="d-block text-secondary text-truncate mt-n1">
                                        {{ $application->job->company->name }} · {{ $application->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    {!! $application->status->toHtml() !!}
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-core::empty-state
                            :title="__('No applications yet')"
                            :subtitle="__('Jobs you apply for will appear here.')"
                        />
                    @endforelse
                </div>
            </x-core::card>
        </div>

        <div class="col-lg-4">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ __('Quick Actions') }}</x-core::card.title>
                </x-core::card.header>

                <div class="list-group list-group-flush">
                    <a href="{{ JobBoardHelper::getJobsPageURL() ?: route('public.index') }}" class="list-group-item list-group-item-action">
                        <x-core::icon name="ti ti-search" class="me-2" />
                        {{ __('Find Jobs') }}
                    </a>
                    <a href="{{ route('public.account.settings') }}" class="list-group-item list-group-item-action">
                        <x-core::icon name="ti ti-user-edit" class="me-2" />
                        {{ __('Update Profile') }}
                    </a>
                    <a href="{{ route('public.career-service.cv-score') }}" class="list-group-item list-group-item-action">
                        <x-core::icon name="ti ti-sparkles" class="me-2" />
                        {{ __('Score My CV') }}
                    </a>
                    <a href="{{ route('public.account.job-alerts.index') }}" class="list-group-item list-group-item-action">
                        <x-core::icon name="ti ti-bell-plus" class="me-2" />
                        {{ __('Create Job Alert') }}
                    </a>
                </div>
            </x-core::card>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ __('Saved Jobs') }}</x-core::card.title>
                    <x-core::card.actions>
                        <x-core::button tag="a" size="sm" :href="route('public.account.jobs.saved')">
                            {{ __('View all') }}
                        </x-core::button>
                    </x-core::card.actions>
                </x-core::card.header>

                <div class="list-group list-group-flush">
                    @forelse ($recentSavedJobs as $job)
                        <div class="list-group-item">
                            <a href="{{ $job->url }}" class="text-reset fw-medium d-block">{{ $job->name }}</a>
                            <div class="text-secondary text-truncate mt-n1">
                                {{ $job->company->name }} · {{ $job->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <x-core::empty-state
                            :title="__('No saved jobs yet')"
                            :subtitle="__('Save jobs you want to revisit later.')"
                        />
                    @endforelse
                </div>
            </x-core::card>
        </div>

        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ __('Recent Activity') }}</x-core::card.title>
                </x-core::card.header>

                <div class="list-group list-group-flush">
                    @forelse ($activities as $activity)
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <div class="d-block text-secondary text-truncate">{!! BaseHelper::clean($activity->getDescription(false)) !!}</div>
                                </div>
                                <div class="col-auto text-secondary">
                                    {{ $activity->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-core::empty-state
                            :title="__('No recent activity')"
                            :subtitle="__('Your account activity will appear here.')"
                        />
                    @endforelse
                </div>
            </x-core::card>
        </div>
    </div>
@endsection
