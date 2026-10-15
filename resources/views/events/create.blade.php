@extends('layouts.app')

@section('title', 'Добавить событие: Антракт')

@section('content')
    <div class="page-head">
        <h1 id="pageTitle">Добавить событие</h1>
    </div>
    <p class="lead">
        Заполните данные, и событие сразу появится в афише. В учебной версии вход не нужен.
    </p>

    <form class="form-card" id="createForm" novalidate data-url="{{ route('events.store') }}" style="margin-top: 28px;">
        <div class="form-grid">
            <div class="field field--full" data-field="name">
                <label class="field__label" for="name">Название</label>
                <input id="name" name="name" type="text" maxlength="120">
                <p class="field__error" data-error-for="name"></p>
            </div>

            <div class="field" data-field="category">
                <label class="field__label" for="category">Категория</label>
                <select id="category" name="category">
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="field__error" data-error-for="category"></p>
            </div>

            <div class="field" data-field="starts_at">
                <label class="field__label" for="starts_at">Начало</label>
                <input id="starts_at" name="starts_at" type="datetime-local">
                <span class="field__hint">Время по Костанаю</span>
                <p class="field__error" data-error-for="starts_at"></p>
            </div>

            <div class="field" data-field="venue">
                <label class="field__label" for="venue">Место</label>
                <input id="venue" name="venue" type="text" maxlength="120">
                <p class="field__error" data-error-for="venue"></p>
            </div>

            <div class="field" data-field="city">
                <label class="field__label" for="city">Город</label>
                <input id="city" name="city" type="text" maxlength="60" value="Костанай">
                <p class="field__error" data-error-for="city"></p>
            </div>

            <div class="field" data-field="price">
                <label class="field__label" for="price">Цена билета, ₸</label>
                <input id="price" name="price" type="number" min="0" step="100" inputmode="numeric">
                <span class="field__hint">Для бесплатного входа укажите 0</span>
                <p class="field__error" data-error-for="price"></p>
            </div>

            <div class="field" data-field="tickets_total">
                <label class="field__label" for="tickets_total">Количество билетов</label>
                <input id="tickets_total" name="tickets_total" type="number" min="1" step="1" inputmode="numeric">
                <p class="field__error" data-error-for="tickets_total"></p>
            </div>

            <div class="field field--full" data-field="description">
                <label class="field__label" for="description">Описание</label>
                <textarea id="description" name="description" maxlength="2000"></textarea>
                <span class="field__hint">Что будет, сколько длится, что взять с собой</span>
                <p class="field__error" data-error-for="description"></p>
            </div>
        </div>

        <div class="actions">
            <button class="btn btn--gold" type="submit" id="submitBtn">Опубликовать</button>
            <a class="btn btn--ghost" href="{{ route('events.index') }}">Отмена</a>
        </div>
        <p class="form-status" id="status" role="status" style="margin-top: 14px;"></p>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/create.js') }}"></script>
@endpush
