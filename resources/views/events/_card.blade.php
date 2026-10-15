{{-- Билет мероприятия. Параметр $feature делает билет крупным и добавляет обратный отсчёт. --}}
<article class="ticket {{ ($feature ?? false) ? 'ticket--feature' : '' }}" data-event-id="{{ $event->id }}">
    <a class="ticket__art art art--{{ $event->category }}" href="{{ route('events.show', $event) }}" tabindex="-1" aria-hidden="true">
        <span class="ticket__chip">{{ $event->category_label }}</span>
        <span class="ticket__day">{{ $event->day }}</span>
        <span class="ticket__month">{{ $event->month_label }}</span>
    </a>

    <div class="ticket__body">
        <h3 class="ticket__title">
            <a href="{{ route('events.show', $event) }}">{{ $event->name }}</a>
        </h3>
        <p class="ticket__meta ticket__meta--when">{{ $event->weekday_label }}, {{ $event->time_label }}</p>
        <p class="ticket__meta">{{ $event->venue }}</p>
        @if ($feature ?? false)
            <p class="ticket__countdown" data-countdown="{{ $event->iso }}"></p>
        @endif
    </div>

    <div class="ticket__tear" aria-hidden="true"></div>

    <div class="ticket__stub">
        <div class="ticket__price">
            {{ $event->price_label }}
            <span class="ticket__left {{ $event->is_low ? 'is-low' : '' }}">{{ $event->left_label }}</span>
        </div>
        @if ($event->is_sold_out)
            <span class="btn btn--disabled" aria-disabled="true">Нет билетов</span>
        @else
            <a class="btn btn--gold" href="{{ route('events.show', $event) }}">Купить</a>
        @endif
    </div>
</article>
