@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    {{-- Summary cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Data Points</div>
                    <div class="h2 mb-0">{{ number_format($totalDataPoints) }}</div>
                    <div class="text-muted small">Jobs with salary data</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Overall Median</div>
                    <div class="h2 mb-0">
                        @if($overallBenchmark['count'] ?? 0)
                            K{{ number_format($overallBenchmark['median'] ?? 0) }}
                        @else
                            —
                        @endif
                    </div>
                    <div class="text-muted small">Monthly ZMW</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Top Sector</div>
                    <div class="h2 mb-0" style="font-size:1.1rem;line-height:1.8">
                        {{ $byCategory->first()['name'] ?? '—' }}
                    </div>
                    <div class="text-muted small">
                        @if($byCategory->isNotEmpty())
                            K{{ number_format($byCategory->first()['median']) }}/mo median
                        @endif
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-3 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Top Title</div>
                    <div class="h2 mb-0" style="font-size:1.1rem;line-height:1.8">
                        {{ $topTitles->first()['title'] ?? '—' }}
                    </div>
                    <div class="text-muted small">
                        @if($topTitles->isNotEmpty())
                            K{{ number_format($topTitles->first()['median']) }}/mo median
                        @endif
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('salary-analytics.index') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <select class="form-select" name="months_back">
                @foreach([3 => 'Last 3 months', 6 => 'Last 6 months', 12 => 'Last 12 months', 24 => 'Last 24 months'] as $val => $label)
                    <option value="{{ $val }}" @selected($monthsBack == $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" type="submit">Apply</button>
        </div>
        <div class="col-auto">
            <a href="{{ route('salary-checker') }}" class="btn btn-outline-secondary" target="_blank">
                <i class="ti ti-external-link me-1"></i>Public Salary Checker
            </a>
        </div>
    </form>

    <div class="row g-3">
        {{-- Top paying titles --}}
        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Top Paying Job Titles</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body class="p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th class="text-end">Min</th>
                                    <th class="text-end">Median</th>
                                    <th class="text-end">Max</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topTitles as $i => $row)
                                    <tr>
                                        <td class="text-muted">{{ $i + 1 }}</td>
                                        <td>{{ $row['title'] }}</td>
                                        <td class="text-end">K{{ number_format($row['min']) }}</td>
                                        <td class="text-end fw-semibold">K{{ number_format($row['median']) }}</td>
                                        <td class="text-end">K{{ number_format($row['max']) }}</td>
                                        <td class="text-end text-muted">{{ $row['count'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No data for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>

        {{-- By category --}}
        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Salary by Sector / Category</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body class="p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Min</th>
                                    <th class="text-end">Median</th>
                                    <th class="text-end">Max</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byCategory as $row)
                                    <tr>
                                        <td>{{ $row['name'] }}</td>
                                        <td class="text-end">K{{ number_format($row['min']) }}</td>
                                        <td class="text-end fw-semibold">K{{ number_format($row['median']) }}</td>
                                        <td class="text-end">K{{ number_format($row['max']) }}</td>
                                        <td class="text-end text-muted">{{ $row['count'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">No data for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>

        {{-- By city --}}
        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Salary by City / Location</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body class="p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>City</th>
                                    <th class="text-end">Min</th>
                                    <th class="text-end">Median</th>
                                    <th class="text-end">Max</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byCity->take(15) as $row)
                                    <tr>
                                        <td>{{ $row['city'] }}</td>
                                        <td class="text-end">K{{ number_format($row['min']) }}</td>
                                        <td class="text-end fw-semibold">K{{ number_format($row['median']) }}</td>
                                        <td class="text-end">K{{ number_format($row['max']) }}</td>
                                        <td class="text-end text-muted">{{ $row['count'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">No data for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>

        {{-- Monthly trend --}}
        <div class="col-lg-6">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Monthly Salary Trend (Median ZMW)</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body class="p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end">Median / mo</th>
                                    <th class="text-end">Postings</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($trends as $row)
                                    <tr>
                                        <td>{{ $row['month'] }}</td>
                                        <td class="text-end fw-semibold">
                                            {{ $row['median'] !== null ? 'K' . number_format($row['median']) : '—' }}
                                        </td>
                                        <td class="text-end text-muted">{{ $row['count'] ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">No trend data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>
@endsection
