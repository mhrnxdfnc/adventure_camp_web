@extends('layouts.app')

@section('title', 'Penyerahan Alat (Pengambilan) — Admin')
@section('page-title', 'Pengambilan Alat Camping')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pengambilan Alat</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Daftar Pesanan Siap Diambil</h2>
            <p class="text-muted small mb-0">Pesanan yang telah disetujui dan pembayaran telah diverifikasi Lunas siap untuk serah terima alat kepada penyewa.</p>
        </div>
    </div>
</div>

<!-- Kartu Informasi SOP Penyerahan -->
<div class="alert alert-info border-info d-flex align-items-start gap-2 mb-4 p-3" style="border-radius: 10px;">
    <span style="font-size: 1.25rem;">ℹ️</span>
    <div>
        <strong class="d-block mb-1 text-dark">Pedoman Operasional Penyerahan Alat:</strong>
        <p class="mb-0 small text-dark">
            Pastikan kartu identitas dan nomor transaksi penyewa telah diperiksa. Hitung dan periksa kondisi fisik alat bersama pelanggan sebelum mencatat penyerahan. Status pesanan akan berubah menjadi <strong>Sedang Disewa</strong> dan stok inventaris akan dialokasikan.
        </p>
    </div>
</div>

<!-- Tabel Pesanan Siap Diambil -->
<div class="ac-card">
    <div class="ac-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="ac-card-title">Antrean Serah Terima ({{ $pesananList->count() }} Pesanan)</h3>
            <small class="text-muted">Hanya menampilkan pesanan berstatus Siap Diambil dengan pembayaran Lunas</small>
        </div>
        <form action="{{ route('admin.operasional.pengambilan') }}" method="GET" class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="max-width: 260px;">
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari No. TRX / Pelanggan...">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
                @if($search !== '')
                    <a href="{{ route('admin.operasional.pengambilan') }}" class="btn btn-outline-danger" title="Reset">&times;</a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="table-pengambilan-admin">
            <thead class="table-light small">
                <tr>
                    <th>No. Transaksi</th>
                    <th>Pelanggan</th>
                    <th>Jadwal Sewa</th>
                    <th>Item Alat Camping</th>
                    <th>Status Bayar</th>
                    <th>Status Pesanan</th>
                    <th class="text-center" style="min-width: 150px;">Aksi Admin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pesananList as $trx)
                    <tr>
                        <td>
                            <strong class="text-dark font-monospace d-block">{{ $trx->no_transaksi }}</strong>
                            <small class="text-muted">{{ $trx->pembayaran?->no_pembayaran ?? '-' }}</small>
                        </td>
                        <td>
                            <div class="font-weight-bold text-dark">{{ $trx->pelanggan->nama_pelanggan ?? 'Pelanggan #' . $trx->id_pelanggan }}</div>
                            <small class="text-muted">{{ $trx->pelanggan->nomor_telepon ?? '-' }}</small>
                        </td>
                        <td>
                            <span class="small text-dark d-block">
                                {{ \Carbon\Carbon::parse($trx->tanggal_sewa)->format('d/m/Y') }} &rarr; {{ \Carbon\Carbon::parse($trx->tanggal_kembali)->format('d/m/Y') }}
                            </span>
                            <span class="badge bg-secondary-subtle text-dark border" style="font-size: 0.7rem;">
                                Durasi: {{ $trx->durasi_hari }} Hari
                            </span>
                        </td>
                        <td>
                            <ul class="list-unstyled mb-0 small">
                                @foreach($trx->detailPenyewaan as $detail)
                                    <li>
                                        • <strong>{{ $detail->alat->nama_alat ?? $detail->kode_alat }}</strong> 
                                        <span class="badge bg-light text-dark border ms-1">{{ $detail->jumlah }} unit</span>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                ✓ Lunas (Rp {{ number_format($trx->pembayaran?->jumlah_pembayaran ?? $trx->total_biaya, 0, ',', '.') }})
                            </span>
                        </td>
                        <td>
                            <x-status-badge :status="$trx->status_penyewaan" />
                        </td>
                        <td class="text-center">
                            <button type="button" 
                                    class="btn btn-sm btn-success py-1 px-3 font-weight-bold d-inline-flex align-items-center gap-1"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalSerahTerima{{ str_replace('-', '', $trx->no_transaksi) }}"
                                    title="Serahkan alat camping kepada pelanggan">
                                <span>📦</span>
                                <span>Serahkan Alat</span>
                            </button>
                        </td>
                    </tr>

                    <!-- Modal Serah Terima Alat -->
                    <div class="modal fade" id="modalSerahTerima{{ str_replace('-', '', $trx->no_transaksi) }}" tabindex="-1" aria-labelledby="modalSerahTerimaLabel{{ str_replace('-', '', $trx->no_transaksi) }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 shadow">
                                <form action="{{ route('admin.operasional.proses-pengambilan', $trx->no_transaksi) }}" method="POST">
                                    @csrf
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title h6 font-weight-bold mb-0" id="modalSerahTerimaLabel{{ str_replace('-', '', $trx->no_transaksi) }}">
                                            📦 Serah Terima Alat Camping #{{ $trx->no_transaksi }}
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4 text-start">
                                        <div class="row g-3 mb-3">
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small font-weight-bold text-dark">Tanggal & Jam Pengambilan:</label>
                                                <input type="datetime-local" 
                                                       name="tanggal_pengambilan" 
                                                       class="form-control" 
                                                       value="{{ now()->format('Y-m-d\TH:i') }}" 
                                                       required>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small font-weight-bold text-dark">Nama Penerima / Penyewa:</label>
                                                <input type="text" 
                                                       name="nama_penerima" 
                                                       class="form-control" 
                                                       value="{{ $trx->pelanggan->nama_pelanggan ?? '' }}" 
                                                       required>
                                            </div>
                                        </div>

                                        <h6 class="font-weight-bold text-dark mb-2">Pemeriksaan Jumlah Alat yang Diserahkan:</h6>
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-bordered align-middle mb-0">
                                                <thead class="table-light small">
                                                    <tr>
                                                        <th>Nama Alat</th>
                                                        <th class="text-center">Jumlah Dipesan</th>
                                                        <th class="text-center" style="width: 180px;">Jumlah Diserahkan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($trx->detailPenyewaan as $detail)
                                                        <tr>
                                                            <td>
                                                                <strong>{{ $detail->alat->nama_alat ?? $detail->kode_alat }}</strong>
                                                                <small class="text-muted d-block">Kode: {{ $detail->kode_alat }}</small>
                                                            </td>
                                                            <td class="text-center font-weight-bold">{{ $detail->jumlah }} unit</td>
                                                            <td>
                                                                <input type="number" 
                                                                       name="items[{{ $detail->kode_alat }}][jumlah_diserahkan]" 
                                                                       class="form-control form-control-sm text-center font-weight-bold" 
                                                                       value="{{ $detail->jumlah }}" 
                                                                       min="1" 
                                                                       max="{{ $detail->jumlah }}" 
                                                                       required>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small font-weight-bold text-dark">Catatan Serah Terima (Opsional):</label>
                                            <textarea name="catatan" class="form-control" rows="2" placeholder="Contoh: Kondisi tenda sudah dicek lengkap bersama penyewa."></textarea>
                                        </div>

                                        <div class="alert alert-warning py-2 px-3 small mb-0 border-warning">
                                            ⚠️ Penyerahan alat akan mengubah status pesanan menjadi <strong>Sedang Disewa</strong> dan mengurangi stok inventaris aktif.
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light p-3">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-sm btn-success font-weight-bold px-3">
                                            ✓ Konfirmasi Penyerahan Alat
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <x-empty-state 
                                title="Tidak Ada Pesanan Siap Diambil" 
                                message="Belum ada pesanan yang disetujui dan telah melunasi pembayaran saat ini." 
                                icon="⛺"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
