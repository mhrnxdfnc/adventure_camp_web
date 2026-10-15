@extends('layouts.app')

@section('title', 'Dashboard Pengguna')
@section('page-title', 'Dashboard Pengguna')

@section('content')
<!-- Welcome Banner -->
<div class="ac-card p-4 mb-4" style="background: linear-gradient(135deg, #1b4332, #2d6a4f); color: white; border: none;">
    <div class="row align-items-center">
        <div class="col-12 col-md-8">
            <span class="badge bg-white text-success mb-2" style="font-size:0.75rem;">Area Pelanggan</span>
            <h2 class="font-weight-bold mb-2">Halo, {{ Auth::user()->nama_user ?? 'Pelanggan Camping' }}! 👋</h2>
            <p class="mb-0 text-white-50" style="font-size:0.95rem;">
                Pantau pesanan alat outdoor kamu, cek tagihan sewa, dan konfirmasi pembayaran dengan mudah.
            </p>
        </div>
        <div class="col-12 col-md-4 mt-3 mt-md-0 text-md-end">
            <span class="btn btn-light text-success font-weight-bold disabled" title="Katalog alat lengkap akan aktif pada tahap pemesanan">
                Katalog Alat (Tahap 2)
            </span>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Stat 1 -->
    <div class="col-12 col-sm-6">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Pesanan Aktif Saya</span>
                <div class="ac-stat-icon-wrap">
                    🏕️
                </div>
            </div>
            <div class="ac-stat-value">1</div>
            <div class="ac-stat-sub">Transaksi sedang diproses</div>
        </div>
    </div>

    <!-- Stat 2 -->
    <div class="col-12 col-sm-6">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Tagihan Menunggu Pembayaran</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #b45309;">
                    💰
                </div>
            </div>
            <div class="ac-stat-value">Rp 200.000</div>
            <div class="ac-stat-sub">Pesanan disetujui admin</div>
        </div>
    </div>
</div>

<!-- Pesanan Aktif Pengguna (Card Layout Responsif) -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="ac-card-title">Riwayat & Status Pesanan Saya</h3>
    <small class="text-muted">Hanya menampilkan transaksi milik Anda</small>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="ac-card">
            <div class="ac-card-header">
                <div>
                    <span class="text-muted small">KODE TRANSAKSI</span>
                    <h4 class="mb-0 font-weight-bold" style="font-size:1.1rem; color:var(--ac-primary);">TRX-20261015-001</h4>
                </div>
                <x-status-badge status="Menunggu Pembayaran" />
            </div>
            <div class="ac-card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Periode Sewa</small>
                    <strong>16 Okt 2026 &mdash; 18 Okt 2026</strong> <span class="text-muted small">(2 hari)</span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Rincian Alat Disewa</small>
                    <div class="d-flex justify-content-between small py-1 border-bottom">
                        <span>Tenda Dome 4 Orang &times; 1</span>
                        <span>Rp 100.000</span>
                    </div>
                    <div class="d-flex justify-content-between small py-1">
                        <span>Sleeping Bag &times; 2</span>
                        <span>Rp 100.000</span>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="font-weight-bold">Total Tagihan:</span>
                        <strong class="text-success" style="font-size:1.1rem;">Rp 200.000</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center text-muted small">
                        <span>Status Pembayaran:</span>
                        <x-status-badge status="Belum Dibayar" />
                    </div>
                </div>

                <!-- Info pembayaran sesuai SOP: tidak ada QR acak dan tidak ada tombol bayar pura-pura -->
                <div class="alert alert-info py-2 px-3 mb-0 small" style="border-radius:8px;">
                    <strong>Petunjuk Pembayaran:</strong> Instruksi bayar resmi (QRIS kasir / transfer rekening bank) akan ditampilkan di halaman ini setelah modul pembayaran diaktifkan.
                </div>
            </div>
        </div>
    </div>

    <!-- Empty State Demonstrasi -->
    <div class="col-12 col-lg-6">
        <x-empty-state 
            title="Tidak Ada Pesanan Lain" 
            message="Kamu tidak memiliki pesanan lain yang sedang berjalan atau menunggu verifikasi saat ini." 
            icon="⛺"
        />
    </div>
</div>
@endsection
