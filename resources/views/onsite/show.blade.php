@extends('layouts.onsite')

@section('title', $alat->nama_alat . ' — Detail Alat On-Site')

@section('content')
<div class="mb-3">
    <a href="{{ route('onsite.katalog') }}" class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1 rounded-pill px-3">
        <span>&larr; Kembali ke Katalog</span>
    </a>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Kartu Rincian Alat -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-secondary font-monospace">{{ $alat->kode_alat }}</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">{{ $alat->kategori }}</span>
                </div>
                <x-status-badge :status="$alat->kondisi" />
            </div>

            <div class="card-body p-4">
                @php
                    $icon = match(strtolower($alat->kategori)) {
                        'tenda' => '⛺',
                        'sleeping bag' => '🛏️',
                        'tas carrier', 'tas' => '🎒',
                        'lampu & lentera', 'lampu' => '🔦',
                        'alat masak', 'masak' => '🍳',
                        'matras' => '🧘',
                        'sepatu' => '🥾',
                        default => '🏕️',
                    };
                @endphp
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div style="width:72px; height:72px; border-radius:16px; background:#d8f3dc; display:flex; align-items:center; justify-content:center; font-size:2.5rem;">
                        {{ $icon }}
                    </div>
                    <div>
                        <h1 class="h4 fw-bold mb-1 text-dark">
                            {{ $alat->nama_alat }}
                        </h1>
                        <span class="text-muted small">Kategori: {{ $alat->kategori }}</span>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-4 mb-4 border">
                    <div class="row text-center g-2">
                        <div class="col-4 border-end">
                            <small class="text-muted d-block">Tarif Sewa</small>
                            <strong class="text-success fs-5">{{ $alat->formatted_tarif }}</strong>
                            <small class="text-muted d-block" style="font-size:0.75rem;">/ 24 jam</small>
                        </div>
                        <div class="col-4 border-end">
                            <small class="text-muted d-block">Stok Siap Sewa</small>
                            <strong class="text-dark fs-5">{{ $alat->stok }} Unit</strong>
                            <small class="text-success d-block" style="font-size:0.75rem;">Tersedia di Toko</small>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Kondisi Fisik</small>
                            <strong class="text-success fs-5">{{ $alat->kondisi }}</strong>
                            <small class="text-muted d-block" style="font-size:0.75rem;">Siap Pakai</small>
                        </div>
                    </div>
                </div>

                <h2 class="h6 fw-bold mb-2">Ketentuan Sewa On-Site:</h2>
                <ul class="text-muted small ps-3 mb-0" style="line-height:1.7;">
                    <li>Perhitungan sewa dihitung per hari (selisih tanggal mulai dan tanggal kembali).</li>
                    <li>Pemeriksaan kelengkapan alat dilakukan langsung bersama kasir saat serah terima.</li>
                    <li>Pengembalian terlambat dikenakan denda sesuai SOP toko yang tertera pada nota.</li>
                    <li>Barang wajib dijaga kebersihan dan kelengkapannya selama masa sewa.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Aksi Tambah ke Keranjang -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-light border-0 py-3 px-4">
                <h3 class="h6 fw-bold mb-0 text-dark">Tambahkan ke Keranjang On-Site</h3>
            </div>
            <div class="card-body p-4">
                @if($alat->isSiapSewa())
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2 px-3 small border-0 rounded-3">
                        <span>✅</span>
                        <div>Alat ini <strong>siap disewa</strong> dengan ketersediaan fisik sebanyak {{ $alat->stok }} unit.</div>
                    </div>

                    <form action="{{ route('onsite.keranjang.tambah') }}" method="POST">
                        @csrf
                        <input type="hidden" name="kode_alat" value="{{ $alat->kode_alat }}">

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Pilih Jumlah Unit Sewa:</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary px-3" onclick="stepDetailQty(-1, {{ $alat->stok }})">-</button>
                                <input type="number" 
                                       name="jumlah" 
                                       id="detailQty" 
                                       class="form-control text-center fw-bold fs-5" 
                                       value="1" 
                                       min="1" 
                                       max="{{ $alat->stok }}" 
                                       required>
                                <button type="button" class="btn btn-outline-secondary px-3" onclick="stepDetailQty(1, {{ $alat->stok }})">+</button>
                            </div>
                            <small class="text-muted mt-1 d-block">Maksimal {{ $alat->stok }} unit sesuai stok toko.</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success fw-bold py-2 kiosk-btn-touch shadow-sm">
                                🛒 Masukkan ke Keranjang Tablet
                            </button>
                            <a href="{{ route('onsite.keranjang') }}" class="btn btn-outline-dark py-2 kiosk-btn-touch">
                                Periksa Keranjang Sekarang &rarr;
                            </a>
                        </div>
                    </form>
                @else
                    <div class="alert alert-warning border-0 rounded-3 small">
                        ⚠️ Alat camping ini saat ini belum siap disewa atau stok sedang kosong.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function stepDetailQty(delta, max) {
        const input = document.getElementById('detailQty');
        if (!input) return;
        let val = parseInt(input.value) || 1;
        val = Math.max(1, Math.min(max, val + delta));
        input.value = val;
    }
</script>
@endpush
