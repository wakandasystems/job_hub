@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ __('Talent Pool') }}</h4>
        <span class="text-muted fs-14">{{ __('Wakanda-verified candidates ready to hire. Unlock a profile for :cost credits.', ['cost' => $unlockCost]) }}</span>
    </div>

    @if ($candidates->isEmpty())
        <x-core::card>
            <x-core::card.body class="text-center py-5">
                <p class="text-muted">{{ __('No verified candidates available yet.') }}</p>
            </x-core::card.body>
        </x-core::card>
    @else
        <div class="row g-3">
            @foreach ($candidates as $candidate)
                @php $unlocked = in_array($candidate->id, $unlockedIds); @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="position-relative flex-shrink-0 me-3">
                                    <img src="{{ $candidate->avatar_url }}" width="48" height="48"
                                         class="rounded-circle object-fit-cover" alt="">
                                    {!! $candidate->wakandaBadgeHtml() !!}
                                </div>
                                <div>
                                    <div class="fw-semibold">
                                        @if ($unlocked)
                                            {{ $candidate->name }}
                                        @else
                                            ●●●●● ●●●●●
                                        @endif
                                    </div>
                                    <div class="text-muted fs-13">
                                        @php
                                            $stars = str_repeat('★', (int)$candidate->wakanda_score) . str_repeat('☆', max(0, 5 - (int)$candidate->wakanda_score));
                                        @endphp
                                        <span style="color:#6f42c1;">{{ $stars }}</span>
                                        &nbsp;{{ $candidate->country->name ?? '' }}
                                    </div>
                                </div>
                            </div>

                            @if ($unlocked)
                                <div class="fs-13 mb-2">
                                    <i class="fi fi-rr-envelope me-1"></i>{{ $candidate->email }}<br>
                                    @if ($candidate->phone)
                                        <i class="fi fi-rr-phone me-1"></i>{{ $candidate->phone }}<br>
                                    @endif
                                    @if ($candidate->experience_years)
                                        <i class="fi fi-rr-briefcase me-1"></i>{{ $candidate->experience_years }} yrs exp<br>
                                    @endif
                                </div>
                                @if ($candidate->bio)
                                    <p class="text-muted fs-13 mb-2">{{ \Illuminate\Support\Str::limit($candidate->bio, 120) }}</p>
                                @endif
                                @if ($candidate->resume_url)
                                    <a href="{{ $candidate->resume_url }}" target="_blank" class="btn btn-xs btn-outline-primary mt-1">
                                        <i class="fi fi-rr-file me-1"></i>{{ __('View CV') }}
                                    </a>
                                @endif
                            @else
                                <p class="text-muted fs-13">{{ __('Unlock to view full contact details, CV, and experience.') }}</p>
                                <button type="button" class="btn btn-sm btn-primary mt-2 unlock-candidate-btn"
                                        data-candidate-id="{{ $candidate->id }}"
                                        data-url="{{ route('public.account.talent-pool.unlock', $candidate->id) }}">
                                    <i class="fi fi-rr-lock me-1"></i>{{ __('Unlock — :cost credits', ['cost' => $unlockCost]) }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $candidates->links() }}
        </div>
    @endif
@endsection

@push('footer')
<script>
$(document).on('click', '.unlock-candidate-btn', function () {
    var $btn       = $(this);
    var candidateId = $btn.data('candidate-id');
    var url         = $btn.data('url');

    if (!confirm('{{ __("Spend :cost credits to unlock this candidate profile?", ["cost" => $unlockCost]) }}')) {
        return;
    }

    $btn.prop('disabled', true).addClass('button-loading');

    $.ajax({
        type: 'POST', url: url,
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function (res) {
            if (res.error) {
                Botble.showError(res.message);
            } else {
                Botble.showSuccess(res.message);
                setTimeout(function () { window.location.reload(); }, 1000);
            }
        },
        error: function () { Botble.showError('Something went wrong.'); },
        complete: function () { $btn.prop('disabled', false).removeClass('button-loading'); },
    });
});
</script>
@endpush
