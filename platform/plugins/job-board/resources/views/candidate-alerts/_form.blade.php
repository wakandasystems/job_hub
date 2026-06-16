@php
    $f                    = $alert?->filters ?? [];
    $selJobTypes          = array_map('strval', (array) ($f['job_type_ids']     ?? []));
    $selCategories        = array_map('strval', (array) ($f['category_ids']     ?? []));
    $selCountries         = array_map('strval', (array) ($f['country_ids']      ?? []));
    $savedKeywords        = (array) ($f['keywords'] ?? (($f['keyword'] ?? null) ? [$f['keyword']] : []));
    $savedCompanyKeywords = (array) ($f['company_keywords'] ?? []);
    $cvAnalysis           = $alert?->cv_analysis;
    $tid                  = $prefix;
    $preloadedCityNames   = [];
@endphp

{{-- ── Tab nav ──────────────────────────────────────────────────────────────── --}}
<ul class="nav nav-tabs nav-tabs-bordered mb-0" id="tabs-{{ $tid }}" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active px-3 py-2" id="tab-candidate-{{ $tid }}-btn"
            data-bs-toggle="tab" data-bs-target="#tab-candidate-{{ $tid }}"
            type="button" role="tab">
            <i class="fas fa-user me-1"></i> Candidate
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link px-3 py-2" id="tab-filters-{{ $tid }}-btn"
            data-bs-toggle="tab" data-bs-target="#tab-filters-{{ $tid }}"
            type="button" role="tab">
            <i class="fas fa-filter me-1"></i> Filters
            @php $filterCount = count(array_filter([$selJobTypes, $selCategories, $selCountries, $savedKeywords, $f['job_experience_id'] ?? null], fn($v) => !empty($v))); @endphp
            @if($filterCount)
                <span class="badge bg-primary text-white ms-1">{{ $filterCount }}</span>
            @endif
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link px-3 py-2" id="tab-package-{{ $tid }}-btn"
            data-bs-toggle="tab" data-bs-target="#tab-package-{{ $tid }}"
            type="button" role="tab">
            <i class="fas fa-box me-1"></i> Package &amp; CV
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link px-3 py-2" id="tab-notes-{{ $tid }}-btn"
            data-bs-toggle="tab" data-bs-target="#tab-notes-{{ $tid }}"
            type="button" role="tab">
            <i class="fas fa-sticky-note me-1"></i> Notes
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-3" id="tabContent-{{ $tid }}" style="max-height:58vh;overflow-y:auto;overflow-x:hidden">

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TAB 1 — CANDIDATE                                             --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="tab-pane fade show active" id="tab-candidate-{{ $tid }}" role="tabpanel">
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-semibold">Candidate Name <span class="text-danger">*</span></label>
                <input type="text" name="candidate_name" class="form-control"
                    value="{{ old('candidate_name', $alert?->candidate_name) }}"
                    placeholder="e.g. John Doe" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    <i class="fab fa-whatsapp me-1" style="color:#25D366"></i>
                    WhatsApp Number <span class="text-danger">*</span>
                </label>
                <input type="text" name="candidate_phone"
                    class="form-control phone-check-input"
                    value="{{ old('candidate_phone', $alert?->candidate_phone) }}"
                    placeholder="+260977000000" required
                    data-check-url="{{ route('job-board.candidate-alerts.check-phone') }}"
                    data-exclude-id="{{ $alert?->id ?? 0 }}">
                <div class="form-text">International format. Alerts are sent here via WhatsApp.</div>
                <div class="phone-check-warning mt-1" style="display:none"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    <i class="fab fa-whatsapp me-1" style="color:#25D366"></i>
                    Second WhatsApp Number <span class="text-muted small">(optional)</span>
                </label>
                <input type="text" name="candidate_phone_2"
                    class="form-control"
                    value="{{ old('candidate_phone_2', $alert?->candidate_phone_2) }}"
                    placeholder="+260977000000">
                <div class="form-text">If set, alerts are also sent to this number via WhatsApp.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Email <span class="text-muted small">(optional)</span></label>
                <input type="email" name="candidate_email" class="form-control"
                    value="{{ old('candidate_email', $alert?->candidate_email) }}"
                    placeholder="john@example.com">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Experience Level</label>
                <select name="filters[job_experience_id]" class="form-select">
                    <option value="">— Any Experience —</option>
                    @foreach($experiences as $expId => $expName)
                        <option value="{{ $expId }}"
                            {{ old('filters.job_experience_id', $f['job_experience_id'] ?? '') == $expId ? 'selected' : '' }}>
                            {{ $expName }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TAB 2 — FILTERS                                               --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="tab-pane fade" id="tab-filters-{{ $tid }}" role="tabpanel">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Leave sections empty to match all. Multiple values within a section are matched with <strong>OR</strong> logic.
            </p>
            <button type="button"
                class="btn btn-outline-success btn-sm btn-preview-filters flex-shrink-0"
                data-url="{{ route('job-board.candidate-alerts.preview-filters') }}"
                data-tid="{{ $tid }}">
                <i class="fas fa-eye me-1"></i> Preview Matching Jobs
            </button>
        </div>

        {{-- Filter preview result panel --}}
        <div id="filter-preview-panel-{{ $tid }}" class="d-none mb-3 border rounded">
            <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-light border-bottom">
                <span class="fw-semibold small" id="filter-preview-label-{{ $tid }}">Matching jobs</span>
                <button type="button" class="btn-close btn-sm btn-close-filter-preview" data-tid="{{ $tid }}" style="font-size:.7rem"></button>
            </div>
            <div id="filter-preview-body-{{ $tid }}" style="max-height:260px;overflow-y:auto;font-size:.82rem"></div>
        </div>

        {{-- ── Keywords ──────────────────────────────────────────── --}}
        <div class="mb-2">
            <button type="button"
                class="btn btn-link text-decoration-none text-dark p-0 d-flex align-items-center gap-2 w-100 collapse-toggle-btn"
                data-bs-toggle="collapse" data-bs-target="#collapse-keywords-{{ $tid }}"
                aria-expanded="false">
                <i class="fas fa-search text-muted" style="width:14px"></i>
                <span class="fw-semibold small">Keywords</span>
                <span class="badge bg-secondary text-white ms-1 kw-count-badge-{{ $tid }}">{{ count($savedKeywords) }}</span>
                <i class="fas fa-chevron-down ms-auto text-muted small collapse-chevron"></i>
            </button>
            <div class="collapse" id="collapse-keywords-{{ $tid }}">
                <div class="pt-2 ps-4">
                    <div class="text-muted small mb-2">Add one or more keywords — jobs matching <em>any</em> keyword will be sent.</div>
                    <div id="keywords-list-{{ $tid }}">
                        @if(!empty($savedKeywords))
                            @foreach($savedKeywords as $i => $kw)
                            <div class="input-group input-group-sm mb-1 keyword-row">
                                <input type="text" name="filters[keywords][]"
                                    class="form-control" value="{{ $kw }}"
                                    placeholder="e.g. Software Engineer">
                                <button type="button" class="btn btn-outline-danger btn-remove-kw" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @endforeach
                        @else
                            <div class="input-group input-group-sm mb-1 keyword-row">
                                <input type="text" name="filters[keywords][]"
                                    class="form-control" placeholder="e.g. Software Engineer">
                                <button type="button" class="btn btn-outline-danger btn-remove-kw" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-add-kw"
                            data-target="keywords-list-{{ $tid }}"
                            data-count-badge="kw-count-badge-{{ $tid }}">
                            <i class="fas fa-plus me-1"></i> Add Keyword
                        </button>
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-magic me-1"></i> Quick Add
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:260px;max-height:380px;overflow-y:auto">
                                <h6 class="dropdown-header">Click a group to add its keywords</h6>
                                @php
                                $kwPresets = [
                                    ['label' => '🎓 Grade 12 / Entry Level', 'keywords' => ['grade 12', 'grade twelve', 'form five', 'O level', 'GCSE', 'school leaver', 'entry level', 'no experience required', 'minimum qualification', 'junior', 'trainee']],
                                    ['label' => '🎓 Intern / Attachment', 'keywords' => ['intern', 'internship', 'graduate trainee', 'attachment', 'industrial attachment', 'graduate program', 'apprentice', 'trainee', 'vacation work']],
                                    ['label' => '⏰ Part Time / Casual', 'keywords' => ['part time', 'part-time', 'casual', 'weekend', 'evening', 'flexible hours', 'temporary', 'contract', 'freelance', 'remote']],
                                    ['label' => '🧹 Service & Hospitality', 'keywords' => ['waiter', 'waitress', 'bartender', 'cleaner', 'housekeeper', 'domestic worker', 'caretaker', 'cook', 'kitchen assistant', 'hotel staff']],
                                    ['label' => '🔒 Security & General Labour', 'keywords' => ['security guard', 'security officer', 'driver', 'gardener', 'general hand', 'labourer', 'casual worker', 'messenger', 'forklift operator']],
                                    ['label' => '💰 Accounting & Finance', 'keywords' => ['accountant', 'auditor', 'bookkeeper', 'finance officer', 'accounts clerk', 'financial analyst', 'cashier', 'payroll officer', 'credit analyst']],
                                    ['label' => '🏦 Banking & Insurance', 'keywords' => ['bank teller', 'banking officer', 'relationship manager', 'underwriter', 'claims officer', 'insurance agent', 'banker']],
                                    ['label' => '🏥 Nursing & Health', 'keywords' => ['nurse', 'nursing officer', 'clinical officer', 'midwife', 'pharmacist', 'doctor', 'health worker', 'radiographer', 'physiotherapist', 'medical officer']],
                                    ['label' => '💻 IT & Technology', 'keywords' => ['software developer', 'programmer', 'IT officer', 'systems administrator', 'web developer', 'data analyst', 'network engineer', 'database administrator', 'ICT officer']],
                                    ['label' => '📚 Education & Teaching', 'keywords' => ['teacher', 'lecturer', 'tutor', 'school administrator', 'early childhood', 'education officer', 'head teacher']],
                                    ['label' => '⚙️ Engineering', 'keywords' => ['engineer', 'civil engineer', 'electrical engineer', 'mechanical engineer', 'structural engineer', 'project manager', 'quantity surveyor', 'site engineer']],
                                    ['label' => '👥 HR & Administration', 'keywords' => ['human resources', 'HR officer', 'HR manager', 'recruitment officer', 'administrative officer', 'secretary', 'receptionist', 'office manager']],
                                    ['label' => '📣 Sales & Marketing', 'keywords' => ['sales representative', 'marketing officer', 'business development', 'sales executive', 'brand ambassador', 'sales manager', 'digital marketing']],
                                    ['label' => '⚖️ Legal', 'keywords' => ['lawyer', 'advocate', 'legal officer', 'paralegal', 'legal assistant', 'compliance officer', 'attorney']],
                                    ['label' => '🚚 Supply Chain & Logistics', 'keywords' => ['procurement officer', 'supply chain', 'logistics officer', 'warehouse officer', 'inventory manager', 'purchasing officer', 'stores officer']],
                                ];
                                @endphp
                                @foreach($kwPresets as $preset)
                                <button type="button"
                                    class="dropdown-item small py-2 btn-kw-preset"
                                    data-target="keywords-list-{{ $tid }}"
                                    data-count-badge="kw-count-badge-{{ $tid }}"
                                    data-keywords="{{ json_encode($preset['keywords']) }}">
                                    {{ $preset['label'] }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-2">

        {{-- ── Companies ──────────────────────────────────────────── --}}
        <div class="mb-2">
            <button type="button"
                class="btn btn-link text-decoration-none text-dark p-0 d-flex align-items-center gap-2 w-100 collapse-toggle-btn"
                data-bs-toggle="collapse" data-bs-target="#collapse-companies-{{ $tid }}"
                aria-expanded="false">
                <i class="fas fa-building text-muted" style="width:14px"></i>
                <span class="fw-semibold small">Companies</span>
                <span class="badge bg-secondary text-white ms-1 co-count-badge-{{ $tid }}">{{ count($savedCompanyKeywords) }}</span>
                <i class="fas fa-chevron-down ms-auto text-muted small collapse-chevron"></i>
            </button>
            <div class="collapse" id="collapse-companies-{{ $tid }}">
                <div class="pt-2 ps-4">
                    <div class="text-muted small mb-2">Add one or more company names — only jobs from matching companies will be sent.</div>
                    <div id="company-list-{{ $tid }}">
                        @if(!empty($savedCompanyKeywords))
                            @foreach($savedCompanyKeywords as $ck)
                            <div class="input-group input-group-sm mb-1 company-kw-row">
                                <input type="text" name="filters[company_keywords][]"
                                    class="form-control" value="{{ $ck }}"
                                    placeholder="e.g. Zambia National Commercial Bank">
                                <button type="button" class="btn btn-outline-danger btn-remove-company-kw" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @endforeach
                        @else
                            <div class="input-group input-group-sm mb-1 company-kw-row">
                                <input type="text" name="filters[company_keywords][]"
                                    class="form-control" placeholder="e.g. Zambia National Commercial Bank">
                                <button type="button" class="btn btn-outline-danger btn-remove-company-kw" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-1 btn-add-company-kw"
                        data-target="company-list-{{ $tid }}"
                        data-count-badge="co-count-badge-{{ $tid }}">
                        <i class="fas fa-plus me-1"></i> Add Company
                    </button>
                </div>
            </div>
        </div>
        <hr class="my-2">

        {{-- ── Country + City/Province ─────────────────────────────────────── --}}
        <div class="mb-2">
            <button type="button"
                class="btn btn-link text-decoration-none text-dark p-0 d-flex align-items-center gap-2 w-100 collapse-toggle-btn"
                data-bs-toggle="collapse" data-bs-target="#collapse-countries-{{ $tid }}"
                aria-expanded="false">
                <i class="fas fa-globe text-muted" style="width:14px"></i>
                <span class="fw-semibold small">Country / Location</span>
                <span class="badge bg-secondary text-white ms-1 country-count-badge-{{ $tid }}">{{ count($selCountries) }} selected</span>
                <i class="fas fa-chevron-down ms-auto text-muted small collapse-chevron"></i>
            </button>
            <div class="collapse" id="collapse-countries-{{ $tid }}">
                <div class="pt-2 ps-4">

                    {{-- Country checkboxes --}}
                    <div class="d-flex gap-2 mb-2">
                        <input type="text" class="form-control form-control-sm filter-search"
                            data-target="countries-box-{{ $tid }}"
                            placeholder="Search countries…">
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-select-all-check"
                            data-target="countries-box-{{ $tid }}"
                            data-count-badge="country-count-badge-{{ $tid }}">All</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-deselect-all-check"
                            data-target="countries-box-{{ $tid }}"
                            data-count-badge="country-count-badge-{{ $tid }}">None</button>
                    </div>
                    <div class="border rounded p-2 mb-3" style="max-height:160px;overflow-y:auto" id="countries-box-{{ $tid }}">
                        <div class="row g-0">
                            @forelse($countries as $countryId => $countryName)
                                <div class="col-md-4 col-6 checkable-item">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            name="filters[country_ids][]"
                                            value="{{ $countryId }}"
                                            id="{{ $tid }}-country-{{ $countryId }}"
                                            {{ in_array((string)$countryId, $selCountries) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="{{ $tid }}-country-{{ $countryId }}">{{ $countryName }}</label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-muted small">No countries found.</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- City / Province text search (searches the address field) --}}
                    <label class="form-label small fw-semibold mb-1">
                        City / Province <span class="text-muted fw-normal">(optional)</span>
                    </label>
                    <input type="text"
                        name="filters[location_keyword]"
                        class="form-control form-control-sm"
                        value="{{ old('filters.location_keyword', $f['location_keyword'] ?? '') }}"
                        placeholder="e.g. Lusaka, Copperbelt, Ndola, Nairobi…">
                    <div class="form-text">Searches the job's location text — works even when city data is not structured.</div>

                </div>
            </div>
        </div>
        <hr class="my-2">

        {{-- ── Job Types ───────────────────────────────────────────── --}}
        <div class="mb-2">
            <div class="d-flex align-items-center gap-1">
                <button type="button"
                    class="btn btn-link text-decoration-none text-dark p-0 d-flex align-items-center gap-2 flex-grow-1 collapse-toggle-btn"
                    data-bs-toggle="collapse" data-bs-target="#collapse-jobtypes-{{ $tid }}"
                    aria-expanded="false">
                    <i class="fas fa-briefcase text-muted" style="width:14px"></i>
                    <span class="fw-semibold small">Job Types</span>
                    <span class="badge bg-secondary text-white ms-1 jt-count-badge-{{ $tid }}">{{ count($selJobTypes) }} selected</span>
                    <i class="fas fa-chevron-down ms-auto text-muted small collapse-chevron"></i>
                </button>
                <button type="button"
                    class="btn btn-outline-danger btn-sm py-0 px-2 flex-shrink-0 btn-deselect-all-check"
                    data-target="jobtypes-box-{{ $tid }}"
                    data-count-badge="jt-count-badge-{{ $tid }}"
                    title="Clear all job types"
                    style="font-size:.7rem;{{ count($selJobTypes) ? '' : 'display:none' }}">
                    <i class="fas fa-times me-1"></i>Clear
                </button>
            </div>
            <div class="collapse" id="collapse-jobtypes-{{ $tid }}">
                <div class="pt-2 ps-4">
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-select-all-check"
                            data-target="jobtypes-box-{{ $tid }}"
                            data-count-badge="jt-count-badge-{{ $tid }}">Select All</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-deselect-all-check"
                            data-target="jobtypes-box-{{ $tid }}"
                            data-count-badge="jt-count-badge-{{ $tid }}">None</button>
                    </div>
                    <div class="border rounded p-2" style="max-height:160px;overflow-y:auto" id="jobtypes-box-{{ $tid }}">
                        @forelse($jobTypes as $typeId => $typeName)
                            <div class="form-check checkable-item">
                                <input class="form-check-input" type="checkbox"
                                    name="filters[job_type_ids][]"
                                    value="{{ $typeId }}"
                                    id="{{ $tid }}-type-{{ $typeId }}"
                                    {{ in_array((string)$typeId, $selJobTypes) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="{{ $tid }}-type-{{ $typeId }}">{{ $typeName }}</label>
                            </div>
                        @empty
                            <span class="text-muted small">No job types found.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-2">

        {{-- ── Categories ──────────────────────────────────────────── --}}
        <div class="mb-2">
            <div class="d-flex align-items-center gap-1">
                <button type="button"
                    class="btn btn-link text-decoration-none text-dark p-0 d-flex align-items-center gap-2 flex-grow-1 collapse-toggle-btn"
                    data-bs-toggle="collapse" data-bs-target="#collapse-categories-{{ $tid }}"
                    aria-expanded="false">
                    <i class="fas fa-tags text-muted" style="width:14px"></i>
                    <span class="fw-semibold small">Categories</span>
                    <span class="badge bg-secondary text-white ms-1 cat-count-badge-{{ $tid }}">{{ count($selCategories) }} selected</span>
                    <i class="fas fa-chevron-down ms-auto text-muted small collapse-chevron"></i>
                </button>
                <button type="button"
                    class="btn btn-outline-danger btn-sm py-0 px-2 flex-shrink-0 btn-deselect-all-check"
                    data-target="categories-box-{{ $tid }}"
                    data-count-badge="cat-count-badge-{{ $tid }}"
                    title="Clear all categories"
                    style="font-size:.7rem;{{ count($selCategories) ? '' : 'display:none' }}">
                    <i class="fas fa-times me-1"></i>Clear
                </button>
            </div>
            <div class="collapse" id="collapse-categories-{{ $tid }}">
                <div class="pt-2 ps-4">
                    <div class="d-flex gap-2 mb-2">
                        <input type="text" class="form-control form-control-sm filter-search"
                            data-target="categories-box-{{ $tid }}"
                            placeholder="Search categories…">
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-select-all-check"
                            data-target="categories-box-{{ $tid }}"
                            data-count-badge="cat-count-badge-{{ $tid }}">All</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-deselect-all-check"
                            data-target="categories-box-{{ $tid }}"
                            data-count-badge="cat-count-badge-{{ $tid }}">None</button>
                    </div>
                    <div class="border rounded p-2" style="max-height:180px;overflow-y:auto" id="categories-box-{{ $tid }}">
                        <div class="row g-0">
                            @forelse($categories as $catId => $catName)
                                <div class="col-md-4 col-6 checkable-item">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            name="filters[category_ids][]"
                                            value="{{ $catId }}"
                                            id="{{ $tid }}-cat-{{ $catId }}"
                                            {{ in_array((string)$catId, $selCategories) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="{{ $tid }}-cat-{{ $catId }}">{{ $catName }}</label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-muted small">No categories found.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /tab-filters --}}

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TAB 3 — PACKAGE & CV                                          --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="tab-pane fade" id="tab-package-{{ $tid }}" role="tabpanel">

        {{-- Duration (create only) --}}
        @if(!$alert)
        <h6 class="fw-semibold mb-3">Subscription Duration</h6>
        <div class="row g-3 mb-4">
            @foreach(\Botble\JobBoard\Models\CandidateAlert::$durations as $days => $info)
                <div class="col-md-4">
                    <div class="form-check h-100">
                        <input class="form-check-input visually-hidden" type="radio"
                            name="duration_days"
                            id="{{ $tid }}-duration-{{ $days }}"
                            value="{{ $days }}"
                            {{ old('duration_days', 60) == $days ? 'checked' : '' }}>
                        <label class="form-check-label d-block border rounded p-3 h-100 duration-card {{ $days == 60 ? 'border-success' : 'border-secondary border-opacity-25' }}"
                            for="{{ $tid }}-duration-{{ $days }}"
                            style="cursor:pointer;transition:all .15s">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold fs-5">{{ $info['label'] }}</span>
                                <span class="badge {{ $info['badge'] }} fs-6">K{{ number_format($info['price'], 0) }}</span>
                            </div>
                            @if($days == 60)
                                <div class="text-success small fw-semibold"><i class="fas fa-star me-1"></i>Best Value</div>
                            @elseif($days == 30)
                                <div class="text-muted small">Standard plan</div>
                            @else
                                <div class="text-muted small">Short-term trial</div>
                            @endif
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
        @else
        @php
            $durInfo = \Botble\JobBoard\Models\CandidateAlert::$durations[$alert->duration_days] ?? ['label' => $alert->duration_days.'d', 'badge' => 'bg-secondary text-white'];
        @endphp

        {{-- Current package summary --}}
        <h6 class="fw-semibold mb-2">Current Package</h6>
        <div class="alert {{ $alert->status === 'expired' ? 'alert-danger' : 'alert-light' }} border d-flex align-items-center gap-3 mb-3">
            <i class="fas fa-calendar-check {{ $alert->status === 'expired' ? 'text-danger' : 'text-primary' }} fs-4"></i>
            <div class="flex-grow-1">
                <span class="badge {{ $durInfo['badge'] }}">{{ $durInfo['label'] }}</span>
                &nbsp;K{{ number_format($alert->price, 0) }}&nbsp;·&nbsp;
                @if($alert->status === 'expired')
                    <span class="text-danger fw-semibold"><i class="fas fa-times-circle me-1"></i>Expired {{ $alert->expires_at?->format('d M Y') }}</span>
                @else
                    Expires <strong>{{ $alert->expires_at?->format('d M Y') ?? 'N/A' }}</strong>
                    &nbsp;<span class="text-muted">({{ $alert->daysRemaining() }}d left)</span>
                @endif
            </div>
        </div>

        {{-- Upgrade / Renew toggle --}}
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch"
                id="{{ $tid }}-upgrade-toggle"
                onchange="document.getElementById('{{ $tid }}-upgrade-panel').style.display = this.checked ? 'block' : 'none'">
            <label class="form-check-label fw-semibold" for="{{ $tid }}-upgrade-toggle">
                {{ $alert->status === 'expired' ? '🔄 Renew Subscription' : '⬆️ Upgrade / Renew Package' }}
            </label>
        </div>

        {{-- Upgrade panel (hidden until toggle is on) --}}
        <div id="{{ $tid }}-upgrade-panel" style="display:none">
            <h6 class="fw-semibold mb-2 text-primary">Select New Package</h6>
            <div class="row g-3 mb-3">
                @foreach(\Botble\JobBoard\Models\CandidateAlert::$durations as $days => $info)
                    <div class="col-md-4">
                        <div class="form-check h-100">
                            <input class="form-check-input visually-hidden" type="radio"
                                name="duration_days"
                                id="{{ $tid }}-duration-{{ $days }}"
                                value="{{ $days }}"
                                {{ $alert->duration_days == $days ? 'checked' : '' }}>
                            <label class="form-check-label d-block border rounded p-3 h-100 duration-card {{ $days == $alert->duration_days ? 'border-primary' : 'border-secondary border-opacity-25' }}"
                                for="{{ $tid }}-duration-{{ $days }}"
                                style="cursor:pointer;transition:all .15s">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold fs-5">{{ $info['label'] }}</span>
                                    <span class="badge {{ $info['badge'] }} fs-6">K{{ number_format($info['price'], 0) }}</span>
                                </div>
                                @if($days == $alert->duration_days)
                                    <div class="text-primary small fw-semibold"><i class="fas fa-check me-1"></i>Current plan</div>
                                @elseif($days == 60)
                                    <div class="text-success small fw-semibold"><i class="fas fa-star me-1"></i>Best Value</div>
                                @elseif($days == 30)
                                    <div class="text-muted small">Standard plan</div>
                                @else
                                    <div class="text-muted small">Short-term</div>
                                @endif
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Extend from --}}
            <h6 class="fw-semibold mb-2">Start Date</h6>
            <div class="d-flex flex-column gap-2 mb-3">
                <div class="form-check border rounded p-3">
                    <input class="form-check-input" type="radio" name="extend_from"
                        id="{{ $tid }}-extend-today" value="today" checked>
                    <label class="form-check-label" for="{{ $tid }}-extend-today">
                        <span class="fw-semibold">From today</span>
                        <span class="text-muted small d-block">
                            Expires {{ now()->addDays($alert->duration_days)->format('d M Y') }} ({{ $alert->duration_days }} days from today)
                        </span>
                    </label>
                </div>
                <div class="form-check border rounded p-3">
                    <input class="form-check-input" type="radio" name="extend_from"
                        id="{{ $tid }}-extend-original" value="original">
                    <label class="form-check-label" for="{{ $tid }}-extend-original">
                        <span class="fw-semibold">From original activation date</span>
                        <span class="text-muted small d-block">
                            Activated {{ $alert->activated_at?->format('d M Y') ?? 'N/A' }} —
                            expires {{ $alert->activated_at?->copy()->addDays($alert->duration_days)->format('d M Y') ?? 'N/A' }}
                        </span>
                    </label>
                </div>
            </div>

            <div class="alert alert-info py-2 px-3 small mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Upgrading will reactivate the alert if expired, reset expiry notifications, and update the price.
            </div>
        </div>
        @endif

        {{-- CV Upload & AI Analysis --}}
        <h6 class="fw-semibold mb-2"><i class="fas fa-brain me-1 text-primary"></i> AI-Powered CV Analysis</h6>
        <p class="text-muted small mb-3">
            Upload the candidate's CV — Claude AI will read it and automatically suggest the best job filters for the Filters tab.
        </p>
        <div class="d-flex align-items-start gap-3 flex-wrap mb-3">
            <div>
                <input type="file" name="cv_file" id="{{ $tid }}-cv-file"
                    class="form-control form-control-sm cv-upload-input"
                    accept=".pdf,.doc,.docx,.txt"
                    data-analyze-url="{{ route('job-board.candidate-alerts.analyze-cv') }}"
                    data-prefix="{{ $prefix }}"
                    style="max-width:300px">
                <div class="form-text">PDF, DOC, DOCX or TXT · max 10 MB</div>
            </div>
            <button type="button" class="btn btn-primary btn-sm btn-analyze-cv mt-1" data-prefix="{{ $prefix }}" disabled>
                <i class="fas fa-magic me-1"></i> Analyse with AI
            </button>
        </div>

        @if($alert && $alert->cv_path)
        <div class="d-flex align-items-center gap-2 text-muted small border rounded px-3 py-2 mb-3">
            <i class="fas fa-file-alt text-primary"></i>
            CV on file: <strong>{{ basename($alert->cv_path) }}</strong>
            <span class="ms-1">(upload a new file above to replace it)</span>
        </div>
        @endif

        {{-- Analysis result panel --}}
        <div id="{{ $prefix }}-analysis-result" class="{{ $cvAnalysis ? '' : 'd-none' }} p-3 bg-light border rounded">
            @if($cvAnalysis)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fas fa-check-circle text-success"></i>
                    <strong class="small">Last Analysis</strong>
                    <span class="badge bg-{{ ($cvAnalysis['confidence'] ?? 0) >= 80 ? 'success' : 'warning' }} text-white ms-auto">
                        {{ $cvAnalysis['confidence'] ?? '?' }}% confidence
                    </span>
                </div>
                @if(!empty($cvAnalysis['summary']))
                    <p class="text-muted small mb-2">{{ $cvAnalysis['summary'] }}</p>
                @endif
                <div class="d-flex gap-1 flex-wrap mb-2">
                    @foreach((array)($cvAnalysis['keywords'] ?? ($cvAnalysis['keyword'] ? [$cvAnalysis['keyword']] : [])) as $kw)
                        <span class="badge bg-dark text-white small"><i class="fas fa-search me-1"></i>{{ $kw }}</span>
                    @endforeach
                    @foreach($cvAnalysis['job_type_names'] ?? [] as $tn)
                        <span class="badge bg-primary text-white small">{{ $tn }}</span>
                    @endforeach
                    @foreach($cvAnalysis['category_names'] ?? [] as $cn)
                        <span class="badge bg-secondary text-white small">{{ $cn }}</span>
                    @endforeach
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm btn-apply-analysis"
                    data-prefix="{{ $prefix }}" data-analysis='@json($cvAnalysis)'>
                    <i class="fas fa-check me-1"></i> Apply These Filters
                </button>
            @endif
        </div>

    </div>{{-- /tab-package --}}

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TAB 4 — NOTES                                                 --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="tab-pane fade" id="tab-notes-{{ $tid }}" role="tabpanel">
        <label class="form-label fw-semibold">Internal Notes <span class="text-muted small">(optional)</span></label>
        <textarea name="notes" class="form-control" rows="5"
            placeholder="Any internal notes about this candidate, their preferences, or subscription details…">{{ old('notes', $alert?->notes) }}</textarea>
        <div class="form-text">These notes are admin-only and never shared with the candidate.</div>
    </div>

</div>{{-- /tab-content --}}

{{-- ── Chevron rotation CSS (inline to avoid style.css dependency) ────────── --}}
<style>
    .collapse-toggle-btn[aria-expanded="true"] .collapse-chevron { transform: rotate(180deg); }
    .collapse-chevron { transition: transform .2s; }
    .duration-card:hover { border-color: #0d6efd !important; background: #f8f9ff; }
    input[type="radio"]:checked + .duration-card { border-color: #0d6efd !important; background: #f0f4ff; box-shadow: 0 0 0 2px #0d6efd40; }
    .btn-kw-preset:hover { background: #f0f4ff; }
</style>
<script>
(function () {

    // ── Filter preview eye button ─────────────────────────────────────────────
    $(document).on('click', '.btn-preview-filters', function () {
        const $btn = $(this);
        const url  = $btn.data('url');
        const tid  = $btn.data('tid');

        // Collect current filter values from the form
        const $form  = $btn.closest('.tab-content').parent();

        const keywords = $('#keywords-list-' + tid + ' input[name="filters[keywords][]"]')
            .map(function () { return $(this).val().trim(); }).get()
            .filter(v => v !== '');

        const companyKeywords = $('#company-list-' + tid + ' input[name="filters[company_keywords][]"]')
            .map(function () { return $(this).val().trim(); }).get()
            .filter(v => v !== '');

        const countryIds = $('#countries-box-' + tid + ' input[type="checkbox"]:checked')
            .map(function () { return $(this).val(); }).get();

        const jobTypeIds = $('#jobtypes-box-' + tid + ' input[type="checkbox"]:checked')
            .map(function () { return $(this).val(); }).get();

        const categoryIds = $('#categories-box-' + tid + ' input[type="checkbox"]:checked')
            .map(function () { return $(this).val(); }).get();

        const experienceId = $form.find('select[name="filters[job_experience_id]"]').val() || '';
        const locationKw   = $form.find('input[name="filters[location_keyword]"]').val() || '';

        const filters = {};
        if (keywords.length)        filters['keywords']          = keywords;
        if (companyKeywords.length) filters['company_keywords']  = companyKeywords;
        if (countryIds.length)      filters['country_ids']       = countryIds;
        if (jobTypeIds.length)      filters['job_type_ids']      = jobTypeIds;
        if (categoryIds.length)     filters['category_ids']      = categoryIds;
        if (experienceId)           filters['job_experience_id'] = experienceId;
        if (locationKw.trim())      filters['location_keyword']  = locationKw.trim();

        const $panel = $('#filter-preview-panel-' + tid);
        const $body  = $('#filter-preview-body-' + tid);
        const $label = $('#filter-preview-label-' + tid);

        $panel.removeClass('d-none');
        $label.text('Loading…');
        $body.html('<div class="p-3 text-center text-muted"><i class="fas fa-spinner fa-spin me-1"></i> Checking matching jobs…</div>');
        $btn.prop('disabled', true);

        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ filters })
        })
        .then(r => r.json())
        .then(resp => {
            const jobs  = resp.data || [];
            const total = resp.total || 0;
            $label.html(total + ' matching job' + (total !== 1 ? 's' : '') + ' found'
                + (total > 200 ? ' <span class="text-muted">(showing first 200)</span>' : '')
                + ' &nbsp;<span class="text-muted small fw-normal">— click a row to see why it matched</span>');

            if (!jobs.length) {
                $body.html('<div class="p-3 text-center text-muted">No matching jobs with the current filters.</div>');
                return;
            }

            let html = '<table class="table table-sm table-hover mb-0 align-middle fp-jobs-table">'
                + '<thead class="table-light"><tr>'
                + '<th style="width:30px">#</th><th>Job Title</th><th>Company</th><th>Location</th><th style="width:85px">Posted</th>'
                + '</tr></thead><tbody>';

            jobs.forEach((job, i) => {
                const reasonsJson = escFp(JSON.stringify(job.match_reasons || []));
                html += `<tr class="fp-job-row" style="cursor:pointer" data-reasons="${reasonsJson}" title="Click to see why this job matched">
                    <td class="text-muted text-center">${i + 1}</td>
                    <td class="fw-semibold" style="max-width:210px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escFp(job.name)}</td>
                    <td class="text-muted small">${escFp(job.company)}</td>
                    <td class="text-muted small">${escFp(job.location || job.country || '')}</td>
                    <td class="text-muted small text-nowrap">${escFp(job.created)}</td>
                </tr>
                <tr class="fp-reason-row d-none">
                    <td colspan="5" class="p-0"></td>
                </tr>`;
            });

            html += '</tbody></table>';
            $body.html(html);
        })
        .catch(() => {
            $body.html('<div class="p-3 text-center text-danger">Preview failed. Try again.</div>');
            $label.text('Error');
        })
        .finally(() => $btn.prop('disabled', false));
    });

    // Close filter preview panel
    $(document).on('click', '.btn-close-filter-preview', function () {
        const tid = $(this).data('tid');
        $('#filter-preview-panel-' + tid).addClass('d-none');
    });

    // Expand / collapse match-reason row when a job row is clicked
    $(document).on('click', '.fp-job-row', function () {
        const $jobRow    = $(this);
        const $reasonRow = $jobRow.next('.fp-reason-row');
        const isOpen     = !$reasonRow.hasClass('d-none');

        // Close all other open reason rows in same table
        $jobRow.closest('tbody').find('.fp-reason-row').addClass('d-none');
        $jobRow.closest('tbody').find('.fp-job-row').css('background', '');

        if (isOpen) return; // was already open — just close it

        $jobRow.css('background', '#f0f7ff');

        let reasons = [];
        try { reasons = JSON.parse($jobRow.attr('data-reasons') || '[]'); } catch (_) {}

        if (!reasons.length) {
            $reasonRow.find('td').html(
                '<div class="px-3 py-2 text-muted small"><i class="fas fa-info-circle me-1"></i>No specific filter was the deciding factor (all-jobs match).</div>'
            );
            $reasonRow.removeClass('d-none');
            return;
        }

        const typeColors = {
            keyword:  { bg: '#fff3cd', border: '#ffc107', icon: 'fas fa-search',          label: 'Keyword' },
            company:  { bg: '#d1ecf1', border: '#0dcaf0', icon: 'fas fa-building',        label: 'Company' },
            job_type: { bg: '#cce5ff', border: '#0d6efd', icon: 'fas fa-briefcase',       label: 'Job Type' },
            category: { bg: '#d4edda', border: '#198754', icon: 'fas fa-tags',            label: 'Category' },
            country:  { bg: '#e2d9f3', border: '#6f42c1', icon: 'fas fa-globe',           label: 'Country' },
            location: { bg: '#fde8d8', border: '#fd7e14', icon: 'fas fa-map-marker-alt',  label: 'Location' },
        };

        let html = '<div class="px-3 py-2" style="background:#f8faff;border-top:2px solid #0d6efd22">';
        html += '<div class="small fw-semibold text-primary mb-2"><i class="fas fa-info-circle me-1"></i>Why this job matched:</div>';
        html += '<div class="d-flex flex-wrap gap-2">';

        reasons.forEach(r => {
            const cfg = typeColors[r.type] || { bg: '#f8f9fa', border: '#6c757d', icon: 'fas fa-check', label: r.type };
            const kwBadge = r.keyword
                ? `<span class="badge" style="background:#333;color:#fff;font-size:.7rem">${escFp(r.keyword)}</span> `
                : '';
            const snippet  = r.snippet ? escFp(r.snippet) : '';
            const highlighted = r.keyword
                ? snippet.replace(new RegExp('(' + escRegex(r.keyword) + ')', 'gi'), '<mark style="background:#ffe066;padding:0 1px;border-radius:2px">$1</mark>')
                : snippet;

            html += `<div class="rounded border px-2 py-1" style="background:${cfg.bg};border-color:${cfg.border}!important;max-width:340px">
                <div class="d-flex align-items-center gap-1 mb-1">
                    <i class="${cfg.icon} text-secondary" style="font-size:.7rem;width:12px"></i>
                    <span class="fw-semibold" style="font-size:.72rem;color:#333">${escFp(r.field)}</span>
                    ${kwBadge}
                </div>
                <div class="text-muted" style="font-size:.75rem;word-break:break-word">${highlighted}</div>
            </div>`;
        });

        html += '</div></div>';

        $reasonRow.find('td').html(html);
        $reasonRow.removeClass('d-none');
    });

    function escRegex(str) {
        return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function escFp(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Quick-Add keyword preset handler (delegated so it works in dynamically shown modals)
    $(document).on('click', '.btn-kw-preset', function () {
        const keywords   = $(this).data('keywords') || [];
        const listId     = $(this).data('target');
        const badgeClass = $(this).data('count-badge');
        const $list      = $('#' + listId);

        const existing = $list.find('input[name="filters[keywords][]"]').map(function () {
            return $(this).val().trim().toLowerCase();
        }).get();

        let added = 0;
        keywords.forEach(function (kw) {
            if (existing.includes(kw.trim().toLowerCase())) return;

            // Check if there is an empty row we can fill first
            const $empty = $list.find('input[name="filters[keywords][]"]').filter(function () {
                return $(this).val().trim() === '';
            }).first();

            if ($empty.length) {
                $empty.val(kw);
            } else {
                const $row = $('<div class="input-group input-group-sm mb-1 keyword-row">' +
                    '<input type="text" name="filters[keywords][]" class="form-control" value="' + kw + '">' +
                    '<button type="button" class="btn btn-outline-danger btn-remove-kw" title="Remove"><i class="fas fa-times"></i></button>' +
                    '</div>');
                $list.append($row);
            }

            existing.push(kw.trim().toLowerCase());
            added++;
        });

        // Update badge
        const count = $list.find('input[name="filters[keywords][]"]').filter(function () {
            return $(this).val().trim() !== '';
        }).length;
        $('.' + badgeClass).text(count);

        if (added > 0) {
            // Ensure keywords section is expanded
            const collapseId = listId.replace('keywords-list-', 'collapse-keywords-');
            const $collapse  = $('#' + collapseId);
            if ($collapse.length && !$collapse.hasClass('show')) {
                new bootstrap.Collapse($collapse[0], { show: true });
            }
        }
    });
})();
</script>
