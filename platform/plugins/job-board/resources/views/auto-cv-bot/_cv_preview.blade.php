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
        return '<button type="button" class="btn btn-sm btn-outline-primary js-request-section-info" data-topic-number="' . $topicNumber . '" data-topic-label="' . e($label) . '" data-url="' . e(route('job-board.auto-cv-bot.request-section-information', $session->id)) . '">' . $icon('message-plus', 'me-1') . e($label) . '</button>';
    };

    $editable = function (string $field, $value, string $emptyText = 'Click to add') {
        $text = $value !== null ? trim((string) $value) : '';

        return '<span class="cv-editable" contenteditable="true" data-field="' . e($field) . '" data-empty-text="' . e($emptyText) . '">' . e($text) . '</span>';
    };

    $clearButton = function (string $section) use ($session, $icon) {
        return '<button type="button" class="btn btn-sm btn-outline-danger js-clear-cv-section" data-section="' . e($section) . '" data-url="' . e(route('job-board.auto-cv-bot.clear-cv-section', $session->id)) . '" title="Clear this section">' . $icon('eraser') . '</button>';
    };

    $addButton = function (string $section, string $label) use ($session, $icon) {
        return '<button type="button" class="btn btn-sm btn-outline-success js-add-cv-item" data-section="' . e($section) . '" data-label="' . e($label) . '" data-url="' . e(route('job-board.auto-cv-bot.add-cv-item', $session->id)) . '" title="Add ' . e($label) . '">' . $icon('plus') . '</button>';
    };

    $requestUrl = route('job-board.auto-cv-bot.request-section-information', $session->id);

    $contactFields = [
        'phone'          => ["What's the best number to call you on? We already have your WhatsApp — this is for a separate call number if different.",  'Call number'],
        'whatsapp'       => ['(Auto-filled from the WhatsApp session number)',                                                                          'WhatsApp number'],
        'email'          => ["What's the best email address to reach you on? If you don't have one, just say no.",                                     'Email address'],
        'location'       => ['Which town or city do you live in?',                                                                                     'Town / City'],
        'address'        => ["Do you want to add your residential area? For example: Chalala, Libala, or Matero. If not, just say no.",                'Residential area'],
        'age'            => ["How old are you, if you don't mind sharing? If you'd rather skip it, just say no.",                                      'Age'],
        'marital_status' => ["Would you like to include your marital status? If not, just say no.",                                                    'Marital status'],
        'linkedin'       => ["Do you have a LinkedIn profile? If not, just say no.",                                                                   'LinkedIn'],
    ];
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
    {{-- Contact sub-field breakdown --}}
    <div class="mt-2 border-top pt-2">
        @foreach ($contactFields as $field => [$question, $label])
            @php
                $val     = trim((string) ($cv[$field] ?? ''));
                $filled  = $val !== '';
                $color   = $filled ? 'success' : 'danger';
                $editKey = $field;
                $emptyTxt = 'Click to add ' . strtolower($label);
            @endphp
            <div class="d-flex align-items-center gap-2 py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                <span class="badge bg-{{ $color }}-lt text-{{ $color }} border border-{{ $color }} flex-shrink-0" style="font-size:10px;min-width:90px">
                    {{ $label }}
                </span>
                <span class="small flex-grow-1 min-width-0" style="word-break:break-word">
                    @if ($filled)
                        {!! $editable($editKey, $val, $emptyTxt) !!}
                    @else
                        <span class="text-danger">✗ missing</span>
                    @endif
                </span>
                @if (!$filled)
                    <button type="button"
                            class="btn btn-xs btn-outline-primary js-request-section-info flex-shrink-0"
                            style="font-size:10px;padding:1px 7px"
                            data-topic-number="2"
                            data-topic-label="{{ $label }}"
                            data-exact-question="{{ $question }}"
                            data-url="{{ $requestUrl }}">
                        {!! $icon('message-plus', 'me-1') !!}Ask
                    </button>
                @endif
            </div>
        @endforeach
    </div>
    {!! $scoreBadge(2) !!}
    <div class="btn-group btn-group-sm mt-2">
        {!! $clearButton('contact') !!}
        {!! $sectionButton(2, 'Get Contact Info') !!}
    </div>
</div>

