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
                            <label class="form-label required">Credit Cost</label>
                            <input type="number" step="1" class="form-control @error('price') is-invalid @enderror"
                                name="price" min="0"
                                value="{{ (int) ceil((float) old('price', $package->price ?? 0)) }}" required>
                            <div class="form-text">Credits deducted from the employer balance when this boost is used.</div>
                            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <input type="hidden" name="currency" value="CRD">
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
