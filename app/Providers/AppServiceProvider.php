<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

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
        View::composer('*', function ($view) {
            $restaurant = Auth::guard('restaurant')->user();
            $currencySymbol = trim((string) ($restaurant->country_currency ?? ''));

            $view->with('currencySymbol', $currencySymbol !== '' ? $currencySymbol : 'Rs');
        });
    }
}
