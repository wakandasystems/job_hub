@php
    Theme::set('pageTitle', trans('plugins/job-board::messages.job_position_closed', ['name' => $job->name]));
@endphp

<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card text-center p-5">
                    <div class="card-body">
                        <div class="mb-4">
                            <i class="mdi mdi-close-circle-outline text-danger" style="font-size: 60px;"></i>
                        </div>
                        <h2 class="mb-3">{{ trans('plugins/job-board::messages.job_position_is_closed') }}</h2>
                        <h4 class="text-muted mb-4">{{ $job->name }}</h4>

                        <p class="text-muted mb-4">
                            {{ trans('plugins/job-board::messages.job_no_longer_available_message') }}
                        </p>

                        <div class="alert alert-info">
                            <p class="mb-0">
                                {{ trans('plugins/job-board::messages.job_check_other_opportunities') }}
                            </p>
                        </div>

                        <div class="mt-4">
                            <a href="{{ $jobsUrl }}" class="btn btn-primary">
                                <i class="mdi mdi-briefcase-search me-2"></i>
                                {{ trans('plugins/job-board::messages.browse_available_jobs') }}
                            </a>
                        </div>
                    </div>
                </div>

                @if($job->company && !$job->hide_company)
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ trans('plugins/job-board::messages.about_the_company') }}</h5>
                        <div class="d-flex align-items-center">
                            @if($job->company->logo)
                            <img src="{{ $job->company->logo_thumb }}" alt="{{ $job->company->name }}" class="me-3" style="width: 60px; height: 60px; object-fit: contain;">
                            @endif
                            <div>
                                <h6 class="mb-1">{{ $job->company->name }}</h6>
                                <p class="text-muted mb-0">{{ Str::limit($job->company->description, 100) }}</p>
                                <a href="{{ $job->company->url }}" class="btn btn-link p-0 mt-2">
                                    {{ trans('plugins/job-board::messages.view_company_profile') }} <i class="mdi mdi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>