/**
 * Задание 5. Обработка событий на странице афиши.
 *
 * Скрипт подключён в конце body, поэтому к моменту его выполнения
 * все HTML-элементы уже существуют. Если подключать скрипт в head,
 * нужен атрибут defer или событие DOMContentLoaded.
 */

const eventsContainer = document.querySelector('#events');
const message = document.querySelector('#message');
const search = document.querySelector('#search');
const title = document.querySelector('#pageTitle');
const changeTitleButton = document.querySelector('#changeTitle');

// Изменение заголовка
changeTitleButton.addEventListener('click', function () {
    title.textContent = 'Выберите мероприятие';
});

// Обработка кнопок карточек.
// Обработчик назначен не каждой кнопке, а общему родителю —
// это делегирование событий (теория 2.10).
eventsContainer.addEventListener('click', function (event) {
    const card = event.target.closest('.event-card');

    if (!card) {
        return;
    }

    if (event.target.matches('.buy-button')) {
        const eventName = card.querySelector('h3').textContent;
        const eventId = card.dataset.eventId;

        message.textContent =
            'Вы выбрали: ' + eventName + '. ID: ' + eventId;
    }

    if (event.target.matches('.hide-button')) {
        card.remove();
        message.textContent = 'Карточка скрыта из интерфейса';
    }
});

// Поиск среди отображаемых карточек.
// Поиск выполняется в браузере: контроллер и база данных
// при каждом введённом символе не вызываются.
search.addEventListener('input', function () {
    const query = search.value.trim().toLowerCase();

    eventsContainer.querySelectorAll('.event-card')
        .forEach(function (card) {
            const name = card.querySelector('h3')
                .textContent.toLowerCase();

            card.hidden = !name.includes(query);
        });
});
