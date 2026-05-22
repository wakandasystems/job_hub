<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ isset($package) && $package ? 'Edit Package' : 'New Featured Package' }}</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label required">Package Name</label>
                            <input class="form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name', $package->name ?? '') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" min="0"
                                value="{{ old('sort_order', $package->sort_order ?? 0) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2">{{ old('description', $package->description ?? '') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Duration (days)</label>
                            <input type="number" class="form-control @error('duration_days') is-invalid @enderror"
                                name="duration_days" min="0"
                                value="{{ old('duration_days', $package->duration_days ?? 7) }}" required>
                            <div class="form-text">Set to <strong>0</strong> for no expiry.</div>
                            @error('duration_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Price</label>
                            <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror"
                                name="price" min="0"
                                value="{{ old('price', $package->price ?? 0) }}" required>
                            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Currency</label>
                            @php
                                $currencies = [
                                    'AED' => 'AED — UAE Dirham',
                                    'AOA' => 'AOA — Angolan Kwanza',
                                    'AUD' => 'AUD — Australian Dollar',
                                    'BIF' => 'BIF — Burundian Franc',
                                    'BRL' => 'BRL — Brazilian Real',
                                    'BWP' => 'BWP — Botswana Pula',
                                    'CAD' => 'CAD — Canadian Dollar',
                                    'CDF' => 'CDF — Congolese Franc',
                                    'CHF' => 'CHF — Swiss Franc',
                                    'CNY' => 'CNY — Chinese Yuan',
                                    'CVE' => 'CVE — Cape Verdean Escudo',
                                    'DJF' => 'DJF — Djiboutian Franc',
                                    'DKK' => 'DKK — Danish Krone',
                                    'DZD' => 'DZD — Algerian Dinar',
                                    'EGP' => 'EGP — Egyptian Pound',
                                    'ERN' => 'ERN — Eritrean Nakfa',
                                    'ETB' => 'ETB — Ethiopian Birr',
                                    'EUR' => 'EUR — Euro',
                                    'GBP' => 'GBP — British Pound',
                                    'GHS' => 'GHS — Ghanaian Cedi',
                                    'GMD' => 'GMD — Gambian Dalasi',
                                    'GNF' => 'GNF — Guinean Franc',
                                    'HKD' => 'HKD — Hong Kong Dollar',
                                    'IDR' => 'IDR — Indonesian Rupiah',
                                    'ILS' => 'ILS — Israeli Shekel',
                                    'INR' => 'INR — Indian Rupee',
                                    'JPY' => 'JPY — Japanese Yen',
                                    'KES' => 'KES — Kenyan Shilling',
                                    'KRW' => 'KRW — South Korean Won',
                                    'LRD' => 'LRD — Liberian Dollar',
                                    'LSL' => 'LSL — Lesotho Loti',
                                    'LYD' => 'LYD — Libyan Dinar',
                                    'MAD' => 'MAD — Moroccan Dirham',
                                    'MGA' => 'MGA — Malagasy Ariary',
                                    'MUR' => 'MUR — Mauritian Rupee',
                                    'MWK' => 'MWK — Malawian Kwacha',
                                    'MXN' => 'MXN — Mexican Peso',
                                    'MZN' => 'MZN — Mozambican Metical',
                                    'NAD' => 'NAD — Namibian Dollar',
                                    'NGN' => 'NGN — Nigerian Naira',
                                    'NOK' => 'NOK — Norwegian Krone',
                                    'NZD' => 'NZD — New Zealand Dollar',
                                    'PHP' => 'PHP — Philippine Peso',
                                    'PKR' => 'PKR — Pakistani Rupee',
                                    'PLN' => 'PLN — Polish Zloty',
                                    'QAR' => 'QAR — Qatari Riyal',
                                    'RWF' => 'RWF — Rwandan Franc',
                                    'SAR' => 'SAR — Saudi Riyal',
                                    'SCR' => 'SCR — Seychellois Rupee',
                                    'SDG' => 'SDG — Sudanese Pound',
                                    'SEK' => 'SEK — Swedish Krona',
                                    'SGD' => 'SGD — Singapore Dollar',
                                    'SLL' => 'SLL — Sierra Leonean Leone',
                                    'SOS' => 'SOS — Somali Shilling',
                                    'STD' => 'STD — São Tomé Dobra',
                                    'SZL' => 'SZL — Swazi Lilangeni',
                                    'THB' => 'THB — Thai Baht',
                                    'TND' => 'TND — Tunisian Dinar',
                                    'TRY' => 'TRY — Turkish Lira',
                                    'TZS' => 'TZS — Tanzanian Shilling',
                                    'UGX' => 'UGX — Ugandan Shilling',
                                    'USD' => 'USD — US Dollar',
                                    'XAF' => 'XAF — Central African CFA Franc',
                                    'XOF' => 'XOF — West African CFA Franc',
                                    'ZAR' => 'ZAR — South African Rand',
                                    'ZMW' => 'ZMW — Zambian Kwacha',
                                    'ZWL' => 'ZWL — Zimbabwean Dollar',
                                ];
                                $selectedCurrency = old('currency', $package->currency ?? 'USD');
                            @endphp
                            <select id="currency-select"
                                class="form-select @error('currency') is-invalid @enderror"
                                name="currency" required>
                                @foreach($currencies as $code => $label)
                                    <option value="{{ $code }}" @selected($selectedCurrency === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Badge Label</label>
                            <input class="form-control @error('badge_label') is-invalid @enderror" name="badge_label" maxlength="50"
                                value="{{ old('badge_label', $package->badge_label ?? 'Featured') }}" required>
                            <div class="form-text">Text shown on the job card (e.g. <em>Featured</em>, <em>Sponsored</em>).</div>
                            @error('badge_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    {{ old('is_active', $package->is_active ?? true) ? 'checked' : '' }}>
                                <span class="form-check-label">Active (visible to employers)</span>
                            </label>
                        </div>
                    </div>
                </x-core::card.body>
                <x-core::card.footer>
                    <button class="btn btn-primary" type="submit">Save Package</button>
                    <a class="btn btn-outline-secondary" href="{{ route('featured-packages.index') }}">Cancel</a>
                </x-core::card.footer>
            </x-core::card>
        </div>
    </div>
</form>

@push('footer')
<script>
    $(document).ready(function () {
        $('#currency-select').select2({
            width: '100%',
            dropdownCssClass: 'currency-select2-dropdown',
            placeholder: 'Search currency…',
        });
    });
</script>
@endpush

@push('header')
<style>
    .currency-select2-dropdown .select2-results__options {
        max-height: 108px; /* ~3 items */
        overflow-y: auto;
    }
</style>
@endpush
