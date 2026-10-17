@extends('layouts.app')

@section('title', 'Tambah Akun Pengguna — Admin')
@section('page-title', 'Tambah Akun Baru')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}" class="text-decoration-none">Manajemen Pengguna</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tambah Akun</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Tambah Akun Pengguna Baru</h2>
            <p class="text-muted small mb-0">Daftarkan akun baru ke dalam sistem Adventure Camp Rental dengan peran yang sesuai.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
            &larr; Kembali ke Daftar Pengguna
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="ac-card">
            <div class="ac-card-header bg-light">
                <h3 class="ac-card-title small font-weight-bold mb-0">📋 Formulir Registrasi Akun</h3>
            </div>
            <div class="ac-card-body p-4">
                <form action="{{ route('admin.users.store') }}" method="POST" id="form-tambah-user">
                    @csrf

                    <!-- Nama Lengkap -->
                    <div class="mb-3">
                        <label for="nama_user" class="form-label small fw-bold text-dark">Nama Lengkap Pengguna <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="nama_user" 
                               id="nama_user" 
                               class="form-control @error('nama_user') is-invalid @enderror" 
                               value="{{ old('nama_user') }}" 
                               placeholder="Contoh: Rian Anggara" 
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
                               value="{{ old('username') }}" 
                               placeholder="Contoh: rian_outdoor" 
                               required 
                               minlength="4" 
                               maxlength="50">
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Hanya huruf, angka, minus (-), titik (.), dan garis bawah (_). Minimal 4 karakter.</small>
                    </div>

                    <!-- Peran Akun (Role) -->
                    <div class="mb-3">
                        <label for="role" class="form-label small fw-bold text-dark">Peran Hak Akses (Role) <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required onchange="toggleCustomerFields(this.value)">
                            <option value="">-- Pilih Peran Akun --</option>
                            <option value="Admin" {{ old('role') === 'Admin' ? 'selected' : '' }}>Admin (Pengelolaan operasional penuh & inventaris)</option>
                            <option value="Pemilik" {{ old('role') === 'Pemilik' ? 'selected' : '' }}>Pemilik (Strictly Read-Only monitoring & laporan)</option>
                            <option value="Pengguna" {{ old('role', 'Pengguna') === 'Pengguna' ? 'selected' : '' }}>Pengguna / Penyewa (Akses katalog & pemesanan alat)</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Bagian Identitas Pelanggan Khusus Role Pengguna -->
                    <div id="section-pelanggan" class="p-3 bg-light rounded border mb-3 {{ old('role', 'Pengguna') === 'Pengguna' ? '' : 'd-none' }}">
                        <h4 class="h6 font-weight-bold text-dark mb-2">⛺ Hubungkan Data Profil Pelanggan</h4>
                        <p class="small text-muted mb-3">
                            Pelanggan on-site toko tidak otomatis dibuatkan akun login. Jika ingin membuatkan akun untuk pelanggan on-site yang sudah terdaftar, Anda dapat memilihnya di bawah ini atau mengisi kontak baru.
                        </p>

                        @if(isset($pelangganOnsite) && $pelangganOnsite->isNotEmpty())
                            <div class="mb-3">
                                <label for="id_pelanggan_onsite" class="form-label small fw-bold text-dark">Pilih Pelanggan Toko Terdaftar (Opsional)</label>
                                <select name="id_pelanggan_onsite" id="id_pelanggan_onsite" class="form-select form-select-sm">
                                    <option value="">-- Buat Profil Baru (Atau isi form kontak di bawah) --</option>
                                    @foreach($pelangganOnsite as $p)
                                        <option value="{{ $p->id_pelanggan }}" {{ old('id_pelanggan_onsite') == $p->id_pelanggan ? 'selected' : '' }}>
                                            {{ $p->nama_pelanggan }} — {{ $p->nomor_telepon }} ({{ Str::limit($p->alamat, 35) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label for="nomor_telepon" class="form-label small fw-bold text-dark">Nomor Telepon</label>
                                <input type="text" name="nomor_telepon" id="nomor_telepon" class="form-control form-control-sm font-monospace" value="{{ old('nomor_telepon') }}" placeholder="Contoh: 081234567890">
                            </div>
                            <div class="col-sm-6">
                                <label for="alamat" class="form-label small fw-bold text-dark">Alamat Domisili</label>
                                <input type="text" name="alamat" id="alamat" class="form-control form-control-sm" value="{{ old('alamat') }}" placeholder="Contoh: Jl. Merdeka No. 12, Bandung">
                            </div>
                        </div>
                    </div>

                    <!-- Status Keaktifan Akun -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Status Akun <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_aktif" id="status_aktif_1" value="1" {{ old('status_aktif', '1') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label small fw-bold text-success" for="status_aktif_1">
                                    ✓ Aktif (Dapat login ke sistem)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_aktif" id="status_aktif_0" value="0" {{ old('status_aktif') === '0' ? 'checked' : '' }}>
                                <label class="form-check-label small fw-bold text-danger" for="status_aktif_0">
                                    ✕ Nonaktif (Akses masuk diblokir)
                                </label>
                            </div>
                        </div>
                        @error('status_aktif')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-4">

                    <!-- Password Baru -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label for="password" class="form-label small fw-bold text-dark">Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   required 
                                   minlength="8" 
                                   placeholder="Minimal 8 karakter">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="password_confirmation" class="form-label small fw-bold text-dark">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="password_confirmation" 
                                   class="form-control" 
                                   required 
                                   minlength="8" 
                                   placeholder="Ulangi password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btn-submit-user">
                            Simpan Akun Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCustomerFields(role) {
    const sec = document.getElementById('section-pelanggan');
    if (!sec) return;
    if (role === 'Pengguna') {
        sec.classList.remove('d-none');
    } else {
        sec.classList.add('d-none');
    }
}
</script>
@endsection
