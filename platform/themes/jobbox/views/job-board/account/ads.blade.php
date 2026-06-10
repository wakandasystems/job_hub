@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
<div>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h3 class="mt-0 mb-0 color-brand-1">{{ __('Advertise') }}</h3>
    </div>
    <p class="color-text-paragraph-2 font-sm mb-30">
        {{ __('Promote your brand by placing your own banner ad in front of job seekers and employers across the site.') }}
    </p>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-20" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($placements->isEmpty())
        <div class="text-center py-40 color-text-paragraph-2">
            <p class="font-sm">{{ __('No ad placements are available yet. Check back soon.') }}</p>
        </div>
    @else
        <ul class="nav nav-tabs mb-4 border-bottom" id="adGroupTabs" role="tablist">
            @foreach($groups as $groupName => $groupPlacements)
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold @if($loop->first) active @endif"
                        id="ad-group-{{ Str::slug($groupName) }}-btn"
                        data-bs-toggle="tab"
                        data-bs-target="#ad-group-{{ Str::slug($groupName) }}"
                        type="button" role="tab"
                        aria-controls="ad-group-{{ Str::slug($groupName) }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        {{ __($groupName) }} <span class="badge bg-light text-dark ms-1">{{ $groupPlacements->count() }}</span>
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content mb-40" id="adGroupTabContent">
            @foreach($groups as $groupName => $groupPlacements)
                <div class="tab-pane fade @if($loop->first) show active @endif"
                    id="ad-group-{{ Str::slug($groupName) }}"
                    role="tabpanel"
                    aria-labelledby="ad-group-{{ Str::slug($groupName) }}-btn">
                    <div class="row g-3">
                        @foreach($groupPlacements as $placement)
                            @php
                                $pricing = $placementPrices[$placement->id] ?? null;
                            @endphp
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border" style="border-radius:12px;">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="mb-3">
                                            <div class="fw-semibold fs-6">{{ $placement->name }}</div>
                                            @if($placement->description)
                                                <p class="color-text-paragraph-2 mb-0 font-sm">{{ $placement->description }}</p>
                                            @endif
                                        </div>
                                        <div class="color-text-paragraph-2 font-sm d-flex align-items-center" style="margin-bottom:1rem;">
                                            <i class="fi-rr-clock me-2"></i> {{ $placement->displayDuration() }}
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-auto">
                                            <span class="fw-bold color-brand-1 fs-5">
                                                {{ $pricing['display'] ?? ($placement->currency . ' ' . number_format($placement->price, 2)) }}
                                            </span>
                                            <button type="button" class="btn btn-apply px-4"
                                                data-bs-toggle="modal" data-bs-target="#requestAdModal"
                                                data-action="{{ route('public.account.ads.store', ['placement' => $placement->id]) }}"
                                                data-name="{{ $placement->name }}">
                                                {{ __('Request') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- My ad requests --}}
    @if($myOrders->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">{{ __('My Ad Requests') }}</h6>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Ref') }}</th>
                                <th>{{ __('Placement') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myOrders as $o)
                                @php
                                    $badgeColor = match($o->status) {
                                        'approved'  => 'bg-success',
                                        'rejected'  => 'bg-danger',
                                        'cancelled' => 'bg-secondary',
                                        default     => 'bg-warning',
                                    };
                                @endphp
                                <tr>
                                    <td class="text-muted">#{{ str_pad((string) $o->id, 6, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $o->placement?->name ?? '—' }}</td>
                                    <td>{{ $o->currency }} {{ number_format($o->amount, 2) }}</td>
                                    <td><span class="badge {{ $badgeColor }} text-white">{{ ucfirst($o->status) }}</span></td>
                                    <td class="text-muted">{{ $o->created_at?->format('d M Y') }}</td>
                                    <td>
                                        @if($o->status === 'pending' && ! $o->charge_id)
                                            <a href="{{ route('public.account.ads.checkout', ['order' => $o->id]) }}" class="btn btn-sm btn-outline-primary">
                                                {{ __('Pay now') }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Request ad modal --}}
    <div class="modal modal-blur fade" id="requestAdModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Request Ad Placement') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="requestAdForm" method="POST" action="" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3" id="requestAdPlacementName"></p>
                        <div class="mb-3">
                            <label class="form-label required">{{ __('Banner Image') }}</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                            <div class="form-text">{{ __('Recommended: PNG or JPG, max 5MB.') }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Click-through URL') }}</label>
                            <input type="url" name="url" class="form-control" placeholder="https://example.com">
                        </div>
                        <div class="mb-0">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input" name="open_in_new_tab" value="1" checked>
                                <span class="form-check-label">{{ __('Open link in a new tab') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-apply">
                            {{ __('Continue to Payment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('footer')
    <script>
        document.querySelectorAll('[data-bs-target="#requestAdModal"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('requestAdForm').action = this.dataset.action;
                document.getElementById('requestAdPlacementName').textContent = '{{ __('Placement') }}: ' + this.dataset.name;
            });
        });
    </script>
    @endpush
</div>
@endsection
