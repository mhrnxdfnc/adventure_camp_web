@extends('layouts.app')

@section('title', 'Keranjang Belanja Alat Camping')
@section('page-title', 'Keranjang Belanja')

@section('content')
<!-- Header Navigasi -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="ac-card-title mb-1" style="font-size:1.3rem;">Keranjang Belanja Alat Camping 🛒</h3>
        <p class="text-muted small mb-0">Periksa daftar peralatan camping dan tentukan jumlah unit. Seluruh barang dalam satu transaksi akan menggunakan satu periode tanggal sewa bersama saat checkout.</p>
    </div>
    <a href="{{ route('pengguna.katalog.index') }}" class="btn btn-sm btn-ac-outline d-inline-flex align-items-center gap-1">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
        </svg>
        <span>+ Tambah Alat dari Katalog</span>
    </a>
</div>

<!-- Pemberitahuan jika ada barang yang melebihi stok fisik -->
@if(!empty($hasStokIssue))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 py-2.5 px-3 small border-0 shadow-sm" style="border-radius:8px;">
        <span class="fs-5">⚠️</span>
        <div>
            <strong>Perhatian Ketersediaan Stok:</strong> Beberapa peralatan di keranjang melebihi batas stok fisik yang tersedia. Silakan kurangi jumlah unit agar tombol checkout dapat aktif.
        </div>
    </div>
@endif

<!-- Pemberitahuan jika ada barang yang dikeluarkan otomatis karena habis atau rusak -->
@if(!empty($removedItems) && count($removedItems) > 0)
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4 py-2.5 px-3 small border-0 shadow-sm" style="border-radius:8px;">
        <span class="fs-5">ℹ️</span>
        <div>
            Pemberitahuan: Peralatan <strong>{{ implode(', ', $removedItems) }}</strong> telah dikeluarkan dari keranjang karena stok fisik habis atau sedang dalam pemeliharaan.
        </div>
    </div>
@endif

