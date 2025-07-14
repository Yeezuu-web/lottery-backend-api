<?php

declare(strict_types=1);

use App\Http\Controllers\Agent\AgentController;
use App\Http\Controllers\Agent\UpdateAgentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent Management API Routes
|--------------------------------------------------------------------------
|
| Routes for agent management functionality including:
| - Listing agents (with hierarchy support)
| - Creating new agents
| - Agent drill-down navigation
| - Agent type information
|
*/

// Agent listing and management
Route::get('/', [AgentController::class, 'index'])->name('agents.index');
Route::post('/', [AgentController::class, 'store'])->name('agents.store');
Route::get('/types', [AgentController::class, 'creatableTypes'])->name('agents.types');

// Individual agent operations
Route::get('/{id}', [AgentController::class, 'show'])->name('agents.show');
Route::get('/{id}/downlines', [AgentController::class, 'downlines'])->name('agents.downlines');
Route::get('/{id}/hierarchy', [AgentController::class, 'hierarchy'])->name('agents.hierarchy');

// Additional endpoints for future expansion
Route::put('/{id}', UpdateAgentController::class)->name('agents.update');
// Route::delete('/{id}', [AgentController::class, 'destroy'])->name('agents.destroy');
// Route::patch('/{id}/status', [AgentController::class, 'updateStatus'])->name('agents.update-status');
