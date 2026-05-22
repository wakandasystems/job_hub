@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    @if($packages->isEmpty())
        <x-core::card>
            <x-core::card.body>
                <div class="empty">
                    <div class="empty-icon">
                        <x-core::icon name="ti ti-coins" />
                    </div>
                    <p class="empty-title">{{ __('No credit packages available') }}</p>
                    <p class="empty-subtitle text-muted">{{ __('No job post credit packages are available yet. Check back soon.') }}</p>
                </div>
            </x-core::card.body>
        </x-core::card>
    @else
        <div class="row row-cols-1 row-cols-lg-3 mb-3 row-cards">
            @foreach ($packages as $package)
                <div class="col">
                    <div @class(['card card-md box-package h-100', 'active' => $package->is_default])>
                        @if ($package->percent_save)
                            <div class="ribbon ribbon-top ribbon-bookmark bg-green">
                                {{ $package->percent_save_text }}
                                <span class="sr-only">{{ trans('plugins/job-board::dashboard.save') }}</span>
                            </div>
                        @endif

                        <div class="card-body">
                            <div class="box-package-price">
                                <h4>{{ number_format($package->number_of_listings) }} {{ __('credits') }}</h4>
                            </div>

                            <div class="box-package-title">
                                {{ $package->name }}
                            </div>

                            <p class="text-muted mb-0">{{ $package->price ? $package->price_text : trans('plugins/job-board::dashboard.free_label') }}</p>

                            <div class="text-center mt-4">
                                <x-core::form :url="route('public.account.package.subscribe.put')" method="put">
                                    <input type="hidden" name="id" value="{{ $package->id }}">
                                    <x-core::button type="submit" class="w-100" color="{{ $package->is_default ? 'success' : null }}" :disabled="$package->isPurchased()">
                                        {{ $package->isPurchased() ? trans('plugins/job-board::dashboard.purchased_label') : __('Purchase credits') }}
                                    </x-core::button>
                                </x-core::form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if (auth('account')->user()->transactions()->exists())
        <x-core::card>
            <x-core::card.header>
                <x-core::card.title>
                    {{ trans('plugins/job-board::dashboard.transactions_title') }}
                </x-core::card.title>
            </x-core::card.header>
            <payment-history-component
                url="{{ route('public.account.ajax.transactions') }}"
                v-slot="{ isLoading, isLoadingMore, data, getData }"
            >
                <x-core::loading v-if="isLoading" />

                <template v-else>
                    <div class="empty" v-if="data.meta.total === 0">
                        <div class="empty-icon">
                            <x-core::icon name="ti ti-exclamation-circle" />
                        </div>
                        <p class="empty-title">
                            {{ trans('plugins/job-board::dashboard.oops') }}
                        </p>
                        <p class="empty-subtitle text-muted">
                            {{ trans('plugins/job-board::dashboard.no_transactions') }}
                        </p>
                    </div>

                    <div v-if="data.meta.total !== 0" class="list-group list-group-flush">
                        <div v-for="item in data.data" :key="item.id" class="list-group-item">
                            <x-core::icon name="ti ti-clock" class="me-2" />
                            <span
                                :title="$sanitize(item.description, { allowedTags: [] })"
                                v-html="$sanitize(item.description)"
                            ></span>
                        </div>
                    </div>
                    <x-core::card.footer v-if="data.links.next">
                        <a href="javascript:void(0)" v-if="!isLoadingMore" @click="getData(data.links.next)">
                            {{  trans('plugins/job-board::dashboard.load_more') }}
                        </a>
                        <a href="javascript:void(0)" v-if="isLoadingMore">
                            {{ trans('plugins/job-board::dashboard.loading_more') }}
                        </a>
                    </x-core::card.footer>
                </template>
            </payment-history-component>
        </x-core::card>
    @endif
@stop
