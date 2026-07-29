## 1. perpustakaan-madarulhudapusat.zedlabs.id (port 8001)

**`/etc/nginx/sites-available/perpustakaan-madarulhudapusat.zedlabs.id`**
```nginx
server {
    listen 80;
    server_name perpustakaan-madarulhudapusat.zedlabs.id;
    root /var/www/perpustakaan-madarulhudapusat.zedlabs.id/public;

    location /.well-known/acme-challenge/ {
        root /var/www/perpustakaan-madarulhudapusat.zedlabs.id/public;
    }

    client_max_body_size 20M;

    location ~* \.(jpg|jpeg|gif|png|css|js|ico|svg|woff|woff2|ttf|eot)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public";
        try_files $uri @octane;
    }

    location / {
        try_files $uri @octane;
    }

    location @octane {
        proxy_pass http://127.0.0.1:8001;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
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
    location ~* \.(env|log|sql)$ {
        deny all;
    }
}
```

**`/etc/supervisor/conf.d/perpustakaan-madarulhudapusat.zedlabs.id.conf`**
```ini
; ==========================================
; 1. Worker WhatsApp (Cepat & Ringan)
; ==========================================
[program:perpustakaan-madarulhudapusat-whatsapp]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/perpustakaan-madarulhudapusat.zedlabs.id
command=php artisan queue:work redis --queue=whatsapp --tries=3 --timeout=30 --sleep=1 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan-madarulhudapusat.zedlabs.id/storage/logs/worker-whatsapp.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="www-data",APP_ENV="production"

; ==========================================
; 2. Worker Berat (Laporan PDF)
; ==========================================
[program:perpustakaan-madarulhudapusat-heavy]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/perpustakaan-madarulhudapusat.zedlabs.id
command=php artisan queue:work redis --queue=default --tries=1 --timeout=3600 --memory=1024 --sleep=1 --max-jobs=200 --max-time=7200
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan-madarulhudapusat.zedlabs.id/storage/logs/worker-heavy.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="www-data",APP_ENV="production"

; ==========================================
; 3. Octane Server (FrankenPHP)
; ==========================================
[program:perpustakaan-madarulhudapusat-octane]
process_name=%(program_name)s
directory=/var/www/perpustakaan-madarulhudapusat.zedlabs.id
command=php artisan octane:start --server=frankenphp --host=127.0.0.1 --port=8001 --workers=8 --max-requests=5000
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan-madarulhudapusat.zedlabs.id/storage/logs/octane.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=10
stopsignal=TERM
stopasgroup=true
killasgroup=true
environment=HOME="/var/www",USER="www-data",APP_ENV="production"
```

---

## 2. perpustakaan-mapansa.zedlabs.id (port 8002)

**`/etc/nginx/sites-available/perpustakaan-mapansa.zedlabs.id`**
```nginx
server {
    listen 80;
    server_name perpustakaan-mapansa.zedlabs.id;
    root /var/www/perpustakaan-mapansa.zedlabs.id/public;

    location /.well-known/acme-challenge/ {
        root /var/www/perpustakaan-mapansa.zedlabs.id/public;
    }

    client_max_body_size 20M;

    location ~* \.(jpg|jpeg|gif|png|css|js|ico|svg|woff|woff2|ttf|eot)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public";
        try_files $uri @octane;
    }

    location / {
        try_files $uri @octane;
    }

    location @octane {
        proxy_pass http://127.0.0.1:8002;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
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
    location ~* \.(env|log|sql)$ {
        deny all;
    }
}
```

**`/etc/supervisor/conf.d/perpustakaan-mapansa.zedlabs.id.conf`**

> Catatan: file supervisor untuk `perpustakaan-mapansa` sudah ada sebelumnya dan memakai port **8001** untuk octane. Sesuai daftar port terbaru kamu, domain ini sekarang seharusnya pakai **8002** — jadi ini update/replace dari conf lama supaya konsisten dengan nginx di atas.

