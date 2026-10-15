<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UiController;

/*
|--------------------------------------------------------------------------
| Web Routes — Adventure Camp Rental
|--------------------------------------------------------------------------
| Rute fondasi antarmuka dan layout dashboard.
*/

// Beranda mengarahkan ke showcase komponen UI
Route::get('/', function () {
    return redirect('/ui-showcase');
});

// Fondasi Halaman Login
Route::get('/login', [UiController::class, 'login'])->name('login');

// Showcase Seluruh Komponen Antarmuka & State
Route::get('/ui-showcase', [UiController::class, 'showcase'])->name('ui.showcase');

// Layout Dashboard Masing-Masing Role
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [UiController::class, 'adminDashboard'])->name('admin.dashboard');
});

Route::prefix('pengguna')->group(function () {
    Route::get('/dashboard', [UiController::class, 'penggunaDashboard'])->name('pengguna.dashboard');
});

Route::prefix('pemilik')->group(function () {
    Route::get('/dashboard', [UiController::class, 'pemilikDashboard'])->name('pemilik.dashboard');
});
