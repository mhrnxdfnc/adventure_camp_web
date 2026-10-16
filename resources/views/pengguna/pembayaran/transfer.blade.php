@extends('layouts.app')

@section('title', 'Instruksi Pembayaran Transfer Bank — ' . $penyewaan->no_transaksi)
@section('page-title', 'Instruksi Pembayaran Transfer Bank')

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
            <p class="text-muted small mb-0">Pesanan telah disetujui Admin. Selesaikan pembayaran menggunakan metode transfer bank resmi.</p>
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
               class="nav-link active font-weight-bold d-flex align-items-center justify-content-center gap-2 py-2"
               style="background-color: var(--ac-primary, #1b4332); color: #fff; border-radius: 8px;">
                <span>🏦</span>
                <span>Transfer Bank</span>
                <span class="badge bg-white text-success small">Dipilih</span>
            </a>
            <a href="{{ route('pengguna.pembayaran.cash', $penyewaan->no_transaksi) }}" 
               class="nav-link text-secondary font-weight-bold d-flex align-items-center justify-content-center gap-2 py-2"
               style="border-radius: 8px;">
                <span>💵</span>
                <span>Tunai / Cash</span>
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

    <!-- Kolom Kanan: Rekening Bank Resmi & Form Unggah Bukti -->
    <div class="col-12 col-lg-5">
        <div class="ac-card h-100">
            <div class="ac-card-header bg-success-subtle border-bottom border-success-subtle">
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size: 1.5rem;">🏦</span>
                    <div>
                        <h3 class="h6 font-weight-bold mb-0 text-success">Instruksi & Rekening Bank</h3>
                        <small class="text-muted">Transfer manual ke rekening resmi toko</small>
                    </div>
                </div>
            </div>

            <div class="ac-card-body">
                <!-- Info Rekening Bank Resmi -->
                @php
                    $bankName = $bankConfig['name'] ?? null;
                    $accountNumber = $bankConfig['account_number'] ?? null;
                    $accountHolder = $bankConfig['account_holder'] ?? null;
                @endphp

                @if(!empty($bankName) && !empty($accountNumber) && !empty($accountHolder))
                    <div class="p-3 rounded-3 mb-4 border" style="background: linear-gradient(135deg, #f8fafc, #edf2f7);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary px-2 py-1">{{ $bankName }}</span>
                            <small class="text-muted">Rekening Resmi</small>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Nomor Rekening:</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="h5 font-weight-bold text-dark mb-0 font-monospace" id="rekening-nomor">{{ $accountNumber }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Atas Nama:</span>
                            <strong class="text-dark" id="rekening-nama">{{ $accountHolder }}</strong>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning border-warning d-flex align-items-start gap-2 mb-4 p-3" style="border-radius: 10px;">
                        <span>⚠️</span>
                        <div>
                            <strong class="d-block mb-1">Informasi Rekening:</strong>
                            <p class="mb-0 small text-dark">
                                Informasi rekening bank resmi belum dikonfigurasi. Silakan minta nomor rekening resmi toko langsung kepada kasir atau Admin.
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Petunjuk Transfer -->
                <h4 class="h6 font-weight-bold text-dark mb-2">Petunjuk Pembayaran Transfer:</h4>
                <ol class="ps-3 text-secondary small mb-4 d-flex flex-column gap-1">
                    <li>Transfer tepat sebesar <strong class="text-success">Rp {{ number_format($penyewaan->total_biaya, 0, ',', '.') }}</strong> ke rekening di atas.</li>
                    <li>Sertakan berita transfer dengan nomor transaksi: <strong class="text-dark">{{ $penyewaan->no_transaksi }}</strong>.</li>
                    <li>Simpan struk / tangkapan layar bukti transfer perbankan Anda.</li>
                    <li>Unggah file bukti transfer melalui formulir di bawah ini.</li>
                    <li>Admin akan memverifikasi mutasi. Status akan berubah menjadi <strong>Lunas</strong> setelah diverifikasi.</li>
                </ol>

                <!-- Status Bukti & Form Unggah -->
                @php
                    $pembayaran = $penyewaan->pembayaran;
                    $statusBayar = $pembayaran?->status_pembayaran ?? 'Belum Dibayar';
                @endphp

                @if($pembayaran && $pembayaran->isLunas())
                    <div class="alert alert-success border-success d-flex align-items-center gap-2 mb-3">
                        <span style="font-size: 1.25rem;">✅</span>
                        <div>
                            <strong>Pembayaran Telah Diverifikasi Lunas</strong>
                            <p class="mb-0 small">Pembayaran Anda telah diverifikasi oleh Admin pada {{ $pembayaran->waktu_konfirmasi?->translatedFormat('d F Y H:i') ?? '-' }} WIB.</p>
                        </div>
                    </div>
                    @if($pembayaran->bukti_pembayaran)
                        <div class="text-center mb-3">
                            <a href="{{ route('pembayaran.bukti', $penyewaan->no_transaksi) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                📄 Lihat Bukti Transfer yang Diverifikasi
                            </a>
                        </div>
                    @endif
                @else
                    @if($pembayaran && $pembayaran->isDitolak())
                        <div class="alert alert-danger border-danger mb-3 p-3" style="border-radius: 8px;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span>❌</span>
                                <strong>Bukti Pembayaran Ditolak Admin</strong>
                            </div>
                            <p class="small mb-2 text-danger">
                                <strong>Alasan Penolakan:</strong> {{ $pembayaran->alasan_penolakan ?? 'Bukti transfer tidak valid atau dana belum masuk ke mutasi rekening.' }}
                            </p>
                            <small class="text-muted d-block">Silakan periksa kembali dan unggah ulang bukti transfer yang benar di bawah ini.</small>
                        </div>
                    @elseif($pembayaran && $pembayaran->isMenungguVerifikasi())
                        <div class="alert alert-info border-info mb-3 p-3" style="border-radius: 8px;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span>⏳</span>
                                <strong>Bukti Transfer Sedang Diverifikasi</strong>
                            </div>
                            <p class="small mb-2">
                                Berkas telah diunggah pada <strong>{{ $pembayaran->waktu_unggah_bukti?->translatedFormat('d F Y H:i') ?? '-' }} WIB</strong> dan sedang menunggu pemeriksaan oleh Admin.
                            </p>
                            <div class="d-flex gap-2">
                                <a href="{{ route('pembayaran.bukti', $penyewaan->no_transaksi) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                    👁️ Lihat Berkas Bukti
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- Form Unggah Bukti Transfer -->
                    <div class="p-3 bg-light rounded-3 border">
                        <h4 class="h6 font-weight-bold text-dark mb-2">
                            {{ ($pembayaran && $pembayaran->isMenungguVerifikasi()) ? 'Unggah Ulang Bukti Transfer' : 'Unggah Bukti Transfer' }}
                        </h4>
                        <p class="text-muted small mb-3">
                            Format yang didukung: <strong>JPG, JPEG, PNG, PDF</strong> (Maksimal <strong>2MB</strong>).
                        </p>

                        <form action="{{ route('pengguna.pembayaran.upload-bukti', $penyewaan->no_transaksi) }}" method="POST" enctype="multipart/form-data" id="form-upload-bukti">
                            @csrf
                            <div class="mb-3">
                                <label for="bukti_transfer" class="form-label small font-weight-bold">Pilih Berkas Bukti Transfer:</label>
                                <input type="file" 
                                       class="form-control @error('bukti_transfer') is-invalid @enderror" 
                                       id="bukti_transfer" 
                                       name="bukti_transfer" 
                                       accept=".jpg,.jpeg,.png,.pdf" 
                                       required>
                                @error('bukti_transfer')
                                    <div class="invalid-feedback small font-weight-bold">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-success w-100 font-weight-bold d-flex align-items-center justify-content-center gap-2" id="btn-submit-bukti">
                                <span>📤</span>
                                <span>Kirim Bukti Pembayaran</span>
                            </button>

                            <div class="text-center mt-2">
                                <small class="text-muted" style="font-size: 0.78rem;">
                                    ⚠️ Pembayaran tidak otomatis lunas. Status akan diverifikasi secara manual oleh Admin.
                                </small>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
