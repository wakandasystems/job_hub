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
            <div class="card">
                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs card-header-tabs px-3 pt-2" id="cvBotTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-cv-preview" data-bs-toggle="tab" data-bs-target="#pane-cv-preview" type="button" role="tab">
                                <x-core::icon name="ti ti-file-text" class="me-1" /> CV Preview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-bot-logic" data-bs-toggle="tab" data-bs-target="#pane-bot-logic" type="button" role="tab">
                                <x-core::icon name="ti ti-brain" class="me-1" /> Bot Logic
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pane-cv-preview" role="tabpanel">
                        <div class="card-body" id="cvPreviewBody">
                            @include('plugins/job-board::auto-cv-bot._cv_preview', ['session' => $session])
                        </div>
                        <div class="card-footer" id="downloadsFooter">
                            @include('plugins/job-board::auto-cv-bot._downloads', ['session' => $session])
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pane-bot-logic" role="tabpanel">
                        <div class="card-body" id="botLogicBody" style="max-height:680px;overflow-y:auto">
                            @include('plugins/job-board::auto-cv-bot._bot_logic', ['session' => $session])
                        </div>
                    </div>
                </div>
            </div>
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

    {{-- Get More Info modal — step 1: choose method; step 2a: WhatsApp; step 2b: admin entry --}}
    <div class="modal fade" id="modal-get-section-info" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="getSectionInfoTitle">Get More Info</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    {{-- Step 1: choose --}}
                    <div id="gsi-step-choose">
                        <p class="text-muted small mb-3">How would you like to collect this information?</p>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-success text-start px-3 py-3" id="btnGsiPickWhatsapp">
                                <strong>Ask candidate on WhatsApp</strong><br>
                                <span class="small text-muted">Send a question and wait for their reply</span>
                            </button>
                            <button type="button" class="btn btn-outline-primary text-start px-3 py-3" id="btnGsiPickAdmin">
                                <strong>Enter information as admin</strong><br>
                                <span class="small text-muted">Type the answer now and the AI processes it instantly</span>
                            </button>
                        </div>
                    </div>

                    {{-- Step 2a: WhatsApp confirm --}}
                    <div id="gsi-step-whatsapp" class="d-none">
                        <p class="text-muted small mb-2">The following question will be sent to the candidate on WhatsApp:</p>
                        <div id="gsiWhatsappPreview" class="border rounded bg-light p-2 small fst-italic"></div>
                    </div>

                    {{-- Step 2b: Admin enter --}}
                    <div id="gsi-step-admin" class="d-none">
                        <p class="text-muted small mb-2">Paste the candidate's information below. Choose how to process it:</p>
                        <textarea id="gsiAdminReplyText" class="form-control" rows="6"
                            placeholder="e.g. Acting Branch Manager at Investrust Bank Feb 2024 — responsible for branch controls, ATM, vault, and staff KPIs."></textarea>
                        <div id="gsiAdminReplyError" class="text-danger small mt-1 d-none"></div>
                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <div class="border rounded p-2 small text-muted text-center" style="line-height:1.3">
                                    <strong class="d-block text-dark">Update CV silently</strong>
                                    AI extracts data &amp; updates scores.<br>No message sent to candidate.
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 small text-muted text-center" style="line-height:1.3">
                                    <strong class="d-block text-dark">Update &amp; continue chat</strong>
                                    Same as above, then AI sends the next question on WhatsApp.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary d-none" id="btnGsiBack">← Back</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success d-none" id="btnGsiSendWhatsapp">Send on WhatsApp</button>
                    <button type="button" class="btn btn-outline-primary d-none" id="btnGsiSubmitAdminSilent">Update CV silently</button>
                    <button type="button" class="btn btn-primary d-none" id="btnGsiSubmitAdmin">Update &amp; continue chat</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Shared confirmation modal used by Send Verification and Generate CV Designs --}}
    <div class="modal fade" id="modal-action-confirm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionConfirmTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-0" id="actionConfirmBody"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnActionConfirmOk"></button>
                </div>
            </div>
        </div>
    </div>

    {{-- Add CV Item modal --}}
    <div class="modal fade" id="modal-add-cv-item" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCvItemTitle">Add Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- experience fields --}}
                    <div id="aci-experience" class="d-none">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="aciExpTitle" placeholder="e.g. Branch Manager">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Company <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="aciExpCompany" placeholder="e.g. Investrust Bank">
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Start Date</label>
                                <input type="text" class="form-control form-control-sm" id="aciExpStart" placeholder="e.g. January 2020">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">End Date</label>
                                <input type="text" class="form-control form-control-sm" id="aciExpEnd" placeholder="e.g. Present">
                            </div>
                        </div>
                    </div>
                    {{-- projects fields --}}
                    <div id="aci-projects" class="d-none">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="aciProjName" placeholder="e.g. Community Mentorship Programme">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Description</label>
                            <textarea class="form-control form-control-sm" id="aciProjDesc" rows="2" placeholder="Brief description…"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Link (optional)</label>
                            <input type="url" class="form-control form-control-sm" id="aciProjLink" placeholder="https://…">
                        </div>
                    </div>
                    {{-- skills fields --}}
                    <div id="aci-skills" class="d-none">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Skill <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="aciSkillValue" placeholder="e.g. Risk Assessment">
                        </div>
                    </div>
                    {{-- languages fields --}}
                    <div id="aci-languages" class="d-none">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Language <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="aciLangName" placeholder="e.g. French">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Proficiency</label>
                            <select class="form-select form-select-sm" id="aciLangLevel">
                                <option value="Fluent">Fluent</option>
                                <option value="Conversational">Conversational</option>
                                <option value="Basic">Basic</option>
                                <option value="Native">Native</option>
                            </select>
                        </div>
                    </div>
                    <div id="aci-error" class="alert alert-danger py-1 px-2 small d-none mt-2 mb-0"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="btnAddCvItemSubmit">Add</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Photo crop modal --}}
    <div class="modal fade" id="modal-crop-photo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crop Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="background:#1a1a1a;min-height:380px;max-height:60vh;overflow:hidden;position:relative">
                    <img id="cropperImg" src="" alt="" style="max-width:100%;display:block">
                </div>
                <div class="modal-footer d-flex align-items-center gap-2 flex-wrap">
                    <div class="btn-group btn-group-sm me-auto">
                        <button type="button" class="btn btn-outline-secondary" id="btnCropRotateLeft" title="Rotate left">↺ 90°</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnCropRotateRight" title="Rotate right">↻ 90°</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnCropFlipH" title="Flip horizontal">↔ Flip</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnCropReset" title="Reset crop">Reset</button>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" id="btnCropAspectFree" title="Free aspect">Free</button>
                        <button type="button" class="btn btn-outline-secondary active" id="btnCropAspect1x1" title="Square">1:1</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnCropAspect4x5" title="Portrait">4:5</button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnCropSave">Save Crop</button>
                </div>
            </div>
        </div>
    </div>

    @push('footer')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
        <script>
            // Initialize SortableJS on all .cv-sortable-list containers inside cvPreviewBody.
            // Called after every CV preview refresh so newly rendered lists get Sortable attached.
            function initCvSortables() {
                document.querySelectorAll('#cvPreviewBody .cv-sortable-list').forEach(function (el) {
                    if (el._sortable) { el._sortable.destroy(); }
                    el._sortable = Sortable.create(el, {
                        handle: '.cv-drag-handle',
                        animation: 150,
                        ghostClass: 'bg-light',
                        onEnd: function (evt) {
                            if (evt.oldIndex === evt.newIndex) { return; }
                            var section    = el.dataset.section;
                            var reorderUrl = el.dataset.reorderUrl;
                            var items      = el.querySelectorAll('.cv-sortable-item');
                            var order      = Array.from(items).map(function (item) { return parseInt(item.dataset.index); });
                            fetch(reorderUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                },
                                body: JSON.stringify({ section: section, order: order }),
                            })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                if (data.error) { Botble.showError(data.error); return; }
                                document.getElementById('cvPreviewBody').innerHTML         = data.cv_html;
                                document.getElementById('improveBody').innerHTML           = data.improve_html;
                                document.getElementById('jobPositionsBody').innerHTML      = data.job_positions_html;
                                initCvSortables();
                            })
                            .catch(function () { Botble.showError('Could not save new order.'); });
                        },
                    });
                });
            }

            // Shared confirmation helper — shows modal-action-confirm then calls cb() on OK.
            function showActionConfirm(title, body, confirmLabel, confirmClass, cb) {
                var modal   = document.getElementById('modal-action-confirm');
                var titleEl = document.getElementById('actionConfirmTitle');
                var bodyEl  = document.getElementById('actionConfirmBody');
                var okBtn   = document.getElementById('btnActionConfirmOk');

                titleEl.textContent = title;
                bodyEl.innerHTML    = body;
                okBtn.textContent   = confirmLabel;
                okBtn.className     = 'btn ' + (confirmClass || 'btn-primary');

                var bsModal = bootstrap.Modal.getOrCreateInstance(modal);

                var handler = function () {
                    okBtn.removeEventListener('click', handler);
                    bsModal.hide();
                    cb();
                };
                okBtn.removeEventListener('click', handler); // defensive
                okBtn.addEventListener('click', handler);

                // Clean up if dismissed without confirming
                modal.addEventListener('hidden.bs.modal', function cleanup() {
                    modal.removeEventListener('hidden.bs.modal', cleanup);
                    okBtn.removeEventListener('click', handler);
                }, { once: true });

                bsModal.show();
            }

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
                        initCvSortables();
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

                initCvSortables();

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
                        var _genBtn = generateButton;
                        showActionConfirm(
                            'Generate CV Designs',
                            'This will use the AI to generate all CV design variants (ATS, Premium, Academic, Creative, Executive) from the collected details. This cannot be undone and will use AI credits. Continue?',
                            'Generate',
                            'btn-primary',
                            function () {
                                var generateOriginalHtml = _genBtn.innerHTML;
                                _genBtn.disabled = true;
                                _genBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generating';

                        fetch(_genBtn.dataset.url, {
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
                                _genBtn.disabled = false;
                                _genBtn.innerHTML = generateOriginalHtml;

                                if (!result.ok) {
                                    Botble.showError(result.data.error || 'Failed to generate CV documents.');
                                    return;
                                }

                                document.getElementById('downloadsFooter').innerHTML = result.data.downloads_html;
                                document.getElementById('aiUsageBody').innerHTML = result.data.ai_usage_html;
                                Botble.showSuccess(result.data.message || 'CV documents generated.');
                            })
                            .catch(function () {
                                _genBtn.disabled = false;
                                _genBtn.innerHTML = generateOriginalHtml;
                                Botble.showError('Network error — please try again.');
                            });
                            } // end showActionConfirm callback
                        );

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
                        var _rcBtn = requestConfirmationButton;
                        showActionConfirm(
                            'Send Verification',
                            'This will send a final check-in to the candidate on WhatsApp asking them to confirm the CV is complete before it is generated. Continue?',
                            'Send Verification',
                            'btn-info',
                            function () {
                                var requestConfirmationOriginalHtml = _rcBtn.innerHTML;
                                _rcBtn.disabled = true;
                                _rcBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending';

                        fetch(_rcBtn.dataset.url, {
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
                                _rcBtn.disabled = false;
                                _rcBtn.innerHTML = requestConfirmationOriginalHtml;

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
                                _rcBtn.disabled = false;
                                _rcBtn.innerHTML = requestConfirmationOriginalHtml;
                                Botble.showError('Network error — please try again.');
                            });
                            } // end showActionConfirm callback
                        );

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

                    // --- Remove individual responsibility ---
                    var removeItemBtn = event.target.closest('.js-remove-cv-array-item');
                    if (removeItemBtn) {
                        var rmPath  = removeItemBtn.dataset.path;
                        var rmIndex = parseInt(removeItemBtn.dataset.index, 10);
                        var rmUrl   = removeItemBtn.dataset.url;

                        removeItemBtn.disabled = true;
                        removeItemBtn.textContent = '…';

                        fetch(rmUrl, {
                            method : 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept'      : 'application/json',
                            },
                            body: JSON.stringify({ path: rmPath, index: rmIndex }),
                        })
                            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                            .then(function (res) {
                                if (!res.ok) { Botble.showError(res.d.error || 'Could not remove item.'); removeItemBtn.disabled = false; removeItemBtn.textContent = '×'; return; }
                                document.getElementById('cvPreviewBody').innerHTML = res.d.cv_html;
                                initCvSortables();
                                if (res.d.job_positions_html) document.getElementById('jobPositionsBody').innerHTML = res.d.job_positions_html;
                                if (res.d.improve_html) document.getElementById('improveBody').innerHTML = res.d.improve_html;
                            })
                            .catch(function () { removeItemBtn.disabled = false; removeItemBtn.textContent = '×'; Botble.showError('Network error.'); });

                        return;
                    }

                    // --- Inline add responsibility ---
                    var addRespBtn = event.target.closest('.js-add-responsibility');
                    if (addRespBtn) {
                        // Prevent double-inserting an input
                        if (addRespBtn.parentNode.querySelector('.js-inline-resp-input')) return;

                        var expIndex  = parseInt(addRespBtn.dataset.expIndex, 10);
                        var nextIndex = parseInt(addRespBtn.dataset.nextIndex, 10);
                        var addUrl    = addRespBtn.dataset.url;

                        var wrapper = document.createElement('div');
                        wrapper.className = 'js-inline-resp-input d-flex align-items-center gap-1 mt-1';
                        wrapper.innerHTML = '<input type="text" class="form-control form-control-sm" placeholder="e.g. Managed a team of 5 sales staff" style="font-size:12px">'
                            + '<button type="button" class="btn btn-sm btn-success js-inline-resp-save flex-shrink-0" style="font-size:11px;padding:2px 8px">Save</button>'
                            + '<button type="button" class="btn btn-sm btn-outline-secondary js-inline-resp-cancel flex-shrink-0" style="font-size:11px;padding:2px 6px">×</button>';

                        addRespBtn.parentNode.insertBefore(wrapper, addRespBtn);
                        wrapper.querySelector('input').focus();

                        function submitResp() {
                            var val = wrapper.querySelector('input').value.trim();
                            if (!val) { wrapper.querySelector('input').focus(); return; }

                            var saveBtn = wrapper.querySelector('.js-inline-resp-save');
                            saveBtn.disabled = true;
                            saveBtn.textContent = '…';

                            var field = 'experience.' + expIndex + '.responsibilities.' + nextIndex;
                            fetch(addUrl, {
                                method : 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                    'Accept'      : 'application/json',
                                },
                                body: JSON.stringify({ field: field, value: val }),
                            })
                                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                                .then(function (res) {
                                    if (!res.ok) { Botble.showError(res.d.error || 'Could not save.'); saveBtn.disabled = false; saveBtn.textContent = 'Save'; return; }
                                    document.getElementById('cvPreviewBody').innerHTML = res.d.cv_html;
                                    initCvSortables();
                                    if (res.d.job_positions_html) document.getElementById('jobPositionsBody').innerHTML = res.d.job_positions_html;
                                    if (res.d.improve_html) document.getElementById('improveBody').innerHTML = res.d.improve_html;
                                })
                                .catch(function () { saveBtn.disabled = false; saveBtn.textContent = 'Save'; Botble.showError('Network error.'); });
                        }

                        wrapper.querySelector('.js-inline-resp-save').addEventListener('click', submitResp);
                        wrapper.querySelector('input').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); submitResp(); } });
                        wrapper.querySelector('.js-inline-resp-cancel').addEventListener('click', function () { wrapper.remove(); });

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
                                initCvSortables();
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

                    // --- Add CV Item ---
                    var addItemBtn = event.target.closest('.js-add-cv-item');
                    if (addItemBtn) {
                        var aciSection = addItemBtn.dataset.section;
                        var aciLabel   = addItemBtn.dataset.label;
                        var aciUrl     = addItemBtn.dataset.url;

                        document.getElementById('addCvItemTitle').textContent = 'Add ' + aciLabel;

                        // Show only the relevant fields panel
                        ['experience', 'projects', 'skills', 'languages'].forEach(function (s) {
                            document.getElementById('aci-' + s).classList.toggle('d-none', s !== aciSection);
                        });

                        // Clear fields
                        document.querySelectorAll('#modal-add-cv-item input, #modal-add-cv-item textarea').forEach(function (el) {
                            el.value = '';
                        });
                        document.getElementById('aci-error').classList.add('d-none');

                        var aciModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-add-cv-item'));

                        // Wire submit
                        var btnSubmit = document.getElementById('btnAddCvItemSubmit');
                        var freshBtn  = btnSubmit.cloneNode(true);
                        btnSubmit.parentNode.replaceChild(freshBtn, btnSubmit);
                        freshBtn.addEventListener('click', function () {
                            var item = {};
                            var errEl = document.getElementById('aci-error');
                            errEl.classList.add('d-none');

                            if (aciSection === 'experience') {
                                var title   = document.getElementById('aciExpTitle').value.trim();
                                var company = document.getElementById('aciExpCompany').value.trim();
                                if (!title || !company) {
                                    errEl.textContent = 'Job title and company are required.';
                                    errEl.classList.remove('d-none');
                                    return;
                                }
                                item = {
                                    job_title  : title,
                                    company    : company,
                                    start_date : document.getElementById('aciExpStart').value.trim(),
                                    end_date   : document.getElementById('aciExpEnd').value.trim(),
                                };
                            } else if (aciSection === 'projects') {
                                var name = document.getElementById('aciProjName').value.trim();
                                if (!name) {
                                    errEl.textContent = 'Project name is required.';
                                    errEl.classList.remove('d-none');
                                    return;
                                }
                                item = {
                                    name       : name,
                                    description: document.getElementById('aciProjDesc').value.trim(),
                                    link       : document.getElementById('aciProjLink').value.trim(),
                                };
                            } else if (aciSection === 'skills') {
                                var skill = document.getElementById('aciSkillValue').value.trim();
                                if (!skill) {
                                    errEl.textContent = 'Skill cannot be empty.';
                                    errEl.classList.remove('d-none');
                                    return;
                                }
                                item = { value: skill };
                            } else if (aciSection === 'languages') {
                                var lang = document.getElementById('aciLangName').value.trim();
                                if (!lang) {
                                    errEl.textContent = 'Language is required.';
                                    errEl.classList.remove('d-none');
                                    return;
                                }
                                item = {
                                    language   : lang,
                                    proficiency: document.getElementById('aciLangLevel').value,
                                };
                            }

                            freshBtn.disabled = true;
                            freshBtn.textContent = 'Saving…';

                            fetch(aciUrl, {
                                method : 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                    'Accept'      : 'application/json',
                                },
                                body: JSON.stringify({ section: aciSection, item: item }),
                            })
                                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                                .then(function (res) {
                                    freshBtn.disabled = false;
                                    freshBtn.textContent = 'Add';
                                    if (!res.ok) {
                                        errEl.textContent = res.d.error || 'Failed to add item.';
                                        errEl.classList.remove('d-none');
                                        return;
                                    }
                                    aciModal.hide();
                                    document.getElementById('cvPreviewBody').innerHTML = res.d.cv_html;
                                    initCvSortables();
                                    if (res.d.job_positions_html) document.getElementById('jobPositionsBody').innerHTML = res.d.job_positions_html;
                                    if (res.d.improve_html) document.getElementById('improveBody').innerHTML = res.d.improve_html;
                                    Botble.showSuccess(res.d.message || 'Item added.');
                                })
                                .catch(function () {
                                    freshBtn.disabled = false;
                                    freshBtn.textContent = 'Add';
                                    errEl.textContent = 'Network error — please try again.';
                                    errEl.classList.remove('d-none');
                                });
                        });

                        aciModal.show();
                        return;
                    }

                    var button = event.target.closest('.js-request-section-info');

                    if (!button) {
                        return;
                    }

                    // --- Get More Info modal wiring ---
                    var _gsiBtn        = button;
                    var gsiModal       = document.getElementById('modal-get-section-info');
                    var bsGsiModal     = bootstrap.Modal.getOrCreateInstance(gsiModal);
                    var injectUrl      = '{{ route('job-board.auto-cv-bot.inject-admin-reply', $session->getKey()) }}';

                    // Elements
                    var gsiTitle       = document.getElementById('getSectionInfoTitle');
                    var gsiPreview     = document.getElementById('gsiWhatsappPreview');
                    var gsiReplyText   = document.getElementById('gsiAdminReplyText');
                    var gsiReplyError  = document.getElementById('gsiAdminReplyError');
                    var stepChoose     = document.getElementById('gsi-step-choose');
                    var stepWhatsapp   = document.getElementById('gsi-step-whatsapp');
                    var stepAdmin      = document.getElementById('gsi-step-admin');
                    var btnBack        = document.getElementById('btnGsiBack');
                    var btnSendWa      = document.getElementById('btnGsiSendWhatsapp');
                    var btnSubmitAdmin = document.getElementById('btnGsiSubmitAdmin');

                    // Reset to step 1
                    gsiTitle.textContent = _gsiBtn.dataset.topicLabel || 'Get More Info';
                    gsiPreview.textContent = _gsiBtn.dataset.exactQuestion || 'A follow-up question for this section will be sent.';
                    gsiReplyText.value = '';
                    gsiReplyError.classList.add('d-none');
                    gsiReplyError.textContent = '';

                    stepChoose.classList.remove('d-none');
                    stepWhatsapp.classList.add('d-none');
                    stepAdmin.classList.add('d-none');
                    btnBack.classList.add('d-none');
                    btnSendWa.classList.add('d-none');
                    document.getElementById('btnGsiSubmitAdminSilent').classList.add('d-none');
                    document.getElementById('btnGsiSubmitAdmin').classList.add('d-none');
                    btnSendWa.disabled = false;

                    var gsiShowStep = function (step) {
                        stepChoose.classList.toggle('d-none', step !== 'choose');
                        stepWhatsapp.classList.toggle('d-none', step !== 'whatsapp');
                        stepAdmin.classList.toggle('d-none', step !== 'admin');
                        btnBack.classList.toggle('d-none', step === 'choose');
                        btnSendWa.classList.toggle('d-none', step !== 'whatsapp');
                        document.getElementById('btnGsiSubmitAdminSilent').classList.toggle('d-none', step !== 'admin');
                        document.getElementById('btnGsiSubmitAdmin').classList.toggle('d-none', step !== 'admin');
                    };

                    // Pick WhatsApp
                    var onPickWhatsapp = function () { gsiShowStep('whatsapp'); };
                    // Pick Admin
                    var onPickAdmin = function () { gsiShowStep('admin'); setTimeout(function () { gsiReplyText.focus(); }, 50); };
                    // Back
                    var onBack = function () { gsiShowStep('choose'); };

                    // Send on WhatsApp
                    var onSendWhatsapp = function () {
                        var origHtml = _gsiBtn.innerHTML;
                        bsGsiModal.hide();
                        _gsiBtn.disabled = true;
                        _gsiBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending';

                        fetch(_gsiBtn.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(Object.assign(
                                { topic_number: parseInt(_gsiBtn.dataset.topicNumber, 10) },
                                _gsiBtn.dataset.exactQuestion ? { exact_question: _gsiBtn.dataset.exactQuestion } : {}
                            )),
                        })
                            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                            .then(function (result) {
                                _gsiBtn.disabled = false;
                                _gsiBtn.innerHTML = origHtml;
                                if (!result.ok) { Botble.showError(result.data.error || 'Failed to send section request.'); return; }
                                Botble.showSuccess(result.data.message || 'Question sent on WhatsApp.');
                                poll();
                                if (!pollTimer) { document.getElementById('liveIndicator').classList.remove('d-none'); pollTimer = setInterval(poll, 3000); }
                            })
                            .catch(function () {
                                _gsiBtn.disabled = false;
                                _gsiBtn.innerHTML = origHtml;
                                Botble.showError('Network error — please try again.');
                            });
                    };

                    // Submit admin reply (silent = true → no WhatsApp; false → AI continues chat)
                    var onSubmitAdmin = function (silent) {
                        var text = gsiReplyText.value.trim();
                        if (!text) {
                            gsiReplyError.textContent = 'Please enter the candidate\'s information.';
                            gsiReplyError.classList.remove('d-none');
                            return;
                        }
                        gsiReplyError.classList.add('d-none');

                        var btnSilent = document.getElementById('btnGsiSubmitAdminSilent');
                        var btnChat   = document.getElementById('btnGsiSubmitAdmin');
                        var activeBtn = silent ? btnSilent : btnChat;
                        var origHtml  = activeBtn.innerHTML;
                        btnSilent.disabled = true;
                        btnChat.disabled   = true;
                        activeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (silent ? 'Updating…' : 'Processing…');

                        fetch(injectUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ reply_text: text, silent: silent }),
                        })
                            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                            .then(function (result) {
                                btnSilent.disabled = false;
                                btnChat.disabled   = false;
                                activeBtn.innerHTML = origHtml;
                                if (!result.ok) {
                                    gsiReplyError.textContent = result.data.error || 'Failed to submit.';
                                    gsiReplyError.classList.remove('d-none');
                                    return;
                                }
                                bsGsiModal.hide();
                                Botble.showSuccess(result.data.message || 'Done.');
                                poll();
                                if (!pollTimer) { document.getElementById('liveIndicator').classList.remove('d-none'); pollTimer = setInterval(poll, 3000); }
                            })
                            .catch(function () {
                                btnSilent.disabled = false;
                                btnChat.disabled   = false;
                                activeBtn.innerHTML = origHtml;
                                gsiReplyError.textContent = 'Network error — please try again.';
                                gsiReplyError.classList.remove('d-none');
                            });
                    };

                    // Wire up buttons (replace old listeners each open by cloning)
                    function rewire(el, fn) {
                        var fresh = el.cloneNode(true);
                        el.parentNode.replaceChild(fresh, el);
                        fresh.addEventListener('click', fn);
                        return fresh;
                    }
                    btnBack        = rewire(btnBack, onBack);
                    btnSendWa      = rewire(btnSendWa, onSendWhatsapp);
                    btnSubmitAdmin = rewire(document.getElementById('btnGsiSubmitAdmin'), function () { onSubmitAdmin(false); });
                    rewire(document.getElementById('btnGsiSubmitAdminSilent'), function () { onSubmitAdmin(true); });
                    rewire(document.getElementById('btnGsiPickWhatsapp'), onPickWhatsapp);
                    rewire(document.getElementById('btnGsiPickAdmin'), onPickAdmin);

                    bsGsiModal.show();
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
                            initCvSortables();
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

            document.addEventListener('click', function (event) {
                var showLinkForm = event.target.closest('#btnShowLinkByIdForm');
                if (showLinkForm) {
                    document.getElementById('linkByIdForm')?.classList.toggle('d-none');
                    return;
                }

                var linkBtn = event.target.closest('.js-link-account-btn');
                if (!linkBtn) return;

                var action = linkBtn.dataset.action;
                var url = linkBtn.dataset.url;
                var body = { action: action };

                if (action === 'link-by-id') {
                    var idInput = document.getElementById('linkAccountIdInput');
                    var accountId = parseInt(idInput?.value || '0', 10);
                    if (!accountId) { Botble.showError('Enter a valid account ID.'); return; }
                    body.account_id = accountId;
                }

                var originalHtml = linkBtn.innerHTML;
                linkBtn.disabled = true;
                linkBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                    .then(function (result) {
                        linkBtn.disabled = false;
                        linkBtn.innerHTML = originalHtml;
                        if (!result.ok) { Botble.showError(result.data.error || 'Failed.'); return; }
                        Botble.showSuccess(result.data.message || 'Done.');
                        if (result.data.hero_html) {
                            document.getElementById('jobPositionsBody').innerHTML = result.data.hero_html;
                        }
                    })
                    .catch(function () {
                        linkBtn.disabled = false;
                        linkBtn.innerHTML = originalHtml;
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
        // ── Photo cropper ─────────────────────────────────────────────────
        (function () {
            var cropperInstance = null;
            var cropModal       = null;
            var saveCropUrl     = null;
            var localUploadObjectUrl = null;

            function openCropperForImage(imageUrl, saveUrl) {
                var img = document.getElementById('cropperImg');

                if (!img || !imageUrl || !saveUrl) {
                    return;
                }

                saveCropUrl = saveUrl;
                img.src = imageUrl;

                if (!cropModal) cropModal = new bootstrap.Modal(document.getElementById('modal-crop-photo'));

                if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }

                img.onload = function () {
                    cropperInstance = new Cropper(img, {
                        aspectRatio : 1,
                        viewMode    : 1,
                        autoCropArea: 0.9,
                        movable     : true,
                        zoomable    : true,
                        rotatable   : true,
                        scalable    : true,
                    });
                };

                cropModal.show();
            }

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-open-crop-photo');
                if (btn) {
                    openCropperForImage(btn.dataset.photoUrl + '?t=' + Date.now(), btn.dataset.saveUrl);
                    return;
                }

                btn = e.target.closest('.js-upload-cv-photo');
                if (!btn) return;

                var inputId = btn.getAttribute('data-upload-input-id');
                var input = inputId ? document.getElementById(inputId) : null;
                if (!input) return;
                input.dataset.saveUrl = btn.dataset.saveUrl || '';
                input.click();
            });

            document.addEventListener('change', function (e) {
                var input = e.target;

                if (!input.matches('input[type="file"][id^="cvPhotoUploadInput"]')) {
                    return;
                }

                var file = input.files && input.files[0];

                if (!file) {
                    return;
                }

                if (!/^image\/(jpeg|png|webp)$/i.test(file.type)) {
                    Botble.showError('Please choose a JPG, PNG, or WEBP image.');
                    input.value = '';
                    return;
                }

                if (localUploadObjectUrl) {
                    URL.revokeObjectURL(localUploadObjectUrl);
                }

                localUploadObjectUrl = URL.createObjectURL(file);
                openCropperForImage(localUploadObjectUrl, input.dataset.saveUrl || '');
                input.value = '';
            });

            // Controls
            document.getElementById('btnCropRotateLeft')?.addEventListener('click',  function () { cropperInstance?.rotate(-90); });
            document.getElementById('btnCropRotateRight')?.addEventListener('click', function () { cropperInstance?.rotate(90); });
            document.getElementById('btnCropFlipH')?.addEventListener('click',       function () { if (!cropperInstance) return; var d = cropperInstance.getData(); cropperInstance.scaleX(d.scaleX === -1 ? 1 : -1); });
            document.getElementById('btnCropReset')?.addEventListener('click',       function () { cropperInstance?.reset(); });

            document.getElementById('btnCropAspectFree')?.addEventListener('click', function () {
                cropperInstance?.setAspectRatio(NaN);
                document.querySelectorAll('#btnCropAspectFree,#btnCropAspect1x1,#btnCropAspect4x5').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
            });
            document.getElementById('btnCropAspect1x1')?.addEventListener('click', function () {
                cropperInstance?.setAspectRatio(1);
                document.querySelectorAll('#btnCropAspectFree,#btnCropAspect1x1,#btnCropAspect4x5').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
            });
            document.getElementById('btnCropAspect4x5')?.addEventListener('click', function () {
                cropperInstance?.setAspectRatio(4 / 5);
                document.querySelectorAll('#btnCropAspectFree,#btnCropAspect1x1,#btnCropAspect4x5').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
            });

            document.getElementById('btnCropSave')?.addEventListener('click', function () {
                if (!cropperInstance || !saveCropUrl) return;
                var btn = this;
                btn.disabled = true;
                btn.textContent = 'Saving…';

                var canvas = cropperInstance.getCroppedCanvas({ width: 800, height: 800, imageSmoothingQuality: 'high' });
                var dataUrl = canvas.toDataURL('image/jpeg', 0.92);

                fetch(saveCropUrl, {
                    method : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept'      : 'application/json',
                    },
                    body: JSON.stringify({ image: dataUrl }),
                })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        btn.disabled = false;
                        btn.textContent = 'Save Crop';
                        if (!res.ok) { Botble.showError(res.d.error || 'Could not save photo.'); return; }
                        cropModal.hide();
                        Botble.showSuccess('Photo cropped and saved.');
                        if (res.d.cv_html) {
                            document.getElementById('cvPreviewBody').innerHTML = res.d.cv_html;
                        }
                        if (res.d.job_positions_html) {
                            document.getElementById('jobPositionsBody').innerHTML = res.d.job_positions_html;
                        }
                        if (res.d.improve_html) {
                            document.getElementById('improveBody').innerHTML = res.d.improve_html;
                        }
                    })
                    .catch(function () {
                        btn.disabled = false;
                        btn.textContent = 'Save Crop';
                        Botble.showError('Network error — please try again.');
                    });
            });

            // Clean up cropper when modal closes
            document.getElementById('modal-crop-photo')?.addEventListener('hidden.bs.modal', function () {
                if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
                if (localUploadObjectUrl) {
                    URL.revokeObjectURL(localUploadObjectUrl);
                    localUploadObjectUrl = null;
                }
            });
        })();
        </script>
    @endpush
@endsection
