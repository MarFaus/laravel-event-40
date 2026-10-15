<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика мероприятий для тестов и быстрого наполнения базы.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $category = fake()->randomElement(array_keys(Event::CATEGORIES));
        $total = fake()->numberBetween(50, 300);

        return [
            'name' => Event::CATEGORIES[$category].' '.fake()->numberBetween(1, 999),
            'category' => $category,
            'venue' => fake()->randomElement(['Областная филармония', 'Драматический театр', 'Городской парк', 'Дворец культуры']),
            'city' => 'Костанай',
            'starts_at' => fake()->dateTimeBetween('+1 day', '+40 days'),
            'price' => fake()->numberBetween(1, 20) * 500,
            'tickets_total' => $total,
            'tickets_left' => $total,
            'description' => 'Описание мероприятия для проверки.',
        ];
    }

    /** Мероприятие, которое уже прошло. */
    public function past(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subDays(3),
        ]);
    }

    /** Все билеты проданы. */
    public function soldOut(): static
    {
        return $this->state(fn () => [
            'tickets_left' => 0,
        ]);
    }
}
