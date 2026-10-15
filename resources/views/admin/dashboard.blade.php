@extends('layouts.app')

@section('title', 'Dashboard Operasional Admin')
@section('page-title', 'Dashboard Admin')

@section('content')
<div class="row g-3 mb-4">
    <!-- Stat 1: Pesanan Menunggu Konfirmasi -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Pesanan Masuk</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #b45309;">
                    ⏳
                </div>
            </div>
            <div class="ac-stat-value">3</div>
            <div class="ac-stat-sub">Menunggu konfirmasi admin</div>
        </div>
    </div>

    <!-- Stat 2: Pembayaran Menunggu Verifikasi -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Verifikasi Pembayaran</span>
                <div class="ac-stat-icon-wrap" style="background-color: #e0f2fe; color: #0369a1;">
                    💳
                </div>
            </div>
            <div class="ac-stat-value">1</div>
            <div class="ac-stat-sub">Bukti transfer belum diperiksa</div>
        </div>
    </div>

    <!-- Stat 3: Sedang Disewa -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Sedang Disewa</span>
                <div class="ac-stat-icon-wrap" style="background-color: #d8f3dc; color: #1b4332;">
                    ⛺
                </div>
            </div>
            <div class="ac-stat-value">4</div>
            <div class="ac-stat-sub">Transaksi aktif di lapangan</div>
        </div>
    </div>

    <!-- Stat 4: Total Stok Fisik -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Stok Fisik Alat</span>
                <div class="ac-stat-icon-wrap">
                    📦
                </div>
            </div>
            <div class="ac-stat-value">28</div>
            <div class="ac-stat-sub">Unit total dalam inventaris</div>
        </div>
    </div>
</div>

<!-- Antrean Pesanan Masuk -->
<x-data-table title="Antrean Pesanan Menunggu Konfirmasi" subtitle="Pesanan terbaru dari pelanggan yang memerlukan pengecekan ketersediaan stok" searchId="searchAdminOrders">
    <x-slot:actions>
        <span class="badge bg-secondary" style="font-size:0.75rem;">Operasional Admin</span>
    </x-slot:actions>

    <thead>
        <tr>
            <th>No. Transaksi</th>
            <th>Pelanggan</th>
            <th>Jadwal Sewa</th>
            <th>Item Alat</th>
            <th>Estimasi Tagihan</th>
            <th>Status Pesanan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>TRX-20261015-001</strong></td>
            <td>Andi Pratama</td>
            <td>16 Okt 2026 &rarr; 18 Okt 2026 <small class="text-muted d-block">(2 hari)</small></td>
            <td>Tenda Dome 4P (1), Sleeping Bag (2)</td>
            <td>Rp 200.000</td>
            <td><x-status-badge status="Menunggu Konfirmasi" /></td>
            <td>
                <!-- Sesuai aturan: tidak ada tombol palsu yang pura-pura bekerja -->
                <button type="button" class="btn btn-sm btn-outline-secondary disabled" title="Aksi persetujuan akan diaktifkan pada implementasi alur bisnis">
                    Tinjau (Tahap 2)
                </button>
            </td>
        </tr>
        <tr>
            <td><strong>TRX-20261014-003</strong></td>
            <td>Siti Aulia</td>
            <td>15 Okt 2026 &rarr; 17 Okt 2026 <small class="text-muted d-block">(2 hari)</small></td>
            <td>Carrier 60L (2)</td>
            <td>Rp 160.000</td>
            <td><x-status-badge status="Sedang Disewa" /></td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-secondary disabled" title="Aksi pengembalian akan diaktifkan pada implementasi alur bisnis">
                    Proses (Tahap 2)
                </button>
            </td>
        </tr>
        <tr>
            <td><strong>TRX-20261012-002</strong></td>
            <td>Budi Santoso</td>
            <td>12 Okt 2026 &rarr; 14 Okt 2026 <small class="text-muted d-block">(2 hari)</small></td>
            <td>Kompor Portable (1), Nesting (1)</td>
            <td>Rp 70.000</td>
            <td><x-status-badge status="Selesai" /></td>
            <td>
                <span class="text-muted small">Selesai</span>
            </td>
        </tr>
    </tbody>
</x-data-table>

<div class="row g-3">
    <!-- Info Inventaris Cepat -->
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100">
            <div class="ac-card-header">
                <h3 class="ac-card-title">Kondisi Alat Camping</h3>
                <span class="badge bg-secondary">Status Fisik</span>
            </div>
            <div class="ac-card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <strong>Siap Disewa (Kondisi Baik)</strong>
                        <small class="text-muted d-block">Stok dapat dipesan pelanggan</small>
                    </div>
                    <span class="badge bg-success" style="font-size:0.9rem;">24 Unit</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <strong>Perlu Perbaikan</strong>
                        <small class="text-muted d-block">Pengecekan ritsleting & frame tenda</small>
                    </div>
                    <span class="badge bg-warning text-dark" style="font-size:0.9rem;">3 Unit</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <div>
                        <strong>Rusak / Afkir</strong>
                        <small class="text-muted d-block">Tidak boleh dihitung sebagai stok sewa</small>
                    </div>
                    <span class="badge bg-danger" style="font-size:0.9rem;">1 Unit</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Catatan Pedoman Operasional -->
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100">
            <div class="ac-card-header">
                <h3 class="ac-card-title">Pedoman Operasional Admin</h3>
                <span class="badge bg-info text-dark">SOP</span>
            </div>
            <div class="ac-card-body">
                <ul class="mb-0 ps-3 small text-muted" style="line-height: 1.7;">
                    <li>Pemeriksaan stok harus dilakukan ulang sebelum menyetujui pesanan.</li>
                    <li>Jangan menyetujui pesanan jika stok periode tersebut tidak mencukupi.</li>
                    <li>Status disetujui <strong>bukan berarti</strong> pembayaran lunas.</li>
                    <li>Pembayaran via transfer hanya ditandai lunas setelah bukti transfer divalidasi.</li>
                    <li>Alat rusak wajib dicatat pada saat pengembalian dan tidak dikembalikan ke stok siap sewa.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
