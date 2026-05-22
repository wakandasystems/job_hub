@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
<div>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h3 class="mt-0 mb-0 color-brand-1">{{ __('Career Services') }}</h3>
        <span class="badge bg-warning text-dark">Boost your career</span>
    </div>
    <p class="color-text-paragraph-2 font-sm mb-30">
        Professional CV writing, LinkedIn optimisation &amp; interview coaching —
        delivered by vetted career coaches within 24–72 hrs.
    </p>

    <div class="card border-0 shadow-sm mb-30">
        <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h5 class="mb-1 fw-semibold">{{ __('Free AI CV Score') }}</h5>
                <p class="mb-0 color-text-paragraph-2 font-sm">{{ __('Score your CV first, then book a human review only if it needs work.') }}</p>
            </div>
            @if($account->resume)
                <button type="button" class="btn btn-apply px-4"
                    data-bs-toggle="modal" data-bs-target="#cvScoreModal">
                    {{ __('Score My CV') }}
                </button>
            @else
                <a href="{{ route('public.career-service.cv-score') }}" class="btn btn-apply px-4">
                    {{ __('Score My CV') }}
                </a>
            @endif
        </div>
    </div>

    {{-- CV source modal --}}
    @if($account->resume)
        <div class="modal fade" id="cvScoreModal" tabindex="-1" aria-labelledby="cvScoreModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-semibold" id="cvScoreModalTitle">{{ __('Which CV should we score?') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-2 pb-4 px-4">
                        <p class="text-muted font-sm mb-4">{{ __('We found a CV on your profile. Score it instantly, or upload a different one.') }}</p>
                        <div class="d-flex flex-column gap-3">
                            <form method="POST" action="{{ route('public.career-service.cv-score.profile') }}">
                                @csrf
                                <button type="submit" class="btn btn-apply w-100 text-start d-flex align-items-center gap-3 px-4 py-3">
                                    <i class="fi-rr-document fs-4 flex-shrink-0"></i>
                                    <div>
                                        <div class="fw-semibold">{{ __('Score my profile CV') }}</div>
                                        <div class="font-xs opacity-75">{{ $account->resumeName }}</div>
                                    </div>
                                </button>
                            </form>
                            <a href="{{ route('public.career-service.cv-score') }}"
                               class="btn btn-outline-primary w-100 text-start d-flex align-items-center gap-3 px-4 py-3"
                               data-bs-dismiss="modal">
                                <i class="fi-rr-upload fs-4 flex-shrink-0"></i>
                                <div>
                                    <div class="fw-semibold">{{ __('Upload or paste a different CV') }}</div>
                                    <div class="font-xs opacity-75">{{ __('Choose a file or paste CV text') }}</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Service cards --}}
    <div class="row g-3 mb-40">
        @foreach($services as $key => $svc)
            @php
                $pricing = $servicePrices[$key] ?? null;
            @endphp
            <div class="col-md-6">
                <div class="card h-100 border career-service-card" style="border-radius:12px;transition:box-shadow .2s,border-color .2s;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10"
                                  style="width:46px;height:46px;flex-shrink:0;">
                                <i class="{{ $svc['icon'] ?? 'fi-rr-briefcase' }} text-primary fs-5"></i>
                            </span>
                            <div>
                                <div class="fw-semibold">{{ $svc['name'] }}</div>
                                @if(!empty($svc['badge']))
                                    <span class="badge bg-warning text-dark" style="font-size:10px;">{{ $svc['badge'] }}</span>
                                @endif
                            </div>
                        </div>
                        <p class="color-text-paragraph-2 mb-3" style="font-size:13px;line-height:1.5;">
                            {{ $svc['description'] }}
                        </p>
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-bold color-brand-1 fs-5">
                                    {{ $pricing['display'] ?? ('$' . $svc['price']) }}
                                </span>
                                <span class="color-text-paragraph-2 ms-1 font-xs">· {{ $svc['delivery'] }}</span>
                                @if($pricing && $pricing['origin_currency_code'] !== $pricing['currency_code'])
                                    <div class="font-xs color-text-paragraph-2">
                                        {{ __('Converted for :country', ['country' => $pricing['target_country'] ?? $pricing['currency_code']]) }}
                                    </div>
                                @endif
                                @if($pricing)
                                    <div class="font-xs color-text-paragraph-2">
                                        {{ __('Original: :country :price', ['country' => $pricing['origin_country'] ?? $pricing['origin_currency_code'], 'price' => $pricing['origin_display']]) }}
                                    </div>
                                @endif
                            </div>
                            <a href="{{ route('public.career-service.checkout', ['service' => $key, 'candidate' => $account->slug]) }}"
                               class="btn btn-apply px-4">
                                Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-center color-text-paragraph-2 mb-40" style="font-size:12px;">
        <i class="fi-rr-shield-check text-success me-1"></i>
        Secure payment &nbsp;·&nbsp; Money-back guarantee if not delivered &nbsp;·&nbsp; Powered by Wakanda Jobs
    </p>

    {{-- My Orders --}}
    @if($myOrders->isNotEmpty())
        <div class="mt-10">
            <h5 class="fw-semibold mb-20">My Orders</h5>
            <div class="table-responsive">
                <table class="table table-borderless align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="font-sm fw-semibold">Ref</th>
                            <th class="font-sm fw-semibold">Service</th>
                            <th class="font-sm fw-semibold">Amount</th>
                            <th class="font-sm fw-semibold">Payment</th>
                            <th class="font-sm fw-semibold">Delivery</th>
                            <th class="font-sm fw-semibold">Date</th>
                            <th class="font-sm fw-semibold"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($myOrders as $order)
                            <tr>
                                <td class="font-xs text-muted">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td class="font-sm">{{ $order->service_name }}</td>
                                <td class="font-sm fw-semibold">{{ $order->currency }} {{ number_format($order->amount, 2) }}</td>
                                <td>
                                    @php
                                        $badge = match($order->status) {
                                            'paid'      => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            'refunded'  => 'bg-warning',
                                            default     => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td>
                                    @php
                                        $deliveryBadge = match($order->delivery_status) {
                                            'delivered'          => 'bg-success',
                                            'in_progress'        => 'bg-primary',
                                            'assigned'           => 'bg-info',
                                            'revision_requested' => 'bg-warning',
                                            'cancelled'          => 'bg-danger',
                                            default              => 'bg-secondary',
                                        };
                                        $deliveryLabels = [
                                            'unassigned'         => 'Pending',
                                            'assigned'           => 'Assigned',
                                            'in_progress'        => 'In Progress',
                                            'delivered'          => 'Delivered',
                                            'revision_requested' => 'Revision',
                                            'cancelled'          => 'Cancelled',
                                        ];
                                    @endphp
                                    <span class="badge {{ $deliveryBadge }}">{{ $deliveryLabels[$order->delivery_status] ?? ucfirst($order->delivery_status) }}</span>
                                </td>
                                <td class="font-xs text-muted">{{ $order->created_at->format('d M Y') }}</td>
                                <td>
                                    @if($order->reviewed_cv_path && $order->delivery_status === 'delivered')
                                        <a href="{{ route('public.career-service.download-reviewed-cv', ['order' => $order->id]) }}"
                                           class="btn btn-outline-success btn-sm py-1 px-2 font-xs"
                                           title="Download your reviewed CV">
                                            <i class="fi-rr-download me-1"></i>Download Reviewed CV
                                        </a>
                                    @elseif($order->status === 'paid' && !$order->candidate_cv_path && $order->service_type !== 'interview_coaching')
                                        <a href="{{ route('public.career-service.thanks', ['order' => $order->id]) }}"
                                           class="btn btn-outline-warning btn-sm py-1 px-2 font-xs"
                                           title="Upload your CV to get started">
                                            <i class="fi-rr-upload me-1"></i>Upload CV
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<style>
    .career-service-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,.10);
        border-color: var(--primary-color) !important;
    }
</style>
@endsection
