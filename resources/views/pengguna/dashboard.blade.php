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
                Pantau status pengambilan alat, batas pengembalian, rincian denda, dan konfirmasi pembayaran dengan mudah.
            </p>
        </div>
        <div class="col-12 col-md-4 mt-3 mt-md-0 text-md-end">
            <a href="{{ route('pengguna.katalog.index') }}" class="btn btn-light text-success font-weight-bold">
                Buka Katalog Alat &rarr;
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Stat 1: Pesanan Aktif -->
    <div class="col-12 col-sm-6">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Pesanan Aktif Saya</span>
                <div class="ac-stat-icon-wrap">
                    🏕️
                </div>
            </div>
            <div class="ac-stat-value">{{ $pesananAktifCount }}</div>
            <div class="ac-stat-sub">Transaksi sedang berlangsung / berjalan</div>
        </div>
    </div>

    <!-- Stat 2: Tagihan Menunggu -->
    <div class="col-12 col-sm-6">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Tagihan Menunggu Pembayaran</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #b45309;">
                    💰
                </div>
            </div>
            <div class="ac-stat-value">Rp {{ number_format($tagihanMenunggu, 0, ',', '.') }}</div>
            <div class="ac-stat-sub">Total tagihan sewa belum lunas</div>
        </div>
    </div>
</div>

<!-- Riwayat & Status Pesanan Pengguna -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="ac-card-title">Riwayat & Status Pesanan Saya</h3>
    <small class="text-muted">Menampilkan seluruh pesanan aktif dan riwayat transaksi lama Anda</small>
</div>

