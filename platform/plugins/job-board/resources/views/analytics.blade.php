@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::stat-widget class="mb-3">
        <x-core::stat-widget.item
            :label="trans('plugins/job-board::job.analytics.total_views')"
            :value="$job->views"
            icon="ti ti-eye"
            color="primary"
        />

        <x-core::stat-widget.item
            :label="trans('plugins/job-board::job.analytics.views_today')"
            :value="$viewsToday"
            icon="ti ti-clock"
            color="success"
        />

        <x-core::stat-widget.item
            :label="trans('plugins/job-board::job.analytics.number_of_favorites')"
            :value="$numberSaved"
            icon="ti ti-heart"
            color="danger"
        />

        <x-core::stat-widget.item
            :label="trans('plugins/job-board::job.analytics.applicants')"
            :value="$applicants"
            icon="ti ti-users"
            color="info"
        />
    </x-core::stat-widget>

    <div class="row row-cols-1 row-cols-md-2 row-cards">
        <div class="col">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/job-board::general.top_referrers') }}
                    </x-core::card.title>
                </x-core::card.header>
                @if ($referrers->isNotEmpty())
                    <x-core::table>
                        <x-core::table.header>
                            <th>#</th>
                            <th>{{ trans('plugins/job-board::general.url') }}</th>
                            <th>{{ trans('plugins/job-board::general.views') }}</th>
                        </x-core::table.header>
                        <x-core::table.body>
                            @foreach ($referrers as $referrer)
                                <x-core::table.body.row>
                                    <x-core::table.body.cell>{{ $loop->iteration }}</x-core::table.body.cell>
                                    <x-core::table.body.cell>{{ $referrer->referer }}</x-core::table.body.cell>
                                    <x-core::table.body.cell>{{ $referrer->total }}</x-core::table.body.cell>
                                </x-core::table.body.row>
                            @endforeach
                        </x-core::table.body>
                    </x-core::table>
                @else
                    <x-core::empty-state
                        :title="trans('No data')"
                        :subtitle="trans('There are no data to display.')"
                    />
                @endif
            </x-core::card>
        </div>
        <div class="col">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/job-board::general.top_countries') }}
                    </x-core::card.title>
                </x-core::card.header>
                @if ($countries->isNotEmpty())
                    <x-core::table>
                        <x-core::table.header>
                            <x-core::table.header.cell>
                                #
                            </x-core::table.header.cell>
                            <x-core::table.header.cell>
                                {{ trans('plugins/job-board::general.country') }}
                            </x-core::table.header.cell>
                            <x-core::table.header.cell>
                                {{ trans('plugins/job-board::general.views') }}
                            </x-core::table.header.cell>
                        </x-core::table.header>
                        <x-core::table.body>
                            @foreach ($countries as $country)
                                <x-core::table.body.row>
                                    <x-core::table.body.cell>{{ $loop->iteration }}</x-core::table.body.cell>
                                    <x-core::table.body.cell>{{ $country->country_full }}</x-core::table.body.cell>
                                    <x-core::table.body.cell>{{ $country->total }}</x-core::table.body.cell>
                                </x-core::table.body.row>
                            @endforeach
                        </x-core::table.body>
                    </x-core::table>
                @else
                    <x-core::empty-state
                        :title="trans('No data')"
                        :subtitle="trans('There are no data to display.')"
                    />
                @endif
            </x-core::card>
        </div>
    </div>
@stop
