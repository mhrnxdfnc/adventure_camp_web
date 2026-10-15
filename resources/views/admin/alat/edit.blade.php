@extends('layouts.app')

@section('title', 'Ubah Data Alat — ' . $alat->nama_alat)
@section('page-title', 'Edit Data Alat')

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.alat.index') }}" class="btn btn-sm btn-ac-outline d-inline-flex align-items-center gap-1">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
        </svg>
        <span>Kembali ke Daftar Alat</span>
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="ac-card">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="ac-card-title">Perbarui Data Alat</h3>
                    <small class="text-muted">ID Master: <strong class="font-monospace text-primary">{{ $alat->kode_alat }}</strong></small>
                </div>
                <x-status-badge :status="$alat->kondisi" />
            </div>

            <div class="ac-card-body">
                <form action="{{ route('admin.alat.update', $alat->kode_alat) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <!-- Kode Alat (Read-Only) -->
                        <div class="col-12 col-md-4">
                            <label class="form-label font-weight-bold small text-muted">Kode Alat (Primary Key)</label>
                            <input type="text" class="form-control font-monospace bg-light" value="{{ $alat->kode_alat }}" readonly disabled>
                            <div class="form-text text-muted" style="font-size:0.75rem;">Kode alat tidak dapat diubah demi integritas relasi.</div>
                        </div>

                        <!-- Nama Alat -->
                        <div class="col-12 col-md-8">
                            <label for="nama_alat" class="form-label font-weight-bold small">Nama Perlengkapan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nama_alat') is-invalid @enderror" id="nama_alat" name="nama_alat" value="{{ old('nama_alat', $alat->nama_alat) }}" maxlength="100" required>
                            @error('nama_alat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Kategori -->
                        <div class="col-12 col-md-6">
                            <label for="kategori" class="form-label font-weight-bold small">Kategori Perlengkapan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('kategori') is-invalid @enderror" id="kategori" name="kategori" value="{{ old('kategori', $alat->kategori) }}" maxlength="50" required list="kategori_datalist">
                            <datalist id="kategori_datalist">
                                <option value="Tenda">
                                <option value="Perlengkapan Tidur">
                                <option value="Tas">
                                <option value="Peralatan Masak">
                                <option value="Penerangan">
                                <option value="Aksesoris Outdoor">
                            </datalist>
                            @error('kategori')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Kondisi -->
                        <div class="col-12 col-md-6">
                            <label for="kondisi" class="form-label font-weight-bold small">Kondisi Fisik Alat <span class="text-danger">*</span></label>
                            <select class="form-select @error('kondisi') is-invalid @enderror" id="kondisi" name="kondisi" required>
                                <option value="Baik" {{ old('kondisi', $alat->kondisi) === 'Baik' ? 'selected' : '' }}>Baik (Siap Sewa)</option>
                                <option value="Perlu Perbaikan" {{ old('kondisi', $alat->kondisi) === 'Perlu Perbaikan' ? 'selected' : '' }}>Perlu Perbaikan (Pemeliharaan)</option>
                                <option value="Rusak" {{ old('kondisi', $alat->kondisi) === 'Rusak' ? 'selected' : '' }}>Rusak (Afkir/Tidak Siap Sewa)</option>
                            </select>
                            @error('kondisi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Stok Fisik -->
                        <div class="col-12 col-md-6">
                            <label for="stok" class="form-label font-weight-bold small">Stok Fisik Tersedia <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('stok') is-invalid @enderror" id="stok" name="stok" value="{{ old('stok', $alat->stok) }}" min="0" required>
                            @error('stok')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text text-muted" style="font-size:0.75rem;">Ubah ke 0 jika alat sedang tidak tersedia.</div>
                            @enderror
                        </div>

                        <!-- Tarif Sewa -->
                        <div class="col-12 col-md-6">
                            <label for="tarif_sewa" class="form-label font-weight-bold small">Tarif Sewa per Hari (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="number" class="form-control @error('tarif_sewa') is-invalid @enderror" id="tarif_sewa" name="tarif_sewa" value="{{ old('tarif_sewa', (int)$alat->tarif_sewa) }}" min="1" step="500" required>
                                @error('tarif_sewa')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <span class="text-muted small">Didaftarkan: {{ $alat->created_at ? $alat->created_at->format('d M Y H:i') : '—' }}</span>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.alat.index') }}" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-ac-primary px-4">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
