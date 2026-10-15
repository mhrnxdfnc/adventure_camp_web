@extends('layouts.app')

@section('title', 'Detail Alat — ' . $alat->nama_alat)
@section('page-title', 'Detail Data Alat')

@section('content')
<div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <a href="{{ route('admin.alat.index') }}" class="btn btn-sm btn-ac-outline d-inline-flex align-items-center gap-1">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
        </svg>
        <span>Kembali ke Daftar Alat</span>
    </a>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.alat.edit', $alat->kode_alat) }}" class="btn btn-sm btn-ac-primary">
            Edit Data
        </a>
        @if(!$alat->hasRentals())
            <form action="{{ route('admin.alat.destroy', $alat->kode_alat) }}" method="POST" onsubmit="return confirm('Hapus alat ini dari inventaris?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
            </form>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="ac-card">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-secondary font-monospace">{{ $alat->kode_alat }}</span>
                    <span class="badge bg-light text-dark border ms-1">{{ $alat->kategori }}</span>
                </div>
                <x-status-badge :status="$alat->kondisi" />
            </div>

            <div class="ac-card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div style="width:64px; height:64px; border-radius:12px; background:var(--ac-accent-soft); display:flex; align-items:center; justify-content:center; font-size:2rem;">
                        ⛺
                    </div>
                    <div>
                        <h2 class="font-weight-bold mb-1" style="color:var(--ac-primary); font-size:1.4rem;">
                            {{ $alat->nama_alat }}
                        </h2>
                        <span class="text-muted small">Didaftarkan pada: {{ $alat->created_at ? $alat->created_at->format('d M Y H:i') : '—' }}</span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-4">
                        <div class="p-3 bg-light rounded-3 border text-center">
                            <small class="text-muted d-block">Stok Fisik</small>
                            <strong style="font-size:1.3rem;">{{ $alat->stok }} Unit</strong>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="p-3 bg-light rounded-3 border text-center">
                            <small class="text-muted d-block">Kondisi</small>
                            <strong style="font-size:1.3rem;">{{ $alat->kondisi }}</strong>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="p-3 bg-light rounded-3 border text-center">
                            <small class="text-muted d-block">Tarif Sewa / Hari</small>
                            <strong class="text-success" style="font-size:1.3rem;">{{ $alat->formatted_tarif }}</strong>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3">
                    <h5 class="font-weight-bold mb-2" style="font-size:1rem;">Kelayakan Sewa Pelanggan:</h5>
                    @if($alat->isSiapSewa())
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-0 py-2 px-3 small border-0" style="border-radius:8px;">
                            <span>✅</span>
                            <div>Alat ini <strong>siap disewa</strong> dan otomatis ditampilkan pada katalog Pengguna.</div>
                        </div>
                    @else
                        <div class="alert alert-warning d-flex align-items-center gap-2 mb-0 py-2 px-3 small border-0" style="border-radius:8px;">
                            <span>⚠️</span>
                            <div>Alat ini <strong>tidak ditampilkan di katalog Pengguna</strong> karena kondisi {{ $alat->kondisi }} atau stok fisik kosong (0 unit).</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Integritas Relasi Transaksi -->
    <div class="col-12 col-lg-4">
        <div class="ac-card">
            <div class="ac-card-header">
                <h4 class="ac-card-title" style="font-size:0.95rem;">Integritas Relasi Transaksi</h4>
            </div>
            <div class="ac-card-body">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Riwayat Penyewaan:</span>
                    <strong class="badge {{ $totalPenyewaan > 0 ? 'bg-primary' : 'bg-secondary' }}" style="font-size:0.85rem;">
                        {{ $totalPenyewaan }} Transaksi
                    </strong>
                </div>

                @if($totalPenyewaan > 0)
                    <div class="alert alert-info py-2 px-3 small mb-0" style="border-radius:8px;">
                        <strong>Perlindungan Integritas:</strong> Alat ini telah tercatat pada transaksi penyewaan (Foreign Key). Sesuai aturan sistem, data ini <strong>tidak boleh dihapus</strong> permanen demi mencegah kerusakan histori transaksi.
                    </div>
                @else
                    <div class="alert alert-success py-2 px-3 small mb-0" style="border-radius:8px;">
                        Alat ini belum pernah digunakan dalam transaksi penyewaan. Aman untuk dihapus jika tidak diperlukan lagi.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
