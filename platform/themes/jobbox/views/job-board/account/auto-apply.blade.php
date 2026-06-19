@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
<div>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h3 class="mt-0 mb-0 color-brand-1">{{ __('Auto Apply') }}</h3>
        @if($preference && $preference->is_active)
            <span class="badge bg-success">Active</span>
        @else
            <span class="badge bg-secondary">Inactive</span>
        @endif
    </div>
    <p class="color-text-paragraph-2 font-sm mb-30">
        {{ __('Automatically send AI-crafted application emails to matching jobs with your CV attached.') }}
    </p>

    {{-- CV Warning --}}
    @if(!$hasCv)
        <div class="alert alert-warning mb-20">
            <i class="ti ti-alert-triangle me-1"></i>
            <strong>CV Required:</strong> You must upload your CV/Resume in your
            <a href="{{ route('public.account.settings') }}">profile settings</a> before enabling Auto Apply.
        </div>
    @endif

    {{-- Quota Status --}}
    @if($quota)
        <div class="card border-0 shadow-sm mb-20">
            <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap gap-4">
                    <div>
                        <span class="color-text-paragraph-2 font-xs d-block">{{ __('Plan') }}</span>
                        <span class="fw-semibold text-primary">{{ $activeOrder?->planLabel() ?? 'Active' }}</span>
                    </div>
                    <div>
                        <span class="color-text-paragraph-2 font-xs d-block">{{ __('Applications sent this cycle') }}</span>
                        <span class="fw-semibold text-primary">{{ $quota->applications_sent }}</span>
                    </div>
                    <div>
                        <span class="color-text-paragraph-2 font-xs d-block">{{ __('Remaining') }}</span>
                        <span class="fw-semibold {{ $quota->hasRemaining() ? 'text-success' : 'text-danger' }}">
                            @if($quota->applications_allowed === -1)
                                Unlimited
                            @else
                                {{ max(0, $quota->applications_allowed - $quota->applications_sent) }} / {{ $quota->applications_allowed }}
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="color-text-paragraph-2 font-xs d-block">{{ __('Current cycle') }}</span>
                        <span class="fw-semibold">{{ $period }}</span>
                    </div>
                    @if($activeOrder?->expiresAt())
                        <div>
                            <span class="color-text-paragraph-2 font-xs d-block">{{ __('Expires') }}</span>
                            <span class="fw-semibold">{{ $activeOrder->expiresAt()->toFormattedDateString() }}</span>
                        </div>
                    @endif
                </div>
                <a href="{{ route('public.auto-apply.plans') }}" class="btn btn-sm btn-outline-primary">
                    {{ __('Upgrade Plan') }}
                </a>
            </div>
        </div>
    @else
        <div class="alert alert-info mb-20">
            <i class="ti ti-info-circle me-1"></i>
            You need an Auto Apply subscription to use this feature.
            <a href="{{ route('public.auto-apply.plans') }}" class="fw-bold">View Plans</a>
        </div>
    @endif

    {{-- Preferences Form --}}
    <div class="card border-0 shadow-sm mb-20">
        <div class="card-body">
            <h5 class="mb-3">{{ __('Auto Apply Preferences') }}</h5>
            <form method="POST" action="{{ route('public.account.auto-apply.update') }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">{{ __('Keywords') }}</label>
                        <input type="text" name="keywords" class="form-control"
                               value="{{ implode(', ', $preference->keywords ?? []) }}"
                               placeholder="e.g. developer, marketing, accountant (comma-separated)">
                        <div class="form-text">Jobs matching any of these keywords will be auto-applied to.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Categories') }}</label>
                        <select name="category_ids[]" class="form-select" multiple size="4">
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}" {{ in_array($id, $preference->category_ids ?? []) ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Countries') }}</label>
                        <select name="country_ids[]" class="form-select" multiple size="4">
                            @foreach($countries as $id => $name)
                                <option value="{{ $id }}" {{ in_array($id, $preference->country_ids ?? []) ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Location Keyword') }}</label>
                        <input type="text" name="location_keyword" class="form-control"
                               value="{{ $preference->location_keyword ?? '' }}"
                               placeholder="e.g. Johannesburg, Cape Town">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Match Score Threshold') }}</label>
                        <div class="input-group">
                            <input type="number" name="match_score_threshold" class="form-control"
                                   value="{{ $preference->match_score_threshold ?? 60 }}"
                                   min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">Only apply to jobs where AI match score is at or above this threshold.</div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">{{ __('Blacklisted Companies') }}</label>
                        <select name="blacklisted_company_ids[]" class="form-select" multiple size="3">
                            @php
                                $companies = \Botble\JobBoard\Models\Company::query()->orderBy('name')->pluck('name', 'id');
                            @endphp
                            @foreach($companies as $id => $name)
                                <option value="{{ $id }}" {{ in_array($id, $preference->blacklisted_company_ids ?? []) ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Auto Apply will never apply to jobs from these companies.</div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                   id="autoApplyActive"
                                   {{ ($preference->is_active ?? false) ? 'checked' : '' }}
                                   {{ !$hasCv ? 'disabled' : '' }}>
                            <label class="form-check-label" for="autoApplyActive">
                                <strong>{{ __('Enable Auto Apply') }}</strong>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> {{ __('Save Preferences') }}
                    </button>
                    @if($preference && $preference->is_active && $quota && $quota->hasRemaining())
                        <a href="{{ route('public.account.auto-apply.backfill') }}" class="btn btn-outline-info ms-2">
                            <i class="ti ti-search me-1"></i> {{ __('Review Recent Matching Jobs') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Application Log --}}
    @if($logs->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">{{ __('Recent Auto Applications') }}</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Job') }}</th>
                                <th>{{ __('Sent To') }}</th>
                                <th>{{ __('Score') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>
                                        @if($log->job)
                                            <a href="{{ $log->job->url ?? '#' }}" target="_blank">
                                                {{ Str::limit($log->job->name, 40) }}
                                            </a>
                                        @else
                                            <span class="text-muted">Deleted</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $log->email_sent_to }}</td>
                                    <td>
                                        @php
                                            $scoreBg = $log->match_score >= 70 ? 'success' : ($log->match_score >= 40 ? 'warning' : 'danger');
                                        @endphp
                                        <span class="badge bg-{{ $scoreBg }}">{{ $log->match_score }}%</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusBg = match($log->status) {
                                                'sent' => 'success',
                                                'failed' => 'danger',
                                                'skipped_low_score' => 'warning',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusBg }}">{{ ucwords(str_replace('_', ' ', $log->status)) }}</span>
                                    </td>
                                    <td class="text-muted small">{{ $log->sent_at?->diffForHumans() }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info email-preview-btn"
                                            data-subject="{{ e($log->ai_email_subject) }}"
                                            data-body="{{ e($log->ai_email_body) }}"
                                            data-score="{{ $log->match_score }}"
                                            data-reasons="{{ e(json_encode($log->match_reasons ?? [])) }}">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Email Preview Modal --}}
<div class="modal fade" id="emailPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Application Email Preview') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><strong>Match Score:</strong> <span id="previewScore" class="badge bg-info"></span></div>
                <div class="mb-2"><strong>Reasons:</strong> <span id="previewReasons" class="text-muted"></span></div>
                <hr>
                <div class="mb-2"><strong>Subject:</strong> <span id="previewSubject"></span></div>
                <div class="card bg-light p-3">
                    <pre id="previewBody" style="white-space:pre-wrap;font-family:inherit;margin:0;"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.email-preview-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('previewSubject').textContent = this.dataset.subject || '(none)';
            document.getElementById('previewBody').textContent = this.dataset.body || '(none)';
            document.getElementById('previewScore').textContent = (this.dataset.score || '0') + '%';
            try {
                var reasons = JSON.parse(this.dataset.reasons || '[]');
                document.getElementById('previewReasons').textContent = reasons.join('; ') || 'N/A';
            } catch(e) {
                document.getElementById('previewReasons').textContent = 'N/A';
            }
            var modal = new bootstrap.Modal(document.getElementById('emailPreviewModal'));
            modal.show();
        });
    });
</script>
@endpush
@endsection
