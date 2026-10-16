@extends('layouts.app')

@section('title', 'Pembelian Stok Supplier — Admin')
@section('page-title', 'Pembelian Stok Inventaris')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.inventaris.index') }}" class="text-decoration-none">Inventaris & Stok</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pembelian Supplier</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Riwayat Pembelian Stok dari Supplier</h2>
            <p class="text-muted small mb-0">Transaksi pengadaan stok inventaris dari vendor/supplier resmi. Terpisah dari riwayat transaksi pesanan pelanggan.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.inventaris.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali ke Inventaris
            </a>
            <a href="{{ route('admin.inventaris.pembelian.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" id="btn-tambah-pembelian">
                <span>➕</span>
                <span>Catat Pembelian Supplier</span>
            </a>
        </div>
    </div>
</div>

<div class="ac-card">
    <div class="ac-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="ac-card-title">Daftar Faktur Pembelian ({{ $pembelianList->total() }} Transaksi)</h3>
            <small class="text-muted">Setiap pembelian menambahkan stok fisik alat secara otomatis</small>
        </div>

        <!-- Filter Form -->
        <form action="{{ route('admin.inventaris.pembelian.index') }}" method="GET" class="d-flex align-items-center flex-wrap gap-2">
            <div class="input-group input-group-sm" style="max-width: 220px;">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari No. PO / Supplier...">
            </div>

            <select name="id_supplier" class="form-select form-select-sm" style="width: auto;">
                <option value="">Semua Supplier</option>
                @foreach($supplierList as $s)
                    <option value="{{ $s->id_supplier }}" {{ (string) $supplierFilter === (string) $s->id_supplier ? 'selected' : '' }}>
                        {{ $s->nama_supplier }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            @if($search !== '' || !empty($supplierFilter))
                <a href="{{ route('admin.inventaris.pembelian.index') }}" class="btn btn-sm btn-outline-danger" title="Reset">&times;</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="table-pembelian-supplier">
            <thead class="table-light small">
                <tr>
                    <th style="width: 170px;">Nomor Pembelian</th>
                    <th style="width: 130px;">Tgl Pembelian</th>
                    <th>Nama Supplier</th>
                    <th>Daftar Alat Dibeli</th>
                    <th class="text-end" style="width: 150px;">Total Pembelian</th>
                    <th>Admin Pencatat</th>
                    <th class="text-center" style="width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pembelianList as $po)
                    <tr>
                        <td>
                            <strong class="font-monospace text-dark d-block">{{ $po->no_pembelian }}</strong>
                        </td>
                        <td>
                            <span class="text-dark">{{ $po->tanggal_pembelian->format('d/m/Y') }}</span>
                        </td>
                        <td>
                            <strong class="text-dark d-block">{{ $po->supplier->nama_supplier ?? 'Supplier #' . $po->id_supplier }}</strong>
                            <small class="text-muted">{{ $po->supplier->telepon ?? '-' }}</small>
                        </td>
                        <td>
                            <ul class="list-unstyled mb-0 small">
                                @foreach($po->detailPembelian as $item)
                                    <li>
                                        • <strong>{{ $item->alat->nama_alat ?? $item->kode_alat }}</strong> 
                                        <span class="badge bg-light text-dark border ms-1">{{ $item->kuantitas }} unit</span>
                                        <span class="text-muted font-monospace">@ Rp {{ number_format($item->harga_beli_satuan, 0, ',', '.') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="text-end font-monospace">
                            <strong class="text-success fs-6">{{ $po->formatted_total }}</strong>
                        </td>
                        <td>
                            <span class="text-dark small d-block">{{ $po->admin->nama_user ?? 'Admin #' . $po->id_admin }}</span>
                            <small class="text-muted" style="font-size: 0.72rem;">{{ $po->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.inventaris.pembelian.show', $po->no_pembelian) }}" 
                               class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm px-2 py-1"
                               id="btn-lihat-po-{{ $po->no_pembelian }}">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="mb-2" style="font-size: 2rem;">🛒</div>
                            <h5 class="fw-bold mb-1">Belum Ada Transaksi Pembelian Supplier</h5>
                            <p class="small mb-0">Gunakan tombol "Catat Pembelian Supplier" untuk mencatat pengadaan stok alat baru dari vendor.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pembelianList->hasPages())
        <div class="ac-card-footer p-3 bg-light border-top">
            {{ $pembelianList->links() }}
        </div>
    @endif
</div>
@endsection
