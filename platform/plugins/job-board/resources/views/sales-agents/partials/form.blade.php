<div class="row">
    <div class="col-md-8">
        <x-core::card>
            <x-core::card.body>
                <div class="mb-3">
                    <label class="form-label">Find Existing Candidate</label>
                    <div
                        id="candidate-selected"
                        class="alert alert-success py-2 px-3 mb-2 {{ old('candidate_account_id', $agent->candidate_account_id) ? '' : 'd-none' }}"
                    >
                        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                            <div>
                                <div class="fw-semibold"><i class="ti ti-user-check me-1"></i> Candidate selected</div>
                                <div class="small" id="candidate-selected-label">
                                    @if(old('candidate_account_id', $agent->candidate_account_id))
                                        {{ $agent->candidateAccount?->name ?: old('name', $agent->name) }}
                                        @if($agent->candidateAccount?->email || old('email', $agent->email))
                                            · {{ $agent->candidateAccount?->email ?: old('email', $agent->email) }}
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-link p-0" id="candidate-clear-btn">Clear &amp; choose another</button>
                        </div>
                    </div>
                    <input
                        type="search"
                        class="form-control"
                        id="candidate-search"
                        placeholder="Search by name, email, or phone"
                        autocomplete="off"
                        data-url="{{ route('sales-agents.search-candidates') }}"
                    >
                    <input type="hidden" name="candidate_account_id" id="candidate-account-id" value="{{ old('candidate_account_id', $agent->candidate_account_id) }}">
                    <div class="list-group mt-2 d-none" id="candidate-search-results"></div>
                    <div class="form-hint">Selecting a candidate pre-fills name, phone, email, and marketing photo. Upload a different photo below only if their profile image is not suitable.</div>
                </div>

                <x-core::form.text-input
                    label="Name"
                    name="name"
                    placeholder="e.g. Melissa Banda"
                    :value="old('name', $agent->name)"
                />

                <div class="row">
                    <div class="col-md-6">
                        <x-core::form.text-input
                            label="WhatsApp Phone"
                            name="phone"
                            placeholder="e.g. +260764650652"
                            :value="old('phone', $agent->phone)"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-core::form.text-input
                            label="Email"
                            type="email"
                            name="email"
                            placeholder="optional"
                            :value="old('email', $agent->email)"
                        />
                    </div>
                </div>

                <x-core::form.text-input
                    label="Referral / Promo Code"
                    name="code"
                    placeholder="e.g. MELISSA10"
                    :value="old('code', $agent->code)"
                    helper-text="Customers enter this at checkout to get the discount and credit this agent. Auto-suggested from the name below, you can edit it."
                />

                <x-core::form.text-input
                    label="Notes"
                    name="notes"
                    placeholder="optional internal notes"
                    :value="old('notes', $agent->notes)"
                />
            </x-core::card.body>
        </x-core::card>
    </div>

    <div class="col-md-4">
        <x-core::card class="mb-3">
            <x-core::card.body>
                <div class="mb-3">
                    <label class="form-label">Marketing Photo</label>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img
                            id="marketing-photo-preview"
                            src="{{ $agent->photoUrl() ?: $agent->candidateAccount?->avatar_thumb_url ?: RvMedia::getDefaultImage() }}"
                            alt="{{ $agent->name ?: $agent->candidateAccount?->name ?: 'Sales agent' }}"
                            class="rounded border"
                            style="width:72px;height:72px;object-fit:cover;"
                        >
                        <div class="text-muted small" id="marketing-photo-preview-note">Used as the reference image when generating this agent's campaign posters.</div>
                    </div>
                    <input type="file" name="photo" accept="image/*" class="form-control" id="marketing-photo-input">
                </div>

                <x-core::form.checkbox
                    label="Use this marketing photo in campaign posters"
                    name="use_marketing_photo"
                    value="1"
                    :checked="old('use_marketing_photo', $agent->use_marketing_photo)"
                />
                <div class="form-hint mb-3">When enabled, poster generation defaults to Nakia + this agent's marketing photo wherever campaigns are generated.</div>

                <x-core::form.select
                    label="Status"
                    name="status"
                    :options="['active' => 'Active', 'inactive' => 'Inactive']"
                    :value="old('status', $agent->status ?: 'active')"
                />

                <x-core::form.text-input
                    label="Commission Rate"
                    type="number"
                    name="commission_rate"
                    :value="old('commission_rate', $agent->commission_rate ?? $defaultCommissionRate ?? 10)"
                    :group-flat="true"
                    helper-text="Percentage of each referred sale paid to this agent."
                >
                    <x-slot:append>
                        <span class="input-group-text">%</span>
                    </x-slot:append>
                </x-core::form.text-input>
            </x-core::card.body>
        </x-core::card>

        <x-core::card>
            <x-core::card.body>
                <x-core::button type="submit" color="primary">
                    Save
                </x-core::button>
            </x-core::card.body>
        </x-core::card>
    </div>
</div>

