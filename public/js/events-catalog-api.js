/**
 * Повышенная сложность. Каталог из базы данных через /api/events/catalog:
 * поиск на сервере, пагинация, счётчик найденного и сравнение
 * «все записи» и «первые 20».
 */
(function () {
    'use strict';

    const search = document.querySelector('#apiSearch');
    const catalog = document.querySelector('#catalog');
    const message = document.querySelector('#catalogMessage');
    const pager = document.querySelector('#catalogPager');
    const compareButton = document.querySelector('#compare');
    const compareResult = document.querySelector('#compareResult');

    if (!search || !catalog || !window.AntraktApi) {
        return;
    }

    const PER_PAGE = 6;
    let page = 1;
    let controller = null;

    function url(extra) {
        const params = new URLSearchParams(extra || {});
        const text = search.value.trim();

        if (text !== '') {
            params.set('search', text);
        }

        return '/api/events/catalog?' + params.toString();
    }

    function pageNumbers(current, last) {
        const wanted = new Set([1, last, current - 1, current, current + 1]);
        const list = Array.from(wanted)
            .filter(function (n) { return n >= 1 && n <= last; })
            .sort(function (a, b) { return a - b; });
        const result = [];

        list.forEach(function (n, index) {
            if (index > 0 && n - list[index - 1] > 1) {
                result.push('…');
            }

            result.push(n);
        });

        return result;
    }

    function pagerButton(label, target, extraClass, disabled) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'pagination__item ' + (extraClass || '');
        button.textContent = label;

        if (disabled) {
            button.disabled = true;
        } else {
            button.dataset.page = String(target);
        }

        return button;
    }

    function renderPager(meta) {
        pager.replaceChildren();

        if (meta.last_page <= 1) {
            return;
        }

        pager.append(pagerButton('Назад', meta.page - 1, '', meta.page <= 1));

        pageNumbers(meta.page, meta.last_page).forEach(function (item) {
            if (item === '…') {
                const gap = document.createElement('span');
                gap.className = 'pagination__item pagination__item--disabled';
                gap.textContent = '…';
                pager.append(gap);

                return;
            }

            const active = item === meta.page;
            const button = pagerButton(String(item), item, active ? 'pagination__item--active' : '');

            if (active) {
                button.setAttribute('aria-current', 'page');
            }

            pager.append(button);
        });

        pager.append(pagerButton('Вперёд', meta.page + 1, '', meta.page >= meta.last_page));
    }

    function render(payload) {
        const meta = payload.meta;

        catalog.replaceChildren();
        pager.replaceChildren();

        if (meta.total === 0) {
            window.Antrakt.setStatus(message, 'Ничего не найдено. Измените запрос.');

            return;
        }

        payload.data.forEach(function (event) {
            catalog.append(window.AntraktApi.createCard(event, { href: '/events/' + event.id }));
        });

        const from = (meta.page - 1) * meta.per_page + 1;
        const to = from + meta.count - 1;

        window.Antrakt.setStatus(
            message,
            'Найдено: ' + meta.total + '. Показано ' + from + '–' + to + '.',
            'ok'
        );

        renderPager(meta);
    }

    async function load() {
        if (controller) {
            controller.abort();
        }

        const own = new AbortController();
        controller = own;

        catalog.setAttribute('aria-busy', 'true');
        window.Antrakt.setStatus(message, 'Загрузка...');

        try {
            const response = await fetch(url({ per_page: PER_PAGE, page: page }), {
                headers: { Accept: 'application/json' },
                signal: own.signal,
            });

            if (response.status === 422) {
                catalog.replaceChildren();
                pager.replaceChildren();
                window.Antrakt.setStatus(message, 'Запрос не принят: слишком длинный текст поиска.', 'error');

                return;
            }

            if (!response.ok) {
                throw new Error('Ошибка загрузки данных (код ' + response.status + ')');
            }

            const payload = await response.json();

            // Страница могла исчезнуть, пока пользователь листал: возвращаемся на последнюю.
            if (payload.data.length === 0 && payload.meta.total > 0 && page > payload.meta.last_page) {
                page = payload.meta.last_page;

                return load();
            }

            render(payload);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            catalog.replaceChildren();
            pager.replaceChildren();
            window.Antrakt.setStatus(message, 'Не удалось загрузить каталог', 'error');
            console.error(error);
        } finally {
            if (controller === own) {
                catalog.removeAttribute('aria-busy');
            }
        }
    }

    search.addEventListener('input', window.Antrakt.debounce(function () {
        page = 1;
        load();
    }, 300));

    pager.addEventListener('click', function (event) {
        const button = event.target.closest('[data-page]');

        if (!button) {
            return;
        }

        page = Number(button.dataset.page);
        load();
        catalog.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    /* Сравнение: все записи и первые 20 */

    async function measure(address) {
        const started = performance.now();
        const response = await fetch(address, { headers: { Accept: 'application/json' } });

        if (!response.ok) {
            throw new Error('Ошибка загрузки данных (код ' + response.status + ')');
        }

        const text = await response.text();
        const elapsed = Math.round(performance.now() - started);
        const body = JSON.parse(text);

        return {
            rows: body.data.length,
            total: body.meta.total,
            kilobytes: (new Blob([text]).size / 1024).toFixed(1) + ' КБ',
            ms: elapsed + ' мс',
            db: body.meta.took_ms + ' мс',
        };
    }

    function cell(tag, text) {
        const node = document.createElement(tag);
        node.textContent = text;

        return node;
    }

    function renderComparison(all, first) {
        const table = document.createElement('table');
        table.className = 'api-table';

        const head = document.createElement('tr');
        ['', 'Все записи', 'Первые 20'].forEach(function (text) {
            head.append(cell('th', text));
        });

        const lines = [
            ['Записей в ответе', all.rows, first.rows],
            ['Размер ответа', all.kilobytes, first.kilobytes],
            ['Время ответа в браузере', all.ms, first.ms],
            ['Время запроса к базе', all.db, first.db],
        ];

        const body = document.createElement('tbody');
        body.append(head);

        lines.forEach(function (line) {
            const row = document.createElement('tr');
            row.append(cell('th', line[0]), cell('td', String(line[1])), cell('td', String(line[2])));
            body.append(row);
        });

        table.append(body);

        const note = document.createElement('p');
        note.textContent = 'Подходящих записей в базе: ' + all.total + '. Режим «все» отдаёт не больше 1000 записей.';

        compareResult.replaceChildren(table, note);
    }

    compareButton.addEventListener('click', async function () {
        compareButton.disabled = true;
        compareResult.textContent = 'Измеряю...';

        try {
            const all = await measure(url({ all: 1 }));
            const first = await measure(url({ per_page: 20, page: 1 }));

            renderComparison(all, first);
        } catch (error) {
            compareResult.textContent = 'Не удалось выполнить сравнение';
            console.error(error);
        } finally {
            compareButton.disabled = false;
        }
    });

    load();
})();
