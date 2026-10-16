@extends('layouts.app')

@section('title', 'Riwayat Transaksi Pelanggan — Pemilik')
@section('page-title', 'Riwayat Transaksi Pelanggan')

@section('content')
<!-- Read-Only Notice -->
<div class="alert alert-secondary d-flex align-items-center gap-2 mb-4 py-2 px-3 border-0 shadow-sm" style="border-radius:10px; background-color:#edf2f7; color:#334155;">
    <svg width="20" height="20" fill="currentColor" class="flex-shrink-0 text-primary" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
    </svg>
    <div class="small">
        <strong>Hak Akses Pemilik (Strictly Read-Only):</strong> Halaman ini memuat seluruh data riwayat pemesanan penyewaan pelanggan dari database untuk audit performa usaha. Seluruh aksi operasional dinonaktifkan.
    </div>
</div>

<!-- Search and Date Filter Form -->
<div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="ac-card-body p-4">
        <form method="GET" action="{{ route('pemilik.transaksi.index') }}" class="row g-3">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label small fw-bold text-muted">Kata Kunci Pencarian</label>
                <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="No. Transaksi atau Nama Pelanggan..." value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="tanggal_mulai" class="form-label small fw-bold text-muted">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control form-control-sm" value="{{ $filters['tanggal_mulai'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="tanggal_selesai" class="form-label small fw-bold text-muted">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control form-control-sm" value="{{ $filters['tanggal_selesai'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="status_penyewaan" class="form-label small fw-bold text-muted">Status Pesanan</label>
                <select name="status_penyewaan" id="status_penyewaan" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="Menunggu Pembayaran" {{ ($filters['status_penyewaan'] ?? '') === 'Menunggu Pembayaran' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                    <option value="Menunggu Konfirmasi" {{ ($filters['status_penyewaan'] ?? '') === 'Menunggu Konfirmasi' ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                    <option value="Disetujui" {{ ($filters['status_penyewaan'] ?? '') === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="Siap Diambil" {{ ($filters['status_penyewaan'] ?? '') === 'Siap Diambil' ? 'selected' : '' }}>Siap Diambil</option>
                    <option value="Berjalan" {{ ($filters['status_penyewaan'] ?? '') === 'Berjalan' ? 'selected' : '' }}>Sedang Disewa / Berjalan</option>
                    <option value="Selesai" {{ ($filters['status_penyewaan'] ?? '') === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="Dibatalkan" {{ ($filters['status_penyewaan'] ?? '') === 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="status_pembayaran" class="form-label small fw-bold text-muted">Status Pembayaran</label>
                <select name="status_pembayaran" id="status_pembayaran" class="form-select form-select-sm">
                    <option value="">Semua Pembayaran</option>
                    <option value="Lunas" {{ ($filters['status_pembayaran'] ?? '') === 'Lunas' ? 'selected' : '' }}>Lunas</option>
                    <option value="Menunggu Verifikasi" {{ ($filters['status_pembayaran'] ?? '') === 'Menunggu Verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    <option value="Belum Dibayar" {{ ($filters['status_pembayaran'] ?? '') === 'Belum Dibayar' ? 'selected' : '' }}>Belum Dibayar</option>
                    <option value="Ditolak" {{ ($filters['status_pembayaran'] ?? '') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                @if($hasActiveFilter)
                    <a href="{{ route('pemilik.transaksi.index') }}" class="btn btn-sm btn-outline-secondary">
                        ✕ Reset Filter
                    </a>
                @endif
                <button type="submit" class="btn btn-sm btn-primary">
                    🔍 Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="ac-card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 px-4 gap-2">
        <div>
            <h3 class="ac-card-title fs-5 fw-bold mb-0 text-dark">📋 Daftar Riwayat Pesanan Pelanggan</h3>
            <small class="text-muted">Riwayat transaksi penyewaan alat camping oleh pelanggan</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary" style="font-size:0.75rem;">Mode Read-Only</span>
        </div>
    </div>

    <div class="ac-card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">No. Transaksi</th>
                        <th>Tanggal Pemesanan</th>
                        <th>Nama Pelanggan</th>
                        <th class="text-end">Total Tagihan</th>
                        <th class="text-center">Metode Pembayaran</th>
                        <th class="text-center">Status Pembayaran</th>
                        <th class="text-center">Status Pesanan</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksiList as $trx)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark font-monospace">{{ $trx->no_transaksi }}</span>
                            </td>
                            <td>
                                <div>{{ $trx->tanggal_sewa ? \Carbon\Carbon::parse($trx->tanggal_sewa)->translatedFormat('d M Y') : '-' }}</div>
                                <small class="text-muted">Durasi: {{ $trx->durasi_hari }} hari</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $trx->pelanggan?->nama_pelanggan ?? 'Pelanggan Umum' }}</div>
                                <small class="text-muted">{{ $trx->pelanggan?->nomor_telepon ?? '-' }}</small>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-dark font-monospace">Rp {{ number_format((float) $trx->total_biaya, 0, ',', '.') }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $trx->pembayaran?->metode_pembayaran ?? '-' }}</span>
                            </td>
                            <td class="text-center">
                                <x-status-badge :status="$trx->status_pembayaran" />
                            </td>
                            <td class="text-center">
                                <x-status-badge :status="$trx->status_penyewaan" />
                            </td>
                            <td class="text-center pe-4">
                                <a href="{{ route('pemilik.transaksi.show', $trx->no_transaksi) }}" class="btn btn-sm btn-outline-primary" style="font-size: 0.8rem;">
                                    🔍 Lihat Rincian
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                @if($hasActiveFilter)
                                    <div class="mb-2" style="font-size: 2.5rem;">🔍</div>
                                    <h6 class="fw-bold text-secondary">Tidak Ada Transaksi Sesuai Filter</h6>
                                    <p class="small text-muted mb-3">Tidak ditemukan data transaksi pemesanan yang cocok dengan kriteria pencarian dan tanggal yang dipilih.</p>
                                    <a href="{{ route('pemilik.transaksi.index') }}" class="btn btn-sm btn-outline-secondary">
                                        Reset Filter
                                    </a>
                                @else
                                    <div class="mb-2" style="font-size: 2.5rem;">📭</div>
                                    <h6 class="fw-bold text-secondary">Belum Ada Riwayat Transaksi Pelanggan</h6>
                                    <p class="small text-muted mb-0">Seluruh riwayat pemesanan yang tercatat di database akan ditampilkan di sini.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
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
