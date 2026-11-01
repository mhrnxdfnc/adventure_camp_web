@extends('layouts.app')

@section('title', 'Laporan Transaksi Penyewaan & Pembayaran')
@section('page-title', 'Laporan Transaksi & Pembayaran')

@section('content')
<!-- Header & Action Buttons -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 font-weight-bold mb-1 text-dark">📋 Laporan Transaksi Penyewaan & Rekapitulasi Kas</h2>
        <p class="text-muted small mb-0">Pantau seluruh riwayat transaksi pemesanan, verifikasi pembayaran kas masuk riil, dan piutang tertunda.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ request()->routeIs('pemilik.*') ? route('pemilik.transaksi.cetak', request()->all()) : route('admin.laporan.transaksi.cetak', request()->all()) }}" 
           target="_blank" 
           class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1 shadow-sm" 
           id="btn-cetak-laporan-transaksi">
            <span>🖨️</span>
            <span>Cetak Laporan</span>
        </a>
        <a href="{{ request()->routeIs('pemilik.*') ? route('pemilik.transaksi.export', request()->all()) : route('admin.laporan.transaksi.export', request()->all()) }}" 
           class="btn btn-success btn-sm d-flex align-items-center gap-1 shadow-sm" 
           id="btn-export-laporan-transaksi">
            <span>📥</span>
            <span>Ekspor Excel (CSV)</span>
        </a>
    </div>
</div>

<!-- 4 Kartu Rekapitulasi Keuangan & Transaksi -->
<div class="row g-3 mb-4">
    <!-- Total Transaksi -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card h-100">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Total Transaksi</span>
                <div class="ac-stat-icon-wrap" style="background-color: #e0f2fe; color: #0284c7;">
                    📑
                </div>
            </div>
            <div class="ac-stat-value font-monospace">{{ number_format($rekapJumlahTransaksi, 0, ',', '.') }}</div>
            <div class="ac-stat-sub text-muted">Pesanan terfilter periode</div>
        </div>
    </div>

    <!-- Total Tagihan -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card h-100">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Total Nilai Tagihan</span>
                <div class="ac-stat-icon-wrap" style="background-color: #f1f5f9; color: #475569;">
                    🧾
                </div>
            </div>
            <div class="ac-stat-value font-monospace">Rp {{ number_format($rekapTotalTagihan, 0, ',', '.') }}</div>
            <div class="ac-stat-sub text-muted">Akumulasi sewa & denda</div>
        </div>
    </div>

    <!-- Penerimaan Kas Riil -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card h-100">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Penerimaan Kas Riil</span>
                <div class="ac-stat-icon-wrap" style="background-color: #dcfce7; color: #16a34a;">
                    💰
                </div>
            </div>
            <div class="ac-stat-value text-success font-monospace">Rp {{ number_format($rekapTotalKasDiterima, 0, ',', '.') }}</div>
            <div class="ac-stat-sub text-success">Pembayaran Lunas terverifikasi</div>
        </div>
    </div>

    <!-- Sisa Tagihan (Piutang) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card h-100">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Sisa Tagihan / Piutang</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fee2e2; color: #dc2626;">
                    ⏳
                </div>
            </div>
            <div class="ac-stat-value text-danger font-monospace">Rp {{ number_format($rekapTotalSisaTagihan, 0, ',', '.') }}</div>
            <div class="ac-stat-sub text-danger">Belum dibayar / tertunda</div>
        </div>
    </div>
</div>