@if(!empty($cartItems) && count($cartItems) > 0)
    <div class="row g-4">
        <!-- Kolom Kiri: Tabel Daftar Item dalam Keranjang -->
        <div class="col-12 col-lg-8">
            <div class="ac-card">
                <div class="ac-card-header d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold text-dark">
                        Daftar Peralatan ({{ count($cartItems) }} Jenis Alat &bull; {{ $totalUnits }} Unit)
                    </span>
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
                                <th class="text-center" style="min-width:145px;">Jumlah Unit</th>
                                <th class="text-end" style="min-width:130px;">Subtotal / Hari</th>
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
                                    $isStokCukup = $item['stok_cukup'] ?? true;
                                @endphp
                                <tr class="{{ ! $isStokCukup ? 'table-danger' : '' }}">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="font-size:1.6rem; line-height:1;">{{ $icon }}</span>
                                            <div>
                                                <strong class="d-block text-dark">{{ $item['nama_alat'] }}</strong>
                                                <div class="d-flex align-items-center gap-2 small flex-wrap">
                                                    <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $item['kode_alat'] }}</span>
                                                    <span class="text-muted">{{ $item['kategori'] }}</span>
                                                    @if($isStokCukup)
                                                        <span class="text-success font-weight-medium">&bull; Stok: {{ $item['stok'] }} unit</span>
                                                    @else
                                                        <span class="badge bg-danger">Melebihi Stok! Tersedia: {{ $item['stok'] }} unit</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end text-muted font-monospace">
                                        Rp {{ number_format($item['tarif_sewa'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <!-- Kontrol Stepper Kuantitas Terhubung ke Form Update -->
                                        <form action="{{ route('pengguna.keranjang.update') }}" method="POST" id="form_cart_{{ $item['kode_alat'] }}" class="d-inline-flex align-items-center justify-content-center m-0">
                                            @csrf
                                            <input type="hidden" name="kode_alat" value="{{ $item['kode_alat'] }}">
                                            <div class="input-group input-group-sm" style="max-width: 130px;">
                                                <button type="button" 
                                                        class="btn btn-outline-secondary" 
                                                        onclick="updateCartItemQty('{{ $item['kode_alat'] }}', -1, {{ $item['stok'] }})"
                                                        title="Kurangi 1 unit"
                                                        {{ $item['jumlah'] <= 1 ? 'disabled' : '' }}>
                                                    -
                                                </button>
                                                <input type="number" 
                                                       name="jumlah" 
                                                       id="cart_qty_{{ $item['kode_alat'] }}" 
                                                       class="form-control text-center font-weight-bold font-monospace p-1" 
                                                       value="{{ $item['jumlah'] }}" 
                                                       min="1" 
                                                       max="{{ $item['stok'] }}" 
                                                       onchange="submitCartItemQty('{{ $item['kode_alat'] }}', {{ $item['stok'] }})"
                                                       onkeydown="if(event.key === 'Enter'){ event.preventDefault(); submitCartItemQty('{{ $item['kode_alat'] }}', {{ $item['stok'] }}); }">
                                                <button type="button" 
                                                        class="btn btn-outline-secondary" 
                                                        onclick="updateCartItemQty('{{ $item['kode_alat'] }}', 1, {{ $item['stok'] }})"
                                                        title="Tambah 1 unit"
                                                        {{ $item['jumlah'] >= $item['stok'] ? 'disabled' : '' }}>
                                                    +
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-end font-weight-bold text-dark font-monospace" style="font-size:1rem;">
                                        Rp {{ number_format($item['subtotal_per_hari'], 0, ',', '.') }}
                                        <small class="text-muted d-block font-sans-serif fw-normal" style="font-size:0.75rem;">
                                            {{ $item['jumlah'] }} unit &times; Rp {{ number_format($item['tarif_sewa'], 0, ',', '.') }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('pengguna.keranjang.hapus') }}" method="POST" onsubmit="return confirm('Hapus alat {{ $item['nama_alat'] }} dari keranjang belanja?');">
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

                <div class="ac-card-footer p-3 bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Seluruh jenis alat dalam keranjang menggunakan satu periode tanggal sewa bersama yang ditentukan saat checkout.
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
                        <small class="text-muted d-block mb-1">Total Sementara / Hari:</small>
                        <div class="fs-4 font-weight-bold text-success font-monospace" id="labelTotalSementara">
                            Rp {{ number_format($totalSubtotalPerHari, 0, ',', '.') }}
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:0.75rem;">
                            * Estimasi tarif harian. Total akhir transaksi dihitung berdasarkan durasi tanggal sewa bersama saat checkout.
                        </small>
                    </div>

                    @if(!empty($hasStokIssue))
                        <button type="button" 
                                class="btn btn-secondary w-100 py-2.5 font-weight-bold d-flex align-items-center justify-content-center gap-2" 
                                disabled 
                                id="btnLanjutCheckoutDisabled"
                                title="Harap sesuaikan kuantitas barang yang melebihi stok terlebih dahulu">
                            <span>Lanjut ke Checkout</span>
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                        <small class="text-danger d-block text-center mt-2 font-weight-medium" style="font-size:0.75rem;">
                            Harap sesuaikan jumlah alat yang melebihi stok sebelum checkout.
                        </small>
                    @else
                        <a href="{{ route('pengguna.checkout.index') }}" 
                           class="btn btn-ac-primary w-100 py-2.5 font-weight-bold d-flex align-items-center justify-content-center gap-2" 
                           id="btnLanjutCheckout">
                            <span>Lanjut ke Checkout</span>
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </a>
                    @endif

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

<script>
    function updateCartItemQty(kode, delta, max) {
        const input = document.getElementById('cart_qty_' + kode);
        if (!input) return;
        let val = parseInt(input.value) || 1;
        val = Math.max(1, Math.min(max, val + delta));
        input.value = val;

        const form = document.getElementById('form_cart_' + kode);
        if (form) form.submit();
    }

    function submitCartItemQty(kode, max) {
        const input = document.getElementById('cart_qty_' + kode);
        if (!input) return;
        let val = parseInt(input.value);
        if (isNaN(val) || val < 1) {
            val = 1;
        } else if (val > max) {
            alert('Jumlah unit melebihi stok yang tersedia (' + max + ' unit).');
            val = max;
        }
        input.value = val;

        const form = document.getElementById('form_cart_' + kode);
        if (form) form.submit();
    }
</script>
@endsection
