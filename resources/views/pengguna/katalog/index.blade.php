@extends('layouts.app')

@section('title', 'Katalog Alat Camping')
@section('page-title', 'Katalog Alat Camping')

@section('content')
<!-- Header & Filter Bar -->
<div class="ac-card p-4 mb-4">
    <div class="row align-items-center g-3">
        <div class="col-12 col-md-6">
            <h3 class="ac-card-title mb-1" style="font-size:1.3rem;">Peralatan Outdoor Siap Sewa ⛺</h3>
            <p class="text-muted small mb-0">Semua perlengkapan telah melalui pengecekan fisik dan dalam kondisi baik serta siap digunakan.</p>
        </div>
        <div class="col-12 col-md-6">
            <form action="{{ route('pengguna.katalog.index') }}" method="GET" class="row g-2 justify-content-md-end">
                <div class="col-12 col-sm-6">
                    <select name="kategori" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        @foreach ($kategoriList as $kat)
                            <option value="{{ $kat }}" {{ request('kategori') === $kat ? 'selected' : '' }}>
                                {{ $kat }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="input-group input-group-sm">
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari alat camping..." aria-label="Cari alat">
                        <button class="btn btn-ac-primary" type="submit">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Active Filter Notice -->
@if(request('kategori') || request('q'))
    <div class="d-flex align-items-center justify-content-between mb-3 px-1">
        <div class="small text-muted">
            Menampilkan hasil untuk: 
            @if(request('kategori')) <span class="badge bg-success-subtle text-success me-1">Kategori: {{ request('kategori') }}</span> @endif
            @if(request('q')) <span class="badge bg-info-subtle text-info me-1">Pencarian: "{{ request('q') }}"</span> @endif
        </div>
        <a href="{{ route('pengguna.katalog.index') }}" class="text-danger small text-decoration-none font-weight-bold">
            &times; Reset Filter
        </a>
    </div>
@endif

<!-- Equipment Grid Cards -->
@if ($alatList->count() > 0)
    <div class="row g-3 mb-4">
        @foreach ($alatList as $alat)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="ac-card h-100 d-flex flex-column" style="transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <div class="ac-card-header bg-light">
                        <span class="badge bg-secondary font-monospace" style="font-size:0.75rem;">{{ $alat->kode_alat }}</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.75rem;">
                            {{ $alat->kategori }}
                        </span>
                    </div>

                    <div class="ac-card-body flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <span style="font-size:1.6rem; line-height:1;">🏕️</span>
                                <div>
                                    <h4 class="font-weight-bold mb-1" style="font-size:1.1rem; color:var(--ac-primary);">
                                        {{ $alat->nama_alat }}
                                    </h4>
                                    <div class="d-flex align-items-center gap-2 small">
                                        <x-status-badge status="Baik" />
                                        <span class="text-muted">&bull;</span>
                                        <span class="text-success font-weight-medium">Stok: {{ $alat->stok }} unit</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-top mt-3 d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted d-block" style="font-size:0.75rem;">Tarif Sewa / Hari</small>
                                <strong class="text-success" style="font-size:1.2rem;">{{ $alat->formatted_tarif }}</strong>
                            </div>

                            <a href="{{ route('pengguna.katalog.show', $alat->kode_alat) }}" class="btn btn-sm btn-ac-primary d-inline-flex align-items-center gap-1">
                                <span>Detail</span>
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <span class="small text-muted">
            Menampilkan {{ $alatList->firstItem() }} sampai {{ $alatList->lastItem() }} dari {{ $alatList->total() }} perlengkapan
        </span>
        {{ $alatList->links() }}
    </div>
@else
    <x-empty-state 
        title="Alat Camping Tidak Ditemukan" 
        message="Tidak ada peralatan yang sesuai dengan kriteria filter pencarian atau stok sedang tidak tersedia." 
        icon="⛺" 
        actionText="Lihat Seluruh Katalog" 
        :actionUrl="route('pengguna.katalog.index')" 
    />
@endif
@endsection
