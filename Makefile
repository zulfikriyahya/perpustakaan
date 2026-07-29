.PHONY: help dev serve build fresh migrate install clear queue-work stop gencode genstructure gen deploy deploy-list deploy-restart deploy-remove

PHP := php
YARN := yarn
ARTISAN := $(PHP) artisan
HOST := 127.0.0.1
PORT := 8000

# Variabel untuk target deploy (isi lewat CLI: make deploy DOMAIN=... PORT=... APP=... ALIAS=...)
DOMAIN ?=
DEPLOY_PORT ?=
APP ?=
ALIAS ?=
DBHOST ?= 192.168.1.100
SSL ?= n

help:
	@echo "Perintah yang tersedia:"
	@echo "  make install        - Install dependency (composer + yarn)"
	@echo "  make dev            - Jalankan Laravel serve + Vite dev server bersamaan"
	@echo "  make serve          - Jalankan Laravel serve saja (127.0.0.1:8000)"
	@echo "  make build          - Build asset frontend untuk production"
	@echo "  make fresh          - Migrate fresh + seed database"
	@echo "  make migrate        - Migrate Database"
	@echo "  make clear          - Clear semua cache Laravel"
	@echo "  make queue-work     - Jalankan queue worker manual"
	@echo "  make stop           - Kill semua proses php artisan serve & vite"
	@echo "  make gencode        - Generate dokumentasi source code (docs/source-code.md)"
	@echo "  make genstructure   - Generate struktur folder project via tree (docs/structure-project.md)"
	@echo "  make gen        - Generate source code + struktur project sekaligus"
	@echo "  make deploy         - Generate & deploy instance baru (interaktif kalau tanpa argumen)"
	@echo "                       contoh: make deploy DOMAIN=perpustakaan-baru.zedlabs.id DEPLOY_PORT=8006 APP=\"SMP Baru\" ALIAS=smpbaru"
	@echo "  make deploy-list    - Lihat status semua instance perpustakaan di supervisor"
	@echo "  make deploy-restart DOMAIN=... - Restart worker+octane satu instance"
	@echo "  make deploy-remove DOMAIN=...  - Hapus config nginx+supervisor satu instance"

install:
	composer install
	$(YARN) install

dev:
	@echo "Menjalankan Laravel serve dan Vite dev server..."
	@$(MAKE) -j2 serve vite-dev

serve:
	$(ARTISAN) serve --host=$(HOST) --port=$(PORT)

vite-dev:
	$(YARN) dev

build:
	$(YARN) build

fresh:
	$(ARTISAN) migrate:fresh --seed

migrate:
	$(ARTISAN) migrate

clear:
	$(ARTISAN) config:clear
	$(ARTISAN) cache:clear
	$(ARTISAN) view:clear
	$(ARTISAN) route:clear

queue-work:
	$(ARTISAN) queue:work redis --queue=whatsapp,default --tries=3 --timeout=60

stop:
	@pkill -f "php artisan serve" || true
	@pkill -f "vite" || true
	@echo "Semua proses dev dihentikan."

gencode:
	@mkdir -p docs
	@./scripts/generate-docs.sh code

genstructure:
	@mkdir -p docs
	@if ! command -v tree >/dev/null 2>&1; then \
		echo "'tree' tidak ditemukan. Install dulu: sudo apt install tree (Debian/Ubuntu) atau brew install tree (macOS)."; \
		exit 1; \
	fi
	@tree -a -n --dirsfirst -I "node_modules|vendor|storage|scripts|.git|docs|public|bootstrap/cache" -o docs/structure-project.md
	@echo "docs/structure-project.md selesai dibuat."

gen: gencode genstructure
	@echo "✅ Semua dokumentasi berhasil digenerate di folder docs/"

deploy:
ifeq ($(strip $(DOMAIN)),)
	@sudo ./scripts/deploy-perpustakaan.sh
else
	@sudo ./scripts/deploy-perpustakaan.sh -d "$(DOMAIN)" -p "$(DEPLOY_PORT)" -n "$(APP)" -a "$(ALIAS)" -h "$(DBHOST)" -s "$(SSL)"
endif

deploy-list:
	@sudo supervisorctl status | grep -E "whatsapp|heavy|octane" || echo "Tidak ada instance perpustakaan terdaftar."

deploy-restart:
ifeq ($(strip $(DOMAIN)),)
	@echo "Gunakan: make deploy-restart DOMAIN=perpustakaan-namadomain.zedlabs.id"
else
	@sudo supervisorctl restart "$(subst .,-,$(DOMAIN))-whatsapp:*" "$(subst .,-,$(DOMAIN))-heavy:*" "$(subst .,-,$(DOMAIN))-octane"
endif

deploy-remove:
ifeq ($(strip $(DOMAIN)),)
	@echo "Gunakan: make deploy-remove DOMAIN=perpustakaan-namadomain.zedlabs.id"
else
	@echo "Menghapus config untuk $(DOMAIN)..."
	@sudo supervisorctl stop "$(subst .,-,$(DOMAIN))-whatsapp:*" "$(subst .,-,$(DOMAIN))-heavy:*" "$(subst .,-,$(DOMAIN))-octane" || true
	@sudo rm -f /etc/supervisor/conf.d/$(DOMAIN).conf
	@sudo rm -f /etc/nginx/sites-enabled/$(DOMAIN)
	@sudo rm -f /etc/nginx/sites-available/$(DOMAIN)
	@sudo supervisorctl reread
	@sudo supervisorctl update
	@sudo nginx -t && sudo systemctl reload nginx
	@echo "✅ Config $(DOMAIN) sudah dihapus."
endif