```ini
; ==========================================
; 1. Worker WhatsApp (Cepat & Ringan)
; ==========================================
[program:perpustakaan-mapansa-whatsapp]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/perpustakaan-mapansa.zedlabs.id
command=php artisan queue:work redis --queue=whatsapp --tries=3 --timeout=30 --sleep=1 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan-mapansa.zedlabs.id/storage/logs/worker-whatsapp.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="www-data",APP_ENV="production"

; ==========================================
; 2. Worker Berat (Laporan PDF)
; ==========================================
[program:perpustakaan-mapansa-heavy]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/perpustakaan-mapansa.zedlabs.id
command=php artisan queue:work redis --queue=default --tries=1 --timeout=3600 --memory=1024 --sleep=1 --max-jobs=200 --max-time=7200
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan-mapansa.zedlabs.id/storage/logs/worker-heavy.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="www-data",APP_ENV="production"

; ==========================================
; 3. Octane Server (FrankenPHP)
; ==========================================
[program:perpustakaan-mapansa-octane]
process_name=%(program_name)s
directory=/var/www/perpustakaan-mapansa.zedlabs.id
command=php artisan octane:start --server=frankenphp --host=127.0.0.1 --port=8002 --workers=8 --max-requests=5000
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan-mapansa.zedlabs.id/storage/logs/octane.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=10
stopsignal=TERM
stopasgroup=true
killasgroup=true
environment=HOME="/var/www",USER="www-data",APP_ENV="production"
```

---

## 3. perpustakaan-mtsdahuci.zedlabs.id (port 8003)

**`/etc/nginx/sites-available/perpustakaan-mtsdahuci.zedlabs.id`**
```nginx
server {
    listen 80;
    server_name perpustakaan-mtsdahuci.zedlabs.id;
    root /var/www/perpustakaan-mtsdahuci.zedlabs.id/public;

    location /.well-known/acme-challenge/ {
        root /var/www/perpustakaan-mtsdahuci.zedlabs.id/public;
    }

    client_max_body_size 20M;

    location ~* \.(jpg|jpeg|gif|png|css|js|ico|svg|woff|woff2|ttf|eot)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public";
        try_files $uri @octane;
    }

    location / {
        try_files $uri @octane;
    }

    location @octane {
        proxy_pass http://127.0.0.1:8003;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
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
    location ~* \.(env|log|sql)$ {
        deny all;
    }
}
```

**`/etc/supervisor/conf.d/perpustakaan-mtsdahuci.zedlabs.id.conf`**
```ini
; ==========================================
; 1. Worker WhatsApp (Cepat & Ringan)
; ==========================================
[program:perpustakaan-mtsdahuci-whatsapp]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/perpustakaan-mtsdahuci.zedlabs.id
command=php artisan queue:work redis --queue=whatsapp --tries=3 --timeout=30 --sleep=1 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan-mtsdahuci.zedlabs.id/storage/logs/worker-whatsapp.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="www-data",APP_ENV="production"

; ==========================================
; 2. Worker Berat (Laporan PDF)
; ==========================================
[program:perpustakaan-mtsdahuci-heavy]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/perpustakaan-mtsdahuci.zedlabs.id
command=php artisan queue:work redis --queue=default --tries=1 --timeout=3600 --memory=1024 --sleep=1 --max-jobs=200 --max-time=7200
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan-mtsdahuci.zedlabs.id/storage/logs/worker-heavy.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="www-data",APP_ENV="production"

; ==========================================
; 3. Octane Server (FrankenPHP)
; ==========================================
[program:perpustakaan-mtsdahuci-octane]
process_name=%(program_name)s
directory=/var/www/perpustakaan-mtsdahuci.zedlabs.id
command=php artisan octane:start --server=frankenphp --host=127.0.0.1 --port=8003 --workers=8 --max-requests=5000
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan-mtsdahuci.zedlabs.id/storage/logs/octane.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=10
stopsignal=TERM
stopasgroup=true
killasgroup=true
environment=HOME="/var/www",USER="www-data",APP_ENV="production"
```

---

## 4. perpustakaan.mtsdarulhudapusat.sch.id (port 8004)

⚠️ Ini domain yang sama dengan file `.env` yang kamu lampirkan (`.sch.id`), dan sudah ada nginx+supervisor conf existing memakai port **8001** dengan SSL aktif. Kamu minta ulang tanpa SSL di port **8004** — ini akan **menggantikan** setup existing. Pastikan itu memang yang kamu mau sebelum apply, karena kalau SSL cert sudah ada & aktif, drop ke port lain berarti perlu update juga `OCTANE_PORT` di `.env` (sekarang `8002`, harus diubah ke `8004`).

**`/etc/nginx/sites-available/perpustakaan.mtsdarulhudapusat.sch.id`**
```nginx
server {
    listen 80;
    server_name perpustakaan.mtsdarulhudapusat.sch.id;
    root /var/www/perpustakaan.mtsdarulhudapusat.sch.id/public;

    location /.well-known/acme-challenge/ {
        root /var/www/perpustakaan.mtsdarulhudapusat.sch.id/public;
    }

    client_max_body_size 20M;

    location ~* \.(jpg|jpeg|gif|png|css|js|ico|svg|woff|woff2|ttf|eot)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public";
        try_files $uri @octane;
    }

    location / {
        try_files $uri @octane;
    }

    location @octane {
        proxy_pass http://127.0.0.1:8004;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
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
    location ~* \.(env|log|sql)$ {
        deny all;
    }
}
```

