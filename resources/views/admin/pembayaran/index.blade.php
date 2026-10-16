@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran — Admin')
@section('page-title', 'Verifikasi Pembayaran')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Verifikasi Pembayaran</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Pemeriksaan & Verifikasi Pembayaran</h2>
            <p class="text-muted small mb-0">Verifikasi mutasi bukti transfer bank, transaksi QRIS kasir, dan penerimaan uang tunai secara akurat.</p>
        </div>
    </div>
</div>

<!-- Kartu Ringkasan Status Verifikasi -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <div class="ac-stat-card border-start border-4 border-warning">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Menunggu Verifikasi</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #b45309;">
                    ⏳
                </div>
            </div>
            <div class="ac-stat-value" id="count-menunggu-verifikasi">{{ $countMenunggu }}</div>
            <div class="ac-stat-sub">Bukti transfer / transaksi masuk</div>
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="ac-stat-card border-start border-4 border-success">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Pembayaran Lunas</span>
                <div class="ac-stat-icon-wrap" style="background-color: #d1fae5; color: #065f46;">
                    ✅
                </div>
            </div>
            <div class="ac-stat-value" id="count-pembayaran-lunas">{{ $countLunas }}</div>
            <div class="ac-stat-sub">Telah diverifikasi Admin</div>
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="ac-stat-card border-start border-4 border-danger">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Bukti Ditolak</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fee2e2; color: #991b1b;">
                    ❌
                </div>
            </div>
            <div class="ac-stat-value" id="count-pembayaran-ditolak">{{ $countDitolak }}</div>
            <div class="ac-stat-sub">Menunggu perbaikan pelanggan</div>
        </div>
    </div>
</div>

