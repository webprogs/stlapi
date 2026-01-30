<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DrawResultController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Admin Authentication (no auth required)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Admin authenticated routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Device Management
    Route::apiResource('devices', DeviceController::class);
    Route::post('/devices/{device}/regenerate-key', [DeviceController::class, 'regenerateKey']);

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/summary', [AnalyticsController::class, 'summary']);
        Route::get('/by-device', [AnalyticsController::class, 'byDevice']);
        Route::get('/by-period', [AnalyticsController::class, 'byPeriod']);
        Route::get('/transactions', [AnalyticsController::class, 'transactions']);
        Route::get('/bets-by-date', [AnalyticsController::class, 'betsByDate']);
        Route::get('/bets-calendar', [AnalyticsController::class, 'betsCalendar']);
        Route::get('/popular-numbers', [AnalyticsController::class, 'popularNumbers']);
    });

    // Draw Results Management
    Route::prefix('draw-results')->group(function () {
        Route::get('/by-date', [DrawResultController::class, 'getByDate']);
        Route::post('/', [DrawResultController::class, 'store']);
        Route::delete('/{id}', [DrawResultController::class, 'destroy']);
        Route::get('/winners', [DrawResultController::class, 'getWinners']);
        Route::get('/calendar', [DrawResultController::class, 'getCalendarData']);
        Route::get('/dates-with-results', [DrawResultController::class, 'getDatesWithResults']);
    });
});

// Device authenticated routes (API Key)
Route::middleware('api.key')->prefix('sync')->group(function () {
    Route::post('/push', [SyncController::class, 'push']);
    Route::get('/pull', [SyncController::class, 'pull']);
    Route::post('/full', [SyncController::class, 'full']);
    Route::get('/status', [SyncController::class, 'status']);
});

// Device authenticated routes for draw results (API Key)
Route::middleware('api.key')->prefix('draw-results')->group(function () {
    Route::get('/by-date', [DrawResultController::class, 'getByDate']);
});
