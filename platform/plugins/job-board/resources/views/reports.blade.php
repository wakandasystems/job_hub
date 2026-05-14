@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row g-4">
        <div class="col-12">
            <x-core::stat-widget class="mb-0">
                <x-core::stat-widget.item
                    :label="trans('plugins/job-board::job-board.reports.total_jobs')"
                    :value="number_format($totalJobs)"
                    icon="ti ti-briefcase"
                    color="primary"
                />

                <x-core::stat-widget.item
                    :label="trans('plugins/job-board::job-board.reports.active_jobs')"
                    :value="number_format($activeJobs)"
                    icon="ti ti-check"
                    color="success"
                />

                <x-core::stat-widget.item
                    :label="trans('plugins/job-board::job-board.reports.expired_jobs')"
                    :value="number_format($expiredJobs)"
                    icon="ti ti-clock"
                    color="warning"
                />

                <x-core::stat-widget.item
                    :label="trans('plugins/job-board::job-board.reports.featured_jobs')"
                    :value="number_format($featuredJobs)"
                    icon="ti ti-star"
                    color="info"
                />
            </x-core::stat-widget>
        </div>

        <div class="col-12">
            <x-core::stat-widget class="mb-0">
                <x-core::stat-widget.item
                    :label="trans('plugins/job-board::job-board.reports.total_applications')"
                    :value="number_format($totalApplications)"
                    icon="ti ti-file-description"
                    color="primary"
                />

                <x-core::stat-widget.item
                    :label="trans('plugins/job-board::job-board.reports.pending_applications')"
                    :value="number_format($applicationsByStatus[\Botble\JobBoard\Enums\JobApplicationStatusEnum::PENDING] ?? 0)"
                    icon="ti ti-hourglass"
                    color="warning"
                />

                <x-core::stat-widget.item
                    :label="trans('plugins/job-board::job-board.reports.approved_applications')"
                    :value="number_format($applicationsByStatus[\Botble\JobBoard\Enums\JobApplicationStatusEnum::CHECKED] ?? 0)"
                    icon="ti ti-check"
                    color="success"
                />

                <x-core::stat-widget.item
                    :label="trans('plugins/job-board::job-board.reports.rejected_applications')"
                    :value="0"
                    icon="ti ti-x"
                    color="danger"
                />
            </x-core::stat-widget>
        </div>

        <div class="col-12">
            <x-core::stat-widget class="mb-0">
                <x-core::stat-widget.item
                    :label="trans('plugins/job-board::job-board.reports.total_companies')"
                    :value="number_format($totalCompanies)"
                    icon="ti ti-building"
                    color="primary"
                />

                <x-core::stat-widget.item
                    :label="trans('plugins/job-board::job-board.reports.featured_companies')"
                    :value="number_format($featuredCompanies)"
                    icon="ti ti-star"
                    color="info"
                />
            </x-core::stat-widget>
        </div>

        <div class="col-lg-6 col-12">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/job-board::job-board.reports.application_trends') }}
                    </x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div id="application-trends-chart" style="height: 300px;"></div>
                </x-core::card.body>
            </x-core::card>
        </div>

        <div class="col-lg-6 col-12">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/job-board::job-board.reports.jobs_by_category') }}
                    </x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div id="jobs-by-category-chart" style="height: 300px;"></div>
                </x-core::card.body>
            </x-core::card>
        </div>

        <div class="col-lg-6 col-12">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/job-board::job-board.reports.jobs_by_type') }}
                    </x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div id="jobs-by-type-chart" style="height: 300px;"></div>
                </x-core::card.body>
            </x-core::card>
        </div>

        <div class="col-lg-6 col-12">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/job-board::job-board.reports.applications_by_location') }}
                    </x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div id="applications-by-location-chart" style="height: 300px;"></div>
                </x-core::card.body>
            </x-core::card>
        </div>

        <div class="col-12">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/job-board::job-board.reports.most_viewed_jobs') }}
                    </x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="text-nowrap" width="60">{{ trans('core/base::tables.id') }}</th>
                                    <th>{{ trans('core/base::tables.name') }}</th>
                                    <th class="text-nowrap text-end" width="100">{{ trans('plugins/job-board::job-board.reports.views') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mostViewedJobs as $job)
                                    <tr>
                                        <td class="text-start">{{ $job->id }}</td>
                                        <td>
                                            <a href="{{ route('jobs.edit', $job->id) }}">{{ Str::limit($job->name, 50) }}</a>
                                        </td>
                                        <td class="text-end">{{ number_format($job->views) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>
@endsection

@push('footer')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Application Trends Chart
        var applicationTrendsOptions = {
            series: [{
                name: '{{ trans('plugins/job-board::job-board.reports.applications') }}',
                data: @json($applicationTrends['counts'])
            }],
            chart: {
                type: 'area',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth'
            },
            xaxis: {
                categories: @json($applicationTrends['dates']),
                labels: {
                    rotate: -45,
                    rotateAlways: false,
                    hideOverlappingLabels: true,
                    trim: true
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                    }
                }
            },
            responsive: [{
                breakpoint: 576,
                options: {
                    xaxis: {
                        labels: {
                            rotate: -90,
                            offsetY: 0
                        }
                    }
                }
            }]
        };
        new ApexCharts(document.querySelector("#application-trends-chart"), applicationTrendsOptions).render();

        // Jobs by Category Chart
        var jobsByCategoryOptions = {
            series: @json($jobsByCategory['counts']),
            chart: {
                type: 'pie',
                height: 300
            },
            labels: @json($jobsByCategory['labels']),
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                fontSize: '14px'
            },
            responsive: [{
                breakpoint: 768,
                options: {
                    legend: {
                        position: 'bottom',
                        fontSize: '12px'
                    }
                }
            }, {
                breakpoint: 480,
                options: {
                    legend: {
                        position: 'bottom',
                        fontSize: '10px'
                    }
                }
            }]
        };
        new ApexCharts(document.querySelector("#jobs-by-category-chart"), jobsByCategoryOptions).render();

        // Jobs by Type Chart
        var jobsByTypeOptions = {
            series: [{
                name: '{{ trans('plugins/job-board::job-board.reports.jobs') }}',
                data: @json($jobsByType['counts'])
            }],
            chart: {
                type: 'bar',
                height: 300
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    distributed: false,
                    dataLabels: {
                        position: 'bottom'
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: @json($jobsByType['labels']),
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            responsive: [{
                breakpoint: 576,
                options: {
                    plotOptions: {
                        bar: {
                            horizontal: true
                        }
                    },
                    xaxis: {
                        labels: {
                            style: {
                                fontSize: '10px'
                            }
                        }
                    }
                }
            }]
        };
        new ApexCharts(document.querySelector("#jobs-by-type-chart"), jobsByTypeOptions).render();

        // Applications by Location Chart
        var applicationsByLocationOptions = {
            series: [{
                name: '{{ trans('plugins/job-board::job-board.reports.applications') }}',
                data: @json($applicationsByLocation['counts'])
            }],
            chart: {
                type: 'bar',
                height: 300
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    distributed: false,
                    dataLabels: {
                        position: 'bottom'
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: @json($applicationsByLocation['labels']),
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            responsive: [{
                breakpoint: 576,
                options: {
                    plotOptions: {
                        bar: {
                            horizontal: true
                        }
                    },
                    xaxis: {
                        labels: {
                            style: {
                                fontSize: '10px'
                            }
                        }
                    }
                }
            }]
        };
        new ApexCharts(document.querySelector("#applications-by-location-chart"), applicationsByLocationOptions).render();
    });
</script>
@endpush
