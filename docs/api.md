# TaskDiary REST API

## Общая информация

REST API задач доступен по маршрутам `/api/*` и возвращает только JSON.

Первая версия API покрывает работу с задачами и подзадачами:

- просмотр списка задач;
- просмотр одной задачи;
- создание задачи;
- обновление задачи;
- изменение статуса;
- удаление задачи;
- создание подзадачи.

## Авторизация

API использует текущую Symfony session-auth.

Для работы с API пользователь должен быть авторизован через web-login. После входа браузер или HTTP-клиент отправляет session cookie, и API routes становятся доступны.

В будущей версии можно добавить Bearer API tokens. В текущей версии Bearer tokens, JWT и API tokens не используются.

## Формат успешного ответа

Список возвращается в поле `data` как массив:

```json
{
  "data": [
    {
      "id": 1,
      "title": "API задача",
      "description": "Создано через REST API",
      "status": "waiting",
      "priority": "medium",
      "category": {
        "id": 10,
        "name": "Работа"
      },
      "parentId": null,
      "startTime": null,
      "endTime": null,
      "createdAt": "2026-07-10T17:10:20+03:00",
      "updatedAt": null
    }
  ]
}
```

Одна задача возвращается в поле `data` как объект:

```json
{
  "data": {
    "id": 1,
    "title": "API задача",
    "description": "Создано через REST API",
    "status": "waiting",
    "priority": "medium",
    "category": {
      "id": 10,
      "name": "Работа"
    },
    "parentId": null,
    "startTime": null,
    "endTime": null,
    "createdAt": "2026-07-10T17:10:20+03:00",
    "updatedAt": null
  }
}
```

Удаление возвращает сообщение в поле `data`:

```json
{
  "data": {
    "message": "Задача удалена."
  }
}
```

## Формат ошибки

Все ошибки API возвращаются в едином формате:

```json
{
  "error": {
    "message": "Некорректный JSON-запрос.",
    "code": 400
  }
}
```

## Endpoints

### GET /api/tasks

Возвращает список задач текущего пользователя.

Query parameters:

- `view` - представление списка, по умолчанию `active`;
- `category` - id категории;
- `priority` - приоритет: `low`, `medium`, `high`.

Response `200`:

```json
{
  "data": [
    {
      "id": 1,
      "title": "API задача",
      "description": "Создано через REST API",
      "status": "waiting",
      "priority": "medium",
      "category": {
        "id": 10,
        "name": "Работа"
      },
      "parentId": null,
      "startTime": null,
      "endTime": null,
      "createdAt": "2026-07-10T17:10:20+03:00",
      "updatedAt": null
    }
  ]
}
```

Возможные ошибки:

- `401 Unauthorized` или redirect на login, если пользователь не авторизован;
- `403 Forbidden`, если доступ запрещен.

### GET /api/tasks/{id}

Возвращает одну задачу по id.

Response `200`:

```json
{
  "data": {
    "id": 1,
    "title": "API задача",
    "description": "Создано через REST API",
    "status": "waiting",
    "priority": "medium",
    "category": {
      "id": 10,
      "name": "Работа"
    },
    "parentId": null,
    "startTime": null,
    "endTime": null,
    "createdAt": "2026-07-10T17:10:20+03:00",
    "updatedAt": null
  }
}
```

Возможные ошибки:

- `404 Not Found`, если задача не найдена;
- `403 Forbidden`, если нет доступа к задаче.

### POST /api/tasks

Создает задачу.

Request body:

```json
{
  "title": "API задача",
  "description": "Создано через REST API",
  "priority": "medium",
  "status": "waiting",
  "categoryName": "Работа",
  "startTime": "2026-07-10T09:00:00+03:00",
  "endTime": "2026-07-10T18:00:00+03:00"
}
```

Поля:

- `title` - обязательное название задачи;
- `description` - описание, опционально;
- `priority` - `low`, `medium`, `high`; по умолчанию `medium`;
- `status` - `waiting`, `in_progress`, `completed`; по умолчанию `waiting`;
- `categoryName` - название категории для основной задачи;
- `startTime` - дата начала, опционально;
- `endTime` - дата окончания, опционально.

Response `201`:

```json
{
  "data": {
    "id": 1,
    "title": "API задача",
    "description": "Создано через REST API",
    "status": "waiting",
    "priority": "medium",
    "category": {
      "id": 10,
      "name": "Работа"
    },
    "parentId": null,
    "startTime": "2026-07-10T09:00:00+03:00",
    "endTime": "2026-07-10T18:00:00+03:00",
    "createdAt": "2026-07-10T17:10:20+03:00",
    "updatedAt": null
  }
}
```

Возможные ошибки:

- `400 Bad Request`, если JSON некорректный или данные не прошли валидацию;
- `403 Forbidden`, если пользователь не может создать задачу.

### PUT /api/tasks/{id}

Обновляет задачу.

Request body:

