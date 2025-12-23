#!/usr/bin/env bash
# Deploy script for PraticaVoice (cloud/on-prem)
# Requirements: run as root/sudo; app user = www-data

set -euo pipefail

APP_USER="www-data"
APP_GROUP="www-data"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${PROJECT_ROOT}/.env"
PULLED=0
CURRENT_COMMIT=""

function log() {
    echo -e "[$(date +'%Y-%m-%d %H:%M:%S')] $*"
}

function require_env() {
    local var="$1"
    if ! grep -E "^${var}=" "${ENV_FILE}" >/dev/null 2>&1; then
        log "❌ Variabile ${var} mancante in .env"
        exit 1
    fi
}

function on_error() {
    log "❌ Errore al deploy (linea $1)."
    if [[ "${PULLED}" -eq 1 ]]; then
        log "↩️  Rollback al commit ${CURRENT_COMMIT}..."
        sudo -u "${APP_USER}" -g "${APP_GROUP}" git reset --hard "${CURRENT_COMMIT}"
        sudo -u "${APP_USER}" -g "${APP_GROUP}" composer install --no-dev --optimize-autoloader
        sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan up || true
        log "✅ Rollback completato."
    fi
    exit 1
}
trap 'on_error $LINENO' ERR

cd "${PROJECT_ROOT}"

log "🔎 Validazione .env"
[[ -f "${ENV_FILE}" ]] || { log "❌ .env non trovato"; exit 1; }
require_env "APP_KEY"
require_env "APP_ENV"
require_env "PRATICAVOICE_MODE"
require_env "DB_CONNECTION"
require_env "DB_DATABASE"

MODE=$(grep -E "^PRATICAVOICE_MODE=" "${ENV_FILE}" | cut -d= -f2-)
if [[ "${MODE}" == "on_prem" ]]; then
    require_env "PDF_BASE_PATH"
fi

log "💾 Salvo commit corrente"
CURRENT_COMMIT=$(git rev-parse HEAD)

log "🛑 Modalità manutenzione"
sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan down || true

log "⬇️  Git pull"
sudo -u "${APP_USER}" -g "${APP_GROUP}" git pull origin main
PULLED=1

log "📦 Composer install (prod)"
sudo -u "${APP_USER}" -g "${APP_GROUP}" composer install --no-dev --optimize-autoloader

log "🗃️  Migrazioni"
sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan migrate --force

log "🧹 Clear cache/config/route/view"
sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan cache:clear
sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan config:clear
sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan route:clear
sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan view:clear

log "⚡ Rigenero cache"
sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan config:cache
sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan route:cache

log "🔒 Permessi storage e cache (best-effort)"

for DIR in storage bootstrap/cache; do
    if mountpoint -q "$DIR"; then
        log "⚠️  $DIR è un mountpoint, skip chown"
        continue
    fi

    chown -R "${APP_USER}:${APP_GROUP}" "$DIR" || log "⚠️  chown non consentito su $DIR"
    find "$DIR" -type d -exec chmod 775 {} \; || true
    find "$DIR" -type f -exec chmod 664 {} \; || true
done

log "✅ App online"
sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan up

log "🔄 Ricarico Apache"
systemctl reload apache2

log "ℹ️  Versione installata:"
sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan tinker --execute="\\App\\Support\\AppVersion::version();"

log "🎉 Deploy completato"
