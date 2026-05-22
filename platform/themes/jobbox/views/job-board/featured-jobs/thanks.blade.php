<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-12 text-center">
                <div class="card border-0 shadow-sm p-5">
                    <div class="mb-4">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:72px;height:72px;">
                            <i class="fi-rr-check text-success fs-1"></i>
                        </span>
                    </div>
                    <h3 class="fw-bold color-brand-1 mb-2">{{ __('Your job is now featured!') }}</h3>
                    <p class="color-text-paragraph-2 font-sm mb-4">
                        {{ __(':job will appear at the top of search results', ['job' => $job?->name ?? '']) }}
                        @if($order->expires_at)
                            {{ __('until :date', ['date' => $order->expires_at->format('d M Y')]) }}.
                        @else
                            .
                        @endif
                    </p>

                    <div class="card bg-light border-0 p-3 mb-4 text-start">
                        <div class="row g-2">
                            <div class="col-6 font-sm text-muted">{{ __('Package') }}</div>
                            <div class="col-6 font-sm fw-semibold">{{ $package?->name }}</div>
                            <div class="col-6 font-sm text-muted">{{ __('Job') }}</div>
                            <div class="col-6 font-sm fw-semibold">{{ \Illuminate\Support\Str::limit($job?->name ?? '—', 35) }}</div>
                            <div class="col-6 font-sm text-muted">{{ __('Badge') }}</div>
                            <div class="col-6"><span class="badge bg-warning text-dark">{{ $package?->badge_label }}</span></div>
                            <div class="col-6 font-sm text-muted">{{ __('Expires') }}</div>
                            <div class="col-6 font-sm fw-semibold">
                                {{ $order->expires_at ? $order->expires_at->format('d M Y') : __('No expiry') }}
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-center">
                        <a href="{{ route('public.account.featured-jobs.index') }}" class="btn btn-apply px-4">
                            {{ __('View My Featured Jobs') }}
                        </a>
                        @if($job?->url)
                            <a href="{{ $job->url }}" target="_blank" class="btn btn-outline-secondary px-4">
                                {{ __('View Job Post') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
