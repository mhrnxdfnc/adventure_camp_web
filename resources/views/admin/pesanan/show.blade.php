@extends('layouts.app')

@section('title', 'Detail Pesanan ' . $penyewaan->no_transaksi)
@section('page-title', 'Detail Pesanan')

@section('content')
<div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $penyewaan->no_transaksi }}</li>
            </ol>
        </nav>
        <h2 class="h4 mb-0 fw-bold d-flex align-items-center gap-2">
            <span>{{ $penyewaan->no_transaksi }}</span>
            @if($penyewaan->isOnsite())
                <span class="badge bg-warning-subtle text-dark border border-warning fs-6">
                    📟 On-site Kasir
                </span>
            @else
                <span class="badge bg-info-subtle text-info border border-info fs-6">
                    🌐 Online
                </span>
            @endif
        </h2>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.pesanan.nota', $penyewaan->no_transaksi) }}" target="_blank" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1 shadow-sm" id="btn-cetak-nota-admin">
            <span>🖨️</span>
            <span>Cetak Nota Resmi</span>
        </a>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            &larr; Kembali ke Dashboard
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Informasi Pemesan & Status -->
    <div class="col-12 col-lg-7">
        <div class="ac-card h-100">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <h3 class="ac-card-title mb-0">Informasi Pemesanan & Pelanggan</h3>
                <div class="d-flex gap-2 align-items-center">
                    <x-status-badge :status="$penyewaan->status_penyewaan" />
                    <span class="badge bg-light text-dark border">
                        Status Bayar: <strong>{{ $penyewaan->status_pembayaran }}</strong>
                    </span>
                </div>
            </div>
            <div class="ac-card-body">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <small class="text-muted d-block">Nama Pelanggan</small>
                        <span class="fw-bold">{{ $penyewaan->pelanggan?->nama_pelanggan ?? 'Pelanggan #' . $penyewaan->id_pelanggan }}</span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <small class="text-muted d-block">Nomor Telepon</small>
                        <span class="font-monospace">{{ $penyewaan->pelanggan?->nomor_telepon ?? '-' }}</span>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Alamat Lengkap</small>
                        <span>{{ $penyewaan->pelanggan?->alamat ?? 'Alamat tidak dicantumkan' }}</span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <small class="text-muted d-block">Jadwal Periode Sewa</small>
                        <span class="fw-semibold">
                            {{ \Carbon\Carbon::parse($penyewaan->tanggal_sewa)->format('d M Y') }} &rarr; {{ \Carbon\Carbon::parse($penyewaan->tanggal_kembali)->format('d M Y') }}
                        </span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <small class="text-muted d-block">Durasi Sewa</small>
                        <span class="badge bg-secondary-subtle text-dark border">{{ $penyewaan->durasi_hari }} Hari</span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <small class="text-muted d-block">Sumber Pemesanan</small>
                        @if($penyewaan->isOnsite())
                            <span class="text-dark fw-bold">📟 On-site (Tablet Kasir)</span>
                        @else
                            <span class="text-primary fw-bold">🌐 Online (Portal Web)</span>
                        @endif
                    </div>
                    <div class="col-12 col-sm-6">
                        <small class="text-muted d-block">Metode Pembayaran Pilihan</small>
                        <span class="badge bg-primary-subtle text-primary border fw-bold">{{ $metodePembayaran }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan Finansial & Instruksi Pembayaran -->
    <div class="col-12 col-lg-5">
        <div class="ac-card h-100">
            <div class="ac-card-header">
                <h3 class="ac-card-title mb-0">Total Tagihan & Instruksi Pembayaran</h3>
            </div>
            <div class="ac-card-body d-flex flex-column justify-content-between">
                <div class="mb-3">
                    <small class="text-muted d-block">Total Tagihan Transaksi</small>
                    <div class="h3 fw-bold text-success mb-2">
                        Rp {{ number_format($penyewaan->total_biaya, 0, ',', '.') }}
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-light text-dark border">
                            Order: <strong>{{ $penyewaan->status_penyewaan }}</strong>
                        </span>
                        <span class="badge bg-light text-dark border">
                            Bayar: <strong>{{ $penyewaan->status_pembayaran }}</strong>
                        </span>
                    </div>
                </div>

                <!-- Box Instruksi Pembayaran -->
                <div class="p-3 bg-light rounded border">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fs-5">💳</span>
                        <strong class="text-dark">Instruksi Pembayaran ({{ $metodePembayaran }})</strong>
                    </div>
                    <p class="small text-muted mb-0" style="line-height: 1.5;">
                        {{ $instruksiPembayaran }}
                    </p>
                </div>

                @if($penyewaan->status_penyewaan === 'Ditolak' && $penyewaan->pembayaran?->alasan_penolakan)
                    <div class="p-3 mt-3 bg-danger-subtle text-danger border border-danger rounded">
                        <strong>Alasan Penolakan Pesanan:</strong>
                        <p class="small mb-0 mt-1">{{ $penyewaan->pembayaran->alasan_penolakan }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Pemeriksaan Ketersediaan Stok Seluruh Alat -->
<div class="ac-card mb-4">
    <div class="ac-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="ac-card-title mb-0">Rincian Barang & Ketersediaan Periode</h3>
            <small class="text-muted">Pemeriksaan ketersediaan stok fisik toko terhadap pesanan aktif lain pada periode yang diminta</small>
        </div>
        <div>
            @if($allAvailable)
                <span class="badge bg-success-subtle text-success border border-success px-3 py-2">
                    ✓ Seluruh Alat Tersedia untuk Periode Ini
                </span>
            @else
                <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-2">
                    ⚠ Stok Alat Tidak Mencukupi untuk Periode Ini
                </span>
            @endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode Barang</th>
                    <th>Nama / Jenis Alat</th>
                    <th>Kondisi Fisik</th>
                    <th class="text-center">Stok Toko</th>
                    <th class="text-center">Stok Tersedia Periode</th>
                    <th class="text-center">Jumlah Diminta</th>
                    <th class="text-center">Status Ketersediaan</th>
                    <th class="text-end">Tarif Harian</th>
                    <th class="text-end">Subtotal ({{ $penyewaan->durasi_hari }} Hari)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($penyewaan->detailPenyewaan as $detail)
                    @php
                        $avail = $itemAvailability[$detail->kode_alat] ?? [
                            'stok_fisik' => 0,
                            'kondisi' => 'Baik',
                            'stok_tersedia_periode' => 0,
                            'jumlah_diminta' => $detail->jumlah,
                            'is_available' => false,
                        ];
                    @endphp
                    <tr>
                        <td class="font-monospace fw-bold">{{ $detail->kode_alat }}</td>
                        <td>
                            <div class="fw-bold">{{ $detail->alat?->nama_alat ?? 'Alat ' . $detail->kode_alat }}</div>
                            <small class="text-muted">{{ $detail->alat?->kategori ?? 'Alat Camping' }}</small>
                        </td>
                        <td>
                            @if(($detail->alat?->kondisi ?? 'Baik') === 'Baik')
                                <span class="badge bg-success-subtle text-success border border-success">Baik</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger">{{ $detail->alat?->kondisi }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $avail['stok_fisik'] }} Unit</td>
                        <td class="text-center">
                            <span class="fw-bold {{ $avail['stok_tersedia_periode'] >= $detail->jumlah ? 'text-success' : 'text-danger' }}">
                                {{ $avail['stok_tersedia_periode'] }} Unit
                            </span>
                        </td>
                        <td class="text-center fw-bold">{{ $detail->jumlah }} Unit</td>
                        <td class="text-center">
                            @if($avail['is_available'])
                                <span class="badge bg-success-subtle text-success border border-success">
                                    ✓ Mencukupi (Tersedia {{ $avail['stok_tersedia_periode'] }})
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger">
                                    ⚠ Kurang (Sisa {{ $avail['stok_tersedia_periode'] }})
                                </span>
                            @endif
                        </td>
                        <td class="text-end font-monospace">Rp {{ number_format($detail->tarif_sewa, 0, ',', '.') }} / hr</td>
                        <td class="text-end font-monospace fw-bold">Rp {{ number_format($detail->subtotal_biaya, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="8" class="text-end fw-bold">Total Tagihan Transaksi:</th>
                    <th class="text-end font-monospace fw-bold text-success fs-5">
                        Rp {{ number_format($penyewaan->total_biaya, 0, ',', '.') }}
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Alert Ketersediaan -->
    <div class="p-3 border-top">
        @if($allAvailable)
            <div class="alert alert-success d-flex align-items-center mb-0 gap-2">
                <span class="fs-4">✓</span>
                <div>
                    <strong>Stok Aman untuk Periode Sewa:</strong> Seluruh alat yang diminta tersedia dan dalam kondisi baik untuk tanggal {{ \Carbon\Carbon::parse($penyewaan->tanggal_sewa)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($penyewaan->tanggal_kembali)->format('d M Y') }}.
                </div>
            </div>
        @else
            <div class="alert alert-danger d-flex align-items-center mb-0 gap-2">
                <span class="fs-4">⚠</span>
                <div>
                    <strong>Peringatan Ketersediaan Stok:</strong> Satu atau lebih alat yang diajukan tidak mencukupi untuk periode sewa yang diminta. Pesanan ini tidak boleh disetujui karena akan menyebabkan pemesanan melebihi stok yang ada.
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Panel Aksi Operasional Admin -->
<div class="ac-card">
    <div class="ac-card-header">
        <h3 class="ac-card-title mb-0">Aksi Operasional Admin</h3>
    </div>
    <div class="ac-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            @if($penyewaan->status_penyewaan === 'Menunggu Konfirmasi')
                <span class="text-muted small">
                    Silakan periksa ketersediaan alat di atas sebelum menentukan persetujuan atau penolakan pesanan.
                </span>
            @else
                <span class="badge bg-secondary-subtle text-dark border">
                    Pesanan telah diproses dengan status: {{ $penyewaan->status_penyewaan }}
                </span>
                @if($penyewaan->isSiapDiambil())
                    <span class="badge bg-success-subtle text-success border border-success ms-1">
                        ✓ Siap Diambil / Diserahkan
                    </span>
                @endif
            @endif
        </div>

        @if($penyewaan->status_penyewaan === 'Menunggu Konfirmasi')
            <div class="d-flex gap-2 align-items-center">
                <!-- Form Setujui dengan Proteksi Double-Click -->
                <form action="{{ route('admin.pesanan.setujui', $penyewaan->no_transaksi) }}" method="POST" id="formSetujuiShow" onsubmit="if (this.submitted) { return false; } this.submitted = true; const btn = this.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.innerHTML = '<span class=\'spinner-border spinner-border-sm me-1\'></span> Memproses...'; } return true;">
                    @csrf
                    <button type="submit" id="btnSetujuiShow" class="btn btn-success" {{ ! $allAvailable ? 'disabled title="Stok tidak mencukupi untuk periode ini"' : '' }}>
                        ✓ Setujui Pesanan
                    </button>
                </form>

                <!-- Tombol Tolak membuka Modal -->
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalTolakPesananShow">
                    ✕ Tolak Pesanan
                </button>
            </div>
        @elseif($penyewaan->isSiapDiambil())
            <div>
                <a href="{{ route('admin.operasional.pengambilan', ['search' => $penyewaan->no_transaksi]) }}" class="btn btn-success d-inline-flex align-items-center gap-2 py-2 px-3 fw-bold">
                    <span>📦</span>
                    <span>Lanjut ke Penyerahan Alat &rarr;</span>
                </a>
            </div>

            <!-- Modal Tolak Pesanan -->
            <div class="modal fade" id="modalTolakPesananShow" tabindex="-1" aria-labelledby="modalTolakPesananShowLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('admin.pesanan.tolak', $penyewaan->no_transaksi) }}" method="POST" id="formTolakShow" onsubmit="if (this.submitted) { return false; } this.submitted = true; const btn = this.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.innerHTML = '<span class=\'spinner-border spinner-border-sm me-1\'></span> Memproses...'; } return true;">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title h6 fw-bold" id="modalTolakPesananShowLabel">
                                    Tolak Pengajuan Pesanan {{ $penyewaan->no_transaksi }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body text-start">
                                <p class="small text-muted mb-3">
                                    Harap berikan alasan penolakan yang jelas. Alasan ini akan tercatat dalam audit trail sistem dan catatan transaksi pelanggan.
                                </p>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                                    <textarea name="alasan_penolakan" class="form-control" rows="3" required minlength="5" maxlength="500" placeholder="Contoh: Stok alat tidak mencukupi untuk periode tanggal yang diminta..."></textarea>
                                    <small class="text-muted">Minimal 5 karakter, maksimal 500 karakter.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" id="btnTolakShow" class="btn btn-sm btn-danger">Konfirmasi Tolak Pesanan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
