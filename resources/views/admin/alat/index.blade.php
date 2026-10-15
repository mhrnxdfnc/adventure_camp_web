@extends('layouts.app')

@section('title', 'Master Data Alat Camping')
@section('page-title', 'Pengelolaan Data Alat')

@section('content')
<!-- Filter & Header Actions -->
<div class="ac-card mb-4">
    <div class="ac-card-header flex-wrap gap-2">
        <div>
            <h3 class="ac-card-title">Inventaris Master Alat</h3>
            <small class="text-muted">Kelola katalog fisik, stok, kondisi, dan tarif sewa perlengkapan outdoor</small>
        </div>

        <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
            <a href="{{ route('admin.alat.create') }}" class="btn btn-sm btn-ac-primary d-inline-flex align-items-center gap-1">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tambah Alat Baru</span>
            </a>
        </div>
    </div>

    <div class="ac-card-body border-bottom bg-light">
        <form action="{{ route('admin.alat.index') }}" method="GET" class="row g-2">
            <div class="col-12 col-md-5">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari kode atau nama alat...">
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <select name="kategori" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $kat)
                        <option value="{{ $kat }}" {{ request('kategori') === $kat ? 'selected' : '' }}>
                            {{ $kat }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <select name="kondisi" class="form-select form-select-sm">
                    <option value="">Semua Kondisi</option>
                    <option value="Baik" {{ request('kondisi') === 'Baik' ? 'selected' : '' }}>Baik</option>
                    <option value="Perlu Perbaikan" {{ request('kondisi') === 'Perlu Perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                    <option value="Rusak" {{ request('kondisi') === 'Rusak' ? 'selected' : '' }}>Rusak</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-ac-primary flex-grow-1">Filter</button>
                @if(request('q') || request('kategori') || request('kondisi'))
                    <a href="{{ route('admin.alat.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset filter">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="ac-table-responsive">
        <table class="ac-table table-hover">
            <thead>
                <tr>
                    <th>Kode Alat</th>
                    <th>Nama Perlengkapan</th>
                    <th>Kategori</th>
                    <th>Stok Fisik</th>
                    <th>Kondisi</th>
                    <th>Tarif / Hari</th>
                    <th>Status Sewa</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alatList as $alat)
                    <tr>
                        <td>
                            <strong class="font-monospace text-primary">{{ $alat->kode_alat }}</strong>
                        </td>
                        <td>
                            <div class="font-weight-bold">{{ $alat->nama_alat }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $alat->kategori }}</span>
                        </td>
                        <td>
                            <strong>{{ $alat->stok }}</strong> unit
                        </td>
                        <td>
                            <x-status-badge :status="$alat->kondisi" />
                        </td>
                        <td>
                            <strong>{{ $alat->formatted_tarif }}</strong>
                        </td>
                        <td>
                            @if($alat->isSiapSewa())
                                <span class="badge bg-success-subtle text-success small">Siap Disewa</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary small">Tidak Tersedia</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.alat.show', $alat->kode_alat) }}" class="btn btn-outline-secondary" title="Lihat Detail">
                                    Detail
                                </a>
                                <a href="{{ route('admin.alat.edit', $alat->kode_alat) }}" class="btn btn-outline-primary" title="Ubah Data">
                                    Edit
                                </a>
                                <form action="{{ route('admin.alat.destroy', $alat->kode_alat) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus alat [{{ $alat->kode_alat }}] {{ $alat->nama_alat }}? Data tidak dapat dihapus jika memiliki riwayat transaksi.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Hapus Alat">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <x-empty-state 
                                title="Tidak Ada Data Alat" 
                                message="Belum ada data perlengkapan atau tidak ditemukan hasil yang cocok dengan filter pencarian." 
                                icon="📦" 
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($alatList->hasPages())
        <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="small text-muted">
                Menampilkan {{ $alatList->firstItem() }} sampai {{ $alatList->lastItem() }} dari {{ $alatList->total() }} data
            </span>
            {{ $alatList->links() }}
        </div>
    @endif
</div>
@endsection
