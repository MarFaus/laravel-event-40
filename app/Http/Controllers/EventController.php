<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Афиша: каталог, страница мероприятия, оформление заказа, добавление мероприятия.
 *
 * Практическая работа:
 *  - lab()   задания 2–5: данные из массива, обработка событий в public/js/events.js;
 *  - index() задание 6: данные из модели Event, пагинация, поиск на сервере;
 *  - data()  теория 2.8: JSON для fetch();
 *  - store() и order() теория 2.9: POST из JavaScript с CSRF-токеном и проверкой на сервере.
 */
class EventController extends Controller
{
    private const SORTS = ['date', 'price_asc', 'price_desc'];

    /**
     * Задания 2–5. Мероприятия берутся из массива, база данных не нужна.
     * Контроллер формирует массив и передаёт его в Blade.
     */
    public function lab(): View
    {
        $events = [
            [
                'id' => 1,
                'name' => 'Концерт',
                'price' => 5000,
            ],
            [
                'id' => 2,
                'name' => 'Театральная постановка',
                'price' => 3500,
            ],
            [
                'id' => 3,
                'name' => 'Городской фестиваль',
                'price' => 2000,
            ],
        ];

        return view('events.lab', compact('events'));
    }

    /**
     * Каталог. Фильтрация, сортировка и пагинация выполняются в SQL-запросе,
     * поэтому браузер получает только записи текущей страницы.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $category = $request->query('category');
        $category = is_string($category) && isset(Event::CATEGORIES[$category]) ? $category : null;

        $sort = $request->query('sort');
        $sort = is_string($sort) && in_array($sort, self::SORTS, true) ? $sort : 'date';

        $query = Event::query()
            ->upcoming()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($search !== '', function ($q) use ($search) {
                // Спецсимволы LIKE экранируются, поиск идёт по столбцу search_index.
                $needle = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)).'%';
                $q->whereRaw("search_index like ? escape '!'", [$needle]);
            });

        match ($sort) {
            'price_asc' => $query->orderBy('price')->orderBy('starts_at'),
            'price_desc' => $query->orderByDesc('price')->orderBy('starts_at'),
            default => $query->orderBy('starts_at'),
        };

        $events = $query->paginate(9)->withQueryString();

        // Количество мероприятий по категориям для кнопок фильтра.
        $counts = Event::query()
            ->upcoming()
            ->select('category')
            ->selectRaw('count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $chips = [[
            'label' => 'Все',
            'count' => (int) $counts->sum(),
            'active' => $category === null,
            'url' => $request->fullUrlWithQuery(['category' => null, 'page' => null]),
        ]];

        foreach (Event::CATEGORIES as $key => $label) {
            $chips[] = [
                'label' => $label,
                'count' => (int) ($counts[$key] ?? 0),
                'active' => $category === $key,
                'url' => $request->fullUrlWithQuery(['category' => $key, 'page' => null]),
            ];
        }

        $isFiltered = $search !== '' || $category !== null || $sort !== 'date' || $events->currentPage() > 1;

        // Ближайшее мероприятие с билетами показывается крупно в начале страницы.
        $featured = $isFiltered
            ? null
            : Event::query()->upcoming()->where('tickets_left', '>', 0)->orderBy('starts_at')->first();

        return view('events.index', [
            'events' => $events,
            'chips' => $chips,
            'search' => $search,
            'category' => $category,
            'sort' => $sort,
            'featured' => $featured,
        ]);
    }

    /**
     * Страница мероприятия с формой заказа.
     */
    public function show(Event $event): View
    {
        $related = Event::query()
            ->upcoming()
            ->where('category', $event->category)
            ->whereKeyNot($event->id)
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        return view('events.show', compact('event', 'related'));
    }

