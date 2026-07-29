#!/bin/bash
set -euo pipefail

# ============================================================
# Script Generate & Deploy Instance perpustakaan Baru
# Usage:
#   ./deploy-perpustakaan.sh                     -> mode interaktif
#   ./deploy-perpustakaan.sh -d domain -p port -n "Nama Sekolah" -a alias_db -h db_host  -> mode non-interaktif
# ============================================================

# ---------- Warna output ----------
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# ---------- Default value ----------
DOMAIN=""
PORT=""
APP_NAME=""
DB_ALIAS=""
DB_HOST="192.168.1.100"
DB_PASSWORD=""
USE_SSL="n"
WHATSAPP_BASE_URL="https://whatsapp.zedlabs.id"
WHATSAPP_API_KEY_ID="key_qwoxn54gozprmrfegaepyby624"
WHATSAPP_API_SECRET="2Q2FJKEEUEIFJRTS7BNQCKYUJR5PRWKRXPOAPBIO5XQ2HIF3JUZQ"
API_SECRET_DEFAULT="P@ndegl@ng_14012000*"

BASE_DIR="/var/www"
NGINX_DIR="/etc/nginx/sites-available"
NGINX_ENABLED_DIR="/etc/nginx/sites-enabled"
SUPERVISOR_DIR="/etc/supervisor/conf.d"
SYSTEM_USER="www-data"

# ---------- Parse argumen CLI ----------
while getopts "d:p:n:a:h:s:" opt; do
  case $opt in
    d) DOMAIN="$OPTARG" ;;
    p) PORT="$OPTARG" ;;
    n) APP_NAME="$OPTARG" ;;
    a) DB_ALIAS="$OPTARG" ;;
    h) DB_HOST="$OPTARG" ;;
    s) USE_SSL="$OPTARG" ;;
    *) error "Argumen tidak dikenal" ;;
  esac
done

# ---------- Mode interaktif kalau argumen kosong ----------
if [[ -z "$DOMAIN" ]]; then
  read -rp "Domain (misal: perpustakaan-sekolahku.zedlabs.id): " DOMAIN
fi
if [[ -z "$PORT" ]]; then
  read -rp "Port Octane (misal: 8006): " PORT
fi
if [[ -z "$APP_NAME" ]]; then
  read -rp "Nama Aplikasi (misal: MTs Sekolahku): " APP_NAME
fi
if [[ -z "$DB_ALIAS" ]]; then
  read -rp "Alias DB/Redis prefix (misal: sekolahku, huruf kecil tanpa spasi): " DB_ALIAS
fi
if [[ -z "$DB_PASSWORD" ]]; then
  read -rsp "Password DB untuk user $DB_ALIAS: " DB_PASSWORD
  echo ""
fi
read -rp "Pakai SSL? (y/n) [default: n]: " ssl_input
USE_SSL="${ssl_input:-$USE_SSL}"

# ---------- Validasi ----------
[[ -z "$DOMAIN" || -z "$PORT" || -z "$APP_NAME" || -z "$DB_ALIAS" ]] && error "Semua input wajib diisi."
[[ ! "$PORT" =~ ^[0-9]+$ ]] && error "Port harus berupa angka."

PROJECT_DIR="$BASE_DIR/$DOMAIN"
SLUG=$(echo "$DOMAIN" | sed 's/\./-/g')

# Hitung REDIS_DB otomatis berdasarkan port terakhir (port - 8000) * 3, biar tidak bentrok
PORT_OFFSET=$((PORT - 8000))
REDIS_DB=$((PORT_OFFSET * 3))
REDIS_CACHE_DB=$((REDIS_DB + 1))
REDIS_QUEUE_DB=$((REDIS_DB + 2))

info "Ringkasan konfigurasi:"
echo "  Domain       : $DOMAIN"
echo "  Port Octane  : $PORT"
echo "  App Name     : $APP_NAME"
echo "  DB Alias     : $DB_ALIAS"
echo "  DB Host      : $DB_HOST"
echo "  Redis DB     : $REDIS_DB / $REDIS_CACHE_DB / $REDIS_QUEUE_DB"
echo "  SSL          : $USE_SSL"
echo "  Project Dir  : $PROJECT_DIR"
read -rp "Lanjutkan generate config? (y/n): " confirm
[[ "$confirm" != "y" ]] && error "Dibatalkan oleh user."

APP_KEY="base64:$(openssl rand -base64 32)"

