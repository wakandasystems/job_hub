@php
    $documents = $session->cv_document_paths ?: [];

    if ($documents === [] && ($session->docx_path || $session->pdf_path)) {
        $documents = [
            'premium' => [
                'label' => 'Premium',
                'docx_path' => $session->docx_path,
                'pdf_path' => $session->pdf_path,
            ],
        ];
    }
@endphp

<div class="d-flex flex-column gap-3">
    <div class="d-flex flex-wrap gap-2">
        @if ($session->status !== 'completed')
            <button type="button" class="btn btn-sm btn-outline-info js-request-final-confirmation" data-url="{{ route('job-board.auto-cv-bot.request-final-confirmation', $session->id) }}">
                <x-core::icon name="ti ti-message-check" class="me-1" /> Send Verification
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnEndConversation" data-bs-toggle="modal" data-bs-target="#modal-end-conversation" data-url="{{ route('job-board.auto-cv-bot.end-conversation', $session->id) }}">
                <x-core::icon name="ti ti-circle-check" class="me-1" /> End Conversation
            </button>
        @endif
        <button type="button" class="btn btn-sm btn-primary" id="btnGeneratePremiumCv" data-url="{{ route('job-board.auto-cv-bot.generate-documents', $session->id) }}">
            <x-core::icon name="ti ti-sparkles" class="me-1" /> Generate CV Designs
        </button>
        @if ($documents !== [])
            <button type="button" class="btn btn-sm btn-success" id="btnSendCvDocuments" data-url="{{ route('job-board.auto-cv-bot.send-documents', $session->id) }}">
                <x-core::icon name="ti ti-brand-whatsapp" class="me-1" /> Send to Candidate
            </button>
        @endif
    </div>

    @if ($documents !== [])
        <div class="row g-2">
            @foreach ($documents as $style => $document)
                <div class="col-md-3">
                    <div class="border rounded p-2 h-100">
                        <div class="fw-semibold small mb-2">{{ $document['label'] ?? ucwords(str_replace('_', ' ', $style)) }} Design</div>
                        <div class="d-flex flex-wrap gap-2">
                            @if (! empty($document['pdf_path']))
                                <button type="button" class="btn btn-sm btn-primary js-preview-cv-document" data-url="{{ route('job-board.auto-cv-bot.preview', [$session->id, $style]) }}" data-label="{{ $document['label'] ?? ucwords(str_replace('_', ' ', $style)) }}">
                                    <x-core::icon name="ti ti-eye" class="me-1" /> Preview
                                </button>
                            @endif
                            @if (! empty($document['pdf_path']))
                                <a href="{{ route('job-board.auto-cv-bot.download', [$session->id, 'pdf', $style]) }}" class="btn btn-sm btn-outline-dark" title="Download PDF">
                                    <x-core::icon name="ti ti-file-type-pdf" noMargin />
                                </a>
                            @endif
                            @if (! empty($document['docx_path']))
                                <a href="{{ route('job-board.auto-cv-bot.download', [$session->id, 'docx', $style]) }}" class="btn btn-sm btn-outline-dark" title="Download DOCX">
                                    <x-core::icon name="ti ti-file-type-doc" noMargin />
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
