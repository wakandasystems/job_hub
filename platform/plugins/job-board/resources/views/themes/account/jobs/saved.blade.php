<h1>{{ SeoHelper::getTitle() }}</h1>

@forelse ($jobs as $job)
    <p>{{ $job->name }}</p>
@empty
    <p>{{ trans('plugins/job-board::messages.no_applied_jobs_found') }}</p>
@endforelse

{!! $jobs->withQueryString()->links() !!}
