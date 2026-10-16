<?php

use App\Http\Controllers\AlatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\UiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Adventure Camp Rental
|--------------------------------------------------------------------------
| Pengaturan rute publik, autentikasi tiga role, dan proteksi otorisasi.
*/

// Beranda mengarahkan ke showcase komponen UI
Route::get('/', function () {
    return redirect('/ui-showcase');
});

// Showcase Komponen UI (Publik untuk pengujian tampilan)
Route::get('/ui-showcase', [UiController::class, 'showcase'])->name('ui.showcase');

// Rute Autentikasi (Tamu)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Rute Keluar Sistem (Terotentikasi)
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Rute Dashboard & Pengelolaan Admin (Hanya untuk Role Admin)
Route::middleware(['auth', 'role:Admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [UiController::class, 'adminDashboard'])->name('dashboard');

        // CRUD Master Data Alat Camping
        Route::resource('alat', AlatController::class)->parameters(['alat' => 'kode_alat']);
    });

// Rute Dashboard & Katalog Pengguna (Hanya untuk Role Pengguna / Pelanggan)
Route::middleware(['auth', 'role:Pengguna'])
    ->prefix('pengguna')
    ->name('pengguna.')
    ->group(function () {
        Route::get('/dashboard', [UiController::class, 'penggunaDashboard'])->name('dashboard');

        // Katalog Alat Camping Siap Sewa
        Route::get('/katalog', [KatalogController::class, 'index'])->name('katalog.index');
        Route::get('/katalog/{kode_alat}', [KatalogController::class, 'show'])->name('katalog.show');

        // Halaman Instruksi Pembayaran Pesanan Disetujui
        Route::get('/pembayaran/{no_transaksi}/qris', [PembayaranController::class, 'showQris'])->name('pembayaran.qris');
        Route::get('/pembayaran/{no_transaksi}/transfer', [PembayaranController::class, 'showTransfer'])->name('pembayaran.transfer');
        Route::get('/pembayaran/{no_transaksi}/cash', [PembayaranController::class, 'showCash'])->name('pembayaran.cash');
        Route::post('/pembayaran/{no_transaksi}/upload-bukti', [PembayaranController::class, 'uploadBukti'])->name('pembayaran.upload-bukti');
    });

// Akses Berkas Bukti Pembayaran Terproteksi (Hanya Admin atau Pengguna Pemilik Transaksi)
Route::middleware('auth')
    ->get('/pembayaran/{no_transaksi}/bukti', [PembayaranController::class, 'lihatBukti'])
    ->name('pembayaran.bukti');

// Rute Dashboard Pemilik (Strictly Read-Only)
Route::middleware(['auth', 'role:Pemilik', 'pemilik.readonly'])
    ->prefix('pemilik')
    ->name('pemilik.')
    ->group(function () {
        Route::get('/dashboard', [UiController::class, 'pemilikDashboard'])->name('dashboard');

        // Endpoint pengujian pencegahan perubahan data oleh Pemilik
        Route::post('/test-action', function () {
            return response()->json(['message' => 'Should not be accessible by Pemilik']);
        })->name('test-action');
    });
