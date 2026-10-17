@extends('layouts.onsite')

@section('title', 'Katalog Perlengkapan Camping — On-Site')

@section('content')
<!-- Header Navigasi & Filter Bar Tablet -->
<div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
    <div class="card-body p-3 p-md-4">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-6">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-3">⛺</span>
                    <div>
                        <h2 class="h5 fw-bold mb-0 text-dark">Katalog Perlengkapan Outdoor</h2>
                        <small class="text-muted">Pilih perlengkapan, atur jumlah unit, dan tambahkan ke keranjang tablet.</small>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <form action="{{ route('onsite.katalog') }}" method="GET" class="row g-2 justify-content-md-end">
                    <div class="col-12 col-sm-6">
                        <select name="kategori" class="form-select form-select-sm rounded-3 py-2" onchange="this.form.submit()">
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
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control rounded-start-3 py-2" placeholder="Cari tenda, matras..." aria-label="Cari alat">
                            <button class="btn btn-dark rounded-end-3 px-3" type="submit">
                                🔍
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Active Filter Notice -->
@if(request('kategori') || request('q'))
    <div class="d-flex align-items-center justify-content-between mb-3 px-2">
        <div class="small text-muted">
            Menampilkan hasil untuk: 
            @if(request('kategori')) <span class="badge bg-success-subtle text-success me-1">Kategori: {{ request('kategori') }}</span> @endif
            @if(request('q')) <span class="badge bg-info-subtle text-info me-1">Pencarian: "{{ request('q') }}"</span> @endif
        </div>
        <a href="{{ route('onsite.katalog') }}" class="text-danger small text-decoration-none fw-bold">
            &times; Reset Filter
        </a>
    </div>
@endif

<!-- Equipment Grid Cards -->
@if ($alatList->count() > 0)
    <div class="row g-3 g-md-4 mb-4">
        @foreach ($alatList as $alat)
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
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden d-flex flex-column" style="transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <div class="card-header bg-light border-0 py-2 px-3 d-flex justify-content-between align-items-center">
                        <span class="badge bg-secondary font-monospace" style="font-size:0.75rem;">{{ $alat->kode_alat }}</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.75rem;">
                            {{ $alat->kategori }}
                        </span>
                    </div>

                    <div class="card-body p-3 p-md-4 flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <span style="font-size: 2.2rem; line-height: 1;">{{ $icon }}</span>
                                <div class="flex-grow-1">
                                    <h3 class="h6 fw-bold mb-1 text-dark" style="font-size: 1.05rem;">
                                        {{ $alat->nama_alat }}
                                    </h3>
                                    <div class="d-flex align-items-center gap-2 small flex-wrap">
                                        <x-status-badge status="Baik" />
                                        <span class="text-muted">&bull;</span>
                                        <span class="text-success fw-semibold">Tersedia: {{ $alat->stok }} unit</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-top mt-3">
                            <div class="d-flex align-items-baseline justify-content-between mb-3">
                                <span class="text-muted small">Tarif Sewa / Hari</span>
                                <strong class="text-success fs-5">{{ $alat->formatted_tarif }}</strong>
                            </div>

                            <!-- Form Tambah ke Keranjang dengan Stepper Kuantitas (Awal 0) -->
                            <form action="{{ route('onsite.keranjang.tambah') }}" method="POST" class="form-add-to-cart" data-kode="{{ $alat->kode_alat }}">
                                @csrf
                                <input type="hidden" name="kode_alat" value="{{ $alat->kode_alat }}">
                                
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="input-group" style="max-width: 130px;">
                                        <button type="button" class="btn btn-outline-secondary px-3" onclick="stepQty('{{ $alat->kode_alat }}', -1, {{ $alat->stok }})" style="font-size: 1.1rem; line-height: 1;">-</button>
                                        <input type="number" 
                                               name="jumlah" 
                                               id="qty_{{ $alat->kode_alat }}" 
                                               class="form-control text-center fw-bold p-1" 
                                               value="0" 
                                               min="0" 
                                               max="{{ $alat->stok }}" 
                                               autocomplete="off"
                                               required 
                                               onchange="validateQtyInput('{{ $alat->kode_alat }}', {{ $alat->stok }})">
                                        <button type="button" class="btn btn-outline-secondary px-3" onclick="stepQty('{{ $alat->kode_alat }}', 1, {{ $alat->stok }})" style="font-size: 1.1rem; line-height: 1;">+</button>
                                    </div>

                                    <button type="submit" class="btn btn-success flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1 py-2 fw-semibold btn-cart-submit" id="btn_cart_{{ $alat->kode_alat }}">
                                        <span>🛒</span>
                                        <span>+ Keranjang</span>
                                    </button>
                                </div>
                            </form>

                            <div class="text-end mt-2">
                                <a href="{{ route('onsite.show', $alat->kode_alat) }}" class="text-muted small text-decoration-none">
                                    Lihat Rincian Spesifikasi &rarr;
                                </a>
                            </div>
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
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
        <div class="fs-1 mb-3">⛺</div>
        <h3 class="h5 fw-bold text-dark">Alat Camping Tidak Ditemukan</h3>
        <p class="text-muted small mb-3">Tidak ada peralatan yang sesuai dengan kriteria filter pencarian atau stok sedang kosong.</p>
        <div>
            <a href="{{ route('onsite.katalog') }}" class="btn btn-outline-dark btn-sm rounded-pill px-4">
                Lihat Seluruh Katalog
            </a>
        </div>
    </div>
