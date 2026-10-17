@extends('layouts.onsite')

@section('title', 'Checkout Pemesanan On-Site')

@section('content')
<div class="mb-3">
    <a href="{{ route('onsite.keranjang') }}" class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1 rounded-pill px-3">
        <span>&larr; Kembali ke Keranjang</span>
    </a>
</div>

<form action="{{ route('onsite.checkout.proses') }}" method="POST" id="formCheckoutOnsite">
    @csrf

    <div class="row g-4">
        <!-- Kolom Kiri: Tanggal, Identitas Tamu, & Metode Pembayaran -->
        <div class="col-12 col-lg-7">
            <!-- Kartu 1: Periode Sewa Terpadu -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="card-header bg-light border-0 py-3 px-4">
                    <h3 class="h6 fw-bold mb-0 text-dark">📅 1. Tentukan Periode Waktu Sewa</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-bold small">
                                Tanggal Mulai Sewa <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   name="tanggal_sewa" 
                                   id="tanggalSewa" 
                                   class="form-control py-2 rounded-3" 
                                   value="{{ old('tanggal_sewa', $tanggalSewaDefault) }}" 
                                   min="{{ date('Y-m-d') }}" 
                                   required 
                                   onchange="onTanggalSewaChange()">
                            @error('tanggal_sewa')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-bold small">
                                Tanggal Pengembalian <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   name="tanggal_kembali" 
                                   id="tanggalKembali" 
                                   class="form-control py-2 rounded-3" 
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
                        <span class="small text-muted">Durasi Sewa Seluruh Barang:</span>
                        <span class="badge bg-success fs-6 font-monospace" id="labelDurasiSewa">{{ $durasiDefault }} Hari</span>
                    </div>
                </div>
            </div>

            <!-- Kartu 2: Identitas Pemesan On-Site (Wajib) -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="card-header bg-light border-0 py-3 px-4">
                    <h3 class="h6 fw-bold mb-0 text-dark">👤 2. Identitas Pelanggan Toko (Wajib)</h3>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Sebagai pelanggan on-site, Anda tidak perlu memiliki akun login. Cukup lengkapi data kontak di bawah ini untuk pencatatan nota dan verifikasi kasir.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">
                            Nama Lengkap Pemesan <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               name="nama_lengkap" 
                               id="namaLengkap" 
                               class="form-control py-2 rounded-3" 
                               value="{{ old('nama_lengkap') }}" 
                               placeholder="Contoh: Budi Santoso" 
                               required 
                               maxlength="100">
                        @error('nama_lengkap')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">
                            Nomor WhatsApp / Telepon Aktif <span class="text-danger">*</span>
                        </label>
                        <input type="tel" 
                               name="nomor_telepon" 
                               id="nomorTelepon" 
                               class="form-control py-2 rounded-3" 
                               value="{{ old('nomor_telepon') }}" 
                               placeholder="Contoh: 08123456789" 
                               required 
                               maxlength="20">
                        @error('nomor_telepon')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small">
                            Alamat Tempat Tinggal / Domisili <span class="text-danger">*</span>
                        </label>
                        <textarea name="alamat" 
                                  id="alamatPemesan" 
                                  class="form-control py-2 rounded-3" 
                                  rows="2" 
                                  placeholder="Contoh: Jl. Merbabu No. 12, Klojen, Malang" 
                                  required 
                                  maxlength="255">{{ old('alamat') }}</textarea>
                        @error('alamat')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Kartu 3: Pilihan Metode Pembayaran -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="card-header bg-light border-0 py-3 px-4">
                    <h3 class="h6 fw-bold mb-0 text-dark">💳 3. Pilihan Metode Pembayaran</h3>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-column gap-2">
                        <label class="p-3 border rounded-3 d-flex align-items-center gap-3" style="cursor:pointer; background-color:#f8faf9;">
                            <input class="form-check-input mt-0" type="radio" name="metode_pembayaran" id="metodeCash" value="Cash" {{ old('metode_pembayaran', 'Cash') === 'Cash' ? 'checked' : '' }} required>
                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                <span style="font-size:1.6rem;">💵</span>
                                <div>
                                    <strong class="d-block text-dark">Tunai di Kasir (Cash)</strong>
                                    <small class="text-muted">Bayar langsung dengan uang tunai kepada kasir toko saat konfirmasi.</small>
                                </div>
                            </div>
                        </label>

                        <label class="p-3 border rounded-3 d-flex align-items-center gap-3" style="cursor:pointer; background-color:#f8faf9;">
                            <input class="form-check-input mt-0" type="radio" name="metode_pembayaran" id="metodeQris" value="QRIS" {{ old('metode_pembayaran') === 'QRIS' ? 'checked' : '' }}>
                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                <span style="font-size:1.6rem;">📱</span>
                                <div>
                                    <strong class="d-block text-dark">QRIS Kasir (Scan QR Cepat)</strong>
                                    <small class="text-muted">Scan QR Code kasir menggunakan GoPay, OVO, Dana, BCA, Livin, dll.</small>
                                </div>
                            </div>
                        </label>

                        <label class="p-3 border rounded-3 d-flex align-items-center gap-3" style="cursor:pointer; background-color:#f8faf9;">
                            <input class="form-check-input mt-0" type="radio" name="metode_pembayaran" id="metodeTransfer" value="Transfer Bank" {{ old('metode_pembayaran') === 'Transfer Bank' ? 'checked' : '' }}>
                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                <span style="font-size:1.6rem;">🏦</span>
                                <div>
                                    <strong class="d-block text-dark">Transfer Bank Resmi</strong>
                                    <small class="text-muted">Transfer melalui rekening bank resmi Adventure Camp Rental.</small>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Rincian Barang & Total Transaksi -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="card-header bg-light border-0 py-3 px-4">
                    <h3 class="h6 fw-bold mb-0 text-dark">📦 Rincian Peralatan Sewa</h3>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Alat</th>
                                    <th class="text-center">Jml</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cartItems as $item)
                                    <tr class="item-row" 
                                        data-kode="{{ $item['kode_alat'] }}" 
                                        data-tarif="{{ $item['tarif_sewa'] }}" 
                                        data-jumlah="{{ $item['jumlah'] }}">
                                        <td>
                                            <strong class="d-block small text-dark">{{ $item['nama_alat'] }}</strong>
                                            <span class="text-muted" style="font-size:0.75rem;">Rp {{ number_format($item['tarif_sewa'], 0, ',', '.') }}/hari</span>
                                        </td>
                                        <td class="text-center font-monospace small">
                                            {{ $item['jumlah'] }} unit
                                        </td>
                                        <td class="text-end fw-bold font-monospace small text-dark item-subtotal" id="subtotal_{{ $item['kode_alat'] }}">
                                            Rp {{ number_format($item['tarif_sewa'] * $item['jumlah'] * $durasiDefault, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Total Unit Barang:</span>
                            <strong class="text-dark">{{ $totalUnits }} Unit</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Durasi Waktu Sewa:</span>
                            <strong class="text-dark" id="ringkasanDurasi">{{ $durasiDefault }} Hari</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <div>
                                <span class="fw-bold text-dark d-block">Total Tagihan:</span>
                                <small class="text-muted" style="font-size:0.75rem;">(Tarif &times; Unit &times; Durasi)</small>
                            </div>
                            <strong class="text-success fs-4 font-monospace" id="labelTotalTransaksi">
                                Rp {{ number_format($totalSubtotalPerHari * $durasiDefault, 0, ',', '.') }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Konfirmasi Pesanan -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="alert alert-warning py-2 px-3 small border-0 rounded-3 mb-3">
                    ⚠️ Pastikan data identitas dan tanggal sewa sudah tepat sebelum menekan tombol kirim.
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold py-3 kiosk-btn-touch shadow-sm text-white" id="btnSubmitPesanan">
                    <span>🚀 Kirim Pesanan On-Site</span>
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function parseDate(dateStr) {
        if (!dateStr) return null;
        const parts = dateStr.split('-');
        if (parts.length !== 3) return null;
        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    }

    function formatDate(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatRupiah(num) {
        return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function onTanggalSewaChange() {
        const sewaInput = document.getElementById('tanggalSewa');
        const kembaliInput = document.getElementById('tanggalKembali');
        if (!sewaInput || !kembaliInput) return;

        const dSewa = parseDate(sewaInput.value);
        if (dSewa) {
            const minKembali = new Date(dSewa);
            minKembali.setDate(minKembali.getDate() + 1);
            kembaliInput.min = formatDate(minKembali);

            const dKembali = parseDate(kembaliInput.value);
            if (!dKembali || dKembali <= dSewa) {
                kembaliInput.value = formatDate(minKembali);
            }
        }
        hitungSemuaBiaya();
    }

    function hitungSemuaBiaya() {
        const sewaInput = document.getElementById('tanggalSewa');
        const kembaliInput = document.getElementById('tanggalKembali');
        if (!sewaInput || !kembaliInput) return;

        const dSewa = parseDate(sewaInput.value);
        const dKembali = parseDate(kembaliInput.value);

        let durasi = 1;
        if (dSewa && dKembali && dKembali > dSewa) {
            const diffTime = dKembali.getTime() - dSewa.getTime();
            durasi = Math.max(1, Math.round(diffTime / (1000 * 60 * 60 * 24)));
        }

        const labelDurasi = document.getElementById('labelDurasiSewa');
        const ringkasanDurasi = document.getElementById('ringkasanDurasi');
        if (labelDurasi) labelDurasi.textContent = durasi + ' Hari';
        if (ringkasanDurasi) ringkasanDurasi.textContent = durasi + ' Hari';

        let grandTotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const tarif = parseFloat(row.dataset.tarif) || 0;
            const jumlah = parseInt(row.dataset.jumlah) || 0;
            const subtotal = tarif * jumlah * durasi;
            grandTotal += subtotal;

            const kode = row.dataset.kode;
            const subtotalCell = document.getElementById('subtotal_' + kode);
            if (subtotalCell) {
                subtotalCell.textContent = formatRupiah(subtotal);
            }
        });

        const labelGrandTotal = document.getElementById('labelTotalTransaksi');
        if (labelGrandTotal) {
            labelGrandTotal.textContent = formatRupiah(grandTotal);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        hitungSemuaBiaya();
    });
</script>
@endpush
