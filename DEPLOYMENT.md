# Production Deployment

This repository does not commit `.env` or `vendor/`. After a fresh pull, the app is not runnable until the server creates `.env` and installs Composer dependencies.

## First-time setup

1. Pull the latest `main`.
2. Copy `.env.production.example` to `.env`.
3. Fill in the real database credentials, mail settings, and any third-party keys.
4. Run:

```bash
chmod +x scripts/deploy-production.sh
./scripts/deploy-production.sh
```

## What the deploy script does

- installs Composer dependencies without dev packages
- generates `APP_KEY` if it is missing
- clears stale Laravel caches
- creates the storage symlink if needed
- runs database migrations
- rebuilds config, route, and view caches

## Nginx / PHP-FPM note

Your web root should point at `public/`, and the PHP-FPM user must be able to read the project and write to `storage/` and `bootstrap/cache/`.
