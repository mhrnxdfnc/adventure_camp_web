@extends('layouts.app')

@section('title', 'Keranjang Belanja Alat Camping')
@section('page-title', 'Keranjang Belanja')

@section('content')
<!-- Header Navigasi -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="ac-card-title mb-1" style="font-size:1.3rem;">Keranjang Belanja Alat Camping 🛒</h3>
        <p class="text-muted small mb-0">Periksa kembali daftar peralatan camping sebelum melanjutkan ke tahap pemilihan tanggal dan pembayaran.</p>
    </div>
    <a href="{{ route('pengguna.katalog.index') }}" class="btn btn-sm btn-ac-outline d-inline-flex align-items-center gap-1">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
        </svg>
        <span>Lanjut Pilih Alat (Katalog)</span>
    </a>
</div>

@if(!empty($cartItems) && count($cartItems) > 0)
    <div class="row g-4">
        <!-- Kolom Kiri: Tabel Daftar Item dalam Keranjang -->
        <div class="col-12 col-lg-8">
            <div class="ac-card">
                <div class="ac-card-header d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold text-dark">Daftar Peralatan ({{ count($cartItems) }} Jenis Alat &bull; {{ $totalUnits }} Unit)</span>
                    <form action="{{ route('pengguna.keranjang.kosongkan') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan seluruh keranjang belanja?');" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0" style="font-size:0.85rem;">
                            Kosongkan Keranjang
                        </button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Alat Camping</th>
                                <th class="text-end" style="min-width:110px;">Tarif / Hari</th>
                                <th class="text-center" style="min-width:140px;">Jumlah Unit</th>
                                <th class="text-end" style="min-width:120px;">Subtotal / Hari</th>
                                <th class="text-center" style="width:50px;">Aksi</th>
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
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="font-size:1.6rem; line-height:1;">{{ $icon }}</span>
                                            <div>
                                                <strong class="d-block text-dark">{{ $item['nama_alat'] }}</strong>
                                                <div class="d-flex align-items-center gap-2 small">
                                                    <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $item['kode_alat'] }}</span>
                                                    <span class="text-muted">{{ $item['kategori'] }}</span>
                                                    <span class="text-success font-weight-medium">&bull; Stok: {{ $item['stok'] }}</span>
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
                                            <form action="{{ route('pengguna.keranjang.update') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="kode_alat" value="{{ $item['kode_alat'] }}">
                                                <input type="hidden" name="jumlah" value="{{ max(1, $item['jumlah'] - 1) }}">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-1" {{ $item['jumlah'] <= 1 ? 'disabled' : '' }}>
                                                    -
                                                </button>
                                            </form>

                                            <span class="px-3 font-weight-bold text-dark font-monospace" style="font-size:1.05rem;">
                                                {{ $item['jumlah'] }}
                                            </span>

                                            <form action="{{ route('pengguna.keranjang.update') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="kode_alat" value="{{ $item['kode_alat'] }}">
                                                <input type="hidden" name="jumlah" value="{{ $item['jumlah'] + 1 }}">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-1" {{ $item['jumlah'] >= $item['stok'] ? 'disabled' : '' }}>
                                                    +
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="text-end font-weight-bold text-dark font-monospace" style="font-size:1rem;">
                                        Rp {{ number_format($item['subtotal_per_hari'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('pengguna.keranjang.hapus') }}" method="POST" onsubmit="return confirm('Hapus alat {{ $item['nama_alat'] }} dari keranjang?');">
                                            @csrf
                                            <input type="hidden" name="kode_alat" value="{{ $item['kode_alat'] }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger p-1" title="Hapus Barang">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="ac-card-footer p-3 bg-light d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Semua harga tertera adalah tarif sewa per hari (24 jam) belum dikalikan durasi hari sewa.
                    </small>
                    <a href="{{ route('pengguna.katalog.index') }}" class="small text-decoration-none font-weight-medium">
                        + Tambah Alat Lainnya
                    </a>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Ringkasan Keranjang & Tombol Checkout -->
        <div class="col-12 col-lg-4">
            <div class="ac-card">
                <div class="ac-card-header">
                    <h4 class="ac-card-title fs-6 mb-0">Ringkasan Keranjang</h4>
                </div>

                <div class="ac-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                        <span>Total Jenis Alat:</span>
                        <strong class="text-dark">{{ count($cartItems) }} Item</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 small text-muted">
                        <span>Total Kuantitas:</span>
                        <strong class="text-dark">{{ $totalUnits }} Unit</strong>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <small class="text-muted d-block mb-1">Total Biaya Sementara / Hari:</small>
                        <div class="fs-4 font-weight-bold text-success font-monospace" id="labelTotalSementara">
                            Rp {{ number_format($totalSubtotalPerHari, 0, ',', '.') }}
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:0.75rem;">
                            *Total akhir dihitung berdasarkan tanggal mulai dan tanggal pengembalian pada halaman checkout.
                        </small>
                    </div>

                    <a href="{{ route('pengguna.checkout.index') }}" class="btn btn-ac-primary w-100 py-2.5 font-weight-bold d-flex align-items-center justify-content-center gap-2" id="btnLanjutCheckout">
                        <span>Lanjut ke Checkout</span>
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>

                    <div class="text-center mt-3">
                        <a href="{{ route('pengguna.katalog.index') }}" class="text-muted small text-decoration-none">
                            &larr; Kembali ke Katalog Alat
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <x-empty-state 
        title="Keranjang Belanja Masih Kosong" 
        message="Anda belum menambahkan peralatan camping ke dalam keranjang. Silakan pilih alat yang Anda butuhkan dari katalog outdoor kami." 
        icon="🛒" 
        actionText="Buka Katalog Alat Camping" 
        :actionUrl="route('pengguna.katalog.index')" 
    />
@endif
@endsection
