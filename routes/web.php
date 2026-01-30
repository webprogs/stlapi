<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BetsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceManagementController;
use App\Http\Controllers\Admin\DrawResultController;
use App\Http\Controllers\Admin\TransactionController;
use Illuminate\Support\Facades\Route;

// Redirect root to admin
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Admin Authentication
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Protected routes
    Route::middleware('admin.auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Devices
        Route::resource('devices', DeviceManagementController::class);
        Route::post('/devices/{device}/regenerate-key', [DeviceManagementController::class, 'regenerateKey'])
            ->name('devices.regenerate-key');

        // Transactions
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');

        // Bets
        Route::get('/bets', [BetsController::class, 'index'])->name('bets.index');
        Route::get('/bets/statistics', [BetsController::class, 'statistics'])->name('bets.statistics');

        // Analytics
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

        // Draw Results
        Route::get('/draw-results', [DrawResultController::class, 'index'])->name('draw-results.index');
        Route::post('/draw-results', [DrawResultController::class, 'store'])->name('draw-results.store');
        Route::delete('/draw-results/{id}', [DrawResultController::class, 'destroy'])->name('draw-results.destroy');
    });
});
