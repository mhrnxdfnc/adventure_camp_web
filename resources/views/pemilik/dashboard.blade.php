@extends('layouts.app')

@section('title', 'Dashboard Pemilik (Read-Only)')
@section('page-title', 'Dashboard Pemilik')

@push('styles')
<style>
.ac-stock-card {
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.ac-stock-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
}
</style>
@endpush

@section('content')
<!-- Read-Only Notice -->
<div class="alert alert-secondary d-flex align-items-center gap-2 mb-4 py-2 px-3 border-0 shadow-sm" style="border-radius:10px; background-color:#edf2f7; color:#334155;">
    <svg width="20" height="20" fill="currentColor" class="flex-shrink-0 text-primary" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
    </svg>
    <div class="small">
        <strong>Hak Akses Pemilik (Strictly Read-Only):</strong> Halaman ini ditujukan untuk pemantauan performa bisnis, penerimaan kas nyata terkonfirmasi, dan status inventaris. Seluruh fungsi perubahan data dinonaktifkan.
    </div>
</div>

<!-- METRIK KEUANGAN HARI INI -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="fw-bold mb-0 text-dark">📅 Performa Keuangan Hari Ini ({{ $todayDateFormatted }})</h5>
    <span class="badge bg-light text-secondary border">Waktu Indonesia Barat (WIB)</span>
</div>
<div class="row g-3 mb-4">
    <!-- 1. Penerimaan Pelanggan Hari Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card border-success-subtle h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="ac-stat-head">
                    <span class="ac-stat-label text-success-emphasis fw-bold">Pendapatan Hari Ini</span>
                    <div class="ac-stat-icon-wrap" style="background-color: #dcfce7; color: #15803d;">
                        💵
                    </div>
                </div>
                <div class="ac-stat-value text-success font-monospace" id="valPendapatanHariIni" style="font-size:1.45rem;">Rp {{ number_format($pendapatanHariIni, 0, ',', '.') }}</div>
                <div class="ac-stat-sub text-muted"><span id="valPenjualanHariIni">{{ $penjualanHariIniCount }}</span> Penjualan Hari Ini (Transaksi Lunas)</div>
                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Total Nilai Pesanan:</span>
                    <span class="fw-bold small text-dark">Rp {{ number_format($nilaiPesananHariIni, 0, ',', '.') }}</span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-success w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalRincianHariIni" style="font-size: 0.75rem;">
                🔍 Rincian Penerimaan ({{ $penjualanHariIniCount }})
            </button>
        </div>
    </div>

    <!-- 2. Pembelian Stok Supplier Hari Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card border-primary-subtle h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="ac-stat-head">
                    <span class="ac-stat-label text-primary-emphasis fw-bold">Pembelian Stok Supplier</span>
                    <div class="ac-stat-icon-wrap" style="background-color: #e0f2fe; color: #0369a1;">
                        📦
                    </div>
                </div>
                <div class="ac-stat-value text-primary font-monospace" style="font-size:1.4rem;">Rp {{ number_format($totalPembelianHariIni, 0, ',', '.') }}</div>
                <div class="ac-stat-sub text-muted">{{ $pembelianHariIniCount }} Transaksi pengadaan supplier</div>
                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Status Pengakuan:</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.7rem;">Pengeluaran Sah</span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalRincianPembelianHariIni" style="font-size: 0.75rem;">
                🔍 Rincian Pembelian ({{ $pembelianHariIniCount }})
            </button>
        </div>
    </div>

    <!-- 3. Biaya Perbaikan Hari Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card border-warning-subtle h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="ac-stat-head">
                    <span class="ac-stat-label text-warning-emphasis fw-bold">Biaya Perbaikan Alat</span>
                    <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #b45309;">
                        🔧
                    </div>
                </div>
                <div class="ac-stat-value text-warning font-monospace" style="font-size:1.4rem;">Rp {{ number_format($totalBiayaPerbaikanHariIni, 0, ',', '.') }}</div>
                <div class="ac-stat-sub text-muted">{{ $perbaikanHariIniCount }} Catatan pengeluaran servis</div>
                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Alokasi Biaya:</span>
                    <span class="fw-bold small text-dark">Perawatan Unit</span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-warning text-dark w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalRincianPerbaikanHariIni" style="font-size: 0.75rem;">
                🔍 Rincian Biaya Servis ({{ $perbaikanHariIniCount }})
            </button>
        </div>
    </div>

    <!-- 4. Arus Kas Bersih Hari Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card border-secondary h-100 d-flex flex-column justify-content-between" style="background-color: #f8fafc;">
            <div>
                <div class="ac-stat-head">
                    <span class="ac-stat-label text-dark fw-bold">Arus Kas Bersih Hari Ini</span>
                    <div class="ac-stat-icon-wrap" style="background-color: {{ $arusKasBersihHariIni >= 0 ? '#dcfce7' : '#fee2e2' }}; color: {{ $arusKasBersihHariIni >= 0 ? '#15803d' : '#b91c1c' }};">
                        {{ $arusKasBersihHariIni >= 0 ? '📈' : '📉' }}
                    </div>
                </div>
                <div class="ac-stat-value font-monospace {{ $arusKasBersihHariIni >= 0 ? 'text-success' : 'text-danger' }}" style="font-size:1.4rem;">
                    Rp {{ number_format($arusKasBersihHariIni, 0, ',', '.') }}
                </div>
                <div class="ac-stat-sub text-muted">Penerimaan &minus; Pembelian &minus; Perbaikan</div>
                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Status Arus Kas:</span>
                    <span class="badge {{ $arusKasBersihHariIni >= 0 ? 'bg-success text-white' : 'bg-danger text-white' }}" style="font-size:0.7rem;">
                        {{ $arusKasBersihHariIni >= 0 ? 'Surplus (+)' : 'Defisit (-)' }}
                    </span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-dark w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalRincianArusKasHariIni" style="font-size: 0.75rem;">
                🔍 Kalkulasi Arus Kas Bersih
            </button>
        </div>
    </div>
</div>

<!-- METRIK KEUANGAN BULAN BERJALAN (MTD) -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="fw-bold mb-0 text-dark">📊 Akumulasi Keuangan Bulan Ini ({{ $currentMonthFormatted }})</h5>
    <span class="badge bg-light text-secondary border">Month-to-Date (MTD)</span>
