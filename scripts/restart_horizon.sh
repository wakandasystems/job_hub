#!/usr/bin/env bash

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
WORKER_USER="${WORKER_USER:-www-data}"

cd "$APP_DIR"

run_as_worker() {
    if [[ "$(id -un)" == "$WORKER_USER" ]]; then
        "$@"
    else
        sudo -u "$WORKER_USER" "$@"
    fi
}

echo "Sending TERM to Horizon master process..."
run_as_worker "$PHP_BIN" artisan horizon:terminate

echo "Waiting for supervisor to relaunch Horizon..."
for _ in $(seq 1 20); do
    sleep 1
    if run_as_worker "$PHP_BIN" artisan horizon:status 2>/dev/null | grep -qi "running"; then
        echo "Horizon is back up and active."
        exit 0
    fi
done

echo "Horizon did not come back up within 20s. Check your process supervisor (systemd/supervisord)." >&2
exit 1
