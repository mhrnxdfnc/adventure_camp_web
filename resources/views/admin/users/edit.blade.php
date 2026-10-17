@extends('layouts.app')

@section('title', 'Edit Akun Pengguna — ' . $user->username)
@section('page-title', 'Edit Akun Pengguna')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}" class="text-decoration-none">Manajemen Pengguna</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $user->username }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Edit Akun Pengguna</h2>
            <p class="text-muted small mb-0">Perbarui data profil, hak akses peran, status aktif, atau atur ulang password akun <strong>{{ $user->username }}</strong>.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
            &larr; Kembali ke Daftar Pengguna
        </a>
    </div>
</div>

@php
    $isSelf = ((int) $user->id_user === (int) Auth::id());
@endphp

@if($isSelf)
    <div class="alert alert-warning border d-flex align-items-center gap-2 mb-4" role="alert">
        <span style="font-size: 1.25rem;">⚠️</span>
        <div class="small">
            <strong>Proteksi Akun Sendiri:</strong> Anda sedang mengelola akun Administrator Anda sendiri. Peran dan status keaktifan dilindungi demi menjamin kesinambungan hak akses sistem.
        </div>
    </div>
@endif

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="ac-card">
            <div class="ac-card-header bg-light d-flex justify-content-between align-items-center">
                <h3 class="ac-card-title small font-weight-bold mb-0">✏️ Formulir Pembaruan Akun #{{ $user->id_user }}</h3>
                <span class="badge bg-secondary-subtle text-dark border font-monospace">Terdaftar: {{ $user->created_at ? $user->created_at->format('d/m/Y') : '-' }}</span>
            </div>
            <div class="ac-card-body p-4">
                <form action="{{ route('admin.users.update', $user->id_user) }}" method="POST" id="form-edit-user">
                    @csrf
                    @method('PUT')

                    <!-- Nama Lengkap -->
                    <div class="mb-3">
                        <label for="nama_user" class="form-label small fw-bold text-dark">Nama Lengkap Pengguna <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="nama_user" 
                               id="nama_user" 
                               class="form-control @error('nama_user') is-invalid @enderror" 
                               value="{{ old('nama_user', $user->nama_user) }}" 
                               required 
                               maxlength="100">
                        @error('nama_user')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label small fw-bold text-dark">Username <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="username" 
                               id="username" 
                               class="form-control font-monospace @error('username') is-invalid @enderror" 
                               value="{{ old('username', $user->username) }}" 
                               required 
                               minlength="4" 
                               maxlength="50">
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Hanya huruf, angka, titik, minus, dan garis bawah. Harus unik di seluruh sistem.</small>
                    </div>

                    <!-- Peran Akun (Role) -->
                    <div class="mb-3">
                        <label for="role" class="form-label small fw-bold text-dark">Peran Hak Akses (Role) <span class="text-danger">*</span></label>
                        @if($isSelf)
                            <input type="text" class="form-control bg-light" value="Admin (Terkunci untuk akun sendiri)" disabled>
                            <input type="hidden" name="role" value="Admin">
                            <small class="text-muted" style="font-size: 0.72rem;">Peran tidak dapat diubah agar akses administrasi Anda tidak hilang.</small>
                        @else
                            <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                                <option value="Admin" {{ old('role', $user->normalized_role) === 'Admin' ? 'selected' : '' }}>Admin (Pengelolaan operasional penuh & inventaris)</option>
                                <option value="Pemilik" {{ old('role', $user->normalized_role) === 'Pemilik' ? 'selected' : '' }}>Pemilik (Strictly Read-Only laporan keuangan & inventaris)</option>
                                <option value="Pengguna" {{ old('role', $user->normalized_role) === 'Pengguna' ? 'selected' : '' }}>Pengguna / Penyewa (Akses katalog & pemesanan alat)</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <!-- Status Keaktifan Akun -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Status Keaktifan Akun <span class="text-danger">*</span></label>
                        @if($isSelf)
                            <div class="p-2 bg-light rounded border small">
                                <span class="badge bg-success">Aktif</span> (Akun Anda sendiri wajib tetap aktif)
                            </div>
                            <input type="hidden" name="status_aktif" value="1">
                        @else
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status_aktif" id="status_aktif_1" value="1" {{ old('status_aktif', (string)$user->status_aktif) === '1' ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold text-success" for="status_aktif_1">
                                        ✓ Aktif (Dapat masuk ke aplikasi)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status_aktif" id="status_aktif_0" value="0" {{ old('status_aktif', (string)$user->status_aktif) === '0' ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold text-danger" for="status_aktif_0">
                                        ✕ Nonaktif (Akses masuk diblokir)
                                    </label>
                                </div>
                            </div>
                            @error('status_aktif')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <hr class="my-4">

                    <!-- Reset Password (Opsional) -->
                    <div class="p-3 bg-light rounded border mb-4">
                        <h4 class="h6 font-weight-bold text-dark mb-1">🔑 Reset Password Pengguna (Opsional)</h4>
                        <p class="small text-muted mb-3">Biarkan kedua kolom password di bawah ini <strong>kosong</strong> jika tidak ingin mengubah password akun ini.</p>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="password" class="form-label small fw-bold text-dark">Password Baru</label>
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       minlength="8" 
                                       placeholder="Kosongkan jika tetap">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="password_confirmation" class="form-label small fw-bold text-dark">Konfirmasi Password Baru</label>
                                <input type="password" 
                                       name="password_confirmation" 
                                       id="password_confirmation" 
                                       class="form-control" 
                                       minlength="8" 
                                       placeholder="Ulangi password baru">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btn-submit-update-user">
                            Simpan Perubahan Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
