@php
    use Botble\JobBoard\Services\AutoCvBotService;
    $defaultTopics = AutoCvBotService::topics();
    $topics = $session->topics ?: $defaultTopics;
    $sectionScores = $session->section_scores ?: [];
    $answers = $session->answers ?: [];
    $customQuestions = $session->custom_questions ?: [];
    $aiCalls = $session->ai_calls ?: [];
    $cv = $session->structured_cv ?: [];

    $defaultQuestions = [
        1  => 'What is your full name as it should appear on your CV?',
        2  => '(auto — asks each contact field one at a time)',
        3  => 'What job title would you like on your CV? For example: Sales Assistant, Cashier, or Receptionist.',
        4  => 'Can you write 2 or 3 short sentences about yourself as a worker?',
        5  => 'What is the name of your school, college, or university?',
        6  => 'Do you have any other work experience to add? If not, just say no.',
        7  => '(auto — asks each type one at a time)',
        8  => 'What skills or tools are you good at? For example: customer service, Microsoft Word, cashier work, or tailoring.',
        9  => 'Do you have any certificates, training, licences, or awards? If not, just say no.',
        10 => 'Which languages do you speak? You can answer like this: English - good, Bemba - good.',
        11 => 'Do you want your CV to say: Available on request? If yes, reply with those exact words.',
        12 => 'Is there anything else important to add? For example: availability, preferred work area, or achievements. If not, just say no.',
    ];

    // Contact sub-fields with their questions
    $contactFields = [
        'phone'          => ['label' => 'Mobile number',   'q' => "What's the best phone number to reach you on?"],
        'email'          => ['label' => 'Email address',   'q' => "What's the best email address to reach you on?\nIf you don't have one, just say no."],
        'location'       => ['label' => 'Town / City',     'q' => "Which town or city do you live in?"],
        'address'        => ['label' => 'Residential area','q' => "Do you want to add your residential area?\nFor example: Chalala, Libala, or Matero.\nIf not, just say no."],
        'age'            => ['label' => 'Age',             'q' => "How old are you, if you don't mind sharing?\nIf you'd rather skip it, just say no."],
        'marital_status' => ['label' => 'Marital status',  'q' => "Would you like to include your marital status?\nIf not, just say no."],
        'linkedin'       => ['label' => 'LinkedIn',        'q' => "Do you have a LinkedIn profile?\nIf not, just say no."],
    ];

    // Project sub-types with their questions
    $projectTypes = [
        'internship' => ['label' => 'Internship / Attachment', 'q' => "Did you do any internship or attachment at a company?\nFor example: 6 months at Bankers Den, or 3 months at a hospital.\nIf not, just say no.",     'keywords' => ['internship', 'attachment']],
        'volunteer'  => ['label' => 'Volunteer work',          'q' => "Did you do any volunteer work?\nFor example: helping at a church, school, or community event.\nIf not, just say no.",                              'keywords' => ['volunteer']],
        'project'    => ['label' => 'Personal / school project','q' => "Did you work on any personal or school project?\nFor example: a business plan, a website, or something you built or helped with.\nIf not, just say no.", 'keywords' => ['project', 'personal project']],
        'github'     => ['label' => 'GitHub / Portfolio link', 'q' => "Do you have a GitHub profile or a link to any work you've done online?\nFor example: github.com/yourname or a website.\nIf not, just say no.",     'keywords' => ['github', 'portfolio', 'online link']],
    ];

    // Resolve contact field status
    $resolvedContact = [];
    foreach ($contactFields as $field => $meta) {
        $filled = trim((string) ($cv[$field] ?? '')) !== '';
        $declined = false;
        if (!$filled && in_array($field, ['email', 'address', 'age', 'marital_status', 'linkedin'])) {
            foreach ($answers as $turn) {
                $q = strtolower((string) ($turn['question_sent'] ?? ''));
                $r = strtolower(trim((string) ($turn['reply'] ?? '')));
                $fieldInQ = match ($field) {
                    'email'          => str_contains($q, 'email'),
                    'address'        => str_contains($q, 'residential area') || str_contains($q, 'area as well'),
                    'age'            => str_contains($q, 'how old') || str_contains($q, 'age'),
                    'marital_status' => str_contains($q, 'marital status'),
                    'linkedin'       => str_contains($q, 'linkedin'),
                    default          => false,
                };
                if ($fieldInQ && in_array($r, ['no', 'none', 'skip', 'n/a', 'na', 'no email', 'no linkedin', 'rather not', 'prefer not'], true)) {
                    $declined = true;
                    break;
                }
            }
        }
        $resolvedContact[$field] = $filled ? 'filled' : ($declined ? 'declined' : 'missing');
    }

    // Resolve project type status
    $resolvedProject = [];
    foreach ($projectTypes as $type => $meta) {
        $asked = false;
        foreach ($answers as $turn) {
            $q = strtolower((string) ($turn['question_sent'] ?? ''));
            foreach ($meta['keywords'] as $kw) {
                if (str_contains($q, $kw)) { $asked = true; break 2; }
            }
        }
        $resolvedProject[$type] = $asked ? 'asked' : 'pending';
    }

    // Map each answer turn to a topic number
    $turnTopicMap = [];
    foreach ($answers as $i => $turn) {
        $q = strtolower(trim((string) ($turn['question_sent'] ?? '')));
        if      (str_contains($q, 'reference') || str_contains($q, 'available on request'))               $turnTopicMap[$i] = 11;
        elseif  (str_contains($q, 'internship') || str_contains($q, 'volunteer') || str_contains($q, 'project') || str_contains($q, 'attachment')) $turnTopicMap[$i] = 7;
        elseif  (str_contains($q, 'certificate') || str_contains($q, 'licence') || str_contains($q, 'training') || str_contains($q, 'award'))       $turnTopicMap[$i] = 9;
        elseif  (str_contains($q, 'language'))                                                             $turnTopicMap[$i] = 10;
        elseif  (str_contains($q, 'skill'))                                                                $turnTopicMap[$i] = 8;
        elseif  (str_contains($q, 'experience') || str_contains($q, 'employer') || str_contains($q, 'company') || str_contains($q, 'responsibilities')) $turnTopicMap[$i] = 6;
        elseif  (str_contains($q, 'school') || str_contains($q, 'college') || str_contains($q, 'university') || str_contains($q, 'qualification') || str_contains($q, 'diploma')) $turnTopicMap[$i] = 5;
        elseif  (str_contains($q, 'sentences') || str_contains($q, 'describe you') || str_contains($q, 'profile') || str_contains($q, 'worker'))  $turnTopicMap[$i] = 4;
        elseif  (str_contains($q, 'job title') || str_contains($q, 'type of work') || str_contains($q, 'receptionist') || str_contains($q, 'role you')) $turnTopicMap[$i] = 3;
        elseif  (str_contains($q, 'mobile') || str_contains($q, 'phone') || str_contains($q, 'email') || str_contains($q, 'town') || str_contains($q, 'city') || str_contains($q, 'how old') || str_contains($q, 'marital') || str_contains($q, 'linkedin') || str_contains($q, 'residential')) $turnTopicMap[$i] = 2;
        elseif  (str_contains($q, 'full name') || str_contains($q, 'your name'))                          $turnTopicMap[$i] = 1;
        elseif  (str_contains($q, 'additional') || str_contains($q, 'anything else') || str_contains($q, 'achievement') || str_contains($q, 'availability')) $turnTopicMap[$i] = 12;
    }

    $turnsPerTopic = [];
    foreach ($turnTopicMap as $topicNum) {
        $turnsPerTopic[$topicNum] = ($turnsPerTopic[$topicNum] ?? 0) + 1;
    }

    $requestSectionUrl = route('job-board.auto-cv-bot.request-section-information', $session->id);
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted small mb-0">
        Drag rows to reorder topics. Edit the description or override the fallback question for any topic. Changes affect this session only.
    </p>
    <button type="button" class="btn btn-sm btn-primary" id="btnSaveTopics">
        <x-core::icon name="ti ti-device-floppy" class="me-1" /> Save Changes
    </button>
