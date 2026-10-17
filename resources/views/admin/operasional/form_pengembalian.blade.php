@extends('layouts.app')

@section('title', 'Pemeriksaan Pengembalian Alat — ' . $penyewaan->no_transaksi)
@section('page-title', 'Form Pemeriksaan & Pengembalian Alat')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.operasional.pengembalian') }}" class="text-decoration-none">Pengembalian Alat</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $penyewaan->no_transaksi }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Pemeriksaan Fisik & Pengembalian Alat</h2>
            <p class="text-muted small mb-0">No. Transaksi: <strong class="font-monospace text-dark">{{ $penyewaan->no_transaksi }}</strong> &bull; Pelanggan: <strong>{{ $penyewaan->pelanggan->nama_pelanggan }}</strong></p>
        </div>
        <a href="{{ route('admin.operasional.pengembalian') }}" class="btn btn-outline-secondary btn-sm">
            &larr; Kembali ke Daftar
        </a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong class="d-block mb-1">Gagal memproses pengembalian:</strong>
        <ul class="mb-0 ps-3 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi) }}" method="POST" id="form-pengembalian">
    @csrf

    <div class="row g-4">
        <!-- Kolom Kiri: Rincian Sewa & Form Kondisi Fisik Alat -->
        <div class="col-lg-7">
            <!-- Informasi Penyerahan Awal -->
            <div class="ac-card mb-4">
                <div class="ac-card-header bg-light">
                    <h3 class="ac-card-title small font-weight-bold mb-0">📋 Data Transaksi & Penyerahan Awal</h3>
                </div>
                <div class="ac-card-body p-3">
                    <div class="row g-2 small">
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Nama Pelanggan:</span>
                            <strong class="text-dark">{{ $penyewaan->pelanggan->nama_pelanggan }}</strong> ({{ $penyewaan->pelanggan->nomor_telepon }})
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Status Pembayaran:</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                Lunas (Rp {{ number_format($penyewaan->pembayaran?->jumlah_pembayaran ?? $penyewaan->total_biaya, 0, ',', '.') }})
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Tanggal Pengambilan Awal:</span>
                            <span class="text-dark">
                                {{ $penyewaan->pengambilan ? \Carbon\Carbon::parse($penyewaan->pengambilan->tanggal_pengambilan)->format('d F Y') : \Carbon\Carbon::parse($penyewaan->tanggal_sewa)->format('d F Y') }}
                            </span>
                            @if($penyewaan->pengambilan)
                                <small class="text-muted d-block">Penerima: {{ $penyewaan->pengambilan->nama_penerima }}</small>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Batas Rencana Pengembalian:</span>
                            <strong class="text-dark" id="label-rencana-kembali" data-tgl-kembali="{{ \Carbon\Carbon::parse($penyewaan->tanggal_kembali)->format('Y-m-d') }}">
                                {{ \Carbon\Carbon::parse($penyewaan->tanggal_kembali)->format('d F Y') }}
                            </strong>
                            <small class="text-muted d-block">Durasi: {{ $penyewaan->durasi_hari }} Hari</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Pemeriksaan Fisik Per Alat -->
            <div class="ac-card">
                <div class="ac-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="ac-card-title">🔍 Pemeriksaan Kondisi Alat Camping</h3>
                        <small class="text-muted">Pisahkan unit berkondisi Baik (stok sewa) dan unit Rusak/Perbaikan</small>
                    </div>
                </div>
                <div class="ac-card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Nama Alat</th>
                                    <th class="text-center" style="width: 80px;">Disewa</th>
                                    <th class="text-center" style="width: 120px;">Kondisi Baik</th>
                                    <th class="text-center" style="width: 120px;">Perlu Perbaikan</th>
                                    <th class="text-center" style="width: 120px;">Rusak / Afkir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($penyewaan->detailPenyewaan as $detail)
                                    @php
                                        $qtyDisewa = $detail->jumlah_diserahkan > 0 ? $detail->jumlah_diserahkan : $detail->jumlah;
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong class="text-dark d-block">{{ $detail->alat->nama_alat ?? $detail->kode_alat }}</strong>
                                            <small class="text-muted font-monospace">{{ $detail->kode_alat }} &bull; Rp {{ number_format($detail->tarif_sewa, 0, ',', '.') }}/hari</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-dark border px-2 py-1 font-monospace" style="font-size: 0.85rem;">
                                                {{ $qtyDisewa }} unit
                                            </span>
                                            <input type="hidden" id="max-qty-{{ $detail->kode_alat }}" value="{{ $qtyDisewa }}">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       name="items[{{ $detail->kode_alat }}][baik]" 
                                                       id="item-baik-{{ $detail->kode_alat }}"
                                                       class="form-control text-center input-kondisi-baik" 
                                                       data-kode="{{ $detail->kode_alat }}"
                                                       value="{{ old('items.' . $detail->kode_alat . '.baik', $qtyDisewa) }}" 
                                                       min="0" 
                                                       max="{{ $qtyDisewa }}" 
                                                       required>
                                                <span class="input-group-text text-success bg-success-subtle">✓</span>
                                            </div>
                                            <small class="text-success d-block text-center mt-1" style="font-size: 0.68rem;">Siap sewa</small>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       name="items[{{ $detail->kode_alat }}][perlu_perbaikan]" 
                                                       id="item-perbaikan-{{ $detail->kode_alat }}"
                                                       class="form-control text-center input-kondisi-perbaikan" 
                                                       data-kode="{{ $detail->kode_alat }}"
                                                       value="{{ old('items.' . $detail->kode_alat . '.perlu_perbaikan', 0) }}" 
                                                       min="0" 
                                                       max="{{ $qtyDisewa }}" 
                                                       required>
                                                <span class="input-group-text text-warning bg-warning-subtle">🔧</span>
                                            </div>
                                            <small class="text-warning d-block text-center mt-1" style="font-size: 0.68rem;">Antrean servis</small>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       name="items[{{ $detail->kode_alat }}][rusak]" 
                                                       id="item-rusak-{{ $detail->kode_alat }}"
                                                       class="form-control text-center input-kondisi-rusak" 
                                                       data-kode="{{ $detail->kode_alat }}"
                                                       value="{{ old('items.' . $detail->kode_alat . '.rusak', 0) }}" 
                                                       min="0" 
                                                       max="{{ $qtyDisewa }}" 
                                                       required>
                                                <span class="input-group-text text-danger bg-danger-subtle">✕</span>
                                            </div>
                                            <small class="text-danger d-block text-center mt-1" style="font-size: 0.68rem;">Afkir / Rusak</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="ac-card-footer bg-light p-3 small text-muted">
                    <em>Perhatian: Total alat kembali (Baik + Perlu Perbaikan + Rusak) harus sama persis dengan jumlah alat yang disewa.</em>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Waktu Pengembalian, Kalkulasi Denda, dan Konfirmasi -->
        <div class="col-lg-5">
            <div class="ac-card mb-4">
                <div class="ac-card-header">
                    <h3 class="ac-card-title">⏱️ Tanggal Aktual Pengembalian</h3>
                </div>
                <div class="ac-card-body p-3">
                    <div class="mb-3">
                        <label for="tanggal_pengembalian_aktual" class="form-label small fw-bold text-dark">Tanggal Pengembalian Aktual <span class="text-danger">*</span></label>
                        <input type="date" 
                               name="tanggal_pengembalian_aktual" 
                               id="tanggal_pengembalian_aktual" 
                               class="form-control @error('tanggal_pengembalian_aktual') is-invalid @enderror" 
                               value="{{ old('tanggal_pengembalian_aktual', date('Y-m-d')) }}" 
                               min="{{ \Carbon\Carbon::parse($penyewaan->tanggal_sewa)->format('Y-m-d') }}"
                               required>
                        @error('tanggal_pengembalian_aktual')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Indikator Keterlambatan Real-time -->
                    <div id="box-keterlambatan" class="p-3 rounded border mb-2 {{ $hariTerlambat > 0 ? 'bg-danger-subtle border-danger-subtle' : 'bg-success-subtle border-success-subtle' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold small" id="text-status-terlambat">
                                @if($hariTerlambat > 0)
                                    ⚠️ Terlambat {{ $hariTerlambat }} Hari
                                @else
                                    ✓ Pengembalian Tepat Waktu
                                @endif
                            </span>
                            <span class="badge {{ $hariTerlambat > 0 ? 'bg-danger' : 'bg-success' }}" id="badge-hari-terlambat">
                                {{ $hariTerlambat }} Hari
                            </span>
                        </div>
                        <div class="small text-muted mt-1" id="subtext-keterlambatan">
                            Tarif sewa per hari paket ini: <strong>Rp {{ number_format($totalTarifHarian, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kalkulasi & Rincian Denda -->
            <div class="ac-card mb-4 border-warning">
                <div class="ac-card-header bg-warning-subtle text-dark">
                    <h3 class="ac-card-title mb-0">💰 Rincian & Alasan Denda</h3>
                </div>
                <div class="ac-card-body p-3">
                    <!-- Estimasi Denda Keterlambatan Otomatis -->
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom small">
                        <span class="text-muted">Denda Keterlambatan:</span>
                        <strong class="text-dark" id="display-denda-keterlambatan">
                            Rp {{ number_format($estimasiDendaTerlambat, 0, ',', '.') }}
                        </strong>
                    </div>

                    <!-- Input Denda Kerusakan / Penggantian Fisik -->
                    <div class="mb-3">
                        <label for="input_denda_kerusakan" class="form-label small fw-bold text-dark">Denda Kerusakan / Kerugian Fisik (Rp)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="number" 
                                   id="input_denda_kerusakan" 
                                   class="form-control" 
                                   value="0" 
                                   min="0" 
                                   step="1000" 
                                   placeholder="0">
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Isi jika terdapat unit rusak atau hilang yang membutuhkan penggantian biaya.</small>
                    </div>

                    <!-- Total Denda yang Dibebankan (Denda Keterlambatan + Kerusakan) -->
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark">Total Tagihan Denda:</span>
                            <h4 class="text-danger fw-bold mb-0 font-monospace" id="display-total-denda">
                                Rp {{ number_format($estimasiDendaTerlambat, 0, ',', '.') }}
                            </h4>
                        </div>
                        <input type="hidden" name="nominal_denda" id="nominal_denda" value="{{ old('nominal_denda', $estimasiDendaTerlambat) }}">
                        <input type="hidden" id="tarif-harian" value="{{ $totalTarifHarian }}">
                    </div>

                    <!-- Alasan Denda (Wajib jika denda > 0) -->
                    <div class="mb-3">
                        <label for="alasan_denda" class="form-label small fw-bold text-dark">
                            Rincian & Alasan Denda <span class="text-danger" id="bintang-alasan" style="{{ $estimasiDendaTerlambat > 0 ? '' : 'display:none;' }}">*</span>
                        </label>
                        <textarea name="alasan_denda" 
                                  id="alasan_denda" 
                                  rows="2" 
                                  class="form-control @error('alasan_denda') is-invalid @enderror" 
                                  placeholder="Contoh: Terlambat 1 hari pengembalian, 1 unit tenda robek pada zipper utama.">{{ old('alasan_denda', $estimasiDendaTerlambat > 0 ? "Keterlambatan {$hariTerlambat} hari dari batas jadwal kembali." : '') }}</textarea>
                        @error('alasan_denda')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Wajib dicantumkan dengan transparan agar penyewa memahami asal perhitungan denda.</small>
                    </div>

                    <!-- Catatan Tambahan Petugas -->
                    <div class="mb-2">
                        <label for="catatan" class="form-label small text-muted">Catatan Operasional (Opsional)</label>
                        <textarea name="catatan" id="catatan" rows="2" class="form-control small" placeholder="Catatan internal kondisi alat atau tindak lanjut perbaikan...">{{ old('catatan') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Tombol Konfirmasi Selesai Pengembalian -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg shadow d-flex align-items-center justify-content-center gap-2" id="btn-submit-pengembalian">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Selesaikan Pengembalian & Perbarui Stok
                </button>
                <a href="{{ route('admin.operasional.pengembalian') }}" class="btn btn-outline-secondary">
                    Batal
                </a>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tglAktualInput = document.getElementById('tanggal_pengembalian_aktual');
    const labelRencanaKembali = document.getElementById('label-rencana-kembali');
    const tglRencanaStr = labelRencanaKembali ? labelRencanaKembali.getAttribute('data-tgl-kembali') : '';
    const tarifHarian = parseFloat(document.getElementById('tarif-harian').value) || 0;
    const inputKerusakan = document.getElementById('input_denda_kerusakan');
    const displayDendaTerlambat = document.getElementById('display-denda-keterlambatan');
    const displayTotalDenda = document.getElementById('display-total-denda');
    const inputNominalDenda = document.getElementById('nominal_denda');
    const alasanDenda = document.getElementById('alasan_denda');
    const bintangAlasan = document.getElementById('bintang-alasan');
    const boxKeterlambatan = document.getElementById('box-keterlambatan');
    const textStatusTerlambat = document.getElementById('text-status-terlambat');
    const badgeHariTerlambat = document.getElementById('badge-hari-terlambat');

    function hitungDenda() {
        if (!tglRencanaStr || !tglAktualInput.value) return;

        const tglRencana = new Date(tglRencanaStr);
        const tglAktual = new Date(tglAktualInput.value);
        tglRencana.setHours(0,0,0,0);
        tglAktual.setHours(0,0,0,0);

        const diffTime = tglAktual - tglRencana;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        const hariTerlambat = diffDays > 0 ? diffDays : 0;

        if (hariTerlambat > 0) {
            boxKeterlambatan.className = 'p-3 rounded border mb-2 bg-danger-subtle border-danger-subtle';
            textStatusTerlambat.textContent = '⚠️ Terlambat ' + hariTerlambat + ' Hari';
            badgeHariTerlambat.className = 'badge bg-danger';
            badgeHariTerlambat.textContent = hariTerlambat + ' Hari';
        } else {
            boxKeterlambatan.className = 'p-3 rounded border mb-2 bg-success-subtle border-success-subtle';
            textStatusTerlambat.textContent = '✓ Pengembalian Tepat Waktu';
            badgeHariTerlambat.className = 'badge bg-success';
            badgeHariTerlambat.textContent = '0 Hari';
        }

        const dendaTerlambat = hariTerlambat * tarifHarian;
        const dendaKerusakan = parseFloat(inputKerusakan.value) || 0;
        const totalDenda = dendaTerlambat + dendaKerusakan;

        displayDendaTerlambat.textContent = 'Rp ' + dendaTerlambat.toLocaleString('id-ID');
        displayTotalDenda.textContent = 'Rp ' + totalDenda.toLocaleString('id-ID');
        inputNominalDenda.value = totalDenda;

        if (totalDenda > 0) {
            bintangAlasan.style.display = 'inline';
            alasanDenda.required = true;
            if (!alasanDenda.value.trim()) {
                let alasanOtomatis = [];
                if (hariTerlambat > 0) {
                    alasanOtomatis.push('Keterlambatan pengembalian ' + hariTerlambat + ' hari');
                }
                if (dendaKerusakan > 0) {
                    alasanOtomatis.push('Biaya perbaikan / ganti rugi fisik');
                }
                alasanDenda.value = alasanOtomatis.join('; ');
            }
        } else {
            bintangAlasan.style.display = 'none';
            alasanDenda.required = false;
        }
    }

    tglAktualInput.addEventListener('change', hitungDenda);
    inputKerusakan.addEventListener('input', hitungDenda);

    // Otomatis sinkronisasi input kondisi baik, perlu perbaikan, dan rusak
    function syncKondisi(kode, sourceField) {
        const max = parseInt(document.getElementById('max-qty-' + kode).value) || 0;
        const inputBaik = document.getElementById('item-baik-' + kode);
        const inputRepair = document.getElementById('item-perbaikan-' + kode);
        const inputRusak = document.getElementById('item-rusak-' + kode);

        let baik = parseInt(inputBaik.value) || 0;
        let repair = parseInt(inputRepair.value) || 0;
        let rusak = parseInt(inputRusak.value) || 0;

        if (baik < 0) baik = 0;
        if (repair < 0) repair = 0;
        if (rusak < 0) rusak = 0;

        if (sourceField === 'baik') {
            if (baik > max) baik = max;
            inputBaik.value = baik;
            let sisa = max - baik;
            if (repair > sisa) {
                repair = sisa;
                inputRepair.value = repair;
            }
            rusak = sisa - repair;
            inputRusak.value = rusak;
        } else if (sourceField === 'perbaikan') {
            if (repair > max) repair = max;
            inputRepair.value = repair;
            let sisa = max - repair;
            if (baik > sisa) {
                baik = sisa;
                inputBaik.value = baik;
            }
            rusak = sisa - baik;
            inputRusak.value = rusak;
        } else if (sourceField === 'rusak') {
            if (rusak > max) rusak = max;
            inputRusak.value = rusak;
            let sisa = max - rusak;
            if (baik > sisa) {
                baik = sisa;
                inputBaik.value = baik;
            }
            repair = sisa - baik;
            inputRepair.value = repair;
        }
    }

    document.querySelectorAll('.input-kondisi-baik').forEach(function(input) {
        input.addEventListener('input', function() {
            syncKondisi(this.getAttribute('data-kode'), 'baik');
        });
    });

    document.querySelectorAll('.input-kondisi-perbaikan').forEach(function(input) {
        input.addEventListener('input', function() {
            syncKondisi(this.getAttribute('data-kode'), 'perbaikan');
        });
    });

    document.querySelectorAll('.input-kondisi-rusak').forEach(function(input) {
        input.addEventListener('input', function() {
            syncKondisi(this.getAttribute('data-kode'), 'rusak');
        });
    });
});
</script>
@endsection
