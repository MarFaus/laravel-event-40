/**
 * Страница мероприятия: количество билетов, итоговая сумма и отправка заказа.
 *
 * Проверки в браузере нужны для удобства. Наличие билетов, цену и допустимое
 * количество окончательно проверяет сервер в EventController@order.
 */
(function () {
    'use strict';

    const form = document.querySelector('#orderForm');

    if (!form) {
        return;
    }

    const MAX_PER_ORDER = 6;

    const price = Number(form.dataset.price);
    let left = Number(form.dataset.left);
    let quantity = 1;

    const value = form.querySelector('#qtyValue');
    const total = form.querySelector('#total');
    const note = form.querySelector('#leftNote');
    const status = form.querySelector('#status');
    const submit = form.querySelector('#submitBtn');
    const minus = form.querySelector('[data-step="-1"]');
    const plus = form.querySelector('[data-step="1"]');
    const nameInput = form.querySelector('#buyerName');
    const emailInput = form.querySelector('#buyerEmail');

    function limit() {
        return Math.max(1, Math.min(MAX_PER_ORDER, left));
    }

    function render() {
        quantity = Math.min(Math.max(quantity, 1), limit());

        value.textContent = quantity;
        total.textContent = Antrakt.formatPrice(price * quantity);
        minus.disabled = quantity <= 1;
        plus.disabled = quantity >= limit();
        note.textContent = (left > 0 ? 'Осталось ' + left : 'Билеты закончились') +
            '. В одном заказе до ' + MAX_PER_ORDER + ' билетов.';
    }

    form.querySelectorAll('[data-step]').forEach(function (button) {
        button.addEventListener('click', function () {
            quantity += Number(button.dataset.step);
            render();
        });
    });

    function validate() {
        const errors = {};

        if (nameInput.value.trim().length < 2) {
            errors.name = ['Укажите имя, минимум 2 символа.'];
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
            errors.email = ['Проверьте email: в адресе есть ошибка.'];
        }

        return errors;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        Antrakt.setStatus(status, '', null);

        const errors = validate();

        Antrakt.showErrors(form, errors);

        if (Object.keys(errors).length > 0) {
            const first = form.querySelector('.has-error input');

            if (first) {
                first.focus();
            }

            return;
        }

        submit.disabled = true;
        Antrakt.setStatus(status, 'Оформляем заказ...', null);

        try {
            const result = await Antrakt.postJson(form.dataset.url, {
                name: nameInput.value.trim(),
                email: emailInput.value.trim(),
                quantity: quantity,
            });

            if (result.status === 201) {
                Antrakt.setStatus(status, 'Заказ оформлен. Открываем билет...', 'ok');
                window.location.href = result.data.url;

                return;
            }

            if (result.status === 422) {
                Antrakt.showErrors(form, result.data.errors);
                Antrakt.setStatus(status, 'Проверьте поля формы.', 'error');
            } else if (result.status === 409) {
                left = Number(result.data.left || 0);
                render();
                Antrakt.setStatus(status, result.data.message, 'error');
            } else if (result.status === 419) {
                Antrakt.setStatus(status, 'Страница устарела. Обновите её и повторите заказ.', 'error');
            } else {
                Antrakt.setStatus(status, 'Не удалось оформить заказ. Попробуйте ещё раз.', 'error');
            }
        } catch (error) {
            Antrakt.setStatus(status, 'Нет связи с сервером. Проверьте интернет и повторите.', 'error');
        } finally {
            submit.disabled = false;
        }
    });

    render();
})();
