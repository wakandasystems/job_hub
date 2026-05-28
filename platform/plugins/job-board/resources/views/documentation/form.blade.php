@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>{{ isset($documentation) ? 'Edit: ' . $documentation->title : 'Create Documentation Entry' }}</x-core::card.title>
            <div class="ms-auto">
                <a href="{{ route('documentation.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-arrow-left"></i> Back
                </a>
            </div>
        </x-core::card.header>
        <x-core::card.body>
            <form method="POST"
                  action="{{ isset($documentation) ? route('documentation.update', $documentation->id) : route('documentation.store') }}">
                @csrf
                @if(isset($documentation))
                    @method('PUT')
                @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $documentation->title ?? '') }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <input type="text" name="category" list="category-list"
                               class="form-control @error('category') is-invalid @enderror"
                               value="{{ old('category', $documentation->category ?? 'General') }}" required>
                        <datalist id="category-list">
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">
                            @endforeach
                            <option value="General">
                            <option value="Features">
                            <option value="Jobs">
                            <option value="Accounts">
                            <option value="Payments">
                            <option value="Admin">
                            <option value="Telegram">
                            <option value="Crawlers">
                        </datalist>
                        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">Sort</label>
                        <input type="number" name="sort_order" class="form-control" min="0"
                               value="{{ old('sort_order', $documentation->sort_order ?? 0) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Content <span class="text-danger">*</span></label>
                        <textarea name="content" id="doc-content"
                                  class="form-control @error('content') is-invalid @enderror"
                                  rows="25" style="font-family: monospace; font-size: 13px;"
                                  placeholder="Write documentation in Markdown format...">{{ old('content', $documentation->content ?? '') }}</textarea>
                        @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Supports Markdown formatting.</small>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="isPublished"
                                   {{ old('is_published', $documentation->is_published ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isPublished">Published</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy"></i>
                        {{ isset($documentation) ? 'Update' : 'Create' }}
                    </button>
                    <a href="{{ route('documentation.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </x-core::card.body>
    </x-core::card>
@endsection
