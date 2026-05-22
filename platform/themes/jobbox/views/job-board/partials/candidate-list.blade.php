@php
    $isEmployer    = $isEmployer    ?? (auth('account')->check() && auth('account')->user()->isEmployer());
    $revealedIds   = $revealedIds   ?? [];
    $canRevealFree = $canRevealFree ?? false;
    $revealCost    = $revealCost    ?? (int) setting('cv_reveal_credit_cost', 1);
    $revealUrlBase = $isEmployer ? route('public.account.cv-reveal.reveal', ['candidate' => 0]) : '#';
@endphp

@if($candidates->total())
    @foreach($candidates as $candidate)
        @php
            $alreadyRevealed = in_array($candidate->id, $revealedIds);
            $isStale         = method_exists($candidate, 'isProfileStale') && $candidate->isProfileStale(90);
        @endphp
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card-grid-2 hover-up">
                <div class="card-grid-2-image-left">
                    <div @class(['card-grid-2-image-rd', 'online' => $candidate->available_for_hiring])>
                        <a href="{{ $candidate->url }}">
                            <figure>
                                <img alt="{{ $candidate->name }}" src="{{ $candidate->avatar_thumb_url }}">
                            </figure>
                        </a>
                    </div>
                    <div class="card-profile pt-10">
                        <a href="{{ $candidate->url }}">
                            <h5>{{ $candidate->name }}</h5>
                        </a>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @if($candidate->available_for_hiring)
                                <span class="badge bg-success" style="font-size:10px;">● {{ __('Open to Work') }}</span>
                            @endif
                            @if(isset($candidate->availability) && $candidate->availability && $candidate->availability !== 'not_looking')
                                <span class="badge bg-soft-primary text-primary" style="font-size:10px;">
                                    {{ \Botble\JobBoard\Models\Account::availabilityOptions()[$candidate->availability] ?? $candidate->availability }}
                                </span>
                            @endif
                            @if($isStale)
                                <span class="badge bg-secondary" style="font-size:10px;">{{ __('Inactive 90d+') }}</span>
                            @endif
                        </div>
                        <span class="font-xs color-text-mutted text-truncate d-block mt-1">{{ $candidate->description }}</span>
                    </div>
                </div>
                <div class="card-block-info">
                    @if(isset($candidate->experience_years) && $candidate->experience_years !== null)
                        <div class="font-xs color-text-paragraph-2 mb-1">
                            <i class="fi-rr-briefcase me-1"></i>
                            {{ \Botble\JobBoard\Models\Account::experienceYearsOptions()[$candidate->experience_years] ?? '' }}
                            @if(isset($candidate->education_level) && $candidate->education_level)
                                &nbsp;·&nbsp;{{ \Botble\JobBoard\Models\Account::educationLevelOptions()[$candidate->education_level] ?? '' }}
                            @endif
                        </div>
                    @endif

                    <div class="employers-info align-items-center justify-content-center mt-10">
                        <div class="row justify-content-between align-items-center">
                            <div class="col-md-6">
                                <span class="d-flex align-items-center">
                                    <i class="fi-rr-marker mr-5 ml-0"></i>
                                    <span class="font-sm color-text-mutted text-truncate">
                                        {{ $candidate->state_name ? $candidate->state_name . ',' : null }} {{ $candidate->country?->code }}
                                    </span>
                                </span>
                            </div>
                            @if(JobBoardHelper::isEnabledReview())
                                <div class="col-md-6">
                                    <div class="mt-5">
                                        {!! Theme::partial('rating-star', ['star' => round($candidate->reviews_avg_star ?? 0)]) !!}
                                        <span class="font-xs color-text-mutted ml-5">({{ $candidate->reviews_count ?? 0 }})</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Contact reveal section --}}
                    <div class="mt-10 candidate-contact-block" data-candidate-id="{{ $candidate->id }}">
                        @if(! $isEmployer)
                            <div class="d-flex gap-2 align-items-center">
                                <span class="font-sm" style="filter:blur(4px);user-select:none;pointer-events:none;">+260 9X XXX XXXX</span>
                                <a href="{{ route('public.account.login') }}" class="btn btn-xs btn-outline-primary ms-auto" style="font-size:11px;">
                                    <i class="fi-rr-lock me-1"></i>{{ __('Sign in') }}
                                </a>
                            </div>
                        @elseif($alreadyRevealed)
                            <div class="font-sm revealed-info">
                                @if($candidate->phone)<div><i class="fi-rr-phone me-1 text-success"></i>{{ $candidate->phone }}</div>@endif
                                <div><i class="fi-rr-envelope me-1 text-primary"></i>{{ $candidate->email }}</div>
                                @if(! $candidate->hide_cv && $candidate->resume)
                                    <a href="{{ $candidate->resumeDownloadUrl }}" target="_blank" class="btn btn-xs btn-outline-success mt-1" style="font-size:11px;">
                                        <i class="fi-rr-file me-1"></i>{{ __('Download CV') }}
                                    </a>
                                @endif
                            </div>
                        @else
                            <button type="button"
                                class="btn btn-sm btn-outline-primary w-100 mt-1 btn-reveal-contact"
                                data-reveal-url="{{ str_replace('/0', '/'.$candidate->id, $revealUrlBase) }}">
                                <i class="fi-rr-unlock me-1"></i>
                                @if($canRevealFree)
                                    {{ __('Reveal Contact') }}
                                @else
                                    {{ __('Reveal (:cost cr)', ['cost' => $revealCost]) }}
                                @endif
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    {{ $candidates->withQueryString()->links(Theme::getThemeNamespace('partials.pagination')) }}
@else
    <p class="text-center text-muted w-100 py-5">{{ __('No candidates found matching your filters.') }}</p>
@endif

@once
@push('footer')
<script>
(function() {
    'use strict';
    document.querySelectorAll('.btn-reveal-contact').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url  = this.dataset.revealUrl;
            var self = this;
            self.disabled = true;
            self.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                    'Accept': 'application/json',
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var html = '';
                    if (data.phone) html += '<div><i class="fi-rr-phone me-1 text-success"></i>' + data.phone + '</div>';
                    if (data.email) html += '<div><i class="fi-rr-envelope me-1 text-primary"></i>' + data.email + '</div>';
                    if (data.cv_url) html += '<div class="mt-1"><a href="' + data.cv_url + '" target="_blank" class="btn btn-xs btn-outline-success" style="font-size:11px;"><i class="fi-rr-file me-1"></i>Download CV</a></div>';
                    self.closest('.candidate-contact-block').innerHTML = '<div class="font-sm revealed-info">' + html + '</div>';
                } else {
                    self.disabled = false;
                    self.innerHTML = '<i class="fi-rr-unlock me-1"></i>Reveal Contact';
                    var msg = (data.message || 'Could not reveal contact.').replace(/<[^>]+>/g, '');
                    alert(msg);
                }
            })
            .catch(function() {
                self.disabled = false;
                self.innerHTML = '<i class="fi-rr-unlock me-1"></i>Reveal Contact';
            });
        });
    });
}());
</script>
@endpush
@endonce
