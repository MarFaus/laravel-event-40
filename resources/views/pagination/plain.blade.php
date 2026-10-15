{{--
    Оформление пагинации. Подключено по умолчанию в AppServiceProvider,
    поэтому в шаблоне достаточно вызвать {{ $events->links() }}.
--}}
@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Страницы">
        @if ($paginator->onFirstPage())
            <span class="pagination__item pagination__item--disabled">Назад</span>
        @else
            <a class="pagination__item" href="{{ $paginator->previousPageUrl() }}" rel="prev">Назад</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination__item pagination__item--disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination__item pagination__item--active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination__item" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pagination__item" href="{{ $paginator->nextPageUrl() }}" rel="next">Вперёд</a>
        @else
            <span class="pagination__item pagination__item--disabled">Вперёд</span>
        @endif
    </nav>
@endif
