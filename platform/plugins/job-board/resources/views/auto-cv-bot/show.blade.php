@extends(BaseHelper::getAdminMasterLayoutTemplate())

@php
    $statusColors = [
        'collecting' => 'info',
        'ready' => 'warning',
        'completed' => 'success',
        'failed' => 'danger',
        'stalled' => 'secondary',
        'paused' => 'secondary',
    ];
@endphp

@push('header')
    <style>
        .cv-editable {
            display: inline-block;
            min-width: 14px;
            border-radius: 3px;
            padding: 0 2px;
            outline: none;
        }
        .cv-editable:hover {
            background: rgba(13, 110, 253, .08);
        }
        .cv-editable:focus {
            background: rgba(13, 110, 253, .12);
            box-shadow: 0 0 0 1px rgba(13, 110, 253, .4);
        }
        .cv-editable:empty::before {
            content: attr(data-empty-text);
            color: #d63939;
        }
        .cv-editable.is-saved {
            background: rgba(45, 194, 117, .25);
        }
        .cv-section {
            border: 1px solid var(--tblr-border-color, rgba(0, 0, 0, .1));
            border-radius: var(--tblr-border-radius, .375rem);
            padding: .75rem .9rem;
            margin-bottom: .75rem;
        }
        .cv-section:last-child {
            margin-bottom: 0;
        }
        .candidate-hero {
            display: grid;
            grid-template-columns: minmax(260px, 1.4fr) minmax(240px, 1fr) minmax(150px, auto);
            gap: 1rem 1.25rem;
            align-items: center;
            width: 100%;
        }
        .candidate-hero__profile {
            display: flex;
            gap: 1rem;
            align-items: center;
            min-width: 0;
        }
        .candidate-hero__avatar {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            object-fit: cover;
            background: linear-gradient(135deg, #e9ecef, #f8f9fa);
            border: 1px solid var(--tblr-border-color, rgba(0, 0, 0, .1));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            flex: 0 0 72px;
            overflow: hidden;
        }
        .candidate-hero__contact {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            align-content: center;
            min-width: 0;
        }
        .candidate-hero__score {
            min-width: 150px;
            text-align: right;
            justify-self: end;
        }
        .candidate-hero__score-value {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1;
        }
        .candidate-hero__score-label {
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        @media (max-width: 991.98px) {
            .candidate-hero {
                grid-template-columns: 1fr;
            }
            .candidate-hero__score {
                justify-self: start;
                text-align: left;
            }
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bolder">CV Bot Session</h4>
            <div class="text-muted small">
                <x-core::icon name="ti ti-brand-whatsapp" class="me-1" />{{ $session->whatsapp_number }}
                <span class="badge bg-{{ $statusColors[$session->status] ?? 'secondary' }} text-white ms-2" id="statusBadge">{{ ucfirst($session->status) }}</span>
                <span class="text-success small ms-2 d-none" id="liveIndicator"><x-core::icon name="ti ti-circle-filled" style="font-size:8px" /> live</span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnContinueInterview">
                <x-core::icon name="ti ti-player-play" class="me-1" /> Continue Interview
            </button>
            @if (!in_array($session->status, ['paused', 'completed'], true))
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPauseSession">
                    <x-core::icon name="ti ti-player-pause" class="me-1" /> Pause
                </button>
            @endif
            <a href="{{ route('job-board.auto-cv-bot.index') }}" class="btn btn-outline-dark btn-sm">
                <x-core::icon name="ti ti-arrow-left" class="me-1" /> Back to CV Bot
            </a>
        </div>
    </div>

    <x-core::card class="mb-3">
        <x-core::card.header>
            <x-core::card.title>Possible Job Positions</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body id="jobPositionsBody">
            @include('plugins/job-board::auto-cv-bot._job_positions', ['session' => $session])
        </x-core::card.body>
    </x-core::card>

    <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3" id="aiUsageBody">
        @include('plugins/job-board::auto-cv-bot._ai_usage', ['session' => $session])
    </div>

    <div id="statusBanner" class="mb-3">
        @include('plugins/job-board::auto-cv-bot._banner', ['session' => $session])
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <x-core::card class="mb-3">
                <x-core::card.header>
                    <x-core::card.title>WhatsApp Transcript</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body id="transcriptBody" style="max-height:380px;overflow:auto">
                    @include('plugins/job-board::auto-cv-bot._transcript', ['session' => $session])
                </x-core::card.body>
                <x-core::card.footer id="resendFooter" class="{{ $session->status === 'collecting' ? '' : 'd-none' }}">
                    <button type="button" class="btn btn-outline-dark btn-sm" id="btnResendQuestion">
                        <x-core::icon name="ti ti-send" class="me-1" /> Resend Last Question
                    </button>
                    <span id="resendError" class="text-danger small ms-2"></span>
                </x-core::card.footer>
            </x-core::card>

            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Sections to Improve</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body id="improveBody">
                    @include('plugins/job-board::auto-cv-bot._improve', ['session' => $session])
                </x-core::card.body>
            </x-core::card>
        </div>

        <div class="col-lg-7">
            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>CV Preview</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body id="cvPreviewBody">
                    @include('plugins/job-board::auto-cv-bot._cv_preview', ['session' => $session])
                </x-core::card.body>
                <x-core::card.footer id="downloadsFooter">
                    @include('plugins/job-board::auto-cv-bot._downloads', ['session' => $session])
                </x-core::card.footer>
            </x-core::card>
        </div>
    </div>

    <div class="modal fade" id="modal-cv-preview" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down" style="max-width:900px">
            <div class="modal-content" style="height:90vh">
                <div class="modal-header">
                    <h5 class="modal-title" id="cvPreviewModalLabel">CV Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0 bg-light position-relative" style="overflow-y:auto" id="cvPreviewViewport">
                    <div class="d-flex justify-content-center align-items-center h-100" id="cvPreviewLoading">
                        <span class="spinner-border text-primary"></span>
                    </div>
                    <div id="cvPreviewPages" class="d-flex flex-column align-items-center gap-3 p-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-upload-cv" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Candidate's CV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="uploadCvForm">
                    <div class="modal-body">
                        <p class="text-muted small">Use this if you already have the candidate's CV (e.g. they sent it some other way). It will be read and folded straight into the structured CV below — no need to wait on WhatsApp.</p>
                        <label class="form-label fw-semibold small">CV file</label>
                        <input type="file" class="form-control" id="uploadCvFile" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                        <div class="form-text">PDF, Word document, or a clear photo. Max 20 MB.</div>
                        <div class="text-danger small mt-2 d-none" id="uploadCvError"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitUploadCv">
                            <x-core::icon name="ti ti-upload" class="me-1" /> Upload &amp; Process
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-end-conversation" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">End Conversation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">This simulates the candidate replying <strong>DONE</strong> — it closes the WhatsApp interview now and generates their CV from whatever details have been collected so far. Use this if the bot is stuck looping on a question.</p>
                    <label class="form-label fw-semibold small">Closing message to send on WhatsApp</label>
                    <textarea class="form-control" id="endConversationMessage" rows="4">Apologies for the repeated questions earlier — that was a glitch on our end, not something on your side. Thank you for all the details you've shared! I'm putting your CV together now.</textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmEndConversation">
                        <x-core::icon name="ti ti-circle-check" class="me-1" /> Send &amp; End Conversation
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
        <script>
            (function () {
                if (typeof pdfjsLib !== 'undefined') {
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                }

                function renderCvPreview(url) {
                    var viewport = document.getElementById('cvPreviewViewport');
                    var pagesContainer = document.getElementById('cvPreviewPages');
                    var loading = document.getElementById('cvPreviewLoading');
                    var firstPageDone = false;

                    pagesContainer.innerHTML = '';
                    loading.classList.remove('d-none');

                    function showError() {
                        loading.classList.add('d-none');
                        pagesContainer.innerHTML = '<div class="text-danger text-center py-5">Could not render the preview. <a href="' + url + '" target="_blank" rel="noopener">Open in a new tab</a> instead.</div>';
                    }

                    if (typeof pdfjsLib === 'undefined') {
                        showError();
                        return;
                    }

                    function renderPage(pdf, pageNumber) {
                        return pdf.getPage(pageNumber).then(function (page) {
                            var baseViewport = page.getViewport({ scale: 1 });
                            var targetWidth = viewport.clientWidth - 32 || 760;
                            var scale = Math.min(targetWidth / baseViewport.width, 1.6);
                            var pageViewport = page.getViewport({ scale: scale });
                            var canvas = document.createElement('canvas');
                            canvas.className = 'shadow-sm bg-white';
                            canvas.width = pageViewport.width;
                            canvas.height = pageViewport.height;
                            pagesContainer.appendChild(canvas);

                            return page.render({ canvasContext: canvas.getContext('2d'), viewport: pageViewport }).promise.then(function () {
                                if (!firstPageDone) {
                                    firstPageDone = true;
                                    loading.classList.add('d-none');
                                }
                            });
                        });
                    }

                    pdfjsLib.getDocument(url).promise.then(function (pdf) {
                        var chain = Promise.resolve();

                        for (var i = 1; i <= pdf.numPages; i++) {
                            (function (pageNumber) {
                                chain = chain.then(function () { return renderPage(pdf, pageNumber); });
                            })(i);
                        }

                        return chain;
                    }).catch(showError);
                }
                var pollUrl = '{{ route('job-board.auto-cv-bot.poll', $session->id) }}';
                var statusColors = {
                    collecting: 'info',
                    ready: 'warning',
                    completed: 'success',
                    failed: 'danger',
                    stalled: 'secondary',
                };
                var liveStatuses = ['collecting', 'ready', 'failed', 'stalled'];
                var pollTimer = null;
                var lastMessageCount = document.querySelectorAll('#transcriptBody [data-message-id]').length;

                function isNearBottom(el) {
                    return el.scrollHeight - el.scrollTop - el.clientHeight < 80;
                }

                function applyUpdate(data) {
                    var $transcript = document.getElementById('transcriptBody');
                    var wasNearBottom = isNearBottom($transcript);
                    var newCount = (data.message_count !== undefined) ? data.message_count : null;

                    document.getElementById('statusBanner').innerHTML = data.banner_html;
                    $transcript.innerHTML = data.transcript_html;

                    var $activeEl = document.activeElement;
                    var isEditingCv = $activeEl && $activeEl.classList && $activeEl.classList.contains('cv-editable');

                    if (!isEditingCv) {
                        document.getElementById('cvPreviewBody').innerHTML = data.cv_html;
                    }

                    document.getElementById('improveBody').innerHTML = data.improve_html;
                    document.getElementById('jobPositionsBody').innerHTML = data.job_positions_html;
                    document.getElementById('downloadsFooter').innerHTML = data.downloads_html;
                    document.getElementById('downloadsFooter').classList.remove('d-none');
                    document.getElementById('aiUsageBody').innerHTML = data.ai_usage_html;
                    document.getElementById('resendFooter').classList.toggle('d-none', data.status !== 'collecting');

                    var $badge = document.getElementById('statusBadge');
                    $badge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                    $badge.className = 'badge bg-' + (statusColors[data.status] || 'secondary') + ' text-white ms-2';

                    if (newCount !== null && newCount > lastMessageCount && wasNearBottom) {
                        $transcript.scrollTop = $transcript.scrollHeight;
                    }
                    lastMessageCount = newCount !== null ? newCount : lastMessageCount;

                    var $live = document.getElementById('liveIndicator');
                    if (data.status === 'completed') {
                        $live.classList.add('d-none');
                        stopPolling();
                    } else {
                        $live.classList.remove('d-none');
                    }
                }

                function poll() {
                    fetch(pollUrl, { headers: { 'Accept': 'application/json' } })
                        .then(function (response) { return response.json(); })
                        .then(applyUpdate)
                        .catch(function () { /* transient network hiccup — try again next tick */ });
                }

                function stopPolling() {
                    if (pollTimer) {
                        clearInterval(pollTimer);
                        pollTimer = null;
                    }
                }

                document.getElementById('modal-cv-preview').addEventListener('hidden.bs.modal', function () {
                    document.getElementById('cvPreviewPages').innerHTML = '';
                });

                var $initialTranscript = document.getElementById('transcriptBody');
                $initialTranscript.scrollTop = $initialTranscript.scrollHeight;

                if (liveStatuses.indexOf('{{ $session->status }}') !== -1) {
                    document.getElementById('liveIndicator').classList.remove('d-none');
                    pollTimer = setInterval(poll, 3000);
                }

                document.addEventListener('click', function (event) {
                    var previewButton = event.target.closest('.js-preview-cv-document');

                    if (previewButton) {
                        document.getElementById('cvPreviewModalLabel').textContent = (previewButton.dataset.label || 'CV') + ' Preview';
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-cv-preview')).show();
                        renderCvPreview(previewButton.dataset.url);

                        return;
                    }

                    var generateButton = event.target.closest('#btnGeneratePremiumCv');

                    if (generateButton) {
                        var generateOriginalHtml = generateButton.innerHTML;
                        generateButton.disabled = true;
                        generateButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generating';

                        fetch(generateButton.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json',
                            },
                        })
                            .then(function (response) {
                                return response.json().then(function (data) {
                                    return { ok: response.ok, data: data };
                                });
                            })
                            .then(function (result) {
                                generateButton.disabled = false;
                                generateButton.innerHTML = generateOriginalHtml;

                                if (!result.ok) {
                                    Botble.showError(result.data.error || 'Failed to generate CV documents.');
                                    return;
                                }

                                document.getElementById('downloadsFooter').innerHTML = result.data.downloads_html;
                                document.getElementById('aiUsageBody').innerHTML = result.data.ai_usage_html;
                                Botble.showSuccess(result.data.message || 'CV documents generated.');
                            })
                            .catch(function () {
                                generateButton.disabled = false;
                                generateButton.innerHTML = generateOriginalHtml;
                                Botble.showError('Network error — please try again.');
                            });

                        return;
                    }

                    var sendDocumentsButton = event.target.closest('#btnSendCvDocuments');

                    if (sendDocumentsButton) {
                        var sendOriginalHtml = sendDocumentsButton.innerHTML;
                        sendDocumentsButton.disabled = true;
                        sendDocumentsButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending';

                        fetch(sendDocumentsButton.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json',
                            },
                        })
                            .then(function (response) {
                                return response.json().then(function (data) {
                                    return { ok: response.ok, data: data };
                                });
                            })
                            .then(function (result) {
                                sendDocumentsButton.disabled = false;
                                sendDocumentsButton.innerHTML = sendOriginalHtml;

                                if (!result.ok) {
                                    Botble.showError(result.data.error || 'Failed to send CV documents.');
                                    return;
                                }

                                Botble.showSuccess(result.data.message || 'CV documents sent.');
                            })
                            .catch(function () {
                                sendDocumentsButton.disabled = false;
                                sendDocumentsButton.innerHTML = sendOriginalHtml;
                                Botble.showError('Network error — please try again.');
                            });

                        return;
                    }

                    var copyErrorButton = event.target.closest('.js-copy-auto-cv-error');

                    if (copyErrorButton) {
                        var target = document.querySelector(copyErrorButton.dataset.target);

                        if (!target) {
                            return;
                        }

                        navigator.clipboard.writeText(target.textContent.trim());
                        Botble.showSuccess('Error copied to clipboard.');

                        return;
                    }

                    var requestPhotoButton = event.target.closest('.js-request-cv-photo');

                    if (requestPhotoButton) {
                        var requestPhotoOriginalHtml = requestPhotoButton.innerHTML;
                        requestPhotoButton.disabled = true;
                        requestPhotoButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending';

                        fetch(requestPhotoButton.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json',
                            },
                        })
                            .then(function (response) {
                                return response.json().then(function (data) {
                                    return { ok: response.ok, data: data };
                                });
                            })
                            .then(function (result) {
                                requestPhotoButton.disabled = false;
                                requestPhotoButton.innerHTML = requestPhotoOriginalHtml;

                                if (!result.ok) {
                                    Botble.showError(result.data.error || 'Failed to send the photo request.');
                                    return;
                                }

                                Botble.showSuccess(result.data.message || 'Photo request sent.');
                                poll();

                                if (!pollTimer) {
                                    document.getElementById('liveIndicator').classList.remove('d-none');
                                    pollTimer = setInterval(poll, 3000);
                                }
                            })
                            .catch(function () {
                                requestPhotoButton.disabled = false;
                                requestPhotoButton.innerHTML = requestPhotoOriginalHtml;
                                Botble.showError('Network error — please try again.');
                            });

                        return;
                    }

                    var requestCvButton = event.target.closest('.js-request-cv-upload');

                    if (requestCvButton) {
                        var requestCvOriginalHtml = requestCvButton.innerHTML;
                        requestCvButton.disabled = true;
                        requestCvButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending';

                        fetch(requestCvButton.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json',
                            },
                        })
                            .then(function (response) {
                                return response.json().then(function (data) {
                                    return { ok: response.ok, data: data };
                                });
                            })
                            .then(function (result) {
                                requestCvButton.disabled = false;
                                requestCvButton.innerHTML = requestCvOriginalHtml;

                                if (!result.ok) {
                                    Botble.showError(result.data.error || 'Failed to send the CV request.');
                                    return;
                                }

                                Botble.showSuccess(result.data.message || 'Asked the candidate again for their CV.');
                                poll();

                                if (!pollTimer) {
                                    document.getElementById('liveIndicator').classList.remove('d-none');
                                    pollTimer = setInterval(poll, 3000);
                                }
                            })
                            .catch(function () {
                                requestCvButton.disabled = false;
                                requestCvButton.innerHTML = requestCvOriginalHtml;
                                Botble.showError('Network error — please try again.');
                            });

                        return;
                    }

                    var requestConfirmationButton = event.target.closest('.js-request-final-confirmation');

                    if (requestConfirmationButton) {
                        var requestConfirmationOriginalHtml = requestConfirmationButton.innerHTML;
                        requestConfirmationButton.disabled = true;
                        requestConfirmationButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending';

                        fetch(requestConfirmationButton.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json',
                            },
                        })
                            .then(function (response) {
                                return response.json().then(function (data) {
                                    return { ok: response.ok, data: data };
                                });
                            })
                            .then(function (result) {
                                requestConfirmationButton.disabled = false;
                                requestConfirmationButton.innerHTML = requestConfirmationOriginalHtml;

                                if (!result.ok) {
                                    Botble.showError(result.data.error || 'Failed to send the confirmation check-in.');
                                    return;
                                }

                                Botble.showSuccess(result.data.message || 'Confirmation check-in sent.');
                                poll();

                                if (!pollTimer) {
                                    document.getElementById('liveIndicator').classList.remove('d-none');
                                    pollTimer = setInterval(poll, 3000);
                                }
                            })
                            .catch(function () {
                                requestConfirmationButton.disabled = false;
                                requestConfirmationButton.innerHTML = requestConfirmationOriginalHtml;
                                Botble.showError('Network error — please try again.');
                            });

                        return;
                    }

                    var askResendButton = event.target.closest('.js-ask-candidate-resend');

                    if (askResendButton) {
                        var askOriginalHtml = askResendButton.innerHTML;
                        askResendButton.disabled = true;
                        askResendButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending';

                        fetch(askResendButton.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json',
                            },
                        })
                            .then(function (response) {
                                return response.json().then(function (data) {
                                    return { ok: response.ok, data: data };
                                });
                            })
                            .then(function (result) {
                                askResendButton.disabled = false;
                                askResendButton.innerHTML = askOriginalHtml;

                                if (!result.ok) {
                                    Botble.showError(result.data.error || 'Failed to ask candidate to resend.');
                                    return;
                                }

                                Botble.showSuccess(result.data.message || 'Asked candidate to resend.');
                                poll();
                            })
                            .catch(function () {
                                askResendButton.disabled = false;
                                askResendButton.innerHTML = askOriginalHtml;
                                Botble.showError('Network error — please try again.');
                            });

                        return;
                    }

                    var retryGenerationButton = event.target.closest('.js-retry-auto-cv-generation');

                    if (retryGenerationButton) {
                        var retryOriginalHtml = retryGenerationButton.innerHTML;
                        retryGenerationButton.disabled = true;
                        retryGenerationButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Retrying';

                        fetch(retryGenerationButton.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json',
                            },
                        })
                            .then(function (response) {
                                return response.json().then(function (data) {
                                    return { ok: response.ok, data: data };
                                });
                            })
                            .then(function (result) {
                                retryGenerationButton.disabled = false;
                                retryGenerationButton.innerHTML = retryOriginalHtml;

                                if (!result.ok) {
                                    Botble.showError(result.data.error || 'Failed to retry CV generation.');
                                    return;
                                }

                                Botble.showSuccess(result.data.message || 'CV regenerated.');
                                poll();
                            })
                            .catch(function () {
                                retryGenerationButton.disabled = false;
                                retryGenerationButton.innerHTML = retryOriginalHtml;
                                Botble.showError('Network error — please try again.');
                            });

                        return;
                    }

                    var clearSectionButton = event.target.closest('.js-clear-cv-section');

                    if (clearSectionButton) {
                        var clearOriginalHtml = clearSectionButton.innerHTML;
                        clearSectionButton.disabled = true;
                        clearSectionButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                        fetch(clearSectionButton.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ section: clearSectionButton.dataset.section }),
                        })
                            .then(function (response) {
                                return response.json().then(function (data) {
                                    return { ok: response.ok, data: data };
                                });
                            })
                            .then(function (result) {
                                if (!result.ok) {
                                    clearSectionButton.disabled = false;
                                    clearSectionButton.innerHTML = clearOriginalHtml;
                                    Botble.showError(result.data.error || 'Failed to clear section.');
                                    return;
                                }

                                document.getElementById('cvPreviewBody').innerHTML = result.data.cv_html;
                                if (result.data.job_positions_html) {
                                    document.getElementById('jobPositionsBody').innerHTML = result.data.job_positions_html;
                                }
                                if (result.data.improve_html) {
                                    document.getElementById('improveBody').innerHTML = result.data.improve_html;
                                }
                                Botble.showSuccess(result.data.message || 'Section cleared.');
                            })
                            .catch(function () {
                                clearSectionButton.disabled = false;
                                clearSectionButton.innerHTML = clearOriginalHtml;
                                Botble.showError('Network error — please try again.');
                            });

                        return;
                    }

                    var button = event.target.closest('.js-request-section-info');

                    if (!button) {
                        return;
                    }

                    var originalHtml = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending';

                    fetch(button.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            topic_number: parseInt(button.dataset.topicNumber, 10),
                        }),
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                return { ok: response.ok, data: data };
                            });
                        })
                        .then(function (result) {
                            button.disabled = false;
                            button.innerHTML = originalHtml;

                            if (!result.ok) {
                                Botble.showError(result.data.error || 'Failed to send section request.');
                                return;
                            }

                            Botble.showSuccess(result.data.message || 'Section request sent.');
                            poll();

                            if (!pollTimer) {
                                document.getElementById('liveIndicator').classList.remove('d-none');
                                pollTimer = setInterval(poll, 3000);
                            }
                        })
                        .catch(function () {
                            button.disabled = false;
                            button.innerHTML = originalHtml;
                            Botble.showError('Network error — please try again.');
                        });
                });
            })();

            document.getElementById('btnResendQuestion')?.addEventListener('click', function () {
                var $btn = $(this).prop('disabled', true);
                var $error = $('#resendError').text('');

                fetch('{{ route('job-board.auto-cv-bot.resend-question', $session->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        $btn.prop('disabled', false);

                        if (!result.ok) {
                            $error.text(result.data.error || 'Failed to resend.');
                            return;
                        }

                        Botble.showSuccess(result.data.message || 'Resent.');
                    })
                    .catch(function () {
                        $btn.prop('disabled', false);
                        $error.text('Network error — please try again.');
                    });
            });

            document.getElementById('btnConfirmEndConversation')?.addEventListener('click', function () {
                var $btn = $(this).prop('disabled', true);
                var originalHtml = $btn.html();
                $btn.html('<span class="spinner-border spinner-border-sm me-1"></span>Sending');
                var message = document.getElementById('endConversationMessage').value;

                fetch('{{ route('job-board.auto-cv-bot.end-conversation', $session->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ message: message }),
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        $btn.prop('disabled', false).html(originalHtml);

                        if (!result.ok) {
                            Botble.showError(result.data.error || 'Failed to end the conversation.');
                            return;
                        }

                        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-end-conversation')).hide();
                        Botble.showSuccess(result.data.message || 'Conversation ended.');
                        window.location.reload();
                    })
                    .catch(function () {
                        $btn.prop('disabled', false).html(originalHtml);
                        Botble.showError('Network error — please try again.');
                    });
            });

            document.getElementById('uploadCvForm')?.addEventListener('submit', function (event) {
                event.preventDefault();

                var fileInput = document.getElementById('uploadCvFile');
                var $error = $('#uploadCvError').addClass('d-none').text('');

                if (!fileInput.files.length) {
                    return;
                }

                var $btn = $('#btnSubmitUploadCv').prop('disabled', true);
                var originalHtml = $btn.html();
                $btn.html('<span class="spinner-border spinner-border-sm me-1"></span>Processing');

                var formData = new FormData();
                formData.append('cv_file', fileInput.files[0]);

                fetch('{{ route('job-board.auto-cv-bot.upload-cv', $session->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        $btn.prop('disabled', false).html(originalHtml);

                        if (!result.ok) {
                            $error.removeClass('d-none').text(result.data.error || 'Failed to upload the CV.');
                            return;
                        }

                        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-upload-cv')).hide();
                        document.getElementById('uploadCvForm').reset();
                        Botble.showSuccess(result.data.message || 'CV uploaded and processed.');
                        poll();

                        if (!pollTimer) {
                            document.getElementById('liveIndicator').classList.remove('d-none');
                            pollTimer = setInterval(poll, 3000);
                        }
                    })
                    .catch(function () {
                        $btn.prop('disabled', false).html(originalHtml);
                        $error.removeClass('d-none').text('Network error — please try again.');
                    });
            });

            document.addEventListener('change', function (event) {
                var toggle = event.target.closest('.js-toggle-references-on-request');

                if (!toggle) {
                    return;
                }

                var enabled = toggle.checked;
                toggle.disabled = true;

                fetch(toggle.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ enabled: enabled }),
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        toggle.disabled = false;

                        if (!result.ok) {
                            toggle.checked = !enabled;
                            Botble.showError(result.data.error || 'Failed to update.');
                            return;
                        }

                        Botble.showSuccess(result.data.message || 'Updated.');
                    })
                    .catch(function () {
                        toggle.disabled = false;
                        toggle.checked = !enabled;
                        Botble.showError('Network error — please try again.');
                    });
            });

            var cvFieldUrl = '{{ route('job-board.auto-cv-bot.update-cv-field', $session->id) }}';

            document.addEventListener('focus', function (event) {
                var el = event.target;

                if (el.classList && el.classList.contains('cv-editable')) {
                    el.dataset.originalValue = el.textContent.trim();
                }
            }, true);

            document.addEventListener('blur', function (event) {
                var el = event.target;

                if (!el.classList || !el.classList.contains('cv-editable')) {
                    return;
                }

                var value = el.textContent.trim();

                if (el.dataset.originalValue === value) {
                    return;
                }

                el.dataset.originalValue = value;

                fetch(cvFieldUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ field: el.dataset.field, value: value }),
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok) {
                            Botble.showError(result.data.error || 'Failed to save edit.');
                            return;
                        }

                        if (result.data.cv_html) {
                            document.getElementById('cvPreviewBody').innerHTML = result.data.cv_html;
                        }

                        if (result.data.job_positions_html) {
                            document.getElementById('jobPositionsBody').innerHTML = result.data.job_positions_html;
                        }

                        if (result.data.improve_html) {
                            document.getElementById('improveBody').innerHTML = result.data.improve_html;
                        }

                        el.classList.add('is-saved');
                        setTimeout(function () { el.classList.remove('is-saved'); }, 1000);
                    })
                    .catch(function () {
                        Botble.showError('Network error — could not save edit.');
                    });
            }, true);

            document.getElementById('btnPauseSession')?.addEventListener('click', function () {
                var $btn = $(this).prop('disabled', true);

                fetch('{{ route('job-board.auto-cv-bot.pause', $session->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        $btn.prop('disabled', false);

                        if (!result.ok) {
                            Botble.showError(result.data.error || 'Failed to pause session.');
                            return;
                        }

                        Botble.showSuccess(result.data.message || 'Session paused.');
                        window.location.reload();
                    })
                    .catch(function () {
                        $btn.prop('disabled', false);
                        Botble.showError('Network error — please try again.');
                    });
            });

            document.getElementById('btnContinueInterview')?.addEventListener('click', function () {
                var $btn = $(this).prop('disabled', true);

                fetch('{{ route('job-board.auto-cv-bot.continue-interview', $session->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        $btn.prop('disabled', false);

                        if (!result.ok) {
                            Botble.showError(result.data.error || 'Failed to continue interview.');
                            return;
                        }

                        Botble.showSuccess(result.data.message || 'Interview continued.');
                        window.location.reload();
                    })
                    .catch(function () {
                        $btn.prop('disabled', false);
                        Botble.showError('Network error — please try again.');
                    });
            });
        </script>
    @endpush
@endsection
