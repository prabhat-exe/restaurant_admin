<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RestaurantAuthController;
use App\Http\Controllers\MenuImportController;


Route::get('/', function () {
    return view('welcome');
});

Route::prefix('restaurant')->group(function () {

    Route::get('register', [RestaurantAuthController::class, 'showRegister'])->name('restaurant.register.form');
    Route::post('register', [RestaurantAuthController::class, 'register'])->name('restaurant.register');

    Route::get('login', [RestaurantAuthController::class, 'showLogin'])->name('restaurant.login');
    Route::post('login', [RestaurantAuthController::class, 'login'])->name('restaurant.login.submit');

    Route::post('logout', [RestaurantAuthController::class, 'logout'])->name('restaurant.logout');

    Route::get('dashboard', [MenuImportController::class, 'dashboard'])
        ->middleware('auth:restaurant')
        ->name('restaurant.dashboard');


    Route::get('menu-import', function () {
        return view('restaurant.menu_import');
    })->middleware('auth:restaurant')->name('menu.import.form');

    Route::post('menu-import', [MenuImportController::class, 'import'])
        ->middleware('auth:restaurant')
        ->name('menu.import');


});