**`/etc/supervisor/conf.d/perpustakaan.mtsdarulhudapusat.sch.id.conf`**
```ini
; ==========================================
; 1. Worker WhatsApp (Cepat & Ringan)
; ==========================================
[program:perpustakaan-mtsdarulhudapusat-whatsapp]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/perpustakaan.mtsdarulhudapusat.sch.id
command=php artisan queue:work redis --queue=whatsapp --tries=3 --timeout=30 --sleep=1 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan.mtsdarulhudapusat.sch.id/storage/logs/worker-whatsapp.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="www-data",APP_ENV="production"

; ==========================================
; 2. Worker Berat (Laporan PDF)
; ==========================================
[program:perpustakaan-mtsdarulhudapusat-heavy]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/perpustakaan.mtsdarulhudapusat.sch.id
command=php artisan queue:work redis --queue=default --tries=1 --timeout=3600 --memory=1024 --sleep=1 --max-jobs=200 --max-time=7200
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan.mtsdarulhudapusat.sch.id/storage/logs/worker-heavy.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="www-data",APP_ENV="production"

; ==========================================
; 3. Octane Server (FrankenPHP)
; ==========================================
[program:perpustakaan-mtsdarulhudapusat-octane]
process_name=%(program_name)s
directory=/var/www/perpustakaan.mtsdarulhudapusat.sch.id
command=php artisan octane:start --server=frankenphp --host=127.0.0.1 --port=8004 --workers=8 --max-requests=5000
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan.mtsdarulhudapusat.sch.id/storage/logs/octane.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=10
stopsignal=TERM
stopasgroup=true
killasgroup=true
environment=HOME="/var/www",USER="www-data",APP_ENV="production"
```

---

## 5. perpustakaan.mtsn1pandeglang.sch.id (port 8005)

**`/etc/nginx/sites-available/perpustakaan.mtsn1pandeglang.sch.id`**
```nginx
server {
    listen 80;
    server_name perpustakaan.mtsn1pandeglang.sch.id;
    root /var/www/perpustakaan.mtsn1pandeglang.sch.id/public;

    location /.well-known/acme-challenge/ {
        root /var/www/perpustakaan.mtsn1pandeglang.sch.id/public;
    }

    client_max_body_size 20M;

    location ~* \.(jpg|jpeg|gif|png|css|js|ico|svg|woff|woff2|ttf|eot)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public";
        try_files $uri @octane;
    }

    location / {
        try_files $uri @octane;
    }

    location @octane {
        proxy_pass http://127.0.0.1:8005;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
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
    location ~* \.(env|log|sql)$ {
        deny all;
    }
}
```

**`/etc/supervisor/conf.d/perpustakaan.mtsn1pandeglang.sch.id.conf`**
```ini
; ==========================================
; 1. Worker WhatsApp (Cepat & Ringan)
; ==========================================
[program:perpustakaan-mtsn1pandeglang-whatsapp]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/perpustakaan.mtsn1pandeglang.sch.id
command=php artisan queue:work redis --queue=whatsapp --tries=3 --timeout=30 --sleep=1 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan.mtsn1pandeglang.sch.id/storage/logs/worker-whatsapp.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="www-data",APP_ENV="production"

; ==========================================
; 2. Worker Berat (Laporan PDF)
; ==========================================
[program:perpustakaan-mtsn1pandeglang-heavy]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/perpustakaan.mtsn1pandeglang.sch.id
command=php artisan queue:work redis --queue=default --tries=1 --timeout=3600 --memory=1024 --sleep=1 --max-jobs=200 --max-time=7200
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan.mtsn1pandeglang.sch.id/storage/logs/worker-heavy.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
environment=HOME="/var/www",USER="www-data",APP_ENV="production"

; ==========================================
; 3. Octane Server (FrankenPHP)
; ==========================================
[program:perpustakaan-mtsn1pandeglang-octane]
process_name=%(program_name)s
directory=/var/www/perpustakaan.mtsn1pandeglang.sch.id
command=php artisan octane:start --server=frankenphp --host=127.0.0.1 --port=8005 --workers=8 --max-requests=5000
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan.mtsn1pandeglang.sch.id/storage/logs/octane.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=10
stopsignal=TERM
stopasgroup=true
killasgroup=true
environment=HOME="/var/www",USER="www-data",APP_ENV="production"
```

---

## Langkah aktivasi

