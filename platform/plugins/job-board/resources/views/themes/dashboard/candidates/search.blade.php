@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    <style>
        .candidate-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.12); }
        .btn-reveal-contact:hover,
        .btn-reveal-contact:focus {
            background: #9777fa !important;
            border-color: #9777fa !important;
            color: #fff !important;
        }
    </style>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('public.account.candidates.search') }}" class="row g-2 mb-4">
        <div class="col-md-3">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Name or keyword...">
        </div>
        <div class="col-md-2">
            <input class="form-control" name="skill" value="{{ request('skill') }}" placeholder="Skill (e.g. PHP)">
        </div>
        <div class="col-md-2">
            <input class="form-control" name="location" value="{{ request('location') }}" placeholder="City / Region">
        </div>
        <div class="col-md-2">
            <select class="form-select" name="experience_years">
                @foreach(\Botble\JobBoard\Models\Account::experienceYearsOptions() as $val => $label)
                    <option value="{{ $val }}" @selected(request('experience_years') == $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="education_level">
                @foreach(\Botble\JobBoard\Models\Account::educationLevelOptions() as $val => $label)
                    <option value="{{ $val }}" @selected(request('education_level') == $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-primary w-100" type="submit">
                <x-core::icon name="ti ti-search" />
            </button>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="availability">
                @foreach(\Botble\JobBoard\Models\Account::availabilityOptions() as $val => $label)
                    <option value="{{ $val }}" @selected(request('availability') == $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="salary_min" value="{{ request('salary_min') }}" placeholder="Salary min">
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="salary_max" value="{{ request('salary_max') }}" placeholder="Salary max">
        </div>
        <div class="col-md-5 d-flex align-items-center gap-3">
            <label class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="open_to_work" value="1" @checked(request('open_to_work'))>
                <span class="form-check-label">Open to Work only</span>
            </label>
            @if(request()->hasAny(['q','skill','location','experience_years','education_level','availability','salary_min','salary_max','open_to_work']))
                <a href="{{ route('public.account.candidates.search') }}" class="btn btn-outline-secondary btn-sm">Clear filters</a>
            @endif
        </div>
    </form>

    {{-- Reveal access notice --}}
    @if($hasSubscriptionAccess)
        <x-core::alert type="success" class="mb-3">
            <x-core::icon name="ti ti-crown" class="me-1" />
            <strong>Continental Reach / Talent Scout plan</strong> — unlimited candidate access included with your subscription.
        </x-core::alert>
    @elseif($canReveal)
        <x-core::alert type="info" class="mb-3">
            <x-core::icon name="ti ti-coin" class="me-1" />
            Upgrade your subscription to view all profiles, or reveal one profile for <strong>{{ $revealCost }} credit(s)</strong>.
            You have <strong>{{ $account->credits }}</strong> credit(s).
            <a href="{{ route('public.account.subscription.index') }}" class="btn btn-sm btn-outline-secondary ms-2">Upgrade Plan</a>
        </x-core::alert>
    @else
        <div id="reveal-access-alert"></div>
        <x-core::alert type="danger" class="mb-3">
            <x-core::icon name="ti ti-alert-circle" class="me-1" />
            <strong>Insufficient credits</strong> — you have <strong>{{ $account->credits }}</strong> credit(s) but need <strong>{{ $revealCost }}</strong> to reveal a contact.
            <a href="{{ route('public.account.credits') }}" class="btn btn-sm btn-warning ms-2">Buy Credits</a>
            <a href="{{ route('public.account.subscription.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Upgrade Plan</a>
        </x-core::alert>
    @endif

    @if($candidates->isEmpty())
        <x-core::card>
            <x-core::card.body>
                <div class="empty">
                    <div class="empty-icon"><x-core::icon name="ti ti-users-group" /></div>
                    <p class="empty-title">No candidates found</p>
                    <p class="empty-subtitle text-muted">Try adjusting your search filters.</p>
                </div>
            </x-core::card.body>
        </x-core::card>
    @else
        <div class="text-muted small mb-3">{{ $candidates->total() }} candidate(s) found</div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-4 g-3 mb-3">
            @foreach($candidates as $candidate)
                @php $alreadyRevealed = in_array($candidate->id, $revealedIds); @endphp
                <div class="col">
                    <div class="card h-100 candidate-card" style="transition:box-shadow .15s;">
                        <div class="card-body d-flex flex-column p-3">

                            {{-- Avatar + name --}}
                            <div class="d-flex align-items-start gap-3 mb-2">
                                <div class="position-relative flex-shrink-0">
                                    <img src="{{ $candidate->avatar_thumb_url }}" alt="{{ $candidate->name }}"
                                         class="avatar avatar-lg rounded-circle">
                                    @if($candidate->available_for_hiring)
                                        <span class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-white border-2"
                                              style="width:12px;height:12px;" title="Open to Work"></span>
                                    @endif
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate mb-1">{{ $candidate->name }}</div>
                                    <div class="d-flex flex-wrap gap-1">
                                        @if($candidate->available_for_hiring)
                                            <span class="badge bg-green-lt text-green" style="font-size:10px;">● Open to Work</span>
                                        @endif
                                        @if($candidate->availability && $candidate->availability !== 'not_looking')
                                            <span class="badge bg-blue-lt text-blue" style="font-size:10px;">
                                                {{ \Botble\JobBoard\Models\Account::availabilityOptions()[$candidate->availability] ?? '' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Description --}}
                            @if($candidate->description)
                                <p class="text-muted small mb-2 lh-sm" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ strip_tags($candidate->description) }}</p>
                            @endif

                            {{-- Experience · Education --}}
                            @if($candidate->experience_years !== null || $candidate->education_level)
                                <div class="text-muted small mb-1">
                                    @if($candidate->experience_years !== null)
                                        <span class="me-2"><x-core::icon name="ti ti-briefcase" class="me-1" />{{ \Botble\JobBoard\Models\Account::experienceYearsOptions()[$candidate->experience_years] ?? '' }}</span>
                                    @endif
                                    @if($candidate->education_level)
                                        <span><x-core::icon name="ti ti-school" class="me-1" />{{ \Botble\JobBoard\Models\Account::educationLevelOptions()[$candidate->education_level] ?? '' }}</span>
                                    @endif
                                </div>
                            @endif

                            {{-- Location --}}
                            @if($candidate->address)
                                <div class="text-muted small mb-1">
                                    <x-core::icon name="ti ti-map-pin" class="me-1" />{{ $candidate->address }}
                                </div>
                            @endif

                            {{-- Salary --}}
                            @if($candidate->desired_salary_from || $candidate->desired_salary_to)
                                <div class="text-muted small mb-2">
                                    <x-core::icon name="ti ti-coin" class="me-1" />
                                    {{ $candidate->desired_salary_from ? number_format($candidate->desired_salary_from) : '?' }}
                                    –
                                    {{ $candidate->desired_salary_to ? number_format($candidate->desired_salary_to) : '?' }}
                                </div>
                            @endif

                            {{-- Skills --}}
                            @if($candidate->favoriteSkills->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    @foreach($candidate->favoriteSkills->take(5) as $skill)
                                        <span class="badge bg-blue-lt text-blue" style="font-size:10px;">{{ $skill->name }}</span>
                                    @endforeach
                                    @if($candidate->favoriteSkills->count() > 5)
                                        <span class="badge bg-secondary-lt text-muted" style="font-size:10px;">+{{ $candidate->favoriteSkills->count() - 5 }}</span>
                                    @endif
                                </div>
                            @endif

                            {{-- Contact reveal --}}
                            <div class="candidate-contact-block mt-auto" data-candidate-id="{{ $candidate->id }}">
                                @if($alreadyRevealed)
                                    <div class="small revealed-info">
                                        @if($candidate->phone)<div class="text-success mb-1"><x-core::icon name="ti ti-phone" class="me-1" />{{ $candidate->phone }}</div>@endif
                                        <div class="text-primary mb-1"><x-core::icon name="ti ti-mail" class="me-1" />{{ $candidate->email }}</div>
                                        @if(! $candidate->hide_cv && $candidate->resume)
                                            <a href="{{ $candidate->resumeDownloadUrl }}" target="_blank" class="btn btn-sm btn-outline-success mt-1 w-100">
                                                <x-core::icon name="ti ti-file-download" class="me-1" /> Download CV
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    @php $canRevealContact = $hasSubscriptionAccess || $canRevealFree || $canReveal; @endphp
                                    <button type="button"
                                        class="btn btn-sm w-100 btn-reveal-contact {{ $canRevealContact ? 'btn-primary' : 'btn-secondary' }}"
                                        data-reveal-url="{{ route('public.account.cv-reveal.reveal', $candidate->id) }}"
                                        {{ $canRevealContact ? '' : 'disabled' }}>
                                        <x-core::icon name="ti ti-lock-open" class="me-1" />
                                        @if($hasSubscriptionAccess || $canRevealFree)
                                            Reveal Contact
                                        @else
                                            Reveal ({{ $revealCost }} cr)
                                        @endif
                                    </button>
                                    <a href="{{ $candidate->url }}" target="_blank"
                                       class="btn btn-sm btn-outline-secondary w-100 mt-1" style="font-size:11px;">
                                        <x-core::icon name="ti ti-external-link" class="me-1" />View Profile
                                    </a>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $candidates->links() }}
    @endif

    @push('footer')
    <script>
    document.querySelectorAll('.btn-reveal-contact').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url = this.dataset.revealUrl, self = this;
            self.disabled = true;
            self.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    var html = '';
                    if (data.phone) html += '<div class="text-success"><i class="ti ti-phone me-1"></i>' + data.phone + '</div>';
                    if (data.email) html += '<div class="text-primary"><i class="ti ti-mail me-1"></i>' + data.email + '</div>';
                    if (data.cv_url) html += '<a href="' + data.cv_url + '" target="_blank" class="btn btn-sm btn-outline-success mt-1 w-100"><i class="ti ti-file-download me-1"></i>Download CV</a>';
                    self.closest('.candidate-contact-block').innerHTML = '<div class="small revealed-info">' + html + '</div>';
                } else {
                    self.disabled = false;
                    self.innerHTML = '<i class="ti ti-lock-open me-1"></i>Reveal ({{ $revealCost }} cr)';
                    var alertBox = document.getElementById('reveal-access-alert');
                    if (alertBox) {
                        alertBox.innerHTML = '<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">'
                            + (data.message || 'Could not reveal.')
                            + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                            + '</div>';
                        alertBox.scrollIntoView({behavior: 'smooth', block: 'center'});
                    } else {
                        Botble.showError(data.message || 'Could not reveal.');
                    }
                }
            })
            .catch(() => {
                self.disabled = false;
                self.innerHTML = '<i class="ti ti-lock-open me-1"></i>Reveal ({{ $revealCost }} cr)';
            });
        });
    });
    </script>
    @endpush
@endsection
