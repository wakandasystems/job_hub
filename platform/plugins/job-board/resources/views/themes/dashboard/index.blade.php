@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    {{-- Quick Actions --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h4 class="mb-0 fw-semibold text-dark" style="font-size:1rem;letter-spacing:.01em;">Quick Actions</h4>
                @php $totalCredits = (auth('account')->user()->credits ?? 0) + (auth('account')->user()->free_credits ?? 0); @endphp
                <span class="badge bg-primary-lt px-3 py-2" style="font-size:.85rem;">
                    <x-core::icon name="ti ti-coin" class="me-1" />
                    {{ number_format($totalCredits) }} credit{{ $totalCredits != 1 ? 's' : '' }} available
                </span>
            </div>
        </div>

        @php
            $actions = [
                [
                    'label'   => 'Post a Job',
                    'desc'    => 'Create a new job listing',
                    'icon'    => 'ti ti-plus',
                    'color'   => 'primary',
                    'route'   => 'public.account.jobs.create',
                    'primary' => true,
                ],
                [
                    'label'   => 'My Jobs',
                    'desc'    => 'Manage your listings',
                    'icon'    => 'ti ti-briefcase',
                    'color'   => 'azure',
                    'route'   => 'public.account.jobs.index',
                    'primary' => false,
                ],
                [
                    'label'   => 'Applicants',
                    'desc'    => 'Review applications',
                    'icon'    => 'ti ti-users',
                    'color'   => 'indigo',
                    'route'   => 'public.account.applicants.index',
                    'primary' => false,
                ],
                [
                    'label'   => 'Browse Talent',
                    'desc'    => 'Find top candidates',
                    'icon'    => 'ti ti-user-search',
                    'color'   => 'cyan',
                    'route'   => 'public.account.candidates.search',
                    'primary' => false,
                ],
                [
                    'label'   => 'My Companies',
                    'desc'    => 'Manage company profiles',
                    'icon'    => 'ti ti-building',
                    'color'   => 'teal',
                    'route'   => 'public.account.companies.index',
                    'primary' => false,
                ],
                [
                    'label'   => 'Buy Credits',
                    'desc'    => 'Top up posting credits',
                    'icon'    => 'ti ti-credit-card',
                    'color'   => 'yellow',
                    'route'   => 'public.account.credits',
                    'primary' => false,
                ],
            ];
        @endphp

        @foreach ($actions as $action)
            <div class="col-6 col-sm-4 col-md-2">
                <a href="{{ route($action['route']) }}"
                   class="card card-sm h-100 text-decoration-none border-0 shadow-sm quick-action-card {{ $action['primary'] ? 'quick-action-primary' : '' }}"
                   style="transition:transform .15s,box-shadow .15s;border-radius:12px!important;overflow:hidden;">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3" style="gap:.4rem;">
                        <span class="avatar avatar-md rounded-circle bg-{{ $action['color'] }}-lt mb-1" style="width:46px;height:46px;font-size:1.35rem;">
                            <x-core::icon
                                :name="$action['icon']"
                                class="quick-action-icon text-{{ $action['color'] }}"
                            />
                        </span>
                        <div class="fw-semibold" style="font-size:.82rem;line-height:1.2;color:#1a2332;">{{ $action['label'] }}</div>
                        <div class="text-muted" style="font-size:.72rem;line-height:1.3;">{{ $action['desc'] }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <style>
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,.10) !important;
        }
        .quick-action-primary {
            background: linear-gradient(135deg, #206bc4 0%, #4dabf7 100%) !important;
        }
        .quick-action-primary .fw-semibold,
        .quick-action-primary .text-muted {
            color: #fff !important;
        }
        .quick-action-primary .avatar {
            background: rgba(255,255,255,.2) !important;
        }
        .quick-action-icon {
            height: 24px;
            stroke-width: 2;
            width: 24px;
        }
        .quick-action-primary .quick-action-icon {
            color: #fff !important;
        }
    </style>

    <x-core::stat-widget class="mb-3 row-cols-1 row-cols-sm-2 row-cols-md-3">
        <x-core::stat-widget.item
            :label="trans('plugins/job-board::dashboard.jobs_label')"
            :value="$totalJobs"
            icon="ti ti-briefcase"
            color="primary"
        />

        <x-core::stat-widget.item
            :label="trans('plugins/job-board::dashboard.companies_label')"
            :value="$totalCompanies"
            icon="ti ti-building"
            color="success"
        />

        <x-core::stat-widget.item
            :label="trans('plugins/job-board::dashboard.applicants_label')"
            :value="$totalApplicants"
            icon="ti ti-users-group"
            color="danger"
        />
    </x-core::stat-widget>

    <div class="row row-cards mb-3">
        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/job-board::dashboard.new_applicants') }}
                    </x-core::card.title>
                </x-core::card.header>
                <div class="list-group list-group-flush">
                    @forelse ($newApplicants as $applicant)
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="{{ route('public.account.applicants.edit', $applicant->id) }}" class="text-reset d-block">{{ $applicant->full_name }}</a>
                                    <div class="d-block text-secondary text-truncate mt-n1">{{ $applicant->email }}</div>
                                </div>
                                <div class="col-auto">
                                    <a href="{{ route('public.account.applicants.edit', $applicant->id) }}" class="list-group-item-actions" title="{{ trans('plugins/job-board::dashboard.view_label') }}">
                                        <x-core::icon name="ti ti-eye" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-core::empty-state
                            :title="trans('plugins/job-board::dashboard.no_new_applicants')"
                            :subtitle="trans('plugins/job-board::dashboard.no_new_applicants_subtitle')"
                        />
                    @endforelse
                </div>
            </x-core::card>
        </div>
        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/job-board::dashboard.recent_activities') }}
                    </x-core::card.title>
                </x-core::card.header>
                <div class="list-group list-group-flush">
                    @forelse ($activities as $activity)
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <div class="d-block text-secondary text-truncate">{!! BaseHelper::clean($activity->getDescription(false)) !!}</div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex align-items-center gap-1">
                                        <x-core::icon name="ti ti-clock" />
                                        {{ $activity->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-core::empty-state
                            :title="trans('plugins/job-board::dashboard.no_recent_activities')"
                            :subtitle="trans('plugins/job-board::dashboard.no_recent_activities_subtitle')"
                        />
                    @endforelse
                </div>
            </x-core::card>
        </div>
    </div>

    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>
                {{ trans('plugins/job-board::dashboard.jobs_about_to_expire') }}
            </x-core::card.title>
        </x-core::card.header>
        <div class="table-responsive">
            <x-core::table>
                <x-core::table.header>
                    <x-core::table.header.cell>
                        {{ trans('plugins/job-board::dashboard.job_name_label') }}
                    </x-core::table.header.cell>
                    <x-core::table.header.cell>
                        {{ trans('plugins/job-board::dashboard.company_label') }}
                    </x-core::table.header.cell>
                    <x-core::table.header.cell>
                        {{ trans('plugins/job-board::dashboard.expire_date_label') }}
                    </x-core::table.header.cell>
                    <x-core::table.header.cell>
                        {{ trans('plugins/job-board::dashboard.status_label') }}
                    </x-core::table.header.cell>
                    <x-core::table.header.cell>
                        {{ trans('plugins/job-board::dashboard.total_applicants_label') }}
                    </x-core::table.header.cell>
                    <x-core::table.header.cell>
                        {{ trans('plugins/job-board::dashboard.actions_label') }}
                    </x-core::table.header.cell>
                </x-core::table.header>
                <x-core::table.body>
                    @foreach ($expiredJobs as $job)
                        <x-core::table.body.row>
                            <x-core::table.body.cell>
                                <a class="fw-bold" href="{{ route('public.account.jobs.edit', $job->id) }}">
                                    {{ $job->name }}
                                </a>
                            </x-core::table.body.cell>
                            <x-core::table.body.cell>
                                {{ $job->company->name }}
                            </x-core::table.body.cell>
                            <x-core::table.body.cell>
                                {{ BaseHelper::formatDate($job->expire_date) }}
                            </x-core::table.body.cell>
                            <x-core::table.body.cell>
                                {!! $job->status->toHtml() !!}
                            </x-core::table.body.cell>
                            <x-core::table.body.cell>
                                {{ $job->applicants_count }}
                            </x-core::table.body.cell>
                            <x-core::table.body.cell>
                                <div class="btn-list">
                                    <x-core::button
                                        tag="a"
                                        color="primary"
                                        icon="ti ti-edit"
                                        size="sm"
                                        :icon-only="true"
                                        :href="route('public.account.jobs.edit', $job->id)"
                                        data-bs-toggle="tooltip"
                                        title="{{ trans('plugins/job-board::dashboard.view_label') }}"
                                    />

                                    @if (auth('account')->user()->canPost())
                                        <x-core::form
                                            :url="route('public.account.jobs.renew', $job->id)"
                                            id="form-renew-job-{{ $job->id }}"
                                            onsubmit="return window.__dashConfirmed || (window.__dashConfirmForm = this, renewConfirmModal.show(), false);"
                                        >
                                            <x-core::button
                                                tag="button"
                                                color="success"
                                                size="sm"
                                                type="submit"
                                            >
                                                {{ trans('plugins/job-board::dashboard.renew_label') }}
                                            </x-core::button>
                                        </x-core::form>
                                    @else
                                        <x-core::button
                                            tag="a"
                                            color="primary"
                                            size="sm"
                                            :href="route('public.account.credits')"
                                            data-bs-toggle="tooltip"
                                            title="{{ trans('plugins/job-board::dashboard.purchase_credits_to_renew') }}"
                                        >
                                            {{ trans('plugins/job-board::dashboard.buy_credits') }}
                                        </x-core::button>
                                    @endif
                                </div>
                            </x-core::table.body.cell>
                        </x-core::table.body.row>
                    @endforeach
                </x-core::table.body>
            </x-core::table>
        </div>
    </x-core::card>
{{-- Renew confirmation modal --}}
<div class="modal fade" id="renewConfirmModal" tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <p class="mb-0 fw-semibold">{{ trans('plugins/job-board::dashboard.confirm_form_submit') }}</p>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="renewConfirmOkBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

@push('footer')
<script>
const renewConfirmModal = new bootstrap.Modal(document.getElementById('renewConfirmModal'));
document.getElementById('renewConfirmOkBtn').addEventListener('click', function () {
    renewConfirmModal.hide();
    if (window.__dashConfirmForm) {
        window.__dashConfirmed = true;
        window.__dashConfirmForm.submit();
        window.__dashConfirmed = false;
        window.__dashConfirmForm = null;
    }
});
</script>
@endpush

@stop
