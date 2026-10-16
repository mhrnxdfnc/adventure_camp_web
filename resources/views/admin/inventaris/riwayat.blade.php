@extends('layouts.app')

@section('title', 'Riwayat Perubahan Stok Inventaris — Admin')
@section('page-title', 'Riwayat Perubahan Stok')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.inventaris.index') }}" class="text-decoration-none">Inventaris & Stok</a></li>
            <li class="breadcrumb-item active" aria-current="page">Riwayat Stok</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Log Riwayat Perubahan Stok Inventaris</h2>
            <p class="text-muted small mb-0">Audit trail lengkap setiap mutasi stok: penyesuaian manual, serah terima sewa, pengembalian, dan pengadaan supplier.</p>
        </div>
        <div>
            <a href="{{ route('admin.inventaris.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali ke Daftar Inventaris
            </a>
        </div>
    </div>
</div>

<div class="ac-card">
    <div class="ac-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="ac-card-title">Riwayat Mutasi Stok ({{ $riwayatList->total() }} Catatan)</h3>
            <small class="text-muted">Setiap perubahan kuantitas tercatat dengan waktu, user, dan alasan transparan</small>
        </div>

        <!-- Filter Form -->
        <form action="{{ route('admin.inventaris.riwayat') }}" method="GET" class="d-flex align-items-center flex-wrap gap-2">
            <div class="input-group input-group-sm" style="max-width: 200px;">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari alasan / alat...">
            </div>

            <select name="kode_alat" class="form-select form-select-sm" style="width: auto;">
                <option value="">Semua Alat</option>
                @foreach($alatList as $a)
                    <option value="{{ $a->kode_alat }}" {{ $selectedAlat === $a->kode_alat ? 'selected' : '' }}>
                        {{ $a->kode_alat }} &mdash; {{ $a->nama_alat }}
                    </option>
                @endforeach
            </select>

            <select name="jenis" class="form-select form-select-sm" style="width: auto;">
                <option value="">Semua Jenis</option>
                <option value="Penambahan Manual" {{ $selectedJenis === 'Penambahan Manual' ? 'selected' : '' }}>Penambahan Manual</option>
                <option value="Pengurangan Manual" {{ $selectedJenis === 'Pengurangan Manual' ? 'selected' : '' }}>Pengurangan Manual</option>
                <option value="Pembelian Supplier" {{ $selectedJenis === 'Pembelian Supplier' ? 'selected' : '' }}>Pembelian Supplier</option>
                <option value="Serah Terima Sewa" {{ $selectedJenis === 'Serah Terima Sewa' ? 'selected' : '' }}>Serah Terima Sewa</option>
                <option value="Pengembalian Sewa" {{ $selectedJenis === 'Pengembalian Sewa' ? 'selected' : '' }}>Pengembalian Sewa</option>
            </select>

            <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            @if($search !== '' || $selectedAlat !== '' || $selectedJenis !== '')
                <a href="{{ route('admin.inventaris.riwayat') }}" class="btn btn-sm btn-outline-danger" title="Reset">&times;</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="table-riwayat-stok-admin">
            <thead class="table-light small">
                <tr>
                    <th style="width: 150px;">Waktu Kejadian</th>
                    <th>Alat Camping</th>
                    <th>Jenis Aktivitas</th>
                    <th class="text-center" style="width: 130px;">Sebelum &rarr; Sesudah</th>
                    <th class="text-center" style="width: 100px;">Perubahan</th>
                    <th>Alasan / Keterangan</th>
                    <th>Dicatat Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatList as $item)
                    <tr>
                        <td>
                            <strong class="text-dark d-block">{{ $item->created_at->format('d/m/Y') }}</strong>
                            <small class="text-muted font-monospace">{{ $item->created_at->format('H:i:s') }} WIB</small>
                        </td>
                        <td>
                            <a href="{{ route('admin.inventaris.show', $item->kode_alat) }}" class="text-decoration-none fw-bold text-dark d-block">
                                {{ $item->alat->nama_alat ?? $item->kode_alat }}
                            </a>
                            <small class="text-muted font-monospace">{{ $item->kode_alat }}</small>
                        </td>
                        <td>
                            @if(str_contains($item->jenis_perubahan, 'Penambahan') || str_contains($item->jenis_perubahan, 'Pembelian'))
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    {{ $item->jenis_perubahan }}
                                </span>
                            @elseif(str_contains($item->jenis_perubahan, 'Pengurangan') || str_contains($item->jenis_perubahan, 'Serah Terima'))
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    {{ $item->jenis_perubahan }}
                                </span>
                            @else
                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                    {{ $item->jenis_perubahan }}
                                </span>
                            @endif
                        </td>
                        <td class="text-center font-monospace">
                            <span class="text-muted">{{ $item->stok_sebelum }}</span>
                            <span class="mx-1 text-muted">&rarr;</span>
                            <strong class="text-dark">{{ $item->stok_sesudah }}</strong>
                        </td>
                        <td class="text-center font-monospace fw-bold">
                            @if($item->jumlah_perubahan > 0)
                                <span class="text-success">+{{ $item->jumlah_perubahan }}</span>
                            @else
                                <span class="text-danger">{{ $item->jumlah_perubahan }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-dark">{{ $item->alasan }}</span>
                        </td>
                        <td>
                            <strong class="text-dark d-block">{{ $item->user->nama_user ?? 'Admin #' . $item->id_user }}</strong>
                            <small class="text-muted">{{ $item->user->normalized_role ?? 'Admin' }}</small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="mb-2" style="font-size: 2rem;">📜</div>
                            <h5 class="fw-bold mb-1">Belum Ada Riwayat Perubahan Stok</h5>
                            <p class="small mb-0">Catatan perubahan stok akan muncul secara otomatis saat penyesuaian manual, penyewaan, atau pengadaan terjadi.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($riwayatList->hasPages())
        <div class="ac-card-footer p-3 bg-light border-top">
            {{ $riwayatList->links() }}
        </div>
    @endif
</div>
@endsection
