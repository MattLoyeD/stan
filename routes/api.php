<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChannelsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ObjectivesController;
use App\Http\Controllers\PluginsController;
use App\Http\Controllers\ProvidersController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StepsController;
use App\Http\Controllers\ToolExecutionsController;
use App\Http\Controllers\ToolPermissionsController;
use Illuminate\Support\Facades\Route;

Route::middleware('App\Http\Middleware\LocalhostOnly')->group(function () {
    Route::get('/setup/status', [AuthController::class, 'setupStatus']);
    Route::get('/auth/auto-token', [AuthController::class, 'autoToken']);
});

Route::middleware(['App\Http\Middleware\LocalhostOnly', 'App\Http\Middleware\ValidateAuthToken'])->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::apiResource('objectives', ObjectivesController::class)->only(['index', 'store', 'show']);
    Route::post('/objectives/{objective}/pause', [ObjectivesController::class, 'pause']);
    Route::post('/objectives/{objective}/resume', [ObjectivesController::class, 'resume']);
    Route::post('/objectives/{objective}/cancel', [ObjectivesController::class, 'cancel']);
    Route::get('/objectives/{objective}/steps', [StepsController::class, 'index']);

    Route::apiResource('sessions', SessionsController::class)->only(['index', 'store', 'show']);
    Route::get('/sessions/{session}/messages', [SessionsController::class, 'messages']);
    Route::post('/sessions/{session}/messages', [SessionsController::class, 'sendMessage']);
    Route::post('/sessions/{session}/close', [SessionsController::class, 'close']);

    Route::get('/tool-executions', [ToolExecutionsController::class, 'index']);

    Route::get('/tool-permissions', [ToolPermissionsController::class, 'index']);
    Route::post('/tool-permissions', [ToolPermissionsController::class, 'store']);
    Route::delete('/tool-permissions/{toolPermission}', [ToolPermissionsController::class, 'destroy']);

    Route::apiResource('providers', ProvidersController::class)->only(['index', 'store', 'destroy']);
    Route::post('/providers/{provider}/test', [ProvidersController::class, 'test']);

    Route::apiResource('plugins', PluginsController::class)->only(['index', 'destroy']);
    Route::post('/plugins/{plugin}/toggle', [PluginsController::class, 'toggle']);

    Route::apiResource('channels', ChannelsController::class)->only(['index', 'store', 'destroy']);
    Route::post('/channels/pair', [ChannelsController::class, 'pair']);

    Route::get('/settings', [SettingsController::class, 'index']);
    Route::post('/settings', [SettingsController::class, 'update']);
    Route::get('/settings/soul', [SettingsController::class, 'soul']);
    Route::post('/settings/soul', [SettingsController::class, 'updateSoul']);
});
