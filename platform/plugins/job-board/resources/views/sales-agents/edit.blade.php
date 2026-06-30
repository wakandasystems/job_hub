@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::form :url="route('sales-agents.update', $agent->getKey())" method="put" enctype="multipart/form-data">
        @csrf

        @include('plugins/job-board::sales-agents.partials.form')
    </x-core::form>

    @include('plugins/job-board::sales-agents.partials.campaign-builder')

    {{-- ── Clients panel ─────────────────────────────────────────────── --}}
    <x-core::card class="mt-4">
        <x-core::card.header>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <x-core::card.title>Clients <span class="badge bg-secondary text-white ms-1">{{ $clients->total() }}</span></x-core::card.title>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-link-client">
                        <x-core::icon name="ti ti-link" class="me-1" /> Link Existing Candidate
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create-client">
                        <x-core::icon name="ti ti-user-plus" class="me-1" /> Add New Candidate
                    </button>
                </div>
            </div>
        </x-core::card.header>
        <x-core::card.body class="{{ $clients->isEmpty() ? 'py-4 text-center text-muted' : '' }}">
            @if ($clients->isEmpty())
                No clients linked yet. Link an existing candidate or add a new one.
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-2">
                        <thead>
                            <tr>
                                <th style="width:44px;"></th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Source</th>
                                <th>Linked</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($clients as $referral)
                                <tr>
                                    <td>
                                        <img src="{{ $referral->account?->avatar_thumb_url ?: RvMedia::getDefaultImage() }}"
                                             alt="{{ $referral->account?->name }}"
                                             class="rounded-circle border"
                                             style="width:36px;height:36px;object-fit:cover;">
                                    </td>
                                    <td>
                                        @if ($referral->account)
                                            <a href="{{ route('accounts.edit', $referral->account->getKey()) }}" class="fw-semibold">{{ $referral->account->name }}</a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $referral->phone ?: '—' }}</td>
                                    <td class="small">{{ $referral->account?->email ?: '—' }}</td>
                                    <td>
                                        <span class="badge {{ $referral->source === 'manual' ? 'bg-info' : 'bg-success' }} text-white">
                                            {{ $referral->source === 'manual' ? 'Manual' : 'Code' }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $referral->first_used_at?->format('d M Y') ?: '—' }}</td>
                                    <td class="text-end">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger js-unlink-client"
                                            data-url="{{ route('sales-agents.unlink-client', [$agent->getKey(), $referral->getKey()]) }}"
                                            data-name="{{ $referral->account?->name ?: $referral->phone }}"
                                            title="Unlink">
                                            <x-core::icon name="ti ti-unlink" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $clients->withQueryString()->links() }}
            @endif
        </x-core::card.body>
    </x-core::card>

    {{-- ── Link Existing Candidate modal ────────────────────────────── --}}
    <div class="modal fade" id="modal-link-client" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Link Existing Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="link-client-error" class="alert alert-danger d-none mb-3"></div>
                    <label class="form-label">Search candidate</label>
                    <input type="search" class="form-control mb-2" id="lc-search" placeholder="Name, email or phone" autocomplete="off"
                        data-url="{{ route('sales-agents.search-candidates') }}">
                    <div class="list-group d-none" id="lc-search-results"></div>
                    <div class="alert alert-success py-2 d-none mt-2" id="lc-selected-alert">
                        <strong id="lc-selected-name"></strong>
                        <button type="button" class="btn btn-sm btn-link p-0 ms-2" id="lc-clear-btn">Clear</button>
                    </div>
                    <input type="hidden" id="lc-account-id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="lc-link-btn" disabled>Link Candidate</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Add New Candidate modal ───────────────────────────────────── --}}
    <div class="modal fade" id="modal-create-client" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="create-client-error" class="alert alert-danger d-none mb-3"></div>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cc-name" placeholder="e.g. Thembi Moyo">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp Phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cc-phone" placeholder="e.g. +260978123456">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-muted small">(optional)</span></label>
                        <input type="email" class="form-control" id="cc-email" placeholder="optional">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="cc-save-btn">Create &amp; Link</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Unlink confirm modal ─────────────────────────────────────── --}}
    <div class="modal fade" id="modal-unlink-confirm" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <x-core::icon name="ti ti-unlink" class="text-danger fs-3" />
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Unlink this client?</h6>
                    <p class="text-muted small mb-4" id="unlink-confirm-label">The candidate account will not be deleted.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="btnConfirmUnlink">Unlink</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
        <script>
        (function () {
            var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // ── Link Existing ────────────────────────────────────────────
            var lcSearch   = document.getElementById('lc-search');
            var lcResults  = document.getElementById('lc-search-results');
            var lcSelected = document.getElementById('lc-selected-alert');
            var lcName     = document.getElementById('lc-selected-name');
            var lcClear    = document.getElementById('lc-clear-btn');
            var lcId       = document.getElementById('lc-account-id');
            var lcBtn      = document.getElementById('lc-link-btn');
            var lcError    = document.getElementById('link-client-error');
            var lcTimer    = null;

            lcSearch.addEventListener('input', function () {
                clearTimeout(lcTimer);
                var q = lcSearch.value.trim();
                if (q.length < 2) { lcResults.classList.add('d-none'); lcResults.innerHTML = ''; return; }
                lcTimer = setTimeout(function () {
                    fetch(lcSearch.dataset.url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (payload) {
                            var items = payload.data || [];
                            lcResults.innerHTML = '';
                            if (!items.length) {
                                lcResults.innerHTML = '<div class="list-group-item text-muted small">No candidates found.</div>';
                                lcResults.classList.remove('d-none');
                                return;
                            }
                            items.forEach(function (c) {
                                var btn  = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2';
                                var img = document.createElement('img');
                                img.src = c.avatar_url; img.className = 'rounded-circle border'; img.style.cssText = 'width:32px;height:32px;object-fit:cover;';
                                var info = document.createElement('span');
                                info.innerHTML = '<span class="d-block fw-semibold">' + (c.name || '') + '</span><span class="d-block text-muted small">' + (c.phone || 'No phone') + (c.email ? ' · ' + c.email : '') + '</span>';
                                btn.appendChild(img); btn.appendChild(info);
                                btn.addEventListener('click', function () {
                                    lcId.value = c.id;
                                    lcName.textContent = c.name + (c.phone ? ' · ' + c.phone : '');
                                    lcSelected.classList.remove('d-none');
                                    lcBtn.disabled = false;
                                    lcResults.classList.add('d-none');
                                    lcSearch.value = c.name;
                                });
                                lcResults.appendChild(btn);
                            });
                            lcResults.classList.remove('d-none');
                        });
                }, 250);
            });

            lcClear.addEventListener('click', function () {
                lcId.value = ''; lcName.textContent = ''; lcSelected.classList.add('d-none'); lcBtn.disabled = true; lcSearch.value = ''; lcSearch.focus();
            });

            lcBtn.addEventListener('click', function () {
                lcError.classList.add('d-none');
                lcBtn.disabled = true;
                fetch('{{ route('sales-agents.link-client', $agent->getKey()) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ account_id: parseInt(lcId.value) }),
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { lcError.textContent = data.message || 'Could not link.'; lcError.classList.remove('d-none'); lcBtn.disabled = false; return; }
                    bootstrap.Modal.getInstance(document.getElementById('modal-link-client'))?.hide();
                    window.location.reload();
                })
                .catch(function () { lcError.textContent = 'Request failed.'; lcError.classList.remove('d-none'); lcBtn.disabled = false; });
            });

            // ── Create New ───────────────────────────────────────────────
            var ccName  = document.getElementById('cc-name');
            var ccPhone = document.getElementById('cc-phone');
            var ccEmail = document.getElementById('cc-email');
            var ccSave  = document.getElementById('cc-save-btn');
            var ccError = document.getElementById('create-client-error');

            ccSave.addEventListener('click', function () {
                ccError.classList.add('d-none');
                if (!ccName.value.trim() || !ccPhone.value.trim()) {
                    ccError.textContent = 'Name and phone are required.'; ccError.classList.remove('d-none'); return;
                }
                ccSave.disabled = true;
                fetch('{{ route('sales-agents.create-client', $agent->getKey()) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ full_name: ccName.value.trim(), phone: ccPhone.value.trim(), email: ccEmail.value.trim() || null }),
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { ccError.textContent = data.message || 'Could not create.'; ccError.classList.remove('d-none'); ccSave.disabled = false; return; }
                    bootstrap.Modal.getInstance(document.getElementById('modal-create-client'))?.hide();
                    window.location.reload();
                })
                .catch(function () { ccError.textContent = 'Request failed.'; ccError.classList.remove('d-none'); ccSave.disabled = false; });
            });

            // ── Unlink ───────────────────────────────────────────────────
            var unlinkUrl = null;
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-unlink-client');
                if (!btn) { return; }
                unlinkUrl = btn.dataset.url;
                document.getElementById('unlink-confirm-label').textContent = (btn.dataset.name || 'This candidate') + ' will be unlinked (account is not deleted).';
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-unlink-confirm')).show();
            });

            document.getElementById('btnConfirmUnlink').addEventListener('click', function () {
                if (!unlinkUrl) { return; }
                fetch(unlinkUrl, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
                    .then(function () { window.location.reload(); });
            });
        })();
        </script>
    @endpush
@stop