```bash
# nginx
sudo ln -s /etc/nginx/sites-available/* /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

---

Oke, berarti kelima project pakai **kredensial WhatsApp gateway yang sama persis** (bukan API key per-sekolah seperti sebelumnya). Berikut versi final — hanya beda `APP_NAME`, `APP_URL`, `DB_*`, `REDIS_*`, dan `OCTANE_PORT`; blok WhatsApp identik di semua.

## Ringkasan perbedaan per domain

| Domain | Port | DB_DATABASE / DB_USERNAME | REDIS_DB/CACHE/QUEUE | REDIS_PREFIX |
|---|---|---|---|---|
| perpustakaan-madarulhudapusat.zedlabs.id | 8001 | perpustakaan_madhpusat | 9/10/11 | perpustakaan-madhpusat_ |
| perpustakaan-mapansa.zedlabs.id | 8002 | perpustakaan_mapansa | 3/4/5 | perpustakaan-mapansa_ |
| perpustakaan-mtsdahuci.zedlabs.id | 8003 | perpustakaan_mtsdahuci | 12/13/14 | perpustakaan-mtsdahuci_ |
| perpustakaan.mtsdarulhudapusat.sch.id | 8004 | perpustakaan_dhpusat | 6/7/8 | perpustakaan-dhpusat_ |
| perpustakaan.mtsn1pandeglang.sch.id | 8005 | perpustakaan_mtsn1pandeglang | 0/1/2 | perpustakaan-mtsn1pandeglang_ |

Blok WhatsApp yang **sama untuk semua**:
```env
WHATSAPP_BASE_URL=https://whatsapp.zedlabs.id
WHATSAPP_API_KEY_ID=key_qwoxn54gozprmrfegaepyby624
WHATSAPP_API_SECRET=2Q2FJKEEUEIFJRTS7BNQCKYUJR5PRWKRXPOAPBIO5XQ2HIF3JUZQ
WHATSAPP_TIMEOUT=15
```

---

## 1. perpustakaan-madarulhudapusat.zedlabs.id (.env)

```env
# ============================================================
# APPLIKASI — WAJIB tetap di .env
# ============================================================
APP_NAME="MA Darul Huda Pusat Pandeglang"
APP_ENV=production
APP_KEY=base64:tq195OP9qKzlMKBDz//1zzM1o59R9GGgjI2kR99aaSA=
APP_DEBUG=false
APP_URL=https://perpustakaan-madarulhudapusat.zedlabs.id
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
# DATABASE — WAJIB tetap di .env
# ============================================================
DB_CONNECTION=mysql
DB_HOST=192.168.1.100
DB_PORT=3306
DB_DATABASE=perpustakaan_madhpusat
DB_USERNAME=perpustakaan_madhpusat
DB_PASSWORD=18012000
DB_PERSISTENT=false

# ============================================================
# REDIS — WAJIB tetap di .env
# ============================================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=9
REDIS_CACHE_DB=10
REDIS_QUEUE_DB=11
REDIS_PREFIX=perpustakaan-madhpusat_

# ============================================================
# CACHE, SESSION, QUEUE — WAJIB tetap di .env
# ============================================================
CACHE_STORE=redis
CACHE_PREFIX=perpustakaan-madhpusat_
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
# API SECRET & BIAYA KARTU — fallback saja, atur via Panel
# ============================================================
API_SECRET=P@ndegl@ng_14012000*
BIAYA_KARTU=15000

VITE_APP_NAME="${APP_NAME}"

# ============================================================
# WHATSAPP GATEWAY — fallback saja, atur via Panel
# ============================================================
WHATSAPP_BASE_URL=https://whatsapp.zedlabs.id
WHATSAPP_API_KEY_ID=key_qwoxn54gozprmrfegaepyby624
WHATSAPP_API_SECRET=2Q2FJKEEUEIFJRTS7BNQCKYUJR5PRWKRXPOAPBIO5XQ2HIF3JUZQ
WHATSAPP_TIMEOUT=15

# ============================================================
# OCTANE / FRANKENPHP — WAJIB tetap di .env
# ============================================================
OCTANE_SERVER=frankenphp
OCTANE_HOST=127.0.0.1
OCTANE_PORT=8001
OCTANE_HTTPS=false
OCTANE_WORKERS=8
FRANKENPHP_NUM_THREADS=8
OCTANE_MAX_REQUESTS=5000
FRANKENPHP_MAX_REQUESTS=5000
OCTANE_GC_ENABLED=true
OCTANE_GC_INTERVAL=1000
OCTANE_MAX_EXECUTION_TIME=30

# ============================================================
# PHP / OPCACHE — WAJIB tetap di .env
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
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 2. perpustakaan-mapansa.zedlabs.id (.env)

