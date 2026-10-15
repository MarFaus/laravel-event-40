<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_страница_заданий_из_массива_открывается(): void
    {
        $response = $this->get('/lab');

        $response->assertOk();
        $response->assertSee('Концерт');
        $response->assertSee('Театральная постановка');
        $response->assertSee('js/events.js');
    }

    public function test_главная_ведёт_в_афишу(): void
    {
        $this->get('/')->assertRedirect('/events');
    }

    public function test_афиша_показывает_предстоящие_события_и_скрывает_прошедшие(): void
    {
        Event::factory()->create(['name' => 'Будущий концерт']);
        Event::factory()->past()->create(['name' => 'Давний спектакль']);

        $response = $this->get('/events');

        $response->assertOk();
        $response->assertSee('Будущий концерт');
        $response->assertDontSee('Давний спектакль');
    }

    public function test_поиск_без_учёта_регистра_работает_на_сервере(): void
    {
        Event::factory()->create(['name' => 'Концерт в филармонии']);
        Event::factory()->create(['name' => 'Выставка кошек']);

        $response = $this->get('/events?search=КОНЦЕРТ');

        $response->assertOk();
        $response->assertSee('Концерт в филармонии');
        $response->assertDontSee('Выставка кошек');
    }

    public function test_фильтр_по_категории(): void
    {
        Event::factory()->create(['name' => 'Рок-вечер', 'category' => 'concert']);
        Event::factory()->create(['name' => 'Лекция о звёздах', 'category' => 'lecture']);

        $response = $this->get('/events?category=lecture');

        $response->assertOk();
        $response->assertSee('Лекция о звёздах');
        $response->assertDontSee('Рок-вечер');
    }

    public function test_пагинация_делит_афишу_на_страницы(): void
    {
        Event::factory()->count(12)->create();

        $this->get('/events')->assertOk()->assertSee('page=2', false);
    }

    public function test_страница_мероприятия_открывается(): void
    {
        $event = Event::factory()->create(['name' => 'Джаз тёплым вечером']);

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('Джаз тёплым вечером')
            ->assertSee('Купить билеты');
    }

    public function test_заказ_создаётся_и_уменьшает_количество_билетов(): void
    {
        $event = Event::factory()->create(['price' => 2000, 'tickets_total' => 10, 'tickets_left' => 10]);

        $response = $this->postJson('/events/'.$event->id.'/orders', [
            'name' => 'Иван',
            'email' => 'ivan@example.com',
            'quantity' => 3,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('left', 7);

        $this->assertSame(7, $event->fresh()->tickets_left);
        $this->assertDatabaseHas('orders', [
            'event_id' => $event->id,
            'buyer_name' => 'Иван',
            'quantity' => 3,
            'total' => 6000,
        ]);
    }

    public function test_нельзя_купить_больше_билетов_чем_осталось(): void
    {
        $event = Event::factory()->create(['tickets_total' => 5, 'tickets_left' => 2]);

        $response = $this->postJson('/events/'.$event->id.'/orders', [
            'name' => 'Иван',
            'email' => 'ivan@example.com',
            'quantity' => 4,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('left', 2);
        $this->assertSame(2, $event->fresh()->tickets_left);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_нельзя_купить_билет_на_прошедшее_событие(): void
    {
        $event = Event::factory()->past()->create();

        $this->postJson('/events/'.$event->id.'/orders', [
            'name' => 'Иван',
            'email' => 'ivan@example.com',
            'quantity' => 1,
        ])->assertStatus(409);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_заказ_с_ошибками_отклоняется(): void
    {
        $event = Event::factory()->create();

        $response = $this->postJson('/events/'.$event->id.'/orders', [
            'name' => 'И',
            'email' => 'не-почта',
            'quantity' => 99,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'quantity']);
    }

    public function test_билет_открывается_по_коду(): void
    {
        $event = Event::factory()->create(['name' => 'Камерный концерт']);
        $order = Order::create([
            'event_id' => $event->id,
            'code' => 'AN-TEST22',
            'buyer_name' => 'Иван',
            'buyer_email' => 'ivan@example.com',
            'quantity' => 2,
            'total' => 4000,
        ]);

        $this->get('/tickets/'.strtolower($order->code))
            ->assertOk()
            ->assertSee('AN-TEST22')
            ->assertSee('Камерный концерт')
            ->assertSee('2 билета');

        $this->get('/tickets/find?code=an-test22')->assertRedirect('/tickets/AN-TEST22');
        $this->get('/tickets/find?code=нет')->assertRedirect('/tickets');
    }

    public function test_страница_мои_билеты_открывается_пустой(): void
    {
        $this->get('/tickets')->assertOk()->assertSee('Пока нет заказов');
    }

    public function test_мероприятие_добавляется_через_post(): void
    {
        $response = $this->postJson('/events', [
            'name' => 'Новый концерт',
            'category' => 'concert',
            'venue' => 'Городской парк',
            'starts_at' => now('Asia/Almaty')->addDays(5)->format('Y-m-d\TH:i'),
            'price' => 2500,
            'tickets_total' => 100,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('events', [
            'name' => 'Новый концерт',
            'price' => 2500,
            'tickets_left' => 100,
            'city' => 'Костанай',
        ]);
    }

    public function test_сервер_отклоняет_некорректное_мероприятие(): void
    {
        $response = $this->postJson('/events', [
            'name' => 'К',
            'category' => 'неизвестная',
            'price' => -100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'category', 'venue', 'starts_at', 'price', 'tickets_total']);
        $this->assertDatabaseCount('events', 0);
    }

    public function test_json_маршрут_возвращает_предстоящие_события(): void
    {
        Event::factory()->create(['name' => 'Городской фестиваль', 'price' => 2000]);
        Event::factory()->past()->create(['name' => 'Прошедшее']);

        $response = $this->getJson('/events/data');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Городской фестиваль', 'price' => 2000]);
        $response->assertJsonMissing(['name' => 'Прошедшее']);
    }

    public function test_сидер_создаёт_афишу_с_заданными_мероприятиями(): void
    {
        $this->seed();

        $this->assertDatabaseHas('events', ['name' => 'Концерт', 'price' => 5000]);
        $this->assertDatabaseHas('events', ['name' => 'Театральная постановка', 'price' => 3500]);
        $this->assertDatabaseHas('events', ['name' => 'Городской фестиваль', 'price' => 2000]);
        $this->assertGreaterThan(9, Event::count());
    }
}