<!-- Filter Tab & Pencarian -->
<div class="ac-card mb-4">
    <div class="ac-card-body p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <!-- Tab Status -->
            <ul class="nav nav-pills gap-1" id="pembayaranTabs">
                <li class="nav-item">
                    <a href="{{ route('admin.pembayaran.index', ['status' => 'Menunggu Verifikasi', 'metode' => $metodeFilter]) }}" 
                       class="nav-link py-2 px-3 {{ $statusFilter === 'Menunggu Verifikasi' ? 'active font-weight-bold' : 'text-secondary' }}"
                       style="{{ $statusFilter === 'Menunggu Verifikasi' ? 'background-color: var(--ac-primary, #1b4332);' : '' }}">
                        <span>⏳ Menunggu Verifikasi</span>
                        @if($countMenunggu > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $countMenunggu }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pembayaran.index', ['status' => 'Belum Dibayar', 'metode' => $metodeFilter]) }}" 
                       class="nav-link py-2 px-3 {{ $statusFilter === 'Belum Dibayar' ? 'active font-weight-bold' : 'text-secondary' }}"
                       style="{{ $statusFilter === 'Belum Dibayar' ? 'background-color: var(--ac-primary, #1b4332);' : '' }}">
                        <span>Belum Dibayar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pembayaran.index', ['status' => 'Lunas', 'metode' => $metodeFilter]) }}" 
                       class="nav-link py-2 px-3 {{ $statusFilter === 'Lunas' ? 'active font-weight-bold' : 'text-secondary' }}"
                       style="{{ $statusFilter === 'Lunas' ? 'background-color: var(--ac-primary, #1b4332);' : '' }}">
                        <span>✅ Lunas</span>
                        @if($countLunas > 0)
                            <span class="badge bg-success ms-1">{{ $countLunas }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pembayaran.index', ['status' => 'Ditolak', 'metode' => $metodeFilter]) }}" 
                       class="nav-link py-2 px-3 {{ $statusFilter === 'Ditolak' ? 'active font-weight-bold' : 'text-secondary' }}"
                       style="{{ $statusFilter === 'Ditolak' ? 'background-color: var(--ac-primary, #1b4332);' : '' }}">
                        <span>❌ Ditolak</span>
                        @if($countDitolak > 0)
                            <span class="badge bg-danger ms-1">{{ $countDitolak }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pembayaran.index', ['status' => 'Semua', 'metode' => $metodeFilter]) }}" 
                       class="nav-link py-2 px-3 {{ $statusFilter === 'Semua' ? 'active font-weight-bold' : 'text-secondary' }}"
                       style="{{ $statusFilter === 'Semua' ? 'background-color: var(--ac-primary, #1b4332);' : '' }}">
                        <span>Semua Transaksi</span>
                    </a>
                </li>
            </ul>

            <!-- Form Pencarian & Filter Metode -->
            <form action="{{ route('admin.pembayaran.index') }}" method="GET" class="d-flex align-items-center gap-2 ms-auto">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <select name="metode" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 140px;">
                    <option value="">Semua Metode</option>
                    <option value="Transfer Bank" {{ $metodeFilter === 'Transfer Bank' ? 'selected' : '' }}>Transfer Bank</option>
                    <option value="QRIS" {{ $metodeFilter === 'QRIS' ? 'selected' : '' }}>QRIS Kasir</option>
                    <option value="Cash" {{ $metodeFilter === 'Cash' ? 'selected' : '' }}>Tunai / Cash</option>
                </select>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari No. TRX / Pelanggan...">
                    <button class="btn btn-outline-secondary" type="submit">Cari</button>
                    @if($search !== '' || $metodeFilter !== '')
                        <a href="{{ route('admin.pembayaran.index', ['status' => $statusFilter]) }}" class="btn btn-outline-danger" title="Reset filter">&times;</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabel Daftar Pembayaran -->
<div class="ac-card">
    <div class="ac-card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="ac-card-title">Daftar Pembayaran ({{ $transaksiList->count() }} Data)</h3>
            <small class="text-muted">Kategori: <strong>{{ $statusFilter }}</strong> {{ $metodeFilter ? '— Metode: ' . $metodeFilter : '' }}</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="table-pembayaran-admin">
            <thead class="table-light small">
                <tr>
                    <th>No. Transaksi</th>
                    <th>Pelanggan</th>
                    <th>Periode & Durasi</th>
                    <th>Metode</th>
                    <th>Jumlah Tagihan</th>
                    <th>Bukti Pembayaran</th>
                    <th>Status Pembayaran</th>
                    <th class="text-center" style="min-width: 170px;">Tindakan Admin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaksiList as $trx)
                    @php
                        $pembayaran = $trx->pembayaran;
                        $metode = $pembayaran?->metode_pembayaran ?? 'Transfer Bank';
                        $statusBayar = $pembayaran?->status_pembayaran ?? 'Belum Dibayar';
                        $hasBukti = !empty($pembayaran?->bukti_pembayaran);
                    @endphp
                    <tr>
                        <!-- No. Transaksi -->
                        <td>
                            <strong class="text-dark font-monospace d-block">{{ $trx->no_transaksi }}</strong>
                            @if($pembayaran?->no_pembayaran)
                                <small class="text-muted font-monospace">{{ $pembayaran->no_pembayaran }}</small>
                            @else
                                <small class="text-muted fst-italic">Belum ada No. Bayar</small>
                            @endif
                        </td>

                        <!-- Pelanggan -->
                        <td>
                            <div class="font-weight-bold text-dark">{{ $trx->pelanggan->nama_pelanggan ?? 'Pelanggan #' . $trx->id_pelanggan }}</div>
                            <small class="text-muted">{{ $trx->pelanggan->nomor_telepon ?? '-' }}</small>
                        </td>

                        <!-- Periode & Durasi -->
                        <td>
                            <span class="small text-dark d-block">
                                {{ \Carbon\Carbon::parse($trx->tanggal_sewa)->format('d/m/Y') }} &rarr; {{ \Carbon\Carbon::parse($trx->tanggal_kembali)->format('d/m/Y') }}
                            </span>
                            <span class="badge bg-secondary-subtle text-dark border" style="font-size: 0.7rem;">
                                {{ $trx->durasi_hari }} Hari ({{ $trx->detailPenyewaan->count() }} item)
                            </span>
                        </td>

                        <!-- Metode Pembayaran -->
                        <td>
                            @if($metode === 'Transfer Bank')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    🏦 Transfer Bank
                                </span>
                            @elseif($metode === 'QRIS')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    📱 QRIS Kasir
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                                    💵 Tunai (Cash)
                                </span>
                            @endif
                        </td>

                        <!-- Jumlah Tagihan -->
                        <td>
                            <strong class="text-success d-block font-monospace">Rp {{ number_format($trx->total_biaya, 0, ',', '.') }}</strong>
                            @if($pembayaran && $pembayaran->isLunas())
                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                    Diterima: Rp {{ number_format($pembayaran->jumlah_pembayaran, 0, ',', '.') }}
                                </small>
                            @endif
                        </td>

                        <!-- Bukti Pembayaran -->
                        <td>
                            @if($hasBukti)
                                <div class="d-flex flex-column gap-1">
                                    <a href="{{ route('pembayaran.bukti', $trx->no_transaksi) }}" 
                                       target="_blank" 
                                       class="btn btn-xs btn-outline-primary py-1 px-2 text-decoration-none d-inline-flex align-items-center gap-1"
                                       style="font-size: 0.78rem;"
                                       title="Lihat berkas bukti transfer resmi">
                                        <span>👁️</span>
                                        <span>Lihat Bukti Transfer</span>
                                    </a>
                                    @if($pembayaran->waktu_unggah_bukti)
                                        <small class="text-muted" style="font-size: 0.72rem;">
                                            {{ $pembayaran->waktu_unggah_bukti->translatedFormat('d M H:i') }}
                                        </small>
                                    @endif
                                </div>
                            @else
                                @if($metode === 'QRIS')
                                    <small class="text-muted d-block" style="font-size: 0.78rem;">
                                        🔍 Cek mutasi QRIS kasir
                                    </small>
                                @elseif($metode === 'Cash')
                                    <small class="text-muted d-block" style="font-size: 0.78rem;">
                                        💵 Terima uang di kasir
                                    </small>
                                @else
                                    <span class="text-muted small fst-italic">Belum diunggah</span>
                                @endif
                            @endif
                        </td>

                        <!-- Status Pembayaran -->
                        <td>
                            <x-status-badge :status="$statusBayar" />
                            @if($pembayaran?->isDitolak() && $pembayaran->alasan_penolakan)
                                <small class="text-danger d-block mt-1" style="font-size: 0.72rem;" title="{{ $pembayaran->alasan_penolakan }}">
                                    Alasan: {{ \Illuminate\Support\Str::limit($pembayaran->alasan_penolakan, 30) }}
                                </small>
                            @elseif($pembayaran?->isLunas() && $pembayaran->admin)
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                    Oleh: {{ $pembayaran->admin->nama_user }}
                                </small>
                            @endif
                        </td>

                        <!-- Tindakan Admin -->
                        <td class="text-center">
                            <div class="d-flex justify-content-center align-items-center gap-1 flex-wrap">
                                <!-- Tombol Rincian -->
                                <a href="{{ route('admin.pembayaran.show', $trx->no_transaksi) }}" 
                                   class="btn btn-sm btn-outline-secondary py-1 px-2"
                                   title="Lihat rincian lengkap transaksi">
                                    Detail
                                </a>

                                @if(! $pembayaran || ! $pembayaran->isLunas())
                                    <!-- Tombol Konfirmasi (Membuka Modal) -->
                                    <button type="button" 
                                            class="btn btn-sm btn-success py-1 px-2 font-weight-bold d-inline-flex align-items-center gap-1"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalKonfirmasi{{ str_replace('-', '', $trx->no_transaksi) }}"
                                            title="Konfirmasi pembayaran lunas">
                                        <span>✓</span>
                                        <span>Konfirmasi</span>
                                    </button>

                                    <!-- Tombol Tolak (Tersedia jika ada bukti atau status menunggu verifikasi) -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger py-1 px-2 d-inline-flex align-items-center gap-1"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalTolak{{ str_replace('-', '', $trx->no_transaksi) }}"
                                            title="Tolak bukti pembayaran tidak valid">
                                        <span>✕</span>
                                        <span>Tolak</span>
                                    </button>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                        Lunas Terverifikasi
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Konfirmasi Pembayaran -->
                    @if(! $pembayaran || ! $pembayaran->isLunas())
                        <div class="modal fade" id="modalKonfirmasi{{ str_replace('-', '', $trx->no_transaksi) }}" tabindex="-1" aria-labelledby="modalKonfirmasiLabel{{ str_replace('-', '', $trx->no_transaksi) }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="{{ route('admin.pembayaran.konfirmasi', $trx->no_transaksi) }}" method="POST">
                                        @csrf
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title h6 font-weight-bold mb-0" id="modalKonfirmasiLabel{{ str_replace('-', '', $trx->no_transaksi) }}">
                                                ✓ Konfirmasi Pembayaran Lunas
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4 text-start">
                                            <div class="p-3 bg-light rounded-3 mb-3 border">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted small">No. Transaksi:</span>
                                                    <strong class="text-dark font-monospace">{{ $trx->no_transaksi }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted small">Pelanggan:</span>
                                                    <strong class="text-dark">{{ $trx->pelanggan->nama_pelanggan ?? '-' }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between pt-2 border-top">
                                                    <span class="text-dark font-weight-bold">Total Tagihan:</span>
                                                    <strong class="text-success font-monospace" style="font-size: 1.1rem;">
                                                        Rp {{ number_format($trx->total_biaya, 0, ',', '.') }}
                                                    </strong>
                                                </div>
                                            </div>

                                            <!-- Pilihan Metode Pembayaran Aktual -->
                                            <div class="mb-3">
                                                <label for="metode_{{ str_replace('-', '', $trx->no_transaksi) }}" class="form-label small font-weight-bold text-dark">
                                                    Metode Pembayaran yang Diverifikasi:
                                                </label>
                                                <select name="metode_pembayaran" id="metode_{{ str_replace('-', '', $trx->no_transaksi) }}" class="form-select form-select-sm">
                                                    <option value="Transfer Bank" {{ $metode === 'Transfer Bank' ? 'selected' : '' }}>🏦 Transfer Bank (Mutasi Rekening)</option>
                                                    <option value="QRIS" {{ $metode === 'QRIS' ? 'selected' : '' }}>📱 QRIS Kasir (Aplikasi Kasir)</option>
                                                    <option value="Cash" {{ $metode === 'Cash' ? 'selected' : '' }}>💵 Tunai / Cash (Uang Fisik Diterima)</option>
                                                </select>
                                            </div>

                                            <!-- Nominal yang Diterima -->
                                            <div class="mb-3">
                                                <label for="jumlah_{{ str_replace('-', '', $trx->no_transaksi) }}" class="form-label small font-weight-bold text-dark">
                                                    Nominal yang Diterima (Rp):
                                                </label>
                                                <input type="number" 
                                                       name="jumlah_diterima" 
                                                       id="jumlah_{{ str_replace('-', '', $trx->no_transaksi) }}" 
                                                       class="form-control font-monospace" 
                                                       value="{{ (int) $trx->total_biaya }}" 
                                                       min="{{ (int) $trx->total_biaya }}"
                                                       step="1"
                                                       required>
                                                <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">
                                                    ⚠️ Pembayaran parsial belum didukung. Nominal harus melunasi seluruh total tagihan (minimal Rp {{ number_format($trx->total_biaya, 0, ',', '.') }}).
                                                </small>
                                            </div>

                                            <!-- SOP Peringatan Pemeriksaan -->
                                            <div class="alert alert-warning py-2 px-3 small mb-0 border-warning" style="border-radius: 8px;">
                                                <strong class="d-block mb-1">Pedoman SOP Admin:</strong>
                                                <ul class="ps-3 mb-0" style="line-height: 1.5;">
                                                    <li>Untuk <strong>Transfer Bank</strong>: Pastikan dana telah masuk di mutasi rekening bank resmi toko.</li>
                                                    <li>Untuk <strong>QRIS</strong>: Pastikan notifikasi dana masuk telah terbit di scanner/aplikasi QRIS kasir.</li>
                                                    <li>Untuk <strong>Cash</strong>: Pastikan uang fisik telah dihitung dan diterima secara tepat.</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light p-3">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-sm btn-success font-weight-bold px-3">
                                                ✓ Konfirmasi Lunas Sekarang
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Penolakan Pembayaran -->
                        <div class="modal fade" id="modalTolak{{ str_replace('-', '', $trx->no_transaksi) }}" tabindex="-1" aria-labelledby="modalTolakLabel{{ str_replace('-', '', $trx->no_transaksi) }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="{{ route('admin.pembayaran.tolak', $trx->no_transaksi) }}" method="POST">
                                        @csrf
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title h6 font-weight-bold mb-0" id="modalTolakLabel{{ str_replace('-', '', $trx->no_transaksi) }}">
                                                ✕ Tolak Bukti Pembayaran
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4 text-start">
                                            <div class="p-3 bg-light rounded-3 mb-3 border">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted small">No. Transaksi:</span>
                                                    <strong class="text-dark font-monospace">{{ $trx->no_transaksi }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted small">Total Tagihan:</span>
                                                    <strong class="text-dark font-monospace">Rp {{ number_format($trx->total_biaya, 0, ',', '.') }}</strong>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="alasan_{{ str_replace('-', '', $trx->no_transaksi) }}" class="form-label small font-weight-bold text-dark">
                                                    Alasan Penolakan <span class="text-danger">*</span>:
                                                </label>
                                                <textarea name="alasan_penolakan" 
                                                          id="alasan_{{ str_replace('-', '', $trx->no_transaksi) }}" 
                                                          class="form-control" 
                                                          rows="3" 
                                                          placeholder="Contoh: Bukti transfer buram/tidak terbaca, nominal transfer tidak sesuai tagihan, atau dana belum masuk mutasi bank." 
                                                          required 
                                                          minlength="5" 
                                                          maxlength="500"></textarea>
                                                <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">
                                                    Wajib diisi (minimal 5 karakter). Alasan ini akan ditampilkan kepada pelanggan agar dapat mengunggah bukti yang benar.
                                                </small>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light p-3">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-sm btn-danger font-weight-bold px-3">
                                                ✕ Tolak Pembayaran
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <x-empty-state 
                                title="Tidak Ada Data Pembayaran" 
                                message="Tidak ditemukan transaksi pembayaran dengan kriteria filter yang dipilih." 
                                icon="💳"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
