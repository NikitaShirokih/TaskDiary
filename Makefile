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
COMPOSE = docker compose -f .docker/docker-compose.yaml
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
		echo "$(YELLOW)Создаём .env.local...$(NC)"; \
		echo 'APP_ENV=dev'                                                                                        > .env.local; \
		echo 'APP_DEBUG=true'                                                                                    >> .env.local; \
		echo 'APP_NAME="Task Diary"'                                                                             >> .env.local; \
		echo 'APP_URL=http://localhost:8082'                                                                     >> .env.local; \
		echo 'DEFAULT_URI=http://localhost:8082'                                                                 >> .env.local; \
		echo ''                                                                                                  >> .env.local; \
		echo 'DATABASE_URL=mysql://user:password@mysql:3306/taskdiary?serverVersion=8.0&charset=utf8mb4'        >> .env.local; \
		echo 'DB_HOST=mysql'                                                                                     >> .env.local; \
		echo 'DB_PORT=3306'                                                                                      >> .env.local; \
		echo 'DB_NAME=taskdiary'                                                                                 >> .env.local; \
		echo 'DB_USER=user'                                                                                      >> .env.local; \
		echo 'DB_PASSWORD=password'                                                                              >> .env.local; \
		echo 'MYSQL_ROOT_PASSWORD=rootpassword'                                                                  >> .env.local; \
		echo ''                                                                                                  >> .env.local; \
		echo 'PHP_MEMORY_LIMIT=256M'                                                                            >> .env.local; \
		echo 'PHP_UPLOAD_MAX_FILESIZE=64M'                                                                      >> .env.local; \
		echo 'PHP_POST_MAX_SIZE=64M'                                                                            >> .env.local; \
		echo 'PHP_MAX_EXECUTION_TIME=300'                                                                       >> .env.local; \
		echo 'PHP_DISPLAY_ERRORS=On'                                                                            >> .env.local; \
		echo 'PHP_ERROR_REPORTING=E_ALL'                                                                        >> .env.local; \
		echo 'PHP_TIMEZONE=Europe/Moscow'                                                                       >> .env.local; \
		echo ''                                                                                                  >> .env.local; \
		echo 'NGINX_HTTP_PORT=8082'                                                                             >> .env.local; \
		echo 'NGINX_HTTPS_PORT=443'                                                                             >> .env.local; \
		echo 'PHPMYADMIN_PORT=8081'                                                                             >> .env.local; \
		echo ''                                                                                                  >> .env.local; \
		echo 'APP_SECRET=changeme'                                                                              >> .env.local; \
		echo 'GIGACHAT_API_KEY=changeme'                                                                        >> .env.local; \
		echo 'MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0'                                          >> .env.local; \
		echo 'MERCURE_PUBLISH_URL=http://localhost:3000/.well-known/mercure'                                     >> .env.local; \
		echo 'MERCURE_JWT_SECRET=changeme'                                                                      >> .env.local; \
		echo "$(GREEN)✅ .env.local создан$(NC)"; \
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
