#!/usr/bin/env bash
# Fix file ownership and permissions for Laravel writable directories.
# Run as root whenever artisan commands leave root-owned files under storage/ or bootstrap/cache/.
# Symptoms: "Failed to open stream: Permission denied" on cache/session/log files.

set -euo pipefail

APP_ROOT="$(cd "$(dirname "$0")" && pwd)"

echo "Fixing ownership and permissions in ${APP_ROOT} ..."

# Directories that must be writable by www-data
WRITABLE_DIRS=(
    "${APP_ROOT}/storage"
    "${APP_ROOT}/bootstrap/cache"
)

for dir in "${WRITABLE_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        chown -R www-data:www-data "$dir"
        chmod -R ug+rwX "$dir"
        echo "  Fixed: $dir"
    else
        echo "  WARNING: $dir does not exist, skipping"
    fi
done

# Report any remaining misowned files (should be empty)
MISOWNED=$(find "${WRITABLE_DIRS[@]}" -not -user www-data 2>/dev/null | wc -l)
if [ "$MISOWNED" -gt 0 ]; then
    echo "  WARNING: ${MISOWNED} file(s) still not owned by www-data:"
    find "${WRITABLE_DIRS[@]}" -not -user www-data 2>/dev/null
else
    echo "All writable directories are correctly owned by www-data."
fi
