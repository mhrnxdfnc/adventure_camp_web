<?php

use App\Http\Controllers\AdminPembayaranController;
use App\Http\Controllers\AdminPesananController;
use App\Http\Controllers\AlatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\OperasionalController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PembelianInventarisController;
use App\Http\Controllers\PemilikDashboardController;
use App\Http\Controllers\PemilikTransaksiController;
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

        // Persetujuan & Penolakan Pengajuan Pesanan
        Route::post('/pesanan/{no_transaksi}/setujui', [AdminPesananController::class, 'setujui'])->name('pesanan.setujui');
        Route::post('/pesanan/{no_transaksi}/tolak', [AdminPesananController::class, 'tolak'])->name('pesanan.tolak');

        // Verifikasi Pembayaran Transaksi
        Route::get('/pembayaran', [AdminPembayaranController::class, 'index'])->name('pembayaran.index');
        Route::get('/pembayaran/{no_transaksi}', [AdminPembayaranController::class, 'show'])->name('pembayaran.show');
        Route::post('/pembayaran/{no_transaksi}/konfirmasi', [AdminPembayaranController::class, 'konfirmasi'])->name('pembayaran.konfirmasi');
        Route::post('/pembayaran/{no_transaksi}/tolak', [AdminPembayaranController::class, 'tolak'])->name('pembayaran.tolak');

        // Operasional Penyerahan Alat (Pengambilan)
        Route::get('/pengambilan', [OperasionalController::class, 'pengambilanIndex'])->name('operasional.pengambilan');
        Route::post('/pengambilan/{no_transaksi}', [OperasionalController::class, 'prosesPengambilan'])->name('operasional.proses-pengambilan');

        // Operasional Pengembalian Alat & Perhitungan Denda
        Route::get('/pengembalian', [OperasionalController::class, 'pengembalianIndex'])->name('operasional.pengembalian');
        Route::get('/pengembalian/{no_transaksi}', [OperasionalController::class, 'formPengembalian'])->name('operasional.form-pengembalian');
        Route::post('/pengembalian/{no_transaksi}', [OperasionalController::class, 'prosesPengembalian'])->name('operasional.proses-pengembalian');

        // Modul Inventaris & Pemantauan Stok
        Route::get('/inventaris', [InventarisController::class, 'index'])->name('inventaris.index');
        Route::get('/inventaris/riwayat', [InventarisController::class, 'riwayatIndex'])->name('inventaris.riwayat');

        // Pembelian Stok Inventaris dari Supplier
        Route::get('/inventaris/pembelian', [PembelianInventarisController::class, 'index'])->name('inventaris.pembelian.index');
        Route::get('/inventaris/pembelian/tambah', [PembelianInventarisController::class, 'create'])->name('inventaris.pembelian.create');
        Route::post('/inventaris/pembelian', [PembelianInventarisController::class, 'store'])->name('inventaris.pembelian.store');
        Route::get('/inventaris/pembelian/{no_pembelian}', [PembelianInventarisController::class, 'show'])->name('inventaris.pembelian.show');

        Route::get('/inventaris/{kode_alat}', [InventarisController::class, 'show'])->name('inventaris.show');
        Route::post('/inventaris/{kode_alat}/adjust', [InventarisController::class, 'adjustStock'])->name('inventaris.adjust-stock');
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
        Route::get('/dashboard', [PemilikDashboardController::class, 'index'])->name('dashboard');
        Route::get('/transaksi', [PemilikTransaksiController::class, 'index'])->name('transaksi.index');
        Route::get('/transaksi/{no_transaksi}', [PemilikTransaksiController::class, 'show'])->name('transaksi.show');

        // Endpoint pengujian pencegahan perubahan data oleh Pemilik
        Route::post('/test-action', function () {
            return response()->json(['message' => 'Should not be accessible by Pemilik']);
        })->name('test-action');
    });
