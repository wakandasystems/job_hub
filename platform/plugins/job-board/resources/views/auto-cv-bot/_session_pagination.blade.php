@if ($sessions->hasPages() || $sessions->total() > 0)
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3" id="cvBotSessionsPagination" data-current-page="{{ $sessions->currentPage() }}">
        <div class="text-muted small">
            Showing {{ $sessions->firstItem() ?: 0 }}-{{ $sessions->lastItem() ?: 0 }} of {{ $sessions->total() }}
        </div>
        <div class="d-flex gap-2">
            <a href="{{ $sessions->previousPageUrl() ?: '#' }}" class="btn btn-sm btn-outline-secondary {{ $sessions->onFirstPage() ? 'disabled' : '' }}" data-cv-bot-page="{{ max(1, $sessions->currentPage() - 1) }}" @if ($sessions->onFirstPage()) aria-disabled="true" @endif>
                <i class="ti ti-chevron-left me-1"></i> Back
            </a>
            <span class="btn btn-sm btn-light disabled">Page {{ $sessions->currentPage() }} of {{ $sessions->lastPage() }}</span>
            <a href="{{ $sessions->nextPageUrl() ?: '#' }}" class="btn btn-sm btn-outline-secondary {{ $sessions->hasMorePages() ? '' : 'disabled' }}" data-cv-bot-page="{{ min($sessions->lastPage(), $sessions->currentPage() + 1) }}" @if (! $sessions->hasMorePages()) aria-disabled="true" @endif>
                Next <i class="ti ti-chevron-right ms-1"></i>
            </a>
        </div>
    </div>
@endif
