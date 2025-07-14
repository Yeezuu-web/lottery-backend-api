<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\MemberAuthController;
use App\Http\Controllers\Auth\UplineAuthController;
use App\Http\Middleware\MemberAuthMiddleware;
use App\Http\Middleware\UplineAuthMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register authentication routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Upline Authentication Routes
Route::prefix('upline')->group(function (): void {
    // Public routes
    Route::post('login', [UplineAuthController::class, 'login']);
    Route::post('refresh', [UplineAuthController::class, 'refresh']);

    // Protected routes
    Route::middleware([UplineAuthMiddleware::class])->group(function (): void {
        Route::post('logout', [UplineAuthController::class, 'logout']);
        Route::get('profile', [UplineAuthController::class, 'profile']);
    });
});

// Member Authentication Routes
Route::prefix('')->group(function (): void {
    // Public routes
    Route::post('login', [MemberAuthController::class, 'login']);
    Route::post('refresh', [MemberAuthController::class, 'refresh']);

    // Protected routes
    Route::middleware([MemberAuthMiddleware::class])->group(function (): void {
        Route::post('logout', [MemberAuthController::class, 'logout']);
        Route::get('profile', [MemberAuthController::class, 'profile']);
    });
});
