@extends('layouts.app')

@section('title', 'Manajemen Pengguna — Admin')
@section('page-title', 'Manajemen Akun Pengguna')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Manajemen Pengguna</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Manajemen Akun Pengguna</h2>
            <p class="text-muted small mb-0">Kelola akun aplikasi, peran hak akses, reset password, dan status keaktifan pengguna secara aman.</p>
        </div>
        <div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" id="btn-tambah-user">
                <span>➕</span>
                <span>Tambah Akun Baru</span>
            </a>
        </div>
    </div>
</div>

<!-- 4 Kartu Metrik Ringkasan Akun -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Total Akun</span>
                <div class="ac-stat-icon-wrap" style="background-color: #e0f2fe; color: #0369a1;">👥</div>
            </div>
            <div class="ac-stat-value font-monospace">{{ $totalUsers }}</div>
            <div class="ac-stat-sub">{{ $totalAktif }} aktif &bull; {{ $totalNonaktif }} nonaktif</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Administrator</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #b45309;">🛡️</div>
            </div>
            <div class="ac-stat-value font-monospace text-primary">{{ $totalAdmin }}</div>
            <div class="ac-stat-sub">Akses operasional penuh</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Pemilik (Owner)</span>
                <div class="ac-stat-icon-wrap" style="background-color: #f1f5f9; color: #475569;">💼</div>
            </div>
            <div class="ac-stat-value font-monospace text-dark">{{ $totalPemilik }}</div>
            <div class="ac-stat-sub">Strictly read-only monitoring</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Pengguna / Penyewa</span>
                <div class="ac-stat-icon-wrap" style="background-color: #dcfce7; color: #15803d;">⛺</div>
            </div>
            <div class="ac-stat-value font-monospace text-success">{{ $totalPengguna }}</div>
            <div class="ac-stat-sub">Akses katalog & pemesanan</div>
        </div>
    </div>
</div>

<!-- Card Tabel Pengguna -->
<div class="ac-card">
    <div class="ac-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="ac-card-title">Daftar Akun Pengguna</h3>
            <small class="text-muted">Total {{ $users->total() }} akun terdaftar dalam sistem</small>
        </div>

        <!-- Filter Form -->
        <form action="{{ route('admin.users.index') }}" method="GET" class="d-flex align-items-center flex-wrap gap-2">
            <div class="input-group input-group-sm" style="max-width: 220px;">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama / username...">
            </div>

            <select name="role" class="form-select form-select-sm" style="width: auto;">
                <option value="">Semua Peran</option>
                <option value="Admin" {{ $selectedRole === 'Admin' ? 'selected' : '' }}>Admin</option>
                <option value="Pemilik" {{ $selectedRole === 'Pemilik' ? 'selected' : '' }}>Pemilik</option>
                <option value="Pengguna" {{ $selectedRole === 'Pengguna' ? 'selected' : '' }}>Pengguna</option>
            </select>

            <select name="status" class="form-select form-select-sm" style="width: auto;">
                <option value="">Semua Status</option>
                <option value="1" {{ $selectedStatus === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ $selectedStatus === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>

            <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            @if($search !== '' || $selectedRole !== '' || $selectedStatus !== '')
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-danger" title="Reset Filter">&times;</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="table-user-management">
            <thead class="table-light small">
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Nama Pengguna</th>
                    <th>Username</th>
                    <th>Peran (Role)</th>
                    <th>Status Akun</th>
                    <th>Terdaftar Sejak</th>
                    <th class="text-center" style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $isCurrentAdmin = ((int) $user->id_user === (int) Auth::id());
                    @endphp
                    <tr>
                        <td class="font-monospace text-muted small">#{{ $user->id_user }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" 
                                     style="width: 32px; height: 32px; font-size: 0.75rem; background-color: {{ $user->isAdmin() ? '#b45309' : ($user->isPemilik() ? '#0369a1' : '#15803d') }};">
                                    {{ strtoupper(substr($user->nama_user, 0, 2)) }}
                                </div>
                                <div>
                                    <strong class="text-dark d-block">{{ $user->nama_user }}</strong>
                                    @if($isCurrentAdmin)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">Akun Anda</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <code class="text-dark fw-bold font-monospace">{{ $user->username }}</code>
                        </td>
                        <td>
                            <x-status-badge :status="$user->normalized_role" />
                        </td>
                        <td>
                            @if($user->isActive())
                                <x-status-badge status="Aktif" />
                            @else
                                <x-status-badge status="Nonaktif" />
                            @endif
                        </td>
                        <td class="small text-muted">
                            {{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex align-items-center gap-1">
                                <!-- Tombol Edit -->
                                <a href="{{ route('admin.users.edit', $user->id_user) }}" 
                                   class="btn btn-sm btn-outline-primary px-2 py-1 shadow-sm" 
                                   title="Edit Akun & Reset Password"
                                   id="btn-edit-user-{{ $user->id_user }}">
                                    ✏️ Edit
                                </a>

                                <!-- Tombol Toggle Status Aktif / Nonaktif -->
                                @if($isCurrentAdmin)
                                    <button class="btn btn-sm btn-outline-secondary px-2 py-1" disabled title="Akun Anda sendiri tidak dapat dinonaktifkan">
                                        🔒 Terkunci
                                    </button>
                                @else
                                    <form action="{{ route('admin.users.toggle-status', $user->id_user) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin {{ $user->isActive() ? 'menonaktifkan' : 'mengaktifkan kembali' }} akun {{ $user->username }}?');">
                                        @csrf
                                        @method('PATCH')
                                        @if($user->isActive())
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1 shadow-sm" title="Nonaktifkan Akun" id="btn-toggle-user-{{ $user->id_user }}">
                                                🚫 Nonaktifkan
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-outline-success px-2 py-1 shadow-sm" title="Aktifkan Akun" id="btn-toggle-user-{{ $user->id_user }}">
                                                ✓ Aktifkan
                                            </button>
                                        @endif
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="mb-2" style="font-size: 2rem;">👤</div>
                            <h5 class="fw-bold mb-1">Tidak Ada Akun Pengguna</h5>
                            <p class="small mb-0">Tidak ditemukan akun yang sesuai dengan kriteria pencarian Anda.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="ac-card-footer p-3 bg-light d-flex justify-content-end">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
