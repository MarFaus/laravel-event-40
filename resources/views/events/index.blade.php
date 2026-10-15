@extends('layouts.app')

@section('title', 'Афиша Костаная: Антракт')
@section('nav_catalog', 'is-active')

@section('content')
    @if ($featured)
        <section class="hero">
            <div class="hero__text">
                <h1 class="hero__title" id="pageTitle">Билеты на всё, что происходит в Костанае</h1>
                <p class="hero__lead">
                    Концерты, спектакли, фестивали и выставки. Выберите событие, оформите заказ
                    и покажите код на входе.
                </p>
                <div class="hero__actions">
                    <a class="btn btn--gold" href="#afisha">Смотреть афишу</a>
                    <a class="btn btn--ghost" href="{{ route('tickets.index') }}">Мои билеты</a>
                </div>
            </div>
            <div class="hero__ticket">
                @include('events._card', ['event' => $featured, 'feature' => true])
            </div>
        </section>
    @else
        <div class="page-head">
            <h1 id="pageTitle">Афиша</h1>
        </div>
    @endif

    <section id="afisha" class="catalog-head" aria-label="Афиша">
        <form class="filters" id="filters" action="{{ route('events.index') }}" method="GET" role="search">
            <label class="field field--search">
                <span class="visually-hidden">Поиск по названию, месту или категории</span>
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Название, место или категория" autocomplete="off">
            </label>
            @if ($category)
                <input type="hidden" name="category" value="{{ $category }}">
            @endif
            <label class="field">
                <span class="visually-hidden">Сортировка</span>
                <select id="sort" name="sort">
                    <option value="date" {{ $sort === 'date' ? 'selected' : '' }}>Сначала ближайшие</option>
                    <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Сначала дешёвые</option>
                    <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Сначала дорогие</option>
                </select>
            </label>
            <button class="btn btn--ink filters__submit" type="submit">Найти</button>
        </form>

        <nav class="chips" id="chips" aria-label="Категории">
            @foreach ($chips as $chip)
                <a class="chip {{ $chip['active'] ? 'is-active' : '' }}" href="{{ $chip['url'] }}" {{ $chip['active'] ? 'aria-current=page' : '' }}>
                    {{ $chip['label'] }}
                    <span class="chip__count">{{ $chip['count'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="grid" id="grid" aria-live="polite">
            @forelse ($events as $event)
                @include('events._card', ['event' => $event])
            @empty
                <div class="empty">
                    <h2>Ничего не найдено</h2>
                    <p>Измените запрос или выберите другую категорию. Возможно, подходящие события ещё не добавлены.</p>
                    <a class="btn btn--ink" href="{{ route('events.index') }}">Сбросить фильтры</a>
                </div>
            @endforelse
        </div>

        <div id="pager">{{ $events->links() }}</div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/catalog.js') }}"></script>
@endpush
