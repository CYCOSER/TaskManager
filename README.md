---

## 📖 Документация API (Swagger UI)

В проект интегрирован Swagger для интерактивного тестирования эндпоинтов. Вы можете отправлять `GET`, `POST`, `PUT` и `DELETE` запросы к задачам без использования сторонних программ (Postman/cURL).

*   **Интерактивная панель (Swagger UI):** Откройте в браузере [http://localhost:8080/api/doc](http://localhost:8080/api/doc)
*   **Спецификация OpenAPI (JSON):** [http://localhost:8080/api/doc.json](http://localhost:8080/api/doc.json)

---

## 📡 Альтернативные варианты запросов к API

Если вы предпочитаете тестировать API через консоль, ниже приведены примеры запросов с помощью утилиты `cURL`.

### 1. Получение списка всех задач
*   **Метод:** `GET`
*   **URL:** `/api/tasks`

```bash
curl -X GET http://localhost:8080/api/tasks \
  -H "Content-Type: application/json"
```

### 2. Создание новой задачи
*   **Метод:** `POST`
*   **URL:** `/api/tasks`
*   **Параметры (JSON):** `title` (обязательное), `description`, `status`

```bash
curl -X POST http://localhost:8080/api/tasks \
  -H "Content-Type: application/json" \
  -d '{"title": "Изучить Docker", "description": "Развернуть проект локально", "status": "pending"}'
```

### 3. Обновление существующей задачи
*   **Метод:** `PUT`
*   **URL:** `/api/tasks/{id}`

```bash
curl -X PUT http://localhost:8080/api/tasks/1 \
  -H "Content-Type: application/json" \
  -d '{"title": "Изучить Docker (Обновлено)", "status": "completed"}'
```

### 4. Удаление задачи
*   **Метод:** `DELETE`
*   **URL:** `/api/tasks/{id}`

```bash
curl -X DELETE http://localhost:8080/api/tasks/1
```
