<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/events');

// Практическая работа, задания 2–5: данные из массива, обработка событий.
Route::get('/lab', [EventController::class, 'lab'])->name('events.lab');

// Каталог (задание 6): данные из модели, пагинация, поиск на сервере.
Route::get('/events', [EventController::class, 'index'])->name('events.index');

// Статические адреса объявляются раньше /events/{event},
// чтобы слова create и data не принимались за идентификатор.
Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
Route::get('/events/data', [EventController::class, 'data'])->name('events.data');
Route::post('/events', [EventController::class, 'store'])->name('events.store');

Route::get('/events/{event}', [EventController::class, 'show'])
    ->whereNumber('event')
    ->name('events.show');
Route::post('/events/{event}/orders', [EventController::class, 'order'])
    ->whereNumber('event')
    ->name('events.order');

// Практическая работа «Получение и отображение мероприятий через API».
// Данные для страницы отдаёт routes/api.php (адреса /api/events).
Route::view('/events-api', 'events.api')->name('events.api');

Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
Route::get('/tickets/find', [TicketController::class, 'find'])->name('tickets.find');
Route::get('/tickets/{code}', [TicketController::class, 'show'])->name('tickets.show');
