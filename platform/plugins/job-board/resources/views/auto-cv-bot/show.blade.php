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

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bolder">{{ $session->candidate_name ?: $session->whatsapp_number }}</h4>
            <div class="text-muted small">
                <i class="ti ti-brand-whatsapp me-1"></i>{{ $session->whatsapp_number }}
                <span class="badge bg-{{ $statusColors[$session->status] ?? 'secondary' }} text-white ms-2" id="statusBadge">{{ ucfirst($session->status) }}</span>
                <span class="text-success small ms-2 d-none" id="liveIndicator"><i class="ti ti-circle-filled" style="font-size:8px"></i> live</span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnContinueInterview">
                <i class="ti ti-player-play me-1"></i> Continue Interview
            </button>
            <a href="{{ route('job-board.auto-cv-bot.index') }}" class="btn btn-outline-dark btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Back to CV Bot
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
                        <i class="ti ti-send me-1"></i> Resend Last Question
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
                        <i class="ti ti-circle-check me-1"></i> Send &amp; End Conversation
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
                    document.getElementById('cvPreviewBody').innerHTML = data.cv_html;
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
