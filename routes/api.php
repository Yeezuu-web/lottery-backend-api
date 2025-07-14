<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::prefix('v1/auth')->group(function (): void {
    require __DIR__.'/auth.php';
});

// Agent Management routes (protected by authentication middleware)
Route::prefix('v1/agents')->middleware(['upline.auth'])->group(function (): void {
    require __DIR__.'/agents.php';
});

// Agent Settings routes (protected by authentication middleware)
Route::prefix('v1/agent-settings')->middleware(['upline.auth'])->group(function (): void {
    require __DIR__.'/api/agent-settings.php';
});

// Order Management routes (protected by authentication middleware)
Route::prefix('v1')->middleware(['member.auth'])->group(function (): void {
    require __DIR__.'/api/orders.php';
});

// Wallet helper routes (public informational endpoints)
Route::prefix('v1/wallet')->group(function (): void {
    // Public helper endpoints for wallet/transaction types
    Route::prefix('wallet-types')->group(function (): void {
        Route::get('/', fn () => response()->json([
            'success' => true,
            'data' => array_map(
                fn ($type): array => $type->toArray(),
                App\Domain\Wallet\ValueObjects\WalletType::cases()
            ),
            'message' => 'Wallet types retrieved successfully',
        ]))->name('wallet-types.index');
    });

    Route::prefix('transaction-types')->group(function (): void {
        Route::get('/', fn () => response()->json([
            'success' => true,
            'data' => array_map(
                fn ($type): array => $type->toArray(),
                App\Domain\Wallet\ValueObjects\TransactionType::cases()
            ),
            'message' => 'Transaction types retrieved successfully',
        ]))->name('transaction-types.index');

        Route::get('/credit', fn () => response()->json([
            'success' => true,
            'data' => array_map(
                fn ($type) => $type->toArray(),
                App\Domain\Wallet\ValueObjects\TransactionType::getCreditTypes()
            ),
            'message' => 'Credit transaction types retrieved successfully',
        ]))->name('transaction-types.credit');

        Route::get('/debit', fn () => response()->json([
            'success' => true,
            'data' => array_map(
                fn ($type) => $type->toArray(),
                App\Domain\Wallet\ValueObjects\TransactionType::getDebitTypes()
            ),
            'message' => 'Debit transaction types retrieved successfully',
        ]))->name('transaction-types.debit');
    });

    Route::prefix('transaction-statuses')->group(function (): void {
        Route::get('/', fn () => response()->json([
            'success' => true,
            'data' => array_map(
                fn ($status): array => $status->toArray(),
                App\Domain\Wallet\ValueObjects\TransactionStatus::cases()
            ),
            'message' => 'Transaction statuses retrieved successfully',
        ]))->name('transaction-statuses.index');
    });
});

// Wallet Management routes (protected by authentication middleware)
Route::prefix('v1/wallet')->middleware(['upline.auth'])->group(function (): void {
    require __DIR__.'/api/wallet.php';
});

// Health check endpoint
Route::get('v1/health', fn () => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toISOString(),
    'services' => [
        'upline_auth' => 'active',
        'member_auth' => 'active',
    ],
]));
