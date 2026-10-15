# Антракт: афиша и продажа билетов (Laravel 12)

Проект по практической работе «Интерактивная страница мероприятий в Laravel»,
доведённый до рабочей платформы: каталог, страница события, заказ билетов,
«Мои билеты», добавление событий.

## Запуск (Windows, PowerShell)

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item database\database.sqlite -ItemType File -Force
php artisan migrate:fresh --seed
php artisan serve
```

Открыть http://127.0.0.1:8000. База SQLite, настраивать ничего не нужно.
`npm install` не требуется: стили и скрипты лежат в `public/`.

## Страницы

| Адрес | Что это |
|---|---|
| `/events` | Афиша: ближайшее событие крупно, поиск, категории, сортировка, пагинация |
| `/events/{id}` | Событие: описание, выбор количества, заказ билетов |
| `/tickets` | Мои билеты (заказы этого браузера), поиск билета по коду |
| `/tickets/{код}` | Страница билета с кодом заказа и печатью |
| `/events/create` | Добавить событие |
| `/lab` | Задания 2–5 практической работы (данные из массива, `events.js`) |
| `/events/data` | JSON для `fetch()` (теория 2.8) |
| `/events-api` | Практическая работа по API: карточки из JSON, состояния загрузки и ошибки, каталог из базы |
| `/api/events` | JSON из массива (задания 2 и 6): `?limit=2` два мероприятия, `?limit=0` пусто, `?fail=1` ошибка 500 (только при `APP_DEBUG=true`) |
| `/api/events/catalog` | JSON из таблицы `events`: `search`, `page`, `per_page` (1–50, по умолчанию 20), `all=1` |

## API (практическая работа «Получение и отображение мероприятий через API»)

- `routes/api.php` подключён в `bootstrap/app.php` (то же, что делает `php artisan install:api`,
  но без Laravel Sanctum, он для этой работы не нужен). Проверка: `php artisan route:list --path=api`.
- `app/Http/Controllers/Api/EventController.php`: `index()` отдаёт массив из трёх мероприятий,
  `catalog()` отдаёт данные из модели `Event` с поиском, пагинацией и замером времени запроса.
- Страница `/events-api`: `public/js/events-api.js` (задания 3–6) и `events-catalog-api.js`
  (поиск, пагинация, сравнение «все записи или первые 20»).
- Открытое API ограничено 60 запросами в минуту с одного адреса (`throttle:60,1`).
- Ответы на контрольные вопросы: `ANSWERS-api-kontrolnye-voprosy.md`.

## Как устроено

- Поиск, фильтры и сортировка выполняются в SQL, в браузер приходит одна страница.
  Живой поиск без перезагрузки (`public/js/catalog.js`) запрашивает ту же страницу
  с debounce и подменяет список.
- Заказ: `EventController@order` проверяет данные, внутри транзакции блокирует
  строку события, проверяет остаток билетов и дату, уменьшает `tickets_left`
  и создаёт заказ. Кнопки и сумма в браузере нужны только для удобства.
- CSRF-токен берётся из `<meta name="csrf-token">` и передаётся заголовком
  `X-CSRF-TOKEN` (`public/js/app.js`).
- Поиск идёт по столбцу `search_index` в нижнем регистре: SQLite не умеет
  сравнивать кириллицу без учёта регистра.
- Даты показываются по Костанаю (`Asia/Almaty`), в базе хранятся в часовом поясе приложения.

## Структура

```
app/Models/Event.php, Order.php
app/Http/Controllers/EventController.php, TicketController.php
database/migrations/  events, orders
database/seeders/EventSeeder.php   14 событий (3 из задания) с датами от сегодняшнего дня
resources/views/layouts/app.blade.php
resources/views/events/   index, show, create, _card, lab
resources/views/tickets/  index, show, _order
public/css/antrakt.css
public/js/  app.js, catalog.js, order.js, create.js, events.js (задания 2–5), events-api.js, events-catalog-api.js (API)
tests/Feature/EventPageTest.php, ApiEventTest.php
routes/api.php, app/Http/Controllers/Api/EventController.php
resources/views/events/api.blade.php, public/css/api.css
ANSWERS-kontrolnye-voprosy.md      ответы на контрольные вопросы (JavaScript)
ANSWERS-api-kontrolnye-voprosy.md  ответы на контрольные вопросы (API)
```

## Ограничения

- Оплата не подключена: заказ создаётся сразу. Для реальной продажи нужен платёжный шлюз.
- Нет входа в систему: «Мои билеты» привязаны к сессии браузера, билет с другого
  устройства открывается по коду. Добавлять события может любой посетитель.
- Для PostgreSQL раскомментируйте блок `DB_CONNECTION=pgsql` в `.env`.

## Тесты

```powershell
php artisan test
```
