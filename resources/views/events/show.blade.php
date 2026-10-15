@extends('layouts.app')

@section('title', $event->name.': Антракт')
@section('nav_catalog', 'is-active')

@section('content')
    <a class="back-link" href="{{ route('events.index') }}">Вся афиша</a>

    <div class="event">
        <div class="event__main">
            <div class="art art--{{ $event->category }} event__art">
                <span class="ticket__chip">{{ $event->category_label }}</span>
                <span class="ticket__day">{{ $event->day }}</span>
                <span class="ticket__month">{{ $event->month_label }}</span>
            </div>

            <h1 class="event__title" id="pageTitle">{{ $event->name }}</h1>

            <dl class="facts">
                <div>
                    <dt>Когда</dt>
                    <dd>{{ $event->date_long }}</dd>
                </div>
                <div>
                    <dt>Где</dt>
                    <dd>{{ $event->venue }}, {{ $event->city }}</dd>
                </div>
                <div>
                    <dt>Цена билета</dt>
                    <dd>{{ $event->price_label }}</dd>
                </div>
            </dl>

            @if ($event->description)
                <p class="prose">{{ $event->description }}</p>
            @endif
        </div>

        <aside class="buy" aria-label="Заказ билетов">
            @if ($event->is_past)
                <div class="buy__closed">
                    <h2 class="buy__title">Событие уже прошло</h2>
                    <p class="buy__note">Билеты не продаются. Посмотрите ближайшие события в афише.</p>
                    <a class="btn btn--ink" href="{{ route('events.index') }}">Открыть афишу</a>
                </div>
            @elseif ($event->is_sold_out)
                <div class="buy__closed">
                    <h2 class="buy__title">Билеты закончились</h2>
                    <p class="buy__note">На это событие всё распродано. Посмотрите похожие события ниже.</p>
                    <a class="btn btn--ink" href="{{ route('events.index') }}">Открыть афишу</a>
                </div>
            @else
                <form id="orderForm" novalidate
                      data-url="{{ route('events.order', $event) }}"
                      data-price="{{ $event->price }}"
                      data-left="{{ $event->tickets_left }}">
                    <div class="buy__top">
                        <h2 class="buy__title">Купить билеты</h2>

                        <div class="qty">
                            <span class="qty__label" id="qtyLabel">Количество</span>
                            <div class="qty__ctrl" role="group" aria-labelledby="qtyLabel">
                                <button type="button" class="qty__btn" data-step="-1" aria-label="Меньше">−</button>
                                <output class="qty__value" id="qtyValue" aria-live="polite">1</output>
                                <button type="button" class="qty__btn" data-step="1" aria-label="Больше">+</button>
                            </div>
                        </div>

                        <div class="field" data-field="name">
                            <label class="field__label" for="buyerName">Имя</label>
                            <input id="buyerName" name="name" type="text" autocomplete="name" maxlength="80">
                            <p class="field__error" data-error-for="name"></p>
                        </div>

                        <div class="field" data-field="email">
                            <label class="field__label" for="buyerEmail">Email</label>
                            <input id="buyerEmail" name="email" type="email" autocomplete="email" maxlength="120">
                            <span class="field__hint">На него придёт код заказа</span>
                            <p class="field__error" data-error-for="email"></p>
                        </div>
                    </div>

                    <div class="ticket__tear" aria-hidden="true"></div>

                    <div class="buy__bottom">
                        <div class="buy__total">
                            <span>Итого</span>
                            <strong id="total">{{ $event->price_label }}</strong>
                        </div>
                        <button class="btn btn--gold btn--wide" type="submit" id="submitBtn">Купить билеты</button>
                        <p class="form-status" id="status" role="status"></p>
                        <p class="buy__note" id="leftNote">{{ $event->left_label }}. В одном заказе до 6 билетов.</p>
                    </div>
                </form>
            @endif
        </aside>
    </div>

    @if ($related->count() > 0)
        <section class="related" aria-label="Похожие события">
            <h2>Ещё в категории «{{ $event->category_label }}»</h2>
            <div class="grid">
                @foreach ($related as $item)
                    @include('events._card', ['event' => $item])
                @endforeach
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/order.js') }}"></script>
@endpush
