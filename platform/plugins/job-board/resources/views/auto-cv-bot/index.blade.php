@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card class="mb-3">
        <x-core::card.body>
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Automated WhatsApp CV Bot</h5>
                    <p class="text-muted small mb-0">
                        Enter a candidate's WhatsApp number and the bot takes over: it asks the interview
                        questions, understands the replies with AI, and tells you on WhatsApp when the CV is
                        ready to check &amp; verify — no manual question-by-question work needed.
                    </p>
                </div>
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#startCvBotModal">
                    <i class="ti ti-robot me-1"></i> Start New CV Bot
                </button>
            </div>

            <div class="alert alert-light border mt-3 mb-0 small">
                <strong>One-time setup:</strong> in your Whapi.Cloud dashboard, go to Settings &rarr; Webhook and
                paste this URL so candidate replies reach this bot:
                <code id="webhookUrlText">{{ $webhookUrl }}</code>
                <button type="button" class="btn btn-link btn-sm p-0 ms-1" id="btnCopyWebhookUrl">copy</button>
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
