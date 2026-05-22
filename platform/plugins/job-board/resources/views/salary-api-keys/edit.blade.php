@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>{{ $key ? 'Edit: ' . $key->name : 'New API Key' }}</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body>
            @if(!$key)
                <div class="alert alert-warning mb-3">
                    <i class="ti ti-alert-triangle me-1"></i>
                    The full API key will be shown <strong>once</strong> after creation. Copy it immediately.
                </div>
            @endif

            <form method="POST"
                action="{{ $key ? route('salary-api-keys.update', $key) : route('salary-api-keys.store') }}">
                @csrf
                @if($key) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name / Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            name="name" value="{{ old('name', $key?->name) }}"
                            placeholder="e.g. ABSA Bank — Payroll Team" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Plan</label>
                        <select class="form-select @error('plan') is-invalid @enderror" name="plan">
                            @foreach(['basic' => 'Basic (500 req/mo)', 'pro' => 'Pro (5,000 req/mo)', 'enterprise' => 'Enterprise (unlimited)'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('plan', $key?->plan ?? 'basic') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Requests / Month</label>
                        <input type="number" class="form-control @error('requests_per_month') is-invalid @enderror"
                            name="requests_per_month"
                            value="{{ old('requests_per_month', $key?->requests_per_month ?? 500) }}"
                            min="1">
                        @error('requests_per_month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Contact Name</label>
                        <input type="text" class="form-control" name="contact_name"
                            value="{{ old('contact_name', $key?->contact_name) }}"
                            placeholder="John Mwale">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Contact Email</label>
                        <input type="email" class="form-control" name="contact_email"
                            value="{{ old('contact_email', $key?->contact_email) }}"
                            placeholder="john@company.com">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Expires At (leave blank = never)</label>
                        <input type="date" class="form-control" name="expires_at"
                            value="{{ old('expires_at', $key?->expires_at?->toDateString()) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2"
                            placeholder="Internal notes about this key…">{{ old('notes', $key?->notes) }}</textarea>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                id="is_active"
                                {{ old('is_active', $key?->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        {{ $key ? 'Save Changes' : 'Generate API Key' }}
                    </button>
                    <a href="{{ route('salary-api-keys.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>

            @if($key)
                <hr class="my-4">
                <div class="row g-2 text-muted small">
                    <div class="col-md-3"><strong>Key prefix:</strong> <code>{{ $key->key_prefix }}…</code></div>
                    <div class="col-md-3"><strong>Requests this month:</strong> {{ number_format($key->requests_this_month) }}</div>
                    <div class="col-md-3"><strong>Last reset:</strong> {{ $key->last_reset_at?->toDateString() ?: 'Never' }}</div>
                    <div class="col-md-3"><strong>Created:</strong> {{ $key->created_at?->toDateString() }}</div>
                </div>
            @endif
        </x-core::card.body>
    </x-core::card>
@endsection
