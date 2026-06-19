@php
    $statusColors = [
        'collecting' => 'info',
        'ready' => 'warning',
        'completed' => 'success',
        'failed' => 'danger',
        'stalled' => 'secondary',
        'paused' => 'secondary',
    ];
@endphp

@forelse ($sessions as $session)
    @php
        $covered = count($session->topics_covered ?: []);
        $total = count($session->topics ?: []);
        $percent = $total > 0 ? min(100, (int) round(($covered / $total) * 100)) : 0;
        $progressColor = $percent >= 100 ? 'success' : ($percent >= 50 ? 'warning' : 'info');
    @endphp
    <tr data-session-id="{{ $session->id }}">
        <td>{{ $session->candidate_name ?: '-' }}</td>
        <td>{{ $session->whatsapp_number }}</td>
        <td>
            <span class="badge bg-{{ $statusColors[$session->status] ?? 'secondary' }} text-white">
                {{ ucfirst($session->status) }}
            </span>
        </td>
        <td style="min-width: 180px;">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                <span class="small">{{ $covered }} / {{ $total }}</span>
                <span class="small text-muted">{{ $percent }}%</span>
            </div>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-{{ $progressColor }}" role="progressbar" style="width: {{ $percent }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </td>
        <td>{{ $session->created_at?->format('d M Y H:i') }}</td>
        <td class="text-end">
            <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                <a href="{{ route('job-board.auto-cv-bot.show', $session->id) }}" class="btn btn-sm btn-outline-dark">
                    View
                </a>
                @if ($session->status === 'paused')
                    <button type="button" class="btn btn-sm btn-success text-white js-cv-bot-action" data-url="{{ route('job-board.auto-cv-bot.resume', $session->id) }}" data-action="resume" data-label="{{ $session->candidate_name ?: $session->whatsapp_number }}" title="Resume session">
                        Play
                    </button>
                @elseif ($session->status !== 'completed')
                    <button type="button" class="btn btn-sm btn-warning text-dark js-cv-bot-action" data-url="{{ route('job-board.auto-cv-bot.pause', $session->id) }}" data-action="pause" data-label="{{ $session->candidate_name ?: $session->whatsapp_number }}" title="Pause session">
                        Pause
                    </button>
                @endif
                <button type="button" class="btn btn-sm btn-danger text-white js-delete-cv-bot-session" data-bs-toggle="modal" data-bs-target="#deleteCvBotSessionModal" data-url="{{ route('job-board.auto-cv-bot.destroy', $session->id) }}" data-label="{{ $session->candidate_name ?: $session->whatsapp_number }}" title="Delete session">
                    Delete
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center text-muted py-4">No CV bot sessions yet.</td>
    </tr>
@endforelse
