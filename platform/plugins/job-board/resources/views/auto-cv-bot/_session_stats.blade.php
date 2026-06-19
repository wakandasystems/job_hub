<div class="row g-2 mb-3" id="cvBotSessionsStats">
    <div class="col-6 col-lg-2">
        <div class="border rounded p-2 h-100">
            <div class="text-muted small">Total</div>
            <div class="fw-bold fs-5">{{ $stats['total'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="border rounded p-2 h-100">
            <div class="text-muted small">Collecting</div>
            <div class="fw-bold fs-5 text-info">{{ $stats['collecting'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="border rounded p-2 h-100">
            <div class="text-muted small">Paused</div>
            <div class="fw-bold fs-5 text-secondary">{{ $stats['paused'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="border rounded p-2 h-100">
            <div class="text-muted small">Completed</div>
            <div class="fw-bold fs-5 text-success">{{ $stats['completed'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="border rounded p-2 h-100">
            <div class="text-muted small">Failed</div>
            <div class="fw-bold fs-5 text-danger">{{ $stats['failed'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="border rounded p-2 h-100">
            <div class="text-muted small">Avg progress</div>
            <div class="fw-bold fs-5">{{ $stats['average_progress'] }}%</div>
        </div>
    </div>
</div>
