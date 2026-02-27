<?php
use App\Http\Controllers\Api\OrderUploadController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RestaurantMenuApiController;
use App\Http\Controllers\Api\CustomerAuthController;
use Illuminate\Support\Facades\Route;

// ...existing code...

Route::get('/restaurants', [RestaurantMenuApiController::class, 'restaurants']);
Route::get('/restaurants/{id}/menu', [RestaurantMenuApiController::class, 'menu']);
Route::post('/restaurants/{id}/menu/reindex', [RestaurantMenuApiController::class, 'triggerReindex']);

Route::post('/login', [CustomerAuthController::class, 'login']);
Route::post('/verify-otp', [CustomerAuthController::class, 'verifyOtp']);
Route::post('/complete/profile', [CustomerAuthController::class, 'completeProfile']);

Route::post('/orders/upload-json', [OrderUploadController::class, 'upload']);
Route::post('/orders/place', [OrderController::class, 'place']);
Route::get('/orders/{order_id}', [OrderController::class, 'summary']);
Route::get('/order-items/{order_item_id}', [OrderController::class, 'itemHistory']);
Route::get('/orders/{order_id}/items', [OrderController::class, 'orderItems']);
