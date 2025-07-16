<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\MemberAuthController;
use App\Http\Controllers\Auth\PermissionController;
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

// Permission Management Routes (Upline only)
Route::prefix('permissions')->middleware([UplineAuthMiddleware::class])->group(function (): void {
    // Get agent permissions
    Route::get('agents/{agentId}', [PermissionController::class, 'getAgentPermissions'])
        ->where('agentId', '[0-9]+');

    // Grant permission
    Route::post('grant', [PermissionController::class, 'grantPermission']);

    // Revoke permission
    Route::post('revoke', [PermissionController::class, 'revokePermission']);

    // Bulk grant permissions
    Route::post('bulk-grant', [PermissionController::class, 'bulkGrantPermissions']);

    // Check permission
    Route::get('check/{permission}', [PermissionController::class, 'checkPermission']);

    // Get available permissions for agent type
    Route::get('available/{agentType}', [PermissionController::class, 'getAvailablePermissions']);

    // Get all permissions (admin)
    Route::get('all', [PermissionController::class, 'getAllPermissions']);
});
