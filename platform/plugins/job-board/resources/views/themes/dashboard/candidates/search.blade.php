@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
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
    @if(! $canRevealFree && $revealCost > 0)
        <x-core::alert type="info" class="mb-3">
            <x-core::icon name="ti ti-coin" class="me-1" />
            Revealing a contact costs <strong>{{ $revealCost }} credit(s)</strong>.
            You have <strong>{{ $account->credits }}</strong> credit(s).
            <a href="{{ route('public.account.packages') }}" class="ms-2">Buy credits</a> or
            <a href="{{ route('public.account.subscription.index') }}">upgrade your plan</a> for unlimited reveals.
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
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-3">
            @foreach($candidates as $candidate)
                @php $alreadyRevealed = in_array($candidate->id, $revealedIds); @endphp
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <img src="{{ $candidate->avatar_thumb_url }}" alt="{{ $candidate->name }}"
                                     class="avatar avatar-md rounded-circle">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $candidate->name }}</div>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
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
                                <a href="{{ $candidate->url }}" target="_blank" class="btn btn-sm btn-ghost-secondary flex-shrink-0"
                                   title="View public profile">
                                    <x-core::icon name="ti ti-external-link" />
                                </a>
                            </div>

                            {{-- Profile details --}}
                            <div class="text-muted small mb-2">
                                @if($candidate->experience_years !== null)
                                    <span class="me-2">
                                        <x-core::icon name="ti ti-briefcase" class="me-1" />
                                        {{ \Botble\JobBoard\Models\Account::experienceYearsOptions()[$candidate->experience_years] ?? '' }}
                                    </span>
                                @endif
                                @if($candidate->education_level)
                                    <span class="me-2">
                                        <x-core::icon name="ti ti-school" class="me-1" />
                                        {{ \Botble\JobBoard\Models\Account::educationLevelOptions()[$candidate->education_level] ?? '' }}
                                    </span>
                                @endif
                                @if($candidate->desired_salary_from || $candidate->desired_salary_to)
                                    <span>
                                        <x-core::icon name="ti ti-coin" class="me-1" />
                                        {{ $candidate->desired_salary_from ? number_format($candidate->desired_salary_from) : '?' }}
                                        –
                                        {{ $candidate->desired_salary_to ? number_format($candidate->desired_salary_to) : '?' }}
                                    </span>
                                @endif
                            </div>

                            @if($candidate->description)
                                <p class="text-muted small mb-2 lh-sm">{{ \Illuminate\Support\Str::limit(strip_tags($candidate->description), 100) }}</p>
                            @endif

                            @if($candidate->favoriteSkills->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    @foreach($candidate->favoriteSkills->take(5) as $skill)
                                        <span class="badge bg-blue-lt">{{ $skill->name }}</span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Contact reveal --}}
                            <div class="candidate-contact-block mt-auto" data-candidate-id="{{ $candidate->id }}">
                                @if($alreadyRevealed)
                                    <div class="small revealed-info">
                                        @if($candidate->phone)<div class="text-success"><x-core::icon name="ti ti-phone" class="me-1" />{{ $candidate->phone }}</div>@endif
                                        <div class="text-primary"><x-core::icon name="ti ti-mail" class="me-1" />{{ $candidate->email }}</div>
                                        @if(! $candidate->hide_cv && $candidate->resume)
                                            <a href="{{ $candidate->resumeDownloadUrl }}" target="_blank" class="btn btn-sm btn-outline-success mt-1 w-100">
                                                <x-core::icon name="ti ti-file-download" class="me-1" /> Download CV
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <button type="button"
                                        class="btn btn-sm btn-primary w-100 btn-reveal-contact"
                                        data-reveal-url="{{ route('public.account.cv-reveal.reveal', $candidate->id) }}">
                                        <x-core::icon name="ti ti-lock-open" class="me-1" />
                                        @if($canRevealFree) Reveal Contact @else Reveal ({{ $revealCost }} cr) @endif
                                    </button>
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
                    self.innerHTML = '<i class="ti ti-lock-open me-1"></i>Reveal Contact';
                    Botble.showError((data.message || 'Could not reveal.').replace(/<[^>]+>/g, ''));
                }
            })
            .catch(() => { self.disabled = false; self.innerHTML = '<i class="ti ti-lock-open me-1"></i>Reveal Contact'; });
        });
    });
    </script>
    @endpush
@endsection
