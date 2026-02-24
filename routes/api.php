<?php
use App\Http\Controllers\Api\OrderUploadController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

// ...existing code...

Route::post('/orders/upload-json', [OrderUploadController::class, 'upload']);
Route::post('/orders/place', [OrderController::class, 'place']);
Route::get('/orders/{order_id}', [OrderController::class, 'summary']);
Route::get('/order-items/{order_item_id}', [OrderController::class, 'itemHistory']);
