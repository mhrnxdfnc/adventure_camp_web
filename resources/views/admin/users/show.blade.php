@extends('layouts.app')

@section('title', 'Detail Akun Pengguna — ' . $user->username)
@section('page-title', 'Detail Akun Pengguna')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}" class="text-decoration-none">Manajemen Pengguna</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $user->username }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Rincian Akun: {{ $user->nama_user }}</h2>
            <p class="text-muted small mb-0">Informasi profil lengkap, hak akses, riwayat transaksi sewa, dan jejak aktivitas akun <code>{{ $user->username }}</code>.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                &larr; Kembali ke Daftar Pengguna
            </a>
            <a href="{{ route('admin.users.edit', $user->id_user) }}" class="btn btn-outline-primary btn-sm shadow-sm" id="btn-edit-user">
                ✏️ Edit Akun
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalResetPassword" id="btn-reset-password">
                🔑 Reset Password
            </button>
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

@if($isCurrentAdmin)
    <div class="alert alert-warning border d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
        <span style="font-size: 1.25rem;">⚠️</span>
        <div class="small">
            <strong>Akun Sendiri:</strong> Anda sedang melihat rincian akun Administrator Anda sendiri. Peran dan status keaktifan dilindungi demi menjamin kesinambungan hak akses sistem.
        </div>
    </div>
@elseif($isLastAdmin)
    <div class="alert alert-warning border d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
        <span style="font-size: 1.25rem;">🛡️</span>
        <div class="small">
            <strong>Proteksi Administrator Terakhir:</strong> Akun ini adalah administrator aktif terakhir di sistem. Status akun ini dilindungi dari deaktivasi.
        </div>
    </div>
@endif

