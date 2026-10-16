@extends('layouts.app')

@section('title', 'Katalog Alat Camping')
@section('page-title', 'Katalog Alat Camping')

@section('content')
<!-- Header & Filter Bar -->
<div class="ac-card p-4 mb-4">
    <div class="row align-items-center g-3">
        <div class="col-12 col-md-6">
            <h3 class="ac-card-title mb-1" style="font-size:1.3rem;">Peralatan Outdoor Siap Sewa ⛺</h3>
            <p class="text-muted small mb-0">Pilih peralatan camping yang Anda butuhkan, atur jumlah, dan tambahkan ke keranjang untuk disewa sekaligus.</p>
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
            <div class="col-12 col-md-6 col-xl-4">
                <div class="ac-card h-100 d-flex flex-column" style="transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <div class="ac-card-header bg-light d-flex justify-content-between align-items-center">
                        <span class="badge bg-secondary font-monospace" style="font-size:0.75rem;">{{ $alat->kode_alat }}</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.75rem;">
                            {{ $alat->kategori }}
                        </span>
                    </div>

                    <div class="ac-card-body flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <span style="font-size:1.8rem; line-height:1;">{{ $icon }}</span>
                                <div>
                                    <h4 class="font-weight-bold mb-1" style="font-size:1.1rem; color:var(--ac-primary);">
                                        {{ $alat->nama_alat }}
                                    </h4>
                                    <div class="d-flex align-items-center gap-2 small flex-wrap">
                                        <x-status-badge status="Baik" />
                                        <span class="text-muted">&bull;</span>
                                        <span class="text-success font-weight-medium">Stok: {{ $alat->stok }} unit</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-top mt-3">
                            <div class="d-flex align-items-baseline justify-content-between mb-2">
                                <small class="text-muted" style="font-size:0.75rem;">Tarif Sewa / Hari</small>
                                <strong class="text-success fs-5">{{ $alat->formatted_tarif }}</strong>
                            </div>

                            <!-- Form Tambah ke Keranjang dengan Stepper Kuantitas -->
                            <form action="{{ route('pengguna.keranjang.tambah') }}" method="POST" class="form-add-to-cart" data-kode="{{ $alat->kode_alat }}">
                                @csrf
                                <input type="hidden" name="kode_alat" value="{{ $alat->kode_alat }}">
                                
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="input-group input-group-sm" style="max-width: 120px;">
                                        <button type="button" class="btn btn-outline-secondary" onclick="stepQty('{{ $alat->kode_alat }}', -1, {{ $alat->stok }})">-</button>
                                        <input type="number" 
                                               name="jumlah" 
                                               id="qty_{{ $alat->kode_alat }}" 
                                               class="form-control text-center font-weight-bold p-1" 
                                               value="1" 
                                               min="1" 
                                               max="{{ $alat->stok }}" 
                                               required 
                                               onchange="validateQtyInput('{{ $alat->kode_alat }}', {{ $alat->stok }})">
                                        <button type="button" class="btn btn-outline-secondary" onclick="stepQty('{{ $alat->kode_alat }}', 1, {{ $alat->stok }})">+</button>
                                    </div>

                                    <button type="submit" class="btn btn-sm btn-ac-primary flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1 btn-cart-submit" id="btn_cart_{{ $alat->kode_alat }}">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <span>+ Keranjang</span>
                                    </button>
                                </div>
                            </form>

                            <div class="text-end">
                                <a href="{{ route('pengguna.katalog.show', $alat->kode_alat) }}" class="text-muted small text-decoration-none">
                                    Lihat Rincian Detail &rarr;
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
    <x-empty-state 
        title="Alat Camping Tidak Ditemukan" 
        message="Tidak ada peralatan yang sesuai dengan kriteria filter pencarian atau stok sedang tidak tersedia." 
        icon="⛺" 
        actionText="Lihat Seluruh Katalog" 
        :actionUrl="route('pengguna.katalog.index')" 
    />
@endif

<!-- Floating Toast Notification Container -->
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

<script>
    function stepQty(kode, delta, max) {
        const input = document.getElementById('qty_' + kode);
        if (!input) return;
        let val = parseInt(input.value) || 1;
        val = Math.max(1, Math.min(max, val + delta));
        input.value = val;
    }

    function validateQtyInput(kode, max) {
        const input = document.getElementById('qty_' + kode);
        if (!input) return;
        let val = parseInt(input.value) || 1;
        if (val < 1) val = 1;
        if (val > max) val = max;
        input.value = val;
    }

    // Interaktif Tambah ke Keranjang via AJAX
    document.addEventListener('DOMContentLoaded', function () {
        const toastEl = document.getElementById('cartToast');
        const toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 3000 }) : null;

        document.querySelectorAll('.form-add-to-cart').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const btn = form.querySelector('.btn-cart-submit');
                const origHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span>Menyimpan...</span>';

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, data })))
                .then(result => {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;

                    if (result.ok) {
                        // Perbarui badge keranjang di navbar dan sidebar
                        const navBadge = document.getElementById('cartBadgeCount');
                        if (navBadge) {
                            navBadge.textContent = result.data.cart_count;
                            navBadge.style.display = result.data.cart_count > 0 ? '' : 'none';
                        }

                        // Beri umpan balik visual
                        btn.classList.remove('btn-ac-primary');
                        btn.classList.add('btn-success');
                        btn.innerHTML = '<span>✓ Masuk</span>';
                        setTimeout(() => {
                            btn.classList.remove('btn-success');
                            btn.classList.add('btn-ac-primary');
                            btn.innerHTML = origHtml;
                        }, 1200);

                        if (toast) {
                            document.getElementById('toastMessage').textContent = result.data.message;
                            toast.show();
                        }
                    } else {
                        alert(result.data.message || 'Gagal menambahkan alat ke keranjang.');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                    // Fallback kirim secara normal jika terjadi error koneksi
                    form.submit();
                });
            });
        });
    });
</script>
@endsection