```env
# ============================================================
# APPLIKASI — WAJIB tetap di .env
# ============================================================
APP_NAME="MA Negeri 1 Pandeglang"
APP_ENV=production
APP_KEY=base64:cXXhFtExwE7no2oEJfA2+b6AZYiWZwB/rBmE6v/PE/k=
APP_DEBUG=false
APP_URL=https://perpustakaan-mapansa.zedlabs.id
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
# DATABASE — WAJIB tetap di .env
# ============================================================
DB_CONNECTION=mysql
DB_HOST=192.168.1.100
DB_PORT=3306
DB_DATABASE=perpustakaan_mapansa
DB_USERNAME=perpustakaan_mapansa
DB_PASSWORD=18012000
DB_PERSISTENT=false

# ============================================================
# REDIS — WAJIB tetap di .env
# ============================================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=3
REDIS_CACHE_DB=4
REDIS_QUEUE_DB=5
REDIS_PREFIX=perpustakaan-mapansa_

# ============================================================
# CACHE, SESSION, QUEUE — WAJIB tetap di .env
# ============================================================
CACHE_STORE=redis
CACHE_PREFIX=perpustakaan-mapansa_
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
# API SECRET & BIAYA KARTU — fallback saja, atur via Panel
# ============================================================
API_SECRET=P@ndegl@ng_14012000*
BIAYA_KARTU=15000

VITE_APP_NAME="${APP_NAME}"

# ============================================================
# WHATSAPP GATEWAY — fallback saja, atur via Panel
# ============================================================
WHATSAPP_BASE_URL=https://whatsapp.zedlabs.id
WHATSAPP_API_KEY_ID=key_qwoxn54gozprmrfegaepyby624
WHATSAPP_API_SECRET=2Q2FJKEEUEIFJRTS7BNQCKYUJR5PRWKRXPOAPBIO5XQ2HIF3JUZQ
WHATSAPP_TIMEOUT=15

# ============================================================
# OCTANE / FRANKENPHP — WAJIB tetap di .env
# ============================================================
OCTANE_SERVER=frankenphp
OCTANE_HOST=127.0.0.1
OCTANE_PORT=8002
OCTANE_HTTPS=false
OCTANE_WORKERS=8
FRANKENPHP_NUM_THREADS=8
OCTANE_MAX_REQUESTS=5000
FRANKENPHP_MAX_REQUESTS=5000
OCTANE_GC_ENABLED=true
OCTANE_GC_INTERVAL=1000
OCTANE_MAX_EXECUTION_TIME=30

# ============================================================
# PHP / OPCACHE — WAJIB tetap di .env
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
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 3. perpustakaan-mtsdahuci.zedlabs.id (.env)

```env
# ============================================================
# APPLIKASI — WAJIB tetap di .env
# ============================================================
APP_NAME="MTs Darul Huda Cikoneng Pandeglang"
APP_ENV=production
APP_KEY=base64:ALLSeV5AU5cDvtRXibPPGjmU0w/J7XhjdZRuoScalPc=
APP_DEBUG=false
APP_URL=https://perpustakaan-mtsdahuci.zedlabs.id
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
# DATABASE — WAJIB tetap di .env
# ============================================================
DB_CONNECTION=mysql
DB_HOST=192.168.1.100
DB_PORT=3306
DB_DATABASE=perpustakaan_mtsdahuci
DB_USERNAME=perpustakaan_mtsdahuci
DB_PASSWORD=18012000
DB_PERSISTENT=false

# ============================================================
# REDIS — WAJIB tetap di .env
# ============================================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=12
REDIS_CACHE_DB=13
REDIS_QUEUE_DB=14
REDIS_PREFIX=perpustakaan-mtsdahuci_

# ============================================================
# CACHE, SESSION, QUEUE — WAJIB tetap di .env
# ============================================================
CACHE_STORE=redis
CACHE_PREFIX=perpustakaan-mtsdahuci_
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
# API SECRET & BIAYA KARTU — fallback saja, atur via Panel
# ============================================================
API_SECRET=P@ndegl@ng_14012000*
BIAYA_KARTU=15000

VITE_APP_NAME="${APP_NAME}"

# ============================================================
# WHATSAPP GATEWAY — fallback saja, atur via Panel
# ============================================================
WHATSAPP_BASE_URL=https://whatsapp.zedlabs.id
WHATSAPP_API_KEY_ID=key_qwoxn54gozprmrfegaepyby624
WHATSAPP_API_SECRET=2Q2FJKEEUEIFJRTS7BNQCKYUJR5PRWKRXPOAPBIO5XQ2HIF3JUZQ
WHATSAPP_TIMEOUT=15

