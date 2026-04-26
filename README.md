# TaskDiary 📋

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.x-000000?style=flat&logo=symfony&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![PHPStan](https://img.shields.io/badge/PHPStan-level%206-blue?style=flat)
![PSR-12](https://img.shields.io/badge/code%20style-PSR--12-brightgreen?style=flat)
![License](https://img.shields.io/badge/license-MIT-green?style=flat)

> Веб-приложение для управления задачами с поддержкой подзадач, категорий, приоритетов и аналитики.

---

## 📸 Скриншоты

### 🔐 Страница входа
![Login](docs/screenshots/login.png)

### 📊 Дашборд
![Dashboard](docs/screenshots/index.png)

### 📋 Список задач
![Task List](docs/screenshots/list.task.png)

### ➕ Создание задачи
![Create Task](docs/screenshots/create.png)

### 🔍 Детали задачи
![Task Detail](docs/screenshots/task.png)

---

## ✨ Возможности

- ✅ **Задачи и подзадачи** — иерархическая структура задач
- 🏷️ **Категории** — группировка задач по категориям с цветовой маркировкой
- 🔥 **Приоритеты** — низкий / средний / высокий
- 📊 **Аналитика** — графики продуктивности, статистика
- 📅 **Календарь** — визуализация задач по датам
- 🔐 **Аутентификация** — регистрация и вход пользователей
- 🛡️ **Безопасность** — CSRF-защита, Voter-авторизация
- 📤 **Экспорт** — выгрузка задач в JSON
- 🤖 **GigaChat AI** — встроенный AI-ассистент для работы с задачами

---

## 🛠️ Технологии

| Технология | Версия | Назначение |
|------------|--------|------------|
| PHP | 8.2+ | Язык программирования |
| Symfony | 7.x | Основной фреймворк |
| Doctrine ORM | 3.x | Работа с базой данных |
| MySQL | 8.0 | База данных |
| Twig | 3.x | Шаблонизатор |
| Bootstrap | 5.x | UI-компоненты |
| Chart.js | 4.x | Графики и аналитика |
| FullCalendar | 6.x | Календарь задач |
| PHPStan | level 6 | Статический анализ |
| PHP CS Fixer | 3.x | Стиль кода PSR-12 |

---

## 🚀 Установка

### Требования

- PHP 8.2+
- Composer
- MySQL 8.0+
- Symfony CLI

### Шаги

1. **Клонировать репозиторий**

```bash
git clone https://github.com/your-username/taskdiary.git
cd taskdiary
