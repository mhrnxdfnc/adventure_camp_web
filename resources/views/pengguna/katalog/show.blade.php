@extends('layouts.app')

@section('title', $alat->nama_alat . ' — Detail Perlengkapan')
@section('page-title', 'Detail Perlengkapan Camping')

@section('content')
<!-- Navigasi & Breadcrumb -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <a href="{{ route('pengguna.katalog.index') }}" class="btn btn-sm btn-ac-outline d-inline-flex align-items-center gap-1">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
        </svg>
        <span>Kembali ke Katalog</span>
    </a>

    <a href="{{ route('pengguna.keranjang.index') }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <span>Lihat Keranjang &amp; Lanjutkan Pesanan &rarr;</span>
    </a>
</div>

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

<div class="row g-4">
    <!-- Kolom Kiri: Ilustrasi Visual & Informasi Produk Aktual -->
    <div class="col-12 col-lg-7">
        <div class="ac-card h-100 d-flex flex-column">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary font-monospace" style="font-size:0.8rem;">{{ $alat->kode_alat }}</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.8rem;">{{ $alat->kategori }}</span>
                </div>
                <x-status-badge :status="$alat->kondisi" />
            </div>

            <div class="ac-card-body flex-grow-1">
                <!-- Area Ilustrasi Visual Produk -->
                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border mb-4">
                    <div style="width:72px; height:72px; border-radius:14px; background:var(--ac-accent-soft); display:flex; align-items:center; justify-content:center; font-size:2.4rem; flex-shrink:0;">
                        {{ $icon }}
                    </div>
                    <div>
                        <h2 class="font-weight-bold mb-1" style="color:var(--ac-primary); font-size:1.4rem;">
                            {{ $alat->nama_alat }}
                        </h2>
                        <span class="text-muted small">Kategori: {{ $alat->kategori }}</span>
                    </div>
                </div>

                <!-- Informasi Ringkasan Aktual dari Database -->
                <div class="p-3 bg-light rounded-3 mb-4 border">
                    <div class="row text-center g-2">
                        <div class="col-4 border-end">
                            <small class="text-muted d-block" style="font-size:0.75rem;">Tarif Sewa</small>
                            <strong class="text-success" style="font-size:1.15rem;">Rp {{ number_format($alat->tarif_sewa, 0, ',', '.') }}</strong>
                            <small class="text-muted d-block" style="font-size:0.75rem;">/ unit / hari</small>
                        </div>
                        <div class="col-4 border-end">
                            <small class="text-muted d-block" style="font-size:0.75rem;">Stok Tersedia</small>
                            <strong class="text-dark" style="font-size:1.15rem;">{{ $alat->stok }} Unit</strong>
                            @if(isset($alat->stok_tersedia_hari_ini) && $alat->stok_tersedia_hari_ini < $alat->stok)
                                <small class="text-warning-emphasis d-block" style="font-size:0.75rem;">{{ $alat->stok_tersedia_hari_ini }} Siap Hari Ini</small>
                            @elseif($isStokTersedia)
                                <small class="text-success d-block" style="font-size:0.75rem;">Tersedia Fisik</small>
                            @else
                                <small class="text-danger d-block" style="font-size:0.75rem;">Habis</small>
                            @endif
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block" style="font-size:0.75rem;">Kondisi Alat</small>
                            <strong class="{{ $isLayakSewa ? 'text-success' : 'text-danger' }}" style="font-size:1.15rem;">{{ $alat->kondisi }}</strong>
                            <small class="text-muted d-block" style="font-size:0.75rem;">{{ $isLayakSewa ? 'Layak Sewa' : 'Tidak Layak' }}</small>
                        </div>
                    </div>
                </div>

                <!-- Rincian Identitas Produk Berdasarkan Data Aktual -->
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 140px;">Kode Produk</td>
                                <td class="font-monospace fw-bold">{{ $alat->kode_alat }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Nama Perlengkapan</td>
                                <td class="fw-bold">{{ $alat->nama_alat }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Kategori</td>
                                <td>{{ $alat->kategori }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Kondisi Fisik</td>
                                <td><x-status-badge :status="$alat->kondisi" /></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Ketersediaan Stok</td>
                                <td>
                                    @if($canRent)
                                        <span class="text-success fw-bold">{{ $alat->stok }} unit siap disewa</span>
                                        @if(isset($alat->stok_tersedia_hari_ini) && $alat->stok_tersedia_hari_ini < $alat->stok)
                                            <span class="text-muted small">({{ $alat->stok_tersedia_hari_ini }} unit tersedia hari ini)</span>
                                        @endif
                                        <small class="text-muted d-block mt-1" style="font-size:0.75rem;">* Ketersediaan final disesuaikan otomatis dengan tanggal sewa dan pengembalian yang Anda tentukan di checkout.</small>
                                    @elseif(! $isStokTersedia)
                                        <span class="text-danger fw-bold">Stok sedang habis</span>
                                    @else
                                        <span class="text-warning fw-bold">Sedang tidak layak sewa</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tarif Sewa Harian</td>
                                <td class="text-success fw-bold">Rp {{ number_format($alat->tarif_sewa, 0, ',', '.') }} / unit / hari</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Ketentuan Penyewaan Singkat dan Jelas -->
                <h5 class="font-weight-bold mb-2" style="font-size:0.95rem;">Ketentuan Penyewaan:</h5>
                <ul class="text-muted small ps-3 mb-0" style="line-height:1.7;">
                    <li>Pemeriksaan kondisi fisik alat dilakukan bersama saat serah terima di toko.</li>
                    <li>Pelanggan wajib menjaga kebersihan dan kelengkapan unit selama masa penyewaan.</li>
                    <li>Durasi dan tanggal pemakaian ditentukan bersama untuk seluruh isi keranjang saat checkout.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Aksi Penambahan ke Keranjang Belanja -->
    <div class="col-12 col-lg-5">
        <div class="ac-card h-100 d-flex flex-column">
            <div class="ac-card-header">
                <h3 class="ac-card-title mb-0" style="font-size:1.15rem;">Tambah ke Keranjang</h3>
            </div>

            <div class="ac-card-body flex-grow-1 d-flex flex-column justify-content-between">
                <div>
                    <!-- Status Kesiapan Sewa -->
                    @if($canRent)
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-4 py-2 px-3 small border-0" style="border-radius:8px;">
                            <span>✅</span>
                            <div>Alat ini <strong>siap disewa</strong> dengan stok fisik sebanyak {{ $alat->stok }} unit.</div>
                        </div>
                    @elseif(! $isStokTersedia)
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 py-2 px-3 small border-0" style="border-radius:8px;">
                            <span>⚠️</span>
                            <div>Stok peralatan ini sedang <strong>habis</strong> dan tidak dapat ditambahkan ke keranjang.</div>
                        </div>
                    @else
                        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4 py-2 px-3 small border-0" style="border-radius:8px;">
                            <span>⚠️</span>
                            <div>Peralatan ini sedang <strong>dalam perbaikan atau tidak layak pakai</strong>.</div>
                        </div>
                    @endif

                    <!-- Tarif Harian -->
                    <div class="p-3 bg-light rounded-3 mb-4 border">
                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                            <span class="text-muted small">Tarif Sewa:</span>
                            <strong class="text-success" style="font-size:1.25rem;">
                                Rp {{ number_format($alat->tarif_sewa, 0, ',', '.') }}
                                <span class="text-muted small fw-normal" style="font-size:0.85rem;">/ unit / hari</span>
                            </strong>
                        </div>
                        <small class="text-muted d-block">
                            * Durasi pemakaian diatur secara terpadu untuk seluruh barang saat checkout di keranjang belanja.
                        </small>
                    </div>

                    <!-- Form Tambah ke Keranjang Belanja dengan Kontrol Kuantitas Konsisten -->
                    <form action="{{ route('pengguna.keranjang.tambah') }}" method="POST" id="formAddToCartDetail">
                        @csrf
                        <input type="hidden" name="kode_alat" value="{{ $alat->kode_alat }}">

                        <div class="mb-4">
                            <label class="form-label font-weight-bold small d-flex justify-content-between mb-2">
                                <span>Pilih Jumlah Unit:</span>
                                <span class="text-muted fw-normal">Tersedia: {{ $alat->stok }} unit</span>
                            </label>

                            @php
                                $maxRentableDetail = $alat->kapasitas_total ?? $alat->stok;
                            @endphp

                            <div class="input-group">
                                <button type="button" 
                                        class="btn btn-outline-secondary" 
                                        id="btnDetailMinus" 
                                        onclick="stepDetailQty(-1, {{ $maxRentableDetail }})" 
                                        disabled>-</button>
                                <input type="number" 
                                       name="jumlah" 
                                       id="inputDetailJumlah" 
                                       class="form-control text-center font-weight-bold fs-5" 
                                       value="0" 
                                       min="0" 
                                       max="{{ $maxRentableDetail }}" 
                                       autocomplete="off" 
                                       required 
                                       {{ ! $canRent ? 'disabled' : '' }}
                                       oninput="validateDetailQtyInput({{ $maxRentableDetail }})"
                                       onblur="if(this.value === '' || isNaN(parseInt(this.value)) || parseInt(this.value) < 0) { this.value = 0; } updateDetailControlState(parseInt(this.value) || 0, {{ $maxRentableDetail }});">
                                <button type="button" 
                                        class="btn btn-outline-secondary" 
                                        id="btnDetailPlus" 
                                        onclick="stepDetailQty(1, {{ $maxRentableDetail }})" 
                                        {{ ! $canRent ? 'disabled' : '' }}>+</button>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size:0.75rem;">
                                Tentukan jumlah unit yang ingin disewa (minimal 1 unit untuk menambah ke keranjang).
                            </small>
                        </div>

                        <button type="submit" 
                                class="btn btn-ac-primary w-100 py-2.5 font-weight-bold d-flex align-items-center justify-content-center gap-2" 
                                id="btnDetailSubmit" 
                                disabled>
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span id="btnDetailSubmitText">+ Tambah ke Keranjang</span>
                        </button>
                    </form>
                </div>

                <!-- Navigasi Tambahan ke Keranjang Belanja -->
                <div class="pt-4 border-top mt-4 text-center">
                    <a href="{{ route('pengguna.keranjang.index') }}" class="btn btn-link text-decoration-none small text-success fw-bold d-inline-flex align-items-center gap-1">
                        <span>Lanjutkan Pesanan di Keranjang Belanja</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1090">
    <div id="detailToast" class="toast align-items-center text-bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <span class="fs-5">🛒</span>
                <span id="detailToastMessage">Alat berhasil ditambahkan ke keranjang!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    function stepDetailQty(delta, max) {
        const input = document.getElementById('inputDetailJumlah');
        if (!input || input.disabled) return;
        let val = parseInt(input.value) || 0;
        val = Math.max(0, Math.min(max, val + delta));
        input.value = val;
        updateDetailControlState(val, max);
    }

    function validateDetailQtyInput(max) {
        const input = document.getElementById('inputDetailJumlah');
        if (!input || input.disabled) return;
        let raw = input.value.trim();
        if (raw === '') {
            updateDetailControlState(0, max);
            return;
        }
        let val = parseInt(raw);
        if (isNaN(val) || val < 0) {
            val = 0;
        } else if (val > max) {
            val = max;
        }
        input.value = val;
        updateDetailControlState(val, max);
    }

    function updateDetailControlState(qty, max) {
        const input = document.getElementById('inputDetailJumlah');
        const btnMinus = document.getElementById('btnDetailMinus');
        const btnPlus = document.getElementById('btnDetailPlus');
        const btnSubmit = document.getElementById('btnDetailSubmit');

        const isInputDisabled = input ? input.disabled : false;

        if (btnMinus) {
            btnMinus.disabled = isInputDisabled || (qty <= 0);
        }
        if (btnPlus) {
            btnPlus.disabled = isInputDisabled || (qty >= max || max <= 0);
        }
        if (btnSubmit) {
            btnSubmit.disabled = isInputDisabled || (qty <= 0 || qty > max || max <= 0);
        }
    }

    // Interaktif Tambah ke Keranjang via AJAX
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formAddToCartDetail');
        const toastEl = document.getElementById('detailToast');
        const toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 3000 }) : null;

        const maxStok = {{ (int) $alat->stok }};
        const initialInput = document.getElementById('inputDetailJumlah');
        const initialQty = parseInt(initialInput ? initialInput.value : 0) || 0;
        updateDetailControlState(initialQty, maxStok);

        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const qtyInput = document.getElementById('inputDetailJumlah');
            const qty = parseInt(qtyInput ? qtyInput.value : 0) || 0;

            if (qty <= 0) {
                const pesanPeringatan = 'Silakan tentukan jumlah unit sewa terlebih dahulu (minimal 1 unit).';
                if (toast && toastEl) {
                    toastEl.className = 'toast align-items-center text-bg-warning border-0 shadow-lg';
                    const iconSpan = toastEl.querySelector('.toast-body span.fs-5');
                    if (iconSpan) iconSpan.textContent = '⚠️';
                    document.getElementById('detailToastMessage').textContent = pesanPeringatan;
                    toast.show();
                } else {
                    alert(pesanPeringatan);
                }
                if (qtyInput) {
                    qtyInput.focus();
                }
                return;
            }

            if (qty > maxStok) {
                alert('Jumlah unit melebihi sisa stok yang tersedia (' + maxStok + ' unit).');
                if (qtyInput) {
                    qtyInput.value = maxStok;
                    updateDetailControlState(maxStok, maxStok);
                }
                return;
            }

            const btn = document.getElementById('btnDetailSubmit');
            const btnText = document.getElementById('btnDetailSubmitText');
            const origText = btnText ? btnText.textContent : '+ Tambah ke Keranjang';
            btn.disabled = true;
            if (btnText) btnText.textContent = 'Menyimpan...';

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
                if (btnText) btnText.textContent = origText;

                if (result.ok) {
                    // Perbarui badge keranjang navbar
                    const navBadge = document.getElementById('cartBadgeCount');
                    if (navBadge) {
                        navBadge.textContent = result.data.cart_count;
                        navBadge.style.display = result.data.cart_count > 0 ? '' : 'none';
                    }

                    // Umpan balik visual pada tombol
                    btn.classList.remove('btn-ac-primary');
                    btn.classList.add('btn-success');
                    if (btnText) btnText.textContent = '✓ Berhasil Ditambahkan';
                    setTimeout(() => {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-ac-primary');
                        if (btnText) btnText.textContent = origText;
                    }, 1400);

                    // Reset input kembali ke 0
                    if (qtyInput) {
                        qtyInput.value = 0;
                        updateDetailControlState(0, maxStok);
                    }

                    if (toast && toastEl) {
                        toastEl.className = 'toast align-items-center text-bg-success border-0 shadow-lg';
                        const iconSpan = toastEl.querySelector('.toast-body span.fs-5');
                        if (iconSpan) iconSpan.textContent = '🛒';
                        document.getElementById('detailToastMessage').textContent = result.data.message;
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
                if (btnText) btnText.textContent = origText;
                form.submit();
            });
        });
    });
</script>
@endsection
