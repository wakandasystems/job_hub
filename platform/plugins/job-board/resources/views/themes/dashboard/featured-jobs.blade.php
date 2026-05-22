@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    @if(session('success'))
        <x-core::alert type="success">{{ session('success') }}</x-core::alert>
    @endif
    @if(session('error'))
        <x-core::alert type="danger">
            {{ session('error') }}
            <a href="{{ route('public.account.credits') }}" class="ms-2">{{ __('Buy credits') }}</a>
        </x-core::alert>
    @endif

    <p class="text-muted mb-4">
        {{ __('Boost your job post to the top of search results and get more qualified applicants.') }}
        {{ __('You have :credits credit(s).', ['credits' => number_format($account->credits)]) }}
    </p>

    @if($packages->isEmpty())
        <x-core::card>
            <x-core::card.body>
                <div class="empty">
                    <div class="empty-icon">
                        <x-core::icon name="ti ti-star-off" />
                    </div>
                    <p class="empty-title">{{ __('No packages available') }}</p>
                    <p class="empty-subtitle text-muted">{{ __('No featured packages are available yet. Check back soon.') }}</p>
                </div>
            </x-core::card.body>
        </x-core::card>
    @else
        <div class="row row-cols-1 row-cols-lg-3 mb-4 row-cards">
            @foreach($packages as $pkg)
                <div class="col">
                    <div class="card card-md h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-yellow text-yellow-fg mb-2">{{ $pkg->badge_label }}</span>
                                <h4 class="mb-1">{{ $pkg->name }}</h4>
                                @if($pkg->description)
                                    <p class="text-muted mb-0">{{ $pkg->description }}</p>
                                @endif
                            </div>

                            <div class="my-3">
                                <div class="d-flex align-items-center gap-2">
                                    <x-core::icon name="ti ti-clock" class="text-muted" />
                                    <span class="text-muted">{{ $pkg->displayDuration() }}</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-auto pt-3 border-top">
                                <div class="h3 mb-0">{{ number_format((int) ceil($pkg->price)) }} {{ __('credits') }}</div>
                                <button type="button" class="btn btn-primary btn-feature-job"
                                    data-package-id="{{ $pkg->id }}"
                                    data-package-name="{{ $pkg->name }}"
                                    data-credit-cost="{{ number_format((int) ceil($pkg->price)) }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#selectJobModal">
                                    <x-core::icon name="ti ti-star" class="me-1" />
                                    {{ __('Use Credits') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Active featured jobs --}}
    @php
        $activeFeatured = $myJobs->where('is_featured', 1)->filter(function ($j) {
            return ! $j->featured_until || \Carbon\Carbon::parse($j->featured_until)->isFuture();
        });
    @endphp
    @if($activeFeatured->isNotEmpty())
        <x-core::card class="mb-4">
            <x-core::card.header>
                <x-core::card.title>{{ __('Currently Featured') }}</x-core::card.title>
            </x-core::card.header>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ __('Job') }}</th>
                            <th>{{ __('Featured Until') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeFeatured as $j)
                            <tr>
                                <td>{{ $j->name }}</td>
                                <td class="text-green">
                                    {{ $j->featured_until ? \Carbon\Carbon::parse($j->featured_until)->format('d M Y') : __('No expiry') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-core::card>
    @endif

    {{-- Order history --}}
    @if($myOrders->isNotEmpty())
        <x-core::card>
            <x-core::card.header>
                <x-core::card.title>{{ __('My Orders') }}</x-core::card.title>
            </x-core::card.header>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('Ref') }}</th>
                            <th>{{ __('Package') }}</th>
                            <th>{{ __('Job') }}</th>
                            <th>{{ __('Credits Used') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Date') }}</th>
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
                                <td class="text-muted">#{{ str_pad($o->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $o->package?->name ?? '—' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($o->job?->name ?? '—', 35) }}</td>
                                <td>{{ number_format((int) $o->amount) }} {{ __('credits') }}</td>
                                <td><span class="badge {{ $badgeColor }} text-white">{{ ucfirst($o->status) }}</span></td>
                                <td class="text-muted">{{ $o->created_at?->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-core::card>
    @endif

    {{-- Job selector modal --}}
    <div class="modal modal-blur fade" id="selectJobModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Select a Job to Feature') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="featureJobForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3" id="selectedPackageName"></p>
                        @if($myJobs->isEmpty())
                            <div class="empty">
                                <div class="empty-icon">
                                    <x-core::icon name="ti ti-briefcase-off" />
                                </div>
                                <p class="empty-title">{{ __('No jobs posted yet') }}</p>
                                <p class="empty-subtitle text-muted">{{ __('Post a job first before you can feature it.') }}</p>
                                <div class="empty-action">
                                    <a href="{{ route('public.account.jobs.create') }}" class="btn btn-primary">
                                        <x-core::icon name="ti ti-plus" class="me-1" />
                                        {{ __('Post a Job') }}
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Choose a job to feature') }}</label>
                                <select name="job_id" class="form-select" required>
                                    <option value="">— {{ __('Select a job') }} —</option>
                                    @foreach($myJobs as $j)
                                        <option value="{{ $j->id }}">
                                            {{ $j->name }}
                                            @if($j->is_featured && (! $j->featured_until || \Carbon\Carbon::parse($j->featured_until)->isFuture()))
                                                ({{ __('Currently featured') }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    @if($myJobs->isNotEmpty())
                        <div class="modal-footer">
                            <button type="button" class="btn me-auto" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">
                                <x-core::icon name="ti ti-coins" class="me-1" />
                                {{ __('Use Credits') }}
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    @push('footer')
    <script>
        document.querySelectorAll('.btn-feature-job').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var packageId = this.dataset.packageId;
                var packageName = this.dataset.packageName;
                var creditCost = this.dataset.creditCost;
                var baseUrl = '{{ route('public.account.featured-jobs.checkout', ['package' => '__PKG__']) }}';
                document.getElementById('featureJobForm').action = baseUrl.replace('__PKG__', packageId);
                document.getElementById('selectedPackageName').textContent = '{{ __('Package') }}: ' + packageName + ' - ' + creditCost + ' {{ __('credits') }}';
            });
        });
    </script>
    @endpush
@stop
