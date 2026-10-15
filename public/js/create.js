/**
 * Форма «Добавить событие»: проверка в браузере и отправка POST с CSRF-токеном.
 * Окончательная проверка выполняется на сервере в EventController@store.
 */
(function () {
    'use strict';

    const form = document.querySelector('#createForm');

    if (!form) {
        return;
    }

    const status = form.querySelector('#status');
    const submit = form.querySelector('#submitBtn');

    function field(name) {
        return form.elements[name];
    }

    function validate() {
        const errors = {};

        if (field('name').value.trim().length < 3) {
            errors.name = ['Укажите название, минимум 3 символа.'];
        }

        if (field('venue').value.trim().length < 2) {
            errors.venue = ['Укажите место проведения.'];
        }

        if (field('starts_at').value === '') {
            errors.starts_at = ['Укажите дату и время начала.'];
        }

        const price = field('price').value.trim();

        if (price === '' || Number(price) < 0 || !Number.isInteger(Number(price))) {
            errors.price = ['Укажите цену целым числом, для бесплатного входа 0.'];
        }

        const total = field('tickets_total').value.trim();

        if (total === '' || Number(total) < 1 || !Number.isInteger(Number(total))) {
            errors.tickets_total = ['Укажите количество билетов, минимум 1.'];
        }

        return errors;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        Antrakt.setStatus(status, '', null);

        const errors = validate();

        Antrakt.showErrors(form, errors);

        if (Object.keys(errors).length > 0) {
            const first = form.querySelector('.has-error input, .has-error select');

            if (first) {
                first.focus();
            }

            return;
        }

        submit.disabled = true;
        Antrakt.setStatus(status, 'Публикуем...', null);

        const payload = {};

        ['name', 'category', 'venue', 'city', 'starts_at', 'price', 'tickets_total', 'description']
            .forEach(function (name) {
                payload[name] = field(name).value.trim();
            });

        payload.price = Number(payload.price);
        payload.tickets_total = Number(payload.tickets_total);

        try {
            const result = await Antrakt.postJson(form.dataset.url, payload);

            if (result.status === 201) {
                Antrakt.setStatus(status, 'Мероприятие опубликовано.', 'ok');
                window.location.href = result.data.url;

                return;
            }

            if (result.status === 422) {
                Antrakt.showErrors(form, result.data.errors);
                Antrakt.setStatus(status, 'Проверьте поля формы.', 'error');
            } else if (result.status === 419) {
                Antrakt.setStatus(status, 'Страница устарела. Обновите её и повторите.', 'error');
            } else {
                Antrakt.setStatus(status, 'Не удалось опубликовать. Попробуйте ещё раз.', 'error');
            }
        } catch (error) {
            Antrakt.setStatus(status, 'Нет связи с сервером. Проверьте интернет и повторите.', 'error');
        } finally {
            submit.disabled = false;
        }
    });
})();
