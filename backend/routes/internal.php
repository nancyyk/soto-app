<?php

use App\Http\Controllers\Internal\InternalTelemetryController;
use App\Http\Controllers\Internal\InternalTransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal Routes — consumed by Node-RED only
|--------------------------------------------------------------------------
| Protected by X-Internal-Secret header middleware.
| NOT exposed publicly — should be firewalled in production.
*/

Route::middleware(['App\Http\Middleware\EnsureInternalSecret'])
    ->prefix('internal')
    ->name('internal.')
    ->group(function () {
        Route::post('/transactions', [InternalTransactionController::class, 'store'])->name('transactions.store');
        Route::post('/telemetry',    [InternalTelemetryController::class, 'update'])->name('telemetry.update');
    });
