<?php

use App\Http\Controllers\Wallet\TransactionController;
use App\Http\Controllers\Wallet\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Wallet API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for wallet management functionality including:
| - Wallet CRUD operations
| - Transaction management
| - Balance operations
| - Transfer operations
|
*/

// Wallet Management Routes
Route::prefix('wallets')->group(function () {

    // Wallet CRUD operations
    Route::get('/{walletId}', [WalletController::class, 'show'])
        ->name('wallets.show')
        ->where('walletId', '[0-9]+');

    Route::post('/', [WalletController::class, 'store'])
        ->name('wallets.store');

    Route::post('/initialize', [WalletController::class, 'initializeWallets'])
        ->name('wallets.initialize');

    // Wallet balance operations
    Route::get('/{walletId}/balance', [WalletController::class, 'balance'])
        ->name('wallets.balance')
        ->where('walletId', '[0-9]+');

    Route::post('/{walletId}/credit', [WalletController::class, 'credit'])
        ->name('wallets.credit')
        ->where('walletId', '[0-9]+');

    Route::post('/{walletId}/debit', [WalletController::class, 'debit'])
        ->name('wallets.debit')
        ->where('walletId', '[0-9]+');

    // Wallet status management
    Route::patch('/{walletId}/activate', [WalletController::class, 'activate'])
        ->name('wallets.activate')
        ->where('walletId', '[0-9]+');

    Route::patch('/{walletId}/deactivate', [WalletController::class, 'deactivate'])
        ->name('wallets.deactivate')
        ->where('walletId', '[0-9]+');

    // Get wallets by owner
    Route::get('/owner/{ownerId}', [WalletController::class, 'getByOwner'])
        ->name('wallets.by-owner')
        ->where('ownerId', '[0-9]+');

    // Transaction operations within wallet context
    Route::get('/{walletId}/transactions', [TransactionController::class, 'getHistory'])
        ->name('wallets.transactions.history')
        ->where('walletId', '[0-9]+');

    Route::get('/{walletId}/transactions/latest', [TransactionController::class, 'getLatest'])
        ->name('wallets.transactions.latest')
        ->where('walletId', '[0-9]+');

    Route::get('/{walletId}/transactions/summary', [TransactionController::class, 'getSummary'])
        ->name('wallets.transactions.summary')
        ->where('walletId', '[0-9]+');
});

// Transaction Management Routes
Route::prefix('transactions')->group(function () {

    // Individual transaction operations
    Route::get('/{transactionId}', [TransactionController::class, 'show'])
        ->name('transactions.show')
        ->where('transactionId', '[0-9]+');

    Route::get('/reference/{reference}', [TransactionController::class, 'getByReference'])
        ->name('transactions.by-reference');

    // Transfer operations
    Route::post('/transfer', [TransactionController::class, 'transfer'])
        ->name('transactions.transfer');

    // Statistics and reporting
    Route::get('/statistics', [TransactionController::class, 'getStatistics'])
        ->name('transactions.statistics');
});

// Helper endpoints moved to main api.php as public routes
