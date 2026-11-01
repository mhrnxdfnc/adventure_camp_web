@extends('layouts.app')

@section('title', 'Edit Supplier — ' . $supplier->nama_supplier)
@section('page-title', 'Edit Supplier')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.supplier.index') }}" class="text-decoration-none">Master Supplier</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit: {{ $supplier->nama_supplier }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Edit Data Supplier</h2>
            <p class="text-muted small mb-0">Perbarui informasi kontak, alamat, atau nama supplier rekanan <strong>{{ $supplier->nama_supplier }}</strong>.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.supplier.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                &larr; Kembali ke Daftar
            </a>
            <a href="{{ route('admin.supplier.show', $supplier->id_supplier) }}" class="btn btn-outline-info btn-sm shadow-sm">
                👁️ Lihat Detail
            </a>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title h6 mb-0 fw-bold text-dark">✏️ Formulir Edit Supplier #{{ $supplier->id_supplier }}</h5>
                <span class="badge bg-secondary-subtle text-dark border font-monospace">Terdaftar: {{ $supplier->created_at ? $supplier->created_at->format('d/m/Y') : '—' }}</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.supplier.update', $supplier->id_supplier) }}" method="POST" id="form-edit-supplier">
                    @csrf
                    @method('PUT')

                    <!-- Nama Supplier -->
                    <div class="mb-3">
                        <label for="nama_supplier" class="form-label small fw-bold text-dark">
                            Nama Supplier / Perusahaan <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               name="nama_supplier" 
                               id="nama_supplier" 
                               class="form-control @error('nama_supplier') is-invalid @enderror" 
                               value="{{ old('nama_supplier', $supplier->nama_supplier) }}" 
                               required 
                               maxlength="100">
                        @error('nama_supplier')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
                                   value="{{ old('kontak_person', $supplier->kontak_person) }}" 
                                   maxlength="100">
                            @error('kontak_person')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                   value="{{ old('telepon', $supplier->telepon) }}" 
                                   maxlength="25">
                            @error('telepon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                  maxlength="500">{{ old('alamat', $supplier->alamat) }}</textarea>
                        @error('alamat')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.supplier.index') }}" class="btn btn-outline-secondary px-3">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary px-4" id="btn-update-supplier">
                            💾 Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