# ============================================================
# OCTANE / FRANKENPHP — WAJIB tetap di .env
# ============================================================
OCTANE_SERVER=frankenphp
OCTANE_HOST=127.0.0.1
OCTANE_PORT=8003
OCTANE_HTTPS=false
OCTANE_WORKERS=8
FRANKENPHP_NUM_THREADS=8
OCTANE_MAX_REQUESTS=5000
FRANKENPHP_MAX_REQUESTS=5000
OCTANE_GC_ENABLED=true
OCTANE_GC_INTERVAL=1000
OCTANE_MAX_EXECUTION_TIME=30

# ============================================================
# PHP / OPCACHE — WAJIB tetap di .env
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
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 4. perpustakaan.mtsdarulhudapusat.sch.id (.env) — sudah benar, tidak berubah

```env
# ============================================================
# APPLIKASI — WAJIB tetap di .env
# ============================================================
APP_NAME="MTs Darul Huda Pusat Pandeglang"
APP_ENV=production
APP_KEY=base64:y+0Sda9E8S1yH6xO2URb6qhVspSd20c1yuDOoiAWZ8I=
APP_DEBUG=false
APP_URL=https://perpustakaan.mtsdarulhudapusat.sch.id
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
# DATABASE — WAJIB tetap di .env
# ============================================================
DB_CONNECTION=mysql
DB_HOST=192.168.1.100
DB_PORT=3306
DB_DATABASE=perpustakaan_dhpusat
DB_USERNAME=perpustakaan_dhpusat
DB_PASSWORD=18012000
DB_PERSISTENT=false

# ============================================================
# REDIS — WAJIB tetap di .env
# ============================================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=6
REDIS_CACHE_DB=7
REDIS_QUEUE_DB=8
REDIS_PREFIX=perpustakaan-dhpusat_

# ============================================================
# CACHE, SESSION, QUEUE — WAJIB tetap di .env
# ============================================================
CACHE_STORE=redis
CACHE_PREFIX=perpustakaan-dhpusat_
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
# API SECRET & BIAYA KARTU — fallback saja, atur via Panel
# ============================================================
API_SECRET=P@ndegl@ng_14012000*
BIAYA_KARTU=15000

VITE_APP_NAME="${APP_NAME}"

# ============================================================
# WHATSAPP GATEWAY — fallback saja, atur via Panel
# ============================================================
WHATSAPP_BASE_URL=https://whatsapp.zedlabs.id
WHATSAPP_API_KEY_ID=key_qwoxn54gozprmrfegaepyby624
WHATSAPP_API_SECRET=2Q2FJKEEUEIFJRTS7BNQCKYUJR5PRWKRXPOAPBIO5XQ2HIF3JUZQ
WHATSAPP_TIMEOUT=15

# ============================================================
# OCTANE / FRANKENPHP — WAJIB tetap di .env
# ============================================================
OCTANE_SERVER=frankenphp
OCTANE_HOST=127.0.0.1
OCTANE_PORT=8004
OCTANE_HTTPS=false
OCTANE_WORKERS=8
FRANKENPHP_NUM_THREADS=8
OCTANE_MAX_REQUESTS=5000
FRANKENPHP_MAX_REQUESTS=5000
OCTANE_GC_ENABLED=true
OCTANE_GC_INTERVAL=1000
OCTANE_MAX_EXECUTION_TIME=30

# ============================================================
# PHP / OPCACHE — WAJIB tetap di .env
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
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 5. perpustakaan.mtsn1pandeglang.sch.id (.env)

```env
# ============================================================
# APPLIKASI — WAJIB tetap di .env
# ============================================================
APP_NAME="MTs Negeri 1 Pandeglang"
APP_ENV=production
APP_KEY=base64:TyWwaZkZFNUCu2hs8Vfp8K5IUIR1xDS3Oq6ZuOFy7tA=
APP_DEBUG=false
APP_URL=https://perpustakaan.mtsn1pandeglang.sch.id
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
# DATABASE — WAJIB tetap di .env
# ============================================================
DB_CONNECTION=mysql
DB_HOST=192.168.1.100
DB_PORT=3306
DB_DATABASE=perpustakaan_mtsn1pandeglang
DB_USERNAME=perpustakaan_mtsn1pandeglang
DB_PASSWORD=18012000
DB_PERSISTENT=false

# ============================================================
# REDIS — WAJIB tetap di .env
# ============================================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
REDIS_PREFIX=perpustakaan-mtsn1pandeglang_

