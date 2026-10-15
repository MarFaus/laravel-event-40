<?php

use App\Http\Controllers\Api\EventController;
use Illuminate\Support\Facades\Route;

/*
 * API-маршруты. Файл подключён в bootstrap/app.php, поэтому ко всем адресам
 * автоматически добавляется префикс /api, а сессии и CSRF не используются.
 *
 * Лимит 60 запросов в минуту с одного адреса защищает открытое API от перегрузки.
 */
Route::middleware('throttle:60,1')->group(function () {
    // Задания 1–2 и 6: данные из массива, адрес /api/events.
    Route::get('/events', [EventController::class, 'index'])->name('api.events.index');

    // Задание повышенной сложности: данные из таблицы events, поиск и пагинация.
    Route::get('/events/catalog', [EventController::class, 'catalog'])->name('api.events.catalog');
});
