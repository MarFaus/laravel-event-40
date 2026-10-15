/**
 * Каталог: поиск и сортировка без перезагрузки страницы.
 *
 * Фильтрация выполняется на сервере (Laravel и база данных). Скрипт запрашивает
 * ту же страницу с параметрами поиска и подменяет в документе список билетов,
 * категории и пагинацию. Без JavaScript работает обычная форма GET.
 */
(function () {
    'use strict';

    const form = document.querySelector('#filters');

    if (!form) {
        return;
    }

    const search = form.querySelector('#search');
    const sort = form.querySelector('#sort');
    const grid = document.querySelector('#grid');
    let controller = null;

    function buildUrl() {
        const params = new URLSearchParams(new FormData(form));

        Array.from(params.keys()).forEach(function (key) {
            if (params.get(key) === '') {
                params.delete(key);
            }
        });

        const query = params.toString();

        return form.action + (query ? '?' + query : '');
    }

    function swap(selector, doc) {
        const current = document.querySelector(selector);
        const fresh = doc.querySelector(selector);

        if (current && fresh) {
            current.innerHTML = fresh.innerHTML;
        }
    }

    async function refresh() {
        const url = buildUrl();

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        grid.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error('Ошибка загрузки: ' + response.status);
            }

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            swap('#grid', doc);
            swap('#pager', doc);
            swap('#chips', doc);

            history.replaceState(null, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.href = url;
            }
        } finally {
            grid.removeAttribute('aria-busy');
        }
    }

    search.addEventListener('input', Antrakt.debounce(refresh, 350));
    sort.addEventListener('change', refresh);

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        refresh();
    });
})();