    /**
     * Оформление заказа. Количество билетов проверяется на сервере
     * внутри транзакции, чтобы два покупателя не купили одно и то же место.
     */
    public function order(Request $request, Event $event): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email', 'max:120'],
            'quantity' => ['required', 'integer', 'min:1', 'max:6'],
        ], [
            'name.required' => 'Укажите имя.',
            'name.min' => 'Имя слишком короткое: нужно минимум 2 символа.',
            'name.max' => 'Имя слишком длинное: не больше 80 символов.',
            'email.required' => 'Укажите email, на него придёт код заказа.',
            'email.email' => 'Проверьте email: в адресе есть ошибка.',
            'quantity.required' => 'Выберите количество билетов.',
            'quantity.integer' => 'Количество билетов должно быть числом.',
            'quantity.min' => 'Выберите от 1 до 6 билетов.',
            'quantity.max' => 'За один заказ можно купить не больше 6 билетов.',
        ]);

        $result = DB::transaction(function () use ($event, $data, $request) {
            // Строка блокируется до конца транзакции, остальные покупатели ждут.
            $locked = Event::query()->lockForUpdate()->findOrFail($event->id);

            if ($locked->starts_at->isPast()) {
                return ['status' => 'past', 'left' => $locked->tickets_left];
            }

            if ($locked->tickets_left < $data['quantity']) {
                return ['status' => 'not_enough', 'left' => $locked->tickets_left];
            }

            $locked->decrement('tickets_left', $data['quantity']);

            $order = Order::create([
                'event_id' => $locked->id,
                'code' => Order::generateCode(),
                'buyer_name' => trim($data['name']),
                'buyer_email' => trim($data['email']),
                'quantity' => $data['quantity'],
                'total' => $locked->price * $data['quantity'],
                'session_id' => $request->session()->getId(),
            ]);

            return ['status' => 'ok', 'order' => $order, 'left' => $locked->tickets_left];
        });

        if ($result['status'] === 'past') {
            return response()->json([
                'message' => 'Это мероприятие уже прошло, билеты не продаются.',
                'left' => $result['left'],
            ], 409);
        }

        if ($result['status'] === 'not_enough') {
            $message = $result['left'] > 0
                ? 'Осталось только '.$result['left'].'. Уменьшите количество билетов.'
                : 'Билеты закончились.';

            return response()->json(['message' => $message, 'left' => $result['left']], 409);
        }

        return response()->json([
            'message' => 'Заказ оформлен.',
            'code' => $result['order']->code,
            'url' => route('tickets.show', $result['order']->code).'?new=1',
            'left' => $result['left'],
        ], 201);
    }

    /**
     * Форма добавления мероприятия.
     */
    public function create(): View
    {
        return view('events.create', ['categories' => Event::CATEGORIES]);
    }

    /**
     * Сохранение мероприятия из формы (POST через fetch с CSRF-токеном).
     * Проверка в браузере нужна для удобства, окончательная проверка здесь.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(Event::CATEGORIES))],
            'venue' => ['required', 'string', 'min:2', 'max:120'],
            'city' => ['nullable', 'string', 'max:60'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'tickets_total' => ['required', 'integer', 'min:1', 'max:5000'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Укажите название.',
            'name.min' => 'Название слишком короткое: нужно минимум 3 символа.',
            'category.required' => 'Выберите категорию.',
            'category.in' => 'Выберите категорию из списка.',
            'venue.required' => 'Укажите место проведения.',
            'starts_at.required' => 'Укажите дату и время начала.',
            'starts_at.date_format' => 'Укажите дату и время в формате календаря.',
            'price.required' => 'Укажите цену билета, для бесплатного входа введите 0.',
            'price.integer' => 'Цена должна быть целым числом.',
            'price.min' => 'Цена не может быть отрицательной.',
            'tickets_total.required' => 'Укажите количество билетов.',
            'tickets_total.integer' => 'Количество билетов должно быть целым числом.',
            'tickets_total.min' => 'Нужен хотя бы один билет.',
        ]);

        // Введённое время считается костанайским, в базу сохраняется в часовом поясе приложения.
        $startsAt = Carbon::createFromFormat('Y-m-d\TH:i', $data['starts_at'], Event::DISPLAY_TIMEZONE);

        if ($startsAt->isPast()) {
            throw ValidationException::withMessages([
                'starts_at' => 'Дата начала уже прошла. Выберите время в будущем.',
            ]);
        }

        $event = Event::create([
            'name' => trim($data['name']),
            'category' => $data['category'],
            'venue' => trim($data['venue']),
            'city' => trim($data['city'] ?? '') ?: 'Костанай',
            'starts_at' => $startsAt->setTimezone(config('app.timezone')),
            'price' => $data['price'],
            'tickets_total' => $data['tickets_total'],
            'tickets_left' => $data['tickets_total'],
            'description' => $data['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Мероприятие опубликовано.',
            'event' => $event->only(['id', 'name', 'price']),
            'url' => route('events.show', $event),
        ], 201);
    }

    /**
     * Теория 2.8. JSON для fetch(): только нужные поля и ограниченное число записей.
     */
    public function data(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $events = Event::query()
            ->upcoming()
            ->when($search !== '', function ($q) use ($search) {
                $needle = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)).'%';
                $q->whereRaw("search_index like ? escape '!'", [$needle]);
            })
            ->orderBy('starts_at')
            ->limit(20)
            ->get(['id', 'name', 'price', 'starts_at']);

        return response()->json($events);
    }
}
