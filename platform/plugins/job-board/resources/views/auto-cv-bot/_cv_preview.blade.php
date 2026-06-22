@php
    $cv = $session->structured_cv ?: [];

    $notProvided = '<span class="text-danger">Not provided yet</span>';

    $hasAny = function (array $row) use (&$hasAny) {
        foreach ($row as $value) {
            if (is_array($value)) {
                if ($hasAny($value)) {
                    return true;
                }
            } elseif (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    };

    $experience = collect($cv['experience'] ?? [])->filter(fn ($row) => is_array($row) && $hasAny($row))->values();
    $education = collect($cv['education'] ?? [])->filter(fn ($row) => is_array($row) && $hasAny($row))->values();
    $projects = collect($cv['projects'] ?? [])->filter(fn ($row) => is_array($row) && $hasAny($row))->values();
    $languages = collect($cv['languages'] ?? [])->filter(fn ($row) => is_array($row) && $hasAny($row))->values();
    $references = collect($cv['references'] ?? [])->filter(fn ($row) => is_array($row) && $hasAny($row))->values();

    $sectionScores = $session->section_scores ?: [];
    $scoreBadge = function (int $topicNumber) use ($sectionScores) {
        $section = $sectionScores[(string) $topicNumber] ?? null;

        if ($section === null) {
            return '';
        }

        $score = (int) ($section['score'] ?? 0);
        $color = $score >= 90 ? 'success' : ($score >= 50 ? 'warning' : 'danger');

        return ' <span class="badge bg-' . $color . ' text-white" title="Sufficient info gathered?">' . $score . '/100</span>';
    };

    $sectionButton = function (int $topicNumber, string $label = 'Get More Info') use ($session) {
        return '<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 js-request-section-info" data-topic-number="' . $topicNumber . '" data-url="' . e(route('job-board.auto-cv-bot.request-section-information', $session->id)) . '"><i class="ti ti-message-plus me-1"></i>' . e($label) . '</button>';
    };
@endphp

<div class="mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <h6 class="mb-0">{!! ($cv['full_name'] ?? '') !== '' ? e($cv['full_name']) : $notProvided !!}{!! $scoreBadge(1) !!}</h6>
        {!! $sectionButton(1, 'Get Name Info') !!}
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <p class="text-muted small mb-0">{!! ($cv['headline'] ?? '') !== '' ? e($cv['headline']) : $notProvided !!}{!! $scoreBadge(3) !!}</p>
        {!! $sectionButton(3, 'Get Headline Info') !!}
    </div>
    <p class="small mb-0">
        <span class="text-muted">Phone:</span> {!! ($cv['phone'] ?? '') !== '' ? e($cv['phone']) : $notProvided !!}
        &middot; <span class="text-muted">Email:</span> {!! ($cv['email'] ?? '') !== '' ? e($cv['email']) : $notProvided !!}
        &middot; <span class="text-muted">Location:</span> {!! ($cv['location'] ?? '') !== '' ? e($cv['location']) : $notProvided !!}
    </p>
    <p class="small mb-0">
        <span class="text-muted">Address:</span> {!! ($cv['address'] ?? '') !== '' ? e($cv['address']) : $notProvided !!}
        &middot; <span class="text-muted">Age:</span> {!! ($cv['age'] ?? '') !== '' ? e($cv['age']) : $notProvided !!}
        &middot; <span class="text-muted">Marital Status:</span> {!! ($cv['marital_status'] ?? '') !== '' ? e($cv['marital_status']) : $notProvided !!}
        {!! $scoreBadge(2) !!}
    </p>
    @if (!empty($cv['linkedin']))
        <p class="small mb-0"><span class="text-muted">LinkedIn:</span> {{ $cv['linkedin'] }}</p>
    @endif
    <div class="mt-2">
        {!! $sectionButton(2, 'Get Contact Info') !!}
    </div>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-1">
    <strong class="small">Photo</strong>
    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 js-request-cv-photo" data-url="{{ route('job-board.auto-cv-bot.request-cv-photo', $session->id) }}"><i class="ti ti-message-plus me-1"></i>Get Photo</button>
</div>
<p class="small mb-0">
    @if ($session->candidate_photo_path)
        <span class="text-success"><i class="ti ti-check me-1"></i>Photo received</span>
    @else
        {!! $notProvided !!}
    @endif
</p>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-1">
    <strong class="small">Profile Summary{!! $scoreBadge(4) !!}</strong>
    {!! $sectionButton(4) !!}
</div>
<p class="small mb-0">{!! ($cv['summary'] ?? '') !== '' ? e($cv['summary']) : $notProvided !!}</p>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-1">
    <strong class="small">Education{!! $scoreBadge(5) !!}</strong>
    {!! $sectionButton(5) !!}
</div>
@if ($education->isNotEmpty())
    @foreach ($education as $row)
        <div class="small mb-1"><strong>{{ $row['qualification'] ?? '' }} — {{ $row['institution'] ?? '' }}</strong></div>
    @endforeach
@else
    <p class="small mb-0">{!! $notProvided !!}</p>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-1">
    <strong class="small">Certifications{!! $scoreBadge(9) !!}</strong>
    {!! $sectionButton(9) !!}
</div>
@if (!empty($cv['certifications']))
    <div>
        @foreach ($cv['certifications'] as $cert)
            @php
                $certLabel = is_array($cert)
                    ? implode(' - ', array_filter([$cert['name'] ?? '', $cert['issuing_body'] ?? '', $cert['date'] ?? $cert['year'] ?? '']))
                    : (string) $cert;
            @endphp
            <span class="badge bg-dark text-white small">{{ $certLabel }}</span>
        @endforeach
    </div>
@else
    <p class="small mb-0">{!! $notProvided !!}</p>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-1">
    <strong class="small">Work Experience{!! $scoreBadge(6) !!}</strong>
    {!! $sectionButton(6) !!}
</div>
@if ($experience->isNotEmpty())
    @foreach ($experience as $row)
        <div class="small mb-1">
            <strong>{{ $row['job_title'] ?? '' }} — {{ $row['company'] ?? '' }}</strong>
            ({{ trim(($row['start_date'] ?? '') . ' to ' . ($row['end_date'] ?? ''), ' to') }})
        </div>
    @endforeach
@else
    <p class="small mb-0">{!! $notProvided !!}</p>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-1">
    <strong class="small">Projects / Volunteer Work{!! $scoreBadge(7) !!}</strong>
    {!! $sectionButton(7) !!}
</div>
@if ($projects->isNotEmpty())
    @foreach ($projects as $row)
        <div class="small mb-1">
            {{ $row['name'] ?? '' }}{{ !empty($row['description']) ? ': ' . $row['description'] : '' }}
            @if (!empty($row['link']))
                — <a href="{{ $row['link'] }}" target="_blank" rel="noopener">{{ $row['link'] }}</a>
            @endif
        </div>
    @endforeach
@else
    <p class="small mb-0">{!! $notProvided !!}</p>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-1">
    <strong class="small">Skills{!! $scoreBadge(8) !!}</strong>
    {!! $sectionButton(8) !!}
</div>
@if (!empty($cv['skills']))
    <div>
        @foreach ($cv['skills'] as $skill)
            <span class="badge bg-dark text-white small">{{ $skill }}</span>
        @endforeach
    </div>
@else
    <p class="small mb-0">{!! $notProvided !!}</p>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-1">
    <strong class="small">Languages{!! $scoreBadge(10) !!}</strong>
    {!! $sectionButton(10) !!}
</div>
@if ($languages->isNotEmpty())
    <div>
        @foreach ($languages as $row)
            <span class="badge bg-dark text-white small">{{ trim(($row['language'] ?? '') . ' - ' . ($row['proficiency'] ?? ''), ' -') }}</span>
        @endforeach
    </div>
@else
    <p class="small mb-0">{!! $notProvided !!}</p>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-1">
    <strong class="small">References{!! $scoreBadge(11) !!}</strong>
    {!! $sectionButton(11) !!}
</div>
@if ($references->isNotEmpty())
    @foreach ($references as $row)
        <div class="small mb-1">
            {{ implode(' | ', array_filter([$row['name'] ?? '', $row['role'] ?? '', $row['company'] ?? '', $row['phone'] ?? '', $row['email'] ?? ''])) }}
        </div>
    @endforeach
    <p class="text-muted small mb-0"><i class="ti ti-info-circle me-1"></i>These details will appear on the generated CV as given.</p>
@else
    <p class="small mb-0">{!! $notProvided !!}</p>
@endif

@if (!empty($cv['notes_for_admin']))
    <div class="alert alert-warning small mt-3 mb-0">
        <strong>Notes for admin:</strong> {{ implode('; ', $cv['notes_for_admin']) }}
    </div>
@endif
