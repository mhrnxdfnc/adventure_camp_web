@extends('layouts.app')

@section('title', 'Audit Trail & Log Aktivitas Sistem')
@section('page-title', 'Audit Trail Log Aktivitas')

@section('content')
<!-- Ringkasan Statistik Audit Trail -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Total Log Aktivitas</span>
                <div class="ac-stat-icon-wrap" style="background-color: #e0f2fe; color: #0284c7;">
                    📜
                </div>
            </div>
            <div class="ac-stat-value" id="stat-total-logs">{{ number_format($totalLogs, 0, ',', '.') }}</div>
            <div class="ac-stat-sub">Catatan audit trail permanen</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Aktivitas Hari Ini</span>
                <div class="ac-stat-icon-wrap" style="background-color: #dcfce7; color: #16a34a;">
                    ⚡
                </div>
            </div>
            <div class="ac-stat-value" id="stat-logs-today">{{ number_format($logsToday, 0, ',', '.') }}</div>
            <div class="ac-stat-sub">Operasi tercatat hari ini (WIB)</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Pengguna Terlibat</span>
                <div class="ac-stat-icon-wrap" style="background-color: #fef3c7; color: #d97706;">
                    👥
                </div>
            </div>
            <div class="ac-stat-value">{{ $totalUsersLogged }}</div>
            <div class="ac-stat-sub">Akun yang melakukan aksi</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ac-stat-card">
            <div class="ac-stat-head">
                <span class="ac-stat-label">Integritas Audit Trail</span>
                <div class="ac-stat-icon-wrap" style="background-color: #f3e8ff; color: #9333ea;">
                    🔒
                </div>
            </div>
            <div class="ac-stat-value" style="font-size: 1.25rem;">Append-Only</div>
            <div class="ac-stat-sub">Bebas dari kredensial rahasia</div>
        </div>
    </div>
</div>

