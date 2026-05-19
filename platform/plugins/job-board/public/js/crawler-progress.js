$(() => {
    // ── Mode picker modal (Full pull vs Incremental) ───────────────────────────
    if (!document.getElementById('crawler-mode-modal')) {
        document.body.insertAdjacentHTML('beforeend', `
<div id="crawler-mode-modal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header py-2 px-3">
        <h6 class="modal-title mb-0 fw-semibold">Run Careers24 agent</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-3 py-3">
        <p class="text-muted small mb-3">Choose how to run this agent:</p>
        <div class="d-grid gap-2">
          <button id="crawler-mode-full" class="btn btn-primary text-start px-3 py-2" style="white-space:normal;">
            <div class="fw-semibold"><i class="ti ti-database-import me-1"></i> Full data pull</div>
            <div class="small opacity-75 mt-1">Fetches all available jobs across every page.<br>Slow &amp; thorough — best run once overnight.</div>
          </button>
          <button id="crawler-mode-incremental" class="btn btn-outline-secondary text-start px-3 py-2" style="white-space:normal;">
            <div class="fw-semibold"><i class="ti ti-refresh me-1"></i> Incremental update</div>
            <div class="small text-muted mt-1">Fetches only new jobs since last run.<br>Fast — safe to run daily or on demand.</div>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>`);
    }

    const $modeModal    = $('#crawler-mode-modal');
    let   _pendingBtn   = null;
    let   _pendingBase  = '';

    $('#crawler-mode-full').on('click', () => {
        $modeModal.modal('hide');
        fireRun(_pendingBtn, _pendingBase + '?mode=full');
    });
    $('#crawler-mode-incremental').on('click', () => {
        $modeModal.modal('hide');
        fireRun(_pendingBtn, _pendingBase + '?mode=incremental');
    });

    // ── Progress modal ─────────────────────────────────────────────────────────
    if (!document.getElementById('crawler-progress-modal')) {
        document.body.insertAdjacentHTML('beforeend', `
<div id="crawler-progress-modal" class="modal fade" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header py-2 px-3">
        <span class="d-flex align-items-center gap-2 small fw-semibold">
          <span class="spinner-border spinner-border-sm text-primary" id="crawler-spinner"></span>
          <span id="crawler-modal-title">Running agent…</span>
        </span>
      </div>
      <div class="modal-body py-2 px-3">
        <div id="crawler-running">
          <div class="d-flex justify-content-between mb-1">
            <small class="text-muted" id="crawler-stage-label">Starting…</small>
            <small class="text-muted" id="crawler-count-label"></small>
          </div>
          <div class="progress mb-0" style="height:6px;">
            <div id="crawler-progress-bar"
                 class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                 role="progressbar" style="width:0%"></div>
          </div>
        </div>
        <div id="crawler-done" style="display:none;">
          <div id="crawler-done-success" style="display:none;" class="small">
            <div class="d-flex align-items-center gap-1 text-success mb-1">
              <i class="ti ti-circle-check"></i> <strong>Completed</strong>
            </div>
            <div id="crawler-stats" class="text-muted"></div>
          </div>
          <div id="crawler-done-error" style="display:none;" class="small">
            <div class="d-flex align-items-center gap-1 text-danger mb-1">
              <i class="ti ti-alert-circle"></i> <strong>Failed</strong>
            </div>
            <div id="crawler-error-msg" class="text-muted"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer py-2 px-3" id="crawler-modal-footer" style="display:none;">
        <a href="#" id="crawler-view-report" class="btn btn-primary btn-sm">View report</a>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>`);
    }

    const $modal       = $('#crawler-progress-modal');
    const $spinner     = $('#crawler-spinner');
    const $title       = $('#crawler-modal-title');
    const $stageLabel  = $('#crawler-stage-label');
    const $countLabel  = $('#crawler-count-label');
    const $bar         = $('#crawler-progress-bar');
    const $running     = $('#crawler-running');
    const $done        = $('#crawler-done');
    const $doneSuccess = $('#crawler-done-success');
    const $doneError   = $('#crawler-done-error');
    const $stats       = $('#crawler-stats');
    const $errorMsg    = $('#crawler-error-msg');
    const $footer      = $('#crawler-modal-footer');
    const $viewReport  = $('#crawler-view-report');

    let pollTimer    = null;
    let runCompleted = false;

    // Derive the active-runs endpoint from the current path (agents index page only).
    const activeRunsUrl = window.location.pathname.replace(/\/+$/, '') + '/active-runs';

    // -------------------------------------------------------------------------
    // Modal helpers
    // -------------------------------------------------------------------------

    function resetModal() {
        $spinner.show();
        $title.text('Running agent…');
        $stageLabel.text('Starting…');
        $countLabel.text('');
        $bar.css('width', '0%').addClass('progress-bar-animated bg-primary').removeClass('bg-danger bg-success');
        $running.show();
        $done.hide();
        $doneSuccess.hide();
        $doneError.hide();
        $footer.hide();
        $stats.html('');
        $errorMsg.text('');
        runCompleted = false;
    }

    function updateProgress(d) {
        const stage = d.stage || 'scanning';
        const page  = d.current_page || 0;
        const total = d.total_pages || 20;
        const found = d.jobs_found_so_far || 0;

        let pct, stageText, countText;

        if (stage === 'scanning') {
            pct       = total > 0 ? Math.min(Math.round((page / total) * 50), 50) : 0;
            stageText = `Scanning page ${page}…`;
            const newFound = d.new_found_so_far || 0;
            countText = newFound ? `${newFound} new found` : (found ? `${found} seen` : '');

        } else if (stage === 'importing_new') {
            const cur      = d.new_current || 0;
            const newTotal = d.new_total || 0;
            pct       = newTotal > 0 ? 50 + Math.min(Math.round((cur / newTotal) * 35), 35) : 52;
            stageText = newTotal > 0
                ? `Importing ${newTotal} new job${newTotal !== 1 ? 's' : ''} (${cur} / ${newTotal})…`
                : 'No new jobs — checking existing…';
            countText = (d.jobs_created || 0) > 0 ? `${d.jobs_created} created` : '';

        } else if (stage === 'updating_existing') {
            const cur = d.existing_current || 0;
            const exT = d.existing_total || 0;
            pct       = exT > 0 ? 85 + Math.min(Math.round((cur / exT) * 13), 13) : 87;
            stageText = exT > 0
                ? `Refreshing ${exT} existing job${exT !== 1 ? 's' : ''} (${cur} / ${exT})…`
                : 'Finalising…';
            countText = (d.jobs_updated || 0) > 0 ? `${d.jobs_updated} updated` : '';

        } else {
            pct       = 99;
            stageText = 'Finalising…';
            countText = '';
        }

        $bar.css('width', pct + '%');
        $stageLabel.text(stageText);
        $countLabel.text(countText);
    }

    function showDone(d) {
        clearInterval(pollTimer);
        pollTimer    = null;
        runCompleted = true;

        $spinner.hide();
        $title.text('Agent finished');
        $running.hide();
        $done.show();
        $footer.show();
        $viewReport.attr('href', d.run_url || '#');

        if (d.status === 'success') {
            $bar.css('width', '100%').removeClass('progress-bar-animated bg-primary').addClass('bg-success');
            $doneSuccess.show();

            const created     = d.jobs_created || 0;
            const updated     = d.jobs_updated || 0;
            const skipped     = d.jobs_skipped || 0;
            const unpublished = d.jobs_unpublished || 0;
            const bgQueued    = d.bg_queued || 0;

            let html = `<span class="me-3"><strong>${created}</strong> new</span>`
                     + `<span class="me-3"><strong>${updated}</strong> updated</span>`;
            if (skipped)     html += `<span class="me-3"><strong>${skipped}</strong> skipped</span>`;
            if (unpublished) html += `<span class="me-3 text-warning"><strong>${unpublished}</strong> unpublished</span>`;
            if (bgQueued)    html += `<br><small class="text-muted">&#8635; ${bgQueued} existing queued for background detail refresh</small>`;

            $stats.html(html);
        } else {
            $bar.css('width', '100%').removeClass('progress-bar-animated bg-primary').addClass('bg-danger');
            $doneError.show();
            $errorMsg.text(d.error_message || 'Unknown error.');
        }

        // Revert all "Running…" buttons so the table reflects the new state.
        restoreRunButtons();
    }

    function pollStatus(statusUrl) {
        if (pollTimer) clearInterval(pollTimer);

        pollTimer = setInterval(() => {
            $httpClient.make().get(statusUrl)
                .then(({ data: resp }) => {
                    const d = resp.data;
                    if (d.status === 'running') {
                        updateProgress(d);
                    } else {
                        showDone(d);
                    }
                })
                .catch(() => {});
        }, 1500);
    }

    // -------------------------------------------------------------------------
    // Row-level "Running…" button management
    // -------------------------------------------------------------------------

    function findRunButton(crawlerId) {
        // The run button href ends with /agents/{id}/run — match by the crawler ID.
        return $('[data-crawler-run]').filter(function () {
            const href = $(this).prop('href') || $(this).attr('href') || '';
            const m = href.match(/\/agents\/(\d+)\/run/);
            return m && parseInt(m[1], 10) === crawlerId;
        });
    }

    function markAsRunning($btn, run) {
        if ($btn.length === 0 || $btn.data('crawler-watching')) return;

        $btn.data('crawler-watching', true)
            .data('crawler-status-url', run.status_url)
            .data('crawler-run-url', run.run_url)
            .removeClass('btn-success btn-icon')
            .addClass('btn-warning')
            .html('<span class="spinner-border spinner-border-sm me-1" style="width:.7rem;height:.7rem;"></span>Running…');
    }

    function restoreRunButtons() {
        $('[data-crawler-run][data-crawler-watching]')
            .removeData(['crawler-watching', 'crawler-status-url', 'crawler-run-url'])
            .removeClass('btn-warning')
            .addClass('btn-success btn-icon')
            .html('<i class="ti ti-player-play"></i>');
    }

    // -------------------------------------------------------------------------
    // Active-run detection (called on load + after every table draw)
    // -------------------------------------------------------------------------

    function checkActiveRuns() {
        $httpClient.make().get(activeRunsUrl)
            .then(({ data: resp }) => {
                const runs = resp.data || [];

                // Clear stale watching state for crawlers no longer running.
                const activeCrawlerIds = new Set(runs.map(r => r.crawler_id));
                $('[data-crawler-run][data-crawler-watching]').each(function () {
                    const href = $(this).prop('href') || $(this).attr('href') || '';
                    const m = href.match(/\/agents\/(\d+)\/run/);
                    if (m && !activeCrawlerIds.has(parseInt(m[1], 10))) {
                        $(this).removeData(['crawler-watching', 'crawler-status-url', 'crawler-run-url'])
                               .removeClass('btn-warning')
                               .addClass('btn-success btn-icon')
                               .html('<i class="ti ti-player-play"></i>');
                    }
                });

                runs.forEach(run => {
                    const $btn = findRunButton(run.crawler_id);
                    if ($btn.length) markAsRunning($btn, run);
                });
            })
            .catch(() => {});
    }

    // Re-check whenever the DataTable redraws (pagination, search, reload).
    $(document).on('draw.dt', () => checkActiveRuns());

    // Initial check on page load.
    checkActiveRuns();

    // -------------------------------------------------------------------------
    // Core: fire a run against a URL (mode already baked into the URL)
    // -------------------------------------------------------------------------

    function fireRun($btn, runUrl) {
        resetModal();
        $modal.modal('show');

        $httpClient.make().post(runUrl)
            .then(({ data: resp }) => {
                const d = resp.data;
                if (d && d.status_url) {
                    markAsRunning($btn, { status_url: d.status_url, run_url: d.run_url });
                    pollStatus(d.status_url);
                } else {
                    $modal.modal('hide');
                    Botble.showSuccess(resp.message || 'Crawl started.');
                }
            })
            .catch(() => {
                $modal.modal('hide');
            });
    }

    // -------------------------------------------------------------------------
    // Click: "Run" — Careers24 shows a mode picker; others start immediately
    // -------------------------------------------------------------------------

    $(document).on('click', '[data-crawler-run]', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const $btn       = $(this);
        const rawHref    = $btn.prop('href') || '';
        const hrefUrl    = new URL(rawHref, window.location.origin);
        const parserType = hrefUrl.searchParams.get('pt') || '';
        // Strip the helper ?pt= param — the server only needs the clean run URL.
        hrefUrl.searchParams.delete('pt');
        const baseRunUrl = hrefUrl.toString();

        // If already watching a running crawl, re-attach to the live progress.
        if ($btn.data('crawler-watching')) {
            resetModal();
            $modal.modal('show');
            pollStatus($btn.data('crawler-status-url'));
            return;
        }

        // Careers24: show the mode picker modal before starting.
        if (parserType === 'careers24') {
            _pendingBtn  = $btn;
            _pendingBase = baseRunUrl;
            $modeModal.modal('show');
            return;
        }

        // All other parser types: start immediately without prompting.
        fireRun($btn, baseRunUrl);
    });

    // -------------------------------------------------------------------------
    // Modal close: reload the DataTable so the status column updates
    // -------------------------------------------------------------------------

    $modal.on('hidden.bs.modal', () => {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }

        if (runCompleted) {
            runCompleted = false;
            try {
                const api = $.fn.DataTable.tables({ visible: true, api: true });
                if (api.length) api.ajax.reload(null, false);
            } catch (_) {}
        }
    });

    // -------------------------------------------------------------------------
    // Fix: prevent .table-responsive overflow from clipping action dropdowns
    // -------------------------------------------------------------------------
    $(document).on('show.bs.dropdown', function (e) {
        $(e.target).closest('.table-responsive').css('overflow', 'visible');
    }).on('hidden.bs.dropdown', function (e) {
        $(e.target).closest('.table-responsive').css('overflow', '');
    });

    // -------------------------------------------------------------------------
    // Status badge tooltip: show on badge hover, positioned via fixed coords
    // -------------------------------------------------------------------------
    $(document).on('mouseenter', '.crstatus', function () {
        const $tip = $(this).find('.crstatus-tip');
        if (!$tip.length) return;
        const rect = this.getBoundingClientRect();
        const left = Math.min(rect.left, window.innerWidth - 330);
        $tip.css({ top: rect.bottom + 8, left }).addClass('crstatus-tip--open');
    }).on('mouseleave', '.crstatus', function () {
        $(this).find('.crstatus-tip').removeClass('crstatus-tip--open');
    });

    // -------------------------------------------------------------------------
    // Agent name tooltip: show on full-row hover, positioned via fixed coords
    // -------------------------------------------------------------------------
    $(document).on('mouseenter', 'tbody tr', function () {
        const $tip = $(this).find('.crname-tip');
        if (!$tip.length) return;
        const anchor = $(this).find('.crname')[0];
        if (!anchor) return;
        const rect = anchor.getBoundingClientRect();
        $tip.css({ top: rect.bottom + 8, left: rect.left }).addClass('crname-tip--open');
    }).on('mouseleave', 'tbody tr', function () {
        $(this).find('.crname-tip').removeClass('crname-tip--open');
    });

    // -------------------------------------------------------------------------
    // Inline toggle: enable / disable agent
    // -------------------------------------------------------------------------
    $(document).on('change', '.crawler-toggle-active', function () {
        const $toggle = $(this);
        const url = $toggle.data('url');
        const enabled = $toggle.is(':checked');

        $toggle.prop('disabled', true);

        $.post(url, { _token: $('meta[name="csrf-token"]').attr('content') })
            .done(function (res) {
                const active = res.data && res.data.is_active;
                $toggle.prop('checked', active);
                Botble.showSuccess(res.message || (active ? 'Agent enabled.' : 'Agent disabled.'));
            })
            .fail(function () {
                $toggle.prop('checked', !enabled);
                Botble.showError('Failed to update agent status.');
            })
            .always(function () {
                $toggle.prop('disabled', false);
            });
    });
});
