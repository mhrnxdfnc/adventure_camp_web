@extends('layouts.auth')

@section('title', 'Masuk ke Sistem')

@section('content')
<form action="{{ route('login') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label for="role" class="form-label font-weight-bold" style="font-size:0.875rem;">Login Sebagai</label>
        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
            <option value="Admin" {{ old('role') === 'Admin' ? 'selected' : '' }}>Admin (Operasional & Inventaris)</option>
            <option value="Pengguna" {{ old('role') === 'Pengguna' ? 'selected' : '' }}>Pengguna (Penyewa Alat Camping)</option>
            <option value="Pemilik" {{ old('role') === 'Pemilik' ? 'selected' : '' }}>Pemilik (Pengawasan Bisnis Read-Only)</option>
        </select>
        @error('role')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text text-muted" style="font-size:0.75rem;">Hak akses akan disesuaikan secara ketat sesuai peran akun.</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="username" class="form-label font-weight-bold" style="font-size:0.875rem;">Username</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </span>
            <input type="text" class="form-control border-start-0 @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" placeholder="Masukkan username" required autocomplete="username" autofocus>
            @error('username')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label font-weight-bold" style="font-size:0.875rem;">Password</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </span>
            <input type="password" class="form-control border-start-0 @error('password') is-invalid @enderror" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label text-muted" for="remember" style="font-size:0.8rem;">
                Ingat saya
            </label>
        </div>
        <a href="{{ url('/ui-showcase') }}" class="text-decoration-none text-success" style="font-size:0.8rem;">
            Lihat Showcase UI &rarr;
        </a>
    </div>

    <button type="submit" class="btn btn-ac-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
        <span>Masuk ke Dashboard</span>
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
        </svg>
    </button>

    <div class="text-center mt-4 pt-3 border-top">
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            Belum punya akun penyewa? 
            <a href="{{ route('register') }}" class="text-success fw-bold text-decoration-none">Daftar Sekarang &rarr;</a>
        </p>
    </div>
</form>
@endsection
