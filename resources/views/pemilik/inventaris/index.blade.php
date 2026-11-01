@extends('layouts.app')

@section('title', 'Laporan Inventaris & Stok — Pemilik')
@section('page-title', 'Laporan Inventaris & Stok')

@section('content')
<!-- Read-Only Notice -->
<div class="alert alert-secondary d-flex align-items-center gap-2 mb-4 py-2 px-3 border-0 shadow-sm" style="border-radius:10px; background-color:#edf2f7; color:#334155;">
    <svg width="20" height="20" fill="currentColor" class="flex-shrink-0 text-primary" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
    </svg>
    <div class="small">
        <strong>Hak Akses Pemilik (Strictly Read-Only):</strong> Halaman ini menyajikan rincian inventaris alat camping dan status unit secara real-time dari database untuk pengawasan operasional dan aset bisnis. Seluruh tindakan perubahan data dinonaktifkan.
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="{{ route('pemilik.inventaris.index', ['status_stok' => 'semua']) }}" class="text-decoration-none">
            <div class="p-3 bg-white border rounded shadow-sm h-100 {{ $statusStok === 'semua' ? 'border-primary border-2 bg-primary-subtle' : '' }}">
                <small class="text-muted d-block">📦 Total Stok Fisik</small>
                <strong class="fs-5 text-dark font-monospace">{{ $ringkasanInventaris['total_stok_fisik'] }} Unit</strong>
                <div class="small text-muted mt-1" style="font-size:0.75rem;">Seluruh unit aset fisik</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('pemilik.inventaris.index', ['status_stok' => 'siap_sewa']) }}" class="text-decoration-none">
            <div class="p-3 bg-white border rounded shadow-sm h-100 {{ $statusStok === 'siap_sewa' ? 'border-success border-2 bg-success-subtle' : '' }}">
                <small class="text-muted d-block">✅ Stok Siap Sewa</small>
                <strong class="fs-5 text-success font-monospace">{{ $ringkasanInventaris['stok_siap_sewa'] }} Unit</strong>
                <div class="small text-muted mt-1" style="font-size:0.75rem;">Kondisi baik di toko</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-2">
        <a href="{{ route('pemilik.inventaris.index', ['status_stok' => 'sedang_disewa']) }}" class="text-decoration-none">
            <div class="p-3 bg-white border rounded shadow-sm h-100 {{ $statusStok === 'sedang_disewa' ? 'border-warning border-2 bg-warning-subtle' : '' }}">
                <small class="text-muted d-block">🏕️ Sedang Disewa</small>
                <strong class="fs-5 text-warning font-monospace">{{ $ringkasanInventaris['stok_sedang_disewa'] }} Unit</strong>
                <div class="small text-muted mt-1" style="font-size:0.75rem;">Dalam sewa aktif</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-2">
        <a href="{{ route('pemilik.inventaris.index', ['status_stok' => 'perlu_perbaikan']) }}" class="text-decoration-none">
            <div class="p-3 bg-white border rounded shadow-sm h-100 {{ $statusStok === 'perlu_perbaikan' ? 'border-warning border-2 bg-warning-subtle' : '' }}">
                <small class="text-muted d-block">🔧 Perlu Perbaikan</small>
                <strong class="fs-5 text-warning font-monospace">{{ $ringkasanInventaris['stok_perlu_perbaikan'] }} Unit</strong>
                <div class="small text-muted mt-1" style="font-size:0.75rem;">Butuh servis teknis</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-2">
        <a href="{{ route('pemilik.inventaris.index', ['status_stok' => 'rusak']) }}" class="text-decoration-none">
            <div class="p-3 bg-white border rounded shadow-sm h-100 {{ $statusStok === 'rusak' ? 'border-danger border-2 bg-danger-subtle' : '' }}">
                <small class="text-muted d-block">⚠️ Rusak / Afkir</small>
                <strong class="fs-5 text-danger font-monospace">{{ $ringkasanInventaris['stok_rusak'] }} Unit</strong>
                <div class="small text-muted mt-1" style="font-size:0.75rem;">Unit afkir tidak sewa</div>
            </div>
        </a>
    </div>
</div>

