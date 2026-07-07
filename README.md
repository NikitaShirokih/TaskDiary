# TaskDiary

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.3-000000?style=flat&logo=symfony&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-4169E1?style=flat&logo=postgresql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?style=flat&logo=docker&logoColor=white)
![CI](https://github.com/NikitaShirokih/TaskDiary/actions/workflows/ci.yml/badge.svg)

Веб-приложение для управления задачами с поддержкой подзадач, категорий, приоритетов, аналитики и AI-ассистента.

---

## Стек

| | |
|---|---|
| Backend | PHP 8.2+, Symfony 7.3 |
| База данных | PostgreSQL 15 |
| Кэш | Redis 7.2 |
| Инфраструктура | Docker, Nginx, PostgreSQL, Redis |
| Качество кода | PHPStan level 6, PSR-12 |

---

## Возможности

- Задачи с подзадачами, приоритетами и дедлайнами
- Категории с цветами и иконками
- Аналитика и дашборд
- AI-ассистент
- Экспорт задач

---

Пример всех переменных — в файле `.env.local.example`

---

## Запуск

> Требования: **Docker** и **Docker Compose**

```bash
git clone https://github.com/NikitaShirokih/TaskDiary.git
cd TaskDiary
make init
```

После первого запуска укажи реальные значения в `.env.local.example`:

```env
APP_SECRET=your_secret
GIGACHAT_API_KEY=your_key
```

## Команды

```bash
make help          # список всех команд

```

## Лицензия

"proprietary".
