@extends(BaseHelper::getAdminMasterLayoutTemplate())

@push('header')
<style>
    .cat-prompt-block { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
    .cat-prompt-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
    }
    .cat-prompt-text {
        margin: 0; padding: 12px; font-size: 12.5px; line-height: 1.6; white-space: pre-wrap;
        max-height: 260px; overflow-y: auto; background: #fff; color: #334155;
    }
    .cat-copy-btn {
        padding: 5px 10px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px;
        font-size: 12px; font-weight: 700; color: #475569; cursor: pointer; transition: all .15s;
    }
    .cat-copy-btn:hover { background: #7c3aed; color: #fff; border-color: #7c3aed; }
    .cat-copy-btn.ok { background: #16a34a; color: #fff; border-color: #16a34a; }
</style>
@endpush

@section('content')
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3 flex-wrap">
                        <div class="flex-grow-1">
                            <h4 class="mb-1">Publer — Category Background Templates</h4>
                            <p class="text-muted mb-0">
                                Create a background template (e.g. "Office / Corporate", "Industrial Site", "Hospitality") and map it
                                to one or more job categories — several categories can share the same background. When generating
                                social images, the job's mapped template supplies the background; the watermark logo and text
                                styling still come from that job's country mapping.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" id="catNewTemplateBtn" type="button">
                                <x-core::icon name="ti ti-plus" class="me-1" /> New Template
                            </button>
                            <a href="{{ route('job-board.publer.index') }}" class="btn btn-outline-secondary">
                                <x-core::icon name="ti ti-arrow-left" class="me-1" /> Back to Country Mapping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Templates table --}}
        <div class="card mb-3">
            <div class="card-header"><strong>Templates</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Square</th>
                                <th>Vertical</th>
                                <th>Mapped Categories</th>
                                <th class="text-center">Active</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                @php
                                    $mappedNames = $template->categories->pluck('name')->all();
                                @endphp
                                <tr data-template-id="{{ $template->id }}" data-template-name="{{ $template->name }}">
                                    <td><strong>{{ $template->name }}</strong></td>
                                    <td>
                                        @if($template->template_square)
                                            <img src="{{ asset($template->template_square) }}" class="rounded border" style="height:40px">
                                        @else
                                            <span class="text-muted small">— none —</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($template->template_vertical)
                                            <img src="{{ asset($template->template_vertical) }}" class="rounded border" style="height:40px">
                                        @else
                                            <span class="text-muted small">— none —</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($mappedNames)
                                            @foreach($mappedNames as $name)
                                                <span class="badge bg-info-subtle text-info-emphasis">{{ $name }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted small">No categories mapped</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                                            <input class="form-check-input template-active-toggle"
                                                   type="checkbox"
                                                   {{ $template->is_active ? 'checked' : '' }}
                                                   data-template-id="{{ $template->id }}">
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                                            <button class="btn btn-sm btn-outline-primary category-template-prompt-btn" type="button"
                                                    title="AI image generator prompt"
                                                    data-template-id="{{ $template->id }}"
                                                    data-name="{{ $template->name }}">
                                                <x-core::icon name="ti ti-sparkles" />
                                            </button>
                                            <button class="btn btn-sm btn-outline-info category-template-edit-btn" type="button"
                                                    title="Edit"
                                                    data-template-id="{{ $template->id }}"
                                                    data-name="{{ $template->name }}"
                                                    data-template-square="{{ $template->template_square ? asset($template->template_square) : '' }}"
                                                    data-template-vertical="{{ $template->template_vertical ? asset($template->template_vertical) : '' }}"
                                                    data-is-active="{{ $template->is_active ? '1' : '0' }}"
                                                    data-category-ids="{{ $template->categories->pluck('id')->implode(',') }}"
                                                    data-update-url="{{ route('job-board.publer.category-templates.update', $template->id) }}"
                                                    data-preview-url="{{ route('job-board.publer.category-templates.preview-image', $template->id) }}">
                                                <x-core::icon name="ti ti-edit" />
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger category-template-delete-btn" type="button"
                                                    data-template-id="{{ $template->id }}">
                                                <x-core::icon name="ti ti-trash" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No templates yet — click "New Template" to create one.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Category coverage --}}
        <div class="card">
            <div class="card-header"><strong>Category Coverage</strong> <span class="text-muted small">— all {{ $coverageCategories->total() }} categories with jobs, most active first</span></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>Jobs</th>
                                <th>Mapped Template</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coverageCategories as $category)
                                @php
                                    $link = $links[$category->id] ?? null;
                                    $mappedTemplate = $link ? $templates->firstWhere('id', $link->template_id) : null;
                                @endphp
                                <tr>
                                    <td>{{ $coverageCategories->firstItem() + $loop->index }}</td>
                                    <td>{{ $category->name }}</td>
                                    <td><span class="badge bg-secondary text-white">{{ $jobCounts[$category->id] ?? 0 }}</span></td>
                                    <td>
                                        @if($mappedTemplate)
                                            <span class="badge bg-success-subtle text-success-emphasis">{{ $mappedTemplate->name }}</span>
                                        @else
                                            <span class="text-muted small">— not mapped —</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $coverageCategories->links() }}
            </div>
        </div>

    </div>
