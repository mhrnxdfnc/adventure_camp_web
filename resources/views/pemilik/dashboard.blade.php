@extends('layouts.app')

@section('title', 'Dashboard Pemilik (Read-Only)')
@section('page-title', 'Dashboard Pemilik')

@section('content')
<!-- Read-Only Notice -->
<div class="alert alert-secondary d-flex align-items-center gap-2 mb-4 py-2 px-3 border-0 shadow-sm" style="border-radius:10px; background-color:#edf2f7; color:#334155;">
    <svg width="20" height="20" fill="currentColor" class="flex-shrink-0 text-primary" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
    </svg>
    <div class="small">
        <strong>Hak Akses Pemilik (Strictly Read-Only):</strong> Halaman ini ditujukan untuk pemantauan performa bisnis, pendapatan terkonfirmasi, dan status inventaris. Seluruh fungsi perubahan data dinonaktifkan.
    </div>
</div>

<!-- 4 Financial Stat Cards -->
<div class="row g-3 mb-4">
    <!-- Penjualan Hari Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Penjualan Hari Ini</span>
                <div class="ac-stat-icon-wrap" style="background-color: #d8f3dc; color: #1b4332;">
                    📊
                </div>
            </div>
            <div class="ac-stat-value" id="valPenjualanHariIni">{{ $penjualanHariIniCount }}</div>
            <div class="ac-stat-sub text-muted">{{ $penjualanHariIniCount }} Transaksi sewa (WIB)</div>
            <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted small">Nilai Pesanan:</span>
                <span class="fw-bold small text-dark">Rp {{ number_format($nilaiPesananHariIni, 0, ',', '.') }}</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-success w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalRincianHariIni" style="font-size: 0.75rem;">
                🔍 Lihat Rincian Hari Ini
            </button>
        </div>
    </div>

    <!-- Pendapatan Hari Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Pendapatan Hari Ini</span>
                <div class="ac-stat-icon-wrap" style="background-color: #e0f2fe; color: #0369a1;">
                    💵
                </div>
            </div>
            <div class="ac-stat-value" id="valPendapatanHariIni" style="font-size:1.45rem;">Rp {{ number_format($pendapatanHariIni, 0, ',', '.') }}</div>
            <div class="ac-stat-sub text-muted">Pembayaran terverifikasi lunas</div>
            <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted small">Uang Masuk Nyata:</span>
                <span class="fw-bold small text-primary">Rp {{ number_format($pendapatanHariIni, 0, ',', '.') }}</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalRincianHariIni" style="font-size: 0.75rem;">
                🔍 Rincian Penerimaan
            </button>
        </div>
    </div>

    <!-- Penjualan Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Penjualan Bulan Ini</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #92400e;">
                    📈
                </div>
            </div>
            <div class="ac-stat-value" id="valPenjualanBulanIni">{{ $penjualanBulanIniCount }}</div>
            <div class="ac-stat-sub text-muted">{{ $penjualanBulanIniCount }} Transaksi bulan berjalan</div>
            <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted small">Nilai Pesanan:</span>
                <span class="fw-bold small text-dark">Rp {{ number_format($nilaiPesananBulanIni, 0, ',', '.') }}</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-warning w-100 mt-2 text-dark" data-bs-toggle="modal" data-bs-target="#modalRincianBulanIni" style="font-size: 0.75rem;">
                🔍 Lihat Rincian Bulan Ini
            </button>
        </div>
    </div>

    <!-- Pendapatan Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Pendapatan Bulan Ini</span>
                <div class="ac-stat-icon-wrap" style="background-color: #dcfce7; color: #15803d;">
                    🏦
                </div>
            </div>
            <div class="ac-stat-value" id="valPendapatanBulanIni" style="font-size:1.45rem;">Rp {{ number_format($pendapatanBulanIni, 0, ',', '.') }}</div>
            <div class="ac-stat-sub text-muted">Dana masuk sah terverifikasi</div>
            <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted small">Uang Masuk Nyata:</span>
                <span class="fw-bold small text-success">Rp {{ number_format($pendapatanBulanIni, 0, ',', '.') }}</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-success w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalRincianBulanIni" style="font-size: 0.75rem;">
                🔍 Rincian Penerimaan
            </button>
        </div>
    </div>