</div>
<div class="row g-3 mb-4">
    <!-- 5. Penerimaan Pelanggan Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card border-success-subtle h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="ac-stat-head">
                    <span class="ac-stat-label text-success-emphasis fw-bold">Pendapatan Bulan Ini</span>
                    <div class="ac-stat-icon-wrap" style="background-color: #dcfce7; color: #15803d;">
                        🏦
                    </div>
                </div>
                <div class="ac-stat-value text-success font-monospace" id="valPendapatanBulanIni" style="font-size:1.45rem;">Rp {{ number_format($pendapatanBulanIni, 0, ',', '.') }}</div>
                <div class="ac-stat-sub text-muted"><span id="valPenjualanBulanIni">{{ $penjualanBulanIniCount }}</span> Penjualan Bulan Ini (Transaksi Lunas)</div>
                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Total Nilai Pesanan:</span>
                    <span class="fw-bold small text-dark">Rp {{ number_format($nilaiPesananBulanIni, 0, ',', '.') }}</span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-success w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalRincianBulanIni" style="font-size: 0.75rem;">
                🔍 Rincian Penerimaan ({{ $penjualanBulanIniCount }})
            </button>
        </div>
    </div>

    <!-- 6. Pembelian Stok Supplier Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card border-primary-subtle h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="ac-stat-head">
                    <span class="ac-stat-label text-primary-emphasis fw-bold">Pembelian Stok Bulan Ini</span>
                    <div class="ac-stat-icon-wrap" style="background-color: #e0f2fe; color: #0369a1;">
                        🛒
                    </div>
                </div>
                <div class="ac-stat-value text-primary font-monospace" style="font-size:1.4rem;">Rp {{ number_format($totalPembelianBulanIni, 0, ',', '.') }}</div>
                <div class="ac-stat-sub text-muted">{{ $pembelianBulanIniCount }} Transaksi pengadaan stok</div>
                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Akumulasi Belanja:</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.7rem;">Stok Toko Bertambah</span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalRincianPembelianBulanIni" style="font-size: 0.75rem;">
                🔍 Rincian Pembelian ({{ $pembelianBulanIniCount }})
            </button>
        </div>
    </div>

    <!-- 7. Biaya Perbaikan Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card border-warning-subtle h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="ac-stat-head">
                    <span class="ac-stat-label text-warning-emphasis fw-bold">Biaya Perbaikan Bulan Ini</span>
                    <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #b45309;">
                        🛠️
                    </div>
                </div>
                <div class="ac-stat-value text-warning font-monospace" style="font-size:1.4rem;">Rp {{ number_format($totalBiayaPerbaikanBulanIni, 0, ',', '.') }}</div>
                <div class="ac-stat-sub text-muted">{{ $perbaikanBulanIniCount }} Catatan pengeluaran servis</div>
                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Status Perawatan:</span>
                    <span class="fw-bold small text-dark">Operasional Toko</span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-warning text-dark w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalRincianPerbaikanBulanIni" style="font-size: 0.75rem;">
                🔍 Rincian Biaya Servis ({{ $perbaikanBulanIniCount }})
            </button>
        </div>
    </div>

    <!-- 8. Arus Kas Bersih Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card border-secondary h-100 d-flex flex-column justify-content-between" style="background-color: #f8fafc;">
            <div>
                <div class="ac-stat-head">
                    <span class="ac-stat-label text-dark fw-bold">Arus Kas Bersih Bulan Ini</span>
                    <div class="ac-stat-icon-wrap" style="background-color: {{ $arusKasBersihBulanIni >= 0 ? '#dcfce7' : '#fee2e2' }}; color: {{ $arusKasBersihBulanIni >= 0 ? '#15803d' : '#b91c1c' }};">
                        {{ $arusKasBersihBulanIni >= 0 ? '💰' : '⚠️' }}
                    </div>
                </div>
                <div class="ac-stat-value font-monospace {{ $arusKasBersihBulanIni >= 0 ? 'text-success' : 'text-danger' }}" style="font-size:1.4rem;">
                    Rp {{ number_format($arusKasBersihBulanIni, 0, ',', '.') }}
                </div>
                <div class="ac-stat-sub text-muted">Akumulasi bersih bulan berjalan</div>
                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Status Keuangan:</span>
                    <span class="badge {{ $arusKasBersihBulanIni >= 0 ? 'bg-success text-white' : 'bg-danger text-white' }}" style="font-size:0.7rem;">
                        {{ $arusKasBersihBulanIni >= 0 ? 'Surplus (+)' : 'Defisit (-)' }}
                    </span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-dark w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalRincianArusKasBulanIni" style="font-size: 0.75rem;">
                🔍 Kalkulasi Arus Kas Bersih
            </button>
        </div>
    </div>
</div>

<!-- MODAL 1: RINCIAN PENERIMAAN KAS PELANGGAN HARI INI -->
<div class="modal fade" id="modalRincianHariIni" tabindex="-1" aria-labelledby="modalRincianHariIniLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRincianHariIniLabel">📋 Rincian Penerimaan Kas Pelanggan Hari Ini ({{ $todayDateFormatted }})</h5>
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
                                    <th>Sumber</th>
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
                                        <td>
                                            @if($bayar->penyewaan?->isOnsite())
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size:0.7rem;">📱 On-site</span>
                                            @else
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.7rem;">🌐 Online</span>
                                            @endif
                                        </td>
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

