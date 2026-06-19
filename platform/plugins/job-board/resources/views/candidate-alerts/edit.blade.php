@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="row g-4">
    <div class="col-12">
        <x-core::card>
            <x-core::card.header>
                <h5 class="mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-bell-check text-primary"></i>
                    Edit Alert: {{ $alert->candidate_name }}
                </h5>
                <a href="{{ route('job-board.candidate-alerts.index') }}" class="btn btn-outline-secondary btn-sm ms-auto">
                    <i class="ti ti-arrow-left me-1"></i> Back to Alerts
                </a>
            </x-core::card.header>
            <x-core::card.body>
                @if(session('success_message'))
                    <div class="alert alert-success alert-dismissible mb-3">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        {{ session('success_message') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger mb-3">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('job-board.candidate-alerts.update', $alert->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('plugins/job-board::candidate-alerts._form', ['alert' => $alert, 'prefix' => 'edit'])
                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> Save Changes
                        </button>
                        <a href="{{ route('job-board.candidate-alerts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </x-core::card.body>
        </x-core::card>
    </div>
</div>
@endsection

@push('footer')
<script>
$(function () {
    function escHtml(str) {
        return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Phone check
    $(document).on('blur', '.phone-check-input', function () {
        const $input = $(this), phone = $input.val().trim(), checkUrl = $input.data('check-url'), excludeId = $input.data('exclude-id') || 0;
        const $warning = $input.closest('.col-md-6').find('.phone-check-warning');
        $warning.hide().html('');
        if (!phone) return;
        fetch(checkUrl + '?phone=' + encodeURIComponent(phone) + '&exclude_id=' + excludeId)
            .then(r => r.json())
            .then(resp => {
                if (!resp.exists) return;
                const alerts = resp.alerts || [];
                let html = '<div class="alert alert-warning py-2 px-3 mb-0 small"><i class="fas fa-exclamation-triangle me-1"></i><strong>This number already has ' + alerts.length + ' alert(s).</strong></div>';
                $warning.html(html).show();
            });
    });
    $(document).on('input', '.phone-check-input', function () { $(this).closest('.col-md-6').find('.phone-check-warning').hide(); });

    // Filter tab helpers
    $(document).on('click', '.btn-select-all-check', function () {
        const $box = $('#' + $(this).data('target')), badge = $(this).data('count-badge');
        $box.find('input[type="checkbox"]:not([disabled])').prop('checked', true); updateCountBadge(badge, $box);
    });
    $(document).on('click', '.btn-deselect-all-check', function () {
        const $box = $('#' + $(this).data('target')), badge = $(this).data('count-badge');
        $box.find('input[type="checkbox"]').prop('checked', false); updateCountBadge(badge, $box);
    });
    $(document).on('change', '.border.rounded input[type="checkbox"]', function () {
        const $box = $(this).closest('.border.rounded'), id = $box.attr('id');
        if (!id) return;
        $('[data-target="' + id + '"]').each(function () { const badge = $(this).data('count-badge'); if (badge) updateCountBadge(badge, $box); });
    });
    function updateCountBadge(badgeClass, $box) {
        const count = $box.find('input[type="checkbox"]:checked').length, total = $box.find('input[type="checkbox"]').length;
        $('.' + badgeClass).text(count + (total > 0 ? ' selected' : ''));
        $('[data-target="' + $box.attr('id') + '"].btn-deselect-all-check').filter('.btn-outline-danger').prop('disabled', count === 0);
    }
    $(document).on('input', '.filter-search', function () {
        const needle = $(this).val().toLowerCase(), $box = $('#' + $(this).data('target'));
        $box.find('.checkable-item').each(function () { $(this).toggle($(this).text().toLowerCase().includes(needle)); });
    });

    // Clear experience level
    $(document).on('click', '.btn-clear-experience', function () {
        $('#' + $(this).data('target')).val('');
    });

    // Clear all filters in the Filters tab
    $(document).on('click', '.btn-clear-all-filters', function () {
        const tid = $(this).data('tid');

        const $kwList = $('#keywords-list-' + tid);
        $kwList.find('.keyword-row').slice(1).remove();
        $kwList.find('input[name="filters[keywords][]"]').val('');
        $('.kw-count-badge-' + tid).text('0');

        const $coList = $('#company-list-' + tid);
        $coList.find('.company-kw-row').slice(1).remove();
        $coList.find('input[name="filters[company_keywords][]"]').val('');
        $('.co-count-badge-' + tid).text('0');

        $('#countries-box-' + tid + ' input[type="checkbox"]').prop('checked', false);
        $('.country-count-badge-' + tid).text('0 selected');

        $('#jobtypes-box-' + tid + ' input[type="checkbox"]').prop('checked', false);
        $('.jt-count-badge-' + tid).text('0 selected');

        $('#categories-box-' + tid + ' input[type="checkbox"]').prop('checked', false);
        $('.cat-count-badge-' + tid).text('0 selected');

        $('[data-target="jobtypes-box-' + tid + '"].btn-deselect-all-check.btn-outline-danger, [data-target="categories-box-' + tid + '"].btn-deselect-all-check.btn-outline-danger').prop('disabled', true);

        $('#tab-filters-' + tid + ' input[name="filters[location_keyword]"]').val('');
        $('#' + tid + '-job-experience-id').val('');

        Botble.showSuccess('All filters cleared.');
    });

    // Keyword rows
    $(document).on('click', '.btn-add-kw', function () {
        const listId = $(this).data('target'), badgeClass = $(this).data('count-badge');
        const $row = $('<div class="input-group input-group-sm mb-1 keyword-row"><input type="text" name="filters[keywords][]" class="form-control" placeholder="e.g. Accountant"><button type="button" class="btn btn-outline-danger btn-remove-kw" title="Remove"><i class="fas fa-times"></i></button></div>');
        $('#' + listId).append($row); $row.find('input').focus(); updateKwBadge(badgeClass, listId);
    });
    $(document).on('click', '.btn-remove-kw', function () {
        const $list = $(this).closest('[id^="keywords-list-"]'), listId = $list.attr('id');
        if ($list.find('.keyword-row').length > 1) $(this).closest('.keyword-row').remove();
        else $(this).closest('.keyword-row').find('input').val('');
        updateKwBadge($('[data-target="' + listId + '"].btn-add-kw').data('count-badge'), listId);
    });
    $(document).on('input', '[name="filters[keywords][]"]', function () {
        const $list = $(this).closest('[id^="keywords-list-"]'), listId = $list.attr('id');
        updateKwBadge($('[data-target="' + listId + '"].btn-add-kw').data('count-badge'), listId);
    });
    function updateKwBadge(badgeClass, listId) {
        if (!badgeClass) return;
        $('.' + badgeClass).text($('#' + listId).find('input').filter(function () { return $(this).val().trim() !== ''; }).length);
    }

    // Company keyword rows
    $(document).on('click', '.btn-add-company-kw', function () {
        const listId = $(this).data('target'), badgeClass = $(this).data('count-badge');
        const $row = $('<div class="input-group input-group-sm mb-1 company-kw-row"><input type="text" name="filters[company_keywords][]" class="form-control" placeholder="e.g. Company Name"><button type="button" class="btn btn-outline-danger btn-remove-company-kw" title="Remove"><i class="fas fa-times"></i></button></div>');
        $('#' + listId).append($row); $row.find('input').focus(); updateCoKwBadge(badgeClass, listId);
    });
    $(document).on('click', '.btn-remove-company-kw', function () {
        const $list = $(this).closest('[id^="company-list-"]'), listId = $list.attr('id');
        if ($list.find('.company-kw-row').length > 1) $(this).closest('.company-kw-row').remove();
        else $(this).closest('.company-kw-row').find('input').val('');
        updateCoKwBadge($('[data-target="' + listId + '"].btn-add-company-kw').data('count-badge'), listId);
    });
    $(document).on('input', '[name="filters[company_keywords][]"]', function () {
        const $list = $(this).closest('[id^="company-list-"]'), listId = $list.attr('id');
        updateCoKwBadge($('[data-target="' + listId + '"].btn-add-company-kw').data('count-badge'), listId);
    });
    function updateCoKwBadge(badgeClass, listId) {
        if (!badgeClass) return;
        $('.' + badgeClass).text($('#' + listId).find('input').filter(function () { return $(this).val().trim() !== ''; }).length);
    }

    // Collapse chevron
    $(document).on('click', '.collapse-toggle-btn', function () {
        const expanded = $(this).attr('aria-expanded') === 'true';
        $(this).find('.collapse-chevron').css('transform', expanded ? '' : 'rotate(180deg)');
    });

    // Candidate account search / link
    const candidateAccountSearchState = {};
    function ensureCandidateAccountState(prefix) {
        if (!candidateAccountSearchState[prefix]) candidateAccountSearchState[prefix] = { page: 1, timer: null, hasMore: false };
        return candidateAccountSearchState[prefix];
    }
    function renderLinkedAccountCvPrompt(prefix, account) {
        const $prompt = $('#candidate-account-cv-prompt-' + prefix);
        if (account.has_cv) {
            $prompt.html('<div class="alert alert-success py-2 px-3 mb-0 small"><div class="d-flex align-items-center justify-content-between gap-2 flex-wrap"><div><strong>Account CV found:</strong> ' + escHtml(account.resume_name || 'CV on file') + '<div class="text-muted mt-1">Use the linked account CV to auto-generate keywords and matching filters.</div></div><button type="button" class="btn btn-success btn-sm btn-analyze-linked-account-cv" data-prefix="' + prefix + '"><i class="ti ti-sparkles me-1"></i> Analyse Account CV</button></div></div>');
        } else {
            $prompt.html('<div class="alert alert-warning py-2 px-3 mb-0 small"><strong>No CV on this account.</strong> Upload a CV below to generate the best keywords and matching filters.</div>');
        }
    }
    function renderSelectedAccount(prefix, account) {
        const phone = account.phone || account.whatsapp_number || '';
        const avatar = account.avatar_url || '';
        const badge = account.wakanda_verified ? '<span class="d-inline-flex align-items-center justify-content-center rounded-circle ms-1" title="Wakanda Verified" style="width:16px;height:16px;background:#6f42c1;color:#fff;font-size:10px;line-height:1;"><i class="ti ti-star-filled"></i></span>' : '';
        $('#candidate-account-selected-' + prefix).removeClass('d-none').attr('data-account-id', account.id || '').attr('data-has-cv', account.has_cv ? '1' : '0').html('<div class="border rounded px-3 py-2 bg-white"><div class="d-flex align-items-start justify-content-between gap-2 flex-wrap"><div><div class="fw-semibold d-flex align-items-center gap-2">' + (avatar ? '<img src="' + escHtml(avatar) + '" alt="" style="width:24px;height:24px;border-radius:999px;object-fit:cover;border:1px solid #e5e7eb">' : '') + '<span>' + escHtml(account.name || '') + '</span>' + badge + '</div><div class="text-muted small">' + (account.email ? '<span class="me-2"><i class="ti ti-mail me-1"></i>' + escHtml(account.email) + '</span>' : '') + (phone ? '<span><i class="ti ti-brand-whatsapp me-1"></i>' + escHtml(phone) + '</span>' : '') + '</div></div><button type="button" class="btn btn-outline-danger btn-sm btn-clear-linked-account" data-prefix="' + prefix + '"><i class="ti ti-x me-1"></i> Clear</button></div></div>');
        $('input[name="linked_account_id"]').val(account.id || '');
        renderLinkedAccountCvPrompt(prefix, account);
    }
    function clearLinkedAccount(prefix) {
        $('#candidate-account-selected-' + prefix).addClass('d-none').attr('data-account-id', '').empty();
        $('#candidate-account-cv-prompt-' + prefix).empty();
        $('input[name="linked_account_id"]').val('');
    }
    function runCandidateAccountSearch(prefix, page) {
        const $input = $('#candidate-account-search-' + prefix), url = $input.data('search-url'), term = $input.val().trim(), state = ensureCandidateAccountState(prefix), $results = $('#candidate-account-results-' + prefix);
        state.page = page;
        if (term.length < 2) { $results.addClass('d-none').empty(); return; }
        $results.removeClass('d-none').html('<div class="border rounded p-3 bg-white text-muted small"><i class="ti ti-loader-2 fa-spin me-1"></i> Searching accounts…</div>');
        fetch(url + '?q=' + encodeURIComponent(term) + '&page=' + page)
            .then(r => r.json())
            .then(resp => {
                const rows = resp.data || []; state.hasMore = !!resp.has_more;
                if (!rows.length) { $results.html('<div class="border rounded p-3 bg-white text-muted small">No matching candidate accounts found.</div>'); return; }
                let html = '<div class="border rounded bg-white overflow-hidden"><div class="list-group list-group-flush">';
                rows.forEach(account => {
                    const phone = account.phone || account.whatsapp_number || '';
                    const avatar = account.avatar_url || '';
                    const badge = account.wakanda_verified ? '<span class="d-inline-flex align-items-center justify-content-center rounded-circle ms-1" title="Wakanda Verified" style="width:16px;height:16px;background:#6f42c1;color:#fff;font-size:10px;line-height:1;"><i class="ti ti-star-filled"></i></span>' : '';
                    html += '<button type="button" class="list-group-item list-group-item-action btn-select-candidate-account" data-prefix="' + prefix + '" data-account-id="' + escHtml(account.id) + '" data-account-name="' + escHtml(account.name || '') + '" data-account-email="' + escHtml(account.email || '') + '" data-account-phone="' + escHtml(phone) + '" data-has-cv="' + (account.has_cv ? '1' : '0') + '" data-resume-name="' + escHtml(account.resume_name || '') + '" data-avatar-url="' + escHtml(avatar) + '" data-wakanda-verified="' + (account.wakanda_verified ? '1' : '0') + '"><div class="d-flex align-items-start justify-content-between gap-2"><div><div class="fw-semibold d-flex align-items-center gap-2">' + (avatar ? '<img src="' + escHtml(avatar) + '" alt="" style="width:24px;height:24px;border-radius:999px;object-fit:cover;border:1px solid #e5e7eb">' : '') + '<span>' + escHtml(account.name || '') + '</span>' + badge + '</div><div class="text-muted small">' + (account.email ? '<span class="me-2"><i class="ti ti-mail me-1"></i>' + escHtml(account.email) + '</span>' : '') + (phone ? '<span><i class="ti ti-brand-whatsapp me-1"></i>' + escHtml(phone) + '</span>' : '') + '</div></div><span class="badge ' + (account.has_cv ? 'bg-success' : 'bg-warning text-dark') + '">' + (account.has_cv ? 'Has CV' : 'No CV') + '</span></div></button>';
                });
                html += '</div><div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light"><button type="button" class="btn btn-outline-secondary btn-sm btn-candidate-account-page" data-prefix="' + prefix + '" data-page="' + Math.max(1, page - 1) + '"' + (page <= 1 ? ' disabled' : '') + '>Prev</button><span class="text-muted small">Page ' + page + '</span><button type="button" class="btn btn-outline-secondary btn-sm btn-candidate-account-page" data-prefix="' + prefix + '" data-page="' + (page + 1) + '"' + (state.hasMore ? '' : ' disabled') + '>Next</button></div></div>';
                $results.html(html);
            })
            .catch(() => $results.html('<div class="border rounded p-3 bg-white text-danger small">Account search failed. Try again.</div>'));
    }
    $(document).on('input', '.candidate-account-search-input', function () {
        const prefix = $(this).data('prefix'), state = ensureCandidateAccountState(prefix);
        clearTimeout(state.timer);
        state.timer = setTimeout(() => runCandidateAccountSearch(prefix, 1), 250);
    });
    $(document).on('click', '.btn-candidate-account-page', function () {
        if ($(this).is(':disabled')) return;
        runCandidateAccountSearch($(this).data('prefix'), parseInt($(this).data('page'), 10) || 1);
    });
    $(document).on('click', '.btn-select-candidate-account', function () {
        const prefix = $(this).data('prefix');
        const account = { id: $(this).data('account-id'), name: $(this).data('account-name'), email: $(this).data('account-email'), phone: $(this).data('account-phone'), has_cv: String($(this).data('has-cv')) === '1', resume_name: $(this).data('resume-name'), avatar_url: $(this).data('avatar-url'), wakanda_verified: String($(this).data('wakanda-verified')) === '1' };
        $('input[name="candidate_name"]').val(account.name || '');
        $('input[name="candidate_phone"]').val(account.phone || '');
        $('input[name="candidate_email"]').val(account.email || '');
        renderSelectedAccount(prefix, account);
        $('#candidate-account-results-' + prefix).addClass('d-none').empty();
        Botble.showSuccess(account.has_cv ? 'Candidate account linked. Analyse the account CV to generate keywords.' : 'Candidate account linked. This account has no CV yet, so upload one below for accurate keyword matching.');
    });
    $(document).on('click', '.btn-clear-linked-account', function () { clearLinkedAccount($(this).data('prefix')); });

    // CV analysis
    $(document).on('change', '.cv-upload-input', function () {
        $('[data-prefix="' + $(this).data('prefix') + '"].btn-analyze-cv').prop('disabled', !(this.files && this.files.length > 0));
    });
    $(document).on('click', '.btn-analyze-cv', function () {
        const $btn = $(this), prefix = $btn.data('prefix'), $fileInput = $('#' + prefix + '-cv-file'), analyzeUrl = $fileInput.data('analyze-url');
        if (!$fileInput[0].files || !$fileInput[0].files.length) { Botble.showError('Please select a CV file first.'); return; }
        $btn.prop('disabled', true).html('<i class="ti ti-loader-2 fa-spin me-1"></i> Analysing…');
        const formData = new FormData();
        formData.append('cv_file', $fileInput[0].files[0]);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        fetch(analyzeUrl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(resp => { if (resp.error) { Botble.showError(resp.error); return; } applyAnalysis(prefix, resp.data); })
            .catch(() => Botble.showError('CV analysis failed.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Analyse with AI'));
    });
    $(document).on('click', '.btn-analyze-linked-account-cv', function () {
        const $btn = $(this), prefix = $btn.data('prefix'), accountId = parseInt($('#candidate-account-selected-' + prefix).attr('data-account-id'), 10) || 0, analyzeUrl = $('#candidate-account-search-' + prefix).data('analyze-account-cv-url');
        if (!accountId) { Botble.showError('Select an account first.'); return; }
        $btn.prop('disabled', true).html('<i class="ti ti-loader-2 fa-spin me-1"></i> Analysing…');
        fetch(analyzeUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }, body: JSON.stringify({ account_id: accountId }) })
            .then(r => r.json())
            .then(resp => { if (resp.error) { Botble.showError(resp.error); return; } applyAnalysis(prefix, resp.data); Botble.showSuccess('Linked account CV analysed and filters applied.'); })
            .catch(() => Botble.showError('Linked account CV analysis failed.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="ti ti-sparkles me-1"></i> Analyse Account CV'));
    });
    $(document).on('click', '.btn-apply-analysis', function () {
        const analysis = $(this).data('analysis');
        if (analysis) applyAnalysis($(this).data('prefix'), analysis);
    });
    function applyAnalysis(prefix, data) {
        if (data.candidate_name) $('input[name="candidate_name"]').val(data.candidate_name);
        if (data.candidate_phone) $('input[name="candidate_phone"]').val(data.candidate_phone);
        if (data.candidate_email) $('input[name="candidate_email"]').val(data.candidate_email);

        const keywords = Array.isArray(data.keywords) && data.keywords.length ? data.keywords : (data.keyword ? [data.keyword] : []);
        const $keywordsList = $('#keywords-list-' + prefix);
        if ($keywordsList.length) {
            $keywordsList.find('input[name="filters[keywords][]"]').val('');
            keywords.forEach(function (keyword, index) {
                let $input = $keywordsList.find('input[name="filters[keywords][]"]').eq(index);
                if (! $input.length) {
                    $keywordsList.append('<div class="input-group input-group-sm mb-1 keyword-row"><input type="text" name="filters[keywords][]" class="form-control" placeholder="e.g. Software Engineer"><button type="button" class="btn btn-outline-danger btn-remove-kw" title="Remove"><i class="fas fa-times"></i></button></div>');
                    $input = $keywordsList.find('input[name="filters[keywords][]"]').eq(index);
                }
                $input.val(keyword);
            });
            $('.kw-count-badge-' + prefix).text(keywords.length);
        }

        $('#jobtypes-box-' + prefix + ' input[type="checkbox"]').prop('checked', false);
        (data.job_type_ids || []).forEach(id => $('#' + prefix + '-type-' + id).prop('checked', true));
        $('.jt-count-badge-' + prefix).text((data.job_type_ids || []).length + ' selected');
        $('[data-target="jobtypes-box-' + prefix + '"].btn-deselect-all-check.btn-outline-danger').prop('disabled', !(data.job_type_ids || []).length);

        $('#categories-box-' + prefix + ' input[type="checkbox"]').prop('checked', false);
        (data.category_ids || []).forEach(id => $('#' + prefix + '-cat-' + id).prop('checked', true));
        $('.cat-count-badge-' + prefix).text((data.category_ids || []).length + ' selected');
        $('[data-target="categories-box-' + prefix + '"].btn-deselect-all-check.btn-outline-danger').prop('disabled', !(data.category_ids || []).length);

        $('#countries-box-' + prefix + ' input[type="checkbox"]').prop('checked', false);
        (data.country_ids || []).forEach(id => $('#' + prefix + '-country-' + id + ', #edit-country-' + id).prop('checked', true));
        $('.country-count-badge-' + prefix).text((data.country_ids || []).length + ' selected');

        if (data.job_experience_id) $('select[name="filters[job_experience_id]"]').val(data.job_experience_id);
        if (data.location_keyword) $('input[name="filters[location_keyword]"]').val(data.location_keyword);
        $('input[name="cv_analysis_payload"]').val(JSON.stringify(data));
        Botble.showSuccess('AI analysis complete. Filters applied — review and adjust as needed.');
    }

    // Duration card selection on edit page (upgrade panel)
    $(document).on('change', 'input[name="duration_days"]', function () {
        $('label.duration-card').removeClass('border-primary').css({ background: '' }).addClass('border-secondary border-opacity-25');
        $(this).next('label.duration-card').addClass('border-primary').css({ background: '#f0f4ff' }).removeClass('border-secondary border-opacity-25');
    });
});
</script>
@endpush
