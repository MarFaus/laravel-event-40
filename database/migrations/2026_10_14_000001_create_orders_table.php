<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Таблица заказов билетов.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('code', 16)->unique();
            $table->string('buyer_name', 80);
            $table->string('buyer_email', 120);
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('total');
            // Идентификатор сессии: по нему страница «Мои билеты» находит заказы этого браузера.
            $table->string('session_id', 64)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
