@extends('layouts.app')

@section('title', 'Билет '.$order->code.': Антракт')
@section('nav_tickets', 'is-active')

@section('content')
    @if ($isNew)
        <div class="banner" role="status">
            Заказ оформлен. Сохраните код {{ $order->code }}.
            <span>Покажите его на входе. Все заказы этого браузера собраны в разделе «Мои билеты».</span>
        </div>
    @endif

    <div class="page-head">
        <h1 id="pageTitle">Ваш билет</h1>
    </div>

    <div class="orders" style="margin-top: 24px;">
        @include('tickets._order', ['order' => $order])
    </div>

    <div class="actions no-print">
        <button class="btn btn--ink" type="button" data-print>Распечатать</button>
        <a class="btn btn--ghost" href="{{ route('events.index') }}">Вернуться в афишу</a>
    </div>
@endsection
