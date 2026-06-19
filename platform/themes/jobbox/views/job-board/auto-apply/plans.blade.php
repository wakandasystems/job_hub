<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-7 text-center">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:72px;height:72px;background:linear-gradient(135deg,#3c65f5,#1e3a8a);">
                    <i class="fi-rr-paper-plane" style="font-size:32px;color:#fff;"></i>
                </div>
                <h1 class="section-title mb-3">Auto Apply</h1>
                <p class="font-md color-text-paragraph-2">
                    Let AI apply to matching jobs automatically. We craft personalized emails using your CV and send them directly to employers.
                </p>
            </div>
        </div>

        {{-- Benefits --}}
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10">
                <div class="row g-3 text-center">
                    @foreach([
                        ['icon' => 'fi-rr-brain', 'text' => 'AI-crafted personalized emails'],
                        ['icon' => 'fi-rr-document', 'text' => 'CV automatically attached'],
                        ['icon' => 'fi-rr-target', 'text' => 'Only applies to matching jobs'],
                        ['icon' => 'fi-rr-envelope', 'text' => 'Employer replies come to you'],
                        ['icon' => 'fi-rr-shield-check', 'text' => 'Match score threshold control'],
                        ['icon' => 'fi-rr-time-check', 'text' => 'Applied the moment jobs go live'],
                    ] as $benefit)
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="p-3 rounded-3 border h-100 d-flex flex-column align-items-center gap-2">
                                <i class="{{ $benefit['icon'] }} fs-4 text-primary"></i>
                                <span class="font-xs">{!! $benefit['text'] !!}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Pricing cards --}}
        <div class="row justify-content-center g-4">
            @foreach($plans as $planKey => $plan)
                @php $isPopular = $plan['badge'] === 'Most Popular'; @endphp
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow h-100 position-relative {{ $isPopular ? 'border-primary border-2' : '' }}"
                         style="{{ $isPopular ? 'border:2px solid #3c65f5!important;' : '' }}">
                        @if($plan['badge'])
                            <span class="badge position-absolute top-0 start-50 translate-middle px-3 py-2"
                                  style="background:{{ $isPopular ? '#3c65f5' : '#0d6efd' }};color:#fff;font-size:.75rem;">
                                {{ $plan['badge'] }}
                            </span>
                        @endif
                        <div class="card-body p-4 pt-5 d-flex flex-column">
                            <div class="text-center mb-4">
                                <div class="display-5 fw-bold text-dark">{{ $plan['displayCurrency'] }} {{ number_format($plan['displayPrice'], 2) }}</div>
                                <div class="text-muted font-sm">{{ $plan['label'] }}</div>
                            </div>
                            <ul class="list-unstyled mb-4 flex-grow-1">
                                <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>{{ $plan['label'] }} subscription</li>
                                <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>
                                    @if($plan['applications_per_month'] === 0)
                                        Unlimited applications
                                    @elseif($plan['duration_days'] < 30)
                                        {{ $plan['applications_per_month'] }} applications for the full plan
                                    @else
                                        {{ $plan['applications_per_month'] }} applications every 30 days
                                    @endif
                                </li>
                                <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>AI-crafted cover emails</li>
                                <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>CV auto-attached</li>
                                <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>Match score filtering</li>
                                <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>Company blacklisting</li>
                                @if($planKey === 'one_time')
                                    <li class="mb-2"><i class="fi-rr-check text-success me-2"></i>Best value — save vs monthly</li>
                                @endif
                            </ul>
                            <a href="{{ route('public.auto-apply.checkout', $planKey) }}"
                               class="btn w-100 btn-apply-big {{ $isPopular ? 'btn-primary' : 'btn-outline-primary' }}">
                                Get Started — {{ $plan['displayCurrency'] }} {{ number_format($plan['displayPrice'], 2) }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row justify-content-center mt-5">
            <div class="col-lg-6 text-center">
                <p class="font-sm color-text-paragraph-2">
                    <i class="fi-rr-lock text-primary me-1"></i>
                    Secure payment &middot; Requires uploaded CV &middot; Weekly email digest of all applications sent
                </p>
            </div>
        </div>
    </div>
</section>
