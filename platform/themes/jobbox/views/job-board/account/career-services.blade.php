@extends(Theme::getThemeNamespace('views.job-board.account.partials.layout-settings'))

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

    {{-- Service cards --}}
    <div class="row g-3 mb-40">
        @foreach($services as $key => $svc)
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
                                <span class="fw-bold color-brand-1 fs-5">${{ $svc['price'] }}</span>
                                <span class="color-text-paragraph-2 ms-1 font-xs">· {{ $svc['delivery'] }}</span>
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
                            <th class="font-sm fw-semibold">Status</th>
                            <th class="font-sm fw-semibold">Date</th>
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
                                            'paid'        => 'bg-success',
                                            'in_progress' => 'bg-primary',
                                            'delivered'   => 'bg-info',
                                            'completed'   => 'bg-success',
                                            'cancelled'   => 'bg-danger',
                                            default       => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                                </td>
                                <td class="font-xs text-muted">{{ $order->created_at->format('d M Y') }}</td>
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
