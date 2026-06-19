@php $positions = $session->suggested_job_positions ?: []; @endphp
@if (empty($positions))
    <p class="text-muted small mb-0">Suggestions will appear here as the CV builds up.</p>
@else
    @foreach ($positions as $position)
        <div class="border rounded p-2 mb-2">
            <div class="fw-semibold small">{{ $position['title'] ?? '' }}</div>
            <div class="text-muted small">{{ $position['reason'] ?? '' }}</div>
        </div>
    @endforeach
@endif
