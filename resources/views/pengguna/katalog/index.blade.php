@extends('layouts.app')

@section('title', 'Katalog Alat Camping')
@section('page-title', 'Katalog Alat Camping')

@section('content')
<!-- Header & Filter Bar -->
<div class="ac-card p-4 mb-4">
    <div class="row align-items-center g-3">
        <div class="col-12 col-md-6">
            <h3 class="ac-card-title mb-1" style="font-size:1.3rem;">Peralatan Outdoor Siap Sewa ⛺</h3>
            <p class="text-muted small mb-0">Pilih peralatan camping dan tentukan jumlah unit yang dibutuhkan. Seluruh barang dalam keranjang menggunakan satu periode sewa saat checkout.</p>
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
                        <button class="btn btn-ac-primary" type="submit" title="Cari Alat">
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
                $isLayakSewa = ($alat->kondisi === 'Baik');
                $isStokTersedia = ($alat->stok > 0);
                $canRent = ($isLayakSewa && $isStokTersedia);
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
                                        <x-status-badge :status="$alat->kondisi" />
                                        <span clas                                        @if($canRent)
                                            <span class="text-success font-weight-medium">Stok: {{ $alat->stok }} unit</span>
                                            @if(isset($alat->stok_tersedia_hari_ini) && $alat->stok_tersedia_hari_ini < $alat->stok)
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:0.7rem;">
                                                    {{ $alat->stok_tersedia_hari_ini }} siap hari ini
                                                </span>
                                            @endif
                                        @elseif(! $isStokTersedia)
                                            <span class="text-danger font-weight-medium">Stok Habis</span>
                                        @else
                                            <span class="text-warning font-weight-medium">Tidak Layak Sewa</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-top mt-3">
                            <div class="d-flex align-items-baseline justify-content-between mb-2">
                                <small class="text-muted" style="font-size:0.75rem;">Tarif Sewa</small>
                                <strong class="text-success" style="font-size:1.15rem;">
                                    Rp {{ number_format($alat->tarif_sewa, 0, ',', '.') }}
                                    <span class="text-muted small fw-normal" style="font-size:0.8rem;">/ unit / hari</span>
                                </strong>
                            </div>

                            @php
                                $maxRentable = $alat->kapasitas_total ?? $alat->stok;
                            @endphp

                            <!-- Form Tambah ke Keranjang dengan Stepper Kuantitas -->
                            <form action="{{ route('pengguna.keranjang.tambah') }}" method="POST" class="form-add-to-cart" data-kode="{{ $alat->kode_alat }}">
                                @csrf
                                <input type="hidden" name="kode_alat" value="{{ $alat->kode_alat }}">
                                
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="input-group input-group-sm" style="max-width: 120px;">
                                        <button type="button" 
                                                class="btn btn-outline-secondary" 
                                                id="btn_minus_{{ $alat->kode_alat }}" 
                                                onclick="stepQty('{{ $alat->kode_alat }}', -1, {{ $maxRentable }})" 
                                                disabled>-</button>
                                        <input type="number" 
                                               name="jumlah" 
                                               id="qty_{{ $alat->kode_alat }}" 
                                               class="form-control text-center font-weight-bold p-1 qty-input" 
                                               value="0" 
                                               min="0" 
                                               max="{{ $maxRentable }}" 
                                               autocomplete="off" 
                                               required 
                                               {{ ! $canRent ? 'disabled' : '' }}
                                               oninput="validateQtyInput('{{ $alat->kode_alat }}', {{ $maxRentable }})"
                                               onblur="if(this.value === '' || isNaN(parseInt(this.value)) || parseInt(this.value) < 0) { this.value = 0; } updateCardControlState('{{ $alat->kode_alat }}', parseInt(this.value) || 0, {{ $maxRentable }});">
                                        <button type="button" 
                                                class="btn btn-outline-secondary" 
                                                id="btn_plus_{{ $alat->kode_alat }}" 
                                                onclick="stepQty('{{ $alat->kode_alat }}', 1, {{ $maxRentable }})"
                                                {{ ! $canRent ? 'disabled' : '' }}>+</button>
                                    </div>                       </div>

                                    <button type="submit" 
                                            class="btn btn-sm btn-ac-primary flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1 btn-cart-submit" 
                                            id="btn_cart_{{ $alat->kode_alat }}" 
                                            disabled>
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
        if (!input || input.disabled) return;
        let val = parseInt(input.value) || 0;
        val = Math.max(0, Math.min(max, val + delta));
        input.value = val;
        updateCardControlState(kode, val, max);
    }

    function validateQtyInput(kode, max) {
        const input = document.getElementById('qty_' + kode);
        if (!input || input.disabled) return;
        let raw = input.value.trim();
        if (raw === '') {
            updateCardControlState(kode, 0, max);
            return;
        }
        let val = parseInt(raw);
        if (isNaN(val) || val < 0) {
            val = 0;
        } else if (val > max) {
            val = max;
        }
        input.value = val;
        updateCardControlState(kode, val, max);
    }

    function updateCardControlState(kode, qty, max) {
        const input = document.getElementById('qty_' + kode);
        const btnCart = document.getElementById('btn_cart_' + kode);
        const btnMinus = document.getElementById('btn_minus_' + kode);
        const btnPlus = document.getElementById('btn_plus_' + kode);

        const isInputDisabled = input ? input.disabled : false;

        if (btnMinus) {
            btnMinus.disabled = isInputDisabled || (qty <= 0);
        }
        if (btnPlus) {
            btnPlus.disabled = isInputDisabled || (qty >= max || max <= 0);
        }
        if (btnCart) {
            btnCart.disabled = isInputDisabled || (qty <= 0 || qty > max || max <= 0);
        }
    }

    // Interaktif Tambah ke Keranjang via AJAX
    document.addEventListener('DOMContentLoaded', function () {
        const toastEl = document.getElementById('cartToast');
        const toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 3000 }) : null;

        // Inisialisasi status awal tombol untuk setiap kartu
        document.querySelectorAll('.form-add-to-cart').forEach(form => {
            const kode = form.dataset.kode;
            const input = document.getElementById('qty_' + kode);
            const max = parseInt(input ? input.max : 0) || 0;
            const qty = parseInt(input ? input.value : 0) || 0;
            updateCardControlState(kode, qty, max);
        });

        document.querySelectorAll('.form-add-to-cart').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const kode = form.dataset.kode;
                const qtyInput = form.querySelector('input[name="jumlah"]');
                const qty = parseInt(qtyInput ? qtyInput.value : 0) || 0;
                const max = parseInt(qtyInput ? qtyInput.max : 0) || 0;

                // Validasi bahwa jumlah harus lebih dari 0 dan tidak melebihi stok
                if (qty <= 0) {
                    const pesanPeringatan = 'Silakan tentukan jumlah unit sewa terlebih dahulu (minimal 1 unit).';
                    if (toast && toastEl) {
                        toastEl.className = 'toast align-items-center text-bg-warning border-0 shadow-lg';
                        const iconSpan = toastEl.querySelector('.toast-body span.fs-5');
                        if (iconSpan) iconSpan.textContent = '⚠️';
                        document.getElementById('toastMessage').textContent = pesanPeringatan;
                        toast.show();
                    } else {
                        alert(pesanPeringatan);
                    }
                    if (qtyInput) {
                        qtyInput.focus();
                        qtyInput.classList.add('is-invalid');
                        setTimeout(() => qtyInput.classList.remove('is-invalid'), 2000);
                    }
                    return;
                }

                if (qty > max) {
                    alert('Jumlah unit melebihi sisa stok yang tersedia (' + max + ' unit).');
                    if (qtyInput) {
                        qtyInput.value = max;
                        updateCardControlState(kode, max, max);
                    }
                    return;
                }

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

                        // Beri umpan balik visual pada tombol
                        btn.classList.remove('btn-ac-primary');
                        btn.classList.add('btn-success');
                        btn.innerHTML = '<span>✓ Masuk</span>';
                        setTimeout(() => {
                            btn.classList.remove('btn-success');
                            btn.classList.add('btn-ac-primary');
                            btn.innerHTML = origHtml;
                        }, 1200);

                        // Reset input kembali ke 0 setelah berhasil dimasukkan ke keranjang
                        if (qtyInput) {
                            qtyInput.value = 0;
                            updateCardControlState(kode, 0, max);
                        }

                        if (toast && toastEl) {
                            toastEl.className = 'toast align-items-center text-bg-success border-0 shadow-lg';
                            const iconSpan = toastEl.querySelector('.toast-body span.fs-5');
                            if (iconSpan) iconSpan.textContent = '🛒';
                            document.getElementById('toastMessage').textContent = result.data.message;
                            toast.show();
                        }
                    } else {
                        const errMsg = (result.data && (result.data.message || (result.data.errors && Object.values(result.data.errors).flat()[0])))
                            || 'Gagal menambahkan alat ke keranjang.';
                        alert(errMsg);
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                    // Fallback kirim form secara normal
                    form.submit();
                });
            });
        });
    });
</script>
@endsection