@php
    $uploadedCvPath  = trim((string) ($session->candidate_cv_path ?? ''));
    $uploadedCvExists = $uploadedCvPath !== '' && \Illuminate\Support\Facades\Storage::disk('local')->exists($uploadedCvPath);
    $uploadedCvUrl   = $uploadedCvExists ? route('job-board.auto-cv-bot.uploaded-cv', $session->id) : null;
    $uploadedCvExt   = $uploadedCvExists ? strtolower(pathinfo($uploadedCvPath, PATHINFO_EXTENSION)) : '';
    $uploadedCvIsPdf = in_array($uploadedCvExt, ['pdf'], true);
    $uploadedCvIsImg = in_array($uploadedCvExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
@endphp

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">
            Existing CV File
            @if ($uploadedCvExists)
                <span class="badge bg-success-lt text-success border border-success ms-1" style="font-size:10px">File on file</span>
            @endif
        </strong>
        <div class="btn-group btn-group-sm">
            @if ($uploadedCvExists)
                <button type="button" class="btn btn-outline-info"
                        data-bs-toggle="modal" data-bs-target="#modal-uploaded-cv-preview"
                        data-cv-url="{{ $uploadedCvUrl }}"
                        data-cv-is-pdf="{{ $uploadedCvIsPdf ? '1' : '0' }}">
                    {!! $icon('eye', 'me-1') !!}Preview
                </button>
            @endif
            <button type="button" class="btn btn-outline-primary js-request-cv-upload" data-url="{{ route('job-board.auto-cv-bot.request-cv-upload', $session->id) }}">{!! $icon('message-plus', 'me-1') !!}Ask Again</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-upload-cv">{!! $icon('upload', 'me-1') !!}Upload It</button>
        </div>
    </div>
    <p class="small text-muted mb-0">
        @if ($uploadedCvExists)
            Candidate's uploaded CV is on file.
        @else
            Ask the candidate again if they have a CV to send (in case they missed it earlier), or upload one yourself if you already have it.
        @endif
    </p>
</div>

{{-- Uploaded CV Preview Modal --}}
<div class="modal fade" id="modal-uploaded-cv-preview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Uploaded CV File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="uploaded-cv-preview-body" style="min-height:500px">
                <div class="d-flex align-items-center justify-content-center h-100 text-muted p-4">Loading…</div>
            </div>
            <div class="modal-footer">
                <a href="{{ $uploadedCvUrl }}" target="_blank" class="btn btn-outline-secondary btn-sm">{!! $icon('external-link', 'me-1') !!}Open in new tab</a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('modal-uploaded-cv-preview');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (e) {
        var btn   = e.relatedTarget;
        var url   = btn ? btn.dataset.cvUrl   : null;
        var isPdf = btn ? btn.dataset.cvIsPdf === '1' : false;
        var body  = document.getElementById('uploaded-cv-preview-body');
        if (!body || !url) return;

        if (isPdf) {
            body.innerHTML = '<iframe src="' + url + '" style="width:100%;height:80vh;border:0"></iframe>';
        } else {
            body.innerHTML = '<div class="text-center p-3"><img src="' + url + '" style="max-width:100%;max-height:80vh;object-fit:contain" alt="Uploaded CV"></div>';
        }
    });
    modal.addEventListener('hidden.bs.modal', function () {
        var body = document.getElementById('uploaded-cv-preview-body');
        if (body) body.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted p-4">Loading…</div>';
    });
})();
</script>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Photo</strong>
        <div class="btn-group btn-group-sm">
            {!! $clearButton('photo') !!}
            <button type="button"
                class="btn btn-outline-secondary js-upload-cv-photo"
                data-save-url="{{ route('job-board.auto-cv-bot.save-cropped-photo', $session->id) }}"
                data-upload-input-id="cvPhotoUploadInput{{ $session->id }}">
                {!! $icon('upload', 'me-1') !!}Upload Photo
            </button>
            <button type="button" class="btn btn-outline-primary js-request-cv-photo" data-url="{{ route('job-board.auto-cv-bot.request-cv-photo', $session->id) }}">{!! $icon('message-plus', 'me-1') !!}Get Photo</button>
        </div>
    </div>
    <input type="file"
        class="d-none"
        accept="image/jpeg,image/png,image/webp"
        id="cvPhotoUploadInput{{ $session->id }}">
    @if ($session->candidate_photo_path)
        <div class="d-flex align-items-start gap-3 mt-1">
            <img src="{{ route('job-board.auto-cv-bot.photo', $session->id) }}"
                 alt="Candidate photo"
                 class="rounded border"
                 style="width:72px;height:72px;object-fit:cover;cursor:pointer"
                 id="cvPhotoThumb">
            <div class="d-flex flex-column gap-1">
                <span class="text-success small">{!! $icon('check', 'me-1') !!}Photo received</span>
                <button type="button"
                    class="btn btn-sm btn-outline-primary js-open-crop-photo"
                    data-photo-url="{{ route('job-board.auto-cv-bot.photo', $session->id) }}"
                    data-save-url="{{ route('job-board.auto-cv-bot.save-cropped-photo', $session->id) }}"
                    style="font-size:11px;padding:2px 8px">
                    ✂ Crop photo
                </button>
                <span class="text-muted small">You can also upload your own image and crop it here.</span>
            </div>
        </div>
    @else
        <p class="small mb-0">{!! $notProvided !!}</p>
        <p class="small text-muted mt-1 mb-0">Upload an image yourself, then crop and save it into this CV session.</p>
    @endif
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
            @php
                $qualification = $row['qualification'] ?? '';
                $field = $row['field'] ?? '';
                $institution = $row['institution'] ?? '';
                $years = trim(implode(' - ', array_filter([$row['start_year'] ?? '', $row['end_year'] ?? ''])));
            @endphp
            <div class="small mb-1">
                <strong>
                    {!! $editable("education.$i.qualification", $qualification, 'Qualification') !!}
                    @if ($field !== '')
                        , {!! $editable("education.$i.field", $field, 'Field of study') !!}
                    @endif
                    — {!! $editable("education.$i.institution", $institution, 'Institution') !!}
                </strong>
                @if ($years !== '')
                    <div class="text-muted">
                        {!! $editable("education.$i.start_year", $row['start_year'] ?? '', 'Start year') !!}
                        @if (($row['start_year'] ?? '') !== '' || ($row['end_year'] ?? '') !== '')
                            -
                        @endif
                        {!! $editable("education.$i.end_year", $row['end_year'] ?? '', 'End year') !!}
                    </div>
                @endif
            </div>
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
            {!! $addButton('experience', 'Work Experience') !!}
            {!! $clearButton('experience') !!}
            {!! $sectionButton(6) !!}
        </div>
    </div>
    @if ($experience->isNotEmpty())
        <div class="cv-sortable-list" data-section="experience" data-reorder-url="{{ route('job-board.auto-cv-bot.reorder-cv-section', $session->id) }}">
        @foreach ($experience as $i => $row)
            @php $responsibilities = array_values((array) ($row['responsibilities'] ?? [])); @endphp
            <div class="border rounded p-2 mb-2 cv-sortable-item" data-index="{{ $i }}">
                <div class="small mb-1 d-flex align-items-start gap-2">
                    <span class="cv-drag-handle text-muted flex-shrink-0" title="Drag to reorder" style="cursor:grab;font-size:16px;line-height:1.4;user-select:none">⠿</span>
                    <div class="flex-grow-1">
                        <strong>{!! $editable("experience.$i.job_title", $row['job_title'] ?? '', 'Job title') !!} — {!! $editable("experience.$i.company", $row['company'] ?? '', 'Company') !!}</strong>
                        ({!! $editable("experience.$i.start_date", $row['start_date'] ?? '', 'Start date') !!} to {!! $editable("experience.$i.end_date", $row['end_date'] ?? '', 'End date') !!})
                    </div>
                </div>
                @if ($responsibilities)
                    <ul class="mb-1 ps-3" style="list-style:disc">
                        @foreach ($responsibilities as $j => $resp)
                            <li class="d-flex align-items-start gap-1 small" style="list-style:none;padding-left:0">
                                <span style="margin-top:2px;flex-shrink:0">•</span>
                                <span class="flex-grow-1">{!! $editable("experience.$i.responsibilities.$j", $resp, 'Responsibility') !!}</span>
                                <button type="button"
                                    class="btn btn-link p-0 ms-1 text-danger js-remove-cv-array-item flex-shrink-0"
                                    style="font-size:13px;line-height:1"
                                    data-path="experience.{{ $i }}.responsibilities"
                                    data-index="{{ $j }}"
                                    data-url="{{ route('job-board.auto-cv-bot.remove-cv-array-item', $session->id) }}"
                                    title="Remove">×</button>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="d-flex align-items-center gap-1 mt-1">
                    <button type="button"
                        class="btn btn-outline-success js-add-responsibility"
                        style="font-size:10px;padding:1px 8px"
                        data-exp-index="{{ $i }}"
                        data-next-index="{{ count($responsibilities) }}"
                        data-url="{{ route('job-board.auto-cv-bot.update-cv-field', $session->id) }}">
                        + Add responsibility
                    </button>
                    <button type="button"
                        class="btn btn-outline-primary js-request-section-info"
                        style="font-size:10px;padding:1px 8px"
                        data-topic-number="6"
                        data-topic-label="Responsibilities — {{ e($row['job_title'] ?? 'this role') }} at {{ e($row['company'] ?? 'this company') }}"
                        data-exact-question="What were your main responsibilities as {{ e($row['job_title'] ?? 'employee') }} at {{ e($row['company'] ?? 'the company') }}? Please list what you did day to day — even simple things like handling customers, filing, managing stock, or reporting to a manager."
                        data-url="{{ $requestUrl }}">
                        Ask responsibilities
                    </button>
                </div>
            </div>
        @endforeach
        </div>
    @else
        <p class="small mb-0">{!! $notProvided !!}</p>
    @endif
