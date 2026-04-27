# TaskDiary

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.3-000000?style=flat&logo=symfony&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?style=flat&logo=docker&logoColor=white)
![CI](https://github.com/NikitaShirokih/TaskDiary/actions/workflows/ci.yml/badge.svg)

Веб-приложение для управления задачами с поддержкой подзадач, категорий, приоритетов, аналитики и AI-ассистента на базе GigaChat.

---

## Стек

| | |
|---|---|
| Backend | PHP 8.2+, Symfony 7.3 |
| База данных | MySQL 8.0 |
| Инфраструктура | Docker, Nginx |
| Качество кода | PHPStan level 6, PSR-12 |

---

## Возможности

- Задачи с подзадачами, приоритетами и дедлайнами
- Категории с цветами и иконками
- Аналитика и дашборд
- AI-ассистент на базе GigaChat
- Экспорт задач

---

## Структура

```
src/
├── Controller/   # HTTP-слой
├── Entity/       # Doctrine-сущности
├── Repository/   # Запросы к БД
├── Service/      # Бизнес-логика
└── DataFixtures/ # Тестовые данные
```

Пример всех переменных — в файле `.env.local.example`

---

## Запуск

> Требования: **Docker** и **Docker Compose**

```bash
git clone https://github.com/NikitaShirokih/TaskDiary.git
cd TaskDiary
make init
```

Приложение запустится на `http://localhost:8082`

После первого запуска укажи реальные значения в `.env.local.example`:

```env
APP_SECRET=your_secret
GIGACHAT_API_KEY=your_key
```

## Команды

```bash
make help          # список всех команд

make start         # запустить проект
make stop          # остановить проект
make restart       # перезапустить проект
make rebuild       # пересобрать контейнеры

make migrate       # выполнить миграции
make db-reset      # сбросить и пересоздать БД
make reset         # полный сброс проекта

make lint          # стиль + анализ + тесты
make prod-deploy   # production deploy

make php-shell     # войти в PHP-контейнер
make logs          # логи контейнеров
```

---

## Лицензия

"proprietary".

