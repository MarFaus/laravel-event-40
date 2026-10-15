<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Антракт: билеты на события Костаная')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700&family=Unbounded:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/antrakt.css') }}">
    @stack('head')
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>
    <header class="site-header">
        <div class="wrap site-header__row">
            <a class="logo" href="{{ route('events.index') }}">
                <svg viewBox="0 0 26 18" aria-hidden="true"><path d="M3 0h20a3 3 0 0 1 3 3v3.2a2.8 2.8 0 0 0 0 5.6V15a3 3 0 0 1-3 3H3a3 3 0 0 1-3-3v-3.2a2.8 2.8 0 0 0 0-5.6V3a3 3 0 0 1 3-3z"/></svg>
                Антракт
            </a>
            <nav class="nav" aria-label="Основное меню">
                <a class="nav__link @yield('nav_catalog')" href="{{ route('events.index') }}">Афиша</a>
                <a class="nav__link @yield('nav_tickets')" href="{{ route('tickets.index') }}">Мои билеты</a>
                <a class="nav__link @yield('nav_api')" href="{{ route('events.api') }}">Через API</a>
                <a class="nav__cta" href="{{ route('events.create') }}">Добавить событие</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="wrap">
            @yield('content')
        </div>
    </main>

    <footer class="site-footer">
        <div class="wrap">
            <span>Антракт. Афиша и билеты Костаная.</span>
            <span>Учебный проект: оплата не подключена, заказы сохраняются в базе данных.</span>
        </div>
    </footer>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
