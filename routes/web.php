<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RestaurantAuthController;
use App\Http\Controllers\MenuImportController;
use App\Http\Controllers\RestaurantOrderController;


Route::get('/', function () {
    return redirect()->route('restaurant.login');
});

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});

Route::prefix('restaurant')->group(function () {

    Route::get('login', [RestaurantAuthController::class, 'showLogin'])->name('restaurant.login');
    Route::post('login', [RestaurantAuthController::class, 'login'])->name('restaurant.login.submit');

    Route::post('logout', [RestaurantAuthController::class, 'logout'])->name('restaurant.logout');

    Route::get('dashboard', [MenuImportController::class, 'dashboard'])
        ->middleware('auth:restaurant')
        ->name('restaurant.dashboard');


    Route::get('menu-import', [MenuImportController::class, 'showImportForm'])
        ->middleware('auth:restaurant')
        ->name('menu.import.form');

    Route::post('menu-import', [MenuImportController::class, 'import'])
        ->middleware('auth:restaurant')
        ->name('menu.import');

    Route::delete('menu-import', [MenuImportController::class, 'destroyMenu'])
        ->middleware('auth:restaurant')
        ->name('menu.destroy');

    Route::get('orders', [RestaurantOrderController::class, 'showOrders'])->middleware('auth:restaurant')->name('restaurant.orders');

    Route::get('download-sample', function () {
    return response()->download(
        public_path('templates/item_sampleFile.xlsx')
        );
    })->middleware('auth:restaurant')->name('menu.sample.download');
});

Route::prefix('admin')->group(function () {
    Route::get('login', [RestaurantAuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('login', [RestaurantAuthController::class, 'adminLogin'])->name('admin.login.submit');

    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [RestaurantAuthController::class, 'adminLogout'])->name('admin.logout');
        Route::get('dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('restaurant/create', [\App\Http\Controllers\AdminController::class, 'createRestaurant'])->name('admin.restaurant.create');
        Route::post('restaurant/store', [\App\Http\Controllers\AdminController::class, 'storeRestaurant'])->name('admin.restaurant.store');
        Route::post('restaurant/update/{id}', [\App\Http\Controllers\AdminController::class, 'updateRestaurant'])->name('admin.restaurant.update');
    });
});