@endsection

{{-- Confirm modal --}}
<div class="modal fade" id="catConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Confirm</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="catConfirmBody"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary btn-sm" id="catConfirmOk">Yes, proceed</button>
            </div>
        </div>
    </div>
</div>

{{-- Template editor modal --}}
<div class="modal fade" id="catTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="catTemplateForm" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="is_active" id="catIsActiveInput" value="1">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <x-core::icon name="ti ti-photo" class="text-info" />
                        <span id="catModalTitle">New Background Template</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Template Name</label>
                            <input type="text" name="name" id="catNameInput" class="form-control form-control-sm"
                                   placeholder="e.g. Office / Corporate, Industrial Site, Hospitality" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">
                                Square Template <span class="text-muted fw-normal">(1080×1080 — Facebook, LinkedIn)</span>
                            </label>
                            <input type="file" name="template_square" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                            <div id="catSquareThumb" class="mt-2 d-none">
                                <img class="rounded border" style="max-height:80px;max-width:100%">
                                <div class="text-muted small mt-1 cat-current-label"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">
                                Vertical Template <span class="text-muted fw-normal">(1080×1920 — TikTok)</span>
                            </label>
                            <input type="file" name="template_vertical" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                            <div id="catVerticalThumb" class="mt-2 d-none">
                                <img class="rounded border" style="max-height:80px;max-width:100%">
                                <div class="text-muted small mt-1 cat-current-label"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">
                                Mapped Categories
                                <span class="text-muted fw-normal">— jobs in any of these categories will use this background. Picking a category here removes it from any other template.</span>
                            </label>
                            <select name="category_ids[]" id="catCategoriesSelect" class="form-select form-select-sm" multiple size="8">
                                @foreach($selectableCategories as $category)
                                    <option value="{{ $category->id }}">
                                        {{ $category->name }} ({{ $jobCounts[$category->id] ?? 0 }} jobs)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="catActiveSwitch" checked>
                                <label class="form-check-label small" for="catActiveSwitch">Active — use this template when generating images for its mapped categories</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info py-2 mb-0 small">
                                <x-core::icon name="ti ti-info-circle" class="me-1" />
                                Use a high-contrast background photo relevant to the mapped categories (office, mine, hospital, garage, etc.).
                                The job title, company, and deadline are overlaid automatically using the country's branding (logo/colors).
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="catPreviewSquareBtn" class="btn btn-outline-info btn-sm d-none" target="_blank">
                        <x-core::icon name="ti ti-eye" class="me-1" /> Preview Square
                    </a>
                    <a href="#" id="catPreviewVertBtn" class="btn btn-outline-info btn-sm d-none" target="_blank">
                        <x-core::icon name="ti ti-eye" class="me-1" /> Preview Vertical
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-core::icon name="ti ti-device-floppy" class="me-1" /> Save Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- AI image-generator prompt modal --}}
<div class="modal fade" id="catPromptModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <x-core::icon name="ti ti-sparkles" class="text-primary" />
                    <span>AI Image Prompt — <span id="catPromptTemplateName"></span></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Paste either prompt into an AI image generator (Midjourney, Gemini, ChatGPT/DALL·E, etc.) to produce
                    a background photo for this template's theme, then upload the result in the template editor above.
                </p>
                <div class="cat-prompt-block mb-3">
                    <div class="cat-prompt-head">
                        <strong>Square <span class="text-muted fw-normal">— 1080×1080 (Facebook, LinkedIn)</span></strong>
                        <button type="button" class="cat-copy-btn" data-format="square">📋 Copy</button>
                    </div>
                    <pre class="cat-prompt-text" id="catPromptSquare"></pre>
                </div>
                <div class="cat-prompt-block">
                    <div class="cat-prompt-head">
                        <strong>Vertical <span class="text-muted fw-normal">— 1080×1920 (TikTok)</span></strong>
                        <button type="button" class="cat-copy-btn" data-format="vertical">📋 Copy</button>
                    </div>
                    <pre class="cat-prompt-text" id="catPromptVertical"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('footer')
