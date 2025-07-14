<?php

use App\Http\Controllers\Order\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Management API Routes
|--------------------------------------------------------------------------
|
| Routes for order and cart management functionality including:
| - Cart operations (add, view, submit, clear)
| - Order history and details
| - Betting functionality
|
*/

// Cart management routes
Route::prefix('cart')->group(function () {
    Route::get('/', [OrderController::class, 'getCart'])
        ->name('orders.cart.get');

    Route::post('/add', [OrderController::class, 'addToCart'])
        ->name('orders.cart.add');

    Route::post('/submit', [OrderController::class, 'submitCart'])
        ->name('orders.cart.submit');

    Route::delete('/clear', [OrderController::class, 'clearCart'])
        ->name('orders.cart.clear');

    Route::delete('/items/{itemId}', [OrderController::class, 'removeFromCart'])
        ->name('orders.cart.remove')
        ->where('itemId', '[0-9]+');
});

// Order management routes
Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'getOrderHistory'])
        ->name('orders.history');

    Route::get('/{orderId}', [OrderController::class, 'getOrder'])
        ->name('orders.show')
        ->where('orderId', '[0-9]+');
});
