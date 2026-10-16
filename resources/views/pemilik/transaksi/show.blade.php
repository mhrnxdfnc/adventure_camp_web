@extends('layouts.app')

@section('title', 'Detail Transaksi ' . $penyewaan->no_transaksi . ' — Pemilik')
@section('page-title', 'Detail Transaksi Pelanggan')

@section('content')
<!-- Read-Only Notice -->
<div class="alert alert-secondary d-flex align-items-center gap-2 mb-4 py-2 px-3 border-0 shadow-sm" style="border-radius:10px; background-color:#edf2f7; color:#334155;">
    <svg width="20" height="20" fill="currentColor" class="flex-shrink-0 text-primary" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
    </svg>
    <div class="small">
        <strong>Hak Akses Pemilik (Strictly Read-Only):</strong> Halaman ini memuat seluruh rincian transaksi pemesanan pelanggan untuk keperluan pengawasan bisnis dan keuangan. Anda tidak memiliki wewenang untuk mengubah status atau melakukan tindakan operasional.
    </div>
</div>

<!-- Header Toolbar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('pemilik.transaksi.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Daftar
        </a>
        <h4 class="mb-0 fw-bold font-monospace text-dark">{{ $penyewaan->no_transaksi }}</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-secondary">Mode Read-Only</span>
        <x-status-badge :status="$penyewaan->status_penyewaan" />
        <x-status-badge :status="$penyewaan->status_pembayaran" />
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Informasi Pemesanan -->
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100 shadow-sm border-0" style="border-radius:12px;">
            <div class="ac-card-header bg-white border-bottom py-3 px-4">
                <h5 class="ac-card-title fs-6 fw-bold mb-0 text-dark">📅 Informasi Penyewaan</h5>
            </div>
            <div class="ac-card-body p-4">
                <div class="row g-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Nomor Transaksi</small>
                        <strong class="font-monospace text-dark">{{ $penyewaan->no_transaksi }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Durasi Sewa</small>
                        <strong class="text-dark">{{ $penyewaan->durasi_hari }} Hari</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Tanggal Mulai Sewa</small>
                        <span class="fw-semibold">{{ $penyewaan->tanggal_sewa ? \Carbon\Carbon::parse($penyewaan->tanggal_sewa)->translatedFormat('d F Y') : '-' }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Tanggal Kembali (Rencana)</small>
                        <span class="fw-semibold">{{ $penyewaan->tanggal_kembali ? \Carbon\Carbon::parse($penyewaan->tanggal_kembali)->translatedFormat('d F Y') : '-' }}</span>
                    </div>
                    <div class="col-12 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Total Tagihan Pesanan:</span>
                            <span class="fs-5 fw-bold text-dark font-monospace">Rp {{ number_format((float) $penyewaan->total_biaya, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informasi Pelanggan -->
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100 shadow-sm border-0" style="border-radius:12px;">
            <div class="ac-card-header bg-white border-bottom py-3 px-4">
                <h5 class="ac-card-title fs-6 fw-bold mb-0 text-dark">👤 Informasi Pelanggan</h5>
            </div>
            <div class="ac-card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <small class="text-muted d-block">Nama Pelanggan</small>
                        <strong class="fs-6 text-dark">{{ $penyewaan->pelanggan?->nama_pelanggan ?? 'Pelanggan Umum' }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Nomor Telepon / WhatsApp</small>
                        <span class="fw-semibold">{{ $penyewaan->pelanggan?->nomor_telepon ?? '-' }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Terdaftar Sejak</small>
                        <span>{{ $penyewaan->pelanggan?->created_at ? \Carbon\Carbon::parse($penyewaan->pelanggan->created_at)->translatedFormat('d M Y') : '-' }}</span>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Alamat Domisili</small>
                        <span class="text-secondary">{{ $penyewaan->pelanggan?->alamat ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rincian Item Alat Outdoor yang Disewa -->
<div class="ac-card shadow-sm border-0 mb-4" style="border-radius:12px;">
    <div class="ac-card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="ac-card-title fs-6 fw-bold mb-0 text-dark">⛺ Rincian Alat Outdoor yang Disewa</h5>
            <small class="text-muted">Daftar item alat, jumlah unit, dan kalkulasi tarif sewa</small>
        </div>
        <span class="badge bg-light text-dark border">{{ $penyewaan->detailPenyewaan->count() }} Item Alat</span>
    </div>

    <div class="ac-card-body p-0">
        @if($penyewaan->detailPenyewaan->isEmpty())
            <div class="p-4 text-center text-muted">
                <div class="mb-2" style="font-size: 2rem;">ℹ️</div>
                <h6 class="fw-bold text-secondary">Catatan Data Historis Legacy</h6>
                <p class="small text-muted mb-0">Transaksi ini tidak memiliki rincian alat di tabel <code>detail_penyewaan</code>. Total biaya bersumber dari rekaman pembayaran yang tersimpan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>Kode Alat</th>
                            <th>Nama Alat Outdoor</th>
                            <th>Kategori</th>
                            <th class="text-end">Tarif Sewa / Hari</th>
                            <th class="text-center">Jumlah Disewa</th>
                            <th class="text-center">Durasi</th>
                            <th class="text-end pe-4">Subtotal Tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($penyewaan->detailPenyewaan as $index => $item)
                            @php
                                $subtotalItem = (float) $item->jumlah * (float) $item->tarif_sewa * (int) $penyewaan->durasi_hari;
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted">{{ $index + 1 }}</td>
                                <td><code>{{ $item->kode_alat }}</code></td>
                                <td class="fw-semibold text-dark">{{ $item->alat?->nama_alat ?? $item->kode_alat }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $item->alat?->kategori ?? '-' }}</span></td>
                                <td class="text-end font-monospace">Rp {{ number_format((float) $item->tarif_sewa, 0, ',', '.') }}</td>
                                <td class="text-center fw-bold">{{ $item->jumlah }} unit</td>
                                <td class="text-center text-muted">{{ $penyewaan->durasi_hari }} hari</td>
                                <td class="text-end pe-4 fw-bold font-monospace text-dark">Rp {{ number_format($subtotalItem, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light border-top">
                        <tr>
                            <th colspan="7" class="text-end ps-4">Total Biaya Sewa:</th>
                            <th class="text-end pe-4 fs-6 text-dark font-monospace">Rp {{ number_format((float) $penyewaan->total_biaya, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Informasi Pembayaran & Keuangan -->
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100 shadow-sm border-0" style="border-radius:12px;">
            <div class="ac-card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="ac-card-title fs-6 fw-bold mb-0 text-dark">💳 Status & Rincian Pembayaran</h5>
                <x-status-badge :status="$penyewaan->status_pembayaran" />
            </div>
            <div class="ac-card-body p-4">
                @if(! $penyewaan->pembayaran)
                    <div class="p-3 text-center text-muted">
                        <p class="mb-0">Belum ada catatan pembayaran yang dibuat untuk transaksi ini.</p>
                    </div>
                @else
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Nomor Pembayaran</small>
                            <strong class="font-monospace text-dark">{{ $penyewaan->pembayaran->no_pembayaran }}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Metode Pembayaran</small>
                            <span class="badge bg-light text-dark border">{{ $penyewaan->pembayaran->metode_pembayaran }}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Total Nilai Tagihan</small>
                            <span class="fw-bold text-dark font-monospace">Rp {{ number_format((float) $penyewaan->pembayaran->total_biaya, 0, ',', '.') }}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Uang Benar-benar Diterima</small>
                            <span class="fw-bold text-success font-monospace">Rp {{ number_format((float) $penyewaan->pembayaran->jumlah_pembayaran, 0, ',', '.') }}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Tanggal Transaksi Pembayaran</small>
                            <span>{{ $penyewaan->pembayaran->tanggal_pembayaran ? \Carbon\Carbon::parse($penyewaan->pembayaran->tanggal_pembayaran)->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB' : '-' }}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Waktu Konfirmasi / Verifikasi</small>
                            <span>{{ $penyewaan->pembayaran->waktu_konfirmasi ? \Carbon\Carbon::parse($penyewaan->pembayaran->waktu_konfirmasi)->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB' : '-' }}</span>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Admin Verifikator</small>
                            <span>{{ $penyewaan->pembayaran->admin?->nama_user ?? ($penyewaan->pembayaran->isLunas() ? 'Sistem / Kasir' : '-') }}</span>
                        </div>

                        @if($penyewaan->pembayaran->bukti_pembayaran)
                            <div class="col-12 pt-2 border-top">
                                <small class="text-muted d-block mb-1">Bukti Transfer Nasabah</small>
                                <a href="{{ route('pembayaran.bukti', $penyewaan->no_transaksi) }}" target="_blank" class="btn btn-sm btn-outline-info" style="font-size:0.8rem;">
                                    📎 Buka Berkas Bukti Pembayaran (Read-Only)
                                </a>
                            </div>
                        @endif

                        @if($penyewaan->pembayaran->alasan_penolakan)
                            <div class="col-12">
                                <div class="alert alert-danger py-2 px-3 small mb-0">
                                    <strong>Alasan Penolakan:</strong> {{ $penyewaan->pembayaran->alasan_penolakan }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Informasi Status Operasional -->
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100 shadow-sm border-0" style="border-radius:12px;">
            <div class="ac-card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="ac-card-title fs-6 fw-bold mb-0 text-dark">📦 Status Penyerahan & Pengembalian</h5>
                <x-status-badge :status="$penyewaan->status_penyewaan" />
            </div>
            <div class="ac-card-body p-4">
                <div class="mb-4">
                    <h6 class="fw-bold small text-muted text-uppercase mb-2">Penyerahan / Pengambilan Alat</h6>
                    @if($penyewaan->pengambilan)
                        <div class="p-3 bg-light rounded small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Tanggal Diambil:</span>
                                <strong>{{ \Carbon\Carbon::parse($penyewaan->pengambilan->tanggal_pengambilan)->translatedFormat('d F Y') }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Nama Penerima:</span>
                                <span>{{ $penyewaan->pengambilan->nama_penerima }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Admin Petugas:</span>
                                <span>{{ $penyewaan->pengambilan->admin?->nama_user ?? '-' }}</span>
                            </div>
                        </div>
                    @else
                        <p class="small text-muted mb-0">Alat belum diserahterimakan kepada pelanggan.</p>
                    @endif
                </div>

                <div class="border-top pt-3">
                    <h6 class="fw-bold small text-muted text-uppercase mb-2">Pengembalian Alat & Kondisi</h6>
                    @if($penyewaan->pengembalian)
                        <div class="p-3 bg-light rounded small mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Tanggal Aktual Kembali:</span>
                                <strong>{{ \Carbon\Carbon::parse($penyewaan->pengembalian->tanggal_pengembalian_aktual)->translatedFormat('d F Y') }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Kondisi Alat Saat Kembali:</span>
                                <strong>{{ $penyewaan->pengembalian->kondisi_alat }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Admin Penerima:</span>
                                <span>{{ $penyewaan->pengembalian->admin?->nama_user ?? '-' }}</span>
                            </div>
                        </div>

                        @if($penyewaan->pengembalian->denda)
                            <div class="alert alert-warning py-2 px-3 small mb-0">
                                <div class="d-flex justify-content-between fw-bold mb-1">
                                    <span>⚠️ Denda Dikenakan:</span>
                                    <span class="text-danger font-monospace">Rp {{ number_format((float) $penyewaan->pengembalian->denda->nominal_denda, 0, ',', '.') }}</span>
                                </div>
                                <div><strong>Alasan:</strong> {{ $penyewaan->pengembalian->denda->alasan_denda }}</div>
                            </div>
                        @else
                            <div class="small text-success">✓ Tidak ada denda keterlambatan atau kerusakan.</div>
                        @endif
                    @else
                        <p class="small text-muted mb-0">Alat belum dikembalikan ke inventaris toko.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