<script>
    (function () {
        var searchInput = document.getElementById('candidate-search');
        var results = document.getElementById('candidate-search-results');
        var nameInput = document.querySelector('input[name="name"]');
        var phoneInput = document.querySelector('input[name="phone"]');
        var emailInput = document.querySelector('input[name="email"]');
        var codeInput = document.querySelector('input[name="code"]');
        var candidateIdInput = document.getElementById('candidate-account-id');
        var candidateSelected = document.getElementById('candidate-selected');
        var candidateSelectedLabel = document.getElementById('candidate-selected-label');
        var candidateClearBtn = document.getElementById('candidate-clear-btn');
        var photoPreview = document.getElementById('marketing-photo-preview');
        var photoInput = document.getElementById('marketing-photo-input');
        var photoNote = document.getElementById('marketing-photo-preview-note');
        var searchTimer = null;
        var uploadedPhotoObjectUrl = null;
        var defaultPhotoPreviewSrc = photoPreview ? photoPreview.getAttribute('src') : '{{ RvMedia::getDefaultImage() }}';
        var defaultPhotoPreviewAlt = photoPreview ? photoPreview.getAttribute('alt') : 'Sales agent';
        var defaultPhotoPreviewNote = photoNote ? photoNote.textContent : 'Used as the reference image when generating this agent\'s campaign posters.';

        if (!nameInput || !codeInput) {
            return;
        }

        function setMarketingPhotoPreview(src, alt, note) {
            if (!photoPreview) {
                return;
            }

            photoPreview.src = src || '{{ RvMedia::getDefaultImage() }}';
            photoPreview.alt = alt || 'Sales agent';

            if (photoNote && note) {
                photoNote.textContent = note;
            }
        }

        function setSelectedCandidateState(candidate) {
            if (!candidateSelected || !candidateSelectedLabel) {
                return;
            }

            if (!candidate || !candidate.id) {
                candidateSelected.classList.add('d-none');
                candidateSelectedLabel.textContent = '';
                return;
            }

            var bits = [];

            if (candidate.name) {
                bits.push(candidate.name);
            }

            if (candidate.email) {
                bits.push(candidate.email);
            }

            if (candidate.phone) {
                bits.push(candidate.phone);
            }

            candidateSelectedLabel.textContent = bits.join(' · ');
            candidateSelected.classList.remove('d-none');
        }

        function clearSelectedCandidate() {
            if (candidateIdInput) {
                candidateIdInput.value = '';
            }

            setSelectedCandidateState(null);
            setMarketingPhotoPreview(defaultPhotoPreviewSrc, defaultPhotoPreviewAlt, defaultPhotoPreviewNote);
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }

            results.classList.add('d-none');
            results.innerHTML = '';
        }

        if (searchInput && results) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);

                var query = searchInput.value.trim();

                if (query.length < 2) {
                    results.classList.add('d-none');
                    results.innerHTML = '';
                    return;
                }

                searchTimer = setTimeout(function () {
                    fetch(searchInput.dataset.url + '?q=' + encodeURIComponent(query), {
                        headers: {
                            'Accept': 'application/json',
                        },
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (payload) {
                            var items = payload.data || [];

                            results.innerHTML = '';

                            if (!items.length) {
                                results.innerHTML = '<div class="list-group-item text-muted small">No candidates found.</div>';
                                results.classList.remove('d-none');
                                return;
                            }

                            items.forEach(function (candidate) {
                                var button = document.createElement('button');
                                var image = document.createElement('img');
                                var content = document.createElement('span');
                                var name = document.createElement('span');
                                var meta = document.createElement('span');

                                button.type = 'button';
                                button.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2';

                                image.src = candidate.avatar_url;
                                image.alt = '';
                                image.className = 'rounded-circle border';
                                image.style.width = '32px';
                                image.style.height = '32px';
                                image.style.objectFit = 'cover';

                                content.className = 'text-start';
                                name.className = 'd-block fw-semibold';
                                name.textContent = candidate.name || '';
                                meta.className = 'd-block text-muted small';
                                meta.textContent = (candidate.phone || 'No phone') + (candidate.email ? ' · ' + candidate.email : '');

                                content.appendChild(name);
                                content.appendChild(meta);
                                button.appendChild(image);
                                button.appendChild(content);

                                button.addEventListener('click', function () {
                                    if (candidateIdInput) {
                                        candidateIdInput.value = candidate.id || '';
                                    }

                                    nameInput.value = candidate.name || '';
                                    phoneInput.value = candidate.phone || '';
                                    emailInput.value = candidate.email || '';
                                    setSelectedCandidateState(candidate);
                                    setMarketingPhotoPreview(
                                        candidate.avatar_url || candidate.avatar_full_url || '{{ RvMedia::getDefaultImage() }}',
                                        candidate.name || 'Sales agent',
                                        'Using the selected candidate profile image as the marketing photo. Upload another photo below if you want to override it.'
                                    );
                                    results.classList.add('d-none');
                                    searchInput.value = candidate.name || '';
                                    nameInput.dispatchEvent(new Event('blur'));
                                });

                                results.appendChild(button);
                            });

                            results.classList.remove('d-none');
                        });
                }, 250);
            });
        }

        if (candidateClearBtn) {
            candidateClearBtn.addEventListener('click', function () {
                clearSelectedCandidate();
            });
        }

        if (photoInput) {
            photoInput.addEventListener('change', function () {
                if (uploadedPhotoObjectUrl) {
                    URL.revokeObjectURL(uploadedPhotoObjectUrl);
                    uploadedPhotoObjectUrl = null;
                }

                if (this.files && this.files.length) {
                    uploadedPhotoObjectUrl = URL.createObjectURL(this.files[0]);
                    setMarketingPhotoPreview(
                        uploadedPhotoObjectUrl,
                        nameInput.value.trim() || 'Sales agent',
                        'Using the uploaded marketing photo.'
                    );
                }
            });
        }

        nameInput.addEventListener('blur', function () {
            if (codeInput.value.trim() !== '') {
                return;
            }

            var firstName = nameInput.value.trim().split(/\s+/)[0] || '';

            if (firstName === '') {
                return;
            }

            var suffix = Math.floor(10 + Math.random() * 90);
            codeInput.value = firstName.toUpperCase().replace(/[^A-Z0-9]/g, '') + suffix;
        });
    })();
</script>
