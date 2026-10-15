{{--
    Практическая работа «Получение и отображение мероприятий через API».

    Страница только размечает контейнеры. Данные приходят из /api/events
    и /api/events/catalog, а карточки создаёт JavaScript.
--}}
@extends('layouts.app')

@section('title', 'Афиша через API: Антракт')
@section('nav_api', 'is-active')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/api.css') }}">
@endpush

@section('content')
    <div class="page-head">
        <h1>Афиша через API</h1>
        <p class="page-head__note">Laravel отдаёт JSON, JavaScript рисует карточки</p>
    </div>
    <p class="lead">
        Эта страница не получает мероприятия в HTML. Браузер запрашивает их через
        <code>fetch()</code>, сервер возвращает чистые данные, а интерфейс сам решает, как их показать.
    </p>

    {{-- Задания 3–6: данные из массива --}}
    <section class="api-panel" aria-labelledby="api-basic-title">
        <div class="api-panel__head">
            <span class="api-tag">Задания 3–6</span>
            <h2 id="api-basic-title">Данные из массива</h2>
            <span class="api-endpoint"><b>GET</b> <span id="endpointLabel">/api/events</span></span>
        </div>

        <div class="api-toolbar">
            <button class="btn btn--gold" id="loadEvents" type="button">Загрузить мероприятия</button>
            <button class="btn btn--ghost" id="refreshEvents" type="button" disabled>Обновить список</button>

            <label class="field api-toolbar__mode">
                <span class="field__label">Режим проверки</span>
                <select id="mode">
                    <option value="">Обычный: три мероприятия</option>
                    <option value="limit=2">Только два мероприятия</option>
                    <option value="limit=0">Пустой ответ</option>
                    <option value="fail=1">Ошибка сервера (500)</option>
                </select>
                <span class="field__hint">Ошибку 500 сервер отдаёт только при APP_DEBUG=true.</span>
            </label>
        </div>

        <p id="message" class="form-status" role="status" aria-live="polite">Список не загружен. Нажмите кнопку.</p>
        <div id="events" class="api-list" aria-live="polite"></div>
        <p id="details" class="api-details" role="status" hidden></p>
    </section>

    {{-- Повышенная сложность: данные из таблицы events --}}
    <section class="api-panel" aria-labelledby="api-db-title">
        <div class="api-panel__head">
            <span class="api-tag api-tag--sky">Повышенная сложность</span>
            <h2 id="api-db-title">Данные из базы</h2>
            <span class="api-endpoint"><b>GET</b> /api/events/catalog</span>
        </div>

        <label class="field field--search api-search">
            <span class="visually-hidden">Поиск мероприятий</span>
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" id="apiSearch" placeholder="Название, место или категория" autocomplete="off">
        </label>

        <p id="catalogMessage" class="form-status" role="status" aria-live="polite"></p>
        <div id="catalog" class="api-list" aria-live="polite"></div>
        <nav id="catalogPager" class="pagination" aria-label="Страницы каталога"></nav>

        <div class="api-compare">
            <h3>Все записи или первые 20</h3>
            <p>
                Сравните размер ответа и время. На небольшой базе разница почти незаметна: чтобы увидеть её,
                добавьте записей командой <code>php artisan tinker --execute="App\Models\Event::factory()->count(500)->create();"</code>.
            </p>
            <button class="btn btn--ink btn--small" id="compare" type="button">Сравнить</button>
            <div id="compareResult" class="api-compare__result" aria-live="polite"></div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/events-api.js') }}"></script>
    <script src="{{ asset('js/events-catalog-api.js') }}"></script>
@endpush