<div class="row g-4">
    <!-- Profil Pengguna (Kolom Kiri) -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4 text-center">
                <div class="avatar-lg mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-bold shadow-sm" style="width: 72px; height: 72px; font-size: 1.75rem;">
                    {{ strtoupper(substr($user->nama_user, 0, 2)) }}
                </div>
                <h5 class="fw-bold mb-1 text-dark">{{ $user->nama_user }}</h5>
                <p class="text-muted small font-monospace mb-2">&#64;{{ $user->username }}</p>
                
                <div class="d-flex justify-content-center gap-2 mb-3">
                    @if($user->isAdmin())
                        <span class="badge bg-danger px-3 py-1">Administrator</span>
                    @elseif($user->isPemilik())
                        <span class="badge bg-info text-dark px-3 py-1">Pemilik (Read-Only)</span>
                    @else
                        <span class="badge bg-success px-3 py-1">Pengguna / Pelanggan</span>
                    @endif

                    @if($user->isActive())
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">Aktif</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1">Nonaktif</span>
                    @endif
                </div>

                <hr class="my-3">

                <div class="text-start small">
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">ID Pengguna:</span>
                        <span class="fw-semibold font-monospace">#{{ $user->id_user }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Terdaftar Sejak:</span>
                        <span class="fw-semibold">{{ $user->created_at ? $user->created_at->format('d M Y, H:i') : '—' }}</span>
                    </div>

                    @if($user->pelanggan)
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">No. Telepon:</span>
                            <span class="fw-semibold font-monospace">{{ $user->pelanggan->nomor_telepon }}</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted d-block">Alamat Domisili:</span>
                            <span class="fw-semibold text-dark">{{ $user->pelanggan->alamat }}</span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">Total Transaksi:</span>
                            <span class="badge bg-primary rounded-pill">{{ $totalTransaksi }} Pesanan</span>
                        </div>
                    @else
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">Tipe Akun:</span>
                            <span class="badge bg-light text-dark border">Akun Internal Manajemen</span>
                        </div>
                    @endif
                </div>

                <hr class="my-3">

                <!-- Tombol Aksi Status -->
                <div class="d-grid gap-2">
                    @if(!$isCurrentAdmin && !$isLastAdmin)
                        <form action="{{ route('admin.users.toggle-status', $user->id_user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin {{ $user->isActive() ? 'menonaktifkan' : 'mengaktifkan kembali' }} akun {{ $user->username }}?');">
                            @csrf
                            @method('PATCH')
                            @if($user->isActive())
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100" id="btn-toggle-status">
                                    🚫 Nonaktifkan Akun
                                </button>
                            @else
                                <button type="submit" class="btn btn-sm btn-outline-success w-100" id="btn-toggle-status">
                                    ✓ Aktifkan Akun
                                </button>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-light border-0 small text-muted text-center py-2">
                🔒 Password dan kredensial aman terenkripsi (bcrypt).
            </div>
        </div>
    </div>

    <!-- Rincian Transaksi & Log Aktivitas (Kolom Kanan) -->
    <div class="col-lg-8">
        @if($user->pelanggan)
            <!-- Riwayat Transaksi Penyewaan -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title h6 mb-0 fw-bold text-dark">
                        🛒 Riwayat Transaksi Penyewaan Terkini
                    </h5>
                    <span class="badge bg-light text-muted border font-monospace">Total: {{ $totalTransaksi }} Transaksi</span>
                </div>
                <div class="card-body p-0">
                    @if($user->pelanggan->penyewaan && $user->pelanggan->penyewaan->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted">
                                    <tr>
                                        <th class="ps-3">No. Transaksi</th>
                                        <th>Periode Sewa</th>
                                        <th>Total Tagihan</th>
                                        <th>Status Pesanan</th>
                                        <th class="text-end pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    @foreach($user->pelanggan->penyewaan as $penyewaan)
                                        <tr>
                                            <td class="ps-3 font-monospace fw-bold text-dark">
                                                {{ $penyewaan->no_transaksi }}
                                                <div class="text-muted" style="font-size: 0.7rem;">{{ $penyewaan->created_at ? $penyewaan->created_at->format('d/m/Y H:i') : '' }}</div>
                                            </td>
                                            <td>
                                                <div>{{ \Carbon\Carbon::parse($penyewaan->tanggal_sewa)->format('d M Y') }} s.d. {{ \Carbon\Carbon::parse($penyewaan->tanggal_kembali)->format('d M Y') }}</div>
                                            </td>
                                            <td class="fw-semibold">
                                                @if($penyewaan->pembayaran)
                                                    {{ $penyewaan->pembayaran->formatted_total ?? 'Rp ' . number_format($penyewaan->pembayaran->total_biaya, 0, ',', '.') }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                <x-status-badge :status="$penyewaan->status_penyewaan" />
                                            </td>
                                            <td class="text-end pe-3">
                                                <a href="{{ route('admin.pesanan.show', $penyewaan->no_transaksi) }}" class="btn btn-sm btn-outline-primary px-2 py-1">
                                                    Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted small">
                            <p class="mb-0">Belum ada riwayat transaksi penyewaan yang diajukan oleh pengguna ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Audit Trail / Riwayat Log Aktivitas Akun -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title h6 mb-0 fw-bold text-dark">
                    📋 Jejak Aktivitas Akun Terkini (Audit Trail)
                </h5>
                <span class="badge bg-light text-muted border font-monospace">Tercatat: {{ $totalAktivitas }} Log</span>
            </div>
            <div class="card-body p-0">
                @if($user->activityLogs && $user->activityLogs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th class="ps-3">Waktu</th>
                                    <th>Modul</th>
                                    <th>Aktivitas</th>
                                    <th class="pe-3">Keterangan / Data</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @foreach($user->activityLogs as $log)
                                    <tr>
                                        <td class="ps-3 text-muted text-nowrap font-monospace" style="font-size: 0.75rem;">
                                            {{ $log->waktu ? $log->waktu->format('d/m/Y H:i:s') : '—' }}
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary border font-monospace" style="font-size: 0.7rem;">{{ $log->modul }}</span>
                                        </td>
                                        <td class="fw-semibold text-dark">{{ $log->aktivitas }}</td>
                                        <td class="pe-3 text-muted text-break" style="font-size: 0.8rem;">
                                            {{ Str::limit($log->data_diproses, 90) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted small">
                        <p class="mb-0">Belum ada catatan aktivitas sistem yang tercatat untuk akun pengguna ini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="modalResetPassword" tabindex="-1" aria-labelledby="modalResetLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered text-start">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.users.reset-password', $user->id_user) }}" method="POST">
                @csrf
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title h6 mb-0" id="modalResetLabel">🔑 Reset Password — {{ $user->username }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border small mb-3">
                        Pengguna: <strong>{{ $user->nama_user }}</strong> (<code>{{ $user->username }}</code>)<br>
                        Peran: <span class="badge bg-secondary">{{ $user->normalized_role }}</span>
                    </div>
                    <p class="small text-muted mb-3">
                        Password baru akan di-hash secara aman menggunakan algoritma bcrypt bawaan Laravel. Password plaintext tidak disimpan maupun ditampilkan.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password baru" required minlength="8">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3">Simpan Password Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