```json
{
  "title": "Обновленная API задача",
  "description": "Новое описание",
  "priority": "high",
  "status": "in_progress",
  "categoryName": "Работа",
  "startTime": "2026-07-10T09:00:00+03:00",
  "endTime": "2026-07-10T18:00:00+03:00"
}
```

Response `200`:

```json
{
  "data": {
    "id": 1,
    "title": "Обновленная API задача",
    "description": "Новое описание",
    "status": "in_progress",
    "priority": "high",
    "category": {
      "id": 10,
      "name": "Работа"
    },
    "parentId": null,
    "startTime": "2026-07-10T09:00:00+03:00",
    "endTime": "2026-07-10T18:00:00+03:00",
    "createdAt": "2026-07-10T17:10:20+03:00",
    "updatedAt": "2026-07-10T17:15:00+03:00"
  }
}
```

Возможные ошибки:

- `400 Bad Request`, если JSON некорректный или данные не прошли валидацию;
- `403 Forbidden`, если нет права редактировать задачу;
- `404 Not Found`, если задача не найдена.

### PATCH /api/tasks/{id}/status

Обновляет только статус задачи.

Request body:

```json
{
  "status": "in_progress"
}
```

Response `200`:

```json
{
  "data": {
    "id": 1,
    "title": "API задача",
    "description": "Создано через REST API",
    "status": "in_progress",
    "priority": "medium",
    "category": {
      "id": 10,
      "name": "Работа"
    },
    "parentId": null,
    "startTime": null,
    "endTime": null,
    "createdAt": "2026-07-10T17:10:20+03:00",
    "updatedAt": "2026-07-10T17:15:00+03:00"
  }
}
```

Возможные ошибки:

- `400 Bad Request`, если JSON некорректный или статус невалидный;
- `403 Forbidden`, если нет права редактировать задачу;
- `404 Not Found`, если задача не найдена.

### DELETE /api/tasks/{id}

Удаляет задачу.

Response `200`:

```json
{
  "data": {
    "message": "Задача удалена."
  }
}
```

Возможные ошибки:

- `403 Forbidden`, если нет права удалить задачу;
- `404 Not Found`, если задача не найдена.

### POST /api/tasks/{id}/subtasks

Создает подзадачу у задачи `{id}`.

Request body:

```json
{
  "title": "API подзадача",
  "description": "Описание подзадачи",
  "priority": "low",
  "status": "waiting"
}
```

Response `201`:

```json
{
  "data": {
    "id": 2,
    "title": "API подзадача",
    "description": "Описание подзадачи",
    "status": "waiting",
    "priority": "low",
    "category": null,
    "parentId": 1,
    "startTime": null,
    "endTime": null,
    "createdAt": "2026-07-10T17:10:21+03:00",
    "updatedAt": null
  }
}
```

Возможные ошибки:

- `400 Bad Request`, если JSON некорректный или данные не прошли валидацию;
- `403 Forbidden`, если нет права редактировать родительскую задачу;
- `404 Not Found`, если родительская задача не найдена.

## Примеры curl

Сначала нужно авторизоваться через web-login и сохранить session cookie:

```bash
curl -c cookies.txt -b cookies.txt -X POST http://localhost:8082/login \
  --data-urlencode "_username=user@example.com" \
  --data-urlencode "_password=password"
```

Получить список задач:

```bash
curl -b cookies.txt http://localhost:8082/api/tasks
```

Создать задачу:

```bash
curl -b cookies.txt -H "Content-Type: application/json" \
  -X POST http://localhost:8082/api/tasks \
  -d '{
    "title": "API задача",
    "description": "Создано через REST API",
    "priority": "medium",
    "status": "waiting",
    "categoryName": "Работа"
  }'
```

Обновить задачу:

```bash
curl -b cookies.txt -H "Content-Type: application/json" \
  -X PUT http://localhost:8082/api/tasks/1 \
  -d '{
    "title": "Обновленная API задача",
    "description": "Новое описание",
    "priority": "high",
    "status": "in_progress",
    "categoryName": "Работа"
  }'
```

Изменить статус:

```bash
curl -b cookies.txt -H "Content-Type: application/json" \
  -X PATCH http://localhost:8082/api/tasks/1/status \
  -d '{"status": "completed"}'
```

Создать подзадачу:

```bash
curl -b cookies.txt -H "Content-Type: application/json" \
  -X POST http://localhost:8082/api/tasks/1/subtasks \
  -d '{
    "title": "API подзадача",
    "priority": "low",
    "status": "waiting"
  }'
```

Удалить задачу:

```bash
curl -b cookies.txt -X DELETE http://localhost:8082/api/tasks/1
```

## HTTP status codes

- `200 OK` - запрос выполнен успешно;
- `201 Created` - задача или подзадача создана;
- `400 Bad Request` - некорректный JSON или невалидные данные;
- `401 Unauthorized` - пользователь не авторизован;
- `403 Forbidden` - у пользователя нет доступа к действию;
- `404 Not Found` - задача не найдена.
