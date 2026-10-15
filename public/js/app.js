/**
 * Общие функции сайта: CSRF-токен, POST-запросы, форматирование цены,
 * debounce и обратный отсчёт до начала мероприятия.
 */
(function () {
    'use strict';

    const meta = document.querySelector('meta[name="csrf-token"]');

    window.Antrakt = {
        csrf: meta ? meta.content : '',

        formatPrice: function (value) {
            return Number(value).toLocaleString('ru-RU') + ' ₸';
        },

        /**
         * POST с JSON и CSRF-токеном. Возвращает { ok, status, data }.
         */
        postJson: async function (url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                },
                body: JSON.stringify(payload),
            });

            let data = {};

            try {
                data = await response.json();
            } catch (error) {
                data = {};
            }

            return { ok: response.ok, status: response.status, data: data };
        },

        /**
         * Показывает ошибки валидации под полями формы.
         * Поля ищутся по атрибуту data-error-for.
         */
        showErrors: function (form, errors) {
            form.querySelectorAll('[data-field]').forEach(function (field) {
                field.classList.remove('has-error');
            });

            form.querySelectorAll('[data-error-for]').forEach(function (node) {
                node.textContent = '';
            });

            Object.keys(errors || {}).forEach(function (name) {
                const node = form.querySelector('[data-error-for="' + name + '"]');
                const field = form.querySelector('[data-field="' + name + '"]');

                if (node) {
                    node.textContent = errors[name][0];
                }

                if (field) {
                    field.classList.add('has-error');
                }
            });
        },

        setStatus: function (node, text, kind) {
            node.textContent = text;
            node.classList.toggle('is-error', kind === 'error');
            node.classList.toggle('is-ok', kind === 'ok');
        },

        /**
         * Debounce: функция вызывается только после паузы в вводе,
         * чтобы не отправлять запрос на каждый символ.
         */
        debounce: function (callback, delay) {
            let timeoutId = null;

            return function () {
                const args = arguments;

                clearTimeout(timeoutId);

                timeoutId = setTimeout(function () {
                    callback.apply(null, args);
                }, delay);
            };
        },
    };

    /* Обратный отсчёт до начала */

    function renderCountdown(node) {
        const diff = new Date(node.dataset.countdown).getTime() - Date.now();

        if (Number.isNaN(diff)) {
            return;
        }

        if (diff <= 0) {
            node.textContent = 'Уже началось';

            return;
        }

        const minutes = Math.floor(diff / 60000);
        const days = Math.floor(minutes / 1440);
        const hours = Math.floor((minutes % 1440) / 60);
        const rest = minutes % 60;

        if (days > 0) {
            node.textContent = 'Начало через ' + days + ' д ' + hours + ' ч';
        } else if (hours > 0) {
            node.textContent = 'Начало через ' + hours + ' ч ' + rest + ' мин';
        } else {
            node.textContent = 'Начало через ' + rest + ' мин';
        }
    }

    const countdowns = document.querySelectorAll('[data-countdown]');

    if (countdowns.length > 0) {
        const tick = function () {
            countdowns.forEach(renderCountdown);
        };

        tick();
        setInterval(tick, 30000);
    }

    /* Кнопка «Распечатать» */

    document.querySelectorAll('[data-print]').forEach(function (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    });
})();
