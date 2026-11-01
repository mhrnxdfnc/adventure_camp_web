@extends('layouts.app')

@section('title', 'Detail Inventaris — ' . $alat->nama_alat)
@section('page-title', 'Detail Inventaris & Kontrol Stok')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.inventaris.index') }}" class="text-decoration-none">Inventaris & Stok</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $alat->kode_alat }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">{{ $alat->nama_alat }} ({{ $alat->kode_alat }})</h2>
            <p class="text-muted small mb-0">Kategori: <strong>{{ $alat->kategori }}</strong> &bull; Tarif: <strong>{{ $alat->formatted_tarif }}/hari</strong></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.inventaris.pembelian.create', ['kode_alat' => $alat->kode_alat]) }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 shadow-sm" id="btn-beli-stok-supplier">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Beli Stok via PO Supplier</span>
            </a>
            <a href="{{ route('admin.inventaris.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali ke Daftar
            </a>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Baris Metrik Stok Alat Ini -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Stok Fisik Total</span>
                <div class="ac-stat-icon-wrap" style="background-color: #e0f2fe; color: #0369a1;">📦</div>
            </div>
            <div class="ac-stat-value font-monospace">{{ $stokFisikTotal }}</div>
            <div class="ac-stat-sub">Semua unit alat ini</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Stok di Store</span>
                <div class="ac-stat-icon-wrap" style="background-color: #f1f5f9; color: #475569;">🏪</div>
            </div>
            <div class="ac-stat-value font-monospace">{{ $stokToko }}</div>
            <div class="ac-stat-sub">Fisik di rak toko</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Stok Siap Sewa</span>
                <div class="ac-stat-icon-wrap" style="background-color: #dcfce7; color: #15803d;">✅</div>
            </div>
            <div class="ac-stat-value text-success font-monospace">{{ $stokSiapSewa }}</div>
            <div class="ac-stat-sub">
                @if($alat->kondisi === 'Baik')
                    Kondisi Baik & siap
                @else
                    <span class="text-danger fw-bold">0 (Kondisi {{ $alat->kondisi }})</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Sedang Disewa</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #b45309;">🏕️</div>
            </div>
            <div class="ac-stat-value text-warning font-monospace">{{ $stokSedangDisewa }}</div>
            <div class="ac-stat-sub">
                Di tangan pelanggan
                @if($stokDialokasikan > 0)
                    &bull; <span class="text-primary">{{ $stokDialokasikan }} di toko</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Perlu Servis</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fffbeb; color: #d97706;">🔧</div>
            </div>
            <div class="ac-stat-value text-warning font-monospace">{{ $stokPerluPerbaikan }}</div>
            <div class="ac-stat-sub">Menunggu perbaikan</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Rusak / Afkir</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fee2e2; color: #b91c1c;">⚠️</div>
            </div>
            <div class="ac-stat-value text-danger font-monospace">{{ $stokRusak }}</div>
            <div class="ac-stat-sub">Tidak disewakan</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Informasi Alat & Transaksi Alokasi Lapangan -->
    <div class="col-lg-7">
        <!-- Informasi Spesifikasi Alat -->
        <div class="ac-card mb-4">
            <div class="ac-card-header">
                <h3 class="ac-card-title">Spesifikasi & Kondisi Fisik</h3>
            </div>
            <div class="ac-card-body p-3">
                <div class="row g-3 small">
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Kode Alat:</span>
                        <strong class="font-monospace text-dark">{{ $alat->kode_alat }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Nama Alat:</span>
                        <strong class="text-dark">{{ $alat->nama_alat }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Kategori:</span>
                        <span class="badge bg-secondary-subtle text-dark border">{{ $alat->kategori }}</span>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Kondisi Alat Saat Ini:</span>
                        <x-status-badge :status="$alat->kondisi" />
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Tarif Sewa Harian:</span>
                        <strong class="text-success font-monospace">{{ $alat->formatted_tarif }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Terdaftar Sejak:</span>
                        <span class="text-dark">{{ $alat->created_at ? $alat->created_at->format('d/m/Y H:i') : '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Transaksi Aktif yang Sedang Disewa di Lapangan -->
        <div class="ac-card mb-4">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="ac-card-title">🏕️ Unit Sedang Disewa di Lapangan</h3>
                    <small class="text-muted">Unit Sedang Dialokasikan / Disewa &bull; Telah diserahkan ke pelanggan dan belum selesai dikembalikan</small>
                </div>
                <span class="badge bg-warning text-dark font-monospace">{{ $stokSedangDisewa }} unit</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Pelanggan</th>
                            <th class="text-center">Diserahkan</th>
                            <th class="text-center">Belum Kembali</th>
                            <th>Jadwal Sewa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sedangDisewaAktif as $detail)
                            <tr>
                                <td>
                                    <strong class="font-monospace text-dark">{{ $detail->no_transaksi }}</strong>
                                </td>
                                <td>
                                    <span class="text-dark fw-bold">{{ $detail->penyewaan->pelanggan->nama_pelanggan ?? '-' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $detail->jumlah_diserahkan > 0 ? $detail->jumlah_diserahkan : $detail->jumlah }} unit
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle font-monospace fw-bold">
                                        {{ $detail->jumlah_sisa_kembali }} unit
                                    </span>
                                </td>
                                <td>
                                    <span>{{ \Carbon\Carbon::parse($detail->penyewaan->tanggal_sewa)->format('d/m/Y') }} &rarr; {{ \Carbon\Carbon::parse($detail->penyewaan->tanggal_kembali)->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <x-status-badge :status="$detail->penyewaan->status_penyewaan" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <em>Tidak ada unit yang sedang disewa di lapangan saat ini.</em>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($dialokasikanAktif->isNotEmpty())
            <!-- Tabel Transaksi yang Dialokasikan di Toko tapi Belum Diambil -->
            <div class="ac-card mb-4 border-info">
                <div class="ac-card-header bg-info-subtle d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="ac-card-title text-dark">📦 Unit Dialokasikan di Toko / Menunggu Pengambilan</h3>
                        <small class="text-muted">Unit masih berada di toko, dialokasikan untuk pesanan aktif pelanggan</small>
                    </div>
                    <span class="badge bg-info text-dark font-monospace">{{ $stokDialokasikan }} unit</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>No. Transaksi</th>
                                <th>Pelanggan</th>
                                <th class="text-center">Dipesan</th>
                                <th>Jadwal Sewa</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dialokasikanAktif as $detail)
                                <tr>
                                    <td>
                                        <strong class="font-monospace text-dark">{{ $detail->no_transaksi }}</strong>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-bold">{{ $detail->penyewaan->pelanggan->nama_pelanggan ?? '-' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info border border-info-subtle font-monospace">
                                            {{ $detail->jumlah }} unit
                                        </span>
                                    </td>
                                    <td>
                                        <span>{{ \Carbon\Carbon::parse($detail->penyewaan->tanggal_sewa)->format('d/m/Y') }} &rarr; {{ \Carbon\Carbon::parse($detail->penyewaan->tanggal_kembali)->format('d/m/Y') }}</span>
                                    </td>
                                    <td>
                                        <x-status-badge :status="$detail->penyewaan->status_penyewaan" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Section Unit Menunggu Konfirmasi Selesai Perbaikan -->
        <div class="ac-card mb-4 border-warning">
            <div class="ac-card-header bg-warning-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="ac-card-title text-dark fs-6 fw-bold mb-0">🔧 Unit Menunggu Konfirmasi Selesai Perbaikan</h3>
                    <small class="text-muted">Unit kembali tersedia di stok siap sewa setelah dikonfirmasi oleh petugas yang berwenang</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning text-dark font-monospace">{{ $stokPerluPerbaikan }} unit</span>
                    <button type="button" class="btn btn-sm btn-dark d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTindakKerusakan" style="font-size:0.75rem;">
                        <span>⚙️ Tindak Unit / Catat Biaya Servis</span>
                    </button>
                </div>
            </div>
            <div class="ac-card-body p-3">
                @if($alat->kondisi === 'Perlu Perbaikan')
                    <div class="alert alert-warning d-flex justify-content-between align-items-center p-3 mb-3">
                        <div>
                            <strong>Master alat berstatus Perlu Perbaikan</strong>
                            <small class="d-block text-muted">Semua stok toko ({{ $alat->stok }} unit) saat ini tidak siap sewa.</small>
                        </div>
                        <form action="{{ route('admin.inventaris.konfirmasi-perbaikan', $alat->kode_alat) }}" method="POST" onsubmit="if (this.submitted) return false; this.submitted = true;">
                            @csrf
                            <input type="hidden" name="jumlah" value="1">
                            <input type="hidden" name="catatan" value="Konfirmasi perbaikan kondisi fisik master alat selesai">
                            <button type="submit" class="btn btn-sm btn-success font-weight-bold">
                                ✓ Konfirmasi Master Selesai (Ubah ke Baik)
                            </button>
                        </form>
                    </div>
                @endif

                @if($unitPerbaikanList->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Pelanggan</th>
                                    <th class="text-center">Perlu Servis</th>
                                    <th class="text-center">Selesai Diperbaiki</th>
                                    <th class="text-center">Sisa Menunggu</th>
                                    <th class="text-end">Aksi Konfirmasi Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unitPerbaikanList as $detail)
                                    @php
                                        $sisaPending = max(0, (int) $detail->jumlah_perlu_perbaikan - (int) ($detail->jumlah_diperbaiki ?? 0));
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong class="font-monospace text-dark">{{ $detail->no_transaksi }}</strong>
                                        </td>
                                        <td>
                                            <span>{{ $detail->penyewaan->pelanggan->nama_pelanggan ?? 'Umum' }}</span>
                                        </td>
                                        <td class="text-center fw-bold text-warning font-monospace">
                                            {{ $detail->jumlah_perlu_perbaikan }} unit
                                        </td>
                                        <td class="text-center text-success font-monospace">
                                            {{ (int) ($detail->jumlah_diperbaiki ?? 0) }} unit
                                        </td>
                                        <td class="text-center fw-bold text-danger font-monospace">
                                            {{ $sisaPending }} unit
                                        </td>
                                        <td class="text-end">
                                            <form action="{{ route('admin.inventaris.konfirmasi-perbaikan', $alat->kode_alat) }}" method="POST" class="d-inline-flex align-items-center gap-1 justify-content-end" onsubmit="if (this.submitted) return false; this.submitted = true;">
                                                @csrf
                                                <input type="hidden" name="id_detail" value="{{ $detail->id_detail }}">
                                                <input type="number" name="jumlah" value="{{ $sisaPending }}" min="1" max="{{ $sisaPending }}" class="form-control form-control-sm text-center font-monospace" style="width: 70px;" required>
                                                <button type="submit" class="btn btn-sm btn-success font-weight-bold d-inline-flex align-items-center gap-1 py-1 px-2" title="Konfirmasi perbaikan selesai">
                                                    <span>✓</span>
                                                    <span>Kembalikan ke Stok</span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif($alat->kondisi !== 'Perlu Perbaikan')
                    <div class="text-center py-3 text-muted small">
                        <span class="d-block mb-1 fs-5">✅</span>
                        Tidak ada unit yang sedang dalam antrean perbaikan untuk alat ini. Seluruh stok terawat dengan baik.
                    </div>
                @endif
            </div>
        </div>

        <!-- Card Riwayat Penanganan Kerusakan & Biaya Servis -->
        <div class="ac-card mb-4">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="ac-card-title fs-6 fw-bold mb-0">🛠️ Riwayat Penanganan Kerusakan & Biaya Servis</h3>
                    <small class="text-muted">Catatan tindakan penanganan, servis teknisi, dan biaya perbaikan riil</small>
                </div>
                <span class="badge bg-secondary font-monospace">{{ $riwayatPenanganan->count() }} catatan</span>
            </div>
            <div class="ac-card-body p-0">
                @if($riwayatPenanganan->isEmpty())
                    <div class="text-center py-4 text-muted small">
                        <span class="d-block mb-1 fs-5">📋</span>
                        Belum ada riwayat penanganan kerusakan atau pengeluaran biaya perbaikan untuk alat ini.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tindakan</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-end">Biaya Servis</th>
                                    <th>Teknisi / Pelaksana</th>
                                    <th>Keterangan</th>
                                    <th>Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($riwayatPenanganan as $log)
                                    <tr>
                                        <td>
                                            <span class="text-dark">{{ \Carbon\Carbon::parse($log->tanggal_tindakan)->format('d/m/Y') }}</span>
                                        </td>
                                        <td>
                                            @if($log->jenis_tindakan === 'Selesai Perbaikan')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">✓ Selesai Perbaikan</span>
                                            @elseif($log->jenis_tindakan === 'Catat Biaya Perbaikan')
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">💵 Catat Biaya</span>
                                            @elseif($log->jenis_tindakan === 'Afkir/Pensiun')
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">⚠️ Afkir/Pensiun</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">⏳ Menunggu</span>
                                            @endif
                                        </td>
                                        <td class="text-center font-monospace fw-bold">{{ $log->jumlah_unit }} unit</td>
                                        <td class="text-end font-monospace fw-bold {{ $log->biaya_perbaikan > 0 ? 'text-danger' : 'text-muted' }}">
                                            Rp {{ number_format($log->biaya_perbaikan, 0, ',', '.') }}
                                        </td>
                                        <td>{{ $log->pelaksana_teknisi ?: '-' }}</td>
                                        <td><span class="text-muted">{{ $log->keterangan ?: '-' }}</span></td>
                                        <td><small class="text-muted">{{ $log->admin->nama ?? 'Admin' }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Section Riwayat Stok (Akan diintegrasikan pada tahap berikutnya) -->
        <div id="section-riwayat-stok">
            @includeIf('admin.inventaris.partials.riwayat_alat', ['alat' => $alat])
        </div>
    </div>

    <!-- Kolom Kanan: Formulir Penyesuaian Stok Manual -->
    <div class="col-lg-5">
        <div class="ac-card border-primary" id="card-penyesuaian-stok">
            <div class="ac-card-header bg-primary text-white">
                <h3 class="ac-card-title text-white mb-0">⚖️ Penyesuaian Stok Fisik Manual</h3>
            </div>
            <div class="ac-card-body p-3">
                <p class="small text-muted mb-3">
                    Gunakan formulir ini untuk mencatat penambahan stok (misal: restok non-supplier, inventarisasi ulang) atau pengurangan stok (misal: afkir unit hilang/rusak). Alasan wajib diisi secara transparan.
                </p>

                <form action="{{ route('admin.inventaris.adjust-stock', $alat->kode_alat) }}" method="POST" id="form-penyesuaian-stok">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Jenis Penyesuaian <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipe_penyesuaian" id="tipe_tambah" value="tambah" checked>
                                <label class="form-check-label small fw-bold text-success" for="tipe_tambah">
                                    ➕ Tambah Stok
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipe_penyesuaian" id="tipe_kurang" value="kurang">
                                <label class="form-check-label small fw-bold text-danger" for="tipe_kurang">
                                    ➖ Kurangi Stok
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="jumlah_perubahan" class="form-label small fw-bold text-dark">Jumlah Unit <span class="text-danger">*</span></label>
                        <input type="number" 
                               name="jumlah" 
                               id="jumlah_perubahan" 
                               class="form-control form-control-sm @error('jumlah') is-invalid @enderror" 
                               value="{{ old('jumlah', 1) }}" 
                               min="1" 
                               required>
                        @error('jumlah')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="alasan_perubahan" class="form-label small fw-bold text-dark">Alasan Penyesuaian Stok <span class="text-danger">*</span></label>
                        <textarea name="alasan" 
                                  id="alasan_perubahan" 
                                  rows="3" 
                                  class="form-control form-control-sm @error('alasan') is-invalid @enderror" 
                                  placeholder="Contoh: Penambahan unit fisik dari gudang cadangan, atau Pengurangan unit rusak parah yang diafkir." 
                                  required>{{ old('alasan') }}</textarea>
                        @error('alasan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Alasan wajib dicantumkan demi integritas audit stok.</small>
                    </div>

                    <div class="p-2 bg-light rounded border mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Stok Toko Saat Ini:</span>
                            <strong class="font-monospace" id="label-stok-sekarang">{{ $stokToko }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Estimasi Stok Setelahnya:</span>
                            <strong class="font-monospace fs-6" id="label-stok-estimasi">{{ $stokToko + 1 }}</strong>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-sm shadow-sm" id="btn-submit-penyesuaian">
                        Simpan Penyesuaian Stok
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tipeTambah = document.getElementById('tipe_tambah');
    const tipeKurang = document.getElementById('tipe_kurang');
    const inputJumlah = document.getElementById('jumlah_perubahan');
    const labelEstimasi = document.getElementById('label-stok-estimasi');
    const stokSekarang = {{ $stokToko }};

    function updateEstimasi() {
        const qty = parseInt(inputJumlah.value) || 0;
        const isTambah = tipeTambah.checked;
        const estimasi = isTambah ? (stokSekarang + qty) : (stokSekarang - qty);

        labelEstimasi.textContent = estimasi;
        if (estimasi < 0) {
            labelEstimasi.className = 'font-monospace fs-6 text-danger fw-bold';
            labelEstimasi.textContent = estimasi + ' (DITOLAK - Stok Negatif!)';
        } else {
            labelEstimasi.className = 'font-monospace fs-6 text-success fw-bold';
        }
    }

    if (tipeTambah && tipeKurang && inputJumlah) {
        tipeTambah.addEventListener('change', updateEstimasi);
        tipeKurang.addEventListener('change', updateEstimasi);
        inputJumlah.addEventListener('input', updateEstimasi);
        updateEstimasi();
    }
});
</script>

<!-- Modal Tindak Kerusakan & Catat Biaya Servis -->
<div class="modal fade" id="modalTindakKerusakan" tabindex="-1" aria-labelledby="modalTindakKerusakanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6" id="modalTindakKerusakanLabel">⚙️ Tindak Unit Bermasalah & Biaya Servis: {{ $alat->nama_alat }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.inventaris.tindak-kerusakan', $alat->kode_alat) }}" method="POST" onsubmit="const btn = this.querySelector('button[type=submit]'); if (this.submitted) return false; this.submitted = true; btn.disabled = true; btn.innerText = 'Menyimpan...'; return true;">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Pilih Jenis Tindakan <span class="text-danger">*</span></label>
                        <select name="jenis_tindakan" class="form-select form-select-sm" required id="selectJenisTindakan">
                            <option value="Menunggu Perbaikan">⏳ Menunggu Perbaikan (Catat Status Antrean Servis)</option>
                            <option value="Catat Biaya Perbaikan">💵 Catat Biaya Perbaikan (Catat Pengeluaran Tanpa Mengubah Stok)</option>
                            <option value="Selesai Perbaikan">✅ Selesai Perbaikan (Kembalikan Unit ke Stok Siap Sewa)</option>
                            <option value="Afkir/Pensiun">⚠️ Afkir / Pensiunkan Unit (Unit Rusak Parah Dikeluarkan)</option>
                        </select>
                    </div>

                    @if($unitPerbaikanList->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Sumber Unit Transaksi (Opsional)</label>
                            <select name="id_detail" class="form-select form-select-sm">
                                <option value="">-- Unit Master Fisik Toko --</option>
                                @foreach($unitPerbaikanList as $detail)
                                    @php
                                        $sisa = max(0, (int) $detail->jumlah_perlu_perbaikan - (int) ($detail->jumlah_diperbaiki ?? 0));
                                    @endphp
                                    <option value="{{ $detail->id_detail }}">
                                        Transaksi {{ $detail->no_transaksi }} &mdash; {{ $detail->penyewaan->pelanggan->nama_pelanggan ?? 'Umum' }} (Sisa: {{ $sisa }} unit)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Jumlah Unit <span class="text-danger">*</span></label>
                            <input type="number" name="jumlah" class="form-control form-control-sm font-monospace" value="1" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Tanggal Tindakan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_tindakan" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Biaya Perbaikan / Servis (Rp)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="biaya_perbaikan" class="form-control font-monospace" value="0" min="0" step="500" placeholder="0">
                        </div>
                        <small class="text-muted" style="font-size:0.75rem;">Masukkan biaya riil yang dibayarkan ke teknisi/bengkel. Akan tercatat pada laporan pengeluaran Pemilik.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Teknisi / Bengkel / Pelaksana</label>
                        <input type="text" name="pelaksana_teknisi" class="form-control form-control-sm" placeholder="Contoh: Bengkel Outdoor Jaya / Staf Gudang">
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-dark">Keterangan / Rincian Kerusakan</label>
                        <textarea name="keterangan" rows="2" class="form-control form-control-sm" placeholder="Contoh: Ganti resleting tenda, penggantian frame patah, pembersihan jamur..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3">Simpan Tindakan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