# ============================================================
# CACHE, SESSION, QUEUE — WAJIB tetap di .env
# ============================================================
CACHE_STORE=redis
CACHE_PREFIX=perpustakaan-mtsn1pandeglang_
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
# API SECRET & BIAYA KARTU — fallback saja, atur via Panel
# ============================================================
API_SECRET=P@ndegl@ng_14012000*
BIAYA_KARTU=15000

VITE_APP_NAME="${APP_NAME}"

# ============================================================
# WHATSAPP GATEWAY — fallback saja, atur via Panel
# ============================================================
WHATSAPP_BASE_URL=https://whatsapp.zedlabs.id
WHATSAPP_API_KEY_ID=key_qwoxn54gozprmrfegaepyby624
WHATSAPP_API_SECRET=2Q2FJKEEUEIFJRTS7BNQCKYUJR5PRWKRXPOAPBIO5XQ2HIF3JUZQ
WHATSAPP_TIMEOUT=15

# ============================================================
# OCTANE / FRANKENPHP — WAJIB tetap di .env
# ============================================================
OCTANE_SERVER=frankenphp
OCTANE_HOST=127.0.0.1
OCTANE_PORT=8005
OCTANE_HTTPS=false
OCTANE_WORKERS=8
FRANKENPHP_NUM_THREADS=8
OCTANE_MAX_REQUESTS=5000
FRANKENPHP_MAX_REQUESTS=5000
OCTANE_GC_ENABLED=true
OCTANE_GC_INTERVAL=1000
OCTANE_MAX_EXECUTION_TIME=30

# ============================================================
# PHP / OPCACHE — WAJIB tetap di .env
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
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Setelah apply ke tiap project

### Versi script:

```bash
#!/bin/bash
set -e
sites=(
  "perpustakaan.mtsdarulhudapusat.sch.id"
  "perpustakaan.mtsn1pandeglang.sch.id"
  "perpustakaan-madarulhudapusat.zedlabs.id"
  "perpustakaan-mapansa.zedlabs.id"
  "perpustakaan-mtsdahuci.zedlabs.id"
)

for site in "${sites[@]}"; do
  cd "/var/www/$site"
  sudo composer update --no-dev --optimize-autoloader
  sudo yarn && sudo yarn build
  sudo php artisan migrate
  sudo php artisan shield:generate --all
  sudo php artisan db:seed --class=ShieldSeeder
  sudo php artisan filament:clear-cached-components
  sudo php artisan optimize:clear
  cd ~
done

sudo supervisorctl restart all
```

### Versi inline:

```bash
for site in perpustakaan.mtsdarulhudapusat.sch.id perpustakaan.mtsn1pandeglang.sch.id perpustakaan-madarulhudapusat.zedlabs.id perpustakaan-mapansa.zedlabs.id perpustakaan-mtsdahuci.zedlabs.id; do cd /var/www/$site && sudo composer update --no-dev --optimize-autoloader && sudo yarn && sudo yarn build && sudo php artisan migrate && sudo php artisan shield:generate --all && sudo php artisan db:seed --class=ShieldSeeder && sudo php artisan filament:clear-cached-components && sudo php artisan optimize:clear && cd ~ || break; done && sudo supervisorctl restart all
```





























curl -X GET "https://perpustakaan.mtsdarulhudapusat.sch.id/api/perpustakaan/rfid-list/version" \
  -H "X-API-KEY: P@ssw0rd_darulhuda"


curl -X POST "https://perpustakaan.mtsdarulhudapusat.sch.id/api/perpustakaan/sync-bulk" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: P@ssw0rd_darulhuda" \
  -d '{
    "data": [
      {
        "rfid": "data dibawah",
        "timestamp": "2026-07-23 08:00:12",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "data dibawah",
        "timestamp": "2026-07-23 08:03:45",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      }
    ]
  }'


untuk perpustakaan hari ini (23 Juli 2026) pukul 06.53 - 07.16 secara acak masing masing


  rfid

  3936160083
  3931772755
  3934818387
  3928366931
  3930921555
  3930283603
  3929912147
  3927958867
  3929038931
  3931293779
  3932138067
  3929417811
  3935114067
  3933909587
  3935875411
  3933567059
  3934494803
  3935347283
  3929419347



