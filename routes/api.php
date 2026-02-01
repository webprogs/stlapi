<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DrawResultController;
use App\Http\Controllers\PingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\SyncLogController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('draw-results/today', [DrawResultController::class, 'today']);

// Admin authentication
Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('me', [AdminAuthController::class, 'me']);

        // Device management
        Route::apiResource('devices', DeviceController::class);
        Route::post('devices/{device}/regenerate-key', [DeviceController::class, 'regenerateKey']);

        // Transaction management
        Route::get('transactions', [TransactionController::class, 'index']);
        Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
        Route::post('transactions/{transaction}/claim', [TransactionController::class, 'claim']);

        // Draw results management
        Route::apiResource('draw-results', DrawResultController::class);

        // Analytics
        Route::prefix('analytics')->group(function () {
            Route::get('summary', [AnalyticsController::class, 'summary']);
            Route::get('by-game', [AnalyticsController::class, 'byGame']);
            Route::get('by-draw-time', [AnalyticsController::class, 'byDrawTime']);
            Route::get('by-device', [AnalyticsController::class, 'byDevice']);
            Route::get('daily', [AnalyticsController::class, 'daily']);
            Route::get('top-numbers', [AnalyticsController::class, 'topNumbers']);
            Route::get('device/{device}', [AnalyticsController::class, 'device']);
        });

        // Reports
        Route::get('reports/daily', [ReportController::class, 'daily']);

        // Sync logs
        Route::get('sync-logs', [SyncLogController::class, 'index']);
    });
});

// Device API (requires device authentication)
Route::middleware('auth.device')->group(function () {
    Route::get('ping', PingController::class);
    Route::post('sync', [SyncController::class, 'sync']);
    Route::post('sync/batch', [SyncController::class, 'batch']);
    Route::get('sync/pull', [SyncController::class, 'pull']);
});
