<h2>{{ trans('plugins/job-board::dashboard.jobs_label') }}</h2>

<ul>
    @foreach ($jobs as $job)
        <li><a href="{{ $job->url }}">{{ $job->name }}</a></li>
    @endforeach
</ul>
