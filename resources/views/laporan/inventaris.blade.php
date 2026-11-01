@extends('layouts.app')

@section('title', 'Laporan Inventaris & Ketersediaan Stok')
@section('page-title', 'Laporan Inventaris & Stok')

@section('content')
<!-- Header & Action Buttons -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 font-weight-bold mb-1 text-dark">📦 Laporan Inventaris, Ketersediaan Stok & Pergerakan Aset</h2>
        <p class="text-muted small mb-0">Pantau ketersediaan unit siap sewa, unit di tangan penyewa, barang dialokasikan di toko, dan riwayat mutasi masuk/keluar.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ request()->routeIs('pemilik.*') ? route('pemilik.inventaris.cetak', request()->all()) : route('admin.laporan.inventaris.cetak', request()->all()) }}" 
           target="_blank" 
           class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1 shadow-sm" 
           id="btn-cetak-laporan-inventaris">
            <span>🖨️</span>
            <span>Cetak Laporan</span>
        </a>
        <a href="{{ request()->routeIs('pemilik.*') ? route('pemilik.inventaris.export', request()->all()) : route('admin.laporan.inventaris.export', request()->all()) }}" 
           class="btn btn-success btn-sm d-flex align-items-center gap-1 shadow-sm" 
           id="btn-export-laporan-inventaris">
            <span>📥</span>
            <span>Ekspor Excel (CSV)</span>
        </a>
    </div>
</div>

<!-- Kartu Ringkasan Status Stok Inventaris -->
<div class="row g-3 mb-4">
    <!-- Total Stok Fisik -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="ac-stat-card h-100">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Total Stok Fisik</span>
                <div class="ac-stat-icon-wrap" style="background-color: #e0f2fe; color: #0284c7;">📦</div>
            </div>
            <div class="ac-stat-value font-monospace">{{ $ringkasanInventaris['total_stok_fisik'] }}</div>
            <div class="ac-stat-sub text-muted">Seluruh unit kepemilikan</div>
        </div>
    </div>

    <!-- Siap Sewa -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="ac-stat-card h-100">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Siap Disewa</span>
                <div class="ac-stat-icon-wrap" style="background-color: #dcfce7; color: #16a34a;">✅</div>
            </div>
            <div class="ac-stat-value text-success font-monospace">{{ $ringkasanInventaris['stok_siap_sewa'] }}</div>
            <div class="ac-stat-sub text-muted">Kondisi Baik di toko</div>
        </div>
    </div>

    <!-- Sedang Disewa di Lapangan -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="ac-stat-card h-100">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Sedang Disewa</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #d97706;">🏕️</div>
            </div>
            <div class="ac-stat-value text-warning font-monospace">{{ $ringkasanInventaris['stok_sedang_disewa'] }}</div>
            <div class="ac-stat-sub text-muted">Di tangan pelanggan</div>
        </div>
    </div>

    <!-- Dialokasikan di Toko -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="ac-stat-card h-100">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Dialokasikan</span>
                <div class="ac-stat-icon-wrap" style="background-color: #e0e7ff; color: #4f46e5;">📋</div>
            </div>
            <div class="ac-stat-value text-primary font-monospace">{{ $ringkasanInventaris['stok_dialokasikan'] }}</div>
            <div class="ac-stat-sub text-muted">Dipesan di toko/belum diambil</div>
        </div>
    </div>

    <!-- Perlu Servis / Rusak -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="ac-stat-card h-100">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Unit Bermasalah</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fee2e2; color: #dc2626;">⚠️</div>
            </div>
            <div class="ac-stat-value text-danger font-monospace">{{ $ringkasanInventaris['stok_perlu_perbaikan'] + $ringkasanInventaris['stok_rusak'] }}</div>
            <div class="ac-stat-sub text-muted">{{ $ringkasanInventaris['stok_perlu_perbaikan'] }} Servis &bull; {{ $ringkasanInventaris['stok_rusak'] }} Rusak</div>
        </div>
    </div>

    <!-- Total Pembelian PO Supplier -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="ac-stat-card h-100">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Biaya Pengadaan</span>
                <div class="ac-stat-icon-wrap" style="background-color: #f1f5f9; color: #334155;">🛒</div>
            </div>
            <div class="ac-stat-value font-monospace" style="font-size: 1.15rem;">Rp {{ number_format($totalBiayaPembelian, 0, ',', '.') }}</div>
            <div class="ac-stat-sub text-muted">Total belanja PO supplier</div>
        </div>
    </div>
</div>

