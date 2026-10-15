@extends('layouts.app')

@section('title', 'Мои билеты: Антракт')
@section('nav_tickets', 'is-active')

@section('content')
    <div class="page-head">
        <h1 id="pageTitle">Мои билеты</h1>
    </div>
    <p class="lead">Здесь заказы, оформленные в этом браузере. Если открываете сайт с другого устройства, найдите билет по коду.</p>

    <form class="lookup" action="{{ route('tickets.find') }}" method="GET">
        <div class="field {{ $errors->first('code') ? 'has-error' : '' }}">
            <label class="visually-hidden" for="code">Код заказа</label>
            <input id="code" name="code" type="text" placeholder="Код заказа, например AN-7K2M9Q" autocomplete="off">
            <p class="field__error">{{ $errors->first('code') }}</p>
        </div>
        <button class="btn btn--ink" type="submit">Открыть билет</button>
    </form>

    @if ($orders->count() > 0)
        <div class="orders">
            @foreach ($orders as $order)
                @include('tickets._order', ['order' => $order, 'showLink' => true])
            @endforeach
        </div>
    @else
        <div class="empty">
            <h2>Пока нет заказов</h2>
            <p>Выберите событие в афише и оформите заказ. Билет появится здесь и придёт с кодом для входа.</p>
            <a class="btn btn--gold" href="{{ route('events.index') }}">Открыть афишу</a>
        </div>
    @endif
@endsection
