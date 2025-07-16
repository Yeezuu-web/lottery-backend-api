<?php

declare(strict_types=1);

use App\Http\Controllers\AgentSettings\AgentSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent Settings API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for agent settings management.
| These routes are protected by authentication middleware.
|
*/

Route::prefix('agent-settings')->group(function (): void {

    // Get agent settings
    Route::get('/{agentId}', [AgentSettingsController::class, 'show'])
        ->name('agent-settings.show')
        ->where('agentId', '[0-9]+');

    // Create agent settings
    Route::post('/', [AgentSettingsController::class, 'store'])
        ->name('agent-settings.store');

    // Update agent settings
    Route::put('/{agentId}', [AgentSettingsController::class, 'update'])
        ->name('agent-settings.update')
        ->where('agentId', '[0-9]+');

});
