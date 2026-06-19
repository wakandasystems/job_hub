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
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">AI Cost (Total)</div>
                    <div class="h2 mb-0">${{ number_format($stats['total_cost'], 4) }}</div>
                    <div class="text-muted small">{{ number_format($stats['total_tokens']) }} tokens</div>
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
                            <th>Company</th>
                            <th>Country</th>
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
                                    <span class="badge bg-{{ $scoreBg }} text-white">{{ $log->match_score }}%</span>
                                </td>
                                <td class="text-muted small">{{ $log->job?->company?->name ?? '—' }}</td>
                                <td class="text-muted small">
                                    @if($log->job?->country)
                                        {{ \Botble\JobBoard\Http\Controllers\AutoApplyLogController::countryFlagEmoji($log->job->country->code ?? '') }}
                                        {{ $log->job->country->name }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusBg = match($log->status) {
                                            'sent' => 'success',
                                            'failed' => 'danger',
                                            'skipped_low_score' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusBg }} text-white">{{ ucwords(str_replace('_', ' ', $log->status)) }}</span>
                                    @if($log->error_message)
                                        <div class="text-muted small">{{ Str::limit($log->error_message, 40) }}</div>
                                    @endif
                                </td>
                                <td>{{ $log->sent_at?->toDateString() }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-info"
                                            title="View email"
                                            aria-label="View email"
                                            data-bs-toggle="modal" data-bs-target="#emailPreviewModal"
                                            data-subject="{{ $log->ai_email_subject }}"
                                            data-body="{{ $log->ai_email_body }}"
                                            data-score="{{ $log->match_score }}"
                                            data-reasons="{{ json_encode($log->match_reasons ?? []) }}"
                                            data-status="{{ ucwords(str_replace('_', ' ', $log->status)) }}"
                                            data-status-bg="{{ $statusBg }}"
                                            data-sent-to="{{ $log->email_sent_to }}"
                                            data-sent-at="{{ $log->sent_at?->toDayDateTimeString() }}"
                                            data-ai-model="{{ $log->ai_model_used }}"
                                            data-tokens="{{ $log->total_tokens ? number_format($log->total_tokens) : null }}"
                                            data-cost="{{ $log->ai_cost_usd ? number_format($log->ai_cost_usd, 5) : null }}"
                                            data-error="{{ $log->error_message }}"
                                            data-resume-url="{{ $log->account?->resume ? \Botble\Media\Facades\RvMedia::getImageUrl($log->account->resume) : '' }}">
                                            <x-core::icon name="ti ti-eye" />
                                        </button>
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger"
                                            title="Delete log"
                                            aria-label="Delete log"
                                            data-bs-toggle="modal" data-bs-target="#deleteLogModal"
                                            data-action="{{ route('auto-apply-logs.destroy', $log) }}"
                                            data-label="{{ $log->account?->name ?? 'this candidate' }} — {{ $log->job?->name ?? 'this job' }}">
                                            <x-core::icon name="ti ti-trash" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No auto apply logs found.</td>
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
                    <div class="row g-2 mb-2">
                        <div class="col-md-6"><strong>Status:</strong> <span id="modalStatus" class="badge bg-secondary text-white"></span></div>
                        <div class="col-md-6"><strong>Match Score:</strong> <span id="modalScore" class="badge bg-info text-white"></span></div>
                        <div class="col-md-6"><strong>Sent To:</strong> <span id="modalSentTo" class="text-muted"></span></div>
                        <div class="col-md-6"><strong>Sent At:</strong> <span id="modalSentAt" class="text-muted"></span></div>
                        <div class="col-md-6"><strong>AI Model:</strong> <span id="modalAiModel" class="text-muted"></span></div>
                        <div class="col-md-6"><strong>AI Usage:</strong> <span id="modalUsage" class="text-muted"></span></div>
                        <div class="col-md-12"><strong>Attachment:</strong> <span id="modalResume" class="text-muted"></span></div>
                    </div>
                    <div class="mb-2" id="modalErrorWrap" style="display:none;">
                        <div class="alert alert-danger small mb-0" id="modalError"></div>
                    </div>
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

    {{-- Delete Log Modal --}}
    <div class="modal fade" id="deleteLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <x-core::icon name="ti ti-trash" class="text-danger" style="width:28px;height:28px;" />
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Delete this log?</h6>
                    <p class="text-muted small mb-4" id="deleteLogModalLabel">This cannot be undone.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <form id="deleteLogForm" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger px-4">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
        <script>
            document.getElementById('emailPreviewModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('modalSubject').textContent = btn.dataset.subject || '(not recorded)';
                document.getElementById('modalBody').textContent = btn.dataset.body || '(not recorded)';
                document.getElementById('modalScore').textContent = (btn.dataset.score || '0') + '%';
                var statusEl = document.getElementById('modalStatus');
                statusEl.textContent = btn.dataset.status || 'N/A';
                statusEl.className = 'badge text-white bg-' + (btn.dataset.statusBg || 'secondary');
                document.getElementById('modalSentTo').textContent = btn.dataset.sentTo || 'N/A';
                document.getElementById('modalSentAt').textContent = btn.dataset.sentAt || 'N/A';
                document.getElementById('modalAiModel').textContent = btn.dataset.aiModel || 'N/A';
                document.getElementById('modalUsage').textContent = btn.dataset.tokens
                    ? (btn.dataset.tokens + ' tokens / $' + btn.dataset.cost)
                    : 'N/A';

                var resumeEl = document.getElementById('modalResume');
                if (btn.dataset.resumeUrl) {
                    resumeEl.innerHTML = '<a href="' + btn.dataset.resumeUrl + '" target="_blank" rel="noopener">View candidate\'s current CV on file</a>';
                } else {
                    resumeEl.textContent = 'No CV on file';
                }

                var errorWrap = document.getElementById('modalErrorWrap');
                if (btn.dataset.error) {
                    document.getElementById('modalError').textContent = btn.dataset.error;
                    errorWrap.style.display = '';
                } else {
                    errorWrap.style.display = 'none';
                }

                try {
                    var reasons = JSON.parse(btn.dataset.reasons || '[]');
                    document.getElementById('modalReasons').textContent = reasons.join('; ') || 'N/A';
                } catch(e) {
                    document.getElementById('modalReasons').textContent = 'N/A';
                }
            });

            document.getElementById('deleteLogModal').addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('deleteLogForm').action = btn.dataset.action;
                document.getElementById('deleteLogModalLabel').textContent = btn.dataset.label || 'This cannot be undone.';
            });
        </script>
    @endpush
@endsection
