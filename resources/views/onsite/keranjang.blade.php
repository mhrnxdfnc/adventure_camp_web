@extends('layouts.onsite')

@section('title', 'Keranjang Belanja On-Site')

@section('content')
<!-- Header Navigasi -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="h5 fw-bold mb-1 text-dark">Keranjang Belanja On-Site 🛒</h2>
        <p class="text-muted small mb-0">Periksa daftar peralatan camping pilihan Anda sebelum melanjutkan ke pengisian tanggal dan identitas sewa.</p>
    </div>
    <a href="{{ route('onsite.katalog') }}" class="btn btn-outline-dark d-inline-flex align-items-center gap-1 rounded-pill px-3 py-2 kiosk-btn-touch">
        <span>&larr; Lanjut Pilih Alat (Katalog)</span>
    </a>
</div>

@if(!empty($cartItems) && count($cartItems) > 0)
    <div class="row g-4">
        <!-- Kolom Kiri: Tabel Daftar Item dalam Keranjang -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark">Daftar Peralatan ({{ count($cartItems) }} Jenis Alat &bull; {{ $totalUnits }} Unit)</span>
                    <form action="{{ route('onsite.keranjang.kosongkan') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan seluruh keranjang belanja on-site?');" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0 fw-semibold">
                            🗑️ Kosongkan Keranjang
                        </button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th class="ps-4">Alat Camping</th>
                                <th class="text-end" style="min-width:110px;">Tarif / Hari</th>
                                <th class="text-center" style="min-width:140px;">Jumlah Unit</th>
                                <th class="text-end" style="min-width:120px;">Subtotal / Hari</th>
                                <th class="text-center pe-4" style="width:60px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cartItems as $item)
                                @php
                                    $icon = match(strtolower($item['kategori'])) {
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
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="font-size:1.8rem; line-height:1;">{{ $icon }}</span>
                                            <div>
                                                <strong class="d-block text-dark">{{ $item['nama_alat'] }}</strong>
                                                <div class="d-flex align-items-center gap-2 small">
                                                    <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $item['kode_alat'] }}</span>
                                                    <span class="text-muted">{{ $item['kategori'] }}</span>
                                                    <span class="text-success fw-semibold">&bull; Stok: {{ $item['stok'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end text-muted font-monospace">
                                        Rp {{ number_format($item['tarif_sewa'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <!-- Kontrol Stepper Kuantitas -->
                                        <div class="d-inline-flex align-items-center">
                                            <form action="{{ route('onsite.keranjang.update') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="kode_alat" value="{{ $item['kode_alat'] }}">
                                                <input type="hidden" name="jumlah" value="{{ max(1, $item['jumlah'] - 1) }}">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-1" {{ $item['jumlah'] <= 1 ? 'disabled' : '' }}>
                                                    -
                                                </button>
                                            </form>

                                            <span class="px-3 fw-bold text-dark font-monospace fs-6">
                                                {{ $item['jumlah'] }}
                                            </span>

                                            <form action="{{ route('onsite.keranjang.update') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="kode_alat" value="{{ $item['kode_alat'] }}">
                                                <input type="hidden" name="jumlah" value="{{ $item['jumlah'] + 1 }}">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-1" {{ $item['jumlah'] >= $item['stok'] ? 'disabled' : '' }}>
                                                    +
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-success font-monospace">
                                        Rp {{ number_format($item['subtotal_per_hari'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-center pe-4">
                                        <form action="{{ route('onsite.keranjang.hapus') }}" method="POST" onsubmit="return confirm('Hapus alat {{ $item['nama_alat'] }} dari keranjang on-site?');">
                                            @csrf
                                            <input type="hidden" name="kode_alat" value="{{ $item['kode_alat'] }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger p-1" title="Hapus dari Keranjang">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Ringkasan Biaya Harian & Tombol Checkout -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-light border-0 py-3 px-4">
                    <h3 class="h6 fw-bold mb-0 text-dark">Ringkasan Keranjang On-Site</h3>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Jenis Alat:</span>
                        <strong class="text-dark">{{ count($cartItems) }} Item</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Unit Peralatan:</span>
                        <strong class="text-dark">{{ $totalUnits }} Unit</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span class="text-muted">Estimasi Biaya / Hari:</span>
                        <strong class="text-success fs-5">Rp {{ number_format($totalSubtotalPerHari, 0, ',', '.') }}</strong>
                    </div>

                    <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-4">
                        ℹ️ Total akhir sewa akan dihitung secara otomatis berdasarkan durasi hari sewa yang Anda tentukan di langkah checkout berikutnya.
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('onsite.checkout') }}" class="btn btn-success fw-bold py-3 kiosk-btn-touch shadow-sm" style="font-size:1.1rem;">
                            <span>Lanjut ke Checkout &rarr;</span>
                        </a>
                        <a href="{{ route('onsite.katalog') }}" class="btn btn-outline-secondary py-2 kiosk-btn-touch">
                            <span>Tambah Alat Lain</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
        <div class="fs-1 mb-3">🛒</div>
        <h3 class="h5 fw-bold text-dark">Keranjang Belanja On-Site Masih Kosong</h3>
        <p class="text-muted small mb-4">Silakan pilih peralatan camping yang Anda perlukan melalui katalog terlebih dahulu.</p>
        <div>
            <a href="{{ route('onsite.katalog') }}" class="btn btn-success px-4 py-2 kiosk-btn-touch rounded-pill fw-bold">
                Buka Katalog Alat Camping
            </a>
        </div>
    </div>
@endif
@endsection