</div>

@php
    $projectSubTypes = [
        'internship' => [
            'label'    => 'Internship / Attachment',
            'q'        => 'Did you do any internship or attachment at a company? For example: 6 months at Bankers Den, or 3 months at a hospital. If not, just say no.',
            'keywords' => ['internship', 'attachment'],
        ],
        'volunteer' => [
            'label'    => 'Volunteer work',
            'q'        => 'Did you do any volunteer work? For example: helping at a church, school, or community event. If not, just say no.',
            'keywords' => ['volunteer'],
        ],
        'project' => [
            'label'    => 'Personal / school project',
            'q'        => "Did you work on any personal or school project? For example: a business plan, a website, or something you built or helped with. If not, just say no.",
            'keywords' => ['personal project', 'school project', 'project'],
        ],
        'github' => [
            'label'    => 'GitHub / Portfolio link',
            'q'        => "Do you have a GitHub profile or a link to any work you've done online? For example: github.com/yourname or a website. If not, just say no.",
            'keywords' => ['github', 'portfolio', 'online link'],
        ],
    ];

    $askedSubTypes = [];
    foreach ($session->answers ?: [] as $turn) {
        $q = strtolower((string) ($turn['question_sent'] ?? ''));
        foreach ($projectSubTypes as $type => $meta) {
            if (isset($askedSubTypes[$type])) continue;
            foreach ($meta['keywords'] as $kw) {
                if (str_contains($q, $kw)) { $askedSubTypes[$type] = true; break; }
            }
        }
    }