# ============================================================
# 1. Generate Nginx Config
# ============================================================
info "Generate nginx config..."
NGINX_CONF="$NGINX_DIR/$DOMAIN"

sudo tee "$NGINX_CONF" > /dev/null <<EOF
server {
    listen 80;
    server_name $DOMAIN;
    root $PROJECT_DIR/public;

    location /.well-known/acme-challenge/ {
        root $PROJECT_DIR/public;
    }

    client_max_body_size 20M;

    location ~* \.(jpg|jpeg|gif|png|css|js|ico|svg|woff|woff2|ttf|eot)\$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public";
        try_files \$uri @octane;
    }

    location / {
        try_files \$uri @octane;
    }

    location @octane {
        proxy_pass http://127.0.0.1:$PORT;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto http;
        proxy_set_header X-Forwarded-Port 80;
        proxy_connect_timeout 60s;
        proxy_send_timeout    60s;
        proxy_read_timeout    60s;
        proxy_buffering off;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
    location ~* \.(env|log|sql)\$ {
        deny all;
    }
}
EOF

info "Nginx config tersimpan di $NGINX_CONF"

# ============================================================
# 2. Generate Supervisor Config
# ============================================================
info "Generate supervisor config..."
SUPERVISOR_CONF="$SUPERVISOR_DIR/$DOMAIN.conf"

sudo tee "$SUPERVISOR_CONF" > /dev/null <<EOF
; ==========================================
; 1. Worker WhatsApp (Cepat & Ringan)
; ==========================================
[program:$SLUG-whatsapp]
process_name=%(program_name)s_%(process_num)02d
directory=$PROJECT_DIR
command=php artisan queue:work redis --queue=whatsapp --tries=3 --timeout=30 --sleep=1 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
user=$SYSTEM_USER
numprocs=1
redirect_stderr=true
stdout_logfile=$PROJECT_DIR/storage/logs/worker-whatsapp.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="$SYSTEM_USER",APP_ENV="production"

; ==========================================
; 2. Worker Berat (Laporan PDF)
; ==========================================
[program:$SLUG-heavy]
process_name=%(program_name)s_%(process_num)02d
directory=$PROJECT_DIR
command=php artisan queue:work redis --queue=default --tries=1 --timeout=3600 --memory=1024 --sleep=1 --max-jobs=200 --max-time=7200
autostart=true
autorestart=true
user=$SYSTEM_USER
numprocs=2
redirect_stderr=true
stdout_logfile=$PROJECT_DIR/storage/logs/worker-heavy.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="$SYSTEM_USER",APP_ENV="production"

; ==========================================
; 3. Octane Server (FrankenPHP)
; ==========================================
[program:$SLUG-octane]
process_name=%(program_name)s
directory=$PROJECT_DIR
command=php artisan octane:start --server=frankenphp --host=127.0.0.1 --port=$PORT --workers=8 --max-requests=5000
autostart=true
autorestart=true
user=$SYSTEM_USER
redirect_stderr=true
stdout_logfile=$PROJECT_DIR/storage/logs/octane.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=10
stopsignal=TERM
stopasgroup=true
killasgroup=true
environment=HOME="/var/www",USER="$SYSTEM_USER",APP_ENV="production"
EOF

info "Supervisor config tersimpan di $SUPERVISOR_CONF"

# ============================================================
# 3. Generate .env (kalau folder project sudah ada)
# ============================================================
APP_URL_SCHEME="http"
[[ "$USE_SSL" == "y" ]] && APP_URL_SCHEME="https"

if [[ -d "$PROJECT_DIR" ]]; then
  info "Generate .env di $PROJECT_DIR..."
  sudo tee "$PROJECT_DIR/.env" > /dev/null <<EOF
# ============================================================
# APPLIKASI
# ============================================================
APP_NAME="$APP_NAME"
APP_ENV=production
APP_KEY=$APP_KEY
APP_DEBUG=false
APP_URL=$APP_URL_SCHEME://$DOMAIN
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID
TIMEZONE="Asia/Jakarta"

# ============================================================
# LOGGING
# ============================================================
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null

# ============================================================
# DATABASE
# ============================================================
DB_CONNECTION=mysql
DB_HOST=$DB_HOST
DB_PORT=3306
DB_DATABASE=perpustakaan_$DB_ALIAS
DB_USERNAME=perpustakaan_$DB_ALIAS
DB_PASSWORD=$DB_PASSWORD
DB_PERSISTENT=false

