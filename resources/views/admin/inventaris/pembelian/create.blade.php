@extends('layouts.app')

@section('title', 'Catat Pembelian Stok Supplier — Admin')
@section('page-title', 'Form Pengadaan Stok Supplier')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.inventaris.index') }}" class="text-decoration-none">Inventaris & Stok</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.inventaris.pembelian.index') }}" class="text-decoration-none">Pembelian Supplier</a></li>
            <li class="breadcrumb-item active" aria-current="page">Catat Pembelian</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Pencatatan Pembelian Stok dari Supplier</h2>
            <p class="text-muted small mb-0">Catat transaksi pengadaan inventaris dari supplier. Stok fisik alat akan bertambah otomatis dan tercatat pada mutasi stok.</p>
        </div>
        <div>
            <a href="{{ route('admin.inventaris.pembelian.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali ke Riwayat Pembelian
            </a>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong class="d-block mb-1">Gagal menyimpan transaksi pembelian:</strong>
        <ul class="mb-0 ps-3 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('admin.inventaris.pembelian.store') }}" method="POST" id="form-pembelian-supplier" onsubmit="const btn = document.getElementById('btn-submit-pembelian'); if (this.submitted) return false; this.submitted = true; btn.disabled = true; btn.innerHTML = '<span>⏳ Menyimpan Pembelian...</span>'; return true;">
    @csrf

    <div class="row g-4">
        <!-- Kolom Kiri: Informasi Dokumen & Supplier -->
        <div class="col-lg-4">
            <div class="ac-card mb-4">
                <div class="ac-card-header">
                    <h3 class="ac-card-title">📄 Dokumen Pengadaan</h3>
                </div>
                <div class="ac-card-body p-3">
                    <div class="mb-3">
                        <label for="no_pembelian" class="form-label small fw-bold text-dark">Nomor Pembelian / PO <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="no_pembelian" 
                               id="no_pembelian" 
                               class="form-control font-monospace @error('no_pembelian') is-invalid @enderror" 
                               value="{{ old('no_pembelian', $suggestedNo) }}" 
                               required>
                        @error('no_pembelian')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="tanggal_pembelian" class="form-label small fw-bold text-dark">Tanggal Pembelian <span class="text-danger">*</span></label>
                        <input type="date" 
                               name="tanggal_pembelian" 
                               id="tanggal_pembelian" 
                               class="form-control @error('tanggal_pembelian') is-invalid @enderror" 
                               value="{{ old('tanggal_pembelian', date('Y-m-d')) }}" 
                               required>
                        @error('tanggal_pembelian')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="catatan" class="form-label small fw-bold text-dark">Catatan Pembelian (Opsional)</label>
                        <textarea name="catatan" id="catatan" rows="3" class="form-control small" placeholder="Keterangan faktur vendor, nomor resi pengiriman, atau syarat garansi...">{{ old('catatan') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="ac-card">
                <div class="ac-card-header">
                    <h3 class="ac-card-title">🏢 Data Supplier / Vendor</h3>
                </div>
                <div class="ac-card-body p-3">
                    <div class="mb-3">
                        <label for="id_supplier" class="form-label small fw-bold text-dark">Pilih Supplier Terdaftar</label>
                        <select name="id_supplier" id="id_supplier" class="form-select @error('id_supplier') is-invalid @enderror">
                            <option value="">-- Pilih Supplier yang Ada --</option>
                            @foreach($supplierList as $s)
                                <option value="{{ $s->id_supplier }}" {{ old('id_supplier') == $s->id_supplier ? 'selected' : '' }}>
                                    {{ $s->nama_supplier }} ({{ $s->telepon ?? 'No Telp -' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="border-top pt-3">
                        <span class="small fw-bold text-muted d-block mb-2">&mdash; ATAU INPUT SUPPLIER BARU &mdash;</span>

                        <div class="mb-2">
                            <label for="nama_supplier_baru" class="form-label small text-dark">Nama Supplier Baru</label>
                            <input type="text" name="nama_supplier_baru" id="nama_supplier_baru" class="form-control form-control-sm" value="{{ old('nama_supplier_baru') }}" placeholder="Contoh: PT Eigerindo Multi Produk">
                        </div>

                        <div class="mb-2">
                            <label for="telepon_supplier" class="form-label small text-dark">Nomor Telepon</label>
                            <input type="text" name="telepon_supplier" id="telepon_supplier" class="form-control form-control-sm" value="{{ old('telepon_supplier') }}" placeholder="Contoh: 081298765432">
                        </div>

                        <div class="mb-2">
                            <label for="alamat_supplier" class="form-label small text-dark">Alamat Supplier</label>
                            <input type="text" name="alamat_supplier" id="alamat_supplier" class="form-control form-control-sm" value="{{ old('alamat_supplier') }}" placeholder="Kota / Alamat lengkap">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Rincian Daftar Alat yang Dibeli -->
        <div class="col-lg-8">
            <div class="ac-card mb-4">
                <div class="ac-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="ac-card-title">📦 Daftar Alat Outdoor yang Dibeli</h3>
                        <small class="text-muted">Masukkan kuantitas dan harga beli satuan per item</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1" id="btn-tambah-baris">
                        <span>➕</span>
                        <span>Tambah Baris Alat</span>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="table-items-beli">
                        <thead class="table-light small">
                            <tr>
                                <th>Alat Outdoor</th>
                                <th style="width: 120px;" class="text-center">Kuantitas</th>
                                <th style="width: 170px;" class="text-end">Harga Beli Satuan (Rp)</th>
                                <th style="width: 170px;" class="text-end">Subtotal (Rp)</th>
                                <th style="width: 50px;" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="tbody-items">
                            @php
                                $oldItems = old('items');
                                if (!is_array($oldItems) || empty($oldItems)) {
                                    $oldItems = [
                                        ['kode_alat' => $selectedKodeAlat ?? '', 'kuantitas' => 1, 'harga_beli_satuan' => 0]
                                    ];
                                }
                            @endphp
                            @foreach($oldItems as $idx => $it)
                                <tr class="item-row">
                                    <td>
                                        <select name="items[{{ $idx }}][kode_alat]" class="form-select form-select-sm select-alat" required>
                                            <option value="">-- Pilih Alat --</option>
                                            @foreach($alatList as $alat)
                                                <option value="{{ $alat->kode_alat }}" {{ ($it['kode_alat'] ?? '') === $alat->kode_alat ? 'selected' : '' }} data-nama="{{ $alat->nama_alat }}">
                                                    {{ $alat->kode_alat }} &mdash; {{ $alat->nama_alat }} (Stok saat ini: {{ $alat->stok }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $idx }}][kuantitas]" class="form-control form-control-sm text-center input-qty" value="{{ $it['kuantitas'] ?? 1 }}" min="1" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $idx }}][harga_beli_satuan]" class="form-control form-control-sm text-end input-harga" value="{{ $it['harga_beli_satuan'] ?? 0 }}" min="0" step="500" required>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-dark cell-subtotal">
                                        Rp {{ number_format(((float)($it['kuantitas'] ?? 1)) * ((float)($it['harga_beli_satuan'] ?? 0)), 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-hapus-baris" title="Hapus baris" {{ count($oldItems) <= 1 ? 'disabled' : '' }}>&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end fs-6">Total Pembelian Supplier:</th>
                                <th class="text-end font-monospace text-success fs-5" id="total-pembelian-display">
                                    Rp 0
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Konfirmasi & Submit Button -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg shadow d-flex align-items-center justify-content-center gap-2" id="btn-submit-pembelian">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Simpan Pembelian Supplier & Perbarui Stok Fisik
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('tbody-items');
    const btnTambah = document.getElementById('btn-tambah-baris');
    const displayTotal = document.getElementById('total-pembelian-display');
    let rowIndex = {{ max(1, count($oldItems)) }};

    function hitungTotal() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(function(row) {
            const qty = parseFloat(row.querySelector('.input-qty').value) || 0;
            const harga = parseFloat(row.querySelector('.input-harga').value) || 0;
            const subtotal = qty * harga;
            row.querySelector('.cell-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            total += subtotal;
        });
        displayTotal.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    tbody.addEventListener('input', function(e) {
        if (e.target.classList.contains('input-qty') || e.target.classList.contains('input-harga')) {
            hitungTotal();
        }
    });

    tbody.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-hapus-baris')) {
            const rows = document.querySelectorAll('.item-row');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
                hitungTotal();
            }
        }
    });

    btnTambah.addEventListener('click', function() {
        const firstRow = document.querySelector('.item-row');
        const newRow = firstRow.cloneNode(true);
        
        newRow.querySelector('.select-alat').name = 'items[' + rowIndex + '][kode_alat]';
        newRow.querySelector('.select-alat').value = '';
        newRow.querySelector('.input-qty').name = 'items[' + rowIndex + '][kuantitas]';
        newRow.querySelector('.input-qty').value = '1';
        newRow.querySelector('.input-harga').name = 'items[' + rowIndex + '][harga_beli_satuan]';
        newRow.querySelector('.input-harga').value = '0';
        newRow.querySelector('.cell-subtotal').textContent = 'Rp 0';
        newRow.querySelector('.btn-hapus-baris').removeAttribute('disabled');

        tbody.appendChild(newRow);
        rowIndex++;
        hitungTotal();
    });

    hitungTotal();
});
</script>
@endsection
