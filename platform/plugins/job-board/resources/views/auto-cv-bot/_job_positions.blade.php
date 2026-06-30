@php
    use Botble\JobBoard\Models\Account;
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

    $linkedAccount = $session->linked_account_id ? Account::query()->find($session->linked_account_id) : null;
    $linkAccountUrl = route('job-board.auto-cv-bot.link-account', $session->id);
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

{{-- Candidate account linking --}}
<div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
    @if ($linkedAccount)
        <span class="badge bg-success-lt text-success border border-success px-2 py-1">
            <x-core::icon name="ti ti-link" class="me-1" />
            Linked: <a href="{{ route('accounts.edit', $linkedAccount->id) }}" class="text-success fw-semibold ms-1" target="_blank">{{ $linkedAccount->name ?? $linkedAccount->email ?? '#' . $linkedAccount->id }}</a>
        </span>
        <button type="button" class="btn btn-sm btn-outline-secondary js-link-account-btn" data-action="unlink" data-url="{{ $linkAccountUrl }}">
            <x-core::icon name="ti ti-unlink" class="me-1" /> Unlink
        </button>
    @else
        <span class="text-muted small">Not linked to a candidate account.</span>
        <button type="button" class="btn btn-sm btn-outline-success js-link-account-btn" data-action="create" data-url="{{ $linkAccountUrl }}">
            <x-core::icon name="ti ti-user-plus" class="me-1" /> Create &amp; Link Account
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnShowAccountSearch">
            <x-core::icon name="ti ti-search" class="me-1" /> Search &amp; Link
        </button>
    @endif
</div>
@unless ($linkedAccount)
<div class="d-none mb-3" id="accountSearchBox">
    <div class="input-group input-group-sm mb-1">
        <input type="text" class="form-control" id="accountSearchInput" placeholder="Search by name, phone, or email…" autocomplete="off">
        <button type="button" class="btn btn-outline-secondary" id="accountSearchClearBtn" style="display:none">✕</button>
    </div>
    <div id="accountSearchResults" class="border rounded bg-white" style="display:none;max-height:260px;overflow-y:auto"></div>
</div>
<script>
(function () {
    var searchUrl = '{{ route('job-board.candidate-alerts.search-accounts') }}';
    var linkUrl   = '{{ $linkAccountUrl }}';
    var token     = document.querySelector('meta[name="csrf-token"]')?.content || '';

    var showBtn   = document.getElementById('btnShowAccountSearch');
    var box       = document.getElementById('accountSearchBox');
    var input     = document.getElementById('accountSearchInput');
    var clearBtn  = document.getElementById('accountSearchClearBtn');
    var results   = document.getElementById('accountSearchResults');
    var timer;

    if (!showBtn) return;

    showBtn.addEventListener('click', function () {
        box.classList.toggle('d-none');
        if (!box.classList.contains('d-none')) input.focus();
    });

    clearBtn.addEventListener('click', function () {
        input.value = '';
        results.style.display = 'none';
        clearBtn.style.display = 'none';
        input.focus();
    });

    input.addEventListener('input', function () {
        clearTimeout(timer);
        var q = input.value.trim();
        clearBtn.style.display = q ? '' : 'none';
        if (q.length < 2) { results.style.display = 'none'; return; }
        timer = setTimeout(function () { doSearch(q, 1); }, 300);
    });

    function doSearch(q, page) {
        results.innerHTML = '<div class="p-2 text-muted small">Searching…</div>';
        results.style.display = 'block';
        fetch(searchUrl + '?q=' + encodeURIComponent(q) + '&page=' + page, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (resp) { renderResults(resp.data || [], resp.has_more || false, q, page); })
            .catch(function () { results.innerHTML = '<div class="p-2 text-danger small">Search failed.</div>'; });
    }

    function renderResults(items, hasMore, q, page) {
        if (!items.length) {
            results.innerHTML = '<div class="p-2 text-muted small">No candidates found.</div>';
            return;
        }
        var html = '';
        items.forEach(function (acc) {
            html += '<div class="d-flex align-items-center gap-2 p-2 border-bottom" style="cursor:pointer" data-id="' + acc.id + '">';
            html += '<div class="flex-grow-1 min-width-0">';
            html += '<div class="fw-semibold small">' + (acc.name || '#' + acc.id) + '</div>';
            html += '<div class="text-muted" style="font-size:11px">' + [acc.email, acc.phone].filter(Boolean).join(' · ') + '</div>';
            html += '</div>';
            html += '<button type="button" class="btn btn-xs btn-outline-primary flex-shrink-0 js-link-this-account" data-id="' + acc.id + '" style="font-size:11px;padding:1px 8px">Link</button>';
            html += '</div>';
        });
        if (hasMore) {
            html += '<div class="p-2 text-center"><button type="button" class="btn btn-link btn-sm js-load-more-accounts" data-q="' + encodeURIComponent(q) + '" data-page="' + (page + 1) + '">Load more…</button></div>';
        }
        results.innerHTML = html;
    }

    results.addEventListener('click', function (e) {
        var linkBtn = e.target.closest('.js-link-this-account');
        var moreBtn = e.target.closest('.js-load-more-accounts');
        if (linkBtn) {
            var id = parseInt(linkBtn.dataset.id, 10);
            linkBtn.disabled = true;
            linkBtn.textContent = '…';
            fetch(linkUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify({ action: 'link', account_id: id })
            }).then(function (r) { return r.json(); }).then(function (resp) {
                if (resp.error) { Botble.showError(resp.error); linkBtn.disabled = false; linkBtn.textContent = 'Link'; return; }
                Botble.showSuccess(resp.message || 'Account linked.');
                document.getElementById('jobPositionsBody')?.dispatchEvent(new CustomEvent('cv-bot:refresh'));
                if (resp.hero_html) document.getElementById('jobPositionsBody').innerHTML = resp.hero_html;
            }).catch(function () { Botble.showError('Link failed.'); linkBtn.disabled = false; linkBtn.textContent = 'Link'; });
        }
        if (moreBtn) {
            var q = decodeURIComponent(moreBtn.dataset.q);
            var pg = parseInt(moreBtn.dataset.page, 10);
            moreBtn.textContent = 'Loading…';
            doSearch(q, pg);
        }
    });
})();
</script>
@endunless

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
