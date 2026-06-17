@php
    $buttonLabel = $slot === 'cover_image' ? 'Generate Cover' : 'Generate Thumbnail';
@endphp

<div class="mt-2 d-flex flex-wrap align-items-center gap-2 blog-ai-image-toolbar" data-slot="{{ $slot }}">
    <button
        type="button"
        class="btn btn-sm btn-primary"
        data-bb-blog-ai-generate
        data-slot="{{ $slot }}"
    >
        <i class="ti ti-sparkles me-1"></i>{{ $buttonLabel }}
    </button>
    <span class="text-muted small">Uses the current title, description, and content, then replaces this image field.</span>
</div>
