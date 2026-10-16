@extends('layouts.app')

@section('title', 'Checkout Sewa Alat Camping')
@section('page-title', 'Checkout Penyewaan')

@section('content')
<div class="mb-3">
    <a href="{{ route('pengguna.keranjang.index') }}" class="btn btn-sm btn-ac-outline d-inline-flex align-items-center gap-1">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
        </svg>
        <span>Kembali ke Keranjang</span>
    </a>
</div>

<form action="{{ route('pengguna.checkout.store') }}" method="POST" id="formCheckout">
    @csrf

    <div class="row g-4">
        <!-- Kolom Kiri: Form Parameter Sewa & Metode Pembayaran -->
        <div class="col-12 col-lg-7">
            <!-- Kartu 1: Periode Sewa -->
            <div class="ac-card mb-4">
                <div class="ac-card-header">
                    <h4 class="ac-card-title fs-6 mb-0">📅 Tentukan Periode Waktu Sewa</h4>
                </div>
                <div class="ac-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-bold small">
                                Tanggal Mulai Sewa <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   name="tanggal_sewa" 
                                   id="tanggalSewa" 
                                   class="form-control" 
                                   value="{{ old('tanggal_sewa', $tanggalSewaDefault) }}" 
                                   min="{{ date('Y-m-d') }}" 
                                   required 
                                   onchange="onTanggalSewaChange()">
                            @error('tanggal_sewa')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-bold small">
                                Tanggal Pengembalian <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   name="tanggal_kembali" 
                                   id="tanggalKembali" 
                                   class="form-control" 
                                   value="{{ old('tanggal_kembali', $tanggalKembaliDefault) }}" 
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}" 
                                   required 
                                   onchange="hitungSemuaBiaya()">
                            @error('tanggal_kembali')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Durasi Waktu Sewa Dihitung:</span>
                        <span class="badge bg-success fs-6 font-monospace" id="labelDurasiSewa">{{ $durasiDefault }} Hari</span>
                    </div>
                </div>
            </div>

            <!-- Kartu 2: Pilihan Metode Pembayaran -->
            <div class="ac-card mb-4">
                <div class="ac-card-header">
                    <h4 class="ac-card-title fs-6 mb-0">💳 Pilih Metode Pembayaran</h4>
                </div>
                <div class="ac-card-body">
                    <p class="text-muted small mb-3">
                        Pilih kanal pembayaran resmi Adventure Camp. Instruksi pembayaran lengkap akan diterbitkan setelah pesanan dikonfirmasi oleh Admin.
                    </p>

                    <div class="d-flex flex-column gap-2">
                        <label class="form-check-card p-3 border rounded-3 d-flex align-items-center gap-3" style="cursor:pointer; transition: all 0.2s ease;">
                            <input class="form-check-input mt-0" type="radio" name="metode_pembayaran" id="metodeQris" value="QRIS" {{ old('metode_pembayaran', 'QRIS') === 'QRIS' ? 'checked' : '' }} required>
                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                <span style="font-size:1.5rem;">📱</span>
                                <div>
                                    <strong class="d-block text-dark">QRIS (Kasir / Pembayaran Cepat)</strong>
                                    <small class="text-muted">Mendukung GoPay, OVO, Dana, ShopeePay, BCA Mobile, dan aplikasi perbankan lainnya.</small>
                                </div>
                            </div>
                        </label>

                        <label class="form-check-card p-3 border rounded-3 d-flex align-items-center gap-3" style="cursor:pointer; transition: all 0.2s ease;">
                            <input class="form-check-input mt-0" type="radio" name="metode_pembayaran" id="metodeTransfer" value="Transfer Bank" {{ old('metode_pembayaran') === 'Transfer Bank' ? 'checked' : '' }}>
                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                <span style="font-size:1.5rem;">🏦</span>
                                <div>
                                    <strong class="d-block text-dark">Transfer Bank Resmi</strong>
                                    <small class="text-muted">Transfer manual ke rekening Bank BCA / Mandiri / BRI Adventure Camp.</small>
                                </div>
                            </div>
                        </label>

                        <label class="form-check-card p-3 border rounded-3 d-flex align-items-center gap-3" style="cursor:pointer; transition: all 0.2s ease;">
                            <input class="form-check-input mt-0" type="radio" name="metode_pembayaran" id="metodeCash" value="Cash" {{ old('metode_pembayaran') === 'Cash' ? 'checked' : '' }}>
                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                <span style="font-size:1.5rem;">💵</span>
                                <div>
                                    <strong class="d-block text-dark">Tunai / Cash di Basecamp</strong>
                                    <small class="text-muted">Pembayaran langsung saat pengambilan alat di store Adventure Camp.</small>
                                </div>
                            </div>
                        </label>
                    </div>

                    @error('metode_pembayaran')
                        <small class="text-danger d-block mt-2">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <!-- Kartu 3: Data Penyewa -->
            <div class="ac-card">
                <div class="ac-card-header">
                    <h4 class="ac-card-title fs-6 mb-0">👤 Informasi Pelanggan / Penyewa</h4>
                </div>
                <div class="ac-card-body small">
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Nama Lengkap:</span>
                            <strong class="text-dark">{{ Auth::user()->nama_user }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Akun Username:</span>
                            <span class="font-monospace text-secondary">{{ Auth::user()->username }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Ringkasan Rincian Seluruh Alat & Total Tagihan -->
        <div class="col-12 col-lg-5">
            <div class="ac-card position-sticky" style="top: 1rem;">
                <div class="ac-card-header d-flex justify-content-between align-items-center">
                    <h4 class="ac-card-title fs-6 mb-0">⛺ Ringkasan Pesanan Alat</h4>
                    <a href="{{ route('pengguna.keranjang.index') }}" class="small text-decoration-none">
                        Ubah Keranjang
                    </a>
                </div>

                <div class="ac-card-body">
                    <div class="p-2 bg-light rounded-3 mb-3 border">
                        <div class="list-group list-group-flush bg-transparent">
                            @foreach($cartItems as $index => $item)
                                <div class="list-group-item bg-transparent px-2 py-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <strong class="text-dark" style="font-size:0.95rem;">{{ $item['nama_alat'] }}</strong>
                                            <small class="text-muted d-block">
                                                {{ $item['jumlah'] }} unit &times; Rp {{ number_format($item['tarif_sewa'], 0, ',', '.') }}
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-dark font-monospace item-subtotal" 
                                                    id="subtotal_{{ $item['kode_alat'] }}"
                                                    data-tarif="{{ $item['tarif_sewa'] }}"
                                                    data-jumlah="{{ $item['jumlah'] }}">
                                                Rp {{ number_format($item['tarif_sewa'] * $item['jumlah'] * $durasiDefault, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    </div>
                                    <div class="small text-muted font-monospace" id="calc_text_{{ $item['kode_alat'] }}" style="font-size:0.75rem;">
                                        {{ $item['jumlah'] }} &times; Rp {{ number_format($item['tarif_sewa'], 0, ',', '.') }} &times; {{ $durasiDefault }} hari
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Perhitungan Total Tagihan -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Total Unit Barang:</span>
                            <span class="text-dark fw-bold">{{ $totalUnits }} Unit</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Lama Sewa:</span>
                            <span class="text-dark fw-bold" id="labelDurasiRingkasan">{{ $durasiDefault }} Hari</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <strong class="text-dark" style="font-size:1rem;">Total Tagihan:</strong>
                            <strong class="text-success fs-4 font-monospace" id="labelGrandTotal">
                                Rp {{ number_format($totalSubtotalPerHari * $durasiDefault, 0, ',', '.') }}
                            </strong>
                        </div>
                    </div>

                    <!-- Tombol Lanjutkan Pesanan dengan Pencegahan Double Submit -->
                    <button type="submit" 
                            class="btn btn-ac-primary w-100 py-3 font-weight-bold d-flex align-items-center justify-content-center gap-2" 
                            id="btnLanjutkanPesanan">
                        <span>Lanjutkan Pesanan</span>
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>

                    <small class="text-muted d-block text-center mt-2" style="font-size:0.75rem;">
                        Admin akan memeriksa ketersediaan stok fisik untuk tanggal sewa yang dipilih sebelum pesanan disetujui.
                    </small>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    const cartItemsData = @json($cartItems);

    function onTanggalSewaChange() {
        const sewaInput = document.getElementById('tanggalSewa');
        const kembaliInput = document.getElementById('tanggalKembali');
        if (sewaInput.value) {
            const d = new Date(sewaInput.value);
            d.setDate(d.getDate() + 1);
            const minKembali = d.toISOString().split('T')[0];
            kembaliInput.min = minKembali;
            if (!kembaliInput.value || kembaliInput.value <= sewaInput.value) {
                kembaliInput.value = minKembali;
            }
        }
        hitungSemuaBiaya();
    }

    function hitungSemuaBiaya() {
        const sewaStr = document.getElementById('tanggalSewa').value;
        const kembaliStr = document.getElementById('tanggalKembali').value;

        let durasi = 1;
        if (sewaStr && kembaliStr) {
            const d1 = new Date(sewaStr);
            const d2 = new Date(kembaliStr);
            const diffTime = d2 - d1;
            const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
            durasi = Math.max(1, diffDays);
        }

        document.getElementById('labelDurasiSewa').textContent = durasi + ' Hari';
        document.getElementById('labelDurasiRingkasan').textContent = durasi + ' Hari';

        let grandTotal = 0;

        cartItemsData.forEach(item => {
            const tarif = parseFloat(item.tarif_sewa);
            const jumlah = parseInt(item.jumlah);
            const subtotal = tarif * jumlah * durasi;
            grandTotal += subtotal;

            const elSubtotal = document.getElementById('subtotal_' + item.kode_alat);
            if (elSubtotal) {
                elSubtotal.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            }

            const elCalc = document.getElementById('calc_text_' + item.kode_alat);
            if (elCalc) {
                elCalc.textContent = jumlah + ' × Rp ' + tarif.toLocaleString('id-ID') + ' × ' + durasi + ' hari';
            }
        });

        document.getElementById('labelGrandTotal').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }

    // Pencegahan pengiriman ganda akibat tombol diklik berulang kali
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formCheckout');
        const submitBtn = document.getElementById('btnLanjutkanPesanan');

        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    <span>Memproses Pesanan...</span>
                `;
            });
        }
    });
</script>
@endsection
