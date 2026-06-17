@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
<div>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h3 class="mt-0 mb-0 color-brand-1">{{ __('Review Matching Jobs') }}</h3>
        <a href="{{ route('public.account.auto-apply.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i> {{ __('Back to Auto Apply') }}
        </a>
    </div>
    <p class="color-text-paragraph-2 font-sm mb-30">
        {{ __('These are jobs from the last 7 days that match your Auto Apply filters. Review and send applications individually.') }}
    </p>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($matchingJobs->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="ti ti-mood-happy" style="font-size:48px;color:#ccc;"></i>
                <p class="text-muted mt-2">{{ __('No matching jobs found in the last 7 days. New matches will be auto-applied when jobs are published.') }}</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="mb-3 text-muted">
                    <strong>{{ $matchingJobs->count() }}</strong> matching jobs found
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Job') }}</th>
                                <th>{{ __('Company') }}</th>
                                <th>{{ __('Location') }}</th>
                                <th>{{ __('Posted') }}</th>
                                <th>{{ __('Apply Email') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matchingJobs as $job)
                                <tr>
                                    <td>
                                        <a href="{{ $job->url ?? '#' }}" target="_blank" class="fw-medium">
                                            {{ Str::limit($job->name, 45) }}
                                        </a>
                                    </td>
                                    <td class="text-muted">{{ $job->company?->name ?? '—' }}</td>
                                    <td class="text-muted small">{{ Str::limit($job->address ?? '—', 30) }}</td>
                                    <td class="text-muted small">{{ $job->created_at?->diffForHumans() }}</td>
                                    <td class="text-muted small">{{ $job->apply_email }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('public.account.auto-apply.send-single', $job->id) }}"
                                              onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='Sending...';">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="ti ti-send me-1"></i> {{ __('Send') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
