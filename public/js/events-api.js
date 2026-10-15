/**
 * Задания 3–6. Получение мероприятий из /api/events и вывод карточек.
 *
 * Последовательность: кнопка -> fetch() -> проверка response.ok ->
 * response.json() -> создание карточек -> сообщение о результате.
 * Состояния: загрузка, успех, пустой список, ошибка.
 *
 * Данные сервера выводятся через textContent, а не innerHTML,
 * поэтому разметка из названий мероприятий не выполнится.
 */
(function () {
    'use strict';

    const loadButton = document.querySelector('#loadEvents');
    const refreshButton = document.querySelector('#refreshEvents');
    const modeSelect = document.querySelector('#mode');
    const endpointLabel = document.querySelector('#endpointLabel');
    const eventsContainer = document.querySelector('#events');
    const message = document.querySelector('#message');
    const details = document.querySelector('#details');

    /**
     * Карточка мероприятия. Общая для двух блоков страницы.
     * options.href: «Подробнее» становится ссылкой; options.onDetails: кнопкой.
     */
    function createCard(event, options) {
        const settings = options || {};

        const card = document.createElement('article');
        card.classList.add('event-card');
        card.dataset.state = event.state || 'available';

        const title = document.createElement('h3');
        title.textContent = event.name;
        card.append(title);

        if (event.date_label) {
            const when = document.createElement('p');
            when.classList.add('event-card__when');
            when.textContent = event.date_label;
            card.append(when);
        }

        const price = document.createElement('p');
        price.classList.add('event-card__price');
        const priceLabel = document.createElement('span');
        priceLabel.textContent = 'Цена: ';
        const priceValue = document.createElement('strong');
        priceValue.textContent = window.Antrakt.formatPrice(event.price);
        price.append(priceLabel, priceValue);

        const status = document.createElement('p');
        status.classList.add('status');
        status.textContent = 'Статус: ' + event.status;

        const foot = document.createElement('div');
        foot.classList.add('event-card__foot');

        if (settings.href) {
            const link = document.createElement('a');
            link.className = 'btn btn--ghost btn--small';
            link.href = settings.href;
            link.textContent = 'Подробнее';
            foot.append(link);
        } else {
            const more = document.createElement('button');
            more.type = 'button';
            more.className = 'btn btn--ghost btn--small';
            more.textContent = 'Подробнее';
            more.addEventListener('click', function () {
                if (settings.onDetails) {
                    settings.onDetails(event, card);
                }
            });
            foot.append(more);
        }

        card.append(price, status, foot);

        return card;
    }

    window.AntraktApi = { createCard: createCard };

    if (!loadButton || !eventsContainer) {
        return;
    }

    let loading = false;
    let requested = false;

    function endpoint() {
        return '/api/events' + (modeSelect.value ? '?' + modeSelect.value : '');
    }

    /** Пока идёт запрос, кнопки заблокированы: повторные нажатия невозможны. */
    function setBusy(busy) {
        loading = busy;
        loadButton.disabled = busy;
        refreshButton.disabled = busy || !requested;
        modeSelect.disabled = busy;
        eventsContainer.setAttribute('aria-busy', busy ? 'true' : 'false');
    }

    function showSkeleton() {
        const items = [];

        for (let i = 0; i < 3; i++) {
            const card = document.createElement('div');
            card.className = 'event-card event-card--skeleton';
            card.setAttribute('aria-hidden', 'true');
            card.innerHTML = '<span></span><span></span><span></span>';
            items.push(card);
        }

        eventsContainer.replaceChildren(...items);
    }

    function showDetails(event, card) {
        eventsContainer.querySelectorAll('.is-selected').forEach(function (node) {
            node.classList.remove('is-selected');
        });

        card.classList.add('is-selected');
        details.hidden = false;
        details.textContent = 'Выбрано мероприятие «' + event.name + '». Идентификатор: ' + event.id + '.';
    }

    async function loadEvents() {
        if (loading) {
            return;
        }

        requested = true;
        setBusy(true);
        details.hidden = true;
        window.Antrakt.setStatus(message, 'Загрузка мероприятий...');
        showSkeleton();

        try {
            const response = await fetch(endpoint(), {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                const failure = new Error('Ошибка загрузки данных');
                failure.status = response.status;
                throw failure;
            }

            const events = await response.json();

            eventsContainer.replaceChildren();

            if (events.length === 0) {
                window.Antrakt.setStatus(message, 'Мероприятия не найдены');

                return;
            }

            events.forEach(function (event) {
                eventsContainer.append(createCard(event, { onDetails: showDetails }));
            });

            window.Antrakt.setStatus(message, 'Загружено мероприятий: ' + events.length, 'ok');
        } catch (error) {
            eventsContainer.replaceChildren();

            const code = error.status ? ' (код ' + error.status + ')' : '';
            window.Antrakt.setStatus(message, 'Не удалось загрузить мероприятия' + code, 'error');
            console.error(error);
        } finally {
            setBusy(false);
        }
    }

    modeSelect.addEventListener('change', function () {
        endpointLabel.textContent = endpoint();
    });

    loadButton.addEventListener('click', loadEvents);
    refreshButton.addEventListener('click', loadEvents);
})();
