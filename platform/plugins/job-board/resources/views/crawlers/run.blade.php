@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row g-3">
        <div class="col-md-6 col-12">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Run Summary</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Agent</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('job-board.crawlers.edit', $run->crawler_id) }}">{{ $run->crawler->name }}</a>
                        </dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">{{ $run->status }}</dd>

                        <dt class="col-sm-4">Started</dt>
                        <dd class="col-sm-8">{{ $run->started_at?->toDateTimeString() ?: '—' }}</dd>

                        <dt class="col-sm-4">Finished</dt>
                        <dd class="col-sm-8">{{ $run->finished_at?->toDateTimeString() ?: '—' }}</dd>
                    </dl>
                </x-core::card.body>
            </x-core::card>
        </div>

        <div class="col-md-6 col-12">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Import Results</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Jobs found</dt>
                        <dd class="col-sm-8">{{ number_format($run->jobs_found) }}</dd>

                        <dt class="col-sm-4">Created</dt>
                        <dd class="col-sm-8">{{ number_format($run->jobs_created) }}</dd>

                        <dt class="col-sm-4">Updated</dt>
                        <dd class="col-sm-8">{{ number_format($run->jobs_updated) }}</dd>

                        <dt class="col-sm-4">Skipped</dt>
                        <dd class="col-sm-8">{{ number_format($run->jobs_skipped) }}</dd>
                    </dl>
                </x-core::card.body>
            </x-core::card>
        </div>

        <div class="col-12">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Error Details</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    @if($run->error_message)
                        <p class="text-danger">{{ $run->error_message }}</p>
                        @if($run->error_trace)
                            <pre class="bg-light p-3 rounded small" style="white-space: pre-wrap;">{{ $run->error_trace }}</pre>
                        @endif
                    @else
                        <p class="text-muted mb-0">No errors were recorded for this run.</p>
                    @endif
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>
@endsection
