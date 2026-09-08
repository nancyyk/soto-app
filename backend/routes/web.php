<?php

use App\Models\Machine;
use Illuminate\Support\Facades\Route;

// ??? Auth Routes (Fortify handles POST /login, POST /logout) ?????????????????
Route::get("/login", fn() => view("auth.login"))->middleware("guest")->name("login");

// ??? Authenticated Routes ?????????????????????????????????????????????????????
Route::middleware(["auth"])->group(function () {

    // Dashboard
    Route::get("/", fn() => redirect()->route("dashboard"));
    Route::get("/dashboard", function () {
        $machines = \App\Models\Machine::orderBy("id")->get();
        return view("dashboard.index", compact("machines"));
    })->name("dashboard");

    // Monitoring
    Route::prefix("monitoring")->name("monitoring.")->group(function () {
        Route::get("/",        fn() => view("monitoring.index"))->name("index");
        Route::get("/{machine}", function (Machine $machine) {
            return view("monitoring.show", compact("machine"));
        })->name("show");
    });

    // Rute TSP
    Route::get("/rute", fn() => view("rute.index"))->name("rute.index");

    // Transaksi
    Route::get("/transaksi", fn() => view("transaksi.index"))->name("transaksi.index");

    // Admin-only routes
    Route::middleware("role:admin")->group(function () {
        Route::get("/pengguna",   fn() => view("pengguna.index"))->name("pengguna.index");
        Route::get("/pengaturan", fn() => view("pengaturan.index"))->name("pengaturan.index");
    });
});
