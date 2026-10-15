<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Даты и месяцы показываются по-русски.
        Carbon::setLocale('ru');

        // Собственное оформление пагинации вместо стандартного Tailwind-варианта.
        // Проект обходится без сборки CSS, поэтому ссылки пагинации
        // оформлены обычными стилями из public/css/antrakt.css.
        Paginator::defaultView('pagination.plain');
        Paginator::defaultSimpleView('pagination.plain-simple');
    }
}