@endphp

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Projects / Volunteer Work{!! $scoreBadge(7) !!}</strong>
        <div class="btn-group btn-group-sm">
            {!! $addButton('projects', 'Project / Volunteer Work') !!}
            {!! $clearButton('projects') !!}
            {!! $sectionButton(7, 'Get Projects Info') !!}
        </div>
    </div>

    {{-- Existing captured entries --}}
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

    {{-- Sub-type breakdown with Ask buttons for anything not yet asked --}}
    <div class="mt-2 border-top pt-2">
        @foreach ($projectSubTypes as $type => $meta)
            @php $asked = isset($askedSubTypes[$type]); @endphp
            <div class="d-flex align-items-center gap-2 py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                <span class="badge {{ $asked ? 'bg-success-lt text-success border border-success' : 'bg-danger-lt text-danger border border-danger' }} flex-shrink-0" style="font-size:10px;min-width:110px">
                    {{ $meta['label'] }}
                </span>
                <span class="small text-muted flex-grow-1" style="font-size:11px">{{ $asked ? '✓ Asked' : '✗ Not asked yet' }}</span>
                @if (!$asked)
                    <button type="button"
                            class="btn btn-xs btn-outline-primary js-request-section-info flex-shrink-0"
                            style="font-size:10px;padding:1px 7px"
                            data-topic-number="7"
                            data-topic-label="{{ $meta['label'] }}"
                            data-exact-question="{{ $meta['q'] }}"
                            data-url="{{ route('job-board.auto-cv-bot.request-section-information', $session->id) }}">
                        {!! $icon('message-plus', 'me-1') !!}Ask
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</div>

<div class="cv-section">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <strong class="small">Skills{!! $scoreBadge(8) !!}</strong>
        <div class="btn-group btn-group-sm">
            {!! $addButton('skills', 'Skill') !!}
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
            {!! $addButton('languages', 'Language') !!}
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
