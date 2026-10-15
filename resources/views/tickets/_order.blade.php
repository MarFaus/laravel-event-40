{{-- Широкий билет заказа. Используется в списке и на странице билета. --}}
<article class="ticket ticket--wide">
    <div class="art art--{{ $order->event->category }}">
        <span class="ticket__chip">{{ $order->event->category_label }}</span>
        <span class="ticket__day">{{ $order->event->day }}</span>
        <span class="ticket__month">{{ $order->event->month_label }}</span>
    </div>

    <div class="ticket__body">
        <h2 class="ticket__title">
            <a href="{{ route('events.show', $order->event) }}">{{ $order->event->name }}</a>
        </h2>
        <p class="ticket__meta ticket__meta--when">{{ $order->event->date_long }}</p>
        <p class="ticket__meta">{{ $order->event->venue }}, {{ $order->event->city }}</p>
        <p class="ticket__meta" style="margin-top: 14px;">
            {{ $order->quantity_label }} на имя {{ $order->buyer_name }}
        </p>
        <p class="ticket__meta">Итого {{ $order->total_label }}</p>
    </div>

    <div class="ticket__tear" aria-hidden="true"></div>

    <div class="ticket__stub">
        <div>
            <span class="ticket__code-label">Код заказа</span>
            <span class="ticket__code">{{ $order->code }}</span>
        </div>
        @if ($showLink ?? false)
            <a class="btn btn--ink btn--small" href="{{ route('tickets.show', $order->code) }}">Открыть</a>
        @endif
    </div>
</article>
