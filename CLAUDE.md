# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Wakanda Jobs** — a Laravel 12 / Botble CMS job board at `https://www.wakandajobs.com`. Single plugin (`job-board`) owns almost all domain logic. Production server; `APP_ENV=production`, `APP_DEBUG=true`.

---

## Common commands

```bash
# Clear all caches (do this after any PHP or Blade change)
php artisan optimize:clear

# Individual clears
php artisan view:clear
php artisan cache:clear
php artisan route:clear
php artisan config:clear

# Run a specific migration
php artisan migrate --path=platform/plugins/job-board/database/migrations/<file>.php --force

# Create a migration scoped to the plugin
php artisan make:migration <name> --path=platform/plugins/job-board/database/migrations

# Deploy to production
./scripts/deploy-production.sh
```

No test suite is currently wired up (`tests/` exists but is empty). No frontend build step is needed for day-to-day work — compiled assets are committed.

### CSS deploy rule
After editing `platform/themes/jobbox/public/css/style.css` always copy to the served path:
```bash
cp platform/themes/jobbox/public/css/style.css public/themes/jobbox/css/style.css
```

### `.env` permissions rule
After any `sed -i` on `.env` (which replaces the file as root), immediately restore ownership:
```bash
chown root:www-data .env && chmod 640 .env
```

---

## Architecture

### Botble CMS plugin system
All code lives under `platform/`. Botble uses a WordPress-style hook system (`add_action`, `add_filter`, `do_action`, `apply_filters`) to wire plugins together without direct dependencies. Constants for hook names are defined in `platform/core/base/helpers/constants.php` and each plugin's own `helpers/constants.php`.

### The `job-board` plugin
`platform/plugins/job-board/` is the single source of truth for all domain logic. Key providers:

| Provider | Role |
|---|---|
| `JobBoardServiceProvider` | Boots the plugin: registers routes, admin menu items, Blade views, email templates, permission definitions |
| `HookServiceProvider` | All cross-plugin wiring: payment hooks, menu badge counts, Twig extensions, social login callbacks |
| `EventServiceProvider` | Maps domain events (e.g. `JobPublishedEvent`) to listeners |
| `CommandServiceProvider` | Registers Artisan commands |

### Routing
Routes are split across four files in `platform/plugins/job-board/routes/`:
- `web.php` — admin panel routes (wrapped in `AdminHelper::registerRoutes()`)
- `public.php` — unauthenticated public pages
- `account.php` — authenticated candidate/employer account pages (`/account/...`)
- `api.php` — REST API endpoints

Account routes use `JobBoardHelper::scope('view.path', $data)` which resolves to `platform/themes/jobbox/views/job-board/<view.path>.blade.php`.

### Theme
`platform/themes/jobbox/` — all frontend Blade views, partials, and assets. Views are resolved via `JobBoardHelper::scope()` (account pages) or `Theme::scope()` (public pages). The theme has no build step; compiled CSS/JS are committed in `public/themes/jobbox/`.

### Payment flow
The payment plugin is generic. The job-board hooks into it via `HookServiceProvider`:

1. `PAYMENT_FILTER_PAYMENT_DATA` — transforms raw POST data into a typed payment data array with `order_id`. Each checkout type (job packages, career services, job alert orders) is detected by a unique hidden field in its form (`job_alert_order_id`, `career_service_order_id`, `subscribed_packaged_id` session).
2. `PAYMENT_ACTION_PAYMENT_PROCESSED` — fires after payment gateway processes the charge. Auto-approves non-manual gateways; leaves bank transfer / COD as `pending`.
3. `ACTION_AFTER_UPDATE_PAYMENT` — fires when admin marks a manual payment as completed in the admin panel, triggering approval of the corresponding order.
4. `PAYMENT_FILTER_REDIRECT_URL` / `PAYMENT_FILTER_CANCEL_URL` — return the correct post-payment redirect per checkout type (stored in session).

Manual payments (bank transfer, COD) require admin approval at `/admin/career-service-orders` or `/admin/job-alert-orders` respectively.

### Admin menu badges
`HookServiceProvider::getMenuItemCount()` returns counts keyed by CSS class name. `countPendingApplications()` maps admin menu item IDs to those class names. The badge component at `platform/core/base/resources/views/components/navbar/badge-count.blade.php` polls `/admin/menu-items-count` via AJAX.

### Job Alert Quota system
Three tables: `jb_job_alert_packages`, `jb_job_alert_orders`, `jb_job_alert_quotas`.

- Orders are created when checkout begins (`status=pending`).
- `JobAlertOrder::approve()` is the **only** place that writes to `jb_job_alert_quotas` and sets `is_approved=true`.
- All paid quota queries must use the `->activePaid()` scope (`whereNotNull('package_id')->where('is_approved', true)`) — both in views and in `SendJobAlertListener`. Free-tier rows have `is_approved=null` and are always active.

### Career Services
`CareerServiceOrder` is created at booking time with `status=pending`. Paid automatically on Stripe/etc callback; requires admin delivery management at `/admin/career-service-orders`.

---

## Conventions

- **Bootstrap modals over `onclick confirm()`** — all destructive/irreversible admin actions use Bootstrap modal confirmations. Wire with `data-bs-toggle="modal"` + `data-action` on the trigger button; populate the form `action` via the `show.bs.modal` event in a `@push('footer')` script block.
- **Admin email notifications** — use `setting('admin_email') ?: config('mail.from.address')` and `Mail::raw()` wrapped in `try/catch(\Throwable)` (non-fatal).
- **New payment checkout types** — add a hidden `<input name="<type>_id">` to the checkout form, handle it first in `PAYMENT_FILTER_PAYMENT_DATA` (store session keys `<type>_callback_url`, `<type>_return_url`, `<type>_order_id`), then in `PAYMENT_ACTION_PAYMENT_PROCESSED`, `PAYMENT_FILTER_REDIRECT_URL`, and `PAYMENT_FILTER_CANCEL_URL`.
- **Migrations always `--force`** — production environment blocks interactive prompts.
- **View cache** — run `php artisan view:clear` after any Blade change; the production server caches compiled views.
