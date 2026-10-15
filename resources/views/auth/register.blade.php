@extends('layouts.auth')

@section('title', 'Pendaftaran Pengguna')

@section('content')
<div class="mb-3 text-center">
    <h3 class="font-weight-bold" style="color:var(--ac-primary); font-size:1.25rem;">Registrasi Akun Penyewa</h3>
    <p class="text-muted small">Lengkapi data diri untuk memesan dan menyewa alat camping</p>
</div>

<form action="{{ route('register') }}" method="POST">
    @csrf

    <!-- Nama Pelanggan -->
    <div class="mb-3">
        <label for="nama_pelanggan" class="form-label font-weight-bold small">Nama Lengkap</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </span>
            <input type="text" class="form-control border-start-0 @error('nama_pelanggan') is-invalid @enderror" id="nama_pelanggan" name="nama_pelanggan" value="{{ old('nama_pelanggan') }}" placeholder="Contoh: Rian Hidayat" required autofocus>
            @error('nama_pelanggan')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Nomor Telepon -->
    <div class="mb-3">
        <label for="nomor_telepon" class="form-label font-weight-bold small">Nomor Telepon / WhatsApp</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
            </span>
            <input type="tel" class="form-control border-start-0 @error('nomor_telepon') is-invalid @enderror" id="nomor_telepon" name="nomor_telepon" value="{{ old('nomor_telepon') }}" placeholder="Contoh: 081234567890" required>
            @error('nomor_telepon')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-text text-muted" style="font-size:0.75rem;">Digunakan untuk konfirmasi pesanan dan informasi pengambilan alat.</div>
    </div>

    <!-- Alamat Domisili -->
    <div class="mb-3">
        <label for="alamat" class="form-label font-weight-bold small">Alamat Domisili</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </span>
            <input type="text" class="form-control border-start-0 @error('alamat') is-invalid @enderror" id="alamat" name="alamat" value="{{ old('alamat') }}" placeholder="Contoh: Jl. Soekarno Hatta No. 45, Malang" required>
            @error('alamat')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Username Akun -->
    <div class="mb-3">
        <label for="username" class="form-label font-weight-bold small">Username Login</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </span>
            <input type="text" class="form-control border-start-0 @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" placeholder="Minimal 4 karakter" required autocomplete="username">
            @error('username')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Password -->
    <div class="mb-3">
        <label for="password" class="form-label font-weight-bold small">Password</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </span>
            <input type="password" class="form-control border-start-0 @error('password') is-invalid @enderror" id="password" name="password" placeholder="Minimal 8 karakter" required autocomplete="new-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Konfirmasi Password -->
    <div class="mb-4">
        <label for="password_confirmation" class="form-label font-weight-bold small">Ulangi Password</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </span>
            <input type="password" class="form-control border-start-0" id="password_confirmation" name="password_confirmation" placeholder="Ketik ulang password" required autocomplete="new-password">
        </div>
    </div>

    <button type="submit" class="btn btn-ac-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
        <span>Daftar Akun Pengguna</span>
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
        </svg>
    </button>

    <div class="text-center mt-4 pt-3 border-top">
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            Sudah memiliki akun? 
            <a href="{{ route('login') }}" class="text-success fw-bold text-decoration-none">Masuk di sini &rarr;</a>
        </p>
    </div>
</form>
@endsection
