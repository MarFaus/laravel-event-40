<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Купленные билеты: список заказов этого браузера и страница билета по коду.
 */
class TicketController extends Controller
{
    /**
     * «Мои билеты»: заказы, оформленные в текущей сессии браузера.
     */
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with('event')
            ->where('session_id', $request->session()->getId())
            ->latest('id')
            ->get();

        return view('tickets.index', compact('orders'));
    }

    /**
     * Открыть билет по коду, который пришёл при оформлении заказа.
     */
    public function find(Request $request): RedirectResponse
    {
        $code = strtoupper(trim((string) $request->query('code', '')));

        $exists = $code !== '' && Order::query()->where('code', $code)->exists();

        if (! $exists) {
            return redirect()
                ->route('tickets.index')
                ->withErrors(['code' => 'Заказ с таким кодом не найден. Проверьте код и попробуйте ещё раз.']);
        }

        return redirect()->route('tickets.show', $code);
    }

    /**
     * Страница билета.
     */
    public function show(Request $request, string $code): View
    {
        $order = Order::query()
            ->with('event')
            ->where('code', strtoupper($code))
            ->firstOrFail();

        return view('tickets.show', [
            'order' => $order,
            'isNew' => $request->boolean('new'),
        ]);
    }
}
