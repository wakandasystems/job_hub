<h1>{{ SeoHelper::getTitle() }}</h1>

@forelse ($applications as $application)
    <p>{{ $application->job->name }}</p>
@empty
    <p>{{ trans('plugins/job-board::messages.no_applied_jobs_found') }}</p>
@endforelse

{!! $applications->withQueryString()->links() !!}
