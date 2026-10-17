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
        <div>
            <a href="{{ route('admin.inventaris.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali ke Daftar Inventaris
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
            <div class="ac-stat-value text-warning font-monospace">{{ $stokDialokasikan }}</div>
            <div class="ac-stat-sub">Di tangan pelanggan</div>
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

        <!-- Tabel Transaksi Aktif yang Menggunakan Unit Ini -->
        <div class="ac-card mb-4">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="ac-card-title">Unit Sedang Dialokasikan / Disewa</h3>
                    <small class="text-muted">Daftar transaksi aktif di lapangan</small>
                </div>
                <span class="badge bg-warning text-dark font-monospace">{{ $stokDialokasikan }} unit</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Pelanggan</th>
                            <th>Unit</th>
                            <th>Jadwal Sewa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alokasiAktif as $detail)
                            <tr>
                                <td>
                                    <strong class="font-monospace text-dark">{{ $detail->no_transaksi }}</strong>
                                </td>
                                <td>
                                    <span class="text-dark fw-bold">{{ $detail->penyewaan->pelanggan->nama_pelanggan ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $detail->jumlah_diserahkan > 0 ? $detail->jumlah_diserahkan : $detail->jumlah }} unit
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
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <em>Tidak ada unit yang sedang disewa atau dialokasikan saat ini.</em>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section Unit Menunggu Konfirmasi Selesai Perbaikan -->
        <div class="ac-card mb-4 border-warning">
            <div class="ac-card-header bg-warning-subtle d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="ac-card-title text-dark fs-6 fw-bold mb-0">🔧 Unit Menunggu Konfirmasi Selesai Perbaikan</h3>
                    <small class="text-muted">Unit kembali tersedia di stok siap sewa setelah dikonfirmasi oleh petugas yang berwenang</small>
                </div>
                <span class="badge bg-warning text-dark font-monospace">{{ $stokPerluPerbaikan }} unit</span>
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
@endsection
