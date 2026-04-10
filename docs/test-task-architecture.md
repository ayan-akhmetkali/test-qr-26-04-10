# Тестовое задание: «Сервис коротких ссылок + QR» (без кода)

## 1) Цель и границы решения

Собрать **Yii2 Basic**-проект с фронтом на **jQuery + Bootstrap** и БД **MySQL/MariaDB**, который:
- принимает URL на главной странице;
- валидирует формат URL и доступность ресурса;
- при успехе сохраняет URL, генерирует короткий код и QR;
- возвращает результат через Ajax без перезагрузки;
- по короткой ссылке делает редирект на исходный URL;
- логирует внешние IP переходов и ведёт счётчик кликов.

Ограничение: не использовать API сторонних сервисов.

---

## 2) Архитектурный подход (чтобы показать глубину проработки)

Чтобы не «склеивать» всё в контроллере и не дублировать код, разделяем ответственность на слои:

1. **Controller layer**
   - принимает HTTP/Ajax-запросы;
   - отдаёт JSON/HTML;
   - не содержит бизнес-логики.

2. **Application service layer**
   - `ShortLinkService` — orchestration сценария “validate -> check availability -> create short link -> generate QR payload”;
   - `RedirectService` — поиск короткого кода, инкремент счётчика, логирование перехода, получение target URL.

3. **Domain/model layer**
   - сущности (`Link`, `ClickLog`) и правила валидации;
   - value object для нормализованного URL (опционально).

4. **Infrastructure layer**
   - генератор короткого кода;
   - компонент проверки доступности URL;
   - компонент генерации QR (локальная библиотека, без внешнего API);
   - репозитории (или ActiveRecord + выделенные query-методы).

Такой дизайн демонстрирует понимание инструментария и снимает риск замечания «использованы готовые решения без архитектуры».

### 2.1 Что **не** делаем принципиально (анти-дублирование)

- **Не используем Smarty**: в Yii2 Basic штатный view-слой уже закрывает задачи шаблонизации, а добавление Smarty здесь создаёт лишний слой абстракции и дублирование возможностей.
- Не пишем «самодельный роутер», «самодельный шаблонизатор» и «самодельный ORM»: используем нативные механики Yii2 (URL rules, view rendering, ActiveRecord/QueryBuilder).
- Не дублируем валидацию одновременно в нескольких местах: canonical validation только в model/service, фронт делает лишь UX-подсказки.
- Не храним бизнес-логику в JS и контроллерах: orchestration в сервисах, контроллеры — thin layer.

Это важный акцент для тимлида: мы не «переизобретаем» то, что фреймворк уже решает, а проектируем то, что относится к доменной задаче.

---

## 3) Потоки (use-cases)

## 3.1 Создание короткой ссылки (Ajax)
1. Пользователь вводит URL и жмёт “OK”.
2. JS отправляет `POST /link/create` (Ajax, JSON).
3. Сервер:
   - валидирует URL (схема `http|https`, корректный host);
   - проверяет доступность (HEAD/GET с timeout и ограничением redirect).
4. Если URL недоступен — вернуть ошибку: **«Данный URL не доступен»**.
5. Если успех:
   - сохраняем запись в `links`;
   - генерируем уникальный `short_code`;
   - формируем short URL;
   - формируем QR-изображение (png/svg) локально.
6. Ajax-ответ: `{ success, short_url, qr_url }`.
7. На странице без reload показываем ссылку + QR.

## 3.2 Переход по короткой ссылке
1. Запрос `GET /r/{code}`.
2. Ищем активную ссылку по `code`.
3. Если не найдено — 404.
4. Если найдено:
   - пишем лог перехода (внешний IP, user-agent, referer, timestamp);
   - увеличиваем счётчик переходов (`click_count`);
   - отдаём `302` на оригинальный URL.

---

## 4) Структура БД (предложение)

## Таблица `links`
- `id` BIGINT PK
- `original_url` TEXT NOT NULL
- `normalized_url` VARCHAR(2048) NOT NULL
- `short_code` VARCHAR(16) NOT NULL UNIQUE
- `click_count` BIGINT NOT NULL DEFAULT 0
- `status` TINYINT NOT NULL DEFAULT 1
- `created_at` DATETIME NOT NULL
- `updated_at` DATETIME NULL

Индексы:
- UNIQUE `ux_links_short_code(short_code)`
- INDEX `ix_links_created_at(created_at)`

## Таблица `link_click_logs`
- `id` BIGINT PK
- `link_id` BIGINT NOT NULL
- `ip` VARCHAR(45) NOT NULL (IPv4/IPv6)
- `user_agent` VARCHAR(1024) NULL
- `referer` VARCHAR(2048) NULL
- `created_at` DATETIME NOT NULL

Индексы:
- INDEX `ix_logs_link_id_created_at(link_id, created_at)`
- INDEX `ix_logs_ip(ip)`

FK:
- `link_click_logs.link_id -> links.id` (ON DELETE CASCADE)

