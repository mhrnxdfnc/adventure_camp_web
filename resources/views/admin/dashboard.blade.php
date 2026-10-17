@extends('layouts.app')

@section('title', 'Dashboard Operasional Admin')
@section('page-title', 'Dashboard Admin')

@section('content')
<!-- Banner Instruksi Pembayaran Setelah Persetujuan Pesanan -->
@if (session('instruksi_pembayaran'))
    <div class="alert alert-info alert-dismissible fade show d-flex align-items-start gap-2 mb-4 shadow-sm border-0" role="alert" style="border-radius:10px;">
        <span class="fs-4">💳</span>
        <div class="flex-grow-1">
            <h6 class="alert-heading fw-bold mb-1">
                Instruksi Pembayaran (Metode: {{ session('metode_pembayaran', 'Cash') }}) — Transaksi {{ session('transaksi_disetujui') }}
            </h6>
            <p class="mb-0 small" style="line-height:1.5;">{{ session('instruksi_pembayaran') }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif

<div class="row g-3 mb-4">
    <!-- Stat 1: Pesanan Menunggu Konfirmasi -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Pesanan Masuk</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #b45309;">
                    ⏳
                </div>
            </div>
            <div class="ac-stat-value">{{ $countMenungguKonfirmasi ?? 3 }}</div>
            <div class="ac-stat-sub">Menunggu konfirmasi admin</div>
        </div>
    </div>

    <!-- Stat 2: Pembayaran Menunggu Verifikasi -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ route('admin.pembayaran.index') }}" class="text-decoration-none text-reset">
            <div class="ac-stat-card">
                <div class="ac-stat-head">
                    <span class="ac-stat-label">Verifikasi Pembayaran</span>
                    <div class="ac-stat-icon-wrap" style="background-color: #e0f2fe; color: #0369a1;">
                        💳
                    </div>
                </div>
                <div class="ac-stat-value">{{ $countMenungguPembayaran ?? 1 }}</div>
                <div class="ac-stat-sub">Bukti transfer belum diperiksa</div>
            </div>
        </a>
    </div>

    <!-- Stat 3: Sedang Disewa -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Sedang Disewa</span>
                <div class="ac-stat-icon-wrap" style="background-color: #d8f3dc; color: #1b4332;">
                    ⛺
                </div>
            </div>
            <div class="ac-stat-value">{{ $countSedangDisewa ?? 4 }}</div>
            <div class="ac-stat-sub">Transaksi aktif di lapangan</div>
        </div>
    </div>

    <!-- Stat 4: Total Stok Fisik -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Stok Fisik Alat</span>
                <div class="ac-stat-icon-wrap">
                    📦
                </div>
            </div>
            <div class="ac-stat-value">{{ $totalStokFisik ?? 28 }}</div>
            <div class="ac-stat-sub">Unit total dalam inventaris</div>
        </div>
    </div>
</div>