<!-- Filter Form -->
<div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="ac-card-body p-3">
        <form method="GET" action="{{ route('pemilik.inventaris.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama atau kode alat..." value="{{ $search }}">
            </div>
            <div class="col-6 col-md-3">
                <select name="kategori" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $kat)
                        <option value="{{ $kat }}" {{ $kategoriFilter === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="status_stok" class="form-select form-select-sm">
                    <option value="semua" {{ $statusStok === 'semua' ? 'selected' : '' }}>Semua Status Stok</option>
                    <option value="siap_sewa" {{ $statusStok === 'siap_sewa' ? 'selected' : '' }}>Hanya Stok Siap Sewa</option>
                    <option value="dialokasikan" {{ $statusStok === 'dialokasikan' ? 'selected' : '' }}>Hanya Terpesan / Menunggu Diambil</option>
                    <option value="sedang_disewa" {{ $statusStok === 'sedang_disewa' ? 'selected' : '' }}>Hanya Barang Sedang Disewa</option>
                    <option value="perlu_perbaikan" {{ $statusStok === 'perlu_perbaikan' ? 'selected' : '' }}>Hanya Perlu Perbaikan</option>
                    <option value="rusak" {{ $statusStok === 'rusak' ? 'selected' : '' }}>Hanya Rusak / Afkir</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
                @if($search !== '' || $kategoriFilter !== '' || $statusStok !== 'semua')
                    <a href="{{ route('pemilik.inventaris.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset">✕</a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($statusStok === 'sedang_disewa')
    <!-- TAMPILAN TABEL BARANG SEDANG DISEWA AKTIF -->
    <div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="ac-card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark">🏕️ Rincian Barang yang Sedang Berada dalam Transaksi Sewa Aktif</h5>
                <small class="text-muted">Total: <strong>{{ $daftarSedangDisewa->sum('jumlah_unit') }} Unit</strong> tersewa di lapangan</small>
            </div>
            <span class="badge bg-warning text-dark px-3 py-2">Read-Only</span>
        </div>
        <div class="ac-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Pelanggan</th>
                            <th>Kode Alat</th>
                            <th>Nama Alat</th>
                            <th>Kategori</th>
                            <th class="text-center">Kuantitas</th>
                            <th>Periode Sewa</th>
                            <th>Status Sewa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($daftarSedangDisewa as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('pemilik.transaksi.show', $item->no_transaksi) }}" class="fw-bold text-decoration-none">
                                        {{ $item->no_transaksi }}
                                    </a>
                                </td>
                                <td>
                                    <strong>{{ $item->nama_pelanggan }}</strong>
                                    <small class="text-muted d-block" style="font-size:0.75rem;">Sumber: {{ ucfirst($item->sumber_pesanan) }}</small>
                                </td>
                                <td><code>{{ $item->kode_alat }}</code></td>
                                <td class="fw-bold text-dark">{{ $item->nama_alat }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $item->kategori }}</span></td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark font-monospace fs-6 px-3 py-1">
                                        {{ $item->jumlah_unit }} Unit
                                    </span>
                                </td>
                                <td>
                                    <small class="d-block text-dark fw-bold">
                                        {{ \Carbon\Carbon::parse($item->tanggal_sewa)->format('d M Y') }} — {{ \Carbon\Carbon::parse($item->tanggal_kembali)->format('d M Y') }}
                                    </small>
                                    <small class="text-muted" style="font-size:0.75rem;">Durasi: {{ $item->durasi_hari }} Hari</small>
                                </td>
                                <td>
                                    <x-status-badge :status="$item->status_penyewaan" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="fs-1 mb-2">🏕️</div>
                                    <p class="mb-0">Tidak ada unit alat yang sedang dalam transaksi sewa aktif saat ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@elseif($statusStok === 'dialokasikan')
    <!-- TAMPILAN TABEL BARANG TERPESAN / MENUNGGU DIAMBIL -->
    <div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="ac-card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark">📦 Rincian Unit Terpesan / Menunggu Diambil di Toko</h5>
                <small class="text-muted">Total: <strong>{{ $daftarDialokasikan->sum('jumlah_unit') }} Unit</strong> dialokasikan (menunggu konfirmasi, pembayaran, atau pengambilan fisik)</small>
            </div>
            <span class="badge bg-info text-dark px-3 py-2">Read-Only</span>
        </div>
        <div class="ac-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Pelanggan</th>
                            <th>Kode Alat</th>
                            <th>Nama Alat</th>
                            <th>Kategori</th>
                            <th class="text-center">Kuantitas</th>
                            <th>Periode Sewa</th>
                            <th>Status Pesanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($daftarDialokasikan as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('pemilik.transaksi.show', $item->no_transaksi) }}" class="fw-bold text-decoration-none">
                                        {{ $item->no_transaksi }}
                                    </a>
                                </td>
                                <td>
                                    <strong>{{ $item->nama_pelanggan }}</strong>
                                    <small class="text-muted d-block" style="font-size:0.75rem;">Sumber: {{ ucfirst($item->sumber_pesanan) }}</small>
                                </td>
                                <td><code>{{ $item->kode_alat }}</code></td>
                                <td class="fw-bold text-dark">{{ $item->nama_alat }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $item->kategori }}</span></td>
                                <td class="text-center">
                                    <span class="badge bg-info text-dark font-monospace fs-6 px-3 py-1">
                                        {{ $item->jumlah_unit }} Unit
                                    </span>
                                </td>
                                <td>
                                    <small class="d-block text-dark fw-bold">
                                        {{ \Carbon\Carbon::parse($item->tanggal_sewa)->format('d M Y') }} — {{ \Carbon\Carbon::parse($item->tanggal_kembali)->format('d M Y') }}
                                    </small>
                                    <small class="text-muted" style="font-size:0.75rem;">Durasi: {{ $item->durasi_hari }} Hari</small>
                                </td>
                                <td>
                                    <x-status-badge :status="$item->status_penyewaan" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="fs-1 mb-2">📦</div>
                                    <p class="mb-0">Tidak ada unit alat yang sedang dialokasikan / menunggu pengambilan saat ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@else
    <!-- TAMPILAN TABEL STATUS & KETERSEDIAAN SELURUH ALAT -->
    <div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="ac-card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark">📋 Daftar Inventaris & Distribusi Unit Alat</h5>
                <small class="text-muted">Total terdata: <strong>{{ $inventarisList->count() }} Jenis Alat</strong> | Total Unit Fisik: <strong>{{ $inventarisList->sum('total_stok_fisik') }} Unit</strong></small>
            </div>
            <span class="badge bg-secondary px-3 py-2">Read-Only</span>
        </div>
        <div class="ac-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Alat</th>
                            <th>Kategori</th>
                            <th>Kondisi</th>
                            <th class="text-end">Tarif / Hari</th>
                            <th class="text-center">Stok Toko</th>
                            <th class="text-center">Terpesan di Toko</th>
                            <th class="text-center">Sedang Disewa</th>
                            <th class="text-center">Perlu Perbaikan</th>
                            <th class="text-center">Rusak</th>
                            <th class="text-center fw-bold text-primary">Total Fisik</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventarisList as $item)
                            <tr>
                                <td><code>{{ $item->kode_alat }}</code></td>
                                <td class="fw-bold text-dark">{{ $item->nama_alat }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $item->kategori }}</span></td>
                                <td><x-status-badge :status="$item->kondisi" /></td>
                                <td class="text-end font-monospace">Rp {{ number_format($item->tarif_sewa, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $item->stok_siap_sewa > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' }} font-monospace px-2 py-1">
                                        {{ $item->stok_siap_sewa }} Unit
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ ($item->stok_dialokasikan ?? 0) > 0 ? 'bg-info-subtle text-info-emphasis border border-info-subtle' : 'bg-light text-muted border' }} font-monospace px-2 py-1">
                                        {{ $item->stok_dialokasikan ?? 0 }} Unit
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $item->stok_sedang_disewa > 0 ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-muted border' }} font-monospace px-2 py-1">
                                        {{ $item->stok_sedang_disewa }} Unit
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $item->stok_perlu_perbaikan > 0 ? 'bg-warning text-dark' : 'bg-light text-muted border' }} font-monospace px-2 py-1">
                                        {{ $item->stok_perlu_perbaikan }} Unit
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $item->stok_rusak > 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-muted border' }} font-monospace px-2 py-1">
                                        {{ $item->stok_rusak }} Unit
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary font-monospace fs-6 px-3 py-1">
                                        {{ $item->total_stok_fisik }} Unit
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <div class="fs-1 mb-2">📦</div>
                                    <p class="mb-0">Tidak ada alat yang sesuai dengan filter pencarian.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mt-3">
    <a href="{{ route('pemilik.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        &larr; Kembali ke Dashboard Pemilik
    </a>
    <small class="text-muted">Data stok diperbarui otomatis berdasarkan kalkulasi terpusat database.</small>
</div>
@endsection