</div>

{{-- AI trigger legend --}}
<div class="alert alert-light border small mb-3 p-2">
    <strong class="d-block mb-1">How the bot works</strong>
    <div class="d-flex flex-wrap gap-2">
        <span class="badge bg-primary-lt text-primary border border-primary">🤖 AI scored</span> Each reply goes to OpenAI — it scores every section, extracts CV data, and picks the next question.
        <span class="badge bg-warning-lt text-warning border border-warning">⚡ Enforced</span> Topics 2 and 7 have system-enforced one-at-a-time questions that override the AI when needed.
        <span class="badge bg-info-lt text-info border border-info">🔁 Repeat guard</span> If AI repeats an already-answered question, a correction prompt fires.
        <span class="badge bg-danger-lt text-danger border border-danger">🛑 Hard stop</span> After 35 turns the session auto-completes regardless.
    </div>
</div>

<div id="topicsContainer">
    @foreach ($topics as $index => $topicDescription)
        @php
            $topicNum    = $index + 1;
            $score       = (int) (($sectionScores[(string) $topicNum]['score'] ?? null) ?? 0);
            $improve     = trim((string) (($sectionScores[(string) $topicNum]['improve'] ?? null) ?? ''));
            $scoreColor  = $score >= 90 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
            $turnCount   = $turnsPerTopic[$topicNum] ?? 0;
            $customQ     = trim((string) ($customQuestions[(string) $topicNum] ?? ''));
            $maxTurns    = (int) ($customQuestions[$topicNum . '_max_turns'] ?? 0);
            $defaultQ    = $defaultQuestions[$topicNum] ?? '';
            $isContact   = $topicNum === 2;
            $isProject   = $topicNum === 7;
            $hitMaxTurns = $maxTurns > 0 && $turnCount >= $maxTurns;
        @endphp

        <div class="bot-logic-row border rounded mb-2" data-topic-index="{{ $index }}">

            {{-- Main topic header row --}}
            <div class="d-flex align-items-start gap-2 p-2">
                <div class="drag-handle text-muted mt-1 flex-shrink-0" style="cursor:grab;font-size:18px" title="Drag to reorder">⠿</div>
                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <span class="badge bg-secondary text-white">#{{ $topicNum }}</span>
                        <span class="badge bg-{{ $scoreColor }} text-white">{{ $score }}/100</span>
                        @if ($turnCount > 0)
                            <span class="badge {{ $hitMaxTurns ? 'bg-secondary' : 'bg-light text-dark border' }}">
                                {{ $turnCount }} turn{{ $turnCount === 1 ? '' : 's' }}{{ $hitMaxTurns ? ' — capped' : '' }}
                            </span>
                        @endif
                        @if ($isContact || $isProject)
                            <span class="badge bg-warning-lt text-warning border border-warning small">⚡ Enforced one-at-a-time</span>
                        @endif
                        @if ($score >= 90 || $hitMaxTurns)
                            <span class="badge bg-success-lt text-success border border-success small">✓ Done</span>
                        @elseif ($improve !== '')
                            <span class="text-muted small fst-italic">{{ $improve }}</span>
                        @endif
                        <label class="d-flex align-items-center gap-1 ms-auto text-muted small" title="Bot will not return to this topic after this many turns (0 = no limit)">
                            <span>Max turns</span>
                            <input type="number" min="0" max="20" step="1"
                                   class="form-control form-control-sm max-turns-input"
                                   style="width:54px"
                                   value="{{ $maxTurns ?: '' }}"
                                   placeholder="∞"
                                   data-topic-num="{{ $topicNum }}">
                        </label>
                    </div>
                    <input type="text"
                           class="form-control form-control-sm mb-1 topic-description-input"
                           value="{{ $topicDescription }}"
                           placeholder="Topic description (used by AI for scoring context)"
                           data-topic-index="{{ $index }}">
                    @if (!$isContact && !$isProject)
                        <textarea
                            class="form-control form-control-sm custom-question-input"
                            rows="2"
                            placeholder="{{ $defaultQ !== '' ? 'Default: ' . $defaultQ : 'Custom fallback question (leave blank to use default)' }}"
                            data-topic-num="{{ $topicNum }}"
                        >{{ $customQ }}</textarea>
                    @else
                        <p class="text-muted small mb-0 fst-italic">Questions are managed in the sub-fields below.</p>
                        <input type="hidden" class="custom-question-input" value="" data-topic-num="{{ $topicNum }}">
                    @endif
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-topic flex-shrink-0 mt-1" title="Remove topic">
                    <x-core::icon name="ti ti-trash" />
                </button>
            </div>

            {{-- Contact sub-fields (topic 2 only) --}}
            @if ($isContact)
                <div class="border-top mx-2 mb-2 pt-2">
                    <div class="small text-muted fw-semibold mb-1 ps-1">Sub-fields — asked one at a time in this order:</div>
                    @foreach ($contactFields as $field => $meta)
                        @php
                            $status      = $resolvedContact[$field];
                            $statusColor = $status === 'filled' ? 'success' : ($status === 'declined' ? 'secondary' : 'danger');
                            $statusLabel = $status === 'filled' ? '✓ ' . trim((string)($cv[$field])) : ($status === 'declined' ? '— declined' : '✗ missing');
                        @endphp
                        <div class="d-flex align-items-start gap-2 py-1 px-1 rounded {{ !$loop->last ? 'border-bottom' : '' }}">
                            <span class="badge bg-{{ $statusColor }}-lt text-{{ $statusColor }} border border-{{ $statusColor }} mt-1 flex-shrink-0" style="min-width:80px;font-size:10px">
                                {{ $meta['label'] }}
                            </span>
                            <div class="flex-grow-1 min-width-0">
                                <div class="small {{ $status === 'missing' ? 'text-danger' : 'text-muted' }}">
                                    {{ $statusLabel }}
                                </div>
                                <div class="text-muted" style="font-size:11px">{{ $meta['q'] }}</div>
                            </div>
                            @if ($status === 'missing')
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary js-request-section-info flex-shrink-0"
                                        style="font-size:11px;padding:2px 8px"
                                        data-topic-number="2"
                                        data-topic-label="{{ $meta['label'] ?? $fieldKey }}"
                                        data-exact-question="{{ $meta['q'] }}"
                                        data-url="{{ $requestSectionUrl }}">Ask</button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Project sub-types (topic 7 only) --}}
            @if ($isProject)
                <div class="border-top mx-2 mb-2 pt-2">
                    <div class="small text-muted fw-semibold mb-1 ps-1">Sub-types — asked one at a time in this order:</div>
                    @foreach ($projectTypes as $type => $meta)
                        @php
                            $status      = $resolvedProject[$type];
                            $statusColor = $status === 'asked' ? 'success' : 'danger';
                            $statusLabel = $status === 'asked' ? '✓ Asked' : '✗ Not asked yet';
                        @endphp
                        <div class="d-flex align-items-start gap-2 py-1 px-1 rounded {{ !$loop->last ? 'border-bottom' : '' }}">
                            <span class="badge bg-{{ $statusColor }}-lt text-{{ $statusColor }} border border-{{ $statusColor }} mt-1 flex-shrink-0" style="min-width:110px;font-size:10px">
                                {{ $meta['label'] }}
                            </span>
                            <div class="flex-grow-1 min-width-0">
                                <div class="small {{ $status === 'pending' ? 'text-danger' : 'text-muted' }}">{{ $statusLabel }}</div>
                                <div class="text-muted" style="font-size:11px">{{ str_replace("\n", ' ', $meta['q']) }}</div>
                            </div>
                            @if ($status === 'pending')
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary js-request-section-info flex-shrink-0"
                                        style="font-size:11px;padding:2px 8px"
                                        data-topic-number="7"
                                        data-topic-label="{{ $meta['label'] }}"
                                        data-exact-question="{{ str_replace("\n", ' ', $meta['q']) }}"
                                        data-url="{{ $requestSectionUrl }}">Ask</button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    @endforeach
