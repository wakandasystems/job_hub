@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    @php
        $vipStartingPlan = collect(\Botble\JobBoard\Models\VipAlertOrder::plans())->sortBy('price')->first();
        $vipStartingText = $vipStartingPlan
            ? $vipStartingPlan['currency'] . ' ' . number_format($vipStartingPlan['price'], 2)
            : null;
    @endphp
    <x-core::stat-widget class="mb-3 row-cols-1 row-cols-sm-2 row-cols-md-4">
        <x-core::stat-widget.item
            :label="__('Applications')"
            :value="$totalApplications"
            icon="ti ti-send"
            color="primary"
        />

        <x-core::stat-widget.item
            :label="__('Saved Jobs')"
            :value="$savedJobs"
            icon="ti ti-bookmark"
            color="success"
        />

        <x-core::stat-widget.item
            :label="__('Job Alerts')"
            :value="$activeAlerts"
            icon="ti ti-bell"
            color="warning"
        />

        <x-core::stat-widget.item
            :label="__('CV Score')"
            :value="$profileScore ? $profileScore . '/100' : __('Not scored')"
            icon="ti ti-sparkles"
            color="info"
        />
    </x-core::stat-widget>

    <div class="row row-cards mb-3">
        <div class="col-lg-8">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ __('Recent Applications') }}</x-core::card.title>
                    <x-core::card.actions>
                        <x-core::button tag="a" size="sm" :href="route('public.account.jobs.applied-jobs')">
                            {{ __('View all') }}
                        </x-core::button>
                    </x-core::card.actions>
                </x-core::card.header>

                <div class="list-group list-group-flush">
                    @forelse ($recentApplications as $application)
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="{{ $application->job->url }}" class="text-reset fw-medium d-block">
                                        {{ $application->job->name }}
                                    </a>
                                    <div class="d-block text-secondary text-truncate mt-n1">
                                        {{ $application->job->company->name }} · {{ $application->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    {!! $application->status->toHtml() !!}
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-core::empty-state
                            :title="__('No applications yet')"
                            :subtitle="__('Jobs you apply for will appear here.')"
                        />
                    @endforelse
                </div>
            </x-core::card>
        </div>

        <div class="col-lg-4">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ __('Quick Actions') }}</x-core::card.title>
                </x-core::card.header>

                <div class="list-group list-group-flush">
                    <a href="{{ JobBoardHelper::getJobsPageURL() ?: route('public.index') }}" class="list-group-item list-group-item-action">
                        <x-core::icon name="ti ti-search" class="me-2" />
                        {{ __('Find Jobs') }}
                    </a>
                    <a href="{{ route('public.account.settings') }}" class="list-group-item list-group-item-action">
                        <x-core::icon name="ti ti-user-edit" class="me-2" />
                        {{ __('Update Profile') }}
                    </a>
                    <a href="{{ route('public.career-service.cv-score') }}" class="list-group-item list-group-item-action">
                        <x-core::icon name="ti ti-sparkles" class="me-2" />
                        {{ __('Score My CV') }}
                    </a>
                    <a href="{{ route('public.account.job-alerts.index') }}" class="list-group-item list-group-item-action">
                        <x-core::icon name="ti ti-bell-plus" class="me-2" />
                        {{ __('Create Job Alert') }}
                    </a>
                </div>
            </x-core::card>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ __('Saved Jobs') }}</x-core::card.title>
                    <x-core::card.actions>
                        <x-core::button tag="a" size="sm" :href="route('public.account.jobs.saved')">
                            {{ __('View all') }}
                        </x-core::button>
                    </x-core::card.actions>
                </x-core::card.header>

                <div class="list-group list-group-flush">
                    @forelse ($recentSavedJobs as $job)
                        <div class="list-group-item">
                            <a href="{{ $job->url }}" class="text-reset fw-medium d-block">{{ $job->name }}</a>
                            <div class="text-secondary text-truncate mt-n1">
                                {{ $job->company->name }} · {{ $job->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <x-core::empty-state
                            :title="__('No saved jobs yet')"
                            :subtitle="__('Save jobs you want to revisit later.')"
                        />
                    @endforelse
                </div>
            </x-core::card>
        </div>

        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ __('Recent Activity') }}</x-core::card.title>
                </x-core::card.header>

                <div class="list-group list-group-flush">
                    @forelse ($activities as $activity)
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <div class="d-block text-secondary text-truncate">{!! BaseHelper::clean($activity->getDescription(false)) !!}</div>
                                </div>
                                <div class="col-auto text-secondary">
                                    {{ $activity->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-core::empty-state
                            :title="__('No recent activity')"
                            :subtitle="__('Your account activity will appear here.')"
                        />
                    @endforelse
                </div>
            </x-core::card>
        </div>
    </div>
@endsection

@push('footer')
    <style>
        .vip-alert-promo {
            border-radius: 18px;
            padding: 0 0 1.5rem;
        }

        .vip-alert-promo .swal2-html-container {
            margin: 0;
            overflow: visible;
        }

        .vip-alert-promo__hero {
            background: linear-gradient(135deg, #101828 0%, #214c3d 100%);
            border-radius: 18px 18px 0 0;
            color: #fff;
            padding: 2rem 1.5rem 1.5rem;
            text-align: center;
        }

        .vip-alert-promo__icon {
            align-items: center;
            background: #25d366;
            border-radius: 50%;
            box-shadow: 0 8px 24px rgba(37, 211, 102, .3);
            display: inline-flex;
            height: 64px;
            justify-content: center;
            margin-bottom: 1rem;
            width: 64px;
        }

        .vip-alert-promo__body {
            color: #475467;
            padding: 1.25rem 1.5rem 0;
            text-align: left;
        }

        .vip-alert-promo__benefit {
            align-items: flex-start;
            display: flex;
            gap: .65rem;
            margin-bottom: .75rem;
        }

        .vip-alert-promo__check {
            color: #12b76a;
            font-weight: 700;
        }

        .vip-alert-promo__price {
            background: #ecfdf3;
            border: 1px solid #abefc6;
            border-radius: 8px;
            color: #067647;
            font-size: .85rem;
            font-weight: 600;
            margin-top: 1rem;
            padding: .65rem .75rem;
            text-align: center;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var storageKey = 'wakanda_vip_alert_promo_seen_v1';
            var cooldown = 7 * 24 * 60 * 60 * 1000;
            var lastShown = 0;

            try {
                lastShown = parseInt(localStorage.getItem(storageKey) || '0', 10);
            } catch (e) {
                return;
            }

            if (Date.now() - lastShown < cooldown) {
                return;
            }

            function showVipAlertPromo() {
                try {
                    localStorage.setItem(storageKey, Date.now().toString());
                } catch (e) {
                    return;
                }

                Swal.fire({
                    width: 480,
                    padding: 0,
                    showCancelButton: true,
                    buttonsStyling: true,
                    confirmButtonText: 'Yes, tell me more',
                    cancelButtonText: 'Not now',
                    confirmButtonColor: '#25d366',
                    cancelButtonColor: '#667085',
                    customClass: {
                        popup: 'vip-alert-promo'
                    },
                    html: `
                        <div class="vip-alert-promo__hero">
                            <span class="vip-alert-promo__icon">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path fill="#fff" d="M12.04 2a9.84 9.84 0 0 0-8.43 14.92L2.05 22l5.2-1.52A9.96 9.96 0 1 0 12.04 2Zm0 17.95a8 8 0 0 1-4.08-1.12l-.29-.17-3.08.9.82-3-.19-.3a7.91 7.91 0 1 1 6.82 3.69Zm4.34-5.93c-.24-.12-1.4-.69-1.62-.77-.22-.08-.38-.12-.54.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.91-1.18a7.17 7.17 0 0 1-1.32-1.64c-.14-.24-.01-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.47-.4-.4-.54-.41h-.46a.88.88 0 0 0-.63.3c-.22.24-.83.81-.83 1.98s.85 2.3.97 2.46c.12.16 1.67 2.55 4.05 3.58.57.24 1.01.39 1.35.5.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.03.14-1.12-.06-.1-.22-.16-.46-.28Z"/>
                                </svg>
                            </span>
                            <h2 class="mb-2 text-white">Jobs matched to you, on WhatsApp</h2>
                            <p class="mb-0 text-white opacity-75">Try Wakanda Jobs VIP Alerts and spend less time searching.</p>
                        </div>
                        <div class="vip-alert-promo__body">
                            <div class="vip-alert-promo__benefit"><span class="vip-alert-promo__check">✓</span><span>Personalised jobs based on your skills and preferred roles</span></div>
                            <div class="vip-alert-promo__benefit"><span class="vip-alert-promo__check">✓</span><span>New matching opportunities delivered directly to WhatsApp</span></div>
                            <div class="vip-alert-promo__benefit"><span class="vip-alert-promo__check">✓</span><span>Choose a plan that works for your job search</span></div>
                            @if($vipStartingText)
                                <div class="vip-alert-promo__price">VIP plans start from {{ $vipStartingText }}</div>
                            @endif
                        </div>
                    `
                }).then(function (result) {
                    if (! result.isConfirmed) {
                        return;
                    }

                    window.location.href = @json('https://wa.me/260970766123?text=' . rawurlencode('Hi Wakanda Jobs, I would like more information about VIP personalised job alerts.'));
                });
            }

            function loadSweetAlert() {
                if (window.Swal) {
                    showVipAlertPromo();
                    return;
                }

                var stylesheet = document.createElement('link');
                stylesheet.rel = 'stylesheet';
                stylesheet.href = '{{ asset('vendor/core/core/base/libraries/sweetalert2/sweetalert2.min.css') }}';
                document.head.appendChild(stylesheet);

                var script = document.createElement('script');
                script.src = '{{ asset('vendor/core/core/base/libraries/sweetalert2/sweetalert2.min.js') }}';
                script.onload = showVipAlertPromo;
                document.body.appendChild(script);
            }

            window.setTimeout(loadSweetAlert, 1800);
        });
    </script>
@endpush
