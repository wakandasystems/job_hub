<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ isset($package) && $package ? 'Edit Package' : 'New Package' }}</x-core::card.title>
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
                            <label class="form-label required">Alerts per Month</label>
                            <input type="number" class="form-control @error('alerts_per_month') is-invalid @enderror"
                                name="alerts_per_month" min="0"
                                value="{{ old('alerts_per_month', $package->alerts_per_month ?? 10) }}" required>
                            <div class="form-text">Set to <strong>0</strong> for unlimited.</div>
                            @error('alerts_per_month') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                            <input class="form-control" name="currency" maxlength="3"
                                value="{{ old('currency', $package->currency ?? strtoupper(cms_currency()->getDefaultCurrency()->title ?? 'USD')) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    {{ old('is_active', $package->is_active ?? true) ? 'checked' : '' }}>
                                <span class="form-check-label">Active (visible to candidates)</span>
                            </label>
                        </div>
                    </div>
                </x-core::card.body>
                <x-core::card.footer>
                    <button class="btn btn-primary" type="submit">Save Package</button>
                    <a class="btn btn-outline-secondary" href="{{ route('career-alert-packages.index') }}">Cancel</a>
                </x-core::card.footer>
            </x-core::card>
        </div>
    </div>
</form>
