@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    <style>
        .package-billing-toggle .btn {
            min-width: 120px;
        }
    </style>

    <div class="d-flex justify-content-end mb-3">
        <div class="btn-group package-billing-toggle" role="group" aria-label="{{ __('Package billing cycle') }}">
            <input type="radio" class="btn-check" name="package_billing_cycle" id="package-billing-monthly" value="monthly" checked>
            <label class="btn btn-outline-primary" for="package-billing-monthly">{{ __('Monthly') }}</label>

            <input type="radio" class="btn-check" name="package_billing_cycle" id="package-billing-annual" value="annual">
            <label class="btn btn-outline-primary" for="package-billing-annual">{{ __('Annual') }} <span class="badge bg-success ms-1">{{ __('20% off') }}</span></label>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-lg-3 mb-3 row-cards">
        @foreach ($packages as $package)
            @php
                $monthlyAmount = (float) $package->price;
                $annualAmount = round($monthlyAmount * 12 * 0.8, 2);
                $monthlyListings = (int) $package->number_of_listings;
                $annualListings = $monthlyListings * 12;
            @endphp
            <div class="col">
                <div @class(['card card-md box-package h-100', 'active' => $package->is_default])>
                    @if ($package->percent_save)
                        <div class="ribbon ribbon-top ribbon-bookmark bg-green">
                            {{ $package->percent_save_text }}
                            <span class="sr-only">{{ trans('plugins/job-board::dashboard.save') }}</span>
                        </div>
                    @endif

                    <div class="card-body">
                        @if($package->price)
                            <div
                                class="box-package-price d-flex align-items-end"
                                data-monthly-price="{{ format_price($monthlyAmount, $package->currency) }}"
                                data-annual-price="{{ format_price($annualAmount, $package->currency) }}"
                                data-monthly-listings="{{ $monthlyListings }}"
                                data-annual-listings="{{ $annualListings }}"
                            >
                                <h4>{{ format_price($monthlyAmount, $package->currency) }}</h4>
                                <span class="text-muted">/{{ $monthlyListings }} {{ trans('plugins/job-board::dashboard.posts_count') }} / {{ __('month') }}</span>
                            </div>
                        @else
                            <div
                                class="box-package-price"
                                data-monthly-price="{{ trans('plugins/job-board::dashboard.free_label') }}"
                                data-annual-price="{{ trans('plugins/job-board::dashboard.free_label') }}"
                                data-monthly-listings="{{ $monthlyListings }}"
                                data-annual-listings="{{ $annualListings }}"
                            >
                                <h4>{{ trans('plugins/job-board::dashboard.free_label') }}</h4>
                            </div>
                        @endif

                        <div class="box-package-title">
                            {{ $package->name }}
                        </div>

                        @if($package->description)
                            <p class="text-muted">{{ $package->description }}</p>
                        @endif

                        @if($features = $package->formatted_features)
                            <ul class="box-package-features list-unstyled">
                                @foreach($features as $feature)
                                    @continue(! $feature)

                                    <li class="item">
                                        <x-core::icon name="ti ti-check" class="text-success" />
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="text-center mt-4">
                            <x-core::form :url="route('public.account.package.subscribe.put')" method="put">
                                <input type="hidden" name="id" value="{{ $package->id }}">
                                <input type="hidden" name="billing_cycle" value="monthly" class="package-billing-cycle-input">
                                <x-core::button type="submit" class="w-100" color="{{ $package->is_default ? 'success' : null }}" :disabled="$package->isPurchased()">
                                    {{ $package->isPurchased() ? trans('plugins/job-board::dashboard.purchased_label') : trans('plugins/job-board::dashboard.purchase') }}
                                </x-core::button>
                            </x-core::form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var billingInputs = document.querySelectorAll('input[name="package_billing_cycle"]');

            function updatePackageBilling(cycle) {
                document.querySelectorAll('.box-package-price').forEach(function (priceBox) {
                    var price = priceBox.dataset[cycle + 'Price'];
                    var listings = priceBox.dataset[cycle + 'Listings'];
                    var heading = priceBox.querySelector('h4');
                    var meta = priceBox.querySelector('.text-muted');

                    if (heading && price) {
                        heading.textContent = price;
                    }

                    if (meta && listings !== undefined) {
                        meta.textContent = '/' + listings + ' {{ trans('plugins/job-board::dashboard.posts_count') }} / ' + (cycle === 'annual' ? '{{ __('year') }}' : '{{ __('month') }}');
                    }
                });

                document.querySelectorAll('.package-billing-cycle-input').forEach(function (input) {
                    input.value = cycle;
                });
            }

            billingInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    updatePackageBilling(this.value);
                });
            });
        });
    </script>
@stop
