@php
    $icon = fn (string $name, string $class = '') => \Botble\Icon\Facades\Icon::render($name, ['class' => $class]);

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

    $experience = collect($cv['experience'] ?? [])->filter(fn ($row) => is_array($row) && $hasAny($row));
    $education = collect($cv['education'] ?? [])->filter(fn ($row) => is_array($row) && $hasAny($row));
    $projects = collect($cv['projects'] ?? [])->filter(fn ($row) => is_array($row) && $hasAny($row));
    $languages = collect($cv['languages'] ?? [])->filter(fn ($row) => is_array($row) && $hasAny($row));
    $references = collect($cv['references'] ?? [])->filter(fn ($row) => is_array($row) && $hasAny($row));

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

    $overallTopicNumbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
    $overallScore = (int) round(collect($overallTopicNumbers)
        ->map(fn (int $topicNumber) => (int) (($sectionScores[(string) $topicNumber]['score'] ?? 0)))
        ->avg() ?: 0);
    $overallScoreColor = $overallScore >= 90 ? 'success' : ($overallScore >= 50 ? 'warning' : 'danger');

    $sectionButton = function (int $topicNumber, string $label = 'Get More Info') use ($session, $icon) {
        return '<button type="button" class="btn btn-sm btn-outline-primary js-request-section-info" data-topic-number="' . $topicNumber . '" data-url="' . e(route('job-board.auto-cv-bot.request-section-information', $session->id)) . '">' . $icon('message-plus', 'me-1') . e($label) . '</button>';
    };

    $editable = function (string $field, $value, string $emptyText = 'Click to add') {
        $text = $value !== null ? trim((string) $value) : '';

        return '<span class="cv-editable" contenteditable="true" data-field="' . e($field) . '" data-empty-text="' . e($emptyText) . '">' . e($text) . '</span>';
    };

    $clearButton = function (string $section) use ($session, $icon) {
        return '<button type="button" class="btn btn-sm btn-outline-danger js-clear-cv-section" data-section="' . e($section) . '" data-url="' . e(route('job-board.auto-cv-bot.clear-cv-section', $session->id)) . '" title="Clear this section">' . $icon('eraser') . '</button>';
    };
@endphp

