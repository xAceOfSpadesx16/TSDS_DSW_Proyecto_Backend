# Makefile — DSW-Backend (host-only)
#
# Esta versión NO usa Docker: el backend corre directamente sobre el
# PHP 8.4 + Composer del host. Cuando se reactive Docker, restaurar
# los targets basados en `docker compose exec`.
#
# Convenciones:
#   - Todos los targets que invocan artisan pasan por la variable
#     `cmd`, permitiendo overrides sin tocar el Makefile.
#   - `serve` arranca el servidor en background con logs en
#     storage/logs/serve.log (compatible con kill por nombre).

PHP      := php
COMPOSER := composer
ARTISAN  := $(PHP) artisan

# --- Gestión de dependencias ------------------------------------------------

install:
	$(COMPOSER) install

update:
	$(COMPOSER) update

# --- Base de datos ----------------------------------------------------------

migrate:
	$(ARTISAN) migrate

migrate-fresh:
	$(ARTISAN) migrate:fresh

seed:
	$(ARTISAN) db:seed

# --- Suite de tests ---------------------------------------------------------

test:
	$(ARTISAN) test

test-coverage:
	$(ARTISAN) test --coverage

# --- Servidor de desarrollo -------------------------------------------------

# Arranca `php artisan serve` en background (no bloquea la terminal).
# El PID se guarda en storage/app/serve.pid para poder matarlo desde
# `make stop-serve`. Los logs caen en storage/logs/serve.log.
SERVE_LOG   := storage/logs/serve.log
SERVE_PID   := storage/app/serve.pid
SERVE_HOST  := 127.0.0.1
SERVE_PORT  := 8000

serve:
	@mkdir -p storage/logs storage/app
	@if [ -f $(SERVE_PID) ] && kill -0 $$(cat $(SERVE_PID)) 2>/dev/null; then \
		echo "Servidor ya corriendo (PID $$(cat $(SERVE_PID))). Usá make stop-serve primero."; \
		exit 1; \
	fi
	@nohup $(ARTISAN) serve --host=$(SERVE_HOST) --port=$(SERVE_PORT) > $(SERVE_LOG) 2>&1 & \
		echo $$! > $(SERVE_PID)
	@echo "Servidor arrancado en http://$(SERVE_HOST):$(SERVE_PORT) (PID $$(cat $(SERVE_PID))). Log: $(SERVE_LOG)"

stop-serve:
	@if [ -f $(SERVE_PID) ] && kill -0 $$(cat $(SERVE_PID)) 2>/dev/null; then \
		kill $$(cat $(SERVE_PID)) && rm -f $(SERVE_PID); \
		echo "Servidor detenido."; \
	else \
		echo "No hay servidor en background (PID file inexistente o proceso muerto)."; \
		rm -f $(SERVE_PID); \
	fi

# --- Utilidades -------------------------------------------------------------

# Wrapper para correr cualquier comando artisan: make artisan cmd="route:list"
artisan:
	$(ARTISAN) $(cmd)

# Atajo para listar todas las rutas
routes:
	$(ARTISAN) route:list

# Limpiar caches (útil tras cambios de config o .env)
clear:
	$(ARTISAN) config:clear
	$(ARTISAN) cache:clear
	$(ARTISAN) route:clear

# --- Default ----------------------------------------------------------------
.PHONY: install update migrate migrate-fresh seed test test-coverage \
        serve stop-serve artisan routes clear

.DEFAULT_GOAL := help

help:
	@echo "Targets disponibles:"
	@echo "  install          composer install"
	@echo "  update           composer update"
	@echo "  migrate          php artisan migrate"
	@echo "  migrate-fresh    php artisan migrate:fresh (BORRA DATOS)"
	@echo "  seed             php artisan db:seed"
	@echo "  test             php artisan test (usa SQLite :memory:)"
	@echo "  test-coverage    php artisan test --coverage"
	@echo "  serve            Arranca php artisan serve en background"
	@echo "  stop-serve       Detiene el servidor en background"
	@echo "  artisan          Wrapper: make artisan cmd=\"route:list\""
	@echo "  routes           Lista todas las rutas"
	@echo "  clear            Limpia config/cache/routes"
