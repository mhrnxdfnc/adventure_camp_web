@extends('layouts.app')

@section('title', 'Instruksi Pembayaran Tunai (Cash) — ' . $penyewaan->no_transaksi)
@section('page-title', 'Instruksi Pembayaran Tunai')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('pengguna.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Instruksi Pembayaran ({{ $penyewaan->no_transaksi }})</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Pembayaran Pesanan</h2>
            <p class="text-muted small mb-0">Pesanan telah disetujui Admin. Selesaikan pembayaran secara tunai di lokasi kasir.</p>
        </div>
        <a href="{{ route('pengguna.dashboard') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Kembali ke Dashboard
        </a>
    </div>
</div>

<!-- Tab Pemilihan / Peralihan Metode Pembayaran -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-body p-2">
        <div class="nav nav-pills nav-fill gap-2" role="tablist">
            <a href="{{ route('pengguna.pembayaran.qris', $penyewaan->no_transaksi) }}" 
               class="nav-link text-secondary font-weight-bold d-flex align-items-center justify-content-center gap-2 py-2"
               style="border-radius: 8px;">
                <span>📱</span>
                <span>QRIS Kasir</span>
            </a>
            <a href="{{ route('pengguna.pembayaran.transfer', $penyewaan->no_transaksi) }}" 
               class="nav-link text-secondary font-weight-bold d-flex align-items-center justify-content-center gap-2 py-2"
               style="border-radius: 8px;">
                <span>🏦</span>
                <span>Transfer Bank</span>
            </a>
            <a href="{{ route('pengguna.pembayaran.cash', $penyewaan->no_transaksi) }}" 
               class="nav-link active font-weight-bold d-flex align-items-center justify-content-center gap-2 py-2"
               style="background-color: var(--ac-primary, #1b4332); color: #fff; border-radius: 8px;">
                <span>💵</span>
                <span>Tunai / Cash</span>
                <span class="badge bg-white text-success small">Dipilih</span>
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Rincian Pesanan & Total Tagihan -->
    <div class="col-12 col-lg-7">
        <div class="ac-card h-100">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase" style="letter-spacing: 0.5px;">Nomor Transaksi</span>
                    <h3 class="h5 font-weight-bold mb-0 text-success" id="transaksi-no">{{ $penyewaan->no_transaksi }}</h3>
                </div>
                <div class="text-end">
                    <span class="text-muted small d-block mb-1">Status Pesanan:</span>
                    <x-status-badge :status="$penyewaan->status_penyewaan" />
                </div>
            </div>

            <div class="ac-card-body">
                <!-- Periode Sewa -->
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-sm-7">
                            <span class="text-muted small d-block">Periode Sewa Camping</span>
                            <strong class="text-dark">
                                {{ \Carbon\Carbon::parse($penyewaan->tanggal_sewa)->isoFormat('D MMMM Y') }}
                                &mdash;
                                {{ \Carbon\Carbon::parse($penyewaan->tanggal_kembali)->isoFormat('D MMMM Y') }}
                            </strong>
                        </div>
                        <div class="col-12 col-sm-5 text-sm-end">
                            <span class="badge bg-secondary-subtle text-dark border px-3 py-2">
                                Durasi: <strong>{{ $penyewaan->durasi_hari }} Hari</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Rincian Pesanan (Daftar Alat) -->
                <div class="mb-4">
                    <h4 class="h6 font-weight-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <span>📦</span>
                        <span>Rincian Alat yang Disewa</span>
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle table-hover mb-0" id="table-rincian-pesanan">
                            <thead class="table-light small">
                                <tr>
                                    <th>Alat Camping</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Tarif / Hari</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($penyewaan->detailPenyewaan as $detail)
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold text-dark">{{ $detail->alat->nama_alat ?? $detail->kode_alat }}</div>
                                            <small class="text-muted">{{ $detail->alat->kategori ?? 'Peralatan Camping' }} ({{ $detail->kode_alat }})</small>
                                        </td>
                                        <td class="text-center font-weight-bold">{{ $detail->jumlah }} unit</td>
                                        <td class="text-end text-muted small">Rp {{ number_format($detail->tarif_sewa, 0, ',', '.') }}</td>
                                        <td class="text-end font-weight-bold text-dark">Rp {{ number_format($detail->subtotal_biaya, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">Tidak ada rincian alat pada transaksi ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pemisahan Status Pesanan dan Status Pembayaran -->
                <div class="border rounded-3 p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="text-muted small">Status Pesanan:</span>
                        <x-status-badge :status="$penyewaan->status_penyewaan" />
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Status Pembayaran:</span>
                        <x-status-badge :status="$penyewaan->status_pembayaran" id="status-pembayaran-badge" />
                    </div>

                    <!-- Total Tagihan -->
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <div>
                            <span class="text-uppercase small text-muted font-weight-bold d-block" style="letter-spacing: 0.5px;">Total Tagihan</span>
                            <small class="text-muted">(Sesuai durasi & rincian alat)</small>
                        </div>
                        <div class="text-end">
                            <span class="h4 font-weight-bold text-success mb-0 d-block" id="total-tagihan">
                                Rp {{ number_format($penyewaan->total_biaya, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Instruksi Pembayaran Tunai Kasir -->
    <div class="col-12 col-lg-5">
        <div class="ac-card h-100">
            <div class="ac-card-header bg-success-subtle border-bottom border-success-subtle">
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size: 1.5rem;">💵</span>
                    <div>
                        <h3 class="h6 font-weight-bold mb-0 text-success">Instruksi Pembayaran Tunai</h3>
                        <small class="text-muted">Pembayaran langsung di meja kasir</small>
                    </div>
                </div>
            </div>

            <div class="ac-card-body">
                <!-- Info Peringatan Kasir -->
                <div class="alert alert-info border-info d-flex align-items-start gap-2 mb-4 p-3" style="border-radius: 10px;">
                    <span style="font-size: 1.25rem;">ℹ️</span>
                    <div>
                        <strong class="d-block mb-1 text-dark">Pembayaran Tunai di Lokasi:</strong>
                        <p class="mb-0 text-dark small" id="cash-notice-message">
                            Pembayaran tunai dilakukan secara langsung kepada kasir atau Admin di outlet/basecamp <strong>Adventure Camp Rental</strong> saat pengambilan alat camping.
                        </p>
                    </div>
                </div>

                <!-- Langkah Pembayaran Cash -->
                <h4 class="h6 font-weight-bold text-dark mb-3">Langkah Pembayaran Tunai:</h4>
                <ol class="ps-3 text-secondary small mb-4 d-flex flex-column gap-2">
                    <li>Kunjungi basecamp Adventure Camp Rental pada jadwal sewa Anda.</li>
                    <li>Tunjukkan nomor transaksi Anda kepada kasir: <strong class="text-dark">{{ $penyewaan->no_transaksi }}</strong>.</li>
                    <li>Siapkan uang tunai pas sebesar <strong class="text-success">Rp {{ number_format($penyewaan->total_biaya, 0, ',', '.') }}</strong>.</li>
                    <li>Serahkan pembayaran tunai kepada kasir / Admin operasional.</li>
                    <li>Admin akan menghitung uang yang diterima, mencatat konfirmasi ke sistem, dan status pembayaran akan langsung berubah menjadi <strong>Lunas</strong>.</li>
                </ol>

                <!-- Status Pembayaran -->
                @php
                    $pembayaran = $penyewaan->pembayaran;
                @endphp

                <div class="p-3 bg-light rounded-3 border text-center">
                    <span class="d-block text-muted small mb-1">Status Pembayaran Saat Ini:</span>
                    <div class="mb-2">
                        <x-status-badge :status="$penyewaan->status_pembayaran" />
                    </div>
                    @if($pembayaran && $pembayaran->isLunas())
                        <div class="alert alert-success border-success py-2 px-3 small mb-0 mt-2">
                            ✅ Pembayaran tunai telah diterima dan dikonfirmasi Lunas oleh Admin ({{ $pembayaran->waktu_konfirmasi?->translatedFormat('d F Y H:i') ?? '-' }}).
                        </div>
                    @else
                        <small class="text-muted d-block" style="font-size: 0.8rem;">
                            Status pembayaran tetap <em>{{ $penyewaan->status_pembayaran }}</em> hingga kasir menerima uang dan mengonfirmasi pembayaran di sistem.
                        </small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
