{{--
    Задание 4. Страница афиши мероприятий.

    Blade выводит данные, полученные из контроллера (EventController@index),
    а JavaScript из public/js/events.js работает с уже сформированными
    HTML-элементами.
--}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Афиша мероприятий</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 30px auto;
            padding: 0 15px;
        }
        .event-card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
        }
        .message {
            font-weight: bold;
        }
        button {
            padding: 8px 12px;
            cursor: pointer;
            margin-right: 5px;
        }
        .nav {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1 id="pageTitle">Афиша мероприятий</h1>

    <button type="button" id="changeTitle">
        Изменить заголовок
    </button>

    <p>
        <input
            type="text"
            id="search"
            placeholder="Поиск мероприятий"
        >
    </p>

    <div id="events">
        @foreach ($events as $event)
            <div class="event-card" data-event-id="{{ $event['id'] }}">
                <h3>{{ $event['name'] }}</h3>
                <p>Цена: {{ $event['price'] }} ₸</p>
                <button type="button" class="buy-button">
                    Купить билет
                </button>
                <button type="button" class="hide-button">
                    Скрыть
                </button>
            </div>
        @endforeach
    </div>

    <p id="message" class="message"></p>

    <p class="nav">
        Готовая платформа:
        <a href="{{ route('events.index') }}">
            платформа «Антракт»: данные из базы, поиск на сервере, заказ билетов
        </a>
    </p>

    <script src="{{ asset('js/events.js') }}"></script>
</body>
</html>