@endif

<!-- Sticky Bottom Bar for Tablet Cart Proceed -->
<div class="card border-0 shadow-lg rounded-4 p-3 bg-white mt-4 border-top">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="fs-4">🛒</span>
            <div>
                <span class="fw-bold text-dark d-block">Keranjang Belanja On-Site</span>
                <small class="text-muted">Total unit terpilih: <span class="fw-bold text-success" id="bottomCartCount">{{ \App\Http\Controllers\OnsiteKioskController::countCart() }}</span> unit</small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('onsite.keranjang') }}" class="btn btn-outline-dark px-4 kiosk-btn-touch">
                Periksa Keranjang
            </a>
            <a href="{{ route('onsite.checkout') }}" class="btn btn-success px-4 kiosk-btn-touch fw-bold shadow-sm">
                Lanjut ke Checkout &rarr;
            </a>
        </div>
    </div>
</div>

<!-- Floating Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1090">
    <div id="cartToast" class="toast align-items-center text-bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <span class="fs-5">🛒</span>
                <span id="toastMessage">Alat berhasil ditambahkan ke keranjang!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function stepQty(kode, delta, max) {
        const input = document.getElementById('qty_' + kode);
        if (!input) return;
        let val = parseInt(input.value);
        if (isNaN(val)) val = 0;
        val = Math.max(0, Math.min(max, val + delta));
        input.value = val;
    }

    function validateQtyInput(kode, max) {
        const input = document.getElementById('qty_' + kode);
        if (!input) return;
        let val = parseInt(input.value);
        if (isNaN(val) || val < 0) val = 0;
        if (val > max) val = max;
        input.value = val;
    }

    // Interaktif Tambah ke Keranjang via AJAX
    document.addEventListener('DOMContentLoaded', function () {
        const toastEl = document.getElementById('cartToast');
        const toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 3500 }) : null;

        document.querySelectorAll('.form-add-to-cart').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const kode = this.dataset.kode;
                const qtyInput = document.getElementById('qty_' + kode);
                const qty = parseInt(qtyInput ? qtyInput.value : 0);

                if (isNaN(qty) || qty <= 0) {
                    alert('Silakan tentukan jumlah unit alat yang ingin disewa terlebih dahulu (tekan tombol +).');
                    if (qtyInput) qtyInput.focus();
                    return;
                }

                const submitBtn = this.querySelector('.btn-cart-submit');
                const originalHtml = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menambahkan...';
                }

                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, body: data })))
                .then(response => {
                    if (response.ok && response.body.success) {
                        // Perbarui counter keranjang
                        const badgeHeader = document.getElementById('kioskCartBadge');
                        const bottomCount = document.getElementById('bottomCartCount');
                        if (badgeHeader) badgeHeader.textContent = response.body.cart_count;
                        if (bottomCount) bottomCount.textContent = response.body.cart_count;

                        // Tampilkan toast
                        const toastMsg = document.getElementById('toastMessage');
                        if (toastMsg) toastMsg.textContent = response.body.message;
                        if (toast) toast.show();

                        // Reset input kuantitas kembali ke 0
                        if (qtyInput) qtyInput.value = 0;
                    } else {
                        const errMsg = (response.body && response.body.message) 
                            ? response.body.message 
                            : 'Gagal menambahkan alat ke keranjang. Silakan periksa ketersediaan stok.';
                        alert(errMsg);
                    }
                })
                .catch(err => {
                    console.error('Cart add error:', err);
                    alert('Terjadi kendala jaringan saat menambahkan ke keranjang. Silakan coba lagi.');
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    }
                });
            });
        });
    });
</script>
@endpush
