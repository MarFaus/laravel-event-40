<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Таблица мероприятий.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 20)->default('concert');
            $table->string('venue');
            $table->string('city', 60)->default('Костанай');
            $table->dateTime('starts_at');
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('tickets_total')->default(0);
            $table->unsignedInteger('tickets_left')->default(0);
            $table->text('description')->nullable();
            // Название, место и категория в нижнем регистре: по нему работает поиск.
            $table->text('search_index')->nullable();
            $table->timestamps();

            $table->index('starts_at');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
