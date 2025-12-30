#!/usr/bin/env bash
# First install script for PraticaVoice (cloud/on-prem)
# Requirements: run as root/sudo; app user = www-data; Ubuntu 24.04 + Apache

set -euo pipefail

APP_USER="www-data"
APP_GROUP="www-data"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${PROJECT_ROOT}/.env"

function log() {
    echo -e "[$(date +'%Y-%m-%d %H:%M:%S')] $*"
}

function check_cmd() {
    command -v "$1" >/dev/null 2>&1 || { log "❌ Comando richiesto mancante: $1"; exit 1; }
}

function check_php_version() {
    php -r 'exit((int) (version_compare(PHP_VERSION, "8.3.0", "<")));' || { log "❌ PHP >= 8.3 richiesto"; exit 1; }
}

cd "${PROJECT_ROOT}"

log "🔎 Verifiche prerequisiti"
check_cmd php
check_cmd composer
check_cmd git
check_cmd systemctl
check_php_version

if ! systemctl is-active --quiet apache2; then
    log "❌ Apache non attivo (apache2). Avviare Apache e riprovare."
    exit 1
fi

log "📝 Controllo .env"
if [[ ! -f "${ENV_FILE}" ]]; then
    log "📄 .env mancante, copio da .env.example"
    cp .env.example .env
    chown "${APP_USER}:${APP_GROUP}" .env
    log "🔑 Genero APP_KEY"
    sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan key:generate
else
    log "ℹ️  .env già presente: verificare APP_KEY e variabili tenant/PDF"
fi

log "📦 Composer install (dev)"
sudo -u "${APP_USER}" -g "${APP_GROUP}" composer install

log "🗄️  Migrazioni (incluso queue se già definita)"
sudo -u "${APP_USER}" -g "${APP_GROUP}" php artisan migrate

log "📂 Creo directory runtime /var/run/praticavoice"
install -d -o "${APP_USER}" -g "${APP_GROUP}" /var/run/praticavoice

log "⚙️  Installo servizio systemd praticavoice-queue"
chmod +x "${PROJECT_ROOT}/scripts/queue-worker.sh"
cat >/etc/systemd/system/praticavoice-queue.service <<'EOF'
[Unit]
Description=PraticaVoice Queue Worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
ExecStart=/usr/bin/env bash -lc '/var/www/praticavoice/scripts/queue-worker.sh'
StandardOutput=append:/var/log/praticavoice-queue.log
StandardError=append:/var/log/praticavoice-queue.log

[Install]
WantedBy=multi-user.target
EOF

log "🔄 Ricarico systemd e avvio worker"
systemctl daemon-reload
systemctl enable praticavoice-queue
systemctl start praticavoice-queue

log "🔒 Permessi storage e cache"
chown -R "${APP_USER}:${APP_GROUP}" storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;


log "🧵 Configurazione Queue (systemd)"

QUEUE_SERVICE="/etc/systemd/system/praticavoice-queue.service"
RUNTIME_DIR="/var/run/praticavoice"
QUEUE_LOG="/var/log/praticavoice-queue.log"

log "📁 Creo directory runtime ${RUNTIME_DIR}"
mkdir -p "${RUNTIME_DIR}"
chown "${APP_USER}:${APP_GROUP}" "${RUNTIME_DIR}"
chmod 755 "${RUNTIME_DIR}"

if [[ ! -f "${QUEUE_SERVICE}" ]]; then
    log "🧩 Creo servizio systemd praticavoice-queue"

    cat > "${QUEUE_SERVICE}" <<EOF
[Unit]
Description=PraticaVoice Queue Worker
After=network.target mysql.service

[Service]
User=${APP_USER}
Group=${APP_GROUP}
Restart=always
RestartSec=5

ExecStart=/usr/bin/php ${PROJECT_ROOT}/artisan queue:work database --sleep=3 --tries=3 --timeout=120

# Heartbeat per InfoSistema
ExecStartPost=/bin/bash -c 'while true; do date +%s > ${RUNTIME_DIR}/queue.last_seen; sleep 30; done'

StandardOutput=append:${QUEUE_LOG}
StandardError=append:${QUEUE_LOG}

[Install]
WantedBy=multi-user.target
EOF

    log "🔄 Ricarico systemd"
    systemctl daemon-reload

    log "🚀 Abilito e avvio praticavoice-queue"
    systemctl enable praticavoice-queue
    systemctl start praticavoice-queue
else
    log "ℹ️  Servizio praticavoice-queue già presente (skip)"
fi

log "✅ Prima installazione completata"
echo
echo "Prossimi passi:"
echo "- Configura PDF_BASE_PATH (se PRATICAVOICE_MODE=on_prem) nel .env"
echo "- Monta il filesystem PDF nel path configurato"
echo "- Esegui ./deploy.sh per futuri aggiornamenti"