<!-- Card Filter & Pencarian Log -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body p-3">
        <form action="{{ request()->routeIs('pemilik.*') ? route('pemilik.logs.index') : route('admin.logs.index') }}" method="GET" class="row g-2 align-items-end">
            <!-- Kata Kunci -->
            <div class="col-12 col-md-4">
                <label class="form-label small font-weight-bold text-muted mb-1">Cari Aktivitas / Informasi</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">🔍</span>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control border-start-0" placeholder="Aktivitas, nomor transaksi, nama user...">
                </div>
            </div>

            <!-- Filter Modul -->
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small font-weight-bold text-muted mb-1">Modul Sistem</label>
                <select name="modul" class="form-select">
                    <option value="">Semua Modul</option>
                    @foreach($availableModules as $m)
                        <option value="{{ $m }}" {{ ($filters['modul'] ?? '') === $m ? 'selected' : '' }}>
                            {{ $m }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Rentang Tanggal Mulai -->
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label small font-weight-bold text-muted mb-1">Mulai Tanggal</label>
                <input type="date" name="tanggal_mulai" value="{{ $filters['tanggal_mulai'] ?? '' }}" class="form-control">
            </div>

            <!-- Rentang Tanggal Selesai -->
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label small font-weight-bold text-muted mb-1">Sampai Tanggal</label>
                <input type="date" name="tanggal_selesai" value="{{ $filters['tanggal_selesai'] ?? '' }}" class="form-control">
            </div>

            <!-- Tombol Aksi Filter -->
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100" title="Terapkan Filter">
                    Filter
                </button>
                @if(!empty($filters['search']) || !empty($filters['modul']) || !empty($filters['tanggal_mulai']) || !empty($filters['tanggal_selesai']))
                    <a href="{{ request()->routeIs('pemilik.*') ? route('pemilik.logs.index') : route('admin.logs.index') }}" class="btn btn-outline-secondary" title="Reset Filter">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Tabel Riwayat Audit Trail -->
<x-data-table title="Riwayat Audit Trail Aktivitas" subtitle="Catatan kronologis seluruh aksi penting di backend Adventure Camp Rental (Strictly Read-Only)">
    <x-slot:actions>
        <span class="badge bg-secondary" style="font-size:0.75rem;">Proteksi Append-Only</span>
    </x-slot:actions>

    <thead>
        <tr>
            <th style="width: 70px;">ID</th>
            <th style="width: 170px;">Waktu (WIB)</th>
            <th style="width: 160px;">Pelaku (User)</th>
            <th style="width: 130px;">Modul</th>
            <th style="width: 220px;">Jenis Aktivitas</th>
            <th>Data &amp; Rincian Diproses</th>
        </tr>
    </thead>
    <tbody>
        @forelse($logs as $log)
            <tr>
                <!-- ID Log -->
                <td>
                    <span class="badge bg-light text-dark font-monospace">#{{ $log->id_log }}</span>
                </td>

                <!-- Waktu Kejadian -->
                <td>
                    <div class="fw-semibold text-nowrap">
                        {{ $log->waktu ? $log->waktu->translatedFormat('d M Y') : '-' }}
                    </div>
                    <small class="text-muted text-nowrap">
                        {{ $log->waktu ? $log->waktu->format('H:i:s') : '-' }} WIB
                    </small>
                </td>

                <!-- Pelaku (User) -->
                <td>
                    @if($log->user)
                        <div class="fw-bold text-truncate" style="max-width: 150px;">
                            {{ $log->user->nama_user }}
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <small class="text-muted">@<span>{{ $log->user->username }}</span></small>
                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">
                                {{ $log->user->role }}
                            </span>
                        </div>
                    @else
                        <span class="text-muted fst-italic">User #{{ $log->id_user }}</span>
                    @endif
                </td>

                <!-- Modul -->
                <td>
                    @php
                        $modulLower = strtolower($log->modul);
                        $badgeStyle = match(true) {
                            str_contains($modulLower, 'pembayaran') => 'background-color: #dbeafe; color: #1e40af;',
                            str_contains($modulLower, 'denda') => 'background-color: #fee2e2; color: #991b1b;',
                            str_contains($modulLower, 'inventaris') || str_contains($modulLower, 'stok') => 'background-color: #fef3c7; color: #92400e;',
                            str_contains($modulLower, 'pengambilan') => 'background-color: #e0e7ff; color: #3730a3;',
                            str_contains($modulLower, 'pengembalian') => 'background-color: #dcfce7; color: #166534;',
                            str_contains($modulLower, 'penyewaan') => 'background-color: #ccfbf1; color: #115e59;',
                            default => 'background-color: #f3f4f6; color: #374151;',
                        };
                    @endphp
                    <span class="badge rounded-pill px-2 py-1" style="{{ $badgeStyle }}">
                        {{ $log->modul }}
                    </span>
                </td>

                <!-- Jenis Aktivitas -->
                <td>
                    <strong class="text-dark">{{ $log->aktivitas }}</strong>
                </td>

                <!-- Data & Rincian Diproses -->
                <td>
                    @if(!empty($log->data_diproses))
                        @php
                            $isJson = str_starts_with(trim($log->data_diproses), '{') || str_starts_with(trim($log->data_diproses), '[');
                        @endphp
                        @if($isJson)
                            <pre class="bg-light p-2 rounded mb-0 text-muted font-monospace" style="font-size: 0.8rem; max-height: 120px; overflow-y: auto; white-space: pre-wrap; word-break: break-word;">{{ $log->data_diproses }}</pre>
                        @else
                            <div class="text-secondary" style="font-size: 0.875rem; word-break: break-word;">
                                {{ $log->data_diproses }}
                            </div>
                        @endif
                    @else
                        <span class="text-muted fst-italic">-</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-5">
                    <x-empty-state 
                        title="Tidak Ada Catatan Log" 
                        message="Tidak ditemukan riwayat aktivitas yang sesuai dengan kriteria filter pencarian saat ini." />
                </td>
            </tr>
        @endforelse
    </tbody>
</x-data-table>

<!-- Pagination Links -->
@if($logs->hasPages())
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">
            Menampilkan {{ $logs->firstItem() ?? 0 }} sampai {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} total aktivitas
        </small>
        <div>
            {{ $logs->links() }}
        </div>
    </div>
@endif

<!-- Kebijakan & Keterangan Keamanan Audit Trail -->
<div class="alert alert-light border mt-4 p-3" style="border-radius: 10px; background-color: #f8fafc;">
    <div class="d-flex align-items-start gap-2">
        <span style="font-size: 1.25rem;">🛡️</span>
        <div style="font-size: 0.85rem; color: #475569;">
            <strong>Pedoman Keamanan &amp; Integritas Audit Trail:</strong>
            <ul class="mb-0 ps-3 mt-1">
                <li>Seluruh catatan dibuat secara otomatis oleh sistem backend dan tidak dapat direkayasa melalui input antarmuka pengguna.</li>
                <li>Audit trail berprinsip <em>append-only</em>: tidak ada fungsi edit atau hapus yang disediakan demi menjamin keaslian riwayat transaksi.</li>
                <li>Data rahasia seperti password, token, atau kredensial pengguna disaring secara otomatis sebelum dicatat.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
