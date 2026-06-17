@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Total</div>
                    <div class="h2 mb-0">{{ number_format($stats['total']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Sent</div>
                    <div class="h2 mb-0 text-success">{{ number_format($stats['sent']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Failed</div>
                    <div class="h2 mb-0 text-danger">{{ number_format($stats['failed']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Skipped (Low Score)</div>
                    <div class="h2 mb-0 text-warning">{{ number_format($stats['skipped']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>Auto Apply Logs</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body>
            <form method="GET" action="{{ route('auto-apply-logs.index') }}" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input class="form-control" name="q" value="{{ request('q') }}"
                           placeholder="Search by candidate, job, or email">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All statuses</option>
                        <option value="sent" @selected(request('status') === 'sent')>Sent</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                        <option value="skipped_low_score" @selected(request('status') === 'skipped_low_score')>Skipped (Low Score)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input class="form-control" name="account_id" value="{{ request('account_id') }}" placeholder="Account ID">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a class="btn btn-outline-secondary" href="{{ route('auto-apply-logs.index') }}">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-vcenter table-striped">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>Candidate</th>
                            <th>Job</th>
                            <th>Sent To</th>
                            <th>Score</th>
                            <th>AI Model</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th width="80"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>#{{ $log->id }}</td>
                                <td>
                                    <div class="fw-medium">{{ $log->account?->name ?? 'Deleted' }}</div>
                                    <div class="text-muted small">{{ $log->account?->email }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        {{ $log->job?->name ?? 'Deleted' }}
                                    </div>
                                </td>
                                <td class="text-muted small">{{ $log->email_sent_to }}</td>
                                <td>
                                    @php
                                        $scoreBg = $log->match_score >= 70 ? 'success' : ($log->match_score >= 40 ? 'warning' : 'danger');
                                    @endphp
                                    <span class="badge bg-{{ $scoreBg }}">{{ $log->match_score }}%</span>
                                </td>
                                <td class="text-muted small">{{ $log->ai_model_used }}</td>
                                <td>
                                    @php
                                        $statusBg = match($log->status) {
                                            'sent' => 'success',
                                            'failed' => 'danger',
                                            'skipped_low_score' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusBg }}">{{ ucwords(str_replace('_', ' ', $log->status)) }}</span>
                                    @if($log->error_message)
                                        <div class="text-muted small">{{ Str::limit($log->error_message, 40) }}</div>
                                    @endif
                                </td>
                                <td>{{ $log->sent_at?->toDateString() }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                        data-bs-toggle="modal" data-bs-target="#emailPreviewModal"
                                        data-subject="{{ $log->ai_email_subject }}"
                                        data-body="{{ $log->ai_email_body }}"
                                        data-score="{{ $log->match_score }}"
                                        data-reasons="{{ json_encode($log->match_reasons ?? []) }}">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No auto apply logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $logs->links() }}
        </x-core::card.body>
    </x-core::card>

    {{-- Email Preview Modal --}}
    <div class="modal fade" id="emailPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Email Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><strong>Match Score:</strong> <span id="modalScore" class="badge bg-info"></span></div>
                    <div class="mb-2"><strong>Reasons:</strong> <span id="modalReasons" class="text-muted"></span></div>
                    <hr>
                    <div class="mb-2"><strong>Subject:</strong> <span id="modalSubject"></span></div>
                    <div class="card bg-light p-3">
                        <pre id="modalBody" style="white-space:pre-wrap;font-family:inherit;margin:0;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
        <script>
            document.getElementById('emailPreviewModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('modalSubject').textContent = btn.dataset.subject || '(none)';
                document.getElementById('modalBody').textContent = btn.dataset.body || '(none)';
                document.getElementById('modalScore').textContent = (btn.dataset.score || '0') + '%';
                try {
                    var reasons = JSON.parse(btn.dataset.reasons || '[]');
                    document.getElementById('modalReasons').textContent = reasons.join('; ') || 'N/A';
                } catch(e) {
                    document.getElementById('modalReasons').textContent = 'N/A';
                }
            });
        </script>
    @endpush
@endsection
