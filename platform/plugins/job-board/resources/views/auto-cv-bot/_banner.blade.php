@if ($session->status === 'completed')
    <div class="alert alert-success mb-0">
        <i class="ti ti-alert-triangle me-1"></i>
        <strong>Needs your review.</strong> The bot finished the interview and generated a CV automatically —
        please check &amp; verify the details below before sending it to the candidate or an employer.
    </div>
@elseif ($session->status === 'failed')
    @php
        $errorDetails = trim((string) ($session->error_trace ?: $session->error_message));
    @endphp
    <div class="card border-danger mb-0">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong><i class="ti ti-alert-triangle me-1"></i>CV Bot failed</strong>
                <div class="small text-white-50">Use this diagnostic when reporting or debugging the issue.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-light js-ask-candidate-resend" data-url="{{ route('job-board.auto-cv-bot.ask-candidate-to-resend', $session->id) }}">
                    <i class="ti ti-message-forward me-1"></i> Ask to Resend
                </button>
                <button type="button" class="btn btn-sm btn-light js-retry-auto-cv-generation" data-url="{{ route('job-board.auto-cv-bot.retry-generation', $session->id) }}">
                    <i class="ti ti-refresh me-1"></i> Retry CV Generation
                </button>
                @if ($errorDetails !== '')
                    <button type="button" class="btn btn-sm btn-light js-copy-auto-cv-error" data-target="#autoCvErrorDetails{{ $session->id }}">
                        <i class="ti ti-copy me-1"></i> Copy Error
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-1">Short error</div>
            <div class="fw-semibold text-danger">{{ $session->error_message ?: 'Unknown error.' }}</div>

            @if ($errorDetails !== '')
                <details class="mt-3">
                    <summary class="small fw-semibold">Full diagnostic details</summary>
                    <pre class="bg-light border rounded p-3 mt-2 mb-0 small text-wrap" id="autoCvErrorDetails{{ $session->id }}" style="white-space: pre-wrap;">{{ $errorDetails }}</pre>
                </details>
            @endif
        </div>
    </div>
@elseif ($session->status === 'stalled')
    <div class="alert alert-secondary mb-0">
        The candidate hasn't replied since {{ $session->last_question_sent_at?->format('d M Y H:i') }}.
        You can resend the last question below.
    </div>
@endif
