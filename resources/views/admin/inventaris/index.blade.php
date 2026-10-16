@extends('layouts.app')

@section('title', 'Inventaris & Pemantauan Stok — Admin')
@section('page-title', 'Inventaris Alat Outdoor')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Inventaris & Stok</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Pemantauan Inventaris & Ketersediaan Stok</h2>
            <p class="text-muted small mb-0">Pantau ketersediaan stok fisik di store, unit siap sewa, unit yang sedang dialokasikan pelanggan, serta kondisi fisik alat.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.inventaris.riwayat') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                <span>📜</span>
                <span>Lihat Riwayat Mutasi Stok</span>
            </a>
        </div>
    </div>
</div>

<!-- 4 Kartu Metrik Inventaris -->
<div class="row g-3 mb-4">
    <!-- Total Stok Fisik -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Total Stok Fisik</span>
                <div class="ac-stat-icon-wrap" style="background-color: #e0f2fe; color: #0369a1;">
                    📦
                </div>
            </div>
            <div class="ac-stat-value font-monospace">{{ $totalFisikSemua }}</div>
            <div class="ac-stat-sub">Semua unit alat toko & sewa</div>
        </div>
    </div>

    <!-- Total Stok Siap Sewa -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Stok Siap Sewa</span>
                <div class="ac-stat-icon-wrap" style="background-color: #dcfce7; color: #15803d;">
                    ✅
                </div>
            </div>
            <div class="ac-stat-value text-success font-monospace">{{ $totalSiapSewaSemua }}</div>
            <div class="ac-stat-sub">Kondisi Baik & ada di store</div>
        </div>
    </div>

    <!-- Total Dialokasikan / Disewa -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Sedang Disewa</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #b45309;">
                    🏕️
                </div>
            </div>
            <div class="ac-stat-value text-warning font-monospace">{{ $totalDialokasikanSemua }}</div>
            <div class="ac-stat-sub">Unit di tangan penyewa aktif</div>
        </div>
    </div>

    <!-- Total Rusak / Perbaikan -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Rusak / Perlu Servis</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fee2e2; color: #b91c1c;">
                    ⚠️
                </div>
            </div>
            <div class="ac-stat-value text-danger font-monospace">{{ $totalBermasalahSemua }}</div>
            <div class="ac-stat-sub">Tidak disewakan ke pelanggan</div>
        </div>
    </div>
</div>

<!-- Informasi SOP Stok -->
<div class="alert alert-secondary border d-flex align-items-start gap-2 mb-4 p-3" style="border-radius: 10px;">
    <span style="font-size: 1.25rem;">ℹ️</span>
    <div>
        <strong class="d-block mb-1 text-dark">Pedoman Pengendalian Stok Inventaris:</strong>
        <p class="mb-0 small text-dark">
            • <strong>Stok Siap Sewa</strong> hanya memperhitungkan alat berkondisi <em>Baik</em> yang berada di toko.<br>
            • Unit dengan kondisi <em>Rusak</em> atau <em>Perlu Perbaikan</em> secara otomatis dikeluarkan dari stok siap sewa.<br>
            • Stok tidak boleh bernilai negatif pada kondisi operasional apapun.
        </p>
    </div>
</div>

<!-- Card Tabel Inventaris -->
<div class="ac-card">
    <div class="ac-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="ac-card-title">Daftar Inventaris Alat Outdoor ({{ $inventarisData->count() }} Item)</h3>
            <small class="text-muted">Data stok aktual dan alokasi penyewaan lapangan</small>
        </div>

        <!-- Filter Form -->
        <form action="{{ route('admin.inventaris.index') }}" method="GET" class="d-flex align-items-center flex-wrap gap-2">
            <div class="input-group input-group-sm" style="max-width: 220px;">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari kode/nama alat...">
            </div>

            <select name="kategori" class="form-select form-select-sm" style="width: auto;">
                <option value="">Semua Kategori</option>
                @foreach($kategoriList as $kat)
                    <option value="{{ $kat }}" {{ $kategoriFilter === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                @endforeach
            </select>

            <select name="kondisi" class="form-select form-select-sm" style="width: auto;">
                <option value="">Semua Kondisi</option>
                <option value="Baik" {{ $kondisiFilter === 'Baik' ? 'selected' : '' }}>Baik</option>
                <option value="Perlu Perbaikan" {{ $kondisiFilter === 'Perlu Perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                <option value="Rusak" {{ $kondisiFilter === 'Rusak' ? 'selected' : '' }}>Rusak</option>
            </select>

            <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            @if($search !== '' || $kategoriFilter !== '' || $kondisiFilter !== '' || $statusStok !== '')
                <a href="{{ route('admin.inventaris.index') }}" class="btn btn-sm btn-outline-danger" title="Reset">&times;</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="table-inventaris-admin">
            <thead class="table-light small">
                <tr>
                    <th style="width: 120px;">Kode Alat</th>
                    <th>Nama & Kategori</th>
                    <th>Kondisi Fisik</th>
                    <th class="text-center" style="width: 120px;">Stok Fisik Total</th>
                    <th class="text-center" style="width: 120px;">Stok Siap Sewa</th>
                    <th class="text-center" style="width: 130px;">Sedang Disewa</th>
                    <th class="text-center" style="width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventarisData as $item)
                    <tr>
                        <td>
                            <strong class="font-monospace text-dark d-block">{{ $item->kode_alat }}</strong>
                            <small class="text-muted">Rp {{ number_format($item->tarif_sewa, 0, ',', '.') }}/hari</small>
                        </td>
                        <td>
                            <div class="font-weight-bold text-dark">{{ $item->nama_alat }}</div>
                            <span class="badge bg-light text-muted border" style="font-size: 0.72rem;">{{ $item->kategori }}</span>
                        </td>
                        <td>
                            <x-status-badge :status="$item->kondisi" />
                        </td>
                        <td class="text-center">
                            <span class="font-monospace fw-bold text-dark fs-6">{{ $item->stok_fisik_total }}</span>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">unit</small>
                        </td>
                        <td class="text-center">
                            @if($item->stok_siap_sewa > 0)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 font-monospace fs-6">
                                    {{ $item->stok_siap_sewa }} unit
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 font-monospace">
                                    0 unit
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($item->stok_dialokasikan > 0)
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 font-monospace">
                                    {{ $item->stok_dialokasikan }} unit
                                </span>
                            @else
                                <span class="text-muted small">&mdash;</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.inventaris.show', $item->kode_alat) }}" 
                               class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm px-2 py-1"
                               id="btn-detail-{{ $item->kode_alat }}">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Detail & Stok
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="mb-2" style="font-size: 2rem;">📦</div>
                            <h5 class="fw-bold mb-1">Tidak Ada Data Inventaris</h5>
                            <p class="small mb-0">Tidak ditemukan alat outdoor yang cocok dengan kriteria pencarian Anda.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
