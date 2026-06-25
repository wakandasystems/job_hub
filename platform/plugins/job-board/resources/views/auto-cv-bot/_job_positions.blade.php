@php
    $cv = $session->structured_cv ?: [];
    $positions = $session->suggested_job_positions ?: [];
    $sectionScores = $session->section_scores ?: [];
    $scoreTopicNumbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
    $totalScore = (int) round(collect($scoreTopicNumbers)
        ->map(fn (int $topicNumber) => (int) (($sectionScores[(string) $topicNumber]['score'] ?? 0)))
        ->avg() ?: 0);
    $scoreColor = $totalScore >= 90 ? 'success' : ($totalScore >= 50 ? 'warning' : 'danger');

    $photoDataUri = null;
    $photoPath = (string) ($session->candidate_photo_path ?? '');

    if ($photoPath !== '' && is_file($photoPath)) {
        $mime = mime_content_type($photoPath) ?: 'image/jpeg';
        $contents = @file_get_contents($photoPath);

        if ($contents !== false) {
            $photoDataUri = 'data:' . $mime . ';base64,' . base64_encode($contents);
        }
    }

    $displayName = trim((string) ($cv['full_name'] ?? $session->candidate_name ?? '')) ?: $session->whatsapp_number;
    $headline = trim((string) ($cv['headline'] ?? ''));
    $contactBits = array_filter([
        trim((string) ($cv['location'] ?? '')),
        trim((string) ($cv['phone'] ?? '')),
        trim((string) ($cv['email'] ?? '')),
    ]);
@endphp

<div class="candidate-hero">
    <div class="candidate-hero__profile">
        <div class="candidate-hero__avatar">
            @if ($photoDataUri)
                <img src="{{ $photoDataUri }}" alt="{{ $displayName }}" class="w-100 h-100">
            @else
                <x-core::icon name="ti ti-user" style="font-size:32px" />
            @endif
        </div>
        <div class="min-w-0">
            <div class="fw-bold fs-5 text-truncate">{{ $displayName }}</div>
            <div class="text-muted">{{ $headline !== '' ? $headline : 'Candidate profile in progress' }}</div>
        </div>
    </div>

    <div class="candidate-hero__contact">
        @if ($contactBits !== [])
            @foreach ($contactBits as $bit)
                <span class="badge bg-light text-dark border px-3 py-2">{{ $bit }}</span>
            @endforeach
        @else
            <span class="text-muted small">Contact details will appear here as the CV builds up.</span>
        @endif
    </div>

    <div class="candidate-hero__score">
        <div class="text-muted small candidate-hero__score-label">Total CV Review Score</div>
        <div class="candidate-hero__score-value text-{{ $scoreColor }}">{{ $totalScore }}/100</div>
    </div>
</div>

<hr class="my-3">

@if (empty($positions))
    <p class="text-muted small mb-0">Suggestions will appear here as the CV builds up.</p>
@else
    <div class="d-flex flex-column gap-2">
        @foreach ($positions as $position)
            <div class="border rounded p-3">
                <div class="fw-semibold">{{ $position['title'] ?? '' }}</div>
                <div class="text-muted small mb-0">{{ $position['reason'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
@endif
