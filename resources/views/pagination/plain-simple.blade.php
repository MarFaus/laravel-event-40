{{--
    Оформление упрощённой пагинации (simplePaginate) — только «Назад» и «Вперёд».
--}}
@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Навигация по страницам">
        @if ($paginator->onFirstPage())
            <span class="pagination__item pagination__item--disabled">Назад</span>
        @else
            <a class="pagination__item" href="{{ $paginator->previousPageUrl() }}" rel="prev">Назад</a>
        @endif

        @if ($paginator->hasMorePages())
            <a class="pagination__item" href="{{ $paginator->nextPageUrl() }}" rel="next">Вперёд</a>
        @else
            <span class="pagination__item pagination__item--disabled">Вперёд</span>
        @endif
    </nav>
@endif
