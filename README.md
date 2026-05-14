## 🚀 Быстрый старт (Запуск в Docker)

### 1. Сборка и запуск контейнеров
Убедитесь, что у вас установлен Docker и Docker Compose. Выполните команду в терминале для сборки и запуска проекта в фоновом режиме:

```bash
docker compose up -d --build
```
*После этого приложение станет доступно по адресу: `http://localhost:8080` (или порту, указанному в вашем `compose.yaml`).*

### 2. Установка зависимостей
Установите необходимые пакеты PHP внутри запущенного контейнера:

```bash
docker compose exec php composer install
```

---

## 🗄️ Настройка базы данных и миграции

После запуска контейнеров необходимо создать структуру таблиц базы данных. Выполните по очереди две команды:

1. **Создание базы данных** (если она не создалась автоматически):
   ```bash
   docker compose exec php bin/console doctrine:database:create --if-not-exists
   ```

2. **Запуск миграций**:
   ```bash
   docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
   ```

---

## 🧪 Запуск тестов (PHPUnit)

Проект содержит юнит-тесты для сущностей и функциональные тесты для проверки CRUD-операций контроллера. Для запуска всех тестов выполните команду:

```bash
docker compose exec php vendor/bin/phpunit
```

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
