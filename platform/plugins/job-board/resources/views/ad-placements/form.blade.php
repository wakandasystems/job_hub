<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>{{ isset($placement) && $placement ? 'Edit Ad Placement' : 'New Ad Placement' }}</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label required">Placement Name</label>
                            <input class="form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name', $placement->name ?? '') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" min="0"
                                value="{{ old('sort_order', $placement->sort_order ?? 0) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Location Key</label>
                            <input class="form-control @error('location') is-invalid @enderror" name="location" maxlength="120"
                                value="{{ old('location', $placement->location ?? '') }}" required>
                            <div class="form-text">
                                Must match a theme ad slot, e.g. <code>job_list_before</code>, <code>job_before</code>,
                                <code>job_after</code>, <code>job_list_after</code>, <code>post_before</code>, <code>post_after</code>,
                                <code>post_list_before</code>, <code>post_list_after</code>, <code>company_before</code>,
                                <code>company_after</code>, <code>company_sidebar_before</code>, <code>company_sidebar_after</code>,
                                <code>candidate_before</code>, <code>candidate_after</code>, <code>candidate_sidebar_before</code>,
                                <code>candidate_sidebar_after</code>, <code>candidate_list_before</code>, <code>candidate_list_after</code>,
                                <code>company_list_before</code>, <code>company_list_after</code>, <code>blog_sidebar_before</code>,
                                <code>blog_sidebar_after</code>, <code>main_content_before</code>, <code>main_content_after</code>,
                                <code>footer_before</code>, <code>footer_after</code>.
                            </div>
                            @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2">{{ old('description', $placement->description ?? '') }}</textarea>
                            <div class="form-text">Shown to employers on the Advertise page to describe what they're buying.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Duration (days)</label>
                            <input type="number" class="form-control @error('duration_days') is-invalid @enderror"
                                name="duration_days" min="0"
                                value="{{ old('duration_days', $placement->duration_days ?? 30) }}" required>
                            <div class="form-text">Set to <strong>0</strong> for no expiry.</div>
                            @error('duration_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Price</label>
                            <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror"
                                name="price" min="0"
                                value="{{ old('price', $placement->price ?? 0) }}" required>
                            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Currency</label>
                            <input class="form-control @error('currency') is-invalid @enderror" name="currency" maxlength="3"
                                value="{{ old('currency', $placement->currency ?? 'USD') }}" required>
                            @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    {{ old('is_active', $placement->is_active ?? true) ? 'checked' : '' }}>
                                <span class="form-check-label">Active (visible to employers)</span>
                            </label>
                        </div>
                    </div>
                </x-core::card.body>
                <x-core::card.footer>
                    <button class="btn btn-primary" type="submit">Save Placement</button>
                    <a class="btn btn-outline-secondary" href="{{ route('ad-placements.index') }}">Cancel</a>
                </x-core::card.footer>
            </x-core::card>
        </div>
    </div>
</form>