<!-- MODAL 2: RINCIAN PEMBELIAN STOK SUPPLIER HARI INI -->
<div class="modal fade" id="modalRincianPembelianHariIni" tabindex="-1" aria-labelledby="modalRincianPembelianHariIniLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRincianPembelianHariIniLabel">📦 Rincian Pengadaan Stok Supplier Hari Ini ({{ $todayDateFormatted }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom d-flex flex-wrap gap-4 justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Jumlah PO Pembelian</small>
                        <strong class="text-dark">{{ $pembelianHariIniCount }} Transaksi PO</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Pengeluaran Stok</small>
                        <strong class="text-primary font-monospace">Rp {{ number_format($totalPembelianHariIni, 0, ',', '.') }}</strong>
                    </div>
                </div>

                @if($rincianPembelianHariIni->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <div class="mb-2" style="font-size: 2rem;">📭</div>
                        <p class="mb-0">Tidak ada pencatatan pembelian stok dari supplier pada hari ini.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. PO</th>
                                    <th>Supplier</th>
                                    <th>Item Alat yang Dibeli</th>
                                    <th>Admin</th>
                                    <th class="text-end">Total Pembelian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rincianPembelianHariIni as $beli)
                                    <tr>
                                        <td><code>{{ $beli->no_pembelian }}</code></td>
                                        <td><strong>{{ $beli->supplier?->nama_supplier ?? 'Supplier' }}</strong></td>
                                        <td>
                                            @foreach($beli->detailPembelian as $d)
                                                <div class="small">
                                                    &bull; {{ $d->alat->nama_alat ?? $d->kode_alat }} ({{ $d->kuantitas }} unit @ Rp {{ number_format($d->harga_beli_satuan, 0, ',', '.') }})
                                                </div>
                                            @endforeach
                                        </td>
                                        <td><small class="text-muted">{{ $beli->admin?->nama ?? 'Admin' }}</small></td>
                                        <td class="text-end fw-bold text-primary font-monospace">
                                            Rp {{ number_format($beli->total_pembelian, 0, ',', '.') }}
                                        </td>
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

<!-- MODAL 3: RINCIAN BIAYA PERBAIKAN HARI INI -->
<div class="modal fade" id="modalRincianPerbaikanHariIni" tabindex="-1" aria-labelledby="modalRincianPerbaikanHariIniLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRincianPerbaikanHariIniLabel">🔧 Rincian Biaya Servis / Perbaikan Hari Ini ({{ $todayDateFormatted }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom d-flex flex-wrap gap-4 justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Catatan Servis Berbayar</small>
                        <strong class="text-dark">{{ $perbaikanHariIniCount }} Catatan</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Biaya Perbaikan</small>
                        <strong class="text-warning font-monospace">Rp {{ number_format($totalBiayaPerbaikanHariIni, 0, ',', '.') }}</strong>
                    </div>
                </div>

                @if($rincianPerbaikanHariIni->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <div class="mb-2" style="font-size: 2rem;">🛠️</div>
                        <p class="mb-0">Tidak ada pengeluaran biaya perbaikan atau servis alat yang dicatat pada hari ini.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Alat</th>
                                    <th>Tindakan</th>
                                    <th>Teknisi / Pelaksana</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">Biaya Servis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rincianPerbaikanHariIni as $perbaikan)
                                    <tr>
                                        <td>
                                            <code>{{ $perbaikan->kode_alat }}</code> &mdash; {{ $perbaikan->alat?->nama_alat }} ({{ $perbaikan->jumlah_unit }} unit)
                                        </td>
                                        <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $perbaikan->jenis_tindakan }}</span></td>
                                        <td>{{ $perbaikan->pelaksana_teknisi ?: '-' }}</td>
                                        <td><small class="text-muted">{{ $perbaikan->keterangan ?: '-' }}</small></td>
                                        <td class="text-end fw-bold text-warning font-monospace">Rp {{ number_format($perbaikan->biaya_perbaikan, 0, ',', '.') }}</td>
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

<!-- MODAL 4: KALKULASI ARUS KAS BERSIH HARI INI -->
<div class="modal fade" id="modalRincianArusKasHariIni" tabindex="-1" aria-labelledby="modalRincianArusKasHariIniLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6" id="modalRincianArusKasHariIniLabel">⚖️ Rekonsiliasi Arus Kas Bersih Hari Ini ({{ $todayDateFormatted }})</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <div class="list-group list-group-flush mb-3">
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                        <div>
                            <span class="fw-bold text-success">➕ Penerimaan Kas Pelanggan</span>
                            <small class="d-block text-muted">{{ $penjualanHariIniCount }} transaksi pembayaran lunas</small>
                        </div>
                        <span class="font-monospace fw-bold text-success fs-6">Rp {{ number_format($pendapatanHariIni, 0, ',', '.') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                        <div>
                            <span class="fw-bold text-primary">➖ Pengeluaran Pembelian Stok Supplier</span>
                            <small class="d-block text-muted">{{ $pembelianHariIniCount }} transaksi PO pengadaan inventaris</small>
                        </div>
                        <span class="font-monospace fw-bold text-primary fs-6">&minus; Rp {{ number_format($totalPembelianHariIni, 0, ',', '.') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                        <div>
                            <span class="fw-bold text-warning">➖ Pengeluaran Biaya Perbaikan Alat</span>
                            <small class="d-block text-muted">{{ $perbaikanHariIniCount }} catatan biaya servis unit rusak</small>
                        </div>
                        <span class="font-monospace fw-bold text-warning fs-6">&minus; Rp {{ number_format($totalBiayaPerbaikanHariIni, 0, ',', '.') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-0 border-top border-2">
                        <div>
                            <strong class="fs-6 text-dark">Arus Kas Bersih Hari Ini</strong>
                            <small class="d-block text-muted">Kas Masuk Nyata &minus; Pengeluaran Sah</small>
                        </div>
                        <strong class="font-monospace fs-5 {{ $arusKasBersihHariIni >= 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format($arusKasBersihHariIni, 0, ',', '.') }}
                        </strong>
                    </div>
                </div>
                <div class="alert {{ $arusKasBersihHariIni >= 0 ? 'alert-success' : 'alert-danger' }} py-2 px-3 mb-0 small">
                    <strong>Status: {{ $arusKasBersihHariIni >= 0 ? 'Surplus Operasional' : 'Defisit Operasional' }}</strong> &mdash; 
                    {{ $arusKasBersihHariIni >= 0 ? 'Kas operasional hari ini dalam posisi positif.' : 'Pengeluaran pengadaan stok/perbaikan hari ini melampaui penerimaan kas.' }}
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 5: RINCIAN PENERIMAAN KAS PELANGGAN BULAN INI -->
<div class="modal fade" id="modalRincianBulanIni" tabindex="-1" aria-labelledby="modalRincianBulanIniLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRincianBulanIniLabel">📋 Rincian Penerimaan Kas Pelanggan Bulan Ini ({{ $currentMonthFormatted }})</h5>
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
                                    <th>Sumber</th>
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
                                        <td>
                                            @if($bayar->penyewaan?->isOnsite())
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size:0.7rem;">📱 On-site</span>
                                            @else
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.7rem;">🌐 Online</span>
                                            @endif
                                        </td>
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

<!-- MODAL 6: RINCIAN PEMBELIAN STOK SUPPLIER BULAN INI -->
<div class="modal fade" id="modalRincianPembelianBulanIni" tabindex="-1" aria-labelledby="modalRincianPembelianBulanIniLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRincianPembelianBulanIniLabel">📦 Rincian Pengadaan Stok Supplier Bulan Ini ({{ $currentMonthFormatted }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom d-flex flex-wrap gap-4 justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Jumlah PO Pembelian</small>
                        <strong class="text-dark">{{ $pembelianBulanIniCount }} Transaksi PO</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Pengeluaran Stok MTD</small>
                        <strong class="text-primary font-monospace">Rp {{ number_format($totalPembelianBulanIni, 0, ',', '.') }}</strong>
                    </div>
                </div>

                @if($rincianPembelianBulanIni->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <div class="mb-2" style="font-size: 2rem;">📭</div>
                        <p class="mb-0">Tidak ada pencatatan pembelian stok dari supplier pada bulan ini.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. PO</th>
                                    <th>Tanggal</th>
                                    <th>Supplier</th>
                                    <th>Item Alat yang Dibeli</th>
                                    <th>Admin</th>
                                    <th class="text-end">Total Pembelian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rincianPembelianBulanIni as $beli)
                                    <tr>
                                        <td><code>{{ $beli->no_pembelian }}</code></td>
                                        <td><small class="text-muted">{{ \Carbon\Carbon::parse($beli->tanggal_pembelian)->format('d M Y') }}</small></td>
                                        <td><strong>{{ $beli->supplier?->nama_supplier ?? 'Supplier' }}</strong></td>
                                        <td>
                                            @foreach($beli->detailPembelian as $d)
                                                <div class="small">
                                                    &bull; {{ $d->alat->nama_alat ?? $d->kode_alat }} ({{ $d->kuantitas }} unit @ Rp {{ number_format($d->harga_beli_satuan, 0, ',', '.') }})
                                                </div>
                                            @endforeach
                                        </td>
                                        <td><small class="text-muted">{{ $beli->admin?->nama ?? 'Admin' }}</small></td>
                                        <td class="text-end fw-bold text-primary font-monospace">
                                            Rp {{ number_format($beli->total_pembelian, 0, ',', '.') }}
                                        </td>
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

<!-- MODAL 7: RINCIAN BIAYA PERBAIKAN BULAN INI -->
<div class="modal fade" id="modalRincianPerbaikanBulanIni" tabindex="-1" aria-labelledby="modalRincianPerbaikanBulanIniLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRincianPerbaikanBulanIniLabel">🔧 Rincian Biaya Servis / Perbaikan Bulan Ini ({{ $currentMonthFormatted }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom d-flex flex-wrap gap-4 justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Catatan Servis Berbayar</small>
                        <strong class="text-dark">{{ $perbaikanBulanIniCount }} Catatan</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Biaya Perbaikan MTD</small>
                        <strong class="text-warning font-monospace">Rp {{ number_format($totalBiayaPerbaikanBulanIni, 0, ',', '.') }}</strong>
                    </div>
                </div>

                @if($rincianPerbaikanBulanIni->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <div class="mb-2" style="font-size: 2rem;">🛠️</div>
                        <p class="mb-0">Tidak ada pengeluaran biaya perbaikan atau servis alat yang dicatat pada bulan ini.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Alat</th>
                                    <th>Tindakan</th>
                                    <th>Teknisi / Pelaksana</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">Biaya Servis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rincianPerbaikanBulanIni as $perbaikan)
                                    <tr>
                                        <td><small class="text-muted">{{ \Carbon\Carbon::parse($perbaikan->tanggal_tindakan)->format('d M Y') }}</small></td>
                                        <td>
                                            <code>{{ $perbaikan->kode_alat }}</code> &mdash; {{ $perbaikan->alat?->nama_alat }} ({{ $perbaikan->jumlah_unit }} unit)
                                        </td>
                                        <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $perbaikan->jenis_tindakan }}</span></td>
                                        <td>{{ $perbaikan->pelaksana_teknisi ?: '-' }}</td>
                                        <td><small class="text-muted">{{ $perbaikan->keterangan ?: '-' }}</small></td>
                                        <td class="text-end fw-bold text-warning font-monospace">Rp {{ number_format($perbaikan->biaya_perbaikan, 0, ',', '.') }}</td>
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

<!-- MODAL 8: KALKULASI ARUS KAS BERSIH BULAN INI -->
<div class="modal fade" id="modalRincianArusKasBulanIni" tabindex="-1" aria-labelledby="modalRincianArusKasBulanIniLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6" id="modalRincianArusKasBulanIniLabel">⚖️ Rekonsiliasi Arus Kas Bersih Bulan Ini ({{ $currentMonthFormatted }})</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <div class="list-group list-group-flush mb-3">
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                        <div>
                            <span class="fw-bold text-success">➕ Penerimaan Kas Pelanggan</span>
                            <small class="d-block text-muted">{{ $penjualanBulanIniCount }} transaksi pembayaran lunas</small>
                        </div>
                        <span class="font-monospace fw-bold text-success fs-6">Rp {{ number_format($pendapatanBulanIni, 0, ',', '.') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                        <div>
                            <span class="fw-bold text-primary">➖ Pengeluaran Pembelian Stok Supplier</span>
                            <small class="d-block text-muted">{{ $pembelianBulanIniCount }} transaksi PO pengadaan inventaris</small>
                        </div>
                        <span class="font-monospace fw-bold text-primary fs-6">&minus; Rp {{ number_format($totalPembelianBulanIni, 0, ',', '.') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                        <div>
                            <span class="fw-bold text-warning">➖ Pengeluaran Biaya Perbaikan Alat</span>
                            <small class="d-block text-muted">{{ $perbaikanBulanIniCount }} catatan biaya servis unit rusak</small>
                        </div>
                        <span class="font-monospace fw-bold text-warning fs-6">&minus; Rp {{ number_format($totalBiayaPerbaikanBulanIni, 0, ',', '.') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-0 border-top border-2">
                        <div>
                            <strong class="fs-6 text-dark">Arus Kas Bersih Bulan Berjalan</strong>
                            <small class="d-block text-muted">Kas Masuk Nyata &minus; Total Pengeluaran Sah</small>
                        </div>
                        <strong class="font-monospace fs-5 {{ $arusKasBersihBulanIni >= 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format($arusKasBersihBulanIni, 0, ',', '.') }}
                        </strong>
                    </div>
                </div>
                <div class="alert {{ $arusKasBersihBulanIni >= 0 ? 'alert-success' : 'alert-danger' }} py-2 px-3 mb-0 small">
                    <strong>Status: {{ $arusKasBersihBulanIni >= 0 ? 'Surplus Operasional' : 'Defisit Operasional' }}</strong> &mdash; 
                    {{ $arusKasBersihBulanIni >= 0 ? 'Arus kas bisnis bulan berjalan dalam posisi surplus yang sehat.' : 'Total pengeluaran bulan berjalan melampaui penerimaan kas.' }}
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- 2 Visual Sales Charts -->
<div class="row g-4 mb-4">
    <!-- Grafik Penjualan Harian / Sesuai Periode Pilihan -->
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100 shadow-sm border-0" style="border-radius:12px;">
            <div class="ac-card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3 px-4 flex-wrap gap-2">
                <div>
                    <h3 class="ac-card-title fs-6 fw-bold mb-0 text-dark">{{ $chartTitle }}</h3>
                    <small class="text-muted">{{ $chartSubtitle }}</small>
                </div>
                <!-- Filter Periode Grafik -->
                <form action="{{ route('pemilik.dashboard') }}" method="GET" class="d-flex align-items-center flex-wrap gap-1" id="formFilterChart">
                    <select name="periode" id="selectPeriode" class="form-select form-select-sm" style="width: auto;" onchange="handlePeriodeChange(this.value)">
                        <option value="7_hari" {{ ($selectedPeriode ?? '7_hari') === '7_hari' ? 'selected' : '' }}>7 Hari</option>
                        <option value="30_hari" {{ ($selectedPeriode ?? '') === '30_hari' ? 'selected' : '' }}>30 Hari</option>
                        <option value="bulan_ini" {{ ($selectedPeriode ?? '') === 'bulan_ini' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="kustom" {{ ($selectedPeriode ?? '') === 'kustom' ? 'selected' : '' }}>Kustom</option>
                    </select>

                    <div id="customDateInputs" class="d-flex align-items-center gap-1 {{ ($selectedPeriode ?? '') === 'kustom' ? '' : 'd-none' }}">
                        <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $tanggalMulaiInput ?? '' }}" style="width: 120px;">
                        <input type="date" name="tanggal_selesai" class="form-control form-control-sm" value="{{ $tanggalSelesaiInput ?? '' }}" style="width: 120px;">
                        <button type="submit" class="btn btn-sm btn-primary py-1 px-2" style="font-size: 0.75rem;">Pilih</button>
                    </div>

                    @if(($selectedPeriode ?? '7_hari') !== '7_hari')
                        <a href="{{ route('pemilik.dashboard') }}" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 0.75rem;" title="Reset ke Default">&times;</a>
                    @endif
                </form>
            </div>
            <div class="ac-card-body p-4">
                @if(! $hasDailyChartData)
                    <div class="p-4 text-center text-muted my-3" id="emptyDailyChart">
                        <div class="mb-2" style="font-size: 2.2rem;">📊</div>
                        <h6 class="fw-bold text-secondary">Belum Ada Transaksi Penjualan {{ ($selectedPeriode ?? '7_hari') === '7_hari' ? '7 Hari Terakhir' : 'pada Periode Ini' }}</h6>
                        <p class="small text-muted mb-0">Grafik akan terisi secara otomatis begitu pembayaran sewa dikonfirmasi lunas.</p>
                    </div>
                @else
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="chartPenjualanHarian"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Grafik Tren Penjualan Bulanan -->
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100 shadow-sm border-0" style="border-radius:12px;">
            <div class="ac-card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3 px-4">
                <div>
                    <h3 class="ac-card-title fs-6 fw-bold mb-0 text-dark">📈 Grafik Tren Penjualan Bulanan (6 Bulan Terakhir)</h3>
                    <small class="text-muted">Tren akumulasi penerimaan dana dan volume sewa (WIB)</small>
                </div>
                <span class="badge bg-light text-dark border">Bulanan</span>
            </div>
            <div class="ac-card-body p-4">
                @if(! $hasMonthlyChartData)
                    <div class="p-4 text-center text-muted my-3" id="emptyMonthlyChart">
                        <div class="mb-2" style="font-size: 2.2rem;">📈</div>
                        <h6 class="fw-bold text-secondary">Belum Ada Riwayat Penjualan 6 Bulan Terakhir</h6>
                        <p class="small text-muted mb-0">Tren penjualan bulanan akan muncul saat ada riwayat transaksi lunas tercatat.</p>
                    </div>
                @else
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="chartPenjualanBulanan"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Pesanan Pelanggan (Read-Only) -->
<x-data-table title="Riwayat Pesanan Pelanggan (Pengawasan Pemilik)" subtitle="Pantau aktivitas penyewaan dan pembayaran pelanggan secara transparan (Sumber Online & On-site)" searchId="searchOwnerOrders">
    <x-slot:actions>
        <span class="badge bg-secondary" style="font-size:0.75rem;">Mode Read-Only</span>
    </x-slot:actions>

    <thead>
        <tr>
            <th>No. Transaksi</th>
            <th>Sumber</th>
            <th>Nama Pelanggan</th>
            <th>Tanggal Sewa</th>
            <th>Durasi</th>
            <th>Total Tagihan</th>
            <th>Metode Pembayaran</th>
            <th>Status Pembayaran</th>
            <th>Status Pesanan</th>
            <th class="text-center">Rincian</th>
        </tr>
    </thead>
    <tbody>
        @forelse($pesananList as $trx)
            <tr>
                <td><strong>{{ $trx->no_transaksi }}</strong></td>
                <td>
                    @if($trx->isOnsite())
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle d-inline-flex align-items-center gap-1" style="font-size:0.75rem;">
                            <span>📱</span> On-site
                        </span>
                    @else
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1" style="font-size:0.75rem;">
                            <span>🌐</span> Online
                        </span>
                    @endif
                </td>
                <td>{{ $trx->pelanggan?->nama_pelanggan ?? 'Pelanggan' }}</td>
                <td>{{ $trx->tanggal_sewa ? \Carbon\Carbon::parse($trx->tanggal_sewa)->format('d M Y') : '-' }}</td>
                <td>{{ $trx->durasi_hari }} hari</td>
                <td><strong>Rp {{ number_format((float) $trx->total_biaya, 0, ',', '.') }}</strong></td>
                <td>
                    <span class="badge bg-light text-dark border">{{ $trx->pembayaran?->metode_pembayaran ?? '-' }}</span>
                </td>
                <td>
                    <x-status-badge :status="$trx->status_pembayaran" />
                </td>
                <td>
                    <x-status-badge :status="$trx->status_penyewaan" />
                </td>
                <td class="text-center">
                    <a href="{{ route('pemilik.transaksi.show', $trx->no_transaksi) }}" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;" title="Lihat Detail Transaksi">
                        🔍 Rincian
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center py-4 text-muted">
                    Belum ada riwayat transaksi penyewaan pelanggan yang tercatat di sistem.
                </td>
            </tr>
        @endforelse
    </tbody>
</x-data-table>

<!-- Ringkasan & Rincian Inventaris Stok (Read-Only & Dapat Diklik) -->
@if(isset($ringkasanInventaris))
    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
        <h5 class="fw-bold text-dark mb-0">📦 Ringkasan Status & Inventaris Stok</h5>
        <a href="{{ route('pemilik.inventaris.index') }}" class="btn btn-sm btn-outline-primary" style="font-size:0.8rem;">
            📋 Buka Laporan Inventaris Lengkap ↗
        </a>
    </div>

    <div class="row g-3 mb-4">
        <!-- Kartu 1: Total Stok Fisik -->
        <div class="col-6 col-md-3">
            <div class="p-3 bg-white border rounded shadow-sm h-100 position-relative ac-stock-card" 
                 role="button" tabindex="0" 
                 data-bs-toggle="modal" data-bs-target="#modalRincianTotalFisik"
                 style="cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                 title="Klik untuk membuka rincian total stok fisik">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted fw-bold">📦 Total Stok Fisik</small>
                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">Rincian</span>
                </div>
                <strong class="fs-5 text-dark font-monospace d-block">{{ $ringkasanInventaris['total_stok_fisik'] }} Unit</strong>
                <div class="text-muted small mt-2 d-flex align-items-center justify-content-between" style="font-size:0.75rem;">
                    <span>Seluruh aset toko & sewa</span>
                    <span class="text-primary fw-bold">🔍 Buka &rarr;</span>
                </div>
            </div>
        </div>

        <!-- Kartu 2: Stok Siap Sewa -->
        <div class="col-6 col-md-3">
            <div class="p-3 bg-white border rounded shadow-sm h-100 position-relative ac-stock-card border-success-subtle" 
                 role="button" tabindex="0" 
                 data-bs-toggle="modal" data-bs-target="#modalRincianStokTersedia"
                 style="cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                 title="Klik untuk membuka rincian stok siap sewa">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-success fw-bold">✅ Stok Siap Sewa</small>
                    <span class="badge bg-success-subtle text-success" style="font-size: 0.65rem;">Tersedia</span>
                </div>
                <strong class="fs-5 text-success font-monospace d-block">{{ $ringkasanInventaris['stok_siap_sewa'] }} Unit</strong>
                <div class="text-muted small mt-2 d-flex align-items-center justify-content-between" style="font-size:0.75rem;">
                    <span>Kondisi baik di toko</span>
                    <span class="text-success fw-bold">🔍 Buka &rarr;</span>
                </div>
            </div>
        </div>

        <!-- Kartu 3: Sedang Disewa -->
        <div class="col-6 col-md-2">
            <div class="p-3 bg-white border rounded shadow-sm h-100 position-relative ac-stock-card border-warning-subtle" 
                 role="button" tabindex="0" 
                 data-bs-toggle="modal" data-bs-target="#modalRincianSedangDisewa"
                 style="cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                 title="Klik untuk membuka rincian alat yang sedang disewa">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-warning-emphasis fw-bold">🏕️ Sedang Disewa</small>
                    <span class="badge bg-warning-subtle text-warning-emphasis" style="font-size: 0.65rem;">Aktif</span>
                </div>
                <strong class="fs-5 text-warning font-monospace d-block">{{ $ringkasanInventaris['stok_sedang_disewa'] }} Unit</strong>
                <div class="text-muted small mt-2 d-flex align-items-center justify-content-between" style="font-size:0.75rem;">
                    <span>Unit di luar toko</span>
                    <span class="text-warning-emphasis fw-bold">🔍 Buka &rarr;</span>
                </div>
            </div>
        </div>

        <!-- Kartu 4: Perlu Perbaikan -->
        <div class="col-6 col-md-2">
            <div class="p-3 bg-white border rounded shadow-sm h-100 position-relative ac-stock-card border-warning-subtle" 
                 role="button" tabindex="0" 
                 data-bs-toggle="modal" data-bs-target="#modalRincianPerluPerbaikan"
                 style="cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                 title="Klik untuk membuka rincian alat yang perlu perbaikan">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-warning-emphasis fw-bold">🔧 Perlu Perbaikan</small>
                    <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Servis</span>
                </div>
                <strong class="fs-5 text-warning font-monospace d-block">{{ $ringkasanInventaris['stok_perlu_perbaikan'] }} Unit</strong>
                <div class="text-muted small mt-2 d-flex align-items-center justify-content-between" style="font-size:0.75rem;">
                    <span>Menunggu perbaikan</span>
                    <span class="text-warning-emphasis fw-bold">🔍 Buka &rarr;</span>
                </div>
            </div>
        </div>

        <!-- Kartu 5: Rusak / Afkir -->
        <div class="col-6 col-md-2">
            <div class="p-3 bg-white border rounded shadow-sm h-100 position-relative ac-stock-card border-danger-subtle" 
                 role="button" tabindex="0" 
                 data-bs-toggle="modal" data-bs-target="#modalRincianRusak"
                 style="cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                 title="Klik untuk membuka rincian alat rusak / afkir">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-danger fw-bold">⚠️ Rusak / Afkir</small>
                    <span class="badge bg-danger-subtle text-danger" style="font-size: 0.65rem;">Afkir</span>
                </div>
                <strong class="fs-5 text-danger font-monospace d-block">{{ $ringkasanInventaris['stok_rusak'] }} Unit</strong>
                <div class="text-muted small mt-2 d-flex align-items-center justify-content-between" style="font-size:0.75rem;">
                    <span>Tidak layak sewa</span>
                    <span class="text-danger fw-bold">🔍 Buka &rarr;</span>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <h3 class="ac-card-title text-danger fs-6 fw-bold mb-0">⚠️ Laporan Alat Rusak & Afkir</h3>
                <span class="badge bg-danger">Read-Only</span>
            </div>
            <div class="ac-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Kondisi</th>
                                <th class="text-end">Jumlah Rusak</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alatRusakList as $alat)
                                <tr>
                                    <td><code>{{ $alat->kode_alat }}</code></td>
                                    <td>{{ $alat->nama_alat }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $alat->kategori }}</span></td>
                                    <td><x-status-badge status="Rusak" /></td>
                                    <td class="text-end fw-bold text-danger font-monospace">{{ $alat->total_unit_rusak ?? $alat->stok }} unit</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">
                                        Tidak ada alat dengan kondisi rusak di inventaris.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="ac-card h-100">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <h3 class="ac-card-title text-warning fs-6 fw-bold mb-0">🔧 Alat Perlu Perbaikan</h3>
                <span class="badge bg-warning text-dark">Read-Only</span>
            </div>
            <div class="ac-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Kondisi</th>
                                <th class="text-end">Jumlah Perbaikan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alatPerluPerbaikanList as $alat)
                                <tr>
                                    <td><code>{{ $alat->kode_alat }}</code></td>
                                    <td>{{ $alat->nama_alat }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $alat->kategori }}</span></td>
                                    <td><x-status-badge status="Perlu Perbaikan" /></td>
                                    <td class="text-end fw-bold text-warning font-monospace">{{ $alat->total_unit_perbaikan ?? $alat->stok }} unit</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">
                                        Tidak ada alat yang memerlukan perbaikan saat ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: RINCIAN TOTAL STOK FISIK -->
<div class="modal fade" id="modalRincianTotalFisik" tabindex="-1" aria-labelledby="modalRincianTotalFisikLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-1" id="modalRincianTotalFisikLabel">📦 Rincian Total Stok Fisik</h5>
                    <p class="text-muted small mb-0">Seluruh aset inventaris alat camping toko dan unit yang sedang tersebar.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary font-monospace">{{ $ringkasanInventaris['total_stok_fisik'] ?? 0 }} Unit Aset</span>
                    <span class="badge bg-light text-dark border">Read-Only</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <input type="text" class="form-control form-control-sm" placeholder="🔍 Cari kode, nama alat, atau kategori..." onkeyup="filterModalTable(this, 'tableModalTotalFisik')">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0" id="tableModalTotalFisik">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Kondisi</th>
                                <th class="text-end">Stok Toko</th>
                                <th class="text-end text-success">Siap Sewa</th>
                                <th class="text-end text-warning-emphasis">Sedang Sewa</th>
                                <th class="text-end text-info-emphasis">Terpesan di Toko</th>
                                <th class="text-end text-warning">Perbaikan</th>
                                <th class="text-end text-danger">Rusak</th>
                                <th class="text-end fw-bold">Total Fisik</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($daftarInventarisLengkap as $item)
                                <tr>
                                    <td><code>{{ $item->kode_alat }}</code></td>
                                    <td class="fw-semibold">{{ $item->nama_alat }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $item->kategori }}</span></td>
                                    <td><x-status-badge :status="$item->kondisi" /></td>
                                    <td class="text-end font-monospace">{{ $item->stok_toko }}</td>
                                    <td class="text-end font-monospace text-success fw-bold">{{ $item->stok_siap_sewa }}</td>
                                    <td class="text-end font-monospace text-warning-emphasis fw-bold">{{ $item->stok_sedang_disewa }}</td>
                                    <td class="text-end font-monospace text-info-emphasis">{{ $item->stok_dialokasikan ?? 0 }}</td>
                                    <td class="text-end font-monospace text-warning">{{ $item->stok_perlu_perbaikan }}</td>
                                    <td class="text-end font-monospace text-danger">{{ $item->stok_rusak }}</td>
                                    <td class="text-end font-monospace fw-bold">{{ $item->total_stok_fisik }} unit</td>
                                </tr>
                            @empty
                                <tr class="empty-row">
                                    <td colspan="11" class="text-center py-4 text-muted">
                                        Tidak ada data inventaris alat yang tercatat.
                                    </td>
                                </tr>
                            @endforelse
                            <tr class="no-results-row text-center text-muted py-3" style="display:none;">
                                <td colspan="11">Tidak ditemukan alat yang cocok dengan pencarian.</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">Total Seluruh Inventaris:</td>
                                <td class="text-end font-monospace">{{ $daftarInventarisLengkap->sum('stok_toko') }}</td>
                                <td class="text-end font-monospace text-success">{{ $ringkasanInventaris['stok_siap_sewa'] ?? 0 }}</td>
                                <td class="text-end font-monospace text-warning-emphasis">{{ $ringkasanInventaris['stok_sedang_disewa'] ?? 0 }}</td>
                                <td class="text-end font-monospace text-info-emphasis">{{ $ringkasanInventaris['stok_dialokasikan'] ?? 0 }}</td>
                                <td class="text-end font-monospace text-warning">{{ $ringkasanInventaris['stok_perlu_perbaikan'] ?? 0 }}</td>
                                <td class="text-end font-monospace text-danger">{{ $ringkasanInventaris['stok_rusak'] ?? 0 }}</td>
                                <td class="text-end font-monospace text-primary">{{ $ringkasanInventaris['total_stok_fisik'] ?? 0 }} unit</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <a href="{{ route('pemilik.inventaris.index') }}" class="btn btn-sm btn-outline-primary">
                    📋 Buka Halaman Laporan Inventaris Lengkap &rarr;
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2: RINCIAN STOK TERSEDIA (SIAP SEWA) -->
<div class="modal fade" id="modalRincianStokTersedia" tabindex="-1" aria-labelledby="modalRincianStokTersediaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-success mb-1" id="modalRincianStokTersediaLabel">✅ Rincian Stok Siap Sewa</h5>
                    <p class="text-muted small mb-0">Alat dalam kondisi baik yang saat ini berada di toko dan dapat disewakan.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success font-monospace">{{ $ringkasanInventaris['stok_siap_sewa'] ?? 0 }} Unit Tersedia</span>
                    <span class="badge bg-light text-dark border">Read-Only</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <input type="text" class="form-control form-control-sm" placeholder="🔍 Cari kode, nama alat, atau kategori..." onkeyup="filterModalTable(this, 'tableModalStokTersedia')">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0" id="tableModalStokTersedia">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Kondisi</th>
                                <th class="text-end">Tarif Sewa (/hari)</th>
                                <th class="text-end">Stok Toko</th>
                                <th class="text-end text-success fw-bold">Siap Sewa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stokSiapSewaList as $item)
                                <tr>
                                    <td><code>{{ $item->kode_alat }}</code></td>
                                    <td class="fw-semibold">{{ $item->nama_alat }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $item->kategori }}</span></td>
                                    <td><x-status-badge :status="$item->kondisi" /></td>
                                    <td class="text-end font-monospace">{{ number_format($item->harga_sewa_per_hari, 0, ',', '.') }}/hari</td>
                                    <td class="text-end font-monospace">{{ $item->stok_toko }}</td>
                                    <td class="text-end font-monospace text-success fw-bold">{{ $item->stok_siap_sewa }} unit</td>
                                </tr>
                            @empty
                                <tr class="empty-row">
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Tidak ada alat yang memiliki stok siap sewa saat ini.
                                    </td>
                                </tr>
                            @endforelse
                            <tr class="no-results-row text-center text-muted py-3" style="display:none;">
                                <td colspan="7">Tidak ditemukan alat yang cocok dengan pencarian.</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="6" class="text-end">Total Unit Siap Sewa:</td>
                                <td class="text-end font-monospace text-success">{{ $ringkasanInventaris['stok_siap_sewa'] ?? 0 }} unit</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <a href="{{ route('pemilik.inventaris.index', ['status' => 'siap_sewa']) }}" class="btn btn-sm btn-outline-success">
                    📋 Buka Laporan Stok Tersedia &rarr;
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 3: RINCIAN ALAT SEDANG DISEWA -->
<div class="modal fade" id="modalRincianSedangDisewa" tabindex="-1" aria-labelledby="modalRincianSedangDisewaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-warning-emphasis mb-1" id="modalRincianSedangDisewaLabel">🏕️ Rincian Alat Sedang Disewa</h5>
                    <p class="text-muted small mb-0">Rincian unit alat yang saat ini berada dalam sewa aktif di tangan pelanggan.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning text-dark font-monospace">{{ $ringkasanInventaris['stok_sedang_disewa'] ?? 0 }} Unit di Lapangan</span>
                    <span class="badge bg-light text-dark border">Read-Only</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-info py-2 px-3 small mb-3 d-flex align-items-center gap-2">
                    <span>ℹ️</span>
                    <div>
                        <strong>Aturan Perhitungan:</strong> Menampilkan unit alat yang saat ini berada di tangan penyewa di luar toko (status <strong>Sedang Disewa</strong> dan <strong>Berjalan</strong>). Pesanan yang siap diambil di toko ditampilkan pada rincian alokasi terpesan.
                    </div>
                </div>

                <div class="mb-3">
                    <input type="text" class="form-control form-control-sm" placeholder="🔍 Cari transaksi, nama alat, atau pelanggan..." onkeyup="filterModalTable(this, 'tableModalSedangDisewa')">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0" id="tableModalSedangDisewa">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Alat & Kategori</th>
                                <th>No. Transaksi</th>
                                <th>Pelanggan</th>
                                <th>Periode Sewa</th>
                                <th>Status Sewa</th>
                                <th class="text-end text-warning-emphasis fw-bold">Unit Disewa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($daftarSedangDisewa as $row)
                                <tr>
                                    <td><code>{{ $row->kode_alat }}</code></td>
                                    <td>
                                        <div class="fw-semibold">{{ $row->nama_alat }}</div>
                                        <small class="badge bg-light text-muted border">{{ $row->kategori }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('pemilik.transaksi.show', $row->nomor_transaksi) }}" class="font-monospace text-primary fw-bold text-decoration-none">
                                            {{ $row->nomor_transaksi }}
                                        </a>
                                    </td>
                                    <td>{{ $row->nama_pelanggan }}</td>
                                    <td class="small">
                                        <div>{{ \Carbon\Carbon::parse($row->tanggal_mulai)->format('d M Y') }}</div>
                                        <div class="text-muted">s/d {{ \Carbon\Carbon::parse($row->tanggal_kembali)->format('d M Y') }}</div>
                                    </td>
                                    <td><x-status-badge :status="$row->status_penyewaan" /></td>
                                    <td class="text-end font-monospace fw-bold text-warning-emphasis">{{ $row->unit_disewa }} unit</td>
                                </tr>
                            @empty
                                <tr class="empty-row">
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Tidak ada alat yang sedang dalam status sewa aktif di lapangan saat ini.
                                    </td>
                                </tr>
                            @endforelse
                            <tr class="no-results-row text-center text-muted py-3" style="display:none;">
                                <td colspan="7">Tidak ditemukan alat yang cocok dengan pencarian.</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="6" class="text-end">Total Seluruh Unit Sedang Disewa di Lapangan:</td>
                                <td class="text-end font-monospace text-warning-emphasis">{{ $ringkasanInventaris['stok_sedang_disewa'] ?? 0 }} unit</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if(isset($daftarDialokasikan) && $daftarDialokasikan->isNotEmpty())
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-dark mb-0">📦 Rincian Unit Terpesan / Menunggu Diambil di Toko</h6>
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace">{{ $ringkasanInventaris['stok_dialokasikan'] ?? 0 }} Unit Terpesan</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Alat</th>
                                        <th>No. Transaksi</th>
                                        <th>Pelanggan</th>
                                        <th>Periode Sewa</th>
                                        <th>Status Pesanan</th>
                                        <th class="text-end text-info-emphasis fw-bold">Unit Dipesan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($daftarDialokasikan as $rowDialokasi)
                                        <tr>
                                            <td><code>{{ $rowDialokasi->kode_alat }}</code></td>
                                            <td>
                                                <div class="fw-semibold">{{ $rowDialokasi->nama_alat }}</div>
                                                <small class="badge bg-light text-muted border">{{ $rowDialokasi->kategori }}</small>
                                            </td>
                                            <td>
                                                <a href="{{ route('pemilik.transaksi.show', $rowDialokasi->nomor_transaksi) }}" class="font-monospace text-primary fw-bold text-decoration-none">
                                                    {{ $rowDialokasi->nomor_transaksi }}
                                                </a>
                                            </td>
                                            <td>{{ $rowDialokasi->nama_pelanggan }}</td>
                                            <td class="small">
                                                <div>{{ \Carbon\Carbon::parse($rowDialokasi->tanggal_mulai)->format('d M Y') }}</div>
                                                <div class="text-muted">s/d {{ \Carbon\Carbon::parse($rowDialokasi->tanggal_kembali)->format('d M Y') }}</div>
                                            </td>
                                            <td><x-status-badge :status="$rowDialokasi->status_penyewaan" /></td>
                                            <td class="text-end font-monospace fw-bold text-info-emphasis">{{ $rowDialokasi->unit_disewa }} unit</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <a href="{{ route('pemilik.inventaris.index', ['status' => 'sedang_disewa']) }}" class="btn btn-sm btn-outline-warning text-dark">
                    📋 Buka Tabel Sedang Disewa Lengkap &rarr;
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 4: RINCIAN ALAT PERLU PERBAIKAN -->
<div class="modal fade" id="modalRincianPerluPerbaikan" tabindex="-1" aria-labelledby="modalRincianPerluPerbaikanLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-warning-emphasis mb-1" id="modalRincianPerluPerbaikanLabel">🔧 Rincian Alat Perlu Perbaikan</h5>
                    <p class="text-muted small mb-0">Daftar alat dan jumlah unit yang tercatat perlu tindakan perbaikan/servis.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning text-dark font-monospace">{{ $ringkasanInventaris['stok_perlu_perbaikan'] ?? 0 }} Unit Servis</span>
                    <span class="badge bg-light text-dark border">Read-Only</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <input type="text" class="form-control form-control-sm" placeholder="🔍 Cari kode, nama alat, atau kategori..." onkeyup="filterModalTable(this, 'tableModalPerluPerbaikan')">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0" id="tableModalPerluPerbaikan">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Kondisi Master</th>
                                <th class="text-end text-warning-emphasis fw-bold">Unit Perlu Perbaikan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alatPerluPerbaikanList as $alat)
                                <tr>
                                    <td><code>{{ $alat->kode_alat }}</code></td>
                                    <td class="fw-semibold">{{ $alat->nama_alat }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $alat->kategori }}</span></td>
                                    <td><x-status-badge :status="$alat->kondisi" /></td>
                                    <td class="text-end font-monospace fw-bold text-warning-emphasis">{{ $alat->total_unit_perbaikan ?? $alat->stok }} unit</td>
                                </tr>
                            @empty
                                <tr class="empty-row">
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Tidak ada alat yang memerlukan perbaikan saat ini.
                                    </td>
                                </tr>
                            @endforelse
                            <tr class="no-results-row text-center text-muted py-3" style="display:none;">
                                <td colspan="5">Tidak ditemukan alat yang cocok dengan pencarian.</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">Total Unit Perlu Perbaikan:</td>
                                <td class="text-end font-monospace text-warning-emphasis">{{ $ringkasanInventaris['stok_perlu_perbaikan'] ?? 0 }} unit</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <a href="{{ route('pemilik.inventaris.index', ['status' => 'perlu_perbaikan']) }}" class="btn btn-sm btn-outline-warning text-dark">
                    📋 Buka Laporan Inventaris &rarr;
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 5: RINCIAN ALAT RUSAK / AFKIR -->
<div class="modal fade" id="modalRincianRusak" tabindex="-1" aria-labelledby="modalRincianRusakLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-danger mb-1" id="modalRincianRusakLabel">⚠️ Rincian Alat Rusak / Afkir</h5>
                    <p class="text-muted small mb-0">Daftar alat dan jumlah unit yang rusak dan tidak layak disewakan.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger font-monospace">{{ $ringkasanInventaris['stok_rusak'] ?? 0 }} Unit Rusak</span>
                    <span class="badge bg-light text-dark border">Read-Only</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <input type="text" class="form-control form-control-sm" placeholder="🔍 Cari kode, nama alat, atau kategori..." onkeyup="filterModalTable(this, 'tableModalRusak')">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0" id="tableModalRusak">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Kondisi Master</th>
                                <th class="text-end text-danger fw-bold">Unit Rusak / Afkir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alatRusakList as $alat)
                                <tr>
                                    <td><code>{{ $alat->kode_alat }}</code></td>
                                    <td class="fw-semibold">{{ $alat->nama_alat }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $alat->kategori }}</span></td>
                                    <td><x-status-badge :status="$alat->kondisi" /></td>
                                    <td class="text-end font-monospace fw-bold text-danger">{{ $alat->total_unit_rusak ?? $alat->stok }} unit</td>
                                </tr>
                            @empty
                                <tr class="empty-row">
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Tidak ada alat dengan kondisi rusak saat ini.
                                    </td>
                                </tr>
                            @endforelse
                            <tr class="no-results-row text-center text-muted py-3" style="display:none;">
                                <td colspan="5">Tidak ditemukan alat yang cocok dengan pencarian.</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">Total Unit Rusak / Afkir:</td>
                                <td class="text-end font-monospace text-danger">{{ $ringkasanInventaris['stok_rusak'] ?? 0 }} unit</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <a href="{{ route('pemilik.inventaris.index', ['status' => 'rusak']) }}" class="btn btn-sm btn-outline-danger">
                    📋 Buka Laporan Inventaris &rarr;
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
function handlePeriodeChange(val) {
    const customDiv = document.getElementById('customDateInputs');
    if (val === 'kustom') {
        if (customDiv) customDiv.classList.remove('d-none');
    } else {
        if (customDiv) customDiv.classList.add('d-none');
        document.getElementById('formFilterChart').submit();
    }
}

function filterModalTable(input, tableId) {
    const filter = input.value.toLowerCase().trim();
    const table = document.getElementById(tableId);
    if (!table) return;
    const rows = table.querySelectorAll('tbody tr:not(.empty-row):not(.no-results-row)');
    let visibleCount = 0;
    rows.forEach(tr => {
        const text = tr.innerText.toLowerCase();
        if (text.includes(filter)) {
            tr.style.display = '';
            visibleCount++;
        } else {
            tr.style.display = 'none';
        }
    });
    const noResultsRow = table.querySelector('.no-results-row');
    if (noResultsRow) {
        noResultsRow.style.display = (visibleCount === 0 && filter !== '') ? '' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Auto-buka modal jika ada hash atau query parameter modal
    const params = new URLSearchParams(window.location.search);
    const modalParam = params.get('modal') || window.location.hash.replace('#', '');
    const modalMap = {
        'total_fisik': 'modalRincianTotalFisik',
        'modalRincianTotalFisik': 'modalRincianTotalFisik',
        'stok_tersedia': 'modalRincianStokTersedia',
        'siap_sewa': 'modalRincianStokTersedia',
        'modalRincianStokTersedia': 'modalRincianStokTersedia',
        'sedang_disewa': 'modalRincianSedangDisewa',
        'modalRincianSedangDisewa': 'modalRincianSedangDisewa',
        'perlu_perbaikan': 'modalRincianPerluPerbaikan',
        'modalRincianPerluPerbaikan': 'modalRincianPerluPerbaikan',
        'rusak': 'modalRincianRusak',
        'modalRincianRusak': 'modalRincianRusak',
        'pembelian_hari_ini': 'modalRincianPembelianHariIni',
        'modalRincianPembelianHariIni': 'modalRincianPembelianHariIni',
        'pembelian_bulan_ini': 'modalRincianPembelianBulanIni',
        'modalRincianPembelianBulanIni': 'modalRincianPembelianBulanIni',
        'perbaikan_hari_ini': 'modalRincianPerbaikanHariIni',
        'modalRincianPerbaikanHariIni': 'modalRincianPerbaikanHariIni',
        'perbaikan_bulan_ini': 'modalRincianPerbaikanBulanIni',
        'modalRincianPerbaikanBulanIni': 'modalRincianPerbaikanBulanIni',
        'arus_kas_hari_ini': 'modalRincianArusKasHariIni',
        'modalRincianArusKasHariIni': 'modalRincianArusKasHariIni',
        'arus_kas_bulan_ini': 'modalRincianArusKasBulanIni',
        'modalRincianArusKasBulanIni': 'modalRincianArusKasBulanIni',
        'penerimaan_hari_ini': 'modalRincianHariIni',
        'modalRincianHariIni': 'modalRincianHariIni',
        'penerimaan_bulan_ini': 'modalRincianBulanIni',
        'modalRincianBulanIni': 'modalRincianBulanIni'
    };

    if (modalParam && modalMap[modalParam]) {
        const targetEl = document.getElementById(modalMap[modalParam]);
        if (targetEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalInstance = new bootstrap.Modal(targetEl);
            modalInstance.show();
        }
    }
    @if($hasDailyChartData)
    const ctxHarian = document.getElementById('chartPenjualanHarian');
    if (ctxHarian && typeof Chart !== 'undefined') {
        const labelsHarian = @json(array_column($grafikHarian, 'label'));
        const dataUangHarian = @json(array_column($grafikHarian, 'uang_diterima'));
        const dataPengeluaranHarian = @json(array_column($grafikHarian, 'total_pengeluaran'));
        const dataArusKasHarian = @json(array_column($grafikHarian, 'arus_kas_bersih'));

        new Chart(ctxHarian, {
            type: 'bar',
            data: {
                labels: labelsHarian,
                datasets: [
                    {
                        label: 'Penerimaan Kas (Rp)',
                        data: dataUangHarian,
                        backgroundColor: 'rgba(40, 167, 69, 0.75)',
                        borderColor: '#28a745',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: 'Pengeluaran Stok & Servis (Rp)',
                        data: dataPengeluaranHarian,
                        backgroundColor: 'rgba(220, 53, 69, 0.75)',
                        borderColor: '#dc3545',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: 'Arus Kas Bersih (Rp)',
                        data: dataArusKasHarian,
                        backgroundColor: 'rgba(13, 110, 253, 0.25)',
                        borderColor: '#0d6efd',
                        borderWidth: 2,
                        borderRadius: 4,
                        type: 'line',
                        tension: 0.2,
                        pointRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let val = context.raw || 0;
                                return label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) {
                                return 'Rp ' + (val >= 1000000 ? (val/1000000) + 'jt' : (val >= 1000 ? (val/1000) + 'rb' : val));
                            },
                            font: { size: 10 }
                        }
                    },
                    x: {
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    }
    @endif

    @if($hasMonthlyChartData)
    const ctxBulanan = document.getElementById('chartPenjualanBulanan');
    if (ctxBulanan && typeof Chart !== 'undefined') {
        const labelsBulanan = @json(array_column($grafikBulanan, 'label'));
        const dataUangBulanan = @json(array_column($grafikBulanan, 'uang_diterima'));
        const dataPengeluaranBulanan = @json(array_column($grafikBulanan, 'total_pengeluaran'));
        const dataArusKasBulanan = @json(array_column($grafikBulanan, 'arus_kas_bersih'));

        new Chart(ctxBulanan, {
            type: 'line',
            data: {
                labels: labelsBulanan,
                datasets: [
                    {
                        label: 'Penerimaan Kas (Rp)',
                        data: dataUangBulanan,
                        backgroundColor: 'rgba(25, 135, 84, 0.15)',
                        borderColor: '#198754',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.3,
                        pointBackgroundColor: '#198754',
                        pointRadius: 4,
                    },
                    {
                        label: 'Pengeluaran (Rp)',
                        data: dataPengeluaranBulanan,
                        backgroundColor: 'rgba(220, 53, 69, 0.15)',
                        borderColor: '#dc3545',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.3,
                        pointBackgroundColor: '#dc3545',
                        pointRadius: 4,
                    },
                    {
                        label: 'Arus Kas Bersih (Rp)',
                        data: dataArusKasBulanan,
                        backgroundColor: 'rgba(13, 110, 253, 0.15)',
                        borderColor: '#0d6efd',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.3,
                        pointBackgroundColor: '#0d6efd',
                        pointRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let val = context.raw || 0;
                                return label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) {
                                return 'Rp ' + (val >= 1000000 ? (val/1000000) + 'jt' : (val >= 1000 ? (val/1000) + 'rb' : val));
                            },
                            font: { size: 10 }
                        }
                    },
                    x: {
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    }
    @endif
});
</script>
@endpush
