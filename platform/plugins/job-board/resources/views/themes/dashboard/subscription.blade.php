@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    @if(session('success'))
        <x-core::alert type="success">{{ session('success') }}</x-core::alert>
    @endif
    @if(session('error'))
        <div role="alert" class="alert alert-danger mb-4">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <x-core::icon name="ti ti-alert-triangle" class="alert-icon flex-shrink-0" />
                <span>{{ session('error') }}</span>
                <a href="{{ route('public.account.subscription.index') }}" class="btn btn-sm btn-danger flex-shrink-0 ms-auto">
                    <x-core::icon name="ti ti-crown" class="me-1" />
                    View Plans
                </a>
            </div>
        </div>
    @endif

    {{-- Active subscription banner --}}
    @if($activeSub)
        <x-core::card class="mb-4 border-success">
            <x-core::card.body>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-success">Active</span>
                            <h5 class="mb-0">{{ $activeSub->package?->name }}</h5>
                            <span class="text-muted small">({{ ucfirst($activeSub->billing_cycle) }})</span>
                        </div>
                        <div class="text-muted small">
                            Renews / expires: <strong>{{ $activeSub->ends_at?->format('d M Y') ?? 'No expiry' }}</strong>
                            @if($activeSub->cancel_at_period_end)
                                &nbsp;·&nbsp;<span class="text-warning">Cancels at period end</span>
                            @endif
                        </div>
                    </div>
                    @if(! $activeSub->cancel_at_period_end)
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#cancelSubscriptionModal"
                            data-action="{{ route('public.account.subscription.cancel') }}"
                            data-label="{{ $activeSub->package?->name ?? 'this subscription' }}">
                            Cancel Subscription
                        </button>
                    @endif
                </div>
            </x-core::card.body>
        </x-core::card>
    @endif

    {{-- Talent Hub value proposition --}}
    @if($plans->where('can_search_candidates', true)->isNotEmpty())
        <div class="card mb-4" style="background:linear-gradient(135deg,#1a2b6d 0%,#3c65f5 100%);border:none;color:#fff;">
            <div class="card-body py-4 px-4">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <span class="d-flex align-items-center justify-content-center rounded-circle"
                              style="width:56px;height:56px;background:rgba(255,255,255,.15);font-size:1.6rem;">
                            🎯
                        </span>
                    </div>
                    <div class="col">
                        <div class="fw-bold fs-4 mb-1">Unlock the Wakanda Jobs Talent Hub</div>
                        <div style="opacity:.88;font-size:.95rem;">
                            Stop waiting for the right CV to land in your inbox. With <strong>Talent Hub access</strong> you search our full candidate database — filter by skills, location, experience and availability — and reveal contact details instantly. <strong>LinkedIn doesn't cover Zambia and Zimbabwe the way we do.</strong> Our talent pool is local, verified and ready to work.
                        </div>
                    </div>
                    <div class="col-lg-auto text-lg-end mt-2 mt-lg-0">
                        <div class="d-flex flex-wrap gap-2 justify-content-start justify-content-lg-end">
                            <span class="badge" style="background:rgba(255,255,255,.2);font-size:.8rem;padding:.45em .85em;">
                                👤 Full candidate profiles
                            </span>
                            <span class="badge" style="background:rgba(255,255,255,.2);font-size:.8rem;padding:.45em .85em;">
                                📞 Phone & email reveals
                            </span>
                            <span class="badge" style="background:rgba(255,255,255,.2);font-size:.8rem;padding:.45em .85em;">
                                📄 CV downloads
                            </span>
                            <span class="badge" style="background:rgba(255,255,255,.2);font-size:.8rem;padding:.45em .85em;">
                                🔎 Advanced search & filters
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div id="plans"></div>
    @if($plans->isEmpty())
        <x-core::card>
            <x-core::card.body>
                <div class="empty">
                    <div class="empty-icon"><x-core::icon name="ti ti-packages" /></div>
                    <p class="empty-title">No subscription plans available</p>
                    <p class="empty-subtitle text-muted">Check back soon.</p>
                </div>
            </x-core::card.body>
        </x-core::card>
    @else
        <div class="row row-cols-1 row-cols-lg-3 mb-4 row-cards">
            @foreach($plans as $plan)
                @php
                    $isCurrentPlan    = $activeSub?->package_id === $plan->id;
                    $annualPrice      = $plan->billing_cycle === 'monthly' ? round($plan->price * 12 * 0.8, 2) : null;
                    $hasTalentAccess  = (bool) $plan->can_search_candidates;
                @endphp
                <div class="col">
                    <div @class([
                        'card card-md h-100 position-relative',
                        'border-primary'  => $plan->is_default && ! $hasTalentAccess,
                        'border-success'  => $isCurrentPlan && ! $hasTalentAccess,
                    ]) @if($hasTalentAccess) style="border:2px solid #3c65f5;box-shadow:0 4px 24px rgba(60,101,245,.18);" @endif>
                        @if($plan->is_default && ! $hasTalentAccess)
                            <div class="ribbon ribbon-top ribbon-bookmark bg-primary">Most Popular</div>
                        @endif
                        @if($hasTalentAccess)
                            <div class="ribbon ribbon-top ribbon-bookmark" style="background:#1a2b6d;">Talent Hub</div>
                        @endif
                        @if($isCurrentPlan)
                            <div class="ribbon ribbon-top ribbon-bookmark bg-success">Your Plan</div>
                        @endif

                        <div class="card-body d-flex flex-column">
                            <h4 class="mb-1">{{ $plan->name }}</h4>
                            @if($plan->description)
                                <p class="text-muted mb-2">{{ $plan->description }}</p>
                            @endif

                            <div class="my-3">
                                <div class="display-5 fw-bold text-primary">
                                    {{ strtoupper(cms_currency()->getDefaultCurrency()->title ?? 'USD') }}
                                    {{ number_format($plan->price, 0) }}
                                    <span class="fs-5 fw-normal text-muted">/mo</span>
                                </div>
                                @if($annualPrice)
                                    <div class="text-muted small mt-1">
                                        or {{ strtoupper(cms_currency()->getDefaultCurrency()->title ?? 'USD') }} {{ number_format($annualPrice, 0) }}/year
                                        <span class="badge bg-green-lt ms-1">20% off</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Talent Hub highlight block --}}
                            @if($hasTalentAccess)
                                <div class="rounded-2 p-3 mb-3" style="background:linear-gradient(135deg,#eef2ff 0%,#e8edff 100%);border:1px solid #c7d4fc;">
                                    <div class="fw-semibold mb-2" style="color:#1a2b6d;font-size:.9rem;">
                                        🎯 Full Talent Hub Access — included
                                    </div>
                                    <ul class="list-unstyled mb-0" style="font-size:.82rem;color:#2d3e8a;">
                                        <li class="mb-1">✓ &nbsp;Search &amp; filter all candidate profiles</li>
                                        <li class="mb-1">✓ &nbsp;Reveal phone, email &amp; WhatsApp — unlimited</li>
                                        <li class="mb-1">✓ &nbsp;Download CVs with one click</li>
                                        <li class="mb-1">✓ &nbsp;Filter by skills, location, experience &amp; availability</li>
                                        <li>✓ &nbsp;Candidates open to work highlighted first</li>
                                    </ul>
                                </div>
                            @endif

                            <ul class="list-unstyled mb-3 flex-grow-1">
                                @foreach($plan->formatted_features ?? [] as $feature)
                                    @continue(! $feature)
                                    @php
                                        $ft = strtolower($feature);
                                        $skip = str_contains($ft, 'cv database') || str_contains($ft, 'database access')
                                             || str_contains($ft, 'candidate search') || str_contains($ft, 'candidate cv')
                                             || str_contains($ft, 'talent hub');
                                    @endphp
                                    @continue($skip)
                                    <li class="mb-1">
                                        <x-core::icon name="ti ti-check" class="text-success me-1" />
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-auto">
                                @if($isCurrentPlan)
                                    <button class="btn btn-success w-100" disabled>
                                        <x-core::icon name="ti ti-circle-check" class="me-1" /> Current Plan
                                    </button>
                                @else
                                    <a href="{{ route('public.account.subscription.checkout', ['package' => $plan->id]) }}"
                                       @class(['btn w-100', 'btn-primary' => ! $hasTalentAccess, 'btn-default' => $hasTalentAccess])
                                       style="{{ $hasTalentAccess ? 'background:#1a2b6d;border-color:#1a2b6d;color:#fff;' : '' }}">
                                        {{ $hasTalentAccess ? '🚀 Get Talent Hub Access' : 'Subscribe Monthly' }}
                                    </a>
                                    @if($annualPrice)
                                        <a href="{{ route('public.account.subscription.checkout', ['package' => $plan->id, 'cycle' => 'annual']) }}"
                                           class="btn btn-outline-primary w-100 mt-2">
                                            Subscribe Annually (save 20%)
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Social proof strip --}}
        @if($plans->where('can_search_candidates', true)->isNotEmpty())
            <div class="text-center text-muted small mb-4">
                <x-core::icon name="ti ti-lock" class="me-1" /> Employer data is never shared with candidates &nbsp;·&nbsp;
                <x-core::icon name="ti ti-refresh" class="me-1" /> Candidate profiles refreshed every 90 days &nbsp;·&nbsp;
                <x-core::icon name="ti ti-shield-check" class="me-1" /> Cancel any time
            </div>
        @endif
    @endif

    {{-- Order history --}}
    @if($myOrders->isNotEmpty())
        <x-core::card>
            <x-core::card.header>
                <x-core::card.title>Subscription History</x-core::card.title>
            </x-core::card.header>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Plan</th>
                            <th>Cycle</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Started</th>
                            <th>Ends</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($myOrders as $sub)
                            @php
                                $badge = match($sub->status) {
                                    'active'    => 'bg-success',
                                    'expired'   => 'bg-secondary',
                                    'cancelled' => 'bg-danger',
                                    default     => 'bg-warning',
                                };
                            @endphp
                            <tr>
                                <td class="text-muted">#{{ str_pad($sub->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $sub->package?->name ?? '—' }}</td>
                                <td>{{ ucfirst($sub->billing_cycle) }}</td>
                                <td>{{ $sub->currency }} {{ number_format($sub->amount, 2) }}</td>
                                <td><span class="badge {{ $badge }} text-white">{{ ucfirst($sub->status) }}</span></td>
                                <td class="text-muted small">{{ $sub->started_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-muted small">{{ $sub->ends_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-core::card>
    @endif

    <div class="modal fade" id="cancelSubscriptionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-x text-danger fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Cancel this subscription?</h6>
                    <p class="text-muted small mb-4" id="cancelSubscriptionModalLabel">It will remain active until the current period ends.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Keep</button>
                        <form id="cancelSubscriptionForm" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger px-4">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
        <script>
            document.getElementById('cancelSubscriptionModal')?.addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('cancelSubscriptionForm').action = btn.dataset.action;
                document.getElementById('cancelSubscriptionModalLabel').textContent = btn.dataset.label;
            });
        </script>
    @endpush
@stop