<div class="cv-section bg-light">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <strong class="small d-block">CV Review Score</strong>
            <span class="text-muted small">Average across the visible CV sections</span>
        </div>
        <span class="badge bg-{{ $overallScoreColor }} text-white fs-6 px-3 py-2">{{ $overallScore }}/100</span>
    </div>
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <h6 class="mb-0">{!! $editable('full_name', $cv['full_name'] ?? '', 'Click to add name') !!}{!! $scoreBadge(1) !!}</h6>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('name') !!}
            {!! $sectionButton(1, 'Get Name Info') !!}
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <p class="text-muted small mb-0">{!! $editable('headline', $cv['headline'] ?? '', 'Click to add headline') !!}{!! $scoreBadge(3) !!}</p>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('headline') !!}
            {!! $sectionButton(3, 'Get Headline Info') !!}
        </div>
    </div>
    <p class="small mb-0">
        <span class="text-muted">Phone:</span> {!! $editable('phone', $cv['phone'] ?? '', 'Click to add phone') !!}
        &middot; <span class="text-muted">Email:</span> {!! $editable('email', $cv['email'] ?? '', 'Click to add email') !!}
        &middot; <span class="text-muted">Location:</span> {!! $editable('location', $cv['location'] ?? '', 'Click to add location') !!}
    </p>
    <p class="small mb-0">
        <span class="text-muted">Address:</span> {!! $editable('address', $cv['address'] ?? '', 'Click to add address') !!}
        &middot; <span class="text-muted">Age:</span> {!! $editable('age', $cv['age'] ?? '', 'Click to add age') !!}
        &middot; <span class="text-muted">Marital Status:</span> {!! $editable('marital_status', $cv['marital_status'] ?? '', 'Click to add status') !!}
        {!! $scoreBadge(2) !!}
    </p>
    @if (!empty($cv['linkedin']))
        <p class="small mb-0"><span class="text-muted">LinkedIn:</span> {!! $editable('linkedin', $cv['linkedin'] ?? '') !!}</p>
    @endif
    <div class="btn-group btn-group-sm mt-2">
        {!! $clearButton('contact') !!}
        {!! $sectionButton(2, 'Get Contact Info') !!}
    </div>
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Existing CV File</strong>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary js-request-cv-upload" data-url="{{ route('job-board.auto-cv-bot.request-cv-upload', $session->id) }}">{!! $icon('message-plus', 'me-1') !!}Ask Again</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-upload-cv">{!! $icon('upload', 'me-1') !!}Upload It</button>
        </div>
    </div>
    <p class="small text-muted mb-0">Ask the candidate again if they have a CV to send (in case they missed it earlier), or upload one yourself if you already have it.</p>
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Photo</strong>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('photo') !!}
            <button type="button" class="btn btn-outline-primary js-request-cv-photo" data-url="{{ route('job-board.auto-cv-bot.request-cv-photo', $session->id) }}">{!! $icon('message-plus', 'me-1') !!}Get Photo</button>
        </div>
    </div>
    <p class="small mb-0">
        @if ($session->candidate_photo_path)
            <span class="text-success">{!! $icon('check', 'me-1') !!}Photo received</span>
        @else
            {!! $notProvided !!}
        @endif
    </p>
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Profile Summary{!! $scoreBadge(4) !!}</strong>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('summary') !!}
            {!! $sectionButton(4) !!}
        </div>
    </div>
    <p class="small mb-0">{!! $editable('summary', $cv['summary'] ?? '', 'Click to add summary') !!}</p>
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Education{!! $scoreBadge(5) !!}</strong>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('education') !!}
            {!! $sectionButton(5) !!}
        </div>
    </div>
    @if ($education->isNotEmpty())
        @foreach ($education as $i => $row)
            <div class="small mb-1"><strong>{!! $editable("education.$i.qualification", $row['qualification'] ?? '', 'Qualification') !!} — {!! $editable("education.$i.institution", $row['institution'] ?? '', 'Institution') !!}</strong></div>
        @endforeach
    @else
        <p class="small mb-0">{!! $notProvided !!}</p>
    @endif
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Certifications{!! $scoreBadge(9) !!}</strong>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('certifications') !!}
            {!! $sectionButton(9) !!}
        </div>
    </div>
    @if (!empty($cv['certifications']))
        <div>
            @foreach ($cv['certifications'] as $i => $cert)
                @php
                    $certIsArray = is_array($cert);
                    $certName = $certIsArray ? ($cert['name'] ?? '') : (string) $cert;
                    $certExtra = $certIsArray ? implode(' - ', array_filter([$cert['issuing_body'] ?? '', $cert['date'] ?? $cert['year'] ?? ''])) : '';
                @endphp
                <span class="badge bg-dark text-white small">{!! $editable($certIsArray ? "certifications.$i.name" : "certifications.$i", $certName, 'Certification') !!}{{ $certExtra !== '' ? ' - ' . $certExtra : '' }}</span>
            @endforeach
        </div>
    @else
        <p class="small mb-0">{!! $notProvided !!}</p>
    @endif
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Work Experience{!! $scoreBadge(6) !!}</strong>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('experience') !!}
            {!! $sectionButton(6) !!}
        </div>
    </div>
    @if ($experience->isNotEmpty())
        @foreach ($experience as $i => $row)
            <div class="small mb-1">
                <strong>{!! $editable("experience.$i.job_title", $row['job_title'] ?? '', 'Job title') !!} — {!! $editable("experience.$i.company", $row['company'] ?? '', 'Company') !!}</strong>
                ({!! $editable("experience.$i.start_date", $row['start_date'] ?? '', 'Start date') !!} to {!! $editable("experience.$i.end_date", $row['end_date'] ?? '', 'End date') !!})
            </div>
        @endforeach
    @else
        <p class="small mb-0">{!! $notProvided !!}</p>
    @endif
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Projects / Volunteer Work{!! $scoreBadge(7) !!}</strong>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('projects') !!}
            {!! $sectionButton(7) !!}
        </div>
    </div>
    @if ($projects->isNotEmpty())
        @foreach ($projects as $i => $row)
            <div class="small mb-1">
                {!! $editable("projects.$i.name", $row['name'] ?? '', 'Project name') !!}:
                {!! $editable("projects.$i.description", $row['description'] ?? '', 'Description') !!}
                @if (!empty($row['link']))
                    — {!! $editable("projects.$i.link", $row['link'] ?? '') !!}
                @endif
            </div>
        @endforeach
    @else
        <p class="small mb-0">{!! $notProvided !!}</p>
    @endif
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Skills{!! $scoreBadge(8) !!}</strong>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('skills') !!}
            {!! $sectionButton(8) !!}
        </div>
    </div>
    @if (!empty($cv['skills']))
        <div>
            @foreach ($cv['skills'] as $i => $skill)
                <span class="badge bg-dark text-white small">{!! $editable("skills.$i", $skill, 'Skill') !!}</span>
            @endforeach
        </div>
    @else
        <p class="small mb-0">{!! $notProvided !!}</p>
    @endif
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Languages{!! $scoreBadge(10) !!}</strong>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('languages') !!}
            {!! $sectionButton(10) !!}
        </div>
    </div>
    @if ($languages->isNotEmpty())
        <div>
            @foreach ($languages as $i => $row)
                <span class="badge bg-dark text-white small">{!! $editable("languages.$i.language", $row['language'] ?? '', 'Language') !!} - {!! $editable("languages.$i.proficiency", $row['proficiency'] ?? '', 'Level') !!}</span>
            @endforeach
        </div>
    @else
        <p class="small mb-0">{!! $notProvided !!}</p>
    @endif
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">References{!! $scoreBadge(11) !!}</strong>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('references') !!}
            {!! $sectionButton(11) !!}
        </div>
    </div>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input js-toggle-references-on-request" type="checkbox" role="switch" id="referencesOnRequestToggle" data-url="{{ route('job-board.auto-cv-bot.toggle-references-available-on-request', $session->id) }}" {{ $session->references_available_on_request ? 'checked' : '' }}>
        <label class="form-check-label small" for="referencesOnRequestToggle">Always show &quot;Available on request&quot; on generated CVs</label>
    </div>
    @if ($references->isNotEmpty())
        @foreach ($references as $i => $row)
            <div class="small mb-1">
                {!! $editable("references.$i.name", $row['name'] ?? '', 'Name') !!} | {!! $editable("references.$i.role", $row['role'] ?? '', 'Role') !!} | {!! $editable("references.$i.company", $row['company'] ?? '', 'Company') !!} | {!! $editable("references.$i.phone", $row['phone'] ?? '', 'Phone') !!} | {!! $editable("references.$i.email", $row['email'] ?? '', 'Email') !!}
            </div>
        @endforeach
        <p class="text-muted small mb-0">{!! $icon('info-circle', 'me-1') !!}These details will appear on the generated CV as given.</p>
    @else
        <p class="small mb-0">{!! $notProvided !!}</p>
    @endif
</div>

@if (!empty($cv['notes_for_admin']))
    <div class="alert alert-warning small mb-0">
        <strong>Notes for admin:</strong> {{ implode('; ', $cv['notes_for_admin']) }}
    </div>
@endif
