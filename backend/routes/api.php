<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MachineController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RouteController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\RfidController;
use App\Http\Middleware\VerifyDeviceKey;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {

    // --- Public ---------------------------------------------------------------
    Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    // --- Authenticated --------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Machines
        Route::get('/machines', [MachineController::class, 'index'])->name('machines.index');
        Route::get('/machines/{machine}', [MachineController::class, 'show'])->name('machines.show');
        Route::patch('/machines/{machine}/simulate-capacity', [MachineController::class, 'simulateCapacity'])
            ->middleware('role:admin')->name('machines.simulate-capacity');

        // Transactions
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

        // Users & Points
        Route::get('/users/{user}/points', [UserController::class, 'points'])->name('users.points');

        // Routes (TSP)
        Route::get('/routes/latest', [RouteController::class, 'latest'])->name('routes.latest');
        Route::post('/routes/recalculate', [RouteController::class, 'recalculate'])
            ->middleware('role:admin,petugas')->name('routes.recalculate');

        // Reports (admin only)
        Route::middleware('role:admin')->group(function () {
            Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
            Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        });

        // RFID Mobile App (Pairing)
        Route::prefix('rfid')->group(function () {
            Route::post('/scan', [RfidController::class, 'startScan']);
            Route::get('/status', [RfidController::class, 'checkStatus']);
            Route::delete('/unlink', [RfidController::class, 'unlink']);
        });
    });

    // --- IoT Device (ESP32) ---------------------------------------------------
    Route::middleware([VerifyDeviceKey::class])->post('/rfid/uid', [RfidController::class, 'receiveUid']);
});