<!-- Antrean Pesanan Masuk (Online & On-site) -->
<x-data-table title="Antrean Pesanan Menunggu Konfirmasi" subtitle="Pesanan online & on-site yang memerlukan pengecekan ketersediaan stok sebelum disetujui" searchId="searchAdminOrders">
    <x-slot:actions>
        <span class="badge bg-secondary" style="font-size:0.75rem;">Operasional Admin</span>
    </x-slot:actions>

    <thead>
        <tr>
            <th>No. Transaksi</th>
            <th>Sumber</th>
            <th>Identitas Pelanggan</th>
            <th>Tanggal Sewa</th>
            <th>Tanggal Kembali</th>
            <th>Durasi</th>
            <th>Total Tagihan</th>
            <th>Status Pesanan</th>
            <th>Status Pembayaran</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($pesananMenunggu) && $pesananMenunggu->isNotEmpty())
            @foreach($pesananMenunggu as $trx)
                @php
                    $cleanId = str_replace('-', '', $trx->no_transaksi);
                    $metode = $trx->pembayaran?->metode_pembayaran ?? 'Cash';
                    $instruksi = \App\Http\Controllers\AdminPesananController::getInstruksiPembayaran($metode);

                    // Analisis ketersediaan stok periode untuk seluruh item dalam pesanan ini
                    $allStockAvailable = true;
                    $itemChecks = [];
                    foreach($trx->detailPenyewaan as $d) {
                        $stokTersedia = \App\Models\Penyewaan::hitungStokTersediaPeriode(
                            $d->kode_alat,
                            $trx->tanggal_sewa,
                            $trx->tanggal_kembali,
                            $trx->no_transaksi
                        );
                        $isGood = ($d->alat?->kondisi === 'Baik');
                        $isAvail = ($stokTersedia >= $d->jumlah) && $isGood;
                        if (! $isAvail) {
                            $allStockAvailable = false;
                        }
                        $itemChecks[$d->kode_alat] = [
                            'stok_fisik' => (int) ($d->alat?->stok ?? 0),
                            'stok_tersedia' => $stokTersedia,
                            'is_available' => $isAvail,
                            'kondisi' => $d->alat?->kondisi ?? '-',
                        ];
                    }
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.pesanan.show', $trx->no_transaksi) }}" class="fw-bold text-decoration-none text-dark">
                            {{ $trx->no_transaksi }}
                        </a>
                    </td>
                    <td>
                        @if($trx->isOnsite())
                            <span class="badge bg-warning-subtle text-dark border border-warning" style="font-size:0.75rem;">
                                📟 On-site
                            </span>
                            <small class="text-muted d-block" style="font-size:0.7rem;">On-Site Kasir</small>
                        @else
                            <span class="badge bg-info-subtle text-info border border-info" style="font-size:0.75rem;">
                                🌐 Online
                            </span>
                        @endif
                    </td>
                    <td>
                        <div class="fw-bold">{{ $trx->pelanggan?->nama_pelanggan ?? 'Pelanggan #' . $trx->id_pelanggan }}</div>
                        <small class="text-muted d-block font-monospace">{{ $trx->pelanggan?->nomor_telepon ?? '-' }}</small>
                    </td>
                    <td>
                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($trx->tanggal_sewa)->format('d M Y') }}</span>
                    </td>
                    <td>
                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($trx->tanggal_kembali)->format('d M Y') }}</span>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $trx->durasi_hari }} Hari</span>
                    </td>
                    <td>
                        <span class="fw-bold font-monospace text-success">
                            Rp {{ number_format($trx->total_biaya, 0, ',', '.') }}
                        </span>
                    </td>
                    <td>
                        <x-status-badge :status="$trx->status_penyewaan" />
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-dark border" style="font-size:0.75rem;">
                            {{ $trx->status_pembayaran }}
                        </span>
                        <small class="text-muted d-block mt-1 font-monospace" style="font-size:0.7rem;">
                            {{ $metode }}
                        </small>
                    </td>
                    <td>
                        <div class="d-flex gap-1 align-items-center">
                            <!-- Tombol Cek Detail & Ketersediaan Stok -->
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDetailPesanan{{ $cleanId }}" title="Periksa ketersediaan stok seluruh alat & detail pesanan">
                                🔍 Detail
                            </button>

                            <!-- Form Setujui Langsung dengan Proteksi Double-Click -->
                            <form action="{{ route('admin.pesanan.setujui', $trx->no_transaksi) }}" method="POST" id="formSetujuiDirect_{{ $cleanId }}" onsubmit="if (this.submitted) { return false; } this.submitted = true; const btn = this.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.innerHTML = '<span class=\'spinner-border spinner-border-sm\'></span>'; } return confirm('Setujui pesanan {{ $trx->no_transaksi }}? Stok akan divalidasi ulang.');">
                                @csrf
                                <button type="submit" id="btnSetujuiDirect_{{ $cleanId }}" class="btn btn-sm btn-success" {{ ! $allStockAvailable ? 'disabled title="Stok tidak mencukupi untuk periode ini"' : 'title="Setujui pesanan"' }}>
                                    ✓
                                </button>
                            </form>

                            <!-- Tombol Tolak membuka Modal -->
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalTolakPesanan{{ $cleanId }}" title="Tolak pesanan">
                                ✕
                            </button>
                        </div>

                        <!-- Modal Detail & Pemeriksaan Ketersediaan Stok -->
                        <div class="modal fade" id="modalDetailPesanan{{ $cleanId }}" tabindex="-1" aria-labelledby="modalDetailPesananLabel{{ $cleanId }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div>
                                            <h5 class="modal-title h6 fw-bold mb-0" id="modalDetailPesananLabel{{ $cleanId }}">
                                                Pemeriksaan Pesanan: {{ $trx->no_transaksi }}
                                            </h5>
                                            <small class="text-muted">
                                                Sumber: {{ $trx->isOnsite() ? 'On-site (Tablet Kasir)' : 'Online (Portal Web)' }} &bull; Durasi: {{ $trx->durasi_hari }} Hari
                                            </small>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <!-- Ringkasan Info Pelanggan & Jadwal -->
                                        <div class="row g-2 mb-3 p-2 bg-light rounded border">
                                            <div class="col-12 col-sm-6">
                                                <small class="text-muted d-block">Pelanggan</small>
                                                <strong>{{ $trx->pelanggan?->nama_pelanggan ?? 'Pelanggan #' . $trx->id_pelanggan }}</strong>
                                                <span class="d-block small font-monospace">{{ $trx->pelanggan?->nomor_telepon ?? '-' }}</span>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <small class="text-muted d-block">Jadwal Periode Sewa</small>
                                                <span class="fw-bold">
                                                    {{ \Carbon\Carbon::parse($trx->tanggal_sewa)->format('d M Y') }} &rarr; {{ \Carbon\Carbon::parse($trx->tanggal_kembali)->format('d M Y') }}
                                                </span>
                                                <span class="d-block small text-muted">({{ $trx->durasi_hari }} Hari)</span>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <small class="text-muted d-block">Status Pesanan vs Status Bayar</small>
                                                <div class="d-flex gap-1 align-items-center mt-1">
                                                    <x-status-badge :status="$trx->status_penyewaan" />
                                                    <span class="badge bg-secondary-subtle text-dark border">Bayar: {{ $trx->status_pembayaran }}</span>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <small class="text-muted d-block">Metode Pembayaran Pilihan</small>
                                                <span class="badge bg-primary-subtle text-primary border fw-bold">{{ $metode }}</span>
                                            </div>
                                        </div>

                                        <!-- Instruksi Pembayaran Sesuai Metode Pilihan Pelanggan -->
                                        <div class="p-3 mb-3 bg-white rounded border">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="fs-5">💳</span>
                                                <strong class="text-dark">Instruksi Pembayaran ({{ $metode }})</strong>
                                            </div>
                                            <p class="small text-muted mb-0" style="line-height:1.5;">
                                                {{ $instruksi }}
                                            </p>
                                        </div>

                                        <!-- Tabel Rincian Alat & Ketersediaan Stok Periode -->
                                        <h6 class="fw-bold mb-2">Rincian Seluruh Alat & Ketersediaan Periode:</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Kode</th>
                                                        <th>Jenis / Nama Alat</th>
                                                        <th class="text-center">Kondisi</th>
                                                        <th class="text-center">Stok Toko</th>
                                                        <th class="text-center">Stok Periode</th>
                                                        <th class="text-center">Diminta</th>
                                                        <th class="text-center">Ketersediaan</th>
                                                        <th class="text-end">Tarif / Hari</th>
                                                        <th class="text-end">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($trx->detailPenyewaan as $detail)
                                                        @php
                                                            $chk = $itemChecks[$detail->kode_alat] ?? [
                                                                'stok_fisik' => 0,
                                                                'stok_tersedia' => 0,
                                                                'is_available' => false,
                                                                'kondisi' => 'Baik',
                                                            ];
                                                        @endphp
                                                        <tr>
                                                            <td class="font-monospace fw-bold">{{ $detail->kode_alat }}</td>
                                                            <td>{{ $detail->alat?->nama_alat ?? $detail->kode_alat }}</td>
                                                            <td class="text-center">
                                                                @if($chk['kondisi'] === 'Baik')
                                                                    <span class="badge bg-success-subtle text-success border border-success">Baik</span>
                                                                @else
                                                                    <span class="badge bg-danger-subtle text-danger border border-danger">{{ $chk['kondisi'] }}</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">{{ $chk['stok_fisik'] }}</td>
                                                            <td class="text-center fw-bold {{ $chk['stok_tersedia'] >= $detail->jumlah ? 'text-success' : 'text-danger' }}">
                                                                {{ $chk['stok_tersedia'] }}
                                                            </td>
                                                            <td class="text-center fw-bold">{{ $detail->jumlah }}</td>
                                                            <td class="text-center">
                                                                @if($chk['is_available'])
                                                                    <span class="badge bg-success-subtle text-success border border-success">
                                                                        ✓ Cukup
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-danger-subtle text-danger border border-danger">
                                                                        ⚠ Kurang (Sisa {{ $chk['stok_tersedia'] }})
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="text-end font-monospace">Rp {{ number_format($detail->tarif_sewa, 0, ',', '.') }}</td>
                                                            <td class="text-end font-monospace fw-bold">Rp {{ number_format($detail->subtotal_biaya, 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <th colspan="8" class="text-end fw-bold">Total Tagihan ({{ $trx->durasi_hari }} Hari):</th>
                                                        <th class="text-end font-monospace fw-bold text-success fs-6">
                                                            Rp {{ number_format($trx->total_biaya, 0, ',', '.') }}
                                                        </th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        <!-- Alert Status Ketersediaan Keseluruhan -->
                                        @if($allStockAvailable)
                                            <div class="alert alert-success d-flex align-items-center mb-0 mt-3 py-2 gap-2">
                                                <span class="fs-5">✓</span>
                                                <div class="small">
                                                    <strong>Stok Mencukupi:</strong> Seluruh alat tersedia dan berkondisi baik untuk periode yang diminta. Pesanan aman disetujui.
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-danger d-flex align-items-center mb-0 mt-3 py-2 gap-2">
                                                <span class="fs-5">⚠</span>
                                                <div class="small">
                                                    <strong>Peringatan Stok:</strong> Satu atau lebih alat memiliki stok kurang dari jumlah yang diminta pada rentang tanggal tersebut.
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="modal-footer d-flex justify-content-between">
                                        <a href="{{ route('admin.pesanan.show', $trx->no_transaksi) }}" class="btn btn-sm btn-outline-secondary">
                                            Buka Halaman Lengkap &rarr;
                                        </a>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalTolakPesanan{{ $cleanId }}">
                                                ✕ Tolak Pesanan
                                            </button>
                                            <form action="{{ route('admin.pesanan.setujui', $trx->no_transaksi) }}" method="POST" id="formSetujuiModal_{{ $cleanId }}" onsubmit="if (this.submitted) { return false; } this.submitted = true; const btn = this.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.innerHTML = '<span class=\'spinner-border spinner-border-sm me-1\'></span> Memproses...'; } return true;">
                                                @csrf
                                                <button type="submit" id="btnSetujuiModal_{{ $cleanId }}" class="btn btn-sm btn-success" {{ ! $allStockAvailable ? 'disabled title="Stok tidak mencukupi untuk periode ini"' : '' }}>
                                                    ✓ Setujui Pesanan
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Tolak Pesanan -->
                        <div class="modal fade" id="modalTolakPesanan{{ $cleanId }}" tabindex="-1" aria-labelledby="modalTolakPesananLabel{{ $cleanId }}" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="{{ route('admin.pesanan.tolak', $trx->no_transaksi) }}" method="POST" id="formTolak_{{ $cleanId }}" onsubmit="if (this.submitted) { return false; } this.submitted = true; const btn = this.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.innerHTML = '<span class=\'spinner-border spinner-border-sm me-1\'></span> Memproses...'; } return true;">
                                    @csrf
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title h6 fw-bold" id="modalTolakPesananLabel{{ $cleanId }}">
                                                Tolak Pesanan {{ $trx->no_transaksi }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                        </div>
                                        <div class="modal-body text-start">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                                                <textarea name="alasan_penolakan" class="form-control" rows="3" required minlength="5" maxlength="500" placeholder="Contoh: Stok alat tidak mencukupi untuk periode tanggal yang diminta..."></textarea>
                                                <small class="text-muted">Alasan penolakan wajib diisi (minimal 5 karakter, maksimal 500 karakter).</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" id="btnTolak_{{ $cleanId }}" class="btn btn-sm btn-danger">Konfirmasi Tolak Pesanan</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="10" class="text-center py-4 text-muted">
                    Tidak ada pesanan masuk yang menunggu konfirmasi saat ini.
                </td>
            </tr>
        @endif
    </tbody>
</x-data-table>

<div class="row g-3">
    <!-- Info Inventaris Cepat -->
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100">
            <div class="ac-card-header">
                <h3 class="ac-card-title">Kondisi Alat Camping</h3>
            </div>
            <div class="ac-card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <strong>Siap Disewa (Kondisi Baik)</strong>
                        <small class="text-muted d-block">Stok dapat dipesan pelanggan</small>
                    </div>
                    <span class="badge bg-success" style="font-size:0.9rem;">{{ $stokKondisiBaik ?? 0 }} Unit</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <strong>Perlu Perbaikan</strong>
                        <small class="text-muted d-block">Pengecekan ritsleting & frame tenda</small>
                    </div>
                    <span class="badge bg-warning text-dark" style="font-size:0.9rem;">{{ $stokPerluPerbaikan ?? 0 }} Unit</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <div>
                        <strong>Rusak / Afkir</strong>
                        <small class="text-muted d-block">Tidak boleh dihitung sebagai stok sewa</small>
                    </div>
                    <span class="badge bg-danger" style="font-size:0.9rem;">{{ $stokRusak ?? 0 }} Unit</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Catatan Pedoman Operasional -->
    <div class="col-12 col-lg-6">
        <div class="ac-card h-100">
            <div class="ac-card-header">
                <h3 class="ac-card-title">Pedoman Operasional Admin</h3>
            </div>
            <div class="ac-card-body">
                <ul class="mb-0 ps-3 small text-muted" style="line-height: 1.7;">
                    <li>Pemeriksaan stok harus dilakukan ulang sebelum menyetujui pesanan online maupun on-site.</li>
                    <li>Jangan menyetujui pesanan jika stok periode tersebut tidak mencukupi.</li>
                    <li>Status disetujui <strong>bukan berarti</strong> pembayaran lunas (status pesanan dan pembayaran terpisah).</li>
                    <li>Pembayaran via transfer hanya ditandai lunas setelah bukti transfer divalidasi.</li>
                    <li>Alat rusak wajib dicatat pada saat pengembalian dan tidak dikembalikan ke stok siap sewa.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
