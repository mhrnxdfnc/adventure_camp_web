@extends('layouts.app')

@section('title', 'Master Data Supplier')
@section('page-title', 'Pengelolaan Data Supplier')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Master Supplier</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Master Data Supplier & Vendor</h2>
            <p class="text-muted small mb-0">Kelola informasi mitra penyedia dan supplier resmi pengadaan alat camping rental.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.supplier.create') }}" class="btn btn-sm btn-primary shadow-sm d-inline-flex align-items-center gap-1" id="btn-tambah-supplier">
                <span>➕ Tambah Supplier Baru</span>
            </a>
            <a href="{{ route('admin.inventaris.pembelian.index') }}" class="btn btn-sm btn-outline-secondary shadow-sm">
                <span>📦 Riwayat PO Inventaris</span>
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <strong>✓ Berhasil:</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <strong>⚠️ Peringatan:</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Ringkasan Metrik Singkat -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1.25rem;">
                    🏢
                </div>
                <div>
                    <span class="text-muted small d-block">Total Supplier Rekanan</span>
                    <h4 class="mb-0 fw-bold text-dark">{{ $totalSupplier }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1.25rem;">
                    📦
                </div>
                <div>
                    <span class="text-muted small d-block">Pernah Menyuplai Stok</span>
                    <h4 class="mb-0 fw-bold text-dark">{{ $supplierWithPurchasesCount }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info-subtle text-info p-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1.25rem;">
                    🛡️
                </div>
                <div>
                    <span class="text-muted small d-block">Integritas Data</span>
                    <span class="badge bg-light text-dark border mt-1">Proteksi Foreign Key Aktif</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card Tabel Supplier -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-bottom">
        <form action="{{ route('admin.supplier.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0">🔍</span>
                    <input type="text" name="q" value="{{ $search }}" class="form-control border-start-0" placeholder="Cari nama supplier, kontak, telepon, atau alamat..." id="input-search-supplier">
                    @if($search)
                        <a href="{{ route('admin.supplier.index') }}" class="btn btn-outline-secondary" title="Hapus Pencarian">✕</a>
                    @endif
                    <button type="submit" class="btn btn-primary px-3">Cari</button>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-6 text-md-end text-muted small">
                Menampilkan {{ $suppliers->firstItem() ?? 0 }} - {{ $suppliers->lastItem() ?? 0 }} dari {{ $suppliers->total() }} supplier
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small">
                <tr>
                    <th class="ps-3" style="width: 60px;">ID</th>
                    <th>Nama Supplier / Vendor</th>
                    <th>Kontak Person</th>
                    <th>No. Telepon</th>
                    <th>Alamat</th>
                    <th class="text-center">Riwayat Pengadaan</th>
                    <th class="text-end pe-3" style="width: 220px;">Aksi</th>
                </tr>
            </thead>
            <tbody class="small">
                @forelse($suppliers as $supplier)
                    <tr>
                        <td class="ps-3 font-monospace text-muted">#{{ $supplier->id_supplier }}</td>
                        <td>
                            <a href="{{ route('admin.supplier.show', $supplier->id_supplier) }}" class="fw-bold text-dark text-decoration-none">
                                {{ $supplier->nama_supplier }}
                            </a>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                Terdaftar: {{ $supplier->created_at ? $supplier->created_at->format('d M Y') : '—' }}
                            </div>
                        </td>
                        <td>
                            {{ $supplier->kontak_person ?: '—' }}
                        </td>
                        <td class="font-monospace">
                            {{ $supplier->telepon ?: '—' }}
                        </td>
                        <td class="text-muted text-break" style="max-width: 220px;">
                            {{ $supplier->alamat ?: '—' }}
                        </td>
                        <td class="text-center">
                            @if($supplier->pembelian_count > 0)
                                <a href="{{ route('admin.supplier.show', $supplier->id_supplier) }}" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none px-2 py-1" title="Lihat riwayat transaksi PO supplier ini">
                                    📦 {{ $supplier->pembelian_count }} Transaksi PO
                                </a>
                            @else
                                <span class="badge bg-light text-muted border px-2 py-1">Belum Ada PO</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-inline-flex align-items-center gap-1">
                                <!-- Tombol Detail -->
                                <a href="{{ route('admin.supplier.show', $supplier->id_supplier) }}" 
                                   class="btn btn-sm btn-outline-info px-2 py-1 shadow-sm" 
                                   title="Rincian Detail Supplier"
                                   id="btn-show-supplier-{{ $supplier->id_supplier }}">
                                    👁️ Detail
                                </a>

                                <!-- Tombol Edit -->
                                <a href="{{ route('admin.supplier.edit', $supplier->id_supplier) }}" 
                                   class="btn btn-sm btn-outline-primary px-2 py-1 shadow-sm" 
                                   title="Edit Data Supplier"
                                   id="btn-edit-supplier-{{ $supplier->id_supplier }}">
                                    ✏️ Edit
                                </a>

                                <!-- Tombol Hapus -->
                                @if($supplier->pembelian_count > 0)
                                    <button class="btn btn-sm btn-outline-secondary px-2 py-1 opacity-75" 
                                            disabled 
                                            title="Supplier tidak dapat dihapus karena memiliki {{ $supplier->pembelian_count }} riwayat pembelian inventaris.">
                                        🔒 Terkunci
                                    </button>
                                @else
                                    <form action="{{ route('admin.supplier.destroy', $supplier->id_supplier) }}" 
                                          method="POST" 
                                          class="d-inline" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus data supplier [{{ $supplier->nama_supplier }}]?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-outline-danger px-2 py-1 shadow-sm" 
                                                title="Hapus Supplier"
                                                id="btn-delete-supplier-{{ $supplier->id_supplier }}">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div style="font-size: 2rem;">🏢</div>
                            <p class="mt-2 mb-1 fw-semibold">Tidak ada data supplier yang ditemukan.</p>
                            @if($search)
                                <p class="small text-muted mb-3">Tidak ada hasil yang cocok dengan kata kunci "<strong>{{ $search }}</strong>".</p>
                                <a href="{{ route('admin.supplier.index') }}" class="btn btn-sm btn-outline-secondary">Reset Pencarian</a>
                            @else
                                <p class="small text-muted mb-3">Belum ada rekanan supplier yang terdaftar di sistem.</p>
                                <a href="{{ route('admin.supplier.create') }}" class="btn btn-sm btn-primary">Tambah Supplier Pertama</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($suppliers->hasPages())
        <div class="card-footer bg-white py-3 border-top d-flex justify-content-end">
            {{ $suppliers->links() }}
        </div>
    @endif
</div>
@endsection
