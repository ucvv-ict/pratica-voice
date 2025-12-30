#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="/var/www/praticavoice"
HEARTBEAT_DIR="/var/run/praticavoice"
HEARTBEAT_FILE="${HEARTBEAT_DIR}/queue.last_seen"

mkdir -p "${HEARTBEAT_DIR}"
touch "${HEARTBEAT_FILE}"
chown -R www-data:www-data "${HEARTBEAT_DIR}" || true

heartbeat() {
    while true; do
        touch "${HEARTBEAT_FILE}"
        sleep 60
    done
}

heartbeat &

cd "${PROJECT_ROOT}"
exec php artisan queue:work database --sleep=3 --tries=3 --timeout=120