Почему так:
- быстрый редирект по `short_code`;
- отдельная таблица логов не раздувает основную таблицу;
- `click_count` хранится денормализовано для быстрых отчётов.

---

## 5) Валидация URL и проверка доступности

### 5.1 Валидность
- принимаем только `http://` и `https://`;
- отклоняем пустые/сломанные URL;
- проверяем host, длину и потенциально небезопасные схемы.

### 5.2 Доступность
- серверный HTTP-клиент (cURL/stream context), без сторонних API;
- timeout (например 3–5 сек);
- ограничение redirect depth (например до 3);
- критерий успеха: итоговый статус 2xx/3xx;
- при сетевой ошибке или 4xx/5xx — «Данный URL не доступен».

Важно: проверку делать асинхронной на уровне UX (Ajax), но синхронной по серверной логике в рамках запроса.

---

## 6) Генерация short code

Рекомендуемая стратегия:
- использовать криптостойкий random-bytes -> base62/base64url строка 6–10 символов;
- проверить уникальность в БД;
- при коллизии повторить генерацию (loop с ограничением попыток).

Почему не просто hash URL:
- одинаковые URL могут требовать разные записи/сроки жизни;
- hash проще предсказать, random code безопаснее.

---

## 7) QR-код (без внешнего API)

- использовать локальную PHP-библиотеку для QR;
- хранить QR как файл (`/web/uploads/qr/...png`) **или** отдавать как data URI;
- в ответе Ajax вернуть путь до QR.

Минимум для UX:
- показать QR + short URL;
- кнопка копирования short URL.

---

## 8) Логирование IP и счётчик

При каждом `GET /r/{code}`:
1. получить внешний IP (учитывая прокси-заголовки только если trusted proxy настроен);
2. вставить запись в `link_click_logs`;
3. атомарно увеличить `links.click_count = click_count + 1`.

Чтобы избежать гонок — инкремент SQL-выражением, не read-modify-write в PHP.

---

## 9) Endpoint-контракт (черновик)

- `GET /` — страница с формой.
- `POST /link/create` (Ajax JSON):
  - request: `{ "url": "https://example.com" }`
  - success: `{ "success": true, "short_url": "https://host/r/Ab3k9Q", "qr_url": "/uploads/qr/Ab3k9Q.png" }`
  - error: `{ "success": false, "message": "Данный URL не доступен" }` или валидационная ошибка.
- `GET /r/{code}` — редирект.

---

## 10) Нефункциональные требования

- CSRF для формы/Ajax.
- Rate limit на создание ссылок (минимальный анти-спам).
- Ограничение длины URL.
- Логирование ошибок сети и генерации QR.
- Единый формат ошибок для фронта.
- Базовые unit/integration тесты:
  - URL validator;
  - code generator collision;
  - redirect increments counter and writes log.

### 10.1 Набор решений «что берём из Yii2, что реализуем сами»

**Берём из Yii2 (чтобы не дублировать):**
- Router + URL manager;
- Controller/action lifecycle;
- View rendering;
- Request/Response/JSON formatting;
- Validation rules/Model scenarios;
- DB migrations;
- AR/Query Builder;
- Error handler + logging.

**Реализуем сами (потому что это доменная логика):**
- политика генерации short code;
- сценарий проверки доступности URL с ограничениями;
- правила логирования переходов и инкремента счётчика;
- формат ответа для фронтового виджета short URL + QR.

---

## 11) План реализации (этапы)

1. Инициализация Yii2 Basic, конфиги окружений.
2. Миграции `links`, `link_click_logs`.
3. AR-модели + rules (фокус на DRY/SOLID/KISS):
   - `Link` и `LinkClickLog` как тонкий слой доступа к данным (single responsibility);
   - правила валидации и связи описаны в AR, без дублирования в контроллерах;
   - переиспользование встроенных валидаторов Yii2 (`url`, `ip`, `exist`, `unique`) вместо самодельных проверок.
4. Сервисы (`ShortLinkService`, `RedirectService`) для orchestration, чтобы AR не превращались в «бог-объекты».
5. Контроллеры `SiteController` (форма) и `RedirectController`.
6. Ajax-frontend на jQuery + Bootstrap view.
7. Генерация QR локальной библиотекой.
8. Тесты + smoke-check.
9. README с шагами развёртывания.

---

## 12) Инструкция по развёртыванию (шаблон для README)

1. Требования:
   - PHP 8.1+;
   - Composer;
   - MySQL/MariaDB;
   - веб-сервер (Nginx/Apache) или `php yii serve`.

2. Установка:
   - `composer install`
   - настроить `config/db.php`
   - создать БД, выполнить миграции `php yii migrate`

3. Права:
   - дать права на директории runtime и (если нужно) uploads/qr.

4. Запуск:
   - `php yii serve --port=8080`
   - открыть `http://localhost:8080`

5. Проверка:
   - создать короткую ссылку через форму;
   - открыть short URL;
   - убедиться, что редирект работает, счётчик растёт, лог IP пишется.
