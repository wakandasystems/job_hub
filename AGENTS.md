# AGENTS.md

Agent-specific rules for this repository. Read alongside `CLAUDE.md`.

## Confirmations — Bootstrap modals, not `onclick confirm()`

All destructive or irreversible admin actions **must** use a Bootstrap modal dialog. Never use `onclick="return confirm('...')"`.

**Pattern:**

```blade
{{-- Trigger button --}}
<button type="button" class="btn btn-sm btn-danger"
    data-bs-toggle="modal" data-bs-target="#deleteModal"
    data-action="{{ route('resource.destroy', $item) }}"
    data-label="{{ $item->name }}">
    Delete
</button>

{{-- Modal (once per page, outside the loop) --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4 px-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                        <i class="ti ti-trash text-danger fs-3"></i>
                    </span>
                </div>
                <h6 class="fw-semibold mb-1">Delete this item?</h6>
                <p class="text-muted small mb-4" id="deleteModalLabel">This cannot be undone.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger px-4">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('footer')
<script>
    document.getElementById('deleteModal').addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        document.getElementById('deleteForm').action = btn.dataset.action;
        document.getElementById('deleteModalLabel').textContent = btn.dataset.label;
    });
</script>
@endpush
```

Use `bg-success` / `ti ti-check` for approve actions, `bg-danger` / `ti ti-x` for reject/delete. Wire the form `action` via the `show.bs.modal` event — never hardcode it in the modal HTML.

## Crawlers — never use sitemaps

Always scrape the live jobs listing page or its AJAX endpoint directly. **Never use XML sitemaps** (`sitemap.xml`, `job_listing-sitemap.xml`, etc.) as a crawl source — sitemaps lag behind the live site and miss newly posted jobs.

For WP Job Manager sites the AJAX endpoint is `/jm-ajax/get_listings/` (POST, form-encoded). It returns `found_jobs`, `max_num_pages`, and `html` with the job cards. Parse that HTML, then fetch each detail page for the full description.

## Crawlers — never run in parallel from the CLI

When running crawlers manually via `php artisan cms:jobs:crawl <id>`, **run only one at a time**. Running multiple crawlers simultaneously forks several memory-heavy PHP processes, saturates the CPU on this 2-core server, pushes it into swap, and takes the site down.

Always run:
```bash
php artisan cms:jobs:crawl 1   # wait for it to finish
php artisan cms:jobs:crawl 60  # then the next
```

Never:
```bash
php artisan cms:jobs:crawl 1 &
php artisan cms:jobs:crawl 60 &  # ← this caused a site outage
```

The scheduler already runs crawlers sequentially (one `foreach` loop) and uses `withoutOverlapping()` to prevent two scheduled runs from stacking. When triggering manually, respect the same constraint.

## Other rules

- Always run `php artisan view:clear && php artisan cache:clear` after Blade or PHP changes.
- All migrations need `--force` flag on this production server.
- Paid job-alert quota queries must use `->activePaid()` scope — never raw `->whereNotNull('package_id')`.
- After editing `.env` with `sed`, restore: `chown root:www-data .env && chmod 640 .env`.
- After editing `platform/themes/jobbox/public/css/style.css`, copy to `public/themes/jobbox/css/style.css`.
