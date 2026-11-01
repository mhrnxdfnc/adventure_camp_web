@extends('layouts.app')

@section('title', 'Detail Supplier — ' . $supplier->nama_supplier)
@section('page-title', 'Detail Supplier')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.supplier.index') }}" class="text-decoration-none">Master Supplier</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $supplier->nama_supplier }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Rincian Mitra: {{ $supplier->nama_supplier }}</h2>
            <p class="text-muted small mb-0">Informasi kontak, profil rekanan, dan histori pengadaan pembelian inventaris alat camping.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.supplier.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                &larr; Kembali ke Daftar
            </a>
            <a href="{{ route('admin.supplier.edit', $supplier->id_supplier) }}" class="btn btn-outline-primary btn-sm shadow-sm" id="btn-edit-supplier">
                ✏️ Edit Supplier
            </a>
            <a href="{{ route('admin.inventaris.pembelian.create') }}" class="btn btn-success btn-sm shadow-sm">
                ➕ Catat Pembelian Stok (PO)
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

<div class="row g-4">
    <!-- Profil Supplier (Kolom Kiri) -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <div class="avatar-lg mx-auto mb-2 d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-bold" style="width: 64px; height: 64px; font-size: 1.5rem;">
                        🏢
                    </div>
                    <h5 class="fw-bold mb-1 text-dark">{{ $supplier->nama_supplier }}</h5>
                    <span class="badge bg-light text-muted border font-monospace">Supplier ID #{{ $supplier->id_supplier }}</span>
                </div>

                <hr class="my-3">

                <div class="small">
                    <div class="mb-2">
                        <span class="text-muted d-block">Kontak Person (PIC):</span>
                        <span class="fw-semibold text-dark">{{ $supplier->kontak_person ?: '— Belum diatur' }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted d-block">Nomor Telepon:</span>
                        <span class="fw-semibold font-monospace text-dark">{{ $supplier->telepon ?: '— Belum diatur' }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted d-block">Alamat Lengkap:</span>
                        <span class="fw-semibold text-dark">{{ $supplier->alamat ?: '— Belum diatur' }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted d-block">Terdaftar Sejak:</span>
                        <span class="fw-semibold text-dark">{{ $supplier->created_at ? $supplier->created_at->format('d M Y, H:i') : '—' }}</span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Ringkasan Statistik Pengadaan -->
                <div class="bg-light rounded p-3 text-center small mb-3">
                    <div class="text-muted mb-1">Total Nilai Pengadaan Disuplai</div>
                    <div class="h5 fw-bold text-success mb-0">
                        Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}
                    </div>
                    <div class="text-muted mt-1" style="font-size:0.75rem;">
                        Dari {{ $totalPembelianCount }} transaksi Purchase Order (PO)
                    </div>
                </div>

                <!-- Tombol Hapus / Proteksi -->
                @if($totalPembelianCount > 0)
                    <div class="alert alert-secondary small mb-0 p-2 text-center border-0">
                        🔒 Supplier ini memiliki data historis pengadaan sehingga dilindungi dari penghapusan fisik.
                    </div>
                @else
                    <form action="{{ route('admin.supplier.destroy', $supplier->id_supplier) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data supplier [{{ $supplier->nama_supplier }}]?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100" id="btn-delete-supplier">
                            🗑️ Hapus Supplier
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Riwayat Pengadaan Inventaris (Kolom Kanan) -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="card-title h6 mb-0 fw-bold text-dark">
                    📦 Riwayat Transaksi Pengadaan Pembelian (PO)
                </h5>
                <span class="badge bg-light text-muted border font-monospace">Total: {{ $totalPembelianCount }} PO</span>
            </div>
            <div class="card-body p-0">
                @if($supplier->pembelian && $supplier->pembelian->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th class="ps-3">No. PO & Tanggal</th>
                                    <th>Item Alat Camping</th>
                                    <th>Total Nilai</th>
                                    <th>Dicatat Oleh</th>
                                    <th class="pe-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @foreach($supplier->pembelian as $pembelian)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="font-monospace fw-bold text-dark">{{ $pembelian->no_pembelian }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                📅 {{ \Carbon\Carbon::parse($pembelian->tanggal_pembelian)->format('d M Y') }}
                                            </div>
                                        </td>
                                        <td>
                                            @if($pembelian->detailPembelian && $pembelian->detailPembelian->count() > 0)
                                                <ul class="list-unstyled mb-0" style="font-size: 0.8rem;">
                                                    @foreach($pembelian->detailPembelian as $detail)
                                                        <li>
                                                            • <strong>{{ $detail->alat->nama_alat ?? $detail->kode_alat }}</strong> 
                                                            <span class="text-muted">({{ $detail->kuantitas }} unit @ Rp {{ number_format($detail->harga_beli_satuan, 0, ',', '.') }})</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-muted fst-italic">Tanpa rincian item</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold text-success text-nowrap">
                                            {{ $pembelian->formatted_total }}
                                        </td>
                                        <td class="text-muted">
                                            {{ $pembelian->admin->nama_user ?? 'Admin' }}
                                        </td>
                                        <td class="pe-3 text-muted text-break" style="max-width: 180px; font-size: 0.75rem;">
                                            {{ $pembelian->catatan ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <div style="font-size: 2rem;">📦</div>
                        <p class="mt-2 mb-1 fw-semibold">Belum Ada Riwayat Pengadaan</p>
                        <p class="small text-muted mb-3">Belum ada transaksi pembelian stok inventaris yang tercatat dari supplier ini.</p>
                        <a href="{{ route('admin.inventaris.pembelian.create') }}" class="btn btn-sm btn-primary">
                            ➕ Buat PO Pembelian Pertama
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
