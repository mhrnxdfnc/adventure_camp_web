@extends('layouts.app')

@section('title', 'Pemeriksaan Pembayaran — ' . $penyewaan->no_transaksi)
@section('page-title', 'Detail Pemeriksaan Pembayaran')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.pembayaran.index') }}" class="text-decoration-none">Verifikasi Pembayaran</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $penyewaan->no_transaksi }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Pemeriksaan Pembayaran #{{ $penyewaan->no_transaksi }}</h2>
            <p class="text-muted small mb-0">Periksa kecocokan data pesanan, mutasi perbankan / kasir, dan bukti transfer.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.pembayaran.index') }}" class="btn btn-sm btn-outline-secondary">
                &larr; Kembali ke Daftar
            </a>
        </div>
    </div>
</div>

@php
    $pembayaran = $penyewaan->pembayaran;
    $metode = $pembayaran?->metode_pembayaran ?? 'Transfer Bank';
    $statusBayar = $pembayaran?->status_pembayaran ?? 'Belum Dibayar';
    $hasBukti = !empty($pembayaran?->bukti_pembayaran);
@endphp

<div class="row g-4">
    <!-- Kolom Kiri: Rincian Transaksi & Alat Camping -->
    <div class="col-12 col-lg-7">
        <div class="ac-card mb-4">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase" style="letter-spacing: 0.5px;">Nomor Transaksi</span>
                    <h3 class="h5 font-weight-bold mb-0 text-success font-monospace" id="transaksi-no">{{ $penyewaan->no_transaksi }}</h3>
                </div>
                <div class="text-end">
                    <span class="text-muted small d-block mb-1">Status Pesanan:</span>
                    <x-status-badge :status="$penyewaan->status_penyewaan" />
                </div>
            </div>

            <div class="ac-card-body">
                <!-- Data Pelanggan -->
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <h4 class="h6 font-weight-bold text-dark mb-2">Informasi Penyewa / Pelanggan:</h4>
                    <div class="row g-2 small">
                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">Nama Lengkap:</span>
                            <strong class="text-dark">{{ $penyewaan->pelanggan->nama_pelanggan ?? '-' }}</strong>
                        </div>
                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">Nomor Telepon:</span>
                            <strong class="text-dark">{{ $penyewaan->pelanggan->nomor_telepon ?? '-' }}</strong>
                        </div>
                        <div class="col-12">
                            <span class="text-muted d-block">Alamat Domisili:</span>
                            <span class="text-dark">{{ $penyewaan->pelanggan->alamat ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Periode Sewa -->
                <div class="p-3 bg-light rounded-3 mb-4 border">
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

                <!-- Rincian Alat yang Disewa -->
                <h4 class="h6 font-weight-bold text-dark mb-3">Rincian Alat Camping:</h4>
                <div class="table-responsive mb-4">
                    <table class="table table-sm align-middle table-hover mb-0">
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
                                        <small class="text-muted">{{ $detail->alat->kategori ?? 'Alat Camping' }} ({{ $detail->kode_alat }})</small>
                                    </td>
                                    <td class="text-center font-weight-bold">{{ $detail->jumlah }} unit</td>
                                    <td class="text-end text-muted small">Rp {{ number_format($detail->tarif_sewa, 0, ',', '.') }}</td>
                                    <td class="text-end font-weight-bold text-dark font-monospace">Rp {{ number_format($detail->subtotal_biaya, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">Tidak ada rincian alat pada transaksi ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Total Tagihan -->
                <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small text-muted font-weight-bold d-block">Total Tagihan Sewa</span>
                        <small class="text-muted">(Dihitung dari seluruh item & durasi hari)</small>
                    </div>
                    <div class="text-end">
                        <span class="h4 font-weight-bold text-success mb-0 font-monospace" id="total-tagihan">
                            Rp {{ number_format($penyewaan->total_biaya, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Status Pembayaran, Bukti Transfer & Tindakan Admin -->
    <div class="col-12 col-lg-5">
        <div class="ac-card mb-4">
            <div class="ac-card-header bg-success-subtle border-bottom border-success-subtle">
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size: 1.5rem;">💳</span>
                    <div>
                        <h3 class="h6 font-weight-bold mb-0 text-success">Status & Bukti Pembayaran</h3>
                        <small class="text-muted">Pemeriksaan mutasi dan konfirmasi kasir</small>
                    </div>
                </div>
            </div>

            <div class="ac-card-body">
                <!-- Info Status Saat Ini -->
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="text-muted small">Status Pembayaran:</span>
                        <x-status-badge :status="$statusBayar" />
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="text-muted small">Metode Pembayaran:</span>
                        <strong class="text-dark">{{ $metode }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="text-muted small">Nomor Pembayaran:</span>
                        <span class="font-monospace text-dark">{{ $pembayaran?->no_pembayaran ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Nominal Diterima:</span>
                        <strong class="text-success font-monospace">Rp {{ number_format($pembayaran?->jumlah_pembayaran ?? 0, 0, ',', '.') }}</strong>
                    </div>
                </div>

                <!-- Informasi Riwayat Verifikasi -->
                @if($pembayaran && $pembayaran->isLunas())
                    <div class="alert alert-success border-success mb-3 p-3" style="border-radius: 8px;">
                        <strong class="d-block mb-1">✓ Pembayaran Diverifikasi Lunas</strong>
                        <div class="small">
                            <div><strong>Diverifikasi Oleh:</strong> {{ $pembayaran->admin->nama_user ?? 'Admin ID ' . $pembayaran->id_admin }}</div>
                            <div><strong>Waktu Konfirmasi:</strong> {{ $pembayaran->waktu_konfirmasi?->translatedFormat('d F Y H:i:s') ?? '-' }} WIB</div>
                        </div>
                    </div>
                @elseif($pembayaran && $pembayaran->isDitolak())
                    <div class="alert alert-danger border-danger mb-3 p-3" style="border-radius: 8px;">
                        <strong class="d-block mb-1">✕ Bukti Pembayaran Ditolak</strong>
                        <div class="small">
                            <div><strong>Alasan Penolakan:</strong> {{ $pembayaran->alasan_penolakan }}</div>
                            <div><strong>Ditolak Oleh:</strong> {{ $pembayaran->admin->nama_user ?? 'Admin' }} ({{ $pembayaran->waktu_konfirmasi?->translatedFormat('d F Y H:i:s') ?? '-' }} WIB)</div>
                        </div>
                    </div>
                @endif

                <!-- Berkas Bukti Transfer -->
                <div class="mb-4">
                    <h4 class="h6 font-weight-bold text-dark mb-2">Berkas Bukti Transfer:</h4>
                    @if($hasBukti)
                        <div class="p-3 bg-light rounded-3 border text-center">
                            <div class="mb-2">
                                <span style="font-size: 2.5rem;">📄</span>
                            </div>
                            <span class="small text-muted d-block mb-2">
                                Diunggah pada: {{ $pembayaran->waktu_unggah_bukti?->translatedFormat('d F Y H:i') ?? '-' }} WIB
                            </span>
                            <a href="{{ route('pembayaran.bukti', $penyewaan->no_transaksi) }}" target="_blank" class="btn btn-sm btn-primary">
                                👁️ Buka Berkas Bukti Transfer Asli
                            </a>
                        </div>
                    @else
                        <div class="alert alert-secondary py-3 text-center mb-0 small">
                            @if($metode === 'QRIS')
                                <span>📱 Pembayaran via <strong>QRIS Kasir</strong>. Periksa riwayat mutasi masuk di scanner / aplikasi kasir toko.</span>
                            @elseif($metode === 'Cash')
                                <span>💵 Pembayaran via <strong>Tunai / Cash</strong>. Konfirmasi setelah menerima uang fisik secara pas di kasir.</span>
                            @else
                                <span class="fst-italic">Pelanggan belum mengunggah berkas bukti transfer.</span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Tindakan Admin -->
                @if(! $pembayaran || ! $pembayaran->isLunas())
                    <div class="p-3 bg-light rounded-3 border">
                        <h4 class="h6 font-weight-bold text-dark mb-3">Tindakan Admin:</h4>
                        
                        <!-- Form Konfirmasi Lunas -->
                        <form action="{{ route('admin.pembayaran.konfirmasi', $penyewaan->no_transaksi) }}" method="POST" class="mb-3">
                            @csrf
                            <input type="hidden" name="metode_pembayaran" value="{{ $metode }}">
                            <div class="mb-2">
                                <label class="form-label small font-weight-bold">Nominal yang Diterima (Rp):</label>
                                <input type="number" 
                                       name="jumlah_diterima" 
                                       class="form-control form-control-sm font-monospace" 
                                       value="{{ (int) $penyewaan->total_biaya }}" 
                                       min="{{ (int) $penyewaan->total_biaya }}"
                                       required>
                                <small class="text-muted" style="font-size: 0.72rem;">* Harus melunasi seluruh tagihan.</small>
                            </div>
                            <button type="submit" class="btn btn-success w-100 font-weight-bold btn-sm py-2" onclick="return confirm('Apakah Anda yakin ingin mengonfirmasi pembayaran transaksi ini sebagai LUNAS?')">
                                ✓ Konfirmasi Pembayaran Lunas
                            </button>
                        </form>

                        <!-- Form Tolak Pembayaran -->
                        <form action="{{ route('admin.pembayaran.tolak', $penyewaan->no_transaksi) }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small font-weight-bold text-danger">Alasan Penolakan (Wajib):</label>
                                <textarea name="alasan_penolakan" class="form-control form-control-sm" rows="2" placeholder="Wajib diisi alasan penolakan..." required minlength="5" maxlength="500"></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-danger w-100 btn-sm" onclick="return confirm('Apakah Anda yakin ingin MENOLAK pembayaran ini?')">
                                ✕ Tolak Bukti Pembayaran
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
