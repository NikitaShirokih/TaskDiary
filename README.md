# TaskDiary 📋

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.3-000000?style=flat&logo=symfony&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?style=flat&logo=docker&logoColor=white)
![PHPStan](https://img.shields.io/badge/PHPStan-level%206-blue?style=flat)
![PSR-12](https://img.shields.io/badge/code%20style-PSR--12-brightgreen?style=flat)
![CI](https://github.com/NikitaShirokih/TaskDiary/actions/workflows/ci.yml/badge.svg)


**TaskDiary** — это веб-приложение для управления задачами с поддержкой подзадач, категорий, приоритетов, статусов, аналитики и AI-ассистента.  
Проект помогает организовывать личные и рабочие задачи, отслеживать продуктивность и удобно планировать дела через список задач, календарь и дашборд.

---

## Содержание

- [Технологии](#технологии)
- [Возможности](#возможности)
- [Использование](#использование)
- [Разработка](#разработка)
    - [Требования](#требования)
    - [Установка зависимостей](#установка-зависимостей)
    - [Настройка окружения](#настройка-окружения)
    - [Запуск через Docker](#запуск-через-docker)
    - [Запуск без Docker](#запуск-без-docker)
    - [Создание production-сборки](#создание-production-сборки)
- [Основные маршруты](#основные-маршруты)
- [Структура данных](#структура-данных)
- [Тестирование и качество кода](#тестирование-и-качество-кода)
- [Deploy и CI/CD](#deploy-и-cicd)
- [Contributing](#contributing)
- [FAQ](#faq)
- [To do](#to-do)
- [Команда проекта](#команда-проекта)
- [Источники](#источники)
- [Лицензия](#лицензия)

---

## Технологии

В проекте используются следующие технологии:

| Технология | Версия | Назначение |
|------------|--------|------------|
| PHP | 8.2+ | Язык программирования |
| PHP-FPM | 8.3 | Выполнение PHP в Docker |
| Symfony | 7.3.* | Основной backend-фреймворк |
| Doctrine ORM | 2.17+ | Работа с сущностями и базой данных |
| Doctrine DBAL | 3.x | Низкоуровневое взаимодействие с БД |
| Doctrine Migrations | 3.6+ | Управление миграциями базы данных |
| MySQL | 8.0 | Реляционная база данных |
| Twig | 2.x / 3.x | Шаблонизатор |
| Symfony Security | 7.3.* | Аутентификация и авторизация |
| Symfony Validator | 7.3.* | Валидация данных |
| Symfony Serializer | 7.3.* | Сериализация данных |
| Symfony Mailer / Notifier | 7.3.* | Отправка уведомлений |
| Symfony HTTP Client | 7.3.* | HTTP-запросы к внешним сервисам |
| Symfony Asset Mapper | 7.3.* | Управление frontend-ассетами |
| Symfony UX Turbo / Stimulus | 2.31+ | Улучшение интерактивности интерфейса |
| Docker | 24+ | Контейнеризация окружения |
| Nginx | Alpine | Веб-сервер |
| PHPStan | level 6 | Статический анализ кода |
| PHP CS Fixer | 3.x | Форматирование кода по стандарту PSR-12 |

---

## Возможности

TaskDiary предоставляет инструменты для удобного управления задачами:

- ✅ **Задачи и подзадачи** — поддержка иерархической структуры задач.
- 🏷️ **Категории** — группировка задач по категориям с цветовой маркировкой.
- 🔥 **Приоритеты** — управление важностью задач.
- 📌 **Статусы задач** — отслеживание состояния выполнения.
- 📊 **Дашборд** — быстрый обзор активных задач, статистики и последних записей.
- 📈 **Аналитика** — данные по продуктивности, среднему времени выполнения и burndown-графикам.
- 📅 **Планирование по датам** — указание времени начала и окончания задачи.
- 🔐 **Аутентификация** — регистрация, вход и выход пользователей.
- 🛡️ **Авторизация** — проверка доступа к задачам через владельца задачи.
- 📤 **Экспорт** — выгрузка задач в JSON.
- 🤖 **AI-ассистент** — интеграция с GigaChat для работы с задачами.

---

## Использование

TaskDiary используется как веб-приложение для личного или рабочего планирования.

После запуска приложения пользователь может:

1. Зарегистрироваться или войти в систему.
2. Создавать задачи.
3. Добавлять подзадачи.
4. Назначать категории и приоритеты.
5. Указывать время начала и окончания задачи.
6. Менять статус выполнения.
7. Просматривать список задач и дашборд.
8. Анализировать продуктивность.
9. Экспортировать задачи в JSON.
10. Использовать AI-ассистента для работы с задачами.

Пример пользовательского сценария:

```text
Пользователь создаёт задачу → выбирает категорию → назначает приоритет →
добавляет подзадачи → отслеживает выполнение → анализирует продуктивность.
```

---

## Разработка

Этот раздел описывает запуск проекта локально.

### Требования

Проект можно запустить двумя способами: через Docker или локально без контейнеров.

#### Вариант 1. Запуск через Docker

Для запуска через Docker необходимы:

- Docker
- Docker Compose

Проверить установку можно командами:

```bash
docker -v
docker compose version
```

#### Вариант 2. Локальный запуск

Для запуска без Docker необходимы:

- PHP 8.2+
- Composer
- MySQL 8.0+
- Symfony CLI

Проверить версии можно командами:

```bash
php -v
composer -V
mysql --version
symfony -v
```

---

### Установка зависимостей

Клонируйте репозиторий:

```bash
git clone https://github.com/your-username/taskdiary.git
cd taskdiary
```

Если проект запускается локально без Docker, установите зависимости:

```bash
composer install
```

Если проект запускается через Docker, зависимости можно установить внутри PHP-контейнера после запуска контейнеров.

---

### Настройка окружения

Создайте локальный файл окружения:

```bash
cp .env .env.local
```

Основные переменные окружения:

```env
APP_ENV=dev
APP_DEBUG=true
APP_NAME="Task Diary"
APP_URL=http://localhost:8082
DEFAULT_URI=http://localhost:8082

DATABASE_URL=mysql://user:password@mysql:3306/taskdiary?serverVersion=8.0&charset=utf8mb4
DB_HOST=mysql
DB_PORT=3306
DB_NAME=taskdiary
DB_USER=user
DB_PASSWORD=password
```

Для работы AI-ассистента также необходимо указать переменные окружения, связанные с GigaChat, если они используются в проекте.

> Не храните реальные пароли, токены и ключи доступа в репозитории. Для этого используйте `.env.local` или секреты окружения.

---

### Запуск через Docker

В проекте предусмотрена Docker-конфигурация.

Используются основные сервисы:

- **nginx** — веб-сервер.
- **php** — PHP-FPM контейнер для Symfony-приложения.
- **mysql** — база данных MySQL.

Nginx пробрасывает приложение на порт:

```text
http://localhost:8082
```

Запустите контейнеры:

```bash
docker compose up -d --build
```

Установите зависимости внутри PHP-контейнера:

```bash
docker compose exec php composer install
```

Создайте базу данных, если она ещё не создана:

```bash
docker compose exec php php bin/console doctrine:database:create
```

Выполните миграции:

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

При необходимости загрузите фикстуры:

```bash
docker compose exec php php bin/console doctrine:fixtures:load
```

После запуска приложение будет доступно по адресу:

```text
http://localhost:8082
```

Остановить контейнеры можно командой:

```bash
docker compose down
```

Остановить контейнеры вместе с удалением volume можно командой:

```bash
docker compose down -v
```

---

### Запуск без Docker

Если проект запускается локально, укажите подключение к локальной базе данных в `.env.local`:

```env
DATABASE_URL=mysql://user:password@127.0.0.1:3306/taskdiary?serverVersion=8.0&charset=utf8mb4
```

Создайте базу данных:

```bash
php bin/console doctrine:database:create
```

Выполните миграции:

```bash
php bin/console doctrine:migrations:migrate
```

Запустите Symfony-сервер:

```bash
symfony server:start
```

Или используйте встроенный PHP-сервер:

```bash
php -S localhost:8082 -t public
```

После запуска приложение будет доступно по адресу:

```text
http://localhost:8082
```

---

### Создание production-сборки

Для подготовки production-окружения установите зависимости без dev-пакетов:

```bash
composer install --no-dev --optimize-autoloader
```

Очистите и прогрейте кэш:

```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

В Docker эти команды можно выполнить внутри PHP-контейнера:

```bash
docker compose exec php composer install --no-dev --optimize-autoloader
docker compose exec php php bin/console cache:clear --env=prod
docker compose exec php php bin/console cache:warmup --env=prod
```

---

## Основные маршруты

Ниже перечислены ключевые маршруты приложения.

| Метод | Маршрут | Назначение |
|------|---------|------------|
| GET | `/` | Дашборд пользователя |
| GET / POST | `/register` | Регистрация пользователя |
| GET / POST | `/login` | Вход в систему |
| GET | `/logout` | Выход из системы |
| GET | `/task/` | Список задач |
| GET | `/task/ajax/list` | AJAX-обновление списка задач |
| GET / POST | `/task/create` | Создание задачи |
| GET | `/task/analytics` | Страница аналитики |
| GET / POST | `/task/{id}/subtask/create` | Создание подзадачи |
| GET | `/task/{id}` | Просмотр задачи |
| GET / POST | `/task/{id}/edit` | Редактирование задачи |
| POST | `/task/{id}/status` | Обновление статуса задачи |

---

## Структура данных

Основные сущности проекта:

| Сущность | Описание |
|---------|----------|
| `User` | Пользователь системы. Содержит email, роли и пароль |
| `Task` | Задача пользователя. Содержит название, описание, даты, статус, приоритет и связи |
| `Category` | Категория задачи. Содержит название, описание, цвет и иконку |

### Task

Сущность `Task` поддерживает:

- название задачи;
- описание;
- дату и время начала;
- дату и время окончания;
- статус;
- приоритет;
- связь с пользователем;
- связь с категорией;
- связь с родительской задачей;
- дочерние подзадачи.

Также для задач добавлены индексы по:

- `status`;
- `priority`;
- `created_at`.

### Category

Сущность `Category` используется для группировки задач.

Категория содержит:

- название;
- описание;
- цвет;
- иконку;
- список связанных задач.

### User

Сущность `User` используется для аутентификации и авторизации.

Пользователь содержит:

- email;
- роли;
- пароль.

---

## Тестирование и качество кода

В проекте настроены инструменты контроля качества кода:

- **PHPStan** — статический анализ кода.
- **PHP CS Fixer** — форматирование и проверка стиля кода.
- **PSR-12** — используемый стандарт оформления кода.

PHPStan настроен на уровень:

```text
level 6
```

### Запуск PHPStan

```bash
vendor/bin/phpstan analyse
```

Через Docker:

```bash
docker compose exec php vendor/bin/phpstan analyse
```

### Проверка стиля кода

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Через Docker:

```bash
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff
```

### Автоматическое исправление стиля

```bash
vendor/bin/php-cs-fixer fix
```

Через Docker:

```bash
docker compose exec php vendor/bin/php-cs-fixer fix
```

### Запуск тестов

Если в проекте установлен PHPUnit, тесты можно запустить командой:

```bash
php bin/phpunit
```

Через Docker:

```bash
docker compose exec php php bin/phpunit
```

---

## Deploy и CI/CD

Проект можно развернуть на VPS, выделенном сервере или другой платформе, поддерживающей PHP 8.2+, MySQL, Composer и Nginx.

### Подготовка к deploy

На сервере необходимо:

1. Склонировать репозиторий.
2. Настроить переменные окружения.
3. Установить production-зависимости.
4. Выполнить миграции.
5. Очистить и прогреть production-кэш.
6. Настроить веб-сервер на директорию `public/`.

Пример команд:

```bash
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### CI/CD

Для CI/CD можно использовать GitHub Actions.

Пример workflow:

```yaml
name: CI

on:
  push:
    branches:
      - main
  pull_request:

jobs:
  quality:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: ctype, iconv, pdo_mysql, mbstring, intl
          coverage: none

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse

      - name: Check code style
        run: vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## Contributing

Мы рады предложениям по улучшению проекта.

Чтобы внести изменения:

1. Сделайте fork репозитория.
2. Создайте новую ветку:

```bash
git checkout -b feature/your-feature-name
```

3. Внесите изменения.
4. Проверьте код перед отправкой:

```bash
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
```

5. Создайте commit:

```bash
git commit -m "feat: add new feature"
```

6. Отправьте изменения в свой fork:

```bash
git push origin feature/your-feature-name
```

7. Создайте Pull Request в основной репозиторий.

### Требования к коду

- Соблюдать стандарт кодирования **PSR-12**.
- Использовать строгую типизацию.
- Проверять код через **PHPStan**.
- Проверять форматирование через **PHP CS Fixer**.
- Не хранить секреты, пароли и токены в репозитории.
- Не отключать CSRF-защиту без необходимости.
- Для новой бизнес-логики добавлять тесты, если в проекте используется PHPUnit.
- Описывать изменения в Pull Request.

---

## FAQ

### Зачем вы разработали этот проект?

Чтобы упростить управление задачами и объединить список дел, подзадачи, категории, аналитику и AI-помощника в одном приложении.

### Можно ли создавать подзадачи?

Да. TaskDiary поддерживает родительские задачи и дочерние подзадачи.

### Есть ли категории?

Да. Задачи можно группировать по категориям. Категории поддерживают название, описание, цвет и иконку.

### Есть ли приоритеты?

Да. У задач есть приоритеты, которые позволяют выделять более важные задачи.

### Есть ли статусы задач?

Да. Задачи имеют статусы, по которым можно отслеживать их состояние.

### Есть ли аналитика?

Да. В проекте есть страница аналитики с данными по продуктивности, среднему времени выполнения и burndown-графикам.

### Есть ли авторизация?

Да. В проекте реализована регистрация, вход, выход и проверка доступа к задачам пользователя.

### Можно ли экспортировать задачи?

Да. В проекте предусмотрен экспорт задач в JSON.

### Можно ли запустить проект через Docker?

Да. Проект запускается через Docker Compose и доступен по адресу `http://localhost:8082`.

---

## To do

- [ ] Расширить покрытие тестами ключевых пользовательских сценариев.
- [ ] Добавить расширенную фильтрацию задач по статусу, категории, приоритету и дате.
- [ ] Реализовать поиск по задачам и подзадачам.
- [ ] Добавить уведомления о приближающихся дедлайнах.
- [ ] Реализовать редактирование профиля пользователя.
- [ ] Добавить возможность смены темы интерфейса.
- [ ] Подготовить подробную production-инструкцию для развёртывания проекта.


---

## Источники

При разработке проекта использовались следующие материалы:

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/orm.html)
- [Doctrine Migrations Documentation](https://www.doctrine-project.org/projects/doctrine-migrations/en/current/)
- [Twig Documentation](https://twig.symfony.com/doc/)
- [Symfony Security Documentation](https://symfony.com/doc/current/security.html)
- [Symfony Validator Documentation](https://symfony.com/doc/current/validation.html)
- [Symfony UX Documentation](https://symfony.com/bundles/ux-turbo/current/index.html)
- [Docker Documentation](https://docs.docker.com/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHP CS Fixer Documentation](https://cs.symfony.com/)
- [Shields.io](https://shields.io/) — генерация бейджей

---

## Лицензия

В `composer.json` указана лицензия:

```json
"license": "proprietary"
```

Это означает, что проект является проприетарным и не распространяется как open-source по лицензии MIT.

Если проект должен распространяться под MIT, необходимо изменить поле `license` в `composer.json`:

```json
"license": "MIT"
```

И добавить файл `LICENSE` в корень проекта.
