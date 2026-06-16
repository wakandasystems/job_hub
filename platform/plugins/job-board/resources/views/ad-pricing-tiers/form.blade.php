<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ isset($tier) && $tier ? 'Edit Pricing Tier' : 'New Pricing Tier' }}</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <p class="text-muted">
                        A <strong>reach</strong> is a group of countries an employer can choose to target with their ad.
                        On each ad placement, you'll set a price for every reach &mdash; e.g. <em>"Footer for Zambia"</em>
                        could be $40, <em>"Footer for Zambia + Southern Africa"</em> $80, and <em>"Footer for All Africa"</em> $200.
                        Build reaches from narrow to broad, with each broader reach including the countries from the
                        narrower ones.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label required">Reach Name</label>
                            <input class="form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name', $tier->name ?? '') }}" placeholder="e.g. Zambia Only, Zambia + Southern Africa, All Africa" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" min="0"
                                value="{{ old('sort_order', $tier->sort_order ?? 0) }}">
                            <div class="form-text">Lowest first &mdash; order narrow reaches before broad ones.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Countries in this reach</label>
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllAfrica">Select all African countries</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearCountries">Clear selection</button>
                            </div>
                            <select class="form-select" name="country_ids[]" id="countrySelect" multiple size="12">
                                @php
                                    $selectedCountryIds = old('country_ids', $tier->country_ids ?? []);
                                @endphp
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" {{ in_array($country->id, $selectedCountryIds) ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Hold Ctrl (or Cmd on Mac) to select multiple countries. A visitor's detected country must fall in this list for ads targeted to this reach to be shown to them.</div>
                        </div>
                    </div>
                </x-core::card.body>
                <x-core::card.footer>
                    <button class="btn btn-primary" type="submit">Save Tier</button>
                    <a class="btn btn-outline-secondary" href="{{ route('ad-pricing-tiers.index') }}">Cancel</a>
                </x-core::card.footer>
            </x-core::card>
        </div>
    </div>
</form>

@push('footer')
    <script>
        (function () {
            var africanCountryIds = @json($africanCountryIds ?? []).map(String);
            var select = document.getElementById('countrySelect');

            document.getElementById('selectAllAfrica').addEventListener('click', function () {
                Array.from(select.options).forEach(function (option) {
                    option.selected = africanCountryIds.includes(option.value);
                });
            });

            document.getElementById('clearCountries').addEventListener('click', function () {
                Array.from(select.options).forEach(function (option) {
                    option.selected = false;
                });
            });
        })();
    </script>
@endpush