# ============================================================
# REDIS
# ============================================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=$REDIS_DB
REDIS_CACHE_DB=$REDIS_CACHE_DB
REDIS_QUEUE_DB=$REDIS_QUEUE_DB
REDIS_PREFIX=perpustakaan-$DB_ALIAS_

# ============================================================
# CACHE, SESSION, QUEUE
# ============================================================
CACHE_STORE=redis
CACHE_PREFIX=perpustakaan-${DB_ALIAS}_
SESSION_DRIVER=redis
SESSION_LIFETIME=525600
SESSION_EXPIRE_ON_CLOSE=true
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
QUEUE_CONNECTION=redis
FILESYSTEM_DISK=local

# ============================================================
# LIVEWIRE
# ============================================================
LIVEWIRE_UPLOAD_MAX_FILE_SIZE=102400
LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_SIZE=102400

# ============================================================
# API SECRET & BIAYA KARTU
# ============================================================
API_SECRET=$API_SECRET_DEFAULT
BIAYA_KARTU=15000

VITE_APP_NAME="\${APP_NAME}"

# ============================================================
# WHATSAPP GATEWAY
# ============================================================
WHATSAPP_BASE_URL=$WHATSAPP_BASE_URL
WHATSAPP_API_KEY_ID=$WHATSAPP_API_KEY_ID
WHATSAPP_API_SECRET=$WHATSAPP_API_SECRET
WHATSAPP_TIMEOUT=15

# ============================================================
# OCTANE / FRANKENPHP
# ============================================================
OCTANE_SERVER=frankenphp
OCTANE_HOST=127.0.0.1
OCTANE_PORT=$PORT
OCTANE_HTTPS=false
OCTANE_WORKERS=8
FRANKENPHP_NUM_THREADS=8
OCTANE_MAX_REQUESTS=5000
FRANKENPHP_MAX_REQUESTS=5000
OCTANE_GC_ENABLED=true
OCTANE_GC_INTERVAL=1000
OCTANE_MAX_EXECUTION_TIME=30

# ============================================================
# PHP / OPCACHE
# ============================================================
PHP_MEMORY_LIMIT=512M
PHP_MAX_EXECUTION_TIME=30
PHP_UPLOAD_MAX_SIZE=100M
OPCACHE_ENABLE=1
OPCACHE_MEMORY=512
OPCACHE_MAX_FILES=20000
OPCACHE_VALIDATE_TIMESTAMPS=0

# ============================================================
# MAIL
# ============================================================
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="admin@zedlabs.id"
MAIL_FROM_NAME="\${APP_NAME}"
EOF
  info ".env tersimpan di $PROJECT_DIR/.env"
else
  warn "Folder project $PROJECT_DIR belum ada, .env tidak digenerate. Clone/deploy project dulu ke lokasi ini."
fi

# ============================================================
# 4. Aktivasi Nginx & Supervisor
# ============================================================
read -rp "Aktifkan sekarang (symlink nginx + reload + supervisor)? (y/n): " activate
if [[ "$activate" == "y" ]]; then
  info "Symlink nginx..."
  sudo ln -sf "$NGINX_CONF" "$NGINX_ENABLED_DIR/$DOMAIN"

  info "Test nginx config..."
  sudo nginx -t || error "Nginx config gagal, cek manual sebelum reload."

  sudo systemctl reload nginx
  info "Nginx reloaded."

  info "Reload supervisor..."
  sudo supervisorctl reread
  sudo supervisorctl update
  sudo supervisorctl status | grep "$SLUG" || true

  if [[ -d "$PROJECT_DIR" ]]; then
    info "Menjalankan artisan config:clear & cache:clear..."
    (cd "$PROJECT_DIR" && sudo -u "$SYSTEM_USER" php artisan config:clear && sudo -u "$SYSTEM_USER" php artisan cache:clear) || warn "Gagal jalankan artisan clear, cek manual."
  fi

  info "Deploy selesai untuk $DOMAIN 🎉"
else
  info "Config sudah digenerate, tapi belum diaktifkan. Jalankan manual kalau siap:"
  echo "  sudo ln -sf $NGINX_CONF $NGINX_ENABLED_DIR/$DOMAIN"
  echo "  sudo nginx -t && sudo systemctl reload nginx"
  echo "  sudo supervisorctl reread && sudo supervisorctl update"
fi
