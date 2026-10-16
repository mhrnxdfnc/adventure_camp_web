@extends('layouts.app')

@section('title', $alat->nama_alat . ' — Detail Alat')
@section('page-title', 'Detail Perlengkapan Camping')

@section('content')
<div class="mb-3">
    <a href="{{ route('pengguna.katalog.index') }}" class="btn btn-sm btn-ac-outline d-inline-flex align-items-center gap-1">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
        </svg>
        <span>Kembali ke Katalog</span>
    </a>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Kartu Rincian Alat -->
    <div class="col-12 col-lg-7">
        <div class="ac-card">
            <div class="ac-card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-secondary font-monospace">{{ $alat->kode_alat }}</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">{{ $alat->kategori }}</span>
                </div>
                <x-status-badge :status="$alat->kondisi" />
            </div>

            <div class="ac-card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div style="width:64px; height:64px; border-radius:12px; background:var(--ac-accent-soft); display:flex; align-items:center; justify-content:center; font-size:2rem;">
                        🏕️
                    </div>
                    <div>
                        <h2 class="font-weight-bold mb-1" style="color:var(--ac-primary); font-size:1.5rem;">
                            {{ $alat->nama_alat }}
                        </h2>
                        <span class="text-muted small">Kategori: {{ $alat->kategori }}</span>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 mb-4 border">
                    <div class="row text-center g-2">
                        <div class="col-4 border-end">
                            <small class="text-muted d-block">Tarif Sewa</small>
                            <strong class="text-success" style="font-size:1.15rem;">{{ $alat->formatted_tarif }}</strong>
                            <small class="text-muted d-block" style="font-size:0.75rem;">/ 24 jam</small>
                        </div>
                        <div class="col-4 border-end">
                            <small class="text-muted d-block">Stok Siap Sewa</small>
                            <strong class="text-dark" style="font-size:1.15rem;">{{ $alat->stok }} Unit</strong>
                            <small class="text-success d-block" style="font-size:0.75rem;">Tersedia Fisik</small>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Kondisi Fisik</small>
                            <strong class="text-success" style="font-size:1.15rem;">{{ $alat->kondisi }}</strong>
                            <small class="text-muted d-block" style="font-size:0.75rem;">Layak Pakai</small>
                        </div>
                    </div>
                </div>

                <h5 class="font-weight-bold mb-2" style="font-size:1rem;">Informasi & Ketentuan Penyewaan:</h5>
                <ul class="text-muted small ps-3 mb-0" style="line-height:1.7;">
                    <li>Perhitungan sewa dihitung per hari (24 jam) sejak serah terima alat di basecamp.</li>
                    <li>Pemeriksaan fisik alat dilakukan bersama saat serah terima pengambilan dan pengembalian.</li>
                    <li>Keterlambatan pengembalian atau kerusakan alat saat pemakaian akan dikenakan denda sesuai SOP.</li>
                    <li>Alat ini wajib dijaga kebersihan dan kelengkapannya selama masa sewa.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Aksi & Informasi Pemesanan -->
    <div class="col-12 col-lg-5">
        <div class="ac-card">
            <div class="ac-card-header">
                <h3 class="ac-card-title">Penyewaan Alat</h3>
            </div>
            <div class="ac-card-body">
                @if($alat->isSiapSewa())
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2 px-3 small border-0" style="border-radius:8px;">
                        <span>✅</span>
                        <div>Alat ini <strong>siap disewa</strong> dengan ketersediaan fisik sebanyak {{ $alat->stok }} unit.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold small">Tarif Dasar:</label>
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                            <span class="text-muted">1 Unit / Hari</span>
                            <strong>{{ $alat->formatted_tarif }}</strong>
                        </div>
                    </div>

                    <form action="{{ route('pengguna.pesanan.store') }}" method="POST" id="formPemesananAlat">
                        @csrf
                        <input type="hidden" name="kode_alat" value="{{ $alat->kode_alat }}">

                        <!-- Input Jumlah Unit -->
                        <div class="mb-3">
                            <label class="form-label font-weight-bold small d-flex justify-content-between">
                                <span>Jumlah Unit Disewa <span class="text-danger">*</span></span>
                                <span class="text-muted fw-normal">Maks. {{ $alat->stok }} unit</span>
                            </label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" onclick="ubahJumlah(-1)">-</button>
                                <input type="number" name="jumlah" id="inputJumlah" class="form-control text-center font-weight-bold" value="1" min="1" max="{{ $alat->stok }}" required onchange="hitungRingkasan()">
                                <button type="button" class="btn btn-outline-secondary" onclick="ubahJumlah(1)">+</button>
                            </div>
                            @error('jumlah')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Periode Sewa -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label font-weight-bold small">Mulai Sewa <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_sewa" id="tanggalSewa" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" required onchange="hitungRingkasan()">
                                @error('tanggal_sewa')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label font-weight-bold small">Kembali <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_kembali" id="tanggalKembali" class="form-control form-control-sm" value="{{ date('Y-m-d', strtotime('+1 day')) }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required onchange="hitungRingkasan()">
                                @error('tanggal_kembali')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- Pilihan Metode Pembayaran -->
                        <div class="mb-3">
                            <label class="form-label font-weight-bold small">Pilihan Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="metode_pembayaran" class="form-select form-select-sm" required>
                                <option value="QRIS" selected>QRIS (Kasir Basecamp)</option>
                                <option value="Transfer Bank">Transfer Bank Resmi</option>
                                <option value="Cash">Tunai / Cash di Basecamp</option>
                            </select>
                            @error('metode_pembayaran')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Ringkasan Biaya Transaksi -->
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Durasi Sewa:</span>
                                <span id="labelDurasi" class="fw-bold text-dark">1 Hari</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mb-2">
                                <span>Kalkulasi:</span>
                                <span id="labelKalkulasi" class="font-monospace">1 &times; {{ $alat->formatted_tarif }} &times; 1 hari</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <strong class="small">Estimasi Tagihan:</strong>
                                <strong class="text-success" style="font-size:1.2rem;" id="labelTotal">
                                    {{ $alat->formatted_tarif }}
                                </strong>
                            </div>
                        </div>

                        <!-- Tombol Submit Lanjutkan Pesanan -->
                        <button type="submit" class="btn btn-ac-primary w-100 py-2.5 font-weight-bold d-flex align-items-center justify-content-center gap-2">
                            <span>Lanjutkan Pesanan</span>
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                        <small class="text-muted d-block text-center mt-2" style="font-size:0.75rem;">
                            Pesanan akan diperiksa ketersediaan stoknya oleh Admin sebelum instruksi pembayaran diterbitkan.
                        </small>
                    </form>

                    <script>
                        const tarifPerHari = {{ (float) $alat->tarif_sewa }};
                        const maxStok = {{ (int) $alat->stok }};

                        function ubahJumlah(delta) {
                            const input = document.getElementById('inputJumlah');
                            let val = parseInt(input.value) || 1;
                            val = Math.max(1, Math.min(maxStok, val + delta));
                            input.value = val;
                            hitungRingkasan();
                        }

                        function hitungRingkasan() {
                            const jumlah = parseInt(document.getElementById('inputJumlah').value) || 1;
                            const sewaStr = document.getElementById('tanggalSewa').value;
                            const kembaliStr = document.getElementById('tanggalKembali').value;

                            let durasi = 1;
                            if (sewaStr && kembaliStr) {
                                const d1 = new Date(sewaStr);
                                const d2 = new Date(kembaliStr);
                                const diffTime = d2 - d1;
                                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                                durasi = Math.max(1, diffDays);
                            }

                            const total = tarifPerHari * jumlah * durasi;

                            document.getElementById('labelDurasi').textContent = durasi + ' Hari';
                            document.getElementById('labelKalkulasi').textContent = jumlah + ' × Rp ' + tarifPerHari.toLocaleString('id-ID') + ' × ' + durasi + ' hr';
                            document.getElementById('labelTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
                        }
                    </script>
                @else
                    <div class="alert alert-warning py-3 px-3 mb-0 small" style="border-radius:8px;">
                        <strong>Perhatian:</strong> Peralatan ini sedang tidak tersedia untuk disewa (stok habis atau sedang dalam pemeliharaan).
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
