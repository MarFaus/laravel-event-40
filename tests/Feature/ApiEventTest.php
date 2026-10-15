<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_возвращает_json_с_тремя_мероприятиями_из_массива(): void
    {
        $response = $this->getJson('/api/events');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(3);
        $response->assertJsonStructure([['id', 'name', 'price', 'status']]);
        $response->assertJsonPath('0.name', 'Концерт');
        $response->assertJsonPath('0.price', 5000);
        $response->assertJsonPath('1.name', 'Театральная постановка');
        $response->assertJsonPath('2.status', 'Билеты заканчиваются');
    }

    public function test_параметр_limit_ограничивает_число_мероприятий(): void
    {
        $this->getJson('/api/events?limit=2')->assertOk()->assertJsonCount(2);
        $this->getJson('/api/events?limit=0')->assertOk()->assertExactJson([]);
    }

    public function test_тестовая_ошибка_работает_только_в_режиме_отладки(): void
    {
        config(['app.debug' => true]);
        $this->getJson('/api/events?fail=1')->assertStatus(500);

        config(['app.debug' => false]);
        $this->getJson('/api/events?fail=1')->assertOk()->assertJsonCount(3);
    }

    public function test_страница_api_открывается_и_подключает_скрипты(): void
    {
        $this->get('/events-api')
            ->assertOk()
            ->assertSee('Загрузить мероприятия')
            ->assertSee('Обновить список')
            ->assertSee('js/events-api.js')
            ->assertSee('js/events-catalog-api.js');
    }

    public function test_каталог_отдаёт_данные_из_базы_без_лишних_полей(): void
    {
        Event::factory()->create(['name' => 'Джаз', 'price' => 4000, 'tickets_left' => 100, 'tickets_total' => 100]);

        $response = $this->getJson('/api/events/catalog');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.name', 'Джаз');
        $response->assertJsonPath('data.0.price', 4000);
        $response->assertJsonPath('data.0.status', 'Доступен');
        $response->assertJsonMissingPath('data.0.description');
        $response->assertJsonMissingPath('data.0.tickets_left');
    }

    public function test_каталог_не_показывает_прошедшие_и_сортирует_по_названию(): void
    {
        Event::factory()->create(['name' => 'Яркий вечер']);
        Event::factory()->create(['name' => 'Балет']);
        Event::factory()->past()->create(['name' => 'Давно прошло']);

        $response = $this->getJson('/api/events/catalog');

        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.name', 'Балет');
        $response->assertJsonPath('data.1.name', 'Яркий вечер');
        $response->assertJsonMissing(['name' => 'Давно прошло']);
    }

    public function test_каталог_ищет_на_сервере_без_учёта_регистра(): void
    {
        Event::factory()->create(['name' => 'Концерт в филармонии']);
        Event::factory()->create(['name' => 'Выставка кошек']);

        $response = $this->getJson('/api/events/catalog?search=КОНЦЕРТ');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.name', 'Концерт в филармонии');
    }

    public function test_спецсимволы_поиска_не_работают_как_шаблон(): void
    {
        Event::factory()->create(['name' => 'Обычный вечер']);

        $this->getJson('/api/events/catalog?search=%25')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/events/catalog?search=_')->assertJsonPath('meta.total', 0);
    }

    public function test_каталог_делится_на_страницы(): void
    {
        Event::factory()->count(12)->create();

        $first = $this->getJson('/api/events/catalog?per_page=5&page=1');
        $third = $this->getJson('/api/events/catalog?per_page=5&page=3');

        $first->assertJsonCount(5, 'data');
        $first->assertJsonPath('meta.total', 12);
        $first->assertJsonPath('meta.last_page', 3);
        $third->assertJsonCount(2, 'data');
        $third->assertJsonPath('meta.page', 3);
    }

    public function test_по_умолчанию_возвращается_не_больше_двадцати_записей(): void
    {
        Event::factory()->count(25)->create();

        $this->getJson('/api/events/catalog')
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.per_page', 20);
    }

    public function test_режим_all_возвращает_все_записи_для_сравнения(): void
    {
        Event::factory()->count(25)->create();

        $this->getJson('/api/events/catalog?all=1')
            ->assertJsonCount(25, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 1);
    }

    public function test_некорректные_параметры_отклоняются(): void
    {
        $this->getJson('/api/events/catalog?per_page=0')->assertStatus(422)->assertJsonValidationErrors('per_page');
        $this->getJson('/api/events/catalog?per_page=500')->assertStatus(422)->assertJsonValidationErrors('per_page');
        $this->getJson('/api/events/catalog?page=-1')->assertStatus(422)->assertJsonValidationErrors('page');
        $this->getJson('/api/events/catalog?search='.str_repeat('a', 101))->assertStatus(422)->assertJsonValidationErrors('search');
    }

    public function test_статусы_распроданного_и_последних_билетов(): void
    {
        Event::factory()->soldOut()->create(['name' => 'А-распродано']);
        Event::factory()->create(['name' => 'Б-последние', 'tickets_total' => 50, 'tickets_left' => 3]);

        $response = $this->getJson('/api/events/catalog');

        $response->assertJsonPath('data.0.status', 'Билетов нет');
        $response->assertJsonPath('data.0.state', 'sold_out');
        $response->assertJsonPath('data.1.status', 'Билеты заканчиваются');
        $response->assertJsonPath('data.1.state', 'low');
    }
}
