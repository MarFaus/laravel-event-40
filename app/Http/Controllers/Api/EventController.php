<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON API мероприятий.
 *
 * Контроллер отдаёт только данные без HTML: как их показать,
 * решает интерфейс (public/js/events-api.js и events-catalog-api.js).
 */
class EventController extends Controller
{
    /** Сколько записей отдаёт режим «все» (защита от слишком больших ответов). */
    private const ALL_LIMIT = 1000;

    /**
     * Задания 2 и 6. Данные хранятся в массиве, база не нужна.
     *
     *   /api/events            три мероприятия (задание 2)
     *   /api/events?limit=2    только два мероприятия (задание 6)
     *   /api/events?limit=0    пустой массив: проверка сообщения «не найдены»
     *   /api/events?fail=1     ответ 500: проверка обработки ошибки
     *                          (работает только при APP_DEBUG=true, на боевом сайте игнорируется)
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('fail') && config('app.debug')) {
            return response()->json(['message' => 'Тестовая ошибка сервера.'], 500);
        }

        $events = [
            [
                'id' => 1,
                'name' => 'Концерт',
                'price' => 5000,
                'status' => 'Доступен',
                'state' => 'available',
            ],
            [
                'id' => 2,
                'name' => 'Театральная постановка',
                'price' => 3500,
                'status' => 'Доступен',
                'state' => 'available',
            ],
            [
                'id' => 3,
                'name' => 'Городской фестиваль',
                'price' => 2000,
                'status' => 'Билеты заканчиваются',
                'state' => 'low',
            ],
        ];

        if ($request->query('limit') !== null && ctype_digit((string) $request->query('limit'))) {
            $events = array_slice($events, 0, (int) $request->query('limit'));
        }

        return response()->json($events);
    }

    /**
     * Задание повышенной сложности. Данные берутся из модели Event.
     *
     *   search    поиск по названию, месту и категории (на сервере, без учёта регистра)
     *   page      номер страницы
     *   per_page  записей на странице, от 1 до 50 (по умолчанию 20)
     *   all=1     все подходящие записи сразу (не более 1000): для сравнения с первыми 20
     *
     * Возвращаются только нужные интерфейсу поля, а не вся строка таблицы.
     */
    public function catalog(Request $request): JsonResponse
    {
        $params = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
            'all' => ['nullable', 'boolean'],
        ]);

        $search = trim((string) ($params['search'] ?? ''));

        $query = Event::query()
            ->upcoming()
            ->when($search !== '', function ($q) use ($search) {
                // Поиск идёт по столбцу search_index в нижнем регистре (см. модель Event).
                $needle = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)).'%';
                $q->whereRaw("search_index like ? escape '!'", [$needle]);
            })
            ->orderBy('name')
            ->orderBy('id');

        $columns = ['id', 'name', 'price', 'tickets_left', 'starts_at'];
        $started = hrtime(true);

        if ($request->boolean('all')) {
            $total = (clone $query)->count();
            $rows = $query->limit(self::ALL_LIMIT)->get($columns);
            $perPage = max(1, $rows->count());
            $page = 1;
            $lastPage = 1;
        } else {
            $perPage = (int) ($params['per_page'] ?? 20);
            $paginator = $query->paginate($perPage, $columns);
            $rows = $paginator->getCollection();
            $total = $paginator->total();
            $page = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
        }

        $tookMs = round((hrtime(true) - $started) / 1_000_000, 1);

        return response()->json([
            'data' => $rows->map(function (Event $event) {
                $status = $this->status($event);

                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'price' => $event->price,
                    'price_label' => $event->price_label,
                    'date_label' => $event->date_label,
                    'status' => $status['text'],
                    'state' => $status['state'],
                ];
            })->values(),
            'meta' => [
                'total' => $total,
                'count' => $rows->count(),
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'took_ms' => $tookMs,
            ],
        ]);
    }

    /** Статус для интерфейса: текст и код (код нужен для оформления). */
    private function status(Event $event): array
    {
        if ($event->is_sold_out) {
            return ['text' => 'Билетов нет', 'state' => 'sold_out'];
        }

        if ($event->is_low) {
            return ['text' => 'Билеты заканчиваются', 'state' => 'low'];
        }

        return ['text' => 'Доступен', 'state' => 'available'];
    }
}
