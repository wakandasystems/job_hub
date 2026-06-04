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
        $('[data-target="' + $box.attr('id') + '"].btn-deselect-all-check').filter('.btn-outline-danger').each(function() { count > 0 ? $(this).show() : $(this).hide(); });
    }
    $(document).on('input', '.filter-search', function () {
        const needle = $(this).val().toLowerCase(), $box = $('#' + $(this).data('target'));
        $box.find('.checkable-item').each(function () { $(this).toggle($(this).text().toLowerCase().includes(needle)); });
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
    $(document).on('click', '.btn-apply-analysis', function () {
        const analysis = $(this).data('analysis');
        if (analysis) applyAnalysis($(this).data('prefix'), analysis);
    });
    function applyAnalysis(prefix, data) {
        if (data.keyword) $('input[name="filters[keyword]"]').val(data.keyword);
        if (data.job_type_ids) data.job_type_ids.forEach(id => $('#' + prefix + '-type-' + id).prop('checked', true));
        if (data.category_ids) data.category_ids.forEach(id => $('#' + prefix + '-cat-' + id).prop('checked', true));
        if (data.job_experience_id) $('select[name="filters[job_experience_id]"]').val(data.job_experience_id);
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
