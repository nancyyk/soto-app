<?php

use App\Models\Machine;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::get('/login', fn () => view('auth.login'))->middleware('guest')->name('login');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', function () {
        Machine::stale()->update(['status_online' => false]);
        $machines = Machine::orderBy('id')->get();

        return view('dashboard.index', compact('machines'));
    })->name('dashboard');

    // Monitoring
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/', fn () => view('monitoring.index'))->name('index');
        Route::get('/{machine}', function (Machine $machine) {
            return view('monitoring.show', compact('machine'));
        })->name('show');
    });

    // Rute TSP
    Route::get('/rute', fn () => view('rute.index'))->name('rute.index');

    // Transaksi
    Route::get('/transaksi', fn () => view('transaksi.index'))->name('transaksi.index');

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/reward', fn () => view('reward.index'))->name('reward.index');
        Route::get('/pengguna', fn () => view('pengguna.index'))->name('pengguna.index');
        Route::get('/pengaturan', fn () => view('pengaturan.index'))->name('pengaturan.index');

        // Ekspor Laporan Rute & Kinerja Logistik
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/rute/pdf', [App\Http\Controllers\Admin\LaporanRuteController::class, 'manifestPdf'])->name('rute.pdf');
            Route::get('/rute/excel', [App\Http\Controllers\Admin\LaporanRuteController::class, 'manifestExcel'])->name('rute.excel');
            Route::get('/kinerja/pdf', [App\Http\Controllers\Admin\LaporanRuteController::class, 'kinerjaPdf'])->name('kinerja.pdf');
            Route::get('/kinerja/excel', [App\Http\Controllers\Admin\LaporanRuteController::class, 'kinerjaExcel'])->name('kinerja.excel');
        });
    });
});