<div class="row g-3">
    @forelse($pesananList as $trx)
        <div class="col-12 col-lg-6">
            <div class="ac-card h-100 d-flex flex-column">
                <div class="ac-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-muted small">KODE TRANSAKSI</span>
                        <h4 class="mb-0 font-weight-bold font-monospace" style="font-size:1.1rem; color:var(--ac-primary);">
                            {{ $trx->no_transaksi }}
                        </h4>
                    </div>
                    <x-status-badge :status="$trx->status_penyewaan" />
                </div>
                
                <div class="ac-card-body flex-grow-1 p-3">
                    <div class="mb-3 pb-2 border-bottom">
                        <small class="text-muted d-block">Jadwal Sewa</small>
                        <strong>{{ \Carbon\Carbon::parse($trx->tanggal_sewa)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($trx->tanggal_kembali)->format('d M Y') }}</strong>
                        <span class="text-muted small">({{ $trx->durasi_hari }} hari)</span>
                    </div>

                    <!-- Rincian Alat Disewa -->
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Rincian Alat Outdoor</small>
                        <div class="p-2 bg-light rounded small">
                            @foreach($trx->detailPenyewaan as $detail)
                                <div class="d-flex justify-content-between py-1 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                    <span>
                                        • <strong>{{ $detail->alat->nama_alat ?? $detail->kode_alat }}</strong> &times; {{ $detail->jumlah }}
                                        @if($detail->jumlah_diserahkan > 0 && $detail->jumlah_diserahkan != $detail->jumlah)
                                            <span class="text-muted">({{ $detail->jumlah_diserahkan }} diserahkan)</span>
                                        @endif
                                    </span>
                                    <span class="font-monospace text-dark">
                                        Rp {{ number_format($detail->subtotal_biaya, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Informasi Operasional: Status Pengambilan Alat -->
                    <div class="mb-2 p-2 rounded small {{ $trx->pengambilan ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : ($trx->status_penyewaan === 'Siap Diambil' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border') }}">
                        <div class="d-flex align-items-center gap-2">
                            <span>📦</span>
                            <div>
                                <strong>Status Pengambilan:</strong>
                                @if($trx->pengambilan)
                                    <span>Telah diambil pada <strong>{{ \Carbon\Carbon::parse($trx->pengambilan->tanggal_pengambilan)->format('d/m/Y') }}</strong> (Penerima: {{ $trx->pengambilan->nama_penerima }})</span>
                                @elseif($trx->status_penyewaan === 'Siap Diambil')
                                    <span>Alat <strong>Siap Diambil</strong> di store. Silakan kunjungi kasir operasional.</span>
                                @else
                                    <span>Menunggu konfirmasi pembayaran & persetujuan admin.</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Operasional: Status Pengembalian Alat -->
                    <div class="mb-3 p-2 rounded small {{ $trx->pengembalian ? 'bg-info-subtle text-info-emphasis border border-info-subtle' : ($trx->isSedangDisewa() ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-muted border') }}">
                        <div class="d-flex align-items-center gap-2">
                            <span>🔄</span>
                            <div>
                                <strong>Status Pengembalian:</strong>
                                @if($trx->pengembalian)
                                    <span>Dikembalikan pada <strong>{{ \Carbon\Carbon::parse($trx->pengembalian->tanggal_pengembalian_aktual)->format('d/m/Y') }}</strong></span>
                                    <small class="d-block text-muted">Kondisi: {{ $trx->pengembalian->kondisi_alat }}</small>
                                @elseif($trx->isSedangDisewa())
                                    @php
                                        $tglKembali = \Carbon\Carbon::parse($trx->tanggal_kembali)->startOfDay();
                                        $hariIni = \Carbon\Carbon::today();
                                        $terlambat = $hariIni->gt($tglKembali) ? $tglKembali->diffInDays($hariIni) : 0;
                                    @endphp
                                    @if($terlambat > 0)
                                        <span class="text-danger fw-bold">⚠️ Melewati batas waktu (Terlambat {{ $terlambat }} hari)! Harap segera kembalikan ke store.</span>
                                    @else
                                        <span>Batas pengembalian: <strong>{{ $tglKembali->format('d/m/Y') }}</strong></span>
                                    @endif
                                @else
                                    <span>Belum dalam masa pengembalian.</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Rincian & Alasan Denda (Jika Ada) -->
                    @if($trx->pengembalian?->denda)
                        <div class="p-3 bg-danger-subtle border border-danger-subtle rounded mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-danger">⚠️ Denda Keterlambatan / Kerusakan</span>
                                <strong class="text-danger font-monospace fs-6">
                                    Rp {{ number_format($trx->pengembalian->denda->nominal_denda, 0, ',', '.') }}
                                </strong>
                            </div>
                            <div class="small text-danger-emphasis">
                                <strong>Rincian & Alasan Denda:</strong> {{ $trx->pengembalian->denda->alasan_denda }}
                            </div>
                        </div>
                    @elseif($trx->isSelesai())
                        <div class="p-2 bg-success-subtle border border-success-subtle rounded text-success small mb-3">
                            ✓ <strong>Bebas Denda</strong> &bull; Seluruh alat telah dikembalikan tepat waktu dan dalam kondisi baik.
                        </div>
                    @endif

                    <!-- Informasi Pembayaran & Tagihan -->
                    <div class="p-3 bg-light rounded-3 mb-2 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="font-weight-bold text-dark">Total Biaya Sewa:</span>
                            <strong class="text-dark font-monospace" style="font-size:1.1rem;">
                                Rp {{ number_format($trx->total_biaya, 0, ',', '.') }}
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="text-muted">Status Pembayaran:</span>
                            <x-status-badge :status="$trx->status_pembayaran" />
                        </div>
                    </div>
                </div>

                <!-- Footer Aksi -->
                <div class="ac-card-footer bg-light p-3 border-top mt-auto">
                    @if(! $trx->pembayaran || ! $trx->pembayaran->isLunas())
                        @if($trx->isSiapDibayar())
                            <a href="{{ route('pengguna.pembayaran.qris', $trx->no_transaksi) }}" 
                               class="btn btn-success w-100 font-weight-bold d-flex align-items-center justify-content-center gap-2" 
                               id="btn-lihat-instruksi-bayar-{{ $trx->no_transaksi }}">
                                <span>💳</span>
                                <span>Lihat Instruksi & Konfirmasi Bayar &rarr;</span>
                            </a>
                        @else
                            <button class="btn btn-outline-secondary w-100" disabled>
                                Menunggu Persetujuan Admin
                            </button>
                        @endif
                    @else
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="text-success fw-bold">✓ Pembayaran Terverifikasi Lunas</span>
                            <span class="text-muted font-monospace">{{ $trx->pembayaran->no_pembayaran }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-empty-state 
                title="Belum Ada Pesanan" 
                message="Anda belum memiliki transaksi penyewaan alat outdoor. Kunjungi katalog untuk mulai memesan!" 
                icon="⛺"
            />
        </div>
    @endforelse
</div>
@endsection