<script>
(function () {
    'use strict';

    const ICON_SAVE = @js((string) Botble\Icon\Facades\Icon::render('device-floppy', ['class' => 'me-1']));

    const TEMPLATE_PROMPTS = @json($prompts);

    // ── AI prompt modal ───────────────────────────────────────────────────────
    const promptModal = new bootstrap.Modal(document.getElementById('catPromptModal'));
    const promptName  = document.getElementById('catPromptTemplateName');
    const promptSquare   = document.getElementById('catPromptSquare');
    const promptVertical = document.getElementById('catPromptVertical');

    document.querySelectorAll('.category-template-prompt-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.templateId;
            const prompts = TEMPLATE_PROMPTS[id] || { square: '', vertical: '' };

            promptName.textContent = this.dataset.name;
            promptSquare.textContent   = prompts.square;
            promptVertical.textContent = prompts.vertical;
            promptModal.show();
        });
    });

    document.querySelectorAll('.cat-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = this.dataset.format === 'vertical' ? promptVertical : promptSquare;
            doCopy(target.textContent, this, '📋 Copy');
        });
    });

    function doCopy(text, btn, resetLabel) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => showOk(btn, resetLabel)).catch(() => legacyCopy(text, btn, resetLabel));
        } else {
            legacyCopy(text, btn, resetLabel);
        }
    }

    function legacyCopy(text, btn, resetLabel) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
        document.body.appendChild(ta);
        ta.focus(); ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showOk(btn, resetLabel);
    }

    function showOk(btn, resetLabel) {
        btn.textContent = '✅ Copied!';
        btn.classList.add('ok');
        setTimeout(() => { btn.textContent = resetLabel; btn.classList.remove('ok'); }, 2200);
    }

    const CREATE_URL  = '{{ route('job-board.publer.category-templates.save') }}';
    const TOG_PATTERN = '{{ route('job-board.publer.category-templates.toggle',  ['template' => 'TEMPLATE_ID']) }}';
    const DEL_PATTERN = '{{ route('job-board.publer.category-templates.destroy', ['template' => 'TEMPLATE_ID']) }}';

    function toggleUrl(id) { return TOG_PATTERN.replace('TEMPLATE_ID', id); }
    function delUrl(id)    { return DEL_PATTERN.replace('TEMPLATE_ID', id); }

    function catConfirm(message, onOk) {
        document.getElementById('catConfirmBody').textContent = message;
        const modal = new bootstrap.Modal(document.getElementById('catConfirmModal'));
        const okBtn = document.getElementById('catConfirmOk');
        const handler = function () {
            modal.hide();
            okBtn.removeEventListener('click', handler);
            onOk();
        };
        okBtn.addEventListener('click', handler);
        modal.show();
    }

    // ── Active toggle ─────────────────────────────────────────────────────────
    document.querySelectorAll('.template-active-toggle').forEach(function (chk) {
        chk.addEventListener('change', function () {
            const id = this.dataset.templateId;

            $.post(toggleUrl(id), { _token: '{{ csrf_token() }}' })
                .done(function () { Botble.showSuccess('Status updated.'); })
                .fail(function () {
                    chk.checked = ! chk.checked;
                    Botble.showError('Failed to update status.');
                });
        });
    });

    // ── Delete template ───────────────────────────────────────────────────────
    document.querySelectorAll('.category-template-delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id   = this.dataset.templateId;
            const name = this.closest('tr').dataset.templateName;

            catConfirm('Delete the "' + name + '" template? Categories mapped to it will become unmapped.', function () {
                $.ajax({ url: delUrl(id), method: 'DELETE', data: { _token: '{{ csrf_token() }}' } })
                    .done(function () {
                        Botble.showSuccess('Template "' + name + '" deleted.');
                        setTimeout(() => location.reload(), 600);
                    })
                    .fail(function () { Botble.showError('Delete failed.'); });
            });
        });
    });

    // ── Editor modal ──────────────────────────────────────────────────────────
    const modal        = new bootstrap.Modal(document.getElementById('catTemplateModal'));
    const form         = document.getElementById('catTemplateForm');
    const nameInput    = document.getElementById('catNameInput');
    const select       = document.getElementById('catCategoriesSelect');
    const activeSwitch = document.getElementById('catActiveSwitch');
    const activeInput  = document.getElementById('catIsActiveInput');
    const modalTitle   = document.getElementById('catModalTitle');
    const previewSquareBtn = document.getElementById('catPreviewSquareBtn');
    const previewVertBtn   = document.getElementById('catPreviewVertBtn');

    activeSwitch.addEventListener('change', function () {
        activeInput.value = this.checked ? '1' : '0';
    });

    function resetForm() {
        form.reset();
        form.action = CREATE_URL;
        modalTitle.textContent = 'New Background Template';
        activeSwitch.checked = true;
        activeInput.value = '1';
        Array.from(select.options).forEach(o => o.selected = false);
        ['catSquareThumb', 'catVerticalThumb'].forEach(id => document.getElementById(id).classList.add('d-none'));
        previewSquareBtn.classList.add('d-none');
        previewVertBtn.classList.add('d-none');
    }

    [['template_square', 'catSquareThumb'], ['template_vertical', 'catVerticalThumb']].forEach(function ([name, thumbId]) {
        const input = document.querySelector(`#catTemplateForm input[name="${name}"]`);
        const thumb = document.getElementById(thumbId);
        input.addEventListener('change', function () {
            if (! this.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                thumb.querySelector('img').src = e.target.result;
                thumb.querySelector('.cat-current-label').textContent = 'New: ' + this.files[0].name;
                thumb.classList.remove('d-none');
            };
            reader.readAsDataURL(this.files[0]);
        });
    });

    document.getElementById('catNewTemplateBtn').addEventListener('click', function () {
        resetForm();
        modal.show();
    });

    document.querySelectorAll('.category-template-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = this.dataset;
            resetForm();

            modalTitle.textContent = 'Edit — ' + d.name;
            form.action = d.updateUrl;
            nameInput.value = d.name;

            function setThumb(thumbId, url, label) {
                const thumb = document.getElementById(thumbId);
                if (url) {
                    thumb.querySelector('img').src = url;
                    thumb.querySelector('.cat-current-label').textContent = 'Current: ' + label;
                    thumb.classList.remove('d-none');
                }
            }
            setThumb('catSquareThumb',   d.templateSquare,   'square template');
            setThumb('catVerticalThumb', d.templateVertical, 'vertical template');

            const isActive = d.isActive !== '0';
            activeSwitch.checked = isActive;
            activeInput.value = isActive ? '1' : '0';

            const ids = (d.categoryIds || '').split(',').filter(Boolean);
            Array.from(select.options).forEach(o => { o.selected = ids.includes(o.value); });

            if (d.previewUrl) {
                previewSquareBtn.href = d.previewUrl + '?format=square';
                previewVertBtn.href   = d.previewUrl + '?format=vertical';
                previewSquareBtn.classList.remove('d-none');
                previewVertBtn.classList.remove('d-none');
            }

            modal.show();
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving…');

        fetch(this.action, {
            method: 'POST',
            body: new FormData(this),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(resp => {
                if (resp.error) {
                    Botble.showError(resp.message || 'Save failed.');
                } else {
                    modal.hide();
                    Botble.showSuccess(resp.message || 'Template saved.');
                    setTimeout(() => location.reload(), 600);
                }
            })
            .catch(() => Botble.showError('Request failed.'))
            .finally(() => $btn.prop('disabled', false).html(ICON_SAVE + ' Save Template'));
    });

})();
</script>
@endpush