</div>

<button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btnAddTopic">
    <x-core::icon name="ti ti-plus" class="me-1" /> Add Topic
</button>

@if (count($aiCalls) > 0)
    <hr class="my-3">
    <strong class="small d-block mb-2">AI Call Log ({{ count($aiCalls) }} calls)</strong>
    <div class="table-responsive">
        <table class="table table-sm table-bordered small mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Model</th><th>Prompt tokens</th><th>Completion tokens</th><th>Cost (USD)</th><th>At</th></tr>
            </thead>
            <tbody>
                @foreach ($aiCalls as $i => $call)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="text-muted">{{ $call['model'] ?? '—' }}</td>
                        <td>{{ number_format($call['prompt_tokens'] ?? 0) }}</td>
                        <td>{{ number_format($call['completion_tokens'] ?? 0) }}</td>
                        <td>${{ number_format((float) ($call['cost_usd'] ?? 0), 4) }}</td>
                        <td class="text-muted">{{ $call['at'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<style>
.bot-logic-row.drag-over { border-color: #0d6efd !important; background: rgba(13,110,253,.04); }
.bot-logic-row.dragging { opacity: .4; }
</style>

<script>
(function () {
    var saveUrl   = '{{ route('job-board.auto-cv-bot.update-topics', $session->id) }}';
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var container = document.getElementById('topicsContainer');
    var defaultQuestions = @json($defaultQuestions);
    var draggingEl = null;

    // Drag-to-reorder
    container.addEventListener('dragstart', function (e) {
        var row = e.target.closest('.bot-logic-row');
        if (!row) return;
        draggingEl = row;
        row.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    container.addEventListener('dragend', function () {
        if (draggingEl) draggingEl.classList.remove('dragging');
        draggingEl = null;
        document.querySelectorAll('.bot-logic-row').forEach(function (r) { r.classList.remove('drag-over'); });
    });
    container.addEventListener('dragover', function (e) {
        e.preventDefault();
        var row = e.target.closest('.bot-logic-row');
        if (!row || row === draggingEl) return;
        document.querySelectorAll('.bot-logic-row').forEach(function (r) { r.classList.remove('drag-over'); });
        row.classList.add('drag-over');
        var after = e.clientY > row.getBoundingClientRect().top + row.getBoundingClientRect().height / 2;
        container.insertBefore(draggingEl, after ? row.nextSibling : row);
    });
    container.addEventListener('mousedown', function (e) {
        var handle = e.target.closest('.drag-handle');
        if (!handle) return;
        var row = handle.closest('.bot-logic-row');
        if (row) row.draggable = true;
    });
    container.addEventListener('mouseup', function () {
        document.querySelectorAll('.bot-logic-row[draggable]').forEach(function (r) { r.draggable = false; });
    });

    // Remove topic
    container.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-remove-topic');
        if (!btn) return;
        var row = btn.closest('.bot-logic-row');
        if (row && container.querySelectorAll('.bot-logic-row').length > 1) {
            row.remove();
            renumberRows();
        } else {
            Botble.showError('You must have at least one topic.');
        }
    });

    function renumberRows() {
        container.querySelectorAll('.bot-logic-row').forEach(function (row, i) {
            row.dataset.topicIndex = i;
            var badge = row.querySelector('.badge.bg-secondary');
            if (badge) badge.textContent = '#' + (i + 1);
            var qInput = row.querySelector('.custom-question-input');
            if (qInput) {
                qInput.dataset.topicNum = i + 1;
                var defQ = defaultQuestions[i + 1];
                if (defQ && qInput.tagName === 'TEXTAREA') qInput.placeholder = 'Default: ' + defQ;
            }
        });
    }

    // Add topic
    document.getElementById('btnAddTopic').addEventListener('click', function () {
        var existingCount = container.querySelectorAll('.bot-logic-row').length;
        var newNum = existingCount + 1;
        var html = '<div class="bot-logic-row border rounded mb-2 p-2" data-topic-index="' + existingCount + '">'
            + '<div class="d-flex align-items-start gap-2">'
            + '<div class="drag-handle text-muted mt-1 flex-shrink-0" style="cursor:grab;font-size:18px">⠿</div>'
            + '<div class="flex-grow-1 min-width-0">'
            + '<div class="d-flex align-items-center gap-2 mb-1">'
            + '<span class="badge bg-secondary text-white">#' + newNum + '</span>'
            + '<span class="badge bg-danger text-white">0/100</span>'
            + '</div>'
            + '<input type="text" class="form-control form-control-sm mb-1 topic-description-input" placeholder="Describe this topic" data-topic-index="' + existingCount + '">'
            + '<textarea class="form-control form-control-sm custom-question-input" rows="2" placeholder="Question to ask on WhatsApp" data-topic-num="' + newNum + '"></textarea>'
            + '</div>'
            + '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-topic flex-shrink-0 mt-1" title="Remove topic">'
            + '<svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7l16 0"></path><path d="M10 11l0 6"></path><path d="M14 11l0 6"></path><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path></svg>'
            + '</button>'
            + '</div></div>';
        container.insertAdjacentHTML('beforeend', html);
    });

    // Save
    document.getElementById('btnSaveTopics').addEventListener('click', function () {
        var btn = this;
        var orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving';

        var topics = [], customQuestions = {};
        container.querySelectorAll('.bot-logic-row').forEach(function (row, i) {
            var topicNum = i + 1;
            var desc = (row.querySelector('.topic-description-input')?.value || '').trim();
            topics.push(desc);
            var q = (row.querySelector('.custom-question-input')?.value || '').trim();
            if (q) customQuestions[topicNum] = q;
            var maxTurnsEl = row.querySelector('.max-turns-input');
            var maxTurns = maxTurnsEl ? parseInt(maxTurnsEl.value, 10) : 0;
            if (maxTurns > 0) customQuestions[topicNum + '_max_turns'] = maxTurns;
        });

        fetch(saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ topics: topics, custom_questions: customQuestions }),
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (result) {
                btn.disabled = false; btn.innerHTML = orig;
                if (!result.ok) { Botble.showError(result.data.error || 'Failed to save.'); return; }
                Botble.showSuccess(result.data.message || 'Saved.');
                renumberRows();
            })
            .catch(function () { btn.disabled = false; btn.innerHTML = orig; Botble.showError('Network error.'); });
    });
})();
</script>
