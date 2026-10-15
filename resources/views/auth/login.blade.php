@extends('layouts.auth')

@section('title', 'Masuk ke Sistem')

@section('content')
<form action="#" method="POST" onsubmit="event.preventDefault(); window.location.href='{{ url('/admin/dashboard') }}';">
    @csrf

    <div class="mb-3">
        <label for="role" class="form-label font-weight-bold" style="font-size:0.875rem;">Login Sebagai</label>
        <select class="form-select" id="role" name="role" required>
            <option value="Admin">Admin (Operasional & Inventaris)</option>
            <option value="Pengguna">Pengguna (Penyewa Alat Camping)</option>
            <option value="Pemilik">Pemilik (Pengawasan Bisnis Read-Only)</option>
        </select>
        <div class="form-text text-muted" style="font-size:0.75rem;">Hak akses akan disesuaikan secara ketat sesuai peran akun.</div>
    </div>

    <div class="mb-3">
        <label for="username" class="form-label font-weight-bold" style="font-size:0.875rem;">Username</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </span>
            <input type="text" class="form-control border-start-0" id="username" name="username" placeholder="Masukkan username" required autocomplete="username">
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
            <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember">
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
        <p class="text-muted mb-0" style="font-size:0.8rem;">
            Belum punya akun penyewa? 
            <span class="text-muted fw-bold" title="Pendaftaran akun mandiri akan dibuka pada tahap autentikasi">Daftar Pelanggan (Tahap 2)</span>
        </p>
    </div>
</form>
@endsection