</div>

<!-- Modal Rincian Hari Ini -->
<div class="modal fade" id="modalRincianHariIni" tabindex="-1" aria-labelledby="modalRincianHariIniLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRincianHariIniLabel">📋 Rincian Penjualan & Penerimaan Hari Ini ({{ $todayDateFormatted }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom d-flex flex-wrap gap-4 justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Total Transaksi Lunas</small>
                        <strong class="text-dark">{{ $penjualanHariIniCount }} Transaksi</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Nilai Pesanan</small>
                        <strong class="text-dark">Rp {{ number_format($nilaiPesananHariIni, 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Uang Benar-benar Diterima</small>
                        <strong class="text-success">Rp {{ number_format($pendapatanHariIni, 0, ',', '.') }}</strong>
                    </div>
                </div>

                @if($rincianHariIni->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <div class="mb-2" style="font-size: 2rem;">📭</div>
                        <p class="mb-0">Belum ada transaksi pembayaran yang dikonfirmasi pada hari ini ({{ $todayDateFormatted }}).</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Pembayaran</th>
                                    <th>No. Transaksi</th>
                                    <th>Pelanggan</th>
                                    <th>Metode</th>
                                    <th>Waktu Konfirmasi</th>
                                    <th class="text-end">Nilai Pesanan</th>
                                    <th class="text-end">Uang Diterima</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rincianHariIni as $bayar)
                                    <tr>
                                        <td><code>{{ $bayar->no_pembayaran }}</code></td>
                                        <td><strong>{{ $bayar->no_transaksi }}</strong></td>
                                        <td>{{ $bayar->penyewaan?->pelanggan?->nama_pelanggan ?? 'Umum' }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $bayar->metode_pembayaran }}</span></td>
                                        <td><small class="text-muted">{{ $bayar->waktu_konfirmasi ? \Carbon\Carbon::parse($bayar->waktu_konfirmasi)->timezone('Asia/Jakarta')->format('H:i:s') . ' WIB' : '-' }}</small></td>
                                        <td class="text-end">Rp {{ number_format((float) ($bayar->total_biaya > 0 ? $bayar->total_biaya : ($bayar->penyewaan?->total_biaya ?? 0)), 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-success">Rp {{ number_format((float) $bayar->jumlah_pembayaran, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rincian Bulan Ini -->
<div class="modal fade" id="modalRincianBulanIni" tabindex="-1" aria-labelledby="modalRincianBulanIniLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRincianBulanIniLabel">📋 Rincian Penjualan & Penerimaan Bulan Ini ({{ $currentMonthFormatted }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom d-flex flex-wrap gap-4 justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Total Transaksi Lunas</small>
                        <strong class="text-dark">{{ $penjualanBulanIniCount }} Transaksi</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Nilai Pesanan</small>
                        <strong class="text-dark">Rp {{ number_format($nilaiPesananBulanIni, 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Uang Benar-benar Diterima</small>
                        <strong class="text-success">Rp {{ number_format($pendapatanBulanIni, 0, ',', '.') }}</strong>
                    </div>
                </div>

                @if($rincianBulanIni->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <div class="mb-2" style="font-size: 2rem;">📭</div>
                        <p class="mb-0">Belum ada transaksi pembayaran yang dikonfirmasi pada bulan berjalan ({{ $currentMonthFormatted }}).</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Pembayaran</th>
                                    <th>No. Transaksi</th>
                                    <th>Pelanggan</th>
                                    <th>Metode</th>
                                    <th>Waktu Konfirmasi</th>
                                    <th class="text-end">Nilai Pesanan</th>
                                    <th class="text-end">Uang Diterima</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rincianBulanIni as $bayar)
                                    <tr>
                                        <td><code>{{ $bayar->no_pembayaran }}</code></td>
                                        <td><strong>{{ $bayar->no_transaksi }}</strong></td>
                                        <td>{{ $bayar->penyewaan?->pelanggan?->nama_pelanggan ?? 'Umum' }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $bayar->metode_pembayaran }}</span></td>
                                        <td><small class="text-muted">{{ $bayar->waktu_konfirmasi ? \Carbon\Carbon::parse($bayar->waktu_konfirmasi)->timezone('Asia/Jakarta')->format('d M H:i') . ' WIB' : '-' }}</small></td>
                                        <td class="text-end">Rp {{ number_format((float) ($bayar->total_biaya > 0 ? $bayar->total_biaya : ($bayar->penyewaan?->total_biaya ?? 0)), 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-success">Rp {{ number_format((float) $bayar->jumlah_pembayaran, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Pesanan Pelanggan (Read-Only) -->
<x-data-table title="Riwayat Pesanan Pelanggan (Pengawasan Pemilik)" subtitle="Pantau aktivitas penyewaan dan pembayaran pelanggan secara transparan" searchId="searchOwnerOrders">
    <x-slot:actions>
        <span class="badge bg-secondary" style="font-size:0.75rem;">Mode Read-Only</span>
    </x-slot:actions>

    <thead>
        <tr>
            <th>No. Transaksi</th>
            <th>Nama Pelanggan</th>
            <th>Tanggal Sewa</th>
            <th>Durasi</th>
            <th>Total Tagihan</th>
            <th>Metode Pembayaran</th>
            <th>Status Pembayaran</th>
            <th>Status Pesanan</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>TRX-20261011-001</strong></td>
            <td>Andi Pratama</td>
            <td>12 Okt 2026</td>
            <td>2 hari</td>
            <td><strong>Rp 610.000</strong></td>
            <td>Cash (Kasir)</td>
            <td><x-status-badge status="Lunas" /></td>
            <td><x-status-badge status="Selesai" /></td>
        </tr>
        <tr>
            <td><strong>TRX-20261006-001</strong></td>
            <td>Budi Santoso</td>
            <td>06 Okt 2026</td>
            <td>1 hari</td>
            <td><strong>Rp 150.000</strong></td>
            <td>Transfer Bank</td>
            <td><x-status-badge status="Lunas" /></td>
            <td><x-status-badge status="Selesai" /></td>
        </tr>
        <tr>
            <td><strong>TRX-20261005-001</strong></td>
            <td>Siti Aulia</td>
            <td>05 Okt 2026</td>
            <td>2 hari</td>
            <td><strong>Rp 380.000</strong></td>
            <td>QRIS (Kasir)</td>
            <td><x-status-badge status="Lunas" /></td>
            <td><x-status-badge status="Selesai" /></td>
        </tr>
    </tbody>
</x-data-table>

<!-- Daftar Alat Rusak / Perlu Perbaikan (Read-Only) -->
<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100">
            <div class="ac-card-header">
                <h3 class="ac-card-title text-danger">⚠️ Laporan Alat Rusak & Afkir</h3>
                <span class="badge bg-danger">Read-Only</span>
            </div>
            <div class="ac-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Alat</th>
                                <th>Kondisi</th>
                                <th>Stok Fisik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ALT004</td>
                                <td>Matras Camping</td>
                                <td><x-status-badge status="Rusak" /></td>
                                <td>1 unit</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="ac-card h-100">
            <div class="ac-card-header">
                <h3 class="ac-card-title text-warning">🔧 Alat Perlu Perbaikan</h3>
                <span class="badge bg-warning text-dark">Read-Only</span>
            </div>
            <div class="ac-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Alat</th>
                                <th>Kondisi</th>
                                <th>Stok Fisik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ALT001</td>
                                <td>Tenda Dome 4 Orang</td>
                                <td><x-status-badge status="Perlu Perbaikan" /></td>
                                <td>1 unit</td>
                            </tr>
                            <tr>
                                <td>ALT003</td>
                                <td>Carrier 60L</td>
                                <td><x-status-badge status="Perlu Perbaikan" /></td>
                                <td>2 unit</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
