@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="container-fluid">

        {{-- Status card --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3 flex-wrap">
                        <div class="flex-grow-1">
                            <h4 class="mb-1">Publer — Country Social Account Mapping</h4>
                            <p class="text-muted mb-0">
                                Map each country's jobs to the correct Facebook Page, LinkedIn Page, Twitter/X, TikTok, or Instagram account in Publer.
                                Jobs are published automatically when posted manually or via crawlers.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary" id="publerFetchBtn" type="button">
                                <x-core::icon name="ti ti-refresh" class="me-1" /> Fetch Accounts
                            </button>
                            <a href="{{ route('job-board.publer.category-templates.index') }}" class="btn btn-outline-info">
                                <x-core::icon name="ti ti-template" class="me-1" /> Category Templates
                            </a>
                            <a href="{{ route('job-board.automations.index') }}" class="btn btn-outline-secondary">
                                <x-core::icon name="ti ti-settings" class="me-1" /> Automations
                            </a>
                        </div>
                    </div>
                    @if($apiKey === '')
                        <div class="card-footer bg-warning-subtle text-warning-emphasis">
                            <x-core::icon name="ti ti-alert-triangle" class="me-1" />
                            No <code>PUBLER_API_KEY</code> configured. Add it to <code>.env</code> or the Settings panel.
                        </div>
                    @else
                        <div class="card-footer bg-success-subtle text-success-emphasis">
                            <x-core::icon name="ti ti-circle-check" class="me-1" />
                            API key configured.
                            @if($workspaceId)
                                Workspace: <code>{{ $workspaceId }}</code>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Accounts selector (hidden until fetched) --}}
        <div id="publerAccountsPanel" class="d-none mb-3">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <strong>Connected Publer Accounts</strong>
                    <span id="publerWorkspaceLabel" class="ms-2 badge bg-white text-primary"></span>
                </div>
                <div class="card-body">
                    <div id="publerAccountsList" class="row g-2"></div>
                </div>
            </div>
        </div>

        {{-- Country mapping table --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Country</th>
                                <th>Jobs</th>
                                <th>Facebook Page</th>
                                <th>LinkedIn Page</th>
                                <th>Twitter / X</th>
                                <th>TikTok</th>
                                <th>Instagram</th>
                                <th class="text-center">Active</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($countries as $country)
                                @php
                                    $mapping = $mappings[$country->id] ?? null;
                                @endphp
                                <tr data-country-id="{{ $country->id }}" data-country-name="{{ $country->name }}">
                                    <td>
                                        <strong>{{ $country->name }}</strong>
                                        @if($country->code)
                                            <span class="text-muted ms-1 small">{{ strtoupper($country->code) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $jobCounts[$country->id] ?? 0 }}</span>
                                    </td>
                                    @foreach(['facebook', 'linkedin', 'twitter', 'tiktok', 'instagram'] as $platform)
                                        <td>
                                            <select class="form-select form-select-sm publer-account-select"
                                                    data-platform="{{ $platform }}"
                                                    style="min-width:140px">
                                                <option value="">— none —</option>
                                                @if($mapping && $mapping->{$platform . '_account_id'})
                                                    <option value="{{ $mapping->{$platform . '_account_id'} }}" selected>
                                                        {{ $mapping->{$platform . '_account_id'} }}
                                                    </option>
                                                @endif
                                            </select>
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                                            <input class="form-check-input publer-active-toggle"
                                                   type="checkbox"
                                                   {{ ($mapping && $mapping->is_active) ? 'checked' : '' }}
                                                   {{ $mapping ? '' : 'disabled' }}
                                                   data-mapping-id="{{ $mapping?->id ?? '' }}">
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                                            <button class="btn btn-sm btn-primary publer-save-btn" type="button"
                                                    data-workspace-id="{{ $workspaceId }}">
                                                <x-core::icon name="ti ti-device-floppy" /> Save
                                            </button>
                                            @if($mapping)
                                                <button class="btn btn-sm btn-outline-info publer-image-btn" type="button"
                                                        title="Image settings"
                                                        data-mapping-id="{{ $mapping->id }}"
                                                        data-country="{{ $country->name }}"
                                                        data-image-mode="{{ $mapping->image_mode }}"
                                                        data-wm-logo="{{ $mapping->wm_logo ? asset($mapping->wm_logo) : '' }}"
                                                        data-text-color="{{ $mapping->text_color ?: '#FFFFFF' }}"
                                                        data-overlay-opacity="{{ $mapping->overlay_opacity ?? 55 }}"
                                                        data-save-url="{{ route('job-board.publer.image-settings', $mapping->id) }}"
                                                        data-preview-url="{{ route('job-board.publer.preview-image', $mapping->id) }}">
                                                    <x-core::icon name="ti ti-photo" />
                                                </button>
                                                <button class="btn btn-sm btn-outline-success publer-test-btn" type="button"
                                                        data-mapping-id="{{ $mapping->id }}">
                                                    <x-core::icon name="ti ti-send" /> Test
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger publer-delete-btn" type="button"
                                                        data-mapping-id="{{ $mapping->id }}">
                                                    <x-core::icon name="ti ti-trash" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection

{{-- Image Settings Modal --}}
<div class="modal fade" id="publerImageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="publerImageForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <x-core::icon name="ti ti-photo" class="text-info" />
                        Image Settings — <span id="imgModalCountry"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    {{-- Image Mode --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Image Generation Mode</label>
                        <div class="d-flex gap-3">
                            <div class="card flex-fill border-2 img-mode-card" data-mode="none" style="cursor:pointer">
                                <div class="card-body text-center py-3">
                                    <x-core::icon name="ti ti-photo-off" class="fs-2 text-muted mb-1 d-block" />
                                    <div class="fw-semibold">None</div>
                                    <small class="text-muted">No image generation — posts text only (or uses existing job image)</small>
                                </div>
                            </div>
                            <div class="card flex-fill border-2 img-mode-card" data-mode="template" style="cursor:pointer">
                                <div class="card-body text-center py-3">
                                    <x-core::icon name="ti ti-template" class="fs-2 text-info mb-1 d-block" />
                                    <div class="fw-semibold">Template (GD)</div>
                                    <small class="text-muted">Upload background images — PHP overlays job details automatically</small>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="image_mode" id="imgModeInput" value="none">
                    </div>

                    {{-- Template settings (shown when mode = template) --}}
                    <div id="imgTemplateFields" class="d-none">
                        <hr class="my-3">

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="alert alert-info py-2 mb-0 small">
                                    <x-core::icon name="ti ti-template" class="me-1" />
                                    Background photos now come from <strong>Category Templates</strong> — map job categories to a
                                    background there. This page only controls the watermark logo and text styling applied on top.
                                    <a href="{{ route('job-board.publer.category-templates.index') }}">Manage Category Templates →</a>
                                </div>
                            </div>

                            {{-- Watermark logo --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">
                                    Watermark Logo <span class="text-muted fw-normal">(PNG recommended — shown bottom-centre)</span>
                                </label>
                                <input type="file" name="wm_logo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                <div id="logoPreviewThumb" class="mt-2 d-none">
                                    <img class="rounded border" style="max-height:60px;max-width:100%">
                                    <div class="text-muted small mt-1 img-current-label"></div>
                                </div>
                            </div>

                            {{-- Text color + opacity --}}
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Text Color</label>
                                <input type="color" name="text_color" id="imgTextColor" class="form-control form-control-color w-100" value="#FFFFFF">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Overlay Opacity <span id="opacityVal" class="text-muted">55</span>%</label>
                                <input type="range" name="overlay_opacity" id="imgOpacity" class="form-range" min="0" max="90" value="55">
                                <div class="d-flex justify-content-between text-muted" style="font-size:.7rem">
                                    <span>0% (clear)</span><span>90% (dark)</span>
                                </div>
                            </div>

                            {{-- Tip box --}}
                            <div class="col-12">
                                <div class="alert alert-info py-2 mb-0 small">
                                    <x-core::icon name="ti ti-info-circle" class="me-1" />
                                    <strong>Tip:</strong> Job title, company, and deadline are placed in the lower portion of the
                                    image with a gradient overlay for readability — pick a watermark logo and text colour that
                                    stay legible against the category templates' backgrounds.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="imgPreviewSquareBtn" class="btn btn-outline-info btn-sm d-none" target="_blank">
                        <x-core::icon name="ti ti-eye" class="me-1" /> Preview Square
                    </a>
                    <a href="#" id="imgPreviewVertBtn" class="btn btn-outline-info btn-sm d-none" target="_blank">
                        <x-core::icon name="ti ti-eye" class="me-1" /> Preview Vertical
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-core::icon name="ti ti-device-floppy" class="me-1" /> Save Image Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Confirmation modal (replaces native confirm() dialogs) --}}
<div class="modal fade" id="publerConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="publerConfirmTitle">Confirm</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="publerConfirmBody"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary btn-sm" id="publerConfirmOk">Yes, proceed</button>
            </div>
        </div>
    </div>
</div>

@push('footer')
<script>
(function () {
    'use strict';

    const ICON_REFRESH = @js((string) Botble\Icon\Facades\Icon::render('refresh', ['class' => 'me-1']));
    const ICON_SAVE    = @js((string) Botble\Icon\Facades\Icon::render('device-floppy', ['class' => 'me-1']));
    const ICON_SEND    = @js((string) Botble\Icon\Facades\Icon::render('send', ['class' => 'me-1']));

    const FETCH_URL      = '{{ route('job-board.publer.fetch-accounts') }}';
    const UPSERT_URL     = '{{ route('job-board.publer.upsert') }}';
    const TEST_PATTERN   = '{{ route('job-board.publer.test',           ['mapping' => 'MAPPING_ID']) }}';
    const DEL_PATTERN    = '{{ route('job-board.publer.destroy',        ['mapping' => 'MAPPING_ID']) }}';
    const TOG_PATTERN    = '{{ route('job-board.publer.toggle',         ['mapping' => 'MAPPING_ID']) }}';
    const IMG_PATTERN    = '{{ route('job-board.publer.image-settings', ['mapping' => 'MAPPING_ID']) }}';
    const PREV_PATTERN   = '{{ route('job-board.publer.preview-image',  ['mapping' => 'MAPPING_ID']) }}';

    function testUrl(id)   { return TEST_PATTERN.replace('MAPPING_ID', id); }
    function delUrl(id)    { return DEL_PATTERN.replace('MAPPING_ID', id); }
    function toggleUrl(id) { return TOG_PATTERN.replace('MAPPING_ID', id); }

    // Keeps label+type indexed by account id after fetch
    let accountsIndex = {};

    // ── Confirmation modal helper ─────────────────────────────────────────────
    function publerConfirm(message, onOk) {
        document.getElementById('publerConfirmBody').textContent = message;
        const modal = new bootstrap.Modal(document.getElementById('publerConfirmModal'));
        const okBtn = document.getElementById('publerConfirmOk');
        const handler = function () {
            modal.hide();
            okBtn.removeEventListener('click', handler);
            onOk();
        };
        okBtn.addEventListener('click', handler);
        modal.show();
    }

    // ── Fetch accounts ────────────────────────────────────────────────────────
    document.getElementById('publerFetchBtn').addEventListener('click', function () {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Fetching…';

        $.post(FETCH_URL, { _token: '{{ csrf_token() }}' })
            .done(function (res) {
                accountsIndex = {};
                res.accounts.forEach(a => { accountsIndex[a.id] = a; });

                // Update workspace label
                if (res.workspace_id) {
                    document.getElementById('publerWorkspaceLabel').textContent = 'Workspace: ' + res.workspace_id;
                }

                // Show accounts panel
                const list = document.getElementById('publerAccountsList');
                list.innerHTML = '';
                res.accounts.forEach(function (a) {
                    list.innerHTML += `
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="border rounded p-2 d-flex align-items-center gap-2">
                                <span class="badge bg-primary">${a.type_label || a.type}</span>
                                <span class="fw-semibold">${a.name}</span>
                                <code class="ms-auto text-muted small">${a.id}</code>
                            </div>
                        </div>`;
                });
                document.getElementById('publerAccountsPanel').classList.remove('d-none');

                // Re-populate all selects in the table
                repopulateSelects(res.accounts);

                Botble.showSuccess('Accounts loaded from Publer.');
            })
            .fail(function (xhr) {
                const msg = xhr.responseJSON?.error || 'Failed to fetch accounts.';
                Botble.showError(msg);
            })
            .always(function () {
                const btn = document.getElementById('publerFetchBtn');
                btn.disabled = false;
                btn.innerHTML = ICON_REFRESH + ' Fetch Accounts';
            });
    });

    function repopulateSelects(accounts) {
        document.querySelectorAll('tr[data-country-id]').forEach(function (row) {
            ['facebook', 'linkedin', 'twitter', 'tiktok', 'instagram'].forEach(function (platform) {
                const sel = row.querySelector(`.publer-account-select[data-platform="${platform}"]`);
                if (! sel) return;

                const current = sel.value;
                sel.innerHTML = '<option value="">— none —</option>';

                accounts.forEach(function (a) {
                    // Only show accounts that match the platform type
                    const match = platformMatch(a.type, platform);
                    if (! match) return;
                    const opt     = document.createElement('option');
                    opt.value     = a.id;
                    opt.textContent = `${a.type_label || a.type}: ${a.name}`;
                    if (a.id === current) opt.selected = true;
                    sel.appendChild(opt);
                });
            });
        });
    }

    function platformMatch(accountType, platform) {
        const map = {
            facebook:  ['fb_page', 'fb_group', 'fb_profile', 'facebook'],
            linkedin:  ['in_page', 'in_profile', 'linkedin'],
            twitter:   ['tw_profile', 'twitter', 'x'],
            tiktok:    ['tiktok', 'tt_profile'],
            instagram: ['ig_account', 'instagram'],
        };
        return (map[platform] || []).some(t => accountType.toLowerCase().includes(t.toLowerCase()));
    }

    // ── Save mapping ──────────────────────────────────────────────────────────
    document.querySelectorAll('.publer-save-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const row        = this.closest('tr');
            const countryId  = row.dataset.countryId;
            const workspaceId = this.dataset.workspaceId || '';

            const data = {
                _token:               '{{ csrf_token() }}',
                country_id:           countryId,
                workspace_id:         workspaceId,
                facebook_account_id:  row.querySelector('.publer-account-select[data-platform="facebook"]')?.value  || '',
                linkedin_account_id:  row.querySelector('.publer-account-select[data-platform="linkedin"]')?.value  || '',
                twitter_account_id:   row.querySelector('.publer-account-select[data-platform="twitter"]')?.value   || '',
                tiktok_account_id:    row.querySelector('.publer-account-select[data-platform="tiktok"]')?.value    || '',
                instagram_account_id: row.querySelector('.publer-account-select[data-platform="instagram"]')?.value || '',
                is_active:            1,
            };

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            $.post(UPSERT_URL, data)
                .done(function (res) {
                    Botble.showSuccess('Mapping saved for ' + row.dataset.countryName + '.');
                    // Enable the active toggle and test/delete buttons after first save
                    // Reload to refresh mapping IDs
                    setTimeout(() => location.reload(), 800);
                })
                .fail(function (xhr) {
                    Botble.showError(xhr.responseJSON?.message || 'Save failed.');
                    btn.disabled = false;
                    btn.innerHTML = ICON_SAVE + ' Save';
                });
        });
    });

    // ── Active toggle ─────────────────────────────────────────────────────────
    document.querySelectorAll('.publer-active-toggle').forEach(function (chk) {
        chk.addEventListener('change', function () {
            const mappingId = this.dataset.mappingId;
            if (! mappingId) return;

            $.post(toggleUrl(mappingId), { _token: '{{ csrf_token() }}' })
                .done(function (res) {
                    Botble.showSuccess('Status updated.');
                })
                .fail(function () {
                    chk.checked = ! chk.checked; // revert
                    Botble.showError('Failed to update status.');
                });
        });
    });

    // ── Test post ─────────────────────────────────────────────────────────────
    document.querySelectorAll('.publer-test-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const mappingId  = this.dataset.mappingId;
            const row        = this.closest('tr');
            const countryName = row.dataset.countryName;

            publerConfirm('Send a test post to ' + countryName + ' accounts via Publer?', function () {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                $.post(testUrl(mappingId), { _token: '{{ csrf_token() }}' })
                .done(function (res) {
                    Botble.showSuccess(res.message || 'Test post sent!');
                })
                .fail(function (xhr) {
                    Botble.showError(xhr.responseJSON?.message || 'Test post failed.');
                })
                .always(function () {
                    btn.disabled = false;
                    btn.innerHTML = ICON_SEND + ' Test';
                });
            });
        });
    });

    // ── Delete mapping ────────────────────────────────────────────────────────
    document.querySelectorAll('.publer-delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const mappingId   = this.dataset.mappingId;
            const row         = this.closest('tr');
            const countryName = row.dataset.countryName;

            publerConfirm('Clear all account mappings for ' + countryName + '?', function () {
                $.ajax({ url: delUrl(mappingId), method: 'DELETE', data: { _token: '{{ csrf_token() }}' } })
                    .done(function () {
                        Botble.showSuccess('Mapping cleared for ' + countryName + '.');
                        setTimeout(() => location.reload(), 600);
                    })
                    .fail(function () {
                        Botble.showError('Delete failed.');
                    });
            });
        });
    });
    // ── Image settings modal ──────────────────────────────────────────────────
    const imgModal       = new bootstrap.Modal(document.getElementById('publerImageModal'));
    const imgModeInput   = document.getElementById('imgModeInput');
    const imgTplFields   = document.getElementById('imgTemplateFields');
    const imgOpacity     = document.getElementById('imgOpacity');
    const opacityVal     = document.getElementById('opacityVal');

    // Mode card selection
    document.querySelectorAll('.img-mode-card').forEach(function (card) {
        card.addEventListener('click', function () {
            document.querySelectorAll('.img-mode-card').forEach(c => c.classList.remove('border-primary', 'bg-primary-subtle'));
            this.classList.add('border-primary', 'bg-primary-subtle');
            const mode = this.dataset.mode;
            imgModeInput.value = mode;
            imgTplFields.classList.toggle('d-none', mode !== 'template');
            document.getElementById('imgPreviewSquareBtn').classList.toggle('d-none', mode !== 'template');
            document.getElementById('imgPreviewVertBtn').classList.toggle('d-none', mode !== 'template');
        });
    });

    // Opacity slider label
    imgOpacity.addEventListener('input', function () { opacityVal.textContent = this.value; });

    // File input previews
    [['wm_logo', 'logoPreviewThumb']].forEach(function ([name, thumbId]) {
        const input = document.querySelector(`#publerImageForm input[name="${name}"]`);
        const thumb = document.getElementById(thumbId);
        if (!input || !thumb) return;
        input.addEventListener('change', function () {
            if (!this.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                thumb.querySelector('img').src = e.target.result;
                thumb.querySelector('.img-current-label').textContent = 'New: ' + this.files[0].name;
                thumb.classList.remove('d-none');
            };
            reader.readAsDataURL(this.files[0]);
        });
    });

    // Open modal with mapping data
    document.querySelectorAll('.publer-image-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = this.dataset;
            document.getElementById('imgModalCountry').textContent = d.country;
            document.getElementById('publerImageForm').action = IMG_PATTERN.replace('MAPPING_ID', d.mappingId);

            const logoUrl     = d.wmLogo;
            const previewBase = PREV_PATTERN.replace('MAPPING_ID', d.mappingId);
            document.getElementById('imgPreviewSquareBtn').href = previewBase + '?format=square';
            document.getElementById('imgPreviewVertBtn').href   = previewBase + '?format=vertical';

            // Existing thumbnails
            function setThumb(thumbId, url, label) {
                const thumb = document.getElementById(thumbId);
                if (url) {
                    thumb.querySelector('img').src = url;
                    thumb.querySelector('.img-current-label').textContent = 'Current: ' + label;
                    thumb.classList.remove('d-none');
                } else {
                    thumb.classList.add('d-none');
                }
            }
            setThumb('logoPreviewThumb', logoUrl, 'watermark logo');

            // Mode
            const mode = d.imageMode || 'none';
            imgModeInput.value = mode;
            document.querySelectorAll('.img-mode-card').forEach(c => {
                c.classList.toggle('border-primary', c.dataset.mode === mode);
                c.classList.toggle('bg-primary-subtle', c.dataset.mode === mode);
            });
            imgTplFields.classList.toggle('d-none', mode !== 'template');
            document.getElementById('imgPreviewSquareBtn').classList.toggle('d-none', mode !== 'template');
            document.getElementById('imgPreviewVertBtn').classList.toggle('d-none', mode !== 'template');

            // Settings
            document.getElementById('imgTextColor').value = d.textColor || '#FFFFFF';
            imgOpacity.value = d.overlayOpacity || 55;
            opacityVal.textContent = imgOpacity.value;

            imgModal.show();
        });
    });

    // Submit image settings form (multipart — use native fetch not $.ajax)
    document.getElementById('publerImageForm').addEventListener('submit', function (e) {
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
                    imgModal.hide();
                    Botble.showSuccess(resp.message || 'Image settings saved.');
                    setTimeout(() => location.reload(), 600);
                }
            })
            .catch(() => Botble.showError('Request failed.'))
            .finally(() => $btn.prop('disabled', false).html(ICON_SAVE + ' Save Image Settings'));
    });

})();
</script>
@endpush
