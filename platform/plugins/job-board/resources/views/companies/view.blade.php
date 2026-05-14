@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row">
        <div class="col-md-3">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/job-board::company.information') }}
                    </x-core::card.title>
                </x-core::card.header>
                
                <x-core::card.body>
                    <div class="text-center mb-3">
                        <img src="{{ $company->logo_thumb }}" alt="{{ $company->name }}" class="rounded" width="100" height="100">
                    </div>
                    
                    <dl class="row">
                        <dt class="col-5">{{ trans('plugins/job-board::company.form.name') }}</dt>
                        <dd class="col-7">{{ $company->name }}</dd>
                        
                        <dt class="col-5">{{ trans('plugins/job-board::company.form.email') }}</dt>
                        <dd class="col-7">{{ $company->email ?: '—' }}</dd>
                        
                        <dt class="col-5">{{ trans('plugins/job-board::company.form.phone') }}</dt>
                        <dd class="col-7">{{ $company->phone ?: '—' }}</dd>
                        
                        <dt class="col-5">{{ trans('core/base::tables.status') }}</dt>
                        <dd class="col-7">{!! BaseHelper::clean($company->status->toHtml()) !!}</dd>
                        
                        <dt class="col-5">{{ trans('plugins/job-board::company.form.is_featured') }}</dt>
                        <dd class="col-7">
                            @if($company->is_featured)
                                <span class="badge bg-success text-success-fg">{{ trans('core/base::base.yes') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ trans('core/base::base.no') }}</span>
                            @endif
                        </dd>
                        
                        <dt class="col-5">{{ trans('plugins/job-board::company.total_jobs') }}</dt>
                        <dd class="col-7">
                            <span class="badge bg-blue text-blue-fg">{{ $company->jobs_count }}</span>
                        </dd>
                        
                        <dt class="col-5">{{ trans('plugins/job-board::company.total_reviews') }}</dt>
                        <dd class="col-7">
                            <span class="badge bg-green text-green-fg">{{ $company->reviews_count }}</span>
                        </dd>
                    </dl>
                </x-core::card.body>
            </x-core::card>
            
            {{-- Verification Section --}}
            <div class="card mt-3">
                @if($company->is_verified)
                    <div class="card-status-top bg-success"></div>
                @else
                    <div class="card-status-top bg-warning"></div>
                @endif
                
                <div class="card-header">
                    <h3 class="card-title">
                        <x-core::icon name="ti ti-shield-check" />
                        {{ trans('plugins/job-board::company.verification_section') }}
                    </h3>
                </div>
                
                <div class="card-body">
                    @if($company->is_verified)
                        <div class="alert alert-success" role="alert">
                            <div class="d-flex">
                                <div class="me-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M5 12l5 5l10 -10"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="alert-title">{{ trans('plugins/job-board::company.verified') }}</h4>
                                    <div class="text-secondary">{{ trans('plugins/job-board::company.company_verified_successfully') }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="datagrid">
                                    @if($company->verifiedBy)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ trans('plugins/job-board::company.verified_by') }}</div>
                                            <div class="datagrid-content">
                                                <strong>{{ $company->verifiedBy->name }}</strong>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($company->verified_at)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ trans('plugins/job-board::company.verified_at') }}</div>
                                            <div class="datagrid-content">
                                                {{ $company->verified_at->format('M d, Y H:i') }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            @if($company->verification_note)
                                <div class="col-12">
                                    <div class="card bg-blue-lt">
                                        <div class="card-body">
                                            <h4 class="card-title">
                                                <x-core::icon name="ti ti-notes" />
                                                {{ trans('plugins/job-board::company.verification_note') }}
                                            </h4>
                                            <p class="text-secondary mb-0">{{ $company->verification_note }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-warning w-100" data-bs-toggle="modal" data-bs-target="#unverify-company-modal">
                                <x-core::icon name="ti ti-shield-x" />
                                {{ trans('plugins/job-board::company.unverify_company') }}
                            </button>
                        </div>
                    @else
                        <div class="alert alert-warning" role="alert">
                            <div class="d-flex">
                                <div class="me-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="alert-title">{{ trans('plugins/job-board::company.not_verified') }}</h4>
                                    <div class="text-secondary">{{ trans('plugins/job-board::company.company_not_verified_yet') }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted mb-3" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"></path>
                                <circle cx="12" cy="11" r="1"></circle>
                                <line x1="12" y1="12" x2="12" y2="14.5"></line>
                            </svg>
                            <h3>{{ trans('plugins/job-board::company.verification_pending') }}</h3>
                            <p class="text-muted">{{ trans('plugins/job-board::company.click_verify_to_approve') }}</p>
                            
                            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#verify-company-modal">
                                <x-core::icon name="ti ti-shield-check" />
                                {{ trans('plugins/job-board::company.verify_company') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/job-board::company.recent_activity') }}
                    </x-core::card.title>
                </x-core::card.header>
                
                <x-core::card.body>
                    <div class="row">
                        <div class="col-md-6">
                            <h4>{{ trans('plugins/job-board::company.recent_jobs') }}</h4>
                            @if($company->jobs()->count() > 0)
                                <div class="list-group">
                                    @foreach($company->jobs()->latest()->limit(5)->get() as $job)
                                        <a href="{{ route('jobs.edit', $job->id) }}" class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-1">{{ $job->name }}</h5>
                                                    <small>{{ $job->created_at->diffForHumans() }}</small>
                                                </div>
                                                {!! BaseHelper::clean($job->status->toHtml()) !!}
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">{{ trans('plugins/job-board::company.no_jobs_yet') }}</p>
                            @endif
                        </div>
                        
                        <div class="col-md-6">
                            <h4>{{ trans('plugins/job-board::company.company_details') }}</h4>
                            <dl class="row">
                                @if($company->website)
                                    <dt class="col-5">{{ trans('plugins/job-board::company.form.website') }}</dt>
                                    <dd class="col-7"><a href="{{ $company->website }}" target="_blank">{{ $company->website }}</a></dd>
                                @endif
                                
                                @if($company->year_founded)
                                    <dt class="col-5">{{ trans('plugins/job-board::company.form.year_founded') }}</dt>
                                    <dd class="col-7">{{ $company->year_founded }}</dd>
                                @endif
                                
                                @if($company->number_of_employees)
                                    <dt class="col-5">{{ trans('plugins/job-board::company.form.number_of_employees') }}</dt>
                                    <dd class="col-7">{{ $company->number_of_employees }}</dd>
                                @endif
                                
                                @if($company->number_of_offices)
                                    <dt class="col-5">{{ trans('plugins/job-board::company.form.number_of_offices') }}</dt>
                                    <dd class="col-7">{{ $company->number_of_offices }}</dd>
                                @endif
                                
                                @if($company->ceo)
                                    <dt class="col-5">{{ trans('plugins/job-board::company.form.ceo') }}</dt>
                                    <dd class="col-7">{{ $company->ceo }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>
@endsection

@push('footer')
    @if(!$company->is_verified)
        <x-core::modal
            id="verify-company-modal"
            :title="trans('plugins/job-board::company.verify_company_confirmation')"
            button-id="confirm-verify-button"
            :button-label="trans('plugins/job-board::company.verify_company')"
            button-class="btn-success"
            size="md"
        >
            <x-core::form :url="route('companies.verify', $company->id)">
                <div class="alert alert-info" role="alert">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <circle cx="12" cy="12" r="9"></circle>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                <polyline points="11 12 12 12 12 16 13 16"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h4 class="alert-title">{{ trans('plugins/job-board::company.verify_company_confirmation') }}</h4>
                            <div class="text-secondary">{{ trans('plugins/job-board::company.verify_company_confirmation_desc', ['name' => $company->name]) }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <x-core::icon name="ti ti-notes" />
                        {{ trans('plugins/job-board::company.verification_note') }}
                    </label>
                    <textarea 
                        class="form-control" 
                        name="verification_note" 
                        rows="3" 
                        placeholder="{{ trans('plugins/job-board::company.verification_note_placeholder') }}"
                    ></textarea>
                    <small class="form-hint">{{ trans('plugins/job-board::company.verification_note_helper') }}</small>
                </div>
            </x-core::form>
        </x-core::modal>
    @else
        <x-core::modal
            id="unverify-company-modal"
            :title="trans('plugins/job-board::company.unverify_company_confirmation')"
            button-id="confirm-unverify-button"
            :button-label="trans('plugins/job-board::company.unverify_company')"
            button-class="btn-warning"
            size="md"
        >
            <x-core::form :url="route('companies.unverify', $company->id)">
                <div class="alert alert-warning" role="alert">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M10.24 3.957l-8.422 14.06a1.989 1.989 0 0 0 1.7 2.983h16.845a1.989 1.989 0 0 0 1.7 -2.983l-8.423 -14.06a1.989 1.989 0 0 0 -3.4 0z"></path>
                                <path d="M12 9v4"></path>
                                <path d="M12 17h.01"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="alert-title">{{ trans('plugins/job-board::company.unverify_company_confirmation') }}</h4>
                            <div class="text-secondary">{{ trans('plugins/job-board::company.unverify_company_confirmation_desc', ['name' => $company->name]) }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <x-core::icon name="ti ti-notes" />
                        {{ trans('plugins/job-board::company.verification_note') }}
                    </label>
                    <textarea 
                        class="form-control" 
                        name="verification_note" 
                        rows="3" 
                        placeholder="{{ trans('plugins/job-board::company.verification_note_placeholder') }}"
                    ></textarea>
                    <small class="form-hint">{{ trans('plugins/job-board::company.verification_note_helper') }}</small>
                </div>
            </x-core::form>
        </x-core::modal>
    @endif
    
    <script>
        $(document).ready(function() {
            $('#confirm-verify-button').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $form = $('#verify-company-modal form');
                
                $button.addClass('button-loading');
                
                $.ajax({
                    type: 'POST',
                    url: $form.attr('action'),
                    data: $form.serialize(),
                    success: function(response) {
                        if (response.error) {
                            Botble.showNotice('error', response.message);
                        } else {
                            Botble.showNotice('success', response.message);
                            $('#verify-company-modal').modal('hide');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        Botble.handleError(xhr);
                    },
                    complete: function() {
                        $button.removeClass('button-loading');
                    }
                });
            });
            
            $('#confirm-unverify-button').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $form = $('#unverify-company-modal form');
                
                $button.addClass('button-loading');
                
                $.ajax({
                    type: 'POST',
                    url: $form.attr('action'),
                    data: $form.serialize(),
                    success: function(response) {
                        if (response.error) {
                            Botble.showNotice('error', response.message);
                        } else {
                            Botble.showNotice('success', response.message);
                            $('#unverify-company-modal').modal('hide');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        Botble.handleError(xhr);
                    },
                    complete: function() {
                        $button.removeClass('button-loading');
                    }
                });
            });
        });
    </script>
@endpush
