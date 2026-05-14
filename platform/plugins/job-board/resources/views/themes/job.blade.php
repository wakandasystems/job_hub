<h2>{{ $job->name }}</h2>

<div class="ck-content">
    {!! BaseHelper::clean($job->content) !!}
</div>

<div class="mt-4">
    {!! apply_filters(BASE_FILTER_PUBLIC_COMMENT_AREA, null, $job) !!}
</div>
