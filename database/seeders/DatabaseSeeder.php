<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Заполнение базы данных учебными данными.
     */
    public function run(): void
    {
        $this->call([
            EventSeeder::class,
        ]);
    }
}
