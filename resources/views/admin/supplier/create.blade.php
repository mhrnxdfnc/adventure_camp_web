@extends('layouts.app')

@section('title', 'Tambah Supplier Baru')
@section('page-title', 'Tambah Supplier')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.supplier.index') }}" class="text-decoration-none">Master Supplier</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tambah Supplier Baru</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Tambah Master Data Supplier</h2>
            <p class="text-muted small mb-0">Daftarkan mitra vendor atau supplier baru penyedia alat camping dan perlengkapan inventaris.</p>
        </div>
        <a href="{{ route('admin.supplier.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
            &larr; Kembali ke Daftar Supplier
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-light py-3 border-bottom">
                <h5 class="card-title h6 mb-0 fw-bold text-dark">📝 Formulir Pendaftaran Rekanan Supplier</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.supplier.store') }}" method="POST" id="form-create-supplier">
                    @csrf

                    <!-- Nama Supplier -->
                    <div class="mb-3">
                        <label for="nama_supplier" class="form-label small fw-bold text-dark">
                            Nama Supplier / Perusahaan <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               name="nama_supplier" 
                               id="nama_supplier" 
                               class="form-control @error('nama_supplier') is-invalid @enderror" 
                               value="{{ old('nama_supplier') }}" 
                               placeholder="Contoh: PT Consina Outdoor Indonesia" 
                               required 
                               maxlength="100">
                        @error('nama_supplier')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text small text-muted">Nama resmi badan usaha atau toko vendor penyuplai barang.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Kontak Person -->
                        <div class="col-md-6">
                            <label for="kontak_person" class="form-label small fw-bold text-dark">
                                Nama Kontak Person (PIC)
                            </label>
                            <input type="text" 
                                   name="kontak_person" 
                                   id="kontak_person" 
                                   class="form-control @error('kontak_person') is-invalid @enderror" 
                                   value="{{ old('kontak_person') }}" 
                                   placeholder="Contoh: Bpk. Bambang Wijaya" 
                                   maxlength="100">
                            @error('kontak_person')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small text-muted">Nama perwakilan pihak supplier yang dapat dihubungi.</div>
                        </div>

                        <!-- No Telepon -->
                        <div class="col-md-6">
                            <label for="telepon" class="form-label small fw-bold text-dark">
                                Nomor Telepon / WhatsApp
                            </label>
                            <input type="text" 
                                   name="telepon" 
                                   id="telepon" 
                                   class="form-control @error('telepon') is-invalid @enderror" 
                                   value="{{ old('telepon') }}" 
                                   placeholder="Contoh: 08123456789 atau 021-889977" 
                                   maxlength="25">
                            @error('telepon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small text-muted">Nomor kontak aktif untuk koordinasi pemesanan stok.</div>
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div class="mb-4">
                        <label for="alamat" class="form-label small fw-bold text-dark">
                            Alamat Kantor / Gudang Supplier
                        </label>
                        <textarea name="alamat" 
                                  id="alamat" 
                                  rows="3" 
                                  class="form-control @error('alamat') is-invalid @enderror" 
                                  placeholder="Contoh: Jl. Raya Outdoor No. 45, Kompleks Pergudangan, Surabaya" 
                                  maxlength="500">{{ old('alamat') }}</textarea>
                        @error('alamat')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text small text-muted">Alamat fisik operasional pengiriman barang dari supplier.</div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.supplier.index') }}" class="btn btn-outline-secondary px-3">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary px-4" id="btn-submit-supplier">
                            💾 Simpan Data Supplier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
