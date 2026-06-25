@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card class="mb-3">
        <x-core::card.body>
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="text-center js-bot-image-block" data-url="{{ route('job-board.auto-cv-bot.persona-image') }}">
                        <img
                            src="{{ $personaImageUrl ?: RvMedia::getDefaultImage() }}"
                            alt="Nakia — AI assistant"
                            class="rounded-circle border js-bot-image-preview"
                            style="width:72px;height:72px;object-fit:cover;"
                        >
                        <div class="mt-1">
                            <button type="button" class="btn btn-link btn-sm p-0 js-change-bot-image">Change image</button>
                            <input type="file" class="d-none js-bot-image-input" accept="image/*">
                        </div>
                        <div class="text-muted" style="font-size:11px;max-width:90px;">Opening message</div>
                    </div>
                    <div class="text-center js-bot-image-block" data-url="{{ route('job-board.auto-cv-bot.confirmation-image') }}">
                        <img
                            src="{{ $confirmationImageUrl ?: RvMedia::getDefaultImage() }}"
                            alt="Confirmation message image"
                            class="rounded-circle border js-bot-image-preview"
                            style="width:72px;height:72px;object-fit:cover;"
                        >
                        <div class="mt-1">
                            <button type="button" class="btn btn-link btn-sm p-0 js-change-bot-image">Change image</button>
                            <input type="file" class="d-none js-bot-image-input" accept="image/*">
                        </div>
                        <div class="text-muted" style="font-size:11px;max-width:90px;">"Reply DONE" message</div>
                    </div>
                    <div>
                        <h5 class="mb-1">Automated WhatsApp CV Bot</h5>
                        <p class="text-muted small mb-0">
                            Enter a candidate's WhatsApp number and the bot takes over: it asks the interview
                            questions, understands the replies with AI, and tells you on WhatsApp when the CV is
                            ready to check &amp; verify — no manual question-by-question work needed.
                        </p>
                        <p class="text-muted small mb-0">
                            The first image is sent with Nakia's opening WhatsApp message; the second is sent with
                            the final "reply DONE to confirm" message, once a candidate's interview is complete.
                        </p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#sendSampleCvModal">
                        <i class="ti ti-file-text me-1"></i> Send Sample CV
                    </button>
                    <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#startCvBotModal">
                        <i class="ti ti-robot me-1"></i> Start New CV Bot
                    </button>
                </div>
            </div>

            <div class="alert alert-light border mt-3 mb-0 small">
                <strong>One-time setup:</strong> in your Whapi.Cloud dashboard, go to Settings &rarr; Webhook and
                paste this URL so candidate replies reach this bot:
                <code id="webhookUrlText">{{ $webhookUrl }}</code>
                <button type="button" class="btn btn-link btn-sm p-0 ms-1" id="btnCopyWebhookUrl">copy</button>
            </div>

            <div class="d-flex align-items-center gap-2 mt-3" id="jsAiModelBlock" data-url="{{ route('job-board.auto-cv-bot.ai-model') }}">
                <label for="jsAiModelSelect" class="small text-muted mb-0">AI model used for the interview:</label>
                <select id="jsAiModelSelect" class="form-select form-select-sm" style="width:auto;">
                    @foreach ($aiModelOptions as $option)
                        <option value="{{ $option }}" @selected($option === $aiModel)>{{ $option }}</option>
                    @endforeach
                </select>
                <span class="small text-success d-none" id="jsAiModelSaved"><i class="ti ti-check"></i> Saved</span>
            </div>
        </x-core::card.body>
    </x-core::card>

    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>CV Bot Sessions</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body>
            <div id="cvBotSessionsStatsWrap">
                @include('plugins/job-board::auto-cv-bot._session_stats', ['stats' => $stats])
            </div>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>WhatsApp</th>
                            <th>Status</th>
                            <th>Topics covered</th>
                            <th>Started</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cvBotSessionsBody">
                        @include('plugins/job-board::auto-cv-bot._session_rows', ['sessions' => $sessions])
                    </tbody>
                </table>
            </div>

            <div id="cvBotSessionsPaginationWrap">
                @include('plugins/job-board::auto-cv-bot._session_pagination', ['sessions' => $sessions])
            </div>
        </x-core::card.body>
    </x-core::card>

    <div class="modal fade" id="deleteCvBotSessionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-trash text-danger fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">Delete this CV bot session?</h6>
                    <p class="text-muted small mb-4" id="deleteCvBotSessionLabel">This cannot be undone.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger px-4" id="btnConfirmDeleteCvBotSession">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cvBotSessionActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" id="cvBotSessionActionIconWrap" style="width:52px;height:52px;">
                            <i class="ti ti-player-play text-success fs-3" id="cvBotSessionActionIcon"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1" id="cvBotSessionActionTitle">Resume this session?</h6>
                    <p class="text-muted small mb-4" id="cvBotSessionActionLabel">The bot will continue from the current question.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success px-4" id="btnConfirmCvBotSessionAction">Approve</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="sendSampleCvModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Sample CV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Sends a WhatsApp message with our 3 CV designs, prefilled with AI-generated example
                        content for an Accounts/Finance Manager role — handy for convincing a prospect our CVs
                        look good before they sign up.
                    </p>
                    <div id="sendSampleCvError" class="alert alert-danger d-none py-2 px-3 small"></div>
                    <div id="sendSampleCvStatus" class="alert alert-info d-none py-2 px-3 small mb-3"></div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp Number</label>
                        <input type="text" id="sendSampleCvWhatsapp" class="form-control" placeholder="e.g. +260970766123">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark" id="btnSendSampleCv">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSendSampleCvSpinner" role="status" aria-hidden="true"></span>
                        <i class="ti ti-brand-whatsapp me-1" id="btnSendSampleCvIcon"></i>
                        <span id="btnSendSampleCvLabel">Send Sample</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="startCvBotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Start New CV Bot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="startCvBotError" class="alert alert-danger d-none py-2 px-3 small"></div>
                    <div class="mb-3">
                        <label class="form-label">Candidate Name</label>
                        <input type="text" id="startCvBotName" class="form-control" placeholder="Optional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp Number</label>
                        <input type="text" id="startCvBotWhatsapp" class="form-control" placeholder="e.g. +260970766123">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Referred By Agent</label>
                        <input type="text" id="startCvBotSalesAgentSearch" class="form-control" placeholder="Optional — search by name, code, or phone" autocomplete="off">
                        <input type="hidden" id="startCvBotSalesAgentCode">
                        <div class="form-text">Leave blank to auto-detect from a previous referral on this number.</div>
                        <div id="startCvBotSalesAgentSelected" class="small text-success mt-2 d-none"></div>
                        <div id="startCvBotSalesAgentResults" class="list-group mt-2 d-none"></div>
                        <div id="startCvBotSalesAgentPagination" class="d-flex justify-content-between align-items-center mt-2 d-none">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="startCvBotSalesAgentPrev">Previous</button>
                            <div id="startCvBotSalesAgentPage" class="small text-muted"></div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="startCvBotSalesAgentNext">Next</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark" id="btnStartCvBot">
                        <i class="ti ti-brand-whatsapp me-1"></i> Start &amp; Send First Question
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
        <script>
            document.getElementById('btnCopyWebhookUrl')?.addEventListener('click', function () {
                navigator.clipboard.writeText(document.getElementById('webhookUrlText').textContent.trim());
                Botble.showSuccess('Webhook URL copied.');
            });

            document.addEventListener('click', function (event) {
                var button = event.target.closest('.js-change-bot-image');

                if (!button) {
                    return;
                }

                button.closest('.js-bot-image-block').querySelector('.js-bot-image-input').click();
            });

            document.addEventListener('change', function (event) {
                var input = event.target.closest('.js-bot-image-input');

                if (!input) {
                    return;
                }

                var file = input.files[0];

                if (!file) {
                    return;
                }

                var block = input.closest('.js-bot-image-block');
                var formData = new FormData();
                formData.append('image', file);

                fetch(block.dataset.url, {
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
                        if (!result.ok) {
                            Botble.showError(result.data.error || 'Failed to update image.');
                            return;
                        }

                        block.querySelector('.js-bot-image-preview').src = result.data.url + '?t=' + Date.now();
                        Botble.showSuccess(result.data.message || 'Image updated.');
                    })
                    .catch(function () {
                        Botble.showError('Network error — please try again.');
                    })
                    .finally(function () {
                        input.value = '';
                    });
            });

            document.getElementById('jsAiModelSelect')?.addEventListener('change', function (event) {
                var select = event.target;
                var block = document.getElementById('jsAiModelBlock');
                var saved = document.getElementById('jsAiModelSaved');

                fetch(block.dataset.url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ model: select.value }),
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok) {
                            Botble.showError(result.data.error || 'Failed to update AI model.');
                            return;
                        }

                        saved.classList.remove('d-none');
                        setTimeout(function () { saved.classList.add('d-none'); }, 2000);
                    })
                    .catch(function () {
                        Botble.showError('Network error — please try again.');
                    });
            });

            (function () {
                var pollUrl = '{{ route('job-board.auto-cv-bot.sessions.poll') }}';
                var body = document.getElementById('cvBotSessionsBody');
                var statsWrap = document.getElementById('cvBotSessionsStatsWrap');
                var paginationWrap = document.getElementById('cvBotSessionsPaginationWrap');
                var pollTimer = null;
                var currentPage = {{ $sessions->currentPage() }};
                var deleteUrl = null;
                var deleteModal = document.getElementById('deleteCvBotSessionModal');
                var actionUrl = null;
                var actionSuccessMessage = 'Session updated.';
                var actionModal = document.getElementById('cvBotSessionActionModal');

                function refreshSessions(page, pushState) {
                    currentPage = page || currentPage || 1;

                    fetch(pollUrl + '?page=' + encodeURIComponent(currentPage), {
                        headers: { 'Accept': 'application/json' },
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            statsWrap.innerHTML = data.stats_html;
                            body.innerHTML = data.rows_html;
                            paginationWrap.innerHTML = data.pagination_html;

                            var pagination = document.getElementById('cvBotSessionsPagination');
                            if (pagination) {
                                currentPage = parseInt(pagination.dataset.currentPage, 10) || currentPage;
                            }

                            if (pushState) {
                                var url = new URL(window.location.href);
                                url.searchParams.set('page', currentPage);
                                window.history.pushState({ cvBotPage: currentPage }, '', url.toString());
                            }
                        })
                        .catch(function () { /* keep the current table and try again on the next tick */ });
                }

                function postSessionAction(url, successMessage) {
                    fetch(url, {
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
                            if (!result.ok) {
                                Botble.showError(result.data.error || 'Action failed.');
                                return;
                            }

                            Botble.showSuccess(result.data.message || successMessage);
                            refreshSessions(currentPage, false);
                        })
                        .catch(function () {
                            Botble.showError('Network error — please try again.');
                        });
                }

                paginationWrap.addEventListener('click', function (event) {
                    var link = event.target.closest('[data-cv-bot-page]');

                    if (!link || link.classList.contains('disabled')) {
                        return;
                    }

                    event.preventDefault();
                    refreshSessions(parseInt(link.dataset.cvBotPage, 10) || 1, true);
                });

                body.addEventListener('click', function (event) {
                    var actionButton = event.target.closest('.js-cv-bot-action');

                    if (actionButton) {
                        actionUrl = actionButton.dataset.url;
                        var action = actionButton.dataset.action;
                        var label = actionButton.dataset.label || 'this session';
                        var isResume = action === 'resume';

                        actionSuccessMessage = isResume ? 'Session resumed.' : 'Session paused.';

                        document.getElementById('cvBotSessionActionTitle').textContent = isResume ? 'Resume this session?' : 'Pause this session?';
                        document.getElementById('cvBotSessionActionLabel').textContent = isResume
                            ? label + ' will continue from the current question.'
                            : label + ' will stop reminders and processing until resumed.';
                        document.getElementById('cvBotSessionActionIconWrap').className = 'd-inline-flex align-items-center justify-content-center rounded-circle ' + (isResume ? 'bg-success bg-opacity-10' : 'bg-secondary bg-opacity-10');
                        document.getElementById('cvBotSessionActionIcon').className = 'ti ' + (isResume ? 'ti-player-play text-success' : 'ti-player-pause text-secondary') + ' fs-3';
                        document.getElementById('btnConfirmCvBotSessionAction').className = 'btn ' + (isResume ? 'btn-success' : 'btn-secondary') + ' px-4';

                        bootstrap.Modal.getOrCreateInstance(actionModal).show();

                        return;
                    }

                    var deleteButton = event.target.closest('.js-delete-cv-bot-session');

                    if (deleteButton) {
                        deleteUrl = deleteButton.dataset.url;
                        document.getElementById('deleteCvBotSessionLabel').textContent = deleteButton.dataset.label || 'This cannot be undone.';
                    }
                });

                document.getElementById('btnConfirmCvBotSessionAction')?.addEventListener('click', function () {
                    if (!actionUrl) {
                        return;
                    }

                    var button = this;
                    button.disabled = true;

                    fetch(actionUrl, {
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
                            button.disabled = false;

                            if (!result.ok) {
                                Botble.showError(result.data.error || 'Action failed.');
                                return;
                            }

                            bootstrap.Modal.getOrCreateInstance(actionModal).hide();
                            actionUrl = null;
                            Botble.showSuccess(result.data.message || actionSuccessMessage);
                            refreshSessions(currentPage, false);
                        })
                        .catch(function () {
                            button.disabled = false;
                            Botble.showError('Network error — please try again.');
                        });
                });

                document.getElementById('btnConfirmDeleteCvBotSession')?.addEventListener('click', function () {
                    if (!deleteUrl) {
                        return;
                    }

                    var button = this;
                    button.disabled = true;

                    fetch(deleteUrl, {
                        method: 'DELETE',
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
                            button.disabled = false;

                            if (!result.ok) {
                                Botble.showError(result.data.error || 'Delete failed.');
                                return;
                            }

                            bootstrap.Modal.getOrCreateInstance(deleteModal).hide();
                            deleteUrl = null;
                            Botble.showSuccess(result.data.message || 'Session deleted.');
                            refreshSessions(currentPage, false);
                        })
                        .catch(function () {
                            button.disabled = false;
                            Botble.showError('Network error — please try again.');
                        });
                });

                window.addEventListener('popstate', function () {
                    var url = new URL(window.location.href);
                    refreshSessions(parseInt(url.searchParams.get('page'), 10) || 1, false);
                });

                pollTimer = setInterval(function () {
                    refreshSessions(currentPage, false);
                }, 5000);
            })();

            document.getElementById('btnSendSampleCv')?.addEventListener('click', function () {
                var $error = $('#sendSampleCvError').addClass('d-none').text('');
                var $status = $('#sendSampleCvStatus').removeClass('d-none alert-success alert-danger').addClass('alert-info').text('Preparing sample CV files and sending them on WhatsApp. This can take a few seconds...');
                var $btn = $(this).prop('disabled', true);
                $('#btnSendSampleCvSpinner').removeClass('d-none');
                $('#btnSendSampleCvIcon').addClass('d-none');
                $('#btnSendSampleCvLabel').text('Sending...');

                fetch('{{ route('job-board.auto-cv-bot.send-sample') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        whatsapp_number: document.getElementById('sendSampleCvWhatsapp').value,
                    }),
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        $btn.prop('disabled', false);
                        $('#btnSendSampleCvSpinner').addClass('d-none');
                        $('#btnSendSampleCvIcon').removeClass('d-none');
                        $('#btnSendSampleCvLabel').text('Send Sample');

                        if (!result.ok) {
                            $status.removeClass('alert-info alert-success').addClass('alert-danger').text('Sample CV send failed.');
                            $error.removeClass('d-none').text(result.data.error || 'Failed to send sample CV.');
                            return;
                        }

                        $status.removeClass('alert-info alert-danger').addClass('alert-success').text('Sample CV sent successfully.');
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('sendSampleCvModal')).hide();
                        document.getElementById('sendSampleCvWhatsapp').value = '';
                        Botble.showSuccess(result.data.message || 'Sample CV sent.');
                    })
                    .catch(function () {
                        $btn.prop('disabled', false);
                        $('#btnSendSampleCvSpinner').addClass('d-none');
                        $('#btnSendSampleCvIcon').removeClass('d-none');
                        $('#btnSendSampleCvLabel').text('Send Sample');
                        $status.removeClass('alert-info alert-success').addClass('alert-danger').text('Network error while sending sample CV.');
                        $error.removeClass('d-none').text('Network error — please try again.');
                    });
            });

            (function () {
                var searchInput = document.getElementById('startCvBotSalesAgentSearch');
                var codeInput = document.getElementById('startCvBotSalesAgentCode');
                var results = document.getElementById('startCvBotSalesAgentResults');
                var selected = document.getElementById('startCvBotSalesAgentSelected');
                var pagination = document.getElementById('startCvBotSalesAgentPagination');
                var pageLabel = document.getElementById('startCvBotSalesAgentPage');
                var prevButton = document.getElementById('startCvBotSalesAgentPrev');
                var nextButton = document.getElementById('startCvBotSalesAgentNext');
                var modal = document.getElementById('startCvBotModal');
                var debounceTimer = null;
                var currentQuery = '';
                var currentPage = 1;
                var lastPage = 1;

                if (!searchInput || !codeInput || !results || !selected || !pagination || !pageLabel || !prevButton || !nextButton || !modal) {
                    return;
                }

                function escapeHtml(value) {
                    return String(value || '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function renderSelected(agent) {
                    if (!agent) {
                        selected.classList.add('d-none');
                        selected.textContent = '';
                        return;
                    }

                    selected.textContent = 'Selected: ' + agent.name + ' (' + agent.code + ')';
                    selected.classList.remove('d-none');
                }

                function renderResults(items, meta) {
                    results.innerHTML = '';

                    if (!items.length) {
                        results.innerHTML = '<div class="list-group-item small text-muted">No agents found.</div>';
                        results.classList.remove('d-none');
                    } else {
                        items.forEach(function (agent) {
                            var button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'list-group-item list-group-item-action';
                            button.innerHTML =
                                '<div class="fw-semibold">' + escapeHtml(agent.name) + '</div>' +
                                '<div class="small text-muted">' + escapeHtml(agent.code) + ' · ' + escapeHtml(agent.phone || 'No phone') + '</div>';
                            button.addEventListener('click', function () {
                                codeInput.value = agent.code || '';
                                searchInput.value = agent.name + ' (' + agent.code + ')';
                                results.classList.add('d-none');
                                pagination.classList.add('d-none');
                                renderSelected(agent);
                            });
                            results.appendChild(button);
                        });

                        results.classList.remove('d-none');
                    }

                    currentPage = meta.current_page || 1;
                    lastPage = meta.last_page || 1;
                    pageLabel.textContent = 'Page ' + currentPage + ' of ' + lastPage;
                    prevButton.disabled = currentPage <= 1;
                    nextButton.disabled = currentPage >= lastPage;
                    pagination.classList.toggle('d-none', !items.length || lastPage <= 1);
                }

                function searchAgents(page) {
                    var query = searchInput.value.trim();

                    currentQuery = query;
                    currentPage = page || 1;

                    if (query === '') {
                        results.classList.add('d-none');
                        pagination.classList.add('d-none');
                        results.innerHTML = '';
                        pageLabel.textContent = '';
                        return;
                    }

                    fetch('{{ route('job-board.auto-cv-bot.search-agents') }}?q=' + encodeURIComponent(query) + '&page=' + currentPage, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                return { ok: response.ok, data: data };
                            });
                        })
                        .then(function (result) {
                            if (!result.ok) {
                                results.classList.add('d-none');
                                pagination.classList.add('d-none');
                                return;
                            }

                            if (searchInput.value.trim() !== currentQuery) {
                                return;
                            }

                            renderResults(result.data.data || [], result.data.meta || {});
                        })
                        .catch(function () {
                            results.classList.add('d-none');
                            pagination.classList.add('d-none');
                        });
                }

                searchInput.addEventListener('input', function () {
                    codeInput.value = '';
                    renderSelected(null);
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () {
                        searchAgents(1);
                    }, 250);
                });

                searchInput.addEventListener('focus', function () {
                    if (searchInput.value.trim() !== '') {
                        searchAgents(1);
                    }
                });

                prevButton.addEventListener('click', function () {
                    if (currentPage > 1) {
                        searchAgents(currentPage - 1);
                    }
                });

                nextButton.addEventListener('click', function () {
                    if (currentPage < lastPage) {
                        searchAgents(currentPage + 1);
                    }
                });

                modal.addEventListener('hidden.bs.modal', function () {
                    results.classList.add('d-none');
                    pagination.classList.add('d-none');
                    results.innerHTML = '';
                    pageLabel.textContent = '';
                });
            })();

            document.getElementById('btnStartCvBot')?.addEventListener('click', function () {
                var $error = $('#startCvBotError').addClass('d-none').text('');
                var $btn = $(this).prop('disabled', true);

                fetch('{{ route('job-board.auto-cv-bot.start') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        candidate_name: document.getElementById('startCvBotName').value,
                        whatsapp_number: document.getElementById('startCvBotWhatsapp').value,
                        sales_agent_code: document.getElementById('startCvBotSalesAgentCode').value,
                    }),
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        $btn.prop('disabled', false);

                        if (!result.ok) {
                            $error.removeClass('d-none').text(result.data.error || 'Failed to start CV bot.');
                            return;
                        }

                        window.location.href = result.data.redirect;
                    })
                    .catch(function () {
                        $btn.prop('disabled', false);
                        $error.removeClass('d-none').text('Network error — please try again.');
                    });
            });
        </script>
    @endpush
@endsection