<!-- Tab Navigasi Sub-Laporan: Status Stok vs Riwayat Pergerakan Stok -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab !== 'mutasi' ? 'active text-primary' : 'text-muted' }}" 
           href="{{ request()->fullUrlWithQuery(['tab' => 'stok']) }}">
            📊 Kondisi & Ketersediaan Stok Alat
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'mutasi' ? 'active text-primary' : 'text-muted' }}" 
           href="{{ request()->fullUrlWithQuery(['tab' => 'mutasi']) }}">
            🔄 Riwayat Pergerakan & Mutasi Stok (Masuk & Keluar)
        </a>
    </li>
</ul>

@if($tab !== 'mutasi')
    <!-- ==================== TAB 1: STATUS & KETERSEDIAAN STOK ==================== -->

    <!-- Form Filter Tab Stok -->
    <div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="ac-card-body p-3">
            <form method="GET" action="{{ request()->url() }}" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="stok">
                <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                <input type="hidden" name="order" value="{{ $order }}">

                <div class="col-12 col-md-4">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama atau kode alat outdoor..." value="{{ $search }}">
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
                        <option value="sedang_disewa" {{ $statusStok === 'sedang_disewa' ? 'selected' : '' }}>Hanya Sedang Disewa</option>
                        <option value="dialokasikan" {{ $statusStok === 'dialokasikan' ? 'selected' : '' }}>Hanya Dialokasikan di Toko</option>
                        <option value="perlu_perbaikan" {{ $statusStok === 'perlu_perbaikan' ? 'selected' : '' }}>Hanya Perlu Servis</option>
                        <option value="rusak" {{ $statusStok === 'rusak' ? 'selected' : '' }}>Hanya Rusak / Afkir</option>
                        <option value="bermasalah" {{ $statusStok === 'bermasalah' ? 'selected' : '' }}>Seluruh Unit Bermasalah</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">🔍 Filter</button>
                    @if($search !== '' || $kategoriFilter !== '' || $statusStok !== 'semua')
                        <a href="{{ request()->url() }}?tab=stok" class="btn btn-sm btn-outline-secondary" title="Reset Filter">✕</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @php
        $buildInvSortUrl = function ($field) use ($sortBy, $order, $search, $kategoriFilter, $statusStok) {
            $newOrder = ($sortBy === $field && $order === 'asc') ? 'desc' : 'asc';
            $params = [
                'tab' => 'stok',
                'q' => $search,
                'kategori' => $kategoriFilter,
                'status_stok' => $statusStok,
                'sort_by' => $field,
                'order' => $newOrder,
            ];
            return request()->url() . '?' . http_build_query($params);
        };

        $invSortIcon = function ($field) use ($sortBy, $order) {
            if ($sortBy === $field) {
                return $order === 'asc' ? '▲' : '▼';
            }
            return '⇅';
        };
    @endphp

    <!-- Card Tabel Inventaris Alat -->
    <div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="ac-card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 px-4 gap-2">
            <div>
                <h3 class="ac-card-title fs-5 fw-bold mb-0 text-dark">📋 Rincian Stok Fisik & Ketersediaan Alat ({{ $inventarisList->count() }} Item)</h3>
                <small class="text-muted">Data stok fisik di toko, unit di tangan penyewa, barang dialokasikan, dan unit bermasalah</small>
            </div>
            <div class="small text-muted">
                Urutan: <strong>{{ strtoupper($sortBy) }} ({{ strtoupper($order) }})</strong>
            </div>
        </div>

        <div class="ac-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-laporan-inventaris">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">
                                <a href="{{ $buildInvSortUrl('kode_alat') }}" class="text-dark text-decoration-none">
                                    Kode Alat {{ $invSortIcon('kode_alat') }}
                                </a>
                            </th>
                            <th>
                                <a href="{{ $buildInvSortUrl('nama_alat') }}" class="text-dark text-decoration-none">
                                    Nama Alat Camping {{ $invSortIcon('nama_alat') }}
                                </a>
                            </th>
                            <th>
                                <a href="{{ $buildInvSortUrl('kategori') }}" class="text-dark text-decoration-none">
                                    Kategori {{ $invSortIcon('kategori') }}
                                </a>
                            </th>
                            <th>Kondisi</th>
                            <th class="text-end">
                                <a href="{{ $buildInvSortUrl('tarif_sewa') }}" class="text-dark text-decoration-none">
                                    Tarif/Hari {{ $invSortIcon('tarif_sewa') }}
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="{{ $buildInvSortUrl('stok_fisik_total') }}" class="text-dark text-decoration-none">
                                    Stok Total {{ $invSortIcon('stok_fisik_total') }}
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="{{ $buildInvSortUrl('stok_siap_sewa') }}" class="text-dark text-decoration-none">
                                    Siap Sewa {{ $invSortIcon('stok_siap_sewa') }}
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="{{ $buildInvSortUrl('stok_sedang_disewa') }}" class="text-dark text-decoration-none">
                                    Sedang Disewa {{ $invSortIcon('stok_sedang_disewa') }}
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="{{ $buildInvSortUrl('stok_dialokasikan') }}" class="text-dark text-decoration-none">
                                    Dialokasikan {{ $invSortIcon('stok_dialokasikan') }}
                                </a>
                            </th>
                            <th class="text-center pe-4">
                                <a href="{{ $buildInvSortUrl('stok_bermasalah') }}" class="text-dark text-decoration-none">
                                    Bermasalah {{ $invSortIcon('stok_bermasalah') }}
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventarisList as $item)
                            <tr>
                                <td class="ps-4">
                                    <span class="font-monospace fw-bold text-dark">{{ $item->kode_alat }}</span>
                                </td>
                                <td>
                                    <strong class="text-dark">{{ $item->nama_alat }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $item->kategori }}</span>
                                </td>
                                <td>
                                    <x-status-badge :status="$item->kondisi" />
                                </td>
                                <td class="text-end font-monospace">
                                    Rp {{ number_format((float) $item->tarif_sewa, 0, ',', '.') }}
                                </td>
                                <td class="text-center font-monospace fw-bold fs-6">
                                    {{ $item->stok_fisik_total }}
                                </td>
                                <td class="text-center">
                                    @if($item->stok_siap_sewa > 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace px-2 py-1">
                                            {{ $item->stok_siap_sewa }} unit
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace px-2 py-1">0 unit</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->stok_sedang_disewa > 0)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle font-monospace px-2 py-1">
                                            {{ $item->stok_sedang_disewa }} unit
                                        </span>
                                    @else
                                        <span class="text-muted small">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->stok_dialokasikan > 0)
                                        <span class="badge bg-info-subtle text-info border border-info-subtle font-monospace px-2 py-1">
                                            {{ $item->stok_dialokasikan }} unit
                                        </span>
                                    @else
                                        <span class="text-muted small">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    @if($item->stok_bermasalah > 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace px-2 py-1" title="{{ $item->stok_perlu_perbaikan }} Servis, {{ $item->stok_rusak }} Rusak">
                                            ⚠️ {{ $item->stok_bermasalah }} unit
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border font-monospace">0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <div class="mb-2" style="font-size: 2rem;">📦</div>
                                    <h6 class="fw-bold">Tidak Ada Data Inventaris</h6>
                                    <p class="small mb-0">Tidak ditemukan alat outdoor yang cocok dengan kriteria filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <!-- Baris Rekapitulasi Footer Halaman -->
                    @if($inventarisList->isNotEmpty())
                        <tfoot class="table-light border-top">
                            <tr class="fw-bold">
                                <td colspan="5" class="ps-4 text-end">TOTAL REKAPITULASI INVENTARIS:</td>
                                <td class="text-center font-monospace text-dark fs-6">{{ $rekapStokFisik }}</td>
                                <td class="text-center font-monospace text-success fs-6">{{ $rekapSiapSewa }}</td>
                                <td class="text-center font-monospace text-warning fs-6">{{ $rekapSedangDisewa }}</td>
                                <td class="text-center font-monospace text-primary fs-6">{{ $rekapDialokasikan }}</td>
                                <td class="text-center font-monospace text-danger fs-6 pe-4">{{ $rekapBermasalah }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

@else
    <!-- ==================== TAB 2: RIWAYAT PERGERAKAN & MUTASI STOK ==================== -->

    <!-- Form Filter Tab Mutasi -->
    <div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="ac-card-body p-3">
            <form method="GET" action="{{ request()->url() }}" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="mutasi">

                <div class="col-12 col-md-3">
                    <label for="mutasi_alat" class="form-label small fw-bold text-muted mb-1">Filter Alat</label>
                    <select name="mutasi_alat" id="mutasi_alat" class="form-select form-select-sm">
                        <option value="">Semua Alat Outdoor</option>
                        @foreach($semuaAlatList as $a)
                            <option value="{{ $a->kode_alat }}" {{ request('mutasi_alat') === $a->kode_alat ? 'selected' : '' }}>
                                {{ $a->kode_alat }} &mdash; {{ $a->nama_alat }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="mutasi_jenis" class="form-label small fw-bold text-muted mb-1">Jenis Perubahan</label>
                    <select name="mutasi_jenis" id="mutasi_jenis" class="form-select form-select-sm">
                        <option value="">Semua Jenis Mutasi</option>
                        <option value="Pembelian Supplier" {{ request('mutasi_jenis') === 'Pembelian Supplier' ? 'selected' : '' }}>Pembelian Supplier</option>
                        <option value="Penyerahan Sewa" {{ request('mutasi_jenis') === 'Penyerahan Sewa' ? 'selected' : '' }}>Penyerahan Sewa</option>
                        <option value="Pengembalian Sewa" {{ request('mutasi_jenis') === 'Pengembalian Sewa' ? 'selected' : '' }}>Pengembalian Sewa</option>
                        <option value="Penyesuaian Manual" {{ request('mutasi_jenis') === 'Penyesuaian Manual' ? 'selected' : '' }}>Penyesuaian Manual</option>
                        <option value="Selesai Perbaikan" {{ request('mutasi_jenis') === 'Selesai Perbaikan' ? 'selected' : '' }}>Selesai Perbaikan</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="mutasi_tgl_mulai" class="form-label small fw-bold text-muted mb-1">Tanggal Mulai</label>
                    <input type="date" name="mutasi_tgl_mulai" id="mutasi_tgl_mulai" class="form-control form-control-sm" value="{{ request('mutasi_tgl_mulai') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label for="mutasi_tgl_selesai" class="form-label small fw-bold text-muted mb-1">Tanggal Selesai</label>
                    <input type="date" name="mutasi_tgl_selesai" id="mutasi_tgl_selesai" class="form-control form-control-sm" value="{{ request('mutasi_tgl_selesai') }}">
                </div>
                <div class="col-12 col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">🔍 Filter Mutasi</button>
                    @if(request()->anyFilled(['mutasi_alat', 'mutasi_jenis', 'mutasi_tgl_mulai', 'mutasi_tgl_selesai']))
                        <a href="{{ request()->url() }}?tab=mutasi" class="btn btn-sm btn-outline-secondary" title="Reset Filter">✕</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Riwayat Pergerakan Stok -->
    <div class="ac-card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="ac-card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 px-4 gap-2">
            <div>
                <h3 class="ac-card-title fs-5 fw-bold mb-0 text-dark">🔄 Catatan Riwayat Mutasi Stok Fisik (Masuk & Keluar)</h3>
                <small class="text-muted">Setiap perubahan stok tercatat secara permanen untuk audit integritas inventaris</small>
            </div>
            <span class="badge bg-secondary" style="font-size:0.75rem;">Audit Trail Stok</span>
        </div>

        <div class="ac-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Waktu</th>
                            <th>Kode Alat</th>
                            <th>Nama Alat</th>
                            <th>Jenis Perubahan</th>
                            <th class="text-center">Perubahan</th>
                            <th class="text-center">Sebelum</th>
                            <th class="text-center">Sesudah</th>
                            <th>Alasan / Keterangan</th>
                            <th class="pe-4">Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayatMutasi as $m)
                            <tr>
                                <td class="ps-4 small text-muted font-monospace">
                                    {{ $m->created_at ? \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td>
                                    <span class="font-monospace fw-bold text-dark">{{ $m->kode_alat }}</span>
                                </td>
                                <td>
                                    <strong>{{ $m->alat?->nama_alat ?? '-' }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $m->jenis_perubahan }}</span>
                                </td>
                                <td class="text-center font-monospace fw-bold">
                                    @if($m->jumlah_perubahan > 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">+{{ $m->jumlah_perubahan }} unit</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $m->jumlah_perubahan }} unit</span>
                                    @endif
                                </td>
                                <td class="text-center font-monospace text-muted">{{ $m->stok_sebelum }}</td>
                                <td class="text-center font-monospace fw-bold text-dark">{{ $m->stok_sesudah }}</td>
                                <td class="small">{{ $m->alasan ?? '-' }}</td>
                                <td class="pe-4 small text-muted">
                                    {{ $m->user?->nama_user ?? 'Sistem' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="mb-2" style="font-size: 2rem;">📜</div>
                                    <h6 class="fw-bold">Tidak Ada Catatan Mutasi Stok</h6>
                                    <p class="small mb-0">Belum ada riwayat pergerakan stok untuk kriteria yang dipilih.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($riwayatMutasi->hasPages())
            <div class="ac-card-footer bg-white border-top py-3 px-4 d-flex justify-content-end">
                {{ $riwayatMutasi->links() }}
            </div>
        @endif
    </div>

@endif
@endsection

