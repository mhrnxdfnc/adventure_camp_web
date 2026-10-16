@extends('layouts.app')

@section('title', 'Faktur Pembelian Supplier — ' . $pembelian->no_pembelian)
@section('page-title', 'Detail Pembelian Supplier')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.inventaris.index') }}" class="text-decoration-none">Inventaris & Stok</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.inventaris.pembelian.index') }}" class="text-decoration-none">Pembelian Supplier</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $pembelian->no_pembelian }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Faktur Pengadaan Stok Supplier</h2>
            <p class="text-muted small mb-0">Nomor PO: <strong class="font-monospace text-dark">{{ $pembelian->no_pembelian }}</strong> &bull; Supplier: <strong>{{ $pembelian->supplier->nama_supplier }}</strong></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.inventaris.pembelian.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali ke Daftar
            </a>
            <a href="{{ route('admin.inventaris.pembelian.create') }}" class="btn btn-primary btn-sm">
                ➕ Catat Pembelian Baru
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <!-- Informasi Dokumen PO -->
        <div class="ac-card mb-4">
            <div class="ac-card-header bg-light">
                <h3 class="ac-card-title small font-weight-bold mb-0">📄 Ringkasan Transaksi PO</h3>
            </div>
            <div class="ac-card-body p-3">
                <div class="mb-2 pb-2 border-bottom small">
                    <span class="text-muted d-block">Nomor Pembelian:</span>
                    <strong class="font-monospace text-dark fs-6">{{ $pembelian->no_pembelian }}</strong>
                </div>
                <div class="mb-2 pb-2 border-bottom small">
                    <span class="text-muted d-block">Tanggal Transaksi:</span>
                    <strong class="text-dark">{{ $pembelian->tanggal_pembelian->format('d F Y') }}</strong>
                </div>
                <div class="mb-2 pb-2 border-bottom small">
                    <span class="text-muted d-block">Admin Pencatat:</span>
                    <span class="text-dark fw-bold">{{ $pembelian->admin->nama_user ?? 'Admin #' . $pembelian->id_admin }}</span>
                    <small class="text-muted d-block">{{ $pembelian->created_at->format('d/m/Y H:i:s') }} WIB</small>
                </div>
                <div class="small">
                    <span class="text-muted d-block">Catatan Pengadaan:</span>
                    <span class="text-dark">{{ $pembelian->catatan ?: '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Informasi Vendor Supplier -->
        <div class="ac-card">
            <div class="ac-card-header bg-light">
                <h3 class="ac-card-title small font-weight-bold mb-0">🏢 Identitas Vendor Supplier</h3>
            </div>
            <div class="ac-card-body p-3 small">
                <div class="mb-2">
                    <span class="text-muted d-block">Nama Perusahaan / Toko:</span>
                    <strong class="text-dark fs-6">{{ $pembelian->supplier->nama_supplier }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">Kontak Person / Telepon:</span>
                    <span class="text-dark">{{ $pembelian->supplier->kontak_person ?: '-' }} ({{ $pembelian->supplier->telepon ?: '-' }})</span>
                </div>
                <div>
                    <span class="text-muted d-block">Alamat Supplier:</span>
                    <span class="text-dark">{{ $pembelian->supplier->alamat ?: '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Rincian Daftar Item Alat yang Dibeli -->
    <div class="col-lg-8">
        <div class="ac-card">
            <div class="ac-card-header">
                <h3 class="ac-card-title">📦 Rincian Item Alat Masuk ({{ $pembelian->detailPembelian->count() }} Jenis Alat)</h3>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Kode Alat</th>
                            <th>Nama Alat Camping</th>
                            <th class="text-center" style="width: 110px;">Kuantitas</th>
                            <th class="text-end" style="width: 170px;">Harga Beli Satuan</th>
                            <th class="text-end" style="width: 170px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pembelian->detailPembelian as $item)
                            <tr>
                                <td>
                                    <strong class="font-monospace text-dark">{{ $item->kode_alat }}</strong>
                                </td>
                                <td>
                                    <span class="text-dark fw-bold d-block">{{ $item->alat->nama_alat ?? $item->kode_alat }}</span>
                                    <span class="badge bg-light text-muted border" style="font-size: 0.72rem;">{{ $item->alat->kategori ?? '-' }}</span>
                                </td>
                                <td class="text-center font-monospace fw-bold">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        +{{ $item->kuantitas }} unit
                                    </span>
                                </td>
                                <td class="text-end font-monospace">
                                    Rp {{ number_format($item->harga_beli_satuan, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end fs-6">Grand Total Pengadaan Supplier:</th>
                            <th class="text-end font-monospace text-success fs-5">
                                {{ $pembelian->formatted_total }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="ac-card-footer bg-light p-3 small text-muted">
                <em>Catatan: Transaksi ini adalah mutasi inventaris stok masuk resmi dan tidak memengaruhi riwayat pesanan sewa pelanggan.</em>
            </div>
        </div>
    </div>
</div>
@endsection
