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
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnEndConversation" data-bs-toggle="modal" data-bs-target="#modal-end-conversation" data-url="{{ route('job-board.auto-cv-bot.end-conversation', $session->id) }}">
                <i class="ti ti-circle-check me-1"></i> End Conversation
            </button>
        @endif
        <button type="button" class="btn btn-sm btn-primary" id="btnGeneratePremiumCv" data-url="{{ route('job-board.auto-cv-bot.generate-documents', $session->id) }}">
            <i class="ti ti-sparkles me-1"></i> Generate 3 CV Designs
        </button>
        @if ($documents !== [])
            <button type="button" class="btn btn-sm btn-success" id="btnSendCvDocuments" data-url="{{ route('job-board.auto-cv-bot.send-documents', $session->id) }}">
                <i class="ti ti-brand-whatsapp me-1"></i> Send to Candidate
            </button>
        @endif
    </div>

    @if ($documents !== [])
        <div class="row g-2">
            @foreach ($documents as $style => $document)
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100">
                        <div class="fw-semibold small mb-2">{{ $document['label'] ?? ucwords(str_replace('_', ' ', $style)) }} Design</div>
                        <div class="d-flex flex-wrap gap-2">
                            @if (! empty($document['docx_path']))
                                <a href="{{ route('job-board.auto-cv-bot.download', [$session->id, 'docx', $style]) }}" class="btn btn-sm btn-outline-dark">
                                    DOCX
                                </a>
                            @endif
                            @if (! empty($document['pdf_path']))
                                <a href="{{ route('job-board.auto-cv-bot.download', [$session->id, 'pdf', $style]) }}" class="btn btn-sm btn-outline-dark">
                                    PDF
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
