@extends('layouts.app')

@section('title', 'Showcase Fondasi Antarmuka UI')
@section('page-title', 'Showcase Komponen UI')

@section('content')
<div class="ac-card p-4 mb-4 border-success-subtle bg-success-subtle text-success-emphasis">
    <div class="d-flex align-items-center gap-3">
        <span class="fs-1">🎨</span>
        <div>
            <h3 class="font-weight-bold mb-1">Showcase Komponen & Fondasi Antarmuka</h3>
            <p class="mb-0 small">
                Halaman ini memvalidasi kelengkapan komponen Blade, Bootstrap 5, responsivitas mobile/desktop, dan penanganan status sesuai ketentuan <code>CONTEXT.md</code>.
            </p>
        </div>
    </div>
</div>

<!-- 1. STATUS BADGES -->
<div class="ac-card mb-4">
    <div class="ac-card-header">
        <h3 class="ac-card-title">1. Komponen Status Badge (<code>&lt;x-status-badge /&gt;</code>)</h3>
    </div>
    <div class="ac-card-body">
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <h6 class="text-uppercase text-muted small font-weight-bold mb-2">Status Pesanan:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <x-status-badge status="Menunggu Konfirmasi" />
                    <x-status-badge status="Disetujui" />
                    <x-status-badge status="Menunggu Pembayaran" />
                    <x-status-badge status="Siap Diambil" />
                    <x-status-badge status="Sedang Disewa" />
                    <x-status-badge status="Selesai" />
                    <x-status-badge status="Ditolak" />
                    <x-status-badge status="Dibatalkan" />
                </div>
            </div>

            <div class="col-12 col-md-4">
                <h6 class="text-uppercase text-muted small font-weight-bold mb-2">Status Pembayaran:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <x-status-badge status="Belum Dibayar" />
                    <x-status-badge status="Menunggu Verifikasi" />
                    <x-status-badge status="Lunas" />
                    <x-status-badge status="Ditolak" />
                </div>
            </div>

            <div class="col-12 col-md-4">
                <h6 class="text-uppercase text-muted small font-weight-bold mb-2">Kondisi Alat Camping:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <x-status-badge status="Baik" />
                    <x-status-badge status="Perlu Perbaikan" />
                    <x-status-badge status="Rusak" />
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. ALERTS & VALIDATION -->
<div class="ac-card mb-4">
    <div class="ac-card-header">
        <h3 class="ac-card-title">2. Komponen Alert & Validasi Input Gagal</h3>
    </div>
    <div class="ac-card-body">
        <!-- Success Alert -->
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3 shadow-sm border-0" role="alert" style="border-radius:10px;">
            <svg width="20" height="20" fill="currentColor" class="flex-shrink-0" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <div class="flex-grow-1">
                <strong>Berhasil:</strong> Tindakan penyimpanan data berhasil dilakukan.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>

        <!-- Warning Alert -->
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 mb-3 shadow-sm border-0" role="alert" style="border-radius:10px;">
            <svg width="20" height="20" fill="currentColor" class="flex-shrink-0" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <div class="flex-grow-1">
                <strong>Peringatan:</strong> Stok alat untuk periode sewa tersebut tersisa 1 unit lagi.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>

        <!-- Error Alert -->
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-3 shadow-sm border-0" role="alert" style="border-radius:10px;">
            <svg width="20" height="20" fill="currentColor" class="flex-shrink-0" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            <div class="flex-grow-1">
                <strong>Gagal:</strong> Nominal pembayaran tidak boleh melebihi sisa tagihan transaksi.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>

        <!-- Validation Error Box -->
        <div class="alert alert-danger mb-0 shadow-sm border-0" role="alert" style="border-radius:10px;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <svg width="20" height="20" fill="currentColor" class="flex-shrink-0" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <strong>Validasi Gagal (422 Unprocessable Entity):</strong>
            </div>
            <ul class="mb-0 ps-4 small">
                <li>Tanggal kembali harus berada setelah tanggal mulai sewa.</li>
                <li>Jumlah alat yang disewa minimal 1 unit.</li>
                <li>Nomor telepon pelanggan wajib diisi dengan format yang benar.</li>
            </ul>
        </div>
    </div>
</div>

<!-- 3. DATA TABLE -->
<div class="mb-4">
    <x-data-table title="3. Komponen Data Table (Responsive & Filterable)" subtitle="Menampilkan data dengan header pencarian, badge status, dan pagination" searchId="demoSearch">
        <x-slot:actions>
            <button type="button" class="btn btn-sm btn-ac-primary disabled" title="Aksi tambah data akan diaktifkan pada modul CRUD masing-masing">
                + Tambah Data (Tahap 2)
            </button>
        </x-slot:actions>

        <thead>
            <tr>
                <th>Kode Alat</th>
                <th>Nama Perlengkapan</th>
                <th>Kategori</th>
                <th>Stok Siap Sewa</th>
                <th>Tarif / Hari</th>
                <th>Kondisi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>ALT001</strong></td>
                <td>Tenda Dome 4 Orang</td>
                <td>Tenda</td>
                <td>1 unit</td>
                <td>Rp 50.000</td>
                <td><x-status-badge status="Baik" /></td>
            </tr>
            <tr>
                <td><strong>ALT002</strong></td>
                <td>Sleeping Bag Polar</td>
                <td>Perlengkapan Tidur</td>
                <td>4 unit</td>
                <td>Rp 25.000</td>
                <td><x-status-badge status="Baik" /></td>
            </tr>
            <tr>
                <td><strong>ALT003</strong></td>
                <td>Carrier 60L Consina</td>
                <td>Tas Gunung</td>
                <td>5 unit</td>
                <td>Rp 40.000</td>
                <td><x-status-badge status="Baik" /></td>
            </tr>
        </tbody>

        <x-slot:pagination>
            <span class="small text-muted">Menampilkan 1 sampai 3 dari 3 data</span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                <li class="page-item active"><span class="page-link bg-success border-success">1</span></li>
                <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
            </ul>
        </x-slot:pagination>
    </x-data-table>
</div>

<!-- 4. EMPTY, LOADING, & ERROR STATES -->
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <h5 class="font-weight-bold mb-2">4. State: Data Kosong</h5>
        <x-empty-state 
            title="Tidak Ada Riwayat" 
            message="Data tidak ditemukan untuk parameter pencarian yang Anda tentukan." 
            icon="📋"
        />
    </div>

    <div class="col-12 col-lg-4">
        <h5 class="font-weight-bold mb-2">5. State: Loading Skeleton</h5>
        <x-loading-state label="Sedang mengambil data sewa..." :rows="2" />
    </div>

    <div class="col-12 col-lg-4">
        <h5 class="font-weight-bold mb-2">6. State: Error Handler</h5>
        <x-error-state 
            title="Gagal Terhubung ke Database" 
            message="Koneksi terputus. Pastikan service MySQL Laragon aktif pada port 3306." 
            code="ERR_DB_CONN" 
            retryUrl="{{ url('/ui-showcase') }}"
        />
    </div>
</div>
@endsection
