# TaskDiary

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.3-000000?style=flat&logo=symfony&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-4169E1?style=flat&logo=postgresql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?style=flat&logo=docker&logoColor=white)
![CI](https://github.com/NikitaShirokih/TaskDiary/actions/workflows/ci.yml/badge.svg)

## Описание

TaskDiary — backend/web-приложение для управления задачами, подзадачами, категориями и комментариями. Проект предоставляет REST API с Bearer-аутентификацией, web auth с подтверждением email и восстановлением пароля, Redis-кэш и модульную архитектуру.

## Возможности

- регистрация и авторизация пользователей;
- подтверждение email через одноразовый token и запрет входа для неподтверждённых пользователей;
- восстановление пароля через одноразовый reset token;
- управление задачами, подзадачами и категориями;
- комментарии к задачам в admin flow;
- REST API для задач и Bearer API tokens;
- rate limiting для login, password reset и API;
- Redis-кэш статистики dashboard;
- инвалидация кэша через Symfony EventDispatcher;
- Mailpit для локальной проверки писем;
- PHPUnit-тесты, PHPStan и GitHub Actions CI.

## Архитектура

```text
src/
├── Common/
└── Module/
    ├── Main/
    ├── Task/
    └── Ai/
```

- `Main` — пользователи, web auth, email verification, password reset, API tokens и rate limiting.
- `Task` — задачи, подзадачи, категории, комментарии, dashboard, REST API и cache invalidation.
- `Ai` — интеграционный слой AI-функций.
- `Common` — общие компоненты проекта.

Основной поток зависимостей: `Controller → Handler/Service → Entity/Repository`. Контроллеры управляют HTTP-flow, а обработка входных данных и бизнес-операции вынесены в handlers и services.

## Технологический стек

| Область | Технологии |
|---|---|
| Backend | PHP 8.3, Symfony 7.3, PHP-FPM |
| Данные | Doctrine ORM, PostgreSQL 15, Redis 7.2 |
| Security | Symfony Security, Symfony RateLimiter |
| Email | Symfony Mailer, Mailpit |
| Инфраструктура | Docker, Docker Compose, Nginx |
| Качество | PHPUnit, PHPStan, GitHub Actions |

## Модули проекта

`Main` реализует identity и security-контур приложения. `Task` содержит предметную модель управления задачами и HTTP/API use cases. `Ai` изолирует построение prompt-контекста и обращения к AI-сервису. Такое разделение удерживает web и API transport отдельно от бизнес-логики и инфраструктуры.

## Auth flow

### Registration и email verification

1. Пользователь отправляет форму регистрации.
2. Приложение создаёт одноразовый email verification token.
3. В базе сохраняется только SHA-256 hash token, raw token включается в email-ссылку.
4. До подтверждения email `VerifiedUserChecker` блокирует login.
5. После перехода по ссылке пользователь отмечается подтверждённым, token и срок его действия очищаются.

### Password reset

1. Пользователь отправляет email на странице восстановления пароля.
2. Приложение генерирует raw reset token и сохраняет только его hash.
3. Raw token передаётся пользователю в email-ссылке.
4. После успешной смены пароля reset token и срок его действия очищаются.

## REST API

Все API routes начинаются с `/api` и возвращают JSON. Основные endpoints:

```text
GET    /api/tasks
GET    /api/tasks/{id}
POST   /api/tasks
PUT    /api/tasks/{id}
PATCH  /api/tasks/{id}/status
DELETE /api/tasks/{id}
POST   /api/tasks/{id}/subtasks
```

Payload, ответы, ошибки и curl-примеры приведены в [документации REST API](docs/api.md).

## API tokens

API tokens создаются в web UI на странице `/profile/api-tokens` и используются для доступа к `/api/*`:

- raw token с префиксом `td_` показывается только один раз;
- в базе хранится SHA-256 hash;
- клиент передаёт token в заголовке `Authorization: Bearer <token>`;
- отозванный token больше не проходит аутентификацию.

```bash
curl http://localhost:8082/api/tasks \
  -H "Authorization: Bearer <token>"
```

## Rate limiting

| Flow | Лимит | Окно |
|---|---:|---:|
| Login | 5 попыток | 1 минута |
| Forgot password | 3 попытки | 10 минут |
| Reset password | 5 попыток | 10 минут |
| REST API | 60 запросов | 1 минута |

Login и password reset используют fixed window, API — sliding window. При исчерпании API-лимита сервер отвечает `429 Too Many Requests` в стандартном JSON error-format.

## Cache invalidation

Статистика dashboard кэшируется в Redis отдельно для каждого пользователя на 600 секунд. Любое изменение задачи запускает следующий flow:

```text
TaskService
→ TaskChangedEvent
→ TaskCacheInvalidationListener
→ TaskCacheInvalidator
→ удаление dashboard cache пользователя
```

Следующее обращение к dashboard пересчитывает статистику и записывает её в кэш заново.

## Тесты и качество кода

Проект содержит unit-, integration-, functional- и E2E-тесты. Web auth-flow и REST API проверяются functional-тестами через реальные HTTP-запросы. PHPStan выполняет статический анализ без baseline.

```bash
docker exec -it task_diary_php php bin/console lint:container
docker exec -it task_diary_php vendor/bin/phpstan analyse --no-progress
docker exec -it task_diary_php vendor/bin/phpunit tests/Unit
docker exec -it task_diary_php vendor/bin/phpunit tests/Functional
```

## Docker запуск

Требуются Docker и Docker Compose. Пример переменных окружения находится в `.env.local.example`.

```bash
docker compose -p taskdiary -f .docker/docker-compose.yaml up -d --build
docker exec -it task_diary_php composer install
docker exec -it task_diary_php php bin/console doctrine:migrations:migrate --no-interaction
docker exec -it task_diary_php php bin/console cache:clear
```

- приложение: <http://localhost:8082>
- Mailpit: <http://localhost:8025>
- SMTP внутри Docker: `mailpit:1025`

Полная инициализация также доступна командой `make init`.

## Полезные команды

```bash
make help           # список Makefile-команд
make start          # запустить контейнеры
make stop           # остановить контейнеры
make migrate        # применить миграции
make fixtures       # загрузить fixtures
make cache-clear    # очистить Symfony cache
make stan           # запустить PHPStan
make test           # запустить PHPUnit
```

Проверить локальную доставку email:

```bash
docker exec -it task_diary_php php bin/console app:send-test-email test@example.com
```

## CI

Workflow `.github/workflows/ci.yml` запускается для push и pull request. GitHub Actions поднимает PostgreSQL и Redis и выполняет:

- `composer validate --strict`;
- установку Composer dependencies;
- подготовку test environment и миграции;
- Symfony container lint;
- PHPStan;
- полный PHPUnit test suite.

## Лицензия

Proprietary.