<!-- Form Filter & Pencarian Laporan -->
<div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="ac-card-body p-3">
        <form method="GET" action="{{ request()->url() }}" class="row g-2 align-items-end">
            <!-- Hidden sort preservation -->
            <input type="hidden" name="sort_by" value="{{ $filters['sort_by'] ?? 'tanggal_sewa' }}">
            <input type="hidden" name="order" value="{{ $filters['order'] ?? 'desc' }}">

            <div class="col-12 col-md-3">
                <label for="search" class="form-label small fw-bold text-muted mb-1">Cari Transaksi / Pelanggan</label>
                <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="No. Transaksi atau Nama/No. Telp..." value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="tanggal_mulai" class="form-label small fw-bold text-muted mb-1">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control form-control-sm" value="{{ $filters['tanggal_mulai'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="tanggal_selesai" class="form-label small fw-bold text-muted mb-1">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control form-control-sm" value="{{ $filters['tanggal_selesai'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="status_penyewaan" class="form-label small fw-bold text-muted mb-1">Status Sewa</label>
                <select name="status_penyewaan" id="status_penyewaan" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="Menunggu Konfirmasi" {{ ($filters['status_penyewaan'] ?? '') === 'Menunggu Konfirmasi' ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                    <option value="Menunggu Pembayaran" {{ ($filters['status_penyewaan'] ?? '') === 'Menunggu Pembayaran' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                    <option value="Disetujui" {{ ($filters['status_penyewaan'] ?? '') === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="Siap Diambil" {{ ($filters['status_penyewaan'] ?? '') === 'Siap Diambil' ? 'selected' : '' }}>Siap Diambil</option>
                    <option value="Berjalan" {{ in_array(($filters['status_penyewaan'] ?? ''), ['Berjalan', 'Sedang Disewa']) ? 'selected' : '' }}>Sedang Disewa / Berjalan</option>
                    <option value="Selesai" {{ ($filters['status_penyewaan'] ?? '') === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="Ditolak" {{ ($filters['status_penyewaan'] ?? '') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                    <option value="Dibatalkan" {{ ($filters['status_penyewaan'] ?? '') === 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="status_pembayaran" class="form-label small fw-bold text-muted mb-1">Status Pembayaran</label>
                <select name="status_pembayaran" id="status_pembayaran" class="form-select form-select-sm">
                    <option value="">Semua Pembayaran</option>
                    <option value="Lunas" {{ ($filters['status_pembayaran'] ?? '') === 'Lunas' ? 'selected' : '' }}>Lunas</option>
                    <option value="Menunggu Verifikasi" {{ ($filters['status_pembayaran'] ?? '') === 'Menunggu Verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    <option value="Belum Dibayar" {{ ($filters['status_pembayaran'] ?? '') === 'Belum Dibayar' ? 'selected' : '' }}>Belum Dibayar</option>
                    <option value="Ditolak" {{ ($filters['status_pembayaran'] ?? '') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Terapkan Filter">
                    🔍 Filter
                </button>
                @if($hasActiveFilter)
                    <a href="{{ request()->url() }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">✕</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Card Tabel Laporan Transaksi -->
<div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="ac-card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 px-4 gap-2">
        <div>
            <h3 class="ac-card-title fs-5 fw-bold mb-0 text-dark">📋 Riwayat Transaksi Pelanggan</h3>
            <small class="text-muted">Menampilkan {{ $transaksiList->total() }} data transaksi yang tercatat dalam sistem</small>
        </div>
        <div class="small text-muted">
            Urutan: <strong>{{ strtoupper($filters['sort_by'] ?? 'tanggal_sewa') }} ({{ strtoupper($filters['order'] ?? 'desc') }})</strong>
        </div>
    </div>

    @php
        // Helper function for sort URL generator
        $buildSortUrl = function ($field) use ($filters) {
            $currentSort = $filters['sort_by'] ?? 'tanggal_sewa';
            $currentOrder = $filters['order'] ?? 'desc';
            $newOrder = ($currentSort === $field && $currentOrder === 'asc') ? 'desc' : 'asc';
            $params = array_merge($filters, ['sort_by' => $field, 'order' => $newOrder]);
            return request()->url() . '?' . http_build_query($params);
        };

        $sortIcon = function ($field) use ($filters) {
            $currentSort = $filters['sort_by'] ?? 'tanggal_sewa';
            $currentOrder = $filters['order'] ?? 'desc';
            if ($currentSort === $field) {
                return $currentOrder === 'asc' ? '▲' : '▼';
            }
            return '⇅';
        };
    @endphp

    <div class="ac-card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-laporan-transaksi">
                <thead class="table-light small">
                    <tr>
                        <th class="ps-4">
                            <a href="{{ $buildSortUrl('no_transaksi') }}" class="text-dark text-decoration-none d-inline-flex align-items-center gap-1">
                                No. Transaksi <span class="text-muted" style="font-size: 0.7rem;">{{ $sortIcon('no_transaksi') }}</span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ $buildSortUrl('tanggal_sewa') }}" class="text-dark text-decoration-none d-inline-flex align-items-center gap-1">
                                Tgl Sewa <span class="text-muted" style="font-size: 0.7rem;">{{ $sortIcon('tanggal_sewa') }}</span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ $buildSortUrl('tanggal_kembali') }}" class="text-dark text-decoration-none d-inline-flex align-items-center gap-1">
                                Tgl Kembali <span class="text-muted" style="font-size: 0.7rem;">{{ $sortIcon('tanggal_kembali') }}</span>
                            </a>
                        </th>
                        <th>Pelanggan</th>
                        <th>Petugas</th>
                        <th class="text-end">
                            <a href="{{ $buildSortUrl('total_biaya') }}" class="text-dark text-decoration-none d-inline-flex align-items-center gap-1 justify-content-end">
                                Total Tagihan <span class="text-muted" style="font-size: 0.7rem;">{{ $sortIcon('total_biaya') }}</span>
                            </a>
                        </th>
                        <th class="text-end">Jumlah Dibayar</th>
                        <th class="text-end">Sisa Tagihan</th>
                        <th class="text-center">
                            <a href="{{ $buildSortUrl('status_pembayaran') }}" class="text-dark text-decoration-none d-inline-flex align-items-center gap-1">
                                Pembayaran <span class="text-muted" style="font-size: 0.7rem;">{{ $sortIcon('status_pembayaran') }}</span>
                            </a>
                        </th>
                        <th class="text-center">
                            <a href="{{ $buildSortUrl('status_penyewaan') }}" class="text-dark text-decoration-none d-inline-flex align-items-center gap-1">
                                Status Sewa <span class="text-muted" style="font-size: 0.7rem;">{{ $sortIcon('status_penyewaan') }}</span>
                            </a>
                        </th>
                        <th class="text-center pe-4" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksiList as $trx)
                        @php
                            $tagihan = (float) $trx->total_biaya;
                            if ($trx->pengembalian?->denda) {
                                $tagihan += (float) $trx->pengembalian->denda->nominal_denda;
                            }
                            $isLunas = $trx->pembayaran && $trx->pembayaran->isLunas();
                            $dibayar = $isLunas ? (float) ($trx->pembayaran->jumlah_pembayaran > 0 ? $trx->pembayaran->jumlah_pembayaran : $tagihan) : 0.0;
                            $sisa = max(0.0, $tagihan - $dibayar);
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark font-monospace">{{ $trx->no_transaksi }}</span>
                                @if($trx->isOnsite())
                                    <span class="badge bg-warning-subtle text-dark border border-warning d-block mt-1" style="font-size:0.65rem; width:fit-content;">📟 On-Site</span>
                                @else
                                    <span class="badge bg-info-subtle text-info border border-info d-block mt-1" style="font-size:0.65rem; width:fit-content;">🌐 Online</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $trx->tanggal_sewa ? \Carbon\Carbon::parse($trx->tanggal_sewa)->format('d/m/Y') : '-' }}</div>
                                <small class="text-muted">{{ $trx->durasi_hari }} Hari</small>
                            </td>
                            <td>
                                <div>{{ $trx->tanggal_kembali ? \Carbon\Carbon::parse($trx->tanggal_kembali)->format('d/m/Y') : '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $trx->pelanggan?->nama_pelanggan ?? 'Pelanggan Umum' }}</div>
                                <small class="text-muted font-monospace">{{ $trx->pelanggan?->nomor_telepon ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">
                                    {{ $trx->petugas_pemroses }}
                                </span>
                            </td>
                            <td class="text-end font-monospace fw-bold text-dark">
                                Rp {{ number_format($tagihan, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-monospace {{ $dibayar > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                                Rp {{ number_format($dibayar, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-monospace {{ $sisa > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                @if($sisa > 0)
                                    Rp {{ number_format($sisa, 0, ',', '.') }}
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Lunas</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <x-status-badge :status="$trx->status_pembayaran" />
                                <small class="text-muted d-block mt-1" style="font-size:0.7rem;">{{ $trx->pembayaran?->metode_pembayaran ?? '-' }}</small>
                            </td>
                            <td class="text-center">
                                <x-status-badge :status="$trx->status_penyewaan" />
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ request()->routeIs('pemilik.*') ? route('pemilik.transaksi.show', $trx->no_transaksi) : route('admin.pesanan.show', $trx->no_transaksi) }}" 
                                       class="btn btn-sm btn-outline-primary py-1 px-2" 
                                       title="Lihat Detail Transaksi"
                                       style="font-size: 0.75rem;">
                                        🔍 Detail
                                    </a>
                                    <a href="{{ route('transaksi.nota', $trx->no_transaksi) }}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-dark py-1 px-2" 
                                       title="Cetak Nota Transaksi"
                                       style="font-size: 0.75rem;">
                                        🖨️ Nota
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                @if($hasActiveFilter)
                                    <div class="mb-2" style="font-size: 2.5rem;">🔍</div>
                                    <h6 class="fw-bold text-secondary">Tidak Ada Transaksi Sesuai Filter</h6>
                                    <p class="small text-muted mb-3">Tidak ditemukan data transaksi yang cocok dengan kriteria pencarian dan tanggal yang dipilih.</p>
                                    <a href="{{ request()->url() }}" class="btn btn-sm btn-outline-secondary">Reset Filter</a>
                                @else
                                    <div class="mb-2" style="font-size: 2.5rem;">📭</div>
                                    <h6 class="fw-bold text-secondary">Belum Ada Riwayat Transaksi Pelanggan</h6>
                                    <p class="small text-muted mb-0">Seluruh riwayat pemesanan yang tercatat di database akan ditampilkan di sini.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <!-- Baris Rekapitulasi Footer Halaman -->
                @if($transaksiList->isNotEmpty())
                    <tfoot class="table-light border-top">
                        <tr class="fw-bold">
                            <td colspan="5" class="ps-4 text-end">TOTAL REKAPITULASI (SELURUH DATA TERFILTER):</td>
                            <td class="text-end font-monospace text-dark fs-6">
                                Rp {{ number_format($rekapTotalTagihan, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-monospace text-success fs-6">
                                Rp {{ number_format($rekapTotalKasDiterima, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-monospace text-danger fs-6">
                                Rp {{ number_format($rekapTotalSisaTagihan, 0, ',', '.') }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    @if($transaksiList->hasPages())
        <div class="ac-card-footer bg-white border-top py-3 px-4 d-flex justify-content-end">
            {{ $transaksiList->links() }}
        </div>
    @endif
</div>
@endsection