curl -X POST "https://perpustakaan.mtsdarulhudapusat.sch.id/api/perpustakaan/sync-bulk" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: P@ssw0rd_darulhuda" \
  -d '{
    "data": [
      {
        "rfid": "3936160083",
        "timestamp": "2026-07-21 06:53:14",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3931772755",
        "timestamp": "2026-07-21 06:54:28",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3934818387",
        "timestamp": "2026-07-21 06:55:09",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3928366931",
        "timestamp": "2026-07-21 06:56:41",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3930921555",
        "timestamp": "2026-07-21 06:58:12",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3930283603",
        "timestamp": "2026-07-21 06:59:33",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929912147",
        "timestamp": "2026-07-21 07:01:05",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3927958867",
        "timestamp": "2026-07-21 07:02:18",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929038931",
        "timestamp": "2026-07-21 07:03:52",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3931293779",
        "timestamp": "2026-07-21 07:05:27",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3932138067",
        "timestamp": "2026-07-21 07:06:44",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929417811",
        "timestamp": "2026-07-21 07:08:19",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3935114067",
        "timestamp": "2026-07-21 07:09:50",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3933909587",
        "timestamp": "2026-07-21 07:11:03",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3935875411",
        "timestamp": "2026-07-21 07:12:36",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3933567059",
        "timestamp": "2026-07-21 07:13:48",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3934494803",
        "timestamp": "2026-07-21 07:14:22",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3935347283",
        "timestamp": "2026-07-21 07:15:11",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929419347",
        "timestamp": "2026-07-21 07:16:04",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      }
    ]
  }'

  curl -X POST "https://perpustakaan.mtsdarulhudapusat.sch.id/api/perpustakaan/sync-bulk" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: P@ssw0rd_darulhuda" \
  -d '{
    "data": [
      {
        "rfid": "3936160083",
        "timestamp": "2026-07-21 14:01:15",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3931772755",
        "timestamp": "2026-07-21 14:02:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3934818387",
        "timestamp": "2026-07-21 14:04:12",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3928366931",
        "timestamp": "2026-07-21 14:05:50",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3930921555",
        "timestamp": "2026-07-21 14:07:03",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3930283603",
        "timestamp": "2026-07-21 14:08:21",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929912147",
        "timestamp": "2026-07-21 14:10:05",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3927958867",
        "timestamp": "2026-07-21 14:11:33",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929038931",
        "timestamp": "2026-07-21 14:13:10",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3931293779",
        "timestamp": "2026-07-21 14:14:45",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3932138067",
        "timestamp": "2026-07-21 14:16:02",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929417811",
        "timestamp": "2026-07-21 14:17:58",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3935114067",
        "timestamp": "2026-07-21 14:19:20",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3933909587",
        "timestamp": "2026-07-21 14:21:09",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3935875411",
        "timestamp": "2026-07-21 14:22:41",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3933567059",
        "timestamp": "2026-07-21 14:24:14",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3934494803",
        "timestamp": "2026-07-21 14:25:30",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3935347283",
        "timestamp": "2026-07-21 14:27:01",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929419347",
        "timestamp": "2026-07-21 14:29:18",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      }
    ]
  }'


---

curl -X POST "https://perpustakaan.mtsdarulhudapusat.sch.id/api/perpustakaan/sync-bulk" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: P@ssw0rd_darulhuda" \
  -d '{
    "data": [
      {
        "rfid": "3936160083",
        "timestamp": "2026-07-18 06:58:12",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3931772755",
        "timestamp": "2026-07-18 07:12:45",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3934818387",
        "timestamp": "2026-07-18 07:01:03",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3928366931",
        "timestamp": "2026-07-18 06:54:29",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3930921555",
        "timestamp": "2026-07-18 07:15:50",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3930283603",
        "timestamp": "2026-07-18 07:06:18",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929912147",
        "timestamp": "2026-07-18 06:56:37",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3927958867",
        "timestamp": "2026-07-18 07:09:11",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929038931",
        "timestamp": "2026-07-18 07:03:24",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3931293779",
        "timestamp": "2026-07-18 07:14:02",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3932138067",
        "timestamp": "2026-07-18 06:53:15",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929417811",
        "timestamp": "2026-07-18 07:08:49",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3935114067",
        "timestamp": "2026-07-18 07:00:33",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3933909587",
        "timestamp": "2026-07-18 07:11:56",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3935875411",
        "timestamp": "2026-07-18 06:57:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3933567059",
        "timestamp": "2026-07-18 07:04:12",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3934494803",
        "timestamp": "2026-07-18 07:16:08",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3935347283",
        "timestamp": "2026-07-18 06:55:04",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "3929419347",
        "timestamp": "2026-07-18 07:10:27",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      }
    ]
  }'

curl -X POST "https://perpustakaan.mtsdarulhudapusat.sch.id/api/perpustakaan/sync-bulk" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: P@ssw0rd_darulhuda" \
  -d '{
    "data": [
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
      {
        "rfid": "DATA_RFID",
        "timestamp": "2026-07-18 14:12:40",
        "device_id": "GERBANG UTAMA",
        "sync_mode": true
      },
    ]
  }'
