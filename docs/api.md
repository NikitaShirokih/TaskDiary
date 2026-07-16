# TaskDiary REST API

## Authentication

Все `/api/*` endpoints требуют Bearer API token и возвращают JSON. Token создаётся авторизованным и подтверждённым пользователем в web UI на странице `/profile/api-tokens`.

Raw token с префиксом `td_` показывается только один раз. В базе хранится его SHA-256 hash; отозванный token больше не проходит authentication.

```http
Authorization: Bearer <token>
```

Пример запроса:

```bash
curl http://localhost:8082/api/tasks \
  -H "Authorization: Bearer ${API_TOKEN}"
```

## Error format

Ошибки API имеют единый формат:

```json
{
  "error": {
    "message": "Описание ошибки",
    "code": 400
  }
}
```

Отсутствующий или некорректный token:

```json
{
  "error": {
    "message": "Требуется API token.",
    "code": 401
  }
}
```

Не найденная задача:

```json
{
  "error": {
    "message": "Задача #42 не найдена.",
    "code": 404
  }
}
```

Основные коды ответа:

- `200 OK` — запрос выполнен;
- `201 Created` — задача или подзадача создана;
- `400 Bad Request` — некорректный JSON или входные данные;
- `401 Unauthorized` — token отсутствует, неверен или отозван;
- `403 Forbidden` — операция над задачей запрещена;
- `404 Not Found` — задача не найдена;
- `429 Too Many Requests` — исчерпан rate limit.

## Rate limits

Для `/api/*` действует sliding window: 60 запросов в минуту. До завершения authentication лимит привязывается к IP-адресу, для аутентифицированного запроса — к пользователю. При превышении лимита возвращается `429`:

```json
{
  "error": {
    "message": "Слишком много API-запросов. Попробуйте позже.",
    "code": 429
  }
}
```

## Endpoints

Все endpoints ниже требуют заголовок `Authorization: Bearer <token>`.

### GET /api/tasks

Возвращает `200 OK` и список задач текущего пользователя в `data`.

Query parameters:

- `view` — представление списка, по умолчанию `active`;
- `category` — числовой id категории;
- `priority` — `low`, `medium` или `high`.

```json
{
  "data": [
    {
      "id": 1,
      "title": "Подготовить отчёт",
      "description": "Собрать данные и отправить руководителю",
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

Возможные ошибки: `401`, `403`, `429`.

### GET /api/tasks/{id}

Возвращает `200 OK` и принадлежащую пользователю задачу в `data`.

```json
{
  "data": {
    "id": 1,
    "title": "Подготовить отчёт",
    "description": "Собрать данные и отправить руководителю",
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

Возможные ошибки: `401`, `403` при отсутствии доступа, `404` если задача не существует, `429`.

### POST /api/tasks

Создаёт задачу и возвращает `201 Created` с созданным объектом в `data`.

Request body:

```json
{
  "title": "Подготовить отчёт",
  "description": "Собрать данные и отправить руководителю",
  "priority": "medium",
  "status": "waiting",
  "categoryName": "Работа",
  "startTime": "2026-07-10T09:00:00+03:00",
  "endTime": "2026-07-10T18:00:00+03:00"
}
```

Поля request body:

- `title` — обязательная непустая строка;
- `description` — необязательная строка;
- `priority` — `low`, `medium`, `high`; по умолчанию `medium`;
- `status` — `waiting`, `in_progress`, `completed`; по умолчанию `waiting`;
- `categoryName` — название категории, необязательное;
- `startTime`, `endTime` — необязательные даты, принимаемые `DateTimeImmutable`.

Успешный ответ имеет тот же объект задачи, что и `GET /api/tasks/{id}`. Возможные ошибки: `400` при некорректном JSON, enum или дате, `401`, `403`, `429`.

### PUT /api/tasks/{id}

Обновляет задачу и возвращает `200 OK` с обновлённым объектом в `data`. Request body имеет ту же структуру, что `POST /api/tasks`; для отсутствующих `priority` и `status` используются значения по умолчанию.

```json
{
  "title": "Обновлённый отчёт",
  "description": "Добавить финальные цифры",
  "priority": "high",
  "status": "in_progress",
  "categoryName": "Работа",
  "startTime": "2026-07-10T09:00:00+03:00",
  "endTime": "2026-07-10T19:00:00+03:00"
}
```

Возможные ошибки: `400`, `401`, `403`, `404`, `429`.

### PATCH /api/tasks/{id}/status

Изменяет только статус задачи и возвращает `200 OK` с обновлённым объектом в `data`.

```json
{
  "status": "completed"
}
```

Допустимые значения: `waiting`, `in_progress`, `completed`. Возможные ошибки: `400` при отсутствующем или некорректном статусе, `401`, `403`, `404`, `429`.

### DELETE /api/tasks/{id}

Удаляет задачу и возвращает `200 OK`:

```json
{
  "data": {
    "message": "Задача удалена."
  }
}
```

Возможные ошибки: `401`, `403`, `404`, `429`.

### POST /api/tasks/{id}/subtasks

Создаёт подзадачу у родительской задачи `{id}` и возвращает `201 Created`. Подзадача не получает отдельную категорию, поэтому `category` в ответе равен `null`.

```json
{
  "title": "Проверить цифры",
  "description": "Сверить значения с источником",
  "priority": "high",
  "status": "waiting"
}
```

Response:

```json
{
  "data": {
    "id": 2,
    "title": "Проверить цифры",
    "description": "Сверить значения с источником",
    "status": "waiting",
    "priority": "high",
    "category": null,
    "parentId": 1,
    "startTime": null,
    "endTime": null,
    "createdAt": "2026-07-10T17:10:21+03:00",
    "updatedAt": null
  }
}
```

Возможные ошибки: `400`, `401`, `403`, `404`, `429`.

## Examples

Сначала создайте token на `/profile/api-tokens` и сохраните показанное значение:

```bash
API_TOKEN="td_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

Получить список задач:

```bash
curl http://localhost:8082/api/tasks \
  -H "Authorization: Bearer ${API_TOKEN}"
```

Создать задачу:

```bash
curl http://localhost:8082/api/tasks \
  -X POST \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Подготовить отчёт",
    "description": "Собрать данные и отправить руководителю",
    "priority": "medium",
    "categoryName": "Работа"
  }'
```

Изменить статус:

```bash
curl http://localhost:8082/api/tasks/1/status \
  -X PATCH \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"status":"completed"}'
```

Создать подзадачу:

```bash
curl http://localhost:8082/api/tasks/1/subtasks \
  -X POST \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Проверить цифры",
    "priority": "high"
  }'
```

Удалить задачу:

```bash
curl http://localhost:8082/api/tasks/1 \
  -X DELETE \
  -H "Authorization: Bearer ${API_TOKEN}"
```
