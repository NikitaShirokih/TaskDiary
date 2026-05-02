.PHONY: help init start stop restart build rebuild \
        install migrate fixtures reset \
        stan cs-check cs-fix test lint \
        prod-deploy logs php-shell cache-clear cache-warmup

# ─── Цвета ────────────────────────────────────────────────────────────────────
GREEN  = \033[0;32m
YELLOW = \033[1;33m
RED    = \033[0;31m
BLUE   = \033[0;34m
NC     = \033[0m

# ─── Переменные ───────────────────────────────────────────────────────────────
COMPOSE = docker compose -f .docker/docker-compose.yaml --env-file .env.local
PHP     = $(COMPOSE) exec php
CONSOLE = $(PHP) php bin/console

# ─── Help ─────────────────────────────────────────────────────────────────────
help: ## Показать список доступных команд
	@echo ""
	@echo "$(GREEN)TaskDiary — доступные команды:$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""

# ─── Главные команды ──────────────────────────────────────────────────────────

init: ## Первый запуск проекта с нуля
	@echo ""
	@echo "$(BLUE)Инициализация проекта...$(NC)"
	@echo ""
	@if [ -f .env.local ]; then \
		echo "$(YELLOW).env.local уже существует — пропускаем$(NC)"; \
	else \
		cp .env.local.example .env.local; \
		echo "$(GREEN)✅ .env.local создан из .env.local.example$(NC)"; \
	fi
	@echo "$(YELLOW)Подготавливаем nginx конфиг...$(NC)"
	@mkdir -p .docker/nginx
	@if [ ! -f .docker/nginx/default.conf ]; then \
		printf 'server {\n    listen 80;\n    server_name localhost;\n    root /var/www/html/public;\n\n    location / {\n        try_files $$uri /index.php$$is_args$$args;\n    }\n\n    location ~ ^/index\\.php(/|$$) {\n        fastcgi_pass task_diary_php:9000;\n        fastcgi_split_path_info ^(.+\\.php)(/.*)$$;\n        include fastcgi_params;\n        fastcgi_param SCRIPT_FILENAME $$realpath_root$$fastcgi_script_name;\n        fastcgi_param DOCUMENT_ROOT $$realpath_root;\n        internal;\n    }\n\n    location ~ \\.php$$ {\n        return 404;\n    }\n\n    error_log /var/log/nginx/error.log;\n    access_log /var/log/nginx/access.log;\n}\n' > .docker/nginx/default.conf; \
		echo "$(GREEN)✅ nginx/default.conf создан$(NC)"; \
	else \
		echo "$(YELLOW)nginx/default.conf уже существует — пропускаем$(NC)"; \
	fi
	@echo "$(YELLOW)Собираем контейнеры...$(NC)"
	@$(COMPOSE) up -d --build
	@echo "$(YELLOW)Устанавливаем зависимости...$(NC)"
	@$(PHP) composer install
	@echo "$(YELLOW)Создаём базу данных...$(NC)"
	@$(CONSOLE) doctrine:database:create --if-not-exists
	@echo "$(YELLOW)Выполняем миграции...$(NC)"
	@$(CONSOLE) doctrine:migrations:migrate --no-interaction
	@echo "$(YELLOW)Загружаем фикстуры...$(NC)"
	@$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo ""
	@echo "$(GREEN)✅ Проект готов к работе!$(NC)"
	@echo "$(BLUE)Открывай: http://localhost:8082$(NC)"
	@echo ""

start: ## Запустить проект
	@echo "$(YELLOW)Запускаем проект...$(NC)"
	@$(COMPOSE) up -d
	@echo "$(GREEN)✅ Проект запущен → http://localhost:8082$(NC)"

stop: ## Остановить проект
	@echo "$(YELLOW)Останавливаем проект...$(NC)"
	@$(COMPOSE) down
	@echo "$(GREEN)✅ Проект остановлен$(NC)"

restart: stop start ## Перезапустить проект

rebuild: ## Пересобрать контейнеры и переустановить зависимости
	@echo "$(YELLOW)Пересобираем контейнеры...$(NC)"
	@$(COMPOSE) down
	@$(COMPOSE) up -d --build
	@$(PHP) composer install
	@echo "$(GREEN)✅ Контейнеры пересобраны$(NC)"

# ─── Зависимости ──────────────────────────────────────────────────────────────

install: ## Установить зависимости через Composer
	@echo "$(YELLOW)Устанавливаем зависимости...$(NC)"
	@$(COMPOSE) up -d
	@$(PHP) composer install
	@echo "$(GREEN)✅ Зависимости установлены$(NC)"

# ─── База данных ──────────────────────────────────────────────────────────────

db-create: ## Создать базу данных
	@echo "$(YELLOW)Создаём базу данных...$(NC)"
	@$(CONSOLE) doctrine:database:create --if-not-exists
	@echo "$(GREEN)✅ База данных создана$(NC)"

migrate: ## Выполнить миграции
	@echo "$(YELLOW)Выполняем миграции...$(NC)"
	@$(CONSOLE) doctrine:migrations:migrate --no-interaction
	@echo "$(GREEN)✅ Миграции выполнены$(NC)"

fixtures: ## Загрузить тестовые фикстуры
	@echo "$(YELLOW)Загружаем фикстуры...$(NC)"
	@$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo "$(GREEN)✅ Фикстуры загружены$(NC)"

db-reset: ## Сбросить и пересоздать базу данных (с фикстурами)
	@echo "$(RED)Сбрасываем базу данных...$(NC)"
	@$(CONSOLE) doctrine:database:drop --force --if-exists
	@$(CONSOLE) doctrine:database:create --if-not-exists
	@$(CONSOLE) doctrine:migrations:migrate --no-interaction
	@$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo "$(GREEN)✅ База данных пересоздана с фикстурами$(NC)"

reset: stop ## Полный сброс проекта (контейнеры + volumes + БД)
	@echo "$(RED)Полный сброс проекта...$(NC)"
	@$(COMPOSE) down -v
	@$(COMPOSE) up -d --build
	@$(PHP) composer install
	@$(CONSOLE) doctrine:database:create --if-not-exists
	@$(CONSOLE) doctrine:migrations:migrate --no-interaction
	@$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo "$(GREEN)✅ Проект полностью сброшен и готов к работе!$(NC)"
	@echo "$(BLUE)Открывай: http://localhost:8082$(NC)"

# ─── Кэш ──────────────────────────────────────────────────────────────────────

cache-clear: ## Очистить кэш Symfony
	@echo "$(YELLOW)Очищаем кэш...$(NC)"
	@$(CONSOLE) cache:clear
	@echo "$(GREEN)✅ Кэш очищен$(NC)"

cache-warmup: ## Прогреть кэш Symfony
	@echo "$(YELLOW)Прогреваем кэш...$(NC)"
	@$(CONSOLE) cache:warmup
	@echo "$(GREEN)✅ Кэш прогрет$(NC)"

# ─── Качество кода ────────────────────────────────────────────────────────────

stan: ## Запустить PHPStan
	@echo "$(YELLOW)Запускаем PHPStan...$(NC)"
	@$(PHP) vendor/bin/phpstan analyse --no-progress
	@echo "$(GREEN)✅ Анализ завершён$(NC)"

cs-check: ## Проверить стиль кода (без изменений)
	@echo "$(YELLOW)Проверяем стиль кода...$(NC)"
	@$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff
	@echo "$(GREEN)✅ Проверка завершена$(NC)"

cs-fix: ## Исправить стиль кода автоматически
	@echo "$(YELLOW)Исправляем стиль кода...$(NC)"
	@$(PHP) vendor/bin/php-cs-fixer fix
	@echo "$(GREEN)✅ Стиль кода исправлен$(NC)"

test: ## Запустить тесты PHPUnit
	@echo "$(YELLOW)Запускаем тесты...$(NC)"
	@$(PHP) php bin/phpunit --testdox
	@echo "$(GREEN)✅ Тесты завершены$(NC)"

lint: cs-fix stan test ## Исправить стиль + анализ + тесты
	@echo ""
	@echo "$(GREEN)✅ Все проверки пройдены!$(NC)"
	@echo ""

# ─── Production ───────────────────────────────────────────────────────────────

prod-deploy: ## Production deploy
	@echo "$(YELLOW)Запускаем production deploy...$(NC)"
	@$(COMPOSE) up -d --build
	@$(PHP) composer install --no-dev --optimize-autoloader
	@$(CONSOLE) doctrine:migrations:migrate --no-interaction
	@$(CONSOLE) cache:clear --env=prod
	@$(CONSOLE) cache:warmup --env=prod
	@echo "$(GREEN)✅ Deploy завершён!$(NC)"

# ─── Утилиты ──────────────────────────────────────────────────────────────────

logs: ## Показать логи контейнеров
	@$(COMPOSE) logs -f

php-shell: ## Войти в PHP-контейнер
	@echo "$(YELLOW)Подключаемся к PHP-контейнеру...$(NC)"
	@$(COMPOSE) exec php bash

# Предотвращаем ошибки с аргументами
%:
	@